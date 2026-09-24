<?php
// Общие части страниц: данные сайта, ссылки, разметка текстов, шапка и подвал, формы, оболочка страницы.
declare(strict_types=1);

// Данные сайта на время одной сборки
final class S
{
    public static string $base = '';
    public static string $siteUrl = '';
    public static bool $noindex = false;
    public static array $company = [];
    public static array $site = [];
    public static array $prices = [];
    public static array $reviews = [];
    public static array $videos = [];
    public static array $photos = [];
    public static array $photoIndex = [];
    public static array $heroDims = [];
    public static array $structure = [];
    public static array $services = [];   // все видимые услуги в порядке structure.servicesOrder
    public static array $svcBySlug = [];
    public static array $menu = [];       // услуги в меню, на главной и в подвале
    public static array $placeholders = [];
    public static int $formSeq = 0;
    public static string $critical = '';
    public static string $cssVer = '';
}

function load_site_data(): void
{
    $d = cfg('data_dir');
    S::$base = rtrim((string)cfg('base_path'), '/');
    S::$siteUrl = rtrim((string)cfg('site_url'), '/');
    S::$noindex = (bool)cfg('noindex');
    S::$company = read_json("$d/company.json");
    S::$site = read_json("$d/site.json");
    S::$prices = read_json("$d/prices.json");
    S::$reviews = read_json("$d/reviews.json")['items'];
    S::$videos = read_json("$d/videos.json");
    S::$photos = read_json("$d/photos.json");
    S::$heroDims = read_json("$d/hero.json");
    S::$structure = read_json("$d/structure.json");
    S::$photoIndex = [];
    foreach (S::$photos as $list) foreach ($list as $p) S::$photoIndex[$p['id']] = $p;

    $files = glob("$d/services/*.json") ?: [];
    sort($files, SORT_STRING);
    $svc = array_map('read_json', $files);
    // Порядок — из structure.json; услуги, которых нет в списке, идут в конце
    $order = array_flip(S::$structure['servicesOrder'] ?? []);
    usort($svc, fn($a, $b) => [$order[$a['slug']] ?? PHP_INT_MAX, $a['slug']] <=> [$order[$b['slug']] ?? PHP_INT_MAX, $b['slug']]);
    S::$services = array_values(array_filter($svc, fn($s) => empty($s['hidden'])));
    S::$svcBySlug = [];
    foreach (S::$services as $s) S::$svcBySlug[$s['slug']] = $s;
    S::$menu = array_values(array_filter(S::$services, fn($s) => !empty($s['menu'])));
    S::$placeholders = [];
    S::$formSeq = 0;
}

// Экранирование: esc — для атрибутов, t — для текста между тегами (кавычки там не мешают)
function esc($s): string
{
    return str_replace(['&', '<', '>', '"'], ['&amp;', '&lt;', '&gt;', '&quot;'], (string)($s ?? ''));
}
function t($s): string
{
    return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], (string)($s ?? ''));
}
function join_map(array $list, callable $fn, string $sep = ''): string { return implode($sep, array_map($fn, $list, array_keys($list))); }

// Пробельные символы как в JavaScript (\s там включает неразрывный пробел и др.)
const WS = '[\s\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]';
function js_trim(string $s): string { return preg_replace('~^' . WS . '+|' . WS . '+$~u', '', $s); }

// Плейсхолдер: видимый жёлтый маркер, если значение не задано
function val(string $key, $value, bool $html = true): string
{
    if ($value !== null && $value !== '' && $value !== false) return $html ? esc($value) : (string)$value;
    S::$placeholders[$key] = true;
    return $html ? '<mark class="ph">{{' . $key . '}}</mark>' : '{{' . $key . '}}';
}
function site(): string { return (S::$siteUrl !== '' ? S::$siteUrl : 'https://{{ДОМЕН}}') . S::$base; }
function url(string $p): string { return site() . $p; }
function phone1(): array { return S::$company['phones'][0]; }
function phone2(): array { return S::$company['phones'][1] ?? S::$company['phones'][0]; }

// Подстановки в текстах: цены, рейтинг, телефоны. Работают в любом тексте сайта.
function subst(string $s): string
{
    $c = S::$company;
    return strtr($s, [
        '@@PRICE_FROM@@' => S::$prices['from'], '@@PRICE_LAND@@' => S::$prices['land'],
        '@@RATING@@' => $c['rating']['value'], '@@REVIEWS@@' => (string)$c['rating']['count'],
        '@@TEL1@@' => t(phone1()['display']), '@@TEL2@@' => t(phone2()['display']),
    ]);
}

function phone_link(array $p, string $cls = '', ?string $inner = null): string
{
    return '<a class="' . $cls . '" href="tel:' . $p['tel'] . '" data-goal="phone_click">' . ($inner ?? esc($p['display'])) . '</a>';
}

// ---------- разметка текстов из админки ----------
// Абзацы — через пустую строку; «## Заголовок» (можно «{#якорь}» в конце); строки «- пункт» — список;
// **жирный**; [текст ссылки](/адрес/); @@PHONE1@@ / @@PHONE2@@ — телефон ссылкой.
function md_inline(string $s): string
{
    $s = t($s);
    $s = str_replace(['@@PHONE1@@', '@@PHONE2@@'], [phone_link(phone1(), '', esc(phone1()['display'])), phone_link(phone2(), '', esc(phone2()['display']))], $s);
    $s = preg_replace_callback('~\[([^\]\n]+)\]\(([^)\s]+)\)~u', function ($m) {
        $href = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!preg_match('~^(/|#|https?://|tel:|mailto:)~i', $href)) return $m[0];
        if (!link_visible(preg_replace('~#.*$~', '', $href))) return $m[1]; // страница скрыта или удалена — остаётся просто текст
        return '<a href="' . esc($href) . '">' . $m[1] . '</a>';
    }, $s);
    return preg_replace('~\*\*(.+?)\*\*~u', '<b>$1</b>', $s);
}

function md_blocks(string $text): string
{
    $out = []; $para = []; $list = [];
    $flush = function () use (&$out, &$para, &$list) {
        if ($para) { $out[] = '<p>' . md_inline(implode(' ', $para)) . '</p>'; $para = []; }
        if ($list) { $out[] = "<ul>\n" . implode("\n", array_map(fn($x) => '<li>' . md_inline($x) . '</li>', $list)) . "\n</ul>"; $list = []; }
    };
    foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $text)) as $line) {
        $line = trim($line);
        if ($line === '') { $flush(); continue; }
        if (str_starts_with($line, '## ')) {
            $flush();
            $h = trim(substr($line, 3)); $id = '';
            if (preg_match('~\s*\{#([a-z0-9_-]+)\}$~i', $h, $m)) { $id = $m[1]; $h = substr($h, 0, -strlen($m[0])); }
            $out[] = '<h2' . ($id !== '' ? ' id="' . $id . '"' : '') . '>' . md_inline($h) . '</h2>';
            continue;
        }
        if (str_starts_with($line, '- ')) { if ($para) { $l = $list; $list = []; $flush(); $list = $l; } $list[] = trim(substr($line, 2)); continue; }
        if ($list) $flush();
        $para[] = $line;
    }
    $flush();
    return implode("\n", $out);
}
// Текст без тегов (для микроразметки FAQ)
function strip_html(string $html): string { return js_trim(preg_replace('~' . WS . '+~u', ' ', preg_replace('~<[^>]+>~', ' ', $html))); }

// ---------- фото ----------
function photo(string $id): array
{
    if (!isset(S::$photoIndex[$id])) throw new RuntimeException('Нет фото ' . $id);
    return S::$photoIndex[$id];
}
function photo_src(array $p): string { return '/' . $p['file']; }

function figure_html(?array $f): string
{
    if (!$f || empty($f['photo'])) return '';
    $p = photo($f['photo']);
    return '<figure style="margin:18px 0 0' . (!empty($f['narrow']) ? ';max-width:640px' : '') . '"><img src="' . photo_src($p) . '" width="' . $p['w'] . '" height="' . $p['h'] . '" alt="' . esc($p['alt']) . '" loading="lazy" style="border-radius:14px"><figcaption style="color:var(--muted);font-size:.9rem;margin-top:6px">' . t($f['caption'] ?? '') . '</figcaption></figure>';
}

// ---------- логотип, шапка, подвал ----------
function logo(string $cls = ''): string
{
    return '<a class="logo ' . $cls . '" href="/" title="На главную"><img src="/images/logo.webp" width="480" height="194" alt="ДЕЗЦЕНТР SANITAR"></a>';
}

const PAGE_LINKS = [['/ceny/', 'Цены'], ['/dokumenty/', 'Документы'], ['/otzyvy/', 'Отзывы'], ['/kontakty/', 'Контакты']];

function site_header(string $current): string
{
    $c = S::$company;
    $groupActive = (bool)array_filter(S::$menu, fn($s) => "/{$s['slug']}/" === $current);
    $sub = join_map(S::$menu, fn($s) => '<a href="/' . $s['slug'] . '/"' . ($current === "/{$s['slug']}/" ? ' aria-current="page"' : '') . '>' . icon($s['icon']) . '<span>' . t($s['name']) . '</span></a>');
    $links = join_map(PAGE_LINKS, fn($l) => '<a href="' . $l[0] . '"' . ($current === $l[0] ? ' aria-current="page"' : '') . '>' . $l[1] . '</a>');
    $mob = join_map(array_merge(array_map(fn($s) => ["/{$s['slug']}/", t($s['name'])], S::$menu), PAGE_LINKS), fn($l) => '<a href="' . $l[0] . '">' . $l[1] . icon('arrow') . '</a>');
    $p = phone1();
    return '<a class="skip" href="#main">Перейти к содержимому</a>
<header class="header">
  <div class="container header__in">
    ' . logo() . '
    <nav class="nav" aria-label="Основное меню">
      <div class="nav__group">
        <button type="button" class="nav__btn' . ($groupActive ? ' is-current' : '') . '" aria-haspopup="true" aria-expanded="false">Услуги ' . icon('arrow', 'nav__chev') . '</button>
        <div class="nav__sub">' . $sub . '</div>
      </div>
      ' . $links . '
    </nav>
    ' . phone_link($p, 'header__phone', icon('phone') . '<span>' . esc($p['display']) . '</span>') . '
    <a class="icon-btn header__soc header__soc--max" href="' . esc(($c['maxChat'] ?? '') !== '' ? $c['maxChat'] : $c['max']) . '" target="_blank" rel="noopener" aria-label="Написать в MAX" title="Написать в MAX" data-goal="max_click">MAX</a>
    <a class="icon-btn header__soc" href="' . esc(($c['telegramChat'] ?? '') !== '' ? $c['telegramChat'] : $c['telegram']) . '" target="_blank" rel="noopener" aria-label="Написать в Telegram" title="Написать в Telegram" data-goal="telegram_click">' . icon('telegram') . '</a>
    <button type="button" class="btn btn--lime btn--sm header__cta" data-open-modal>Вызвать специалиста</button>
    <button type="button" class="icon-btn burger" aria-label="Открыть меню" aria-expanded="false" aria-controls="mnav" data-burger>' . icon('menu') . '</button>
  </div>
  <nav class="mnav" id="mnav" aria-label="Мобильное меню" hidden>
    ' . $mob . '
    <button type="button" class="btn btn--lime btn--block" data-open-modal>Вызвать специалиста</button>
  </nav>
</header>';
}

function site_footer(): string
{
    $c = S::$company;
    $p0 = phone1(); $p1 = phone2();
    $svc = join_map(S::$menu, fn($s) => '<li><a href="/' . $s['slug'] . '/">' . t($s['name']) . '</a></li>');
    $credit = 'https://sitomika.ru/?utm_source=' . $c['slug'] . '&amp;utm_medium=footer&amp;utm_campaign=client-sites';
    $email = !empty($c['email']) ? '<a href="mailto:' . esc($c['email']) . '">' . esc($c['email']) . '</a>' : val('EMAIL', $c['email'] ?? '');
    return '<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        ' . logo('logo--footer') . '
        <p style="margin-top:14px">' . t(S::$site['common']['footerText']) . '</p>
        <p class="footer__soc">Наши группы с полезными материалами:</p>
        <div class="social">
          <a href="' . esc($c['telegram']) . '" target="_blank" rel="noopener" data-goal="telegram_click">' . icon('telegram') . ' Telegram-канал</a>
          <a href="' . esc($c['max']) . '" target="_blank" rel="noopener" data-goal="max_click">' . icon('max') . ' Группа в MAX</a>
        </div>
      </div>
      <div><p class="footer__h">Услуги</p><ul>' . $svc . '</ul></div>
      <div><p class="footer__h">Компания</p><ul>
        <li><a href="/ceny/">Цены</a></li><li><a href="/dokumenty/">Документы</a></li><li><a href="/otzyvy/">Отзывы</a></li><li><a href="/kontakty/">Контакты</a></li></ul></div>
      <div><p class="footer__h">Контакты</p>
        <p>' . phone_link($p0, '', esc($p0['display'])) . '<br>' . phone_link($p1, '', esc($p1['display'])) . '</p>
        <p>Адрес: ' . val('АДРЕС', $c['address']) . '</p>
        <p>Режим работы: ' . val('ЧАСЫ_РАБОТЫ', $c['hours']) . '</p>
        <p>E-mail: ' . $email . '</p>
      </div>
    </div>
    <div class="footer__bottom">
      <span>© ' . date('Y') . ' Дезцентр SANITAR</span>
      <span class="footer__bottom-links">
        <a href="/politika-konfidencialnosti/">Политика конфиденциальности</a>
        <a href="/politika-konfidencialnosti/#soglasie">Согласие на обработку данных</a>
        <a class="footer-credit" href="' . $credit . '" target="_blank" rel="noopener">Разработано в sitomika.ru</a>
      </span>
    </div>
  </div>
</footer>';
}

function bottom_bar(): string
{
    $p = phone1();
    return '<div class="bar" role="navigation" aria-label="Быстрая связь">
  <a class="btn btn--lime" href="tel:' . $p['tel'] . '" data-goal="phone_click">' . icon('phone') . ' Позвонить</a>
  <button type="button" class="btn btn--ghost" data-open-modal>' . icon('clipboard') . ' Заявка</button>
</div>';
}

function modals(): string
{
    return '<dialog id="lead-modal" aria-labelledby="lead-modal-title">
  <div class="modal">
    <button type="button" class="icon-btn modal__x" data-close aria-label="Закрыть">' . icon('close') . '</button>
    <h2 id="lead-modal-title">Вызвать специалиста</h2>
    <p style="color:var(--muted)">Оставьте телефон — перезвоним и рассчитаем стоимость.</p>
    ' . lead_form(['variant' => 'modal']) . '
  </div>
</dialog>
<dialog id="lightbox" class="lightbox" aria-label="Просмотр документа">
  <button type="button" class="icon-btn modal__x" data-close aria-label="Закрыть">' . icon('close') . '</button>
  <img alt="" width="800" height="1000">
  <p></p>
</dialog>';
}

function cookie_banner(): string
{
    $text = !empty(S::$company['metrikaId'])
        ? 'Сайт использует файлы cookie для работы и Яндекс.Метрику (статистика и вебвизор). Продолжая, вы соглашаетесь с этим.'
        : 'Сайт использует файлы cookie для корректной работы.';
    return '<div class="cookie" id="cookie" role="region" aria-label="Уведомление о cookie" hidden>
  <p>' . $text . ' <a href="/politika-konfidencialnosti/#cookie-policy">Подробнее</a></p>
  <button type="button" class="btn btn--blue btn--sm" data-cookie-ok>Понятно</button>
</div>';
}

// ---------- формы ----------
// Заявки принимает app/lead.php (заглушка public/lead.php в корне сайта)
const FORM_ENDPOINT = '/lead.php';
const OBJECT_OPTS = ['Квартира', 'Дом', 'Участок', 'Коммерческий объект'];
function service_opts(): array { return array_merge(array_map(fn($s) => $s['name'], S::$menu), ['Не знаю, нужна консультация']); }

function lead_form(array $o = []): string
{
    $variant = $o['variant'] ?? 'full'; $service = $o['service'] ?? ''; $cta = $o['cta'] ?? 'Отправить заявку';
    $n = ++S::$formSeq;
    $p = fn($s) => "f$n-$s";
    $opts = fn($arr, $sel) => join_map($arr, fn($x) => '<option' . ($x === $sel ? ' selected' : '') . '>' . t($x) . '</option>');
    $action = FORM_ENDPOINT;
    $nameField = '<div class="field"><label for="' . $p('name') . '">Имя <i>*</i></label><input class="input" id="' . $p('name') . '" name="name" type="text" autocomplete="given-name" required minlength="2" maxlength="80"><span class="err" data-err="name"></span></div>';
    $phoneField = '<div class="field"><label for="' . $p('phone') . '">Телефон <i>*</i></label><input class="input" id="' . $p('phone') . '" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" required><span class="err" data-err="phone"></span></div>';
    $serviceField = '<div class="field"><label for="' . $p('service') . '">Услуга</label><select class="input" id="' . $p('service') . '" name="service">' . $opts(service_opts(), $service) . '</select></div>';
    $consent = '<label class="consent"><input type="checkbox" name="consent" value="1" required><span>Я согласен(-на) на обработку персональных данных в соответствии с <a href="/politika-konfidencialnosti/" target="_blank" rel="noopener">политикой конфиденциальности</a>.</span></label><span class="err" data-err="consent"></span>';
    $hp = '<div class="hp" aria-hidden="true"><label>Не заполняйте это поле <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div><input type="hidden" name="t" value=""><input type="hidden" name="source" value="' . $variant . '">';
    $btn = '<button class="btn btn--lime btn--block" type="submit" disabled><span class="spinner" aria-hidden="true"></span><span class="btn__t">' . $cta . '</span></button>';
    $status = '<div class="form__status" role="status" aria-live="polite"></div>';
    $submit = $btn . $status;

    if ($variant === 'mini') {
        return '<form class="mini-form" data-lead novalidate method="post" action="' . $action . '">' . $hp . '
  <div class="mini-form__row">' . $nameField . $phoneField . '<div class="mini-btn">' . $btn . '</div></div>
  <div style="margin-top:10px">' . $consent . '</div>' . $status . '</form>';
    }
    if ($variant === 'modal') {
        return '<form class="form" data-lead novalidate method="post" action="' . $action . '" style="box-shadow:none;border:0;padding:0">' . $hp . '
  <div class="form__grid">' . $nameField . $phoneField . '<div class="full">' . $serviceField . '</div><div class="full">' . $consent . '</div><div class="full">' . $submit . '</div></div></form>';
    }
    return '<form class="form" data-lead novalidate method="post" action="' . $action . '">' . $hp . '
  <div class="form__grid">
    ' . $nameField . $phoneField . '
    ' . $serviceField . '
    <div class="field"><label for="' . $p('obj') . '">Тип объекта</label><select class="input" id="' . $p('obj') . '" name="object"><option value="">Выберите</option>' . $opts(OBJECT_OPTS, '') . '</select></div>
    <div class="field"><label for="' . $p('addr') . '">Адрес или район</label><input class="input" id="' . $p('addr') . '" name="address" type="text" autocomplete="street-address" maxlength="200"></div>
    <div class="field"><label for="' . $p('time') . '">Удобное время звонка</label><input class="input" id="' . $p('time') . '" name="call_time" type="text" placeholder="например, после 18:00" maxlength="80"></div>
    <div class="field full"><label for="' . $p('msg') . '">Комментарий</label><textarea class="input" id="' . $p('msg') . '" name="message" maxlength="1000" placeholder="Какие насекомые или грызуны, площадь, что уже пробовали"></textarea></div>
    <div class="full">' . $consent . '</div>
    <div class="full">' . $submit . '</div>
  </div></form>';
}

// Блок «Заявка» — форма + контакты
function form_section(array $o = []): string
{
    $title = $o['title'] ?? 'Оставьте заявку';
    $lead = $o['lead'] ?? 'Укажите, что нужно обработать: перезвоним, уточним детали и рассчитаем стоимость.';
    $p0 = phone1(); $p1 = phone2();
    return '<section class="section' . (!empty($o['alt']) ? ' section--alt' : '') . '" id="zayavka"><div class="container">
  <div class="section__head"><span class="eyebrow">Заявка</span><h2>' . t($title) . '</h2><p>' . t($lead) . '</p></div>
  <div class="form-wrap">
    ' . lead_form(['variant' => 'full', 'service' => $o['service'] ?? '']) . '
    <div class="contact-card">
      ' . phone_link($p0, '', icon('phone') . '<span>' . esc($p0['display']) . '<small>Позвонить</small></span>') . '
      ' . phone_link($p1, '', icon('phone') . '<span>' . esc($p1['display']) . '<small>Позвонить</small></span>') . '
    </div>
  </div></div></section>';
}

// ---------- компоненты контента ----------
function vid_tile(array $v): string
{
    $src = "/video/work-{$v['id']}.mp4";
    $poster = "/video/work-{$v['id']}.webp";
    return '<button type="button" class="vid" data-video="' . $src . '" data-cat="' . esc($v['cat']) . '" aria-label="Смотреть видео: ' . esc($v['cap']) . '">
    <img src="' . $poster . '" width="' . $v['w'] . '" height="' . $v['h'] . '" alt="' . esc($v['cap']) . ' — кадр из видео" loading="lazy" decoding="async">
    <span class="vid__play">' . icon('play') . '</span><span class="vid__cap">' . esc($v['cap']) . '</span></button>';
}

function review_cards(array $list): string
{
    return join_map($list, fn($r) => '<article class="card review reveal">
    <div class="review__top"><span class="stars" role="img" aria-label="Оценка 5 из 5">' . str_repeat(icon('star'), 5) . '</span></div>
    <blockquote>' . esc($r['text']) . '</blockquote>
    <div class="review__who">' . esc($r['name']) . '<small>Отзыв на Яндексе, ' . esc($r['date']) . '</small></div></article>');
}
function reviews_for(array $tags, int $max = 3): array
{
    $pool = []; $rest = [];
    foreach (S::$reviews as $r) { if (array_intersect($r['tags'] ?? [], $tags)) $pool[] = $r; else $rest[] = $r; }
    return array_slice(array_merge($pool, $rest), 0, $max);
}
function yandex_link(string $text = 'Все отзывы на Яндексе'): string
{
    $u = S::$company['yandexReviewsUrl'] ?? '';
    return $u !== '' ? '<a class="btn btn--ghost" href="' . esc($u) . '" target="_blank" rel="noopener">' . $text . '</a>' : '';
}
function faq_block(array $items): string
{
    return '<div class="faq">' . join_map($items, fn($f) => '<details><summary>' . esc($f['q']) . '</summary><div class="ans">' . md_blocks($f['a']) . '</div></details>') . '</div>';
}
function crumbs(array $items): string
{
    $n = count($items);
    return '<nav class="crumbs" aria-label="Хлебные крошки"><ol>' . join_map($items, fn($c, $i) => $i === $n - 1 ? '<li aria-current="page">' . esc($c['name']) . '</li>' : '<li><a href="' . $c['href'] . '">' . esc($c['name']) . '</a></li>') . '</ol></nav>';
}
function price_table(): string
{
    $P = S::$prices;
    $head = join_map($P['head'], fn($h) => '<th scope="col">' . t($h) . '</th>');
    $rows = join_map($P['rows'], fn($r) => '<tr><th scope="row">' . t($r['name']) . '</th>' . join_map([$r['v1'], $r['v2'], $r['v3']], fn($x) => '<td class="num">' . t($x) . '&nbsp;₽</td>') . '</tr>');
    $ask = fn($name) => '<tr><th scope="row">' . $name . '</th><td class="ask" colspan="3">по запросу</td></tr>';
    return '<div class="table-wrap"><table class="price"><caption class="sr-only">Цены на дезинсекцию квартир</caption><thead><tr>' . $head . '</tr></thead><tbody>' . $rows . $ask('Дом, коттедж') . $ask('Офис, магазин, общепит, склад') . '</tbody></table></div>';
}
function related_links(array $items): string
{
    return '<div class="related">' . join_map($items, fn($l) => '<a href="' . esc($l['href']) . '">' . t($l['label']) . icon('arrow') . '</a>') . '</div>';
}
function checklist(array $items): string
{
    return '<ul class="checklist">' . join_map($items, fn($x) => '<li>' . icon('check') . '<span>' . md_inline($x) . '</span></li>') . '</ul>';
}

// ---------- JSON-LD ----------
function json_ld($o): string { return str_replace('<', '\\u003c', json_encode($o, JSON_FLAGS | JSON_UNESCAPED_LINE_TERMINATORS)); }
function area_served(): array { $c = S::$company; return [['@type' => 'City', 'name' => $c['city']], ['@type' => 'AdministrativeArea', 'name' => $c['region']]]; }
function ld_local_business(): array
{
    $c = S::$company;
    $o = [
        '@context' => 'https://schema.org', '@type' => 'LocalBusiness',
        '@id' => site() . '/#business', 'name' => $c['brand'], 'url' => site() . '/',
        'telephone' => array_map(fn($p) => $p['tel'], $c['phones']), 'image' => site() . $c['ogImage'],
        'areaServed' => area_served(),
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $c['city'], 'addressRegion' => $c['region'], 'addressCountry' => 'RU'],
        'sameAs' => [$c['telegram'], $c['max']],
    ];
    if (!empty($c['address'])) $o['address']['streetAddress'] = preg_replace('~^г. [^,]+, ~u', '', $c['address']);
    if (!empty($c['hours'])) $o['openingHours'] = 'Mo-Su 09:00-20:00';
    if (!empty($c['geo'])) $o['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $c['geo']['lat'], 'longitude' => $c['geo']['lon']];
    if (!empty($c['email'])) $o['email'] = $c['email'];
    if (!empty($c['legal']['name'])) $o['legalName'] = $c['legal']['name'];
    if (!empty($c['legal']['inn'])) $o['taxID'] = $c['legal']['inn'];
    return $o;
}
function ld_faq(array $items): array
{
    return ['@context' => 'https://schema.org', '@type' => 'FAQPage',
        'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_html(md_blocks($f['a']))]], $items)];
}
function ld_breadcrumbs(array $items): array
{
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(fn($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => url($c['href'])], $items, array_keys($items))];
}
function ld_service(string $name, string $description, string $pth): array
{
    return ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $name, 'description' => $description, 'serviceType' => $name,
        'provider' => ['@id' => site() . '/#business'], 'areaServed' => area_served(), 'url' => url($pth)];
}

// ---------- первый экран: арт-фон (data/hero.json) или фото из медиатеки ----------
// На главной фон во всю ширину, на внутренних — правые 72%. На телефоне — отдельный вертикальный файл.
const HERO_MOBILE = '(max-width:767px)';
const HERO_DESKTOP = '(min-width:768px)';

function hero_art(array $spec, bool $home): array
{
    $sizes = $home ? '100vw' : '(min-width:920px) 72vw, 100vw';
    if (!empty($spec['art'])) {
        $name = $spec['art'];
        $d = S::$heroDims[$name] ?? null;
        if (!$d) throw new RuntimeException('Нет арт-фона ' . $name);
        $b = "/images/hero/$name";
        return ['pos' => $spec['pos'] ?? 'center', 'd' => $d, 'src' => "$b.webp", 'srcM' => "$b-m.webp", 'srcset' => "$b-1200.webp 1200w, $b.webp {$d['w']}w", 'sizes' => $sizes, 'strip' => !empty($d['strip'])];
    }
    $p = photo((string)($spec['photo'] ?? ''));
    $src = photo_src($p);
    $sm = !empty($p['sm']) ? '/' . $p['sm'] : $src;
    $d = ['w' => $p['w'], 'h' => $p['h'], 'mw' => $p['sw'] ?? $p['w'], 'mh' => $p['sh'] ?? $p['h']];
    return ['pos' => $spec['pos'] ?? 'center', 'd' => $d, 'src' => $src, 'srcM' => $sm, 'srcset' => "$src {$p['w']}w", 'sizes' => $sizes, 'strip' => false];
}

// fade — затенить верх и низ картинки, как у картинок-полос (страницы услуг)
function hero_bg(array $spec, bool $home = false, bool $fade = false): string
{
    $a = hero_art($spec, $home);
    $cls = $fade && !$a['strip'] ? ' hero__bg--fade' : '';
    return '<div class="hero__bg' . $cls . '" aria-hidden="true"><picture><source media="' . HERO_MOBILE . '" srcset="' . $a['srcM'] . '" width="' . $a['d']['mw'] . '" height="' . $a['d']['mh'] . '"><img src="' . $a['src'] . '" srcset="' . $a['srcset'] . '" sizes="' . $a['sizes'] . '" width="' . $a['d']['w'] . '" height="' . $a['d']['h'] . '" alt="" fetchpriority="high" decoding="async" style="object-position:' . esc($a['pos']) . '"></picture></div>';
}
function hero_preload(array $spec, bool $home): string
{
    $a = hero_art($spec, $home);
    return '<link rel="preload" as="image" href="' . $a['srcM'] . '" media="' . HERO_MOBILE . '" fetchpriority="high"><link rel="preload" as="image" href="' . $a['src'] . '" imagesrcset="' . $a['srcset'] . '" imagesizes="' . $a['sizes'] . '" media="' . HERO_DESKTOP . '" fetchpriority="high">';
}
function hero_spec(string $key): array { return S::$structure['hero'][$key]; }

// ---------- оболочка страницы ----------
// Версия файла по содержимому: после обновления браузеры сразу берут новый (в .htaccess кэш на 30 дней)
function asset_ver(string $p): string
{
    static $cache = [];
    $f = rtrim((string)cfg('public_dir'), '/\\') . $p;
    return $cache[$p] ??= is_file($f) ? substr(md5_file($f), 0, 10) : '0';
}

// Раздел админки, где правится страница (для кнопки «Редактировать» у вошедшего в админку)
function edit_route(string $pth): string
{
    static $map = ['/' => 'section/home', '/ceny/' => 'section/ceny', '/dokumenty/' => 'section/dokumenty', '/otzyvy/' => 'section/otzyvy', '/kontakty/' => 'section/kontakty', '/politika-konfidencialnosti/' => 'section/company'];
    if (isset($map[$pth])) return $map[$pth];
    if (preg_match('~^/([a-z0-9-]+)/$~', $pth, $m) && isset(S::$svcBySlug[$m[1]])) return 'service/' . $m[1];
    return '';
}

function shell(array $pg): string
{
    $c = S::$company;
    $pth = $pg['path'];
    $canonical = url($pth === '/404.html' ? '/' : $pth);
    $img = site() . ($pg['ogImage'] ?? $c['ogImage']);
    $lds = implode("\n", array_map(fn($o) => '<script type="application/ld+json">' . json_ld($o) . '</script>', $pg['ld'] ?? []));
    $cfg = json_encode(['ym' => $c['metrikaId'] ?? '', 'endpoint' => S::$base . FORM_ENDPOINT, 'base' => S::$base,'tel' => phone1()['display'], 'telHref' => phone1()['tel']], JSON_FLAGS | JSON_HEX_TAG);
    $title = esc($pg['title']); $desc = esc($pg['description']);
    $html = '<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . $title . '</title>
<meta name="description" content="' . $desc . '">
' . (!empty($pg['noindex']) || S::$noindex ? '<meta name="robots" content="noindex,follow">' : '') . '
<link rel="canonical" href="' . $canonical . '">
<meta name="theme-color" content="#0A2A6E">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="' . esc($c['brand']) . '">
<meta property="og:title" content="' . $title . '">
<meta property="og:description" content="' . $desc . '">
<meta property="og:url" content="' . $canonical . '">
<meta property="og:image" content="' . $img . '">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="' . $title . '">
<meta name="twitter:description" content="' . $desc . '">
<meta name="twitter:image" content="' . $img . '">
<link rel="icon" href="/favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preload" href="/fonts/golos-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
' . hero_preload($pg['hero'], $pth === '/') . '
<script>document.documentElement.className+=\' js\'</script>
<style>' . S::$critical . '</style>
<link rel="stylesheet" href="/styles.css?v=' . S::$cssVer . '" media="print" onload="this.media=\'all\'">
<noscript><link rel="stylesheet" href="/styles.css?v=' . S::$cssVer . '"></noscript>
' . $lds . '
</head>
<body' . (($e = edit_route($pth)) !== '' ? ' data-edit="' . $e . '"' : '') . '>
' . sprite_svg() . '
' . site_header($pth) . '
<main id="main">
' . $pg['body'] . '
</main>
' . site_footer() . '
' . bottom_bar() . '
' . modals() . '
' . cookie_banner() . '
<script>window.SANITAR=' . $cfg . ';</script>
<script src="/script.js?v=' . asset_ver('/script.js') . '" defer></script>
</body>
</html>
';
    return subst($html);
}
