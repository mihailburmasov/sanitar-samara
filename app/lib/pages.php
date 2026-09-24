<?php
// Страницы сайта. Каждая функция возвращает страницу: path, title, description, body, ld, hero, noindex.
declare(strict_types=1);

// Ссылки на скрытые или удалённые услуги при сборке пропускаются
function link_visible(string $href): bool
{
    if (!preg_match('~^/([a-z0-9-]+)/$~', $href, $m)) return true;
    $pages = ['ceny', 'dokumenty', 'otzyvy', 'kontakty', 'politika-konfidencialnosti'];
    return in_array($m[1], $pages, true) || isset(S::$svcBySlug[$m[1]]);
}

// Проверка целостности данных перед сборкой: ошибка останавливает сборку, сайт остаётся прежним
function validate_data(): void
{
    foreach (S::$structure['hero'] as $spec) hero_art($spec, false);
    foreach (S::$services as $s) {
        if (empty($s['heroBg'])) throw new RuntimeException("Услуга «{$s['name']}»: не выбран фон первого экрана");
        hero_art($s['heroBg'], false);
        foreach (['introFigure', 'howFigure'] as $k) if (!empty($s[$k]['photo'])) photo($s[$k]['photo']);
        if (!empty($s['parent']) && !isset(S::$svcBySlug[$s['parent']])) throw new RuntimeException("Услуга «{$s['name']}»: родительская услуга скрыта или удалена");
    }
    if (!S::$menu) throw new RuntimeException('Нет ни одной услуги в меню');
    foreach (S::$site['common']['docs'] as $d) photo($d['photo']);
    $pub = rtrim((string)cfg('public_dir'), '/\\');
    foreach (S::$videos['items'] as $v) if (!is_file("$pub/video/work-{$v['id']}.mp4")) throw new RuntimeException("Нет видео work-{$v['id']}.mp4");
}

function docs_grid(bool $reveal): string
{
    return join_map(S::$site['common']['docs'], function ($d) use ($reveal) {
        $p = photo($d['photo']); $src = photo_src($p);
        return '<button type="button" class="doc' . ($reveal ? ' reveal' : '') . '" data-lightbox="' . $src . '" data-caption="' . esc($d['title']) . '" data-alt="' . esc($p['alt']) . '"><img src="' . $src . '" width="' . $p['w'] . '" height="' . $p['h'] . '" alt="' . esc($p['alt']) . '" loading="lazy"><span>' . t($d['title']) . '</span></button>';
    });
}
function methods_grid(bool $reveal): string
{
    return join_map(S::$site['common']['methods'], fn($m) => '<article class="card method' . ($reveal ? ' reveal' : '') . '"><h3>' . icon($m['icon']) . t($m['t']) . '</h3><p>' . t($m['d']) . '</p><span class="when">' . t($m['when']) . '</span></article>');
}
function rating_line(bool $reveal): string
{
    $r = S::$company['rating'];
    return '<div class="rating-line' . ($reveal ? ' reveal' : '') . '"><span class="stars" role="img" aria-label="Оценка 5 из 5">' . str_repeat(icon('star'), 5) . '</span><b>' . t($r['value']) . '</b><span>на Яндексе по ' . t((string)$r['count']) . ' отзывам (' . t($r['asOf']) . ')</span></div>';
}
function h1_hl(string $s): string { return preg_replace('~\*\*(.+?)\*\*~u', '<span class="hl">$1</span>', t($s)); }

// =====================================================================
// ГЛАВНАЯ
// =====================================================================
function page_home(): array
{
    $h = S::$site['home']; $C = S::$company;
    $services = join_map(S::$menu, fn($s) => '<article class="card svc reveal">
      <span class="svc__ico">' . icon($s['icon']) . '</span>
      <h3><a class="stretch" href="/' . $s['slug'] . '/">' . t($s['name']) . '</a></h3>
      <p>' . t($s['short']) . '</p>
      <div class="svc__price">' . t($s['cardPrice']) . ' <small style="font-weight:500;color:var(--muted)">' . t($s['cardPriceNote']) . '</small></div>
      <span class="svc__more">Подробнее ' . icon('arrow') . '</span></article>');
    $pests = join_map(array_filter($h['pests'], fn($p) => link_visible(preg_replace('~#.*$~', '', $p['href']))), fn($p) => '<a class="pest reveal" href="' . esc($p['href']) . '">' . icon($p['icon']) . '<span>' . t($p['name']) . '</span></a>');
    $clients = join_map($h['clients'], fn($c) => '<div class="client reveal">' . icon($c['icon']) . '<b>' . t($c['name']) . '</b><span style="color:var(--muted);font-size:.95rem">' . t($c['text']) . '</span></div>');
    $steps = join_map(S::$site['common']['steps'], fn($s) => '<li class="reveal"><b>' . t($s['t']) . '</b><span>' . t($s['d']) . '</span></li>');
    $chips = join_map(S::$videos['cats'], fn($c) => '<button type="button" class="chip" data-filter="' . esc($c['id']) . '" aria-pressed="' . ($c['id'] === 'all' ? 'true' : 'false') . '">' . t($c['label']) . '</button>');
    $vids = join_map(S::$videos['items'], fn($v) => vid_tile($v));
    $byName = array_column(S::$reviews, null, 'name');
    // отзыв переименовали или удалили — на главной его просто нет (сборка предупредит)
    foreach ($h['reviews'] as $n) if (!isset($byName[$n])) Build::$warnings[] = "/: на главной указан отзыв «{$n}», но такого автора нет в отзывах";
    $homeReviews = array_values(array_map(fn($n) => $byName[$n], array_filter($h['reviews'], fn($n) => isset($byName[$n]))));
    $cities = join_map($C['cities'], fn($c) => '<li class="chip">' . icon('pin') . t($c) . '</li>');
    $facts = join_map($h['facts'], fn($f) => '
    <li>' . icon($f['icon']) . '<span>' . t($f['t']) . '<small>' . t($f['s']) . '</small></span></li>');
    $guarBig = join_map($h['guarBig'], fn($g) => '
      <div class="guar__big"><b>' . t($g['n']) . '</b><span>' . t($g['t']) . '</span></div>');
    $guarList = join_map($h['guarList'], fn($x) => '
        <li>' . icon('check') . '<span>' . md_inline($x) . '</span></li>');

    $body = '
<section class="hero hero--home on-dark">
  ' . hero_bg(hero_spec('home'), true) . '
  <div class="container">
  <div class="hero__grid">
    <div class="hero__text">
      <span class="hero__eyebrow">' . icon('shield-check') . ' ' . t($h['eyebrow']) . '</span>
      <h1>' . h1_hl($h['h1']) . '</h1>
      <p class="hero__lead">' . t($h['lead']) . '</p>
      <div class="hero__cta">
        <button type="button" class="btn btn--lime" data-open-modal>' . icon('phone') . ' Вызвать специалиста</button>
        <a class="btn btn--ghost" href="#ceny">' . icon('calc') . ' Рассчитать стоимость</a>
      </div>
    </div>
  </div>
  <ul class="facts">' . $facts . '
  </ul>
  </div>
</section>

<section class="section" id="uslugi"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Услуги</span><h2>' . t($h['servicesTitle']) . '</h2><p>' . t($h['servicesText']) . '</p></div>
  <div class="grid services">' . $services . '</div>
</div></section>

<section class="section section--alt" id="vrediteli"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Кого уничтожаем</span><h2>' . t($h['pestsTitle']) . '</h2><p>' . t($h['pestsText']) . '</p></div>
  <div class="grid pests">' . $pests . '</div>
</div></section>

<section class="section" id="ceny"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Цены</span><h2>' . t($h['priceTitle']) . '</h2><p>' . t($h['priceText']) . '</p></div>
  <div class="reveal">' . price_table() . '</div>
  <p class="price-note">' . t($h['priceNote']) . '</p>
  <div class="price-extra">
    <div class="price-card reveal">' . icon('sprout') . '<div><b>от ' . t(S::$prices['land']) . ' ₽ за сотку</b><span>' . t($h['priceLandText']) . '</span></div></div>
    <div class="price-card reveal">' . icon('clipboard') . '<div><b>Расчёт по заявке</b><span>' . t($h['priceAskText']) . '</span></div></div>
  </div>
  <div style="margin-top:20px;display:flex;flex-wrap:wrap;gap:12px">
    <a class="btn btn--lime" href="#zayavka">' . icon('clipboard') . ' Оставить заявку на расчёт</a>
    <a class="btn btn--ghost" href="/ceny/">Все цены</a>
  </div>
</div></section>

<section class="section section--alt" id="kak-rabotaem"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Как мы работаем</span><h2>' . t($h['stepsTitle']) . '</h2></div>
  <ol class="steps">' . $steps . '</ol>
</div></section>

<section class="section" id="raboty"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Видео работ</span><h2>' . t($h['videosTitle']) . '</h2><p>' . t($h['videosText']) . '</p></div>
  <div class="chips gal-filter" role="group" aria-label="Фильтр видео">' . $chips . '</div>
  <div class="grid gallery">' . $vids . '</div>
  <dialog id="video-modal" class="vdlg" aria-label="Просмотр видео">
    <div class="vmodal">
      <div class="vmodal__bar">
        <span class="vmodal__cap" aria-live="polite"></span>
        <span class="vmodal__count" aria-hidden="true"></span>
        <button type="button" class="icon-btn" data-close aria-label="Закрыть видео">' . icon('close') . '</button>
      </div>
      <div class="vmodal__stage">
        <video controls muted loop playsinline preload="auto"></video>
        <button type="button" class="vnav vnav--prev" aria-label="Предыдущее видео">' . icon('arrow') . '</button>
        <button type="button" class="vnav vnav--next" aria-label="Следующее видео">' . icon('arrow') . '</button>
      </div>
      <p class="vmodal__hint">Стрелки ← → на клавиатуре или свайп переключают видео</p>
    </div>
  </dialog>
</div></section>

<section class="section section--alt" id="metody"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Методы обработки</span><h2>' . t($h['methodsTitle']) . '</h2><p>' . t($h['methodsText']) . '</p></div>
  <div class="grid methods">' . methods_grid(true) . '</div>
</div></section>

<section class="section" id="garantiya"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Гарантия</span><h2>' . t($h['guarTitle']) . '</h2></div>
  <div class="guar">
    <div class="reveal" style="display:grid;gap:14px">' . $guarBig . '
    </div>
    <div class="card reveal">
      <h3>' . t($h['guarListTitle']) . '</h3>
      <ul class="checklist">' . $guarList . '
      </ul>
    </div>
  </div>
</div></section>

<section class="section section--alt" id="dokumenty"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Документы</span><h2>' . t($h['docsTitle']) . '</h2><p>' . t($h['docsText']) . '</p></div>
  <div class="grid docs">' . docs_grid(true) . '</div>
  <p class="price-note"><a href="/dokumenty/">Все документы →</a></p>
</div></section>

<section class="section" id="otzyvy"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Отзывы</span><h2>' . t($h['reviewsTitle']) . '</h2></div>
  ' . rating_line(true) . '
  <div class="grid reviews">' . review_cards($homeReviews) . '</div>
  <div style="margin-top:22px;display:flex;flex-wrap:wrap;gap:12px"><a class="btn btn--ghost" href="/otzyvy/">Все отзывы</a>' . yandex_link() . '</div>
</div></section>

<section class="section section--alt" id="klienty"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Для кого</span><h2>' . t($h['clientsTitle']) . '</h2></div>
  <div class="grid clients">' . $clients . '</div>
</div></section>

<section class="section" id="geografiya"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">География</span><h2>' . t($h['geoTitle']) . '</h2><p>' . t($h['geoText']) . '</p></div>
  <ul class="chips reveal">' . $cities . '</ul>
</div></section>

<section class="section section--alt" id="faq"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Вопросы</span><h2>Частые вопросы</h2></div>
  ' . faq_block($h['faq']) . '
</div></section>

' . form_section(['title' => $h['formTitle']]) . '
';
    return ['path' => '/', 'title' => $h['metaTitle'], 'description' => $h['metaDescription'], 'body' => $body, 'hero' => hero_spec('home'),
        'ld' => [ld_local_business(), ld_faq($h['faq'])]];
}

// =====================================================================
// СТРАНИЦА УСЛУГИ (общий шаблон для услуг и страниц отдельных вредителей)
// =====================================================================
function price_land_card(): string
{
    return '<div class="price-card">' . icon('sprout') . '<div><b>от ' . t(S::$prices['land']) . ' ₽ за сотку</b><span>Комплексная обработка участка: клещи, комары, садовые вредители. Минимальный выезд.</span></div></div>';
}
function price_ask_card(string $what): string
{
    return '<div class="price-card">' . icon('clipboard') . '<div><b>Стоимость — по запросу</b><span>' . t($what) . ' Оставьте заявку в форме: укажите местонахождение, метраж и вид вредителя — рассчитаем стоимость.</span></div></div>';
}
function price_block(array $p): string
{
    $h = '';
    if (!empty($p['table'])) $h .= price_table() . '<p class="price-note">' . t($p['tableNote'] ?? '') . '</p>';
    $ask = trim((string)($p['ask'] ?? ''));
    if (!empty($p['land'])) $h .= '<div class="price-extra">' . price_land_card() . ($ask !== '' ? price_ask_card($ask) : '') . '</div>';
    elseif ($ask !== '') $h .= price_ask_card($ask);
    if (!empty($p['askButton'])) $h .= '<div style="margin-top:16px"><a class="btn btn--lime" href="#zayavka">' . icon('clipboard') . ' Оставить заявку на расчёт</a></div>';
    if (!empty($p['note'])) $h .= '<p class="price-note">' . t($p['note']) . '</p>';
    return $h;
}
function warranty_list(array $items): string
{
    return "<ul class=\"checklist\">\n" . implode("\n", array_map(fn($x) => '  <li>' . icon('check') . '<span>' . md_inline($x) . '</span></li>', $items)) . "\n</ul>";
}

function page_service(array $d): array
{
    $pth = '/' . $d['slug'] . '/';
    $parent = !empty($d['parent']) ? S::$svcBySlug[$d['parent']] : null;
    $crumbItems = array_merge([['name' => 'Главная', 'href' => '/']], $parent ? [['name' => $parent['name'], 'href' => '/' . $parent['slug'] . '/']] : [], [['name' => $d['name'], 'href' => $pth]]);
    $p0 = phone1(); $p1 = phone2();
    $how = trim((string)($d['how'] ?? ''));
    $related = array_values(array_filter($d['related'] ?? [], fn($l) => link_visible($l['href'])));
    $chip = trim((string)($d['chip'] ?? ''));
    $body = '
<section class="page-hero on-dark">
' . hero_bg($d['heroBg'], false, true) . '<div class="container">
  ' . crumbs($crumbItems) . '
  <h1>' . t($d['h1']) . '</h1>
  <p class="lead">' . t($d['lead']) . '</p>
  ' . ($chip !== '' ? '<div class="price-chip">' . icon('ruble') . ' ' . t($chip) . '</div>' : '') . '
  <div class="page-hero__cta">
    <button type="button" class="btn btn--lime" data-open-modal>' . icon('phone') . ' Вызвать специалиста</button>
    <a class="btn btn--ghost" href="#zayavka">' . icon('calc') . ' Рассчитать стоимость</a>
  </div>
</div></section>

<section class="section"><div class="container two-col">
  <div class="prose">' . md_blocks($d['intro'] ?? '') . (!empty($d['introFigure']['photo']) ? "\n" . figure_html($d['introFigure']) : '') . '</div>
  <aside class="aside-sticky card">
    <h3>Быстрый расчёт</h3>
    <p style="color:var(--muted);font-size:.95rem">Оставьте телефон — перезвоним и назовём стоимость.</p>
    ' . lead_form(['variant' => 'mini', 'cta' => 'Перезвоните мне']) . '
    <p style="margin:0;font-size:.95rem">Или позвоните: ' . phone_link($p0, '', esc($p0['display'])) . ', ' . phone_link($p1, '', esc($p1['display'])) . '</p>
  </aside>
</div></section>

' . ($how !== '' ? '<section class="section section--alt"><div class="container"><div class="prose">' . md_blocks($how) . (!empty($d['howFigure']['photo']) ? "\n" . figure_html($d['howFigure']) : '') . '</div>' . (!empty($d['showMethods']) ? '<div class="grid methods" style="margin-top:24px">' . methods_grid(false) . '</div>' : '') . '</div></section>' : '') . '

<section class="section' . ($how !== '' ? '' : ' section--alt') . '" id="price"><div class="container">
  <div class="section__head"><span class="eyebrow">Цена</span><h2>' . t(($d['priceTitle'] ?? '') !== '' ? $d['priceTitle'] : 'Сколько это стоит') . '</h2></div>
  ' . price_block($d['price'] ?? []) . '
</div></section>

<section class="section section--alt"><div class="container two-col">
  <div>
    <div class="section__head"><span class="eyebrow">Подготовка</span><h2>Как подготовиться к обработке</h2></div>
    ' . checklist($d['prep'] ?? []) . '
  </div>
  <div class="card" id="garantiya">
    <h3>Гарантия и после обработки</h3>
    ' . warranty_list($d['warranty'] ?? []) . '
  </div>
</div></section>

<section class="section"><div class="container">
  <div class="section__head"><span class="eyebrow">Отзывы</span><h2>Отзывы клиентов</h2></div>
  <div class="grid reviews">' . review_cards(reviews_for($d['reviewTags'] ?? [], 3)) . '</div>
  <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap"><a class="btn btn--ghost" href="/otzyvy/">Все отзывы</a>' . yandex_link() . '</div>
</div></section>

<section class="section section--alt"><div class="container">
  <div class="section__head"><span class="eyebrow">Вопросы</span><h2>Частые вопросы</h2></div>
  ' . faq_block($d['faq'] ?? []) . '
</div></section>

<section class="section"><div class="container">
  <div class="section__head"><h2>Смотрите также</h2></div>
  ' . related_links($related) . '
</div></section>

' . form_section(['title' => 'Оставьте заявку', 'service' => !empty(S::$svcBySlug[$d['formService'] ?? '']['menu']) ? S::$svcBySlug[$d['formService']]['name'] : '', 'alt' => true]) . '
';
    return ['path' => $pth, 'title' => $d['metaTitle'], 'description' => $d['metaDescription'], 'body' => $body, 'hero' => $d['heroBg'],
        'ld' => [ld_service($d['name'], $d['metaDescription'], $pth), ld_faq($d['faq'] ?? []), ld_breadcrumbs($crumbItems)]];
}

// =====================================================================
// ВНУТРЕННИЕ СТРАНИЦЫ
// =====================================================================
function inner_hero(string $title, string $lead, array $items, string $heroKey): string
{
    return '<section class="page-hero on-dark">' . hero_bg(hero_spec($heroKey)) . '<div class="container">' . crumbs($items) . '<h1>' . t($title) . '</h1>' . ($lead !== '' ? '<p class="lead">' . t($lead) . '</p>' : '') . '</div></section>';
}
function crumb_items(string $name, string $href): array { return [['name' => 'Главная', 'href' => '/'], ['name' => $name, 'href' => $href]]; }

function page_ceny(): array
{
    $h = S::$site['ceny'];
    $items = crumb_items('Цены', '/ceny/');
    $others = array_values(array_filter(S::$menu, fn($s) => $s['slug'] !== 'dezinsekciya'));
    $body = inner_hero($h['h1'], $h['lead'], $items, 'ceny') . '
<section class="section"><div class="container">
  <div class="section__head"><span class="eyebrow">Квартиры</span><h2>' . t($h['flatTitle']) . '</h2><p>' . t($h['flatText']) . '</p></div>
  ' . price_table() . '
  <p class="price-note">' . t($h['flatNote']) . '</p>
</div></section>
<section class="section section--alt"><div class="container">
  <div class="section__head"><span class="eyebrow">Участки</span><h2>' . t($h['landTitle']) . '</h2></div>
  <div class="price-extra"><div class="price-card">' . icon('sprout') . '<div><b>от ' . t(S::$prices['land']) . ' ₽ за сотку</b><span>' . t($h['landText']) . '</span></div></div>
  <div class="price-card">' . icon('leaf') . '<div><b>' . t($h['treesTitle']) . '</b><span>' . t($h['treesText']) . '</span></div></div></div>
</div></section>
<section class="section"><div class="container">
  <div class="section__head"><span class="eyebrow">Индивидуальный расчёт</span><h2>' . t($h['indTitle']) . '</h2><p>' . t($h['indText']) . '</p></div>
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">' . join_map($others, fn($s) => '<a class="pest" href="/' . $s['slug'] . '/" style="align-items:flex-start;text-align:left">' . icon($s['icon']) . '<span>' . t($s['name']) . '</span><small>по запросу</small></a>') . '</div>
  <div style="margin-top:22px;display:flex;flex-wrap:wrap;gap:12px"><a class="btn btn--lime" href="#zayavka">' . icon('clipboard') . ' Оставить заявку на расчёт</a><button type="button" class="btn btn--ghost" data-open-modal>Вызвать специалиста</button></div>
  <div class="callout" style="margin-top:22px"><p>' . md_inline($h['callout']) . '</p></div>
</div></section>
' . form_section(['alt' => true]);
    return ['path' => '/ceny/', 'title' => $h['metaTitle'], 'description' => $h['metaDescription'], 'body' => $body, 'hero' => hero_spec('ceny'), 'ld' => [ld_breadcrumbs($items)]];
}

function page_dokumenty(): array
{
    $h = S::$site['dokumenty'];
    $items = crumb_items('Документы', '/dokumenty/');
    $body = inner_hero($h['h1'], $h['lead'], $items, 'dokumenty') . '
<section class="section"><div class="container">
  <div class="grid docs" style="max-width:720px">' . docs_grid(false) . '</div>
  <div class="prose" style="margin-top:30px">
    <h2>' . t($h['orgTitle']) . '</h2>
    <p>' . t($h['orgText']) . '</p>
    <h2>' . t($h['companyTitle']) . '</h2>
    <p>Лицензии и разрешения компании: ' . val('СКАН_ЛИЦЕНЗИИ_КОМПАНИИ', $h['licenseNote'] ?? '') . '</p>
  </div>
</div></section>
' . form_section(['alt' => true]);
    return ['path' => '/dokumenty/', 'title' => $h['metaTitle'], 'description' => $h['metaDescription'], 'body' => $body, 'hero' => hero_spec('dokumenty'), 'ld' => [ld_breadcrumbs($items)]];
}

function page_otzyvy(): array
{
    $h = S::$site['otzyvy'];
    $items = crumb_items('Отзывы', '/otzyvy/');
    $body = inner_hero($h['h1'], $h['lead'], $items, 'otzyvy') . '
<section class="section"><div class="container">
  ' . rating_line(false) . '
  <div class="grid reviews">' . review_cards(S::$reviews) . '</div>
  <p class="price-note">' . t($h['note']) . '</p>
  <div style="margin-top:18px">' . yandex_link('Читать все отзывы на Яндексе') . '</div>
</div></section>
' . form_section(['alt' => true]);
    return ['path' => '/otzyvy/', 'title' => $h['metaTitle'], 'description' => $h['metaDescription'], 'body' => $body, 'hero' => hero_spec('otzyvy'), 'ld' => [ld_breadcrumbs($items)]];
}

// encodeURIComponent из JavaScript
function uri_component(string $s): string { return strtr(rawurlencode($s), ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')']); }

function page_kontakty(): array
{
    $h = S::$site['kontakty']; $C = S::$company;
    $items = crumb_items('Контакты', '/kontakty/');
    $p0 = phone1(); $p1 = phone2();
    if (!empty($C['address'])) {
        $g = $C['geo'] ?? null;
        $map = ($g ? '<iframe class="map-frame" src="https://yandex.ru/map-widget/v1/?ll=' . $g['lon'] . ',' . $g['lat'] . '&amp;z=16&amp;pt=' . $g['lon'] . ',' . $g['lat'] . ',pm2rdm" title="Офис на карте: ' . esc($C['address']) . '" loading="lazy" allowfullscreen></iframe>' : '')
            . '<a class="btn btn--ghost" href="https://yandex.ru/maps/?text=' . uri_component($C['address']) . '" target="_blank" rel="noopener">' . icon('pin') . ' Открыть на Яндекс Картах</a>';
    } else {
        $map = '<p>Карта: ' . val('КАРТА_АДРЕС', '') . '</p>';
    }
    $email = !empty($C['email']) ? '<a href="mailto:' . esc($C['email']) . '">' . esc($C['email']) . '</a>' : val('EMAIL', $C['email'] ?? '');
    $body = inner_hero($h['h1'], $h['lead'], $items, 'kontakty') . '
<section class="section"><div class="container two-col">
  <div>
    <div class="contact-card">
      ' . phone_link($p0, '', icon('phone') . '<span>' . esc($p0['display']) . '<small>Позвонить</small></span>') . '
      ' . phone_link($p1, '', icon('phone') . '<span>' . esc($p1['display']) . '<small>Позвонить</small></span>') . '
    </div>
    <div class="prose" style="margin-top:24px">
      <p><b>Адрес:</b> ' . val('АДРЕС', $C['address']) . '</p>
      <p><b>Режим работы:</b> ' . val('ЧАСЫ_РАБОТЫ', $C['hours']) . '</p>
      <p><b>E-mail:</b> ' . $email . '</p>
      <p><b>Территория работы:</b> Самара и Самарская область.</p>
      <p><b>Мы в мессенджерах:</b> <a href="' . esc($C['telegram']) . '" target="_blank" rel="noopener" data-goal="telegram_click">Telegram-канал</a>, <a href="' . esc($C['max']) . '" target="_blank" rel="noopener" data-goal="max_click">группа в MAX</a></p>
    </div>
    <div class="map-box">' . $map . '</div>
  </div>
  <div>' . lead_form(['variant' => 'full']) . '</div>
</div></section>';
    return ['path' => '/kontakty/', 'title' => $h['metaTitle'], 'description' => $h['metaDescription'], 'body' => $body, 'hero' => hero_spec('kontakty'), 'ld' => [ld_breadcrumbs($items)]];
}

// Дата «24 сентября 2026 г.» — как toLocaleDateString('ru-RU') в прежнем сборщике
function date_ru(string $ymd): string
{
    static $m = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    if (!preg_match('~^(\d{4})-(\d\d)-(\d\d)$~', $ymd, $x)) throw new RuntimeException('Дата редакции политики должна быть в формате ГГГГ-ММ-ДД');
    return $x[3] . ' ' . $m[(int)$x[2] - 1] . ' ' . $x[1] . ' г.';
}

function page_politika(): array
{
    $C = S::$company;
    $items = crumb_items('Политика конфиденциальности', '/politika-konfidencialnosti/');
    $ym = !empty($C['metrikaId']);
    $ogrnLabel = str_starts_with((string)$C['legal']['name'], 'ИП ') ? 'ОГРНИП' : 'ОГРН'; // у индивидуального предпринимателя — ОГРНИП
    $op = val('ЮР_НАЗВАНИЕ', $C['legal']['name']) . ' (ИНН ' . val('ИНН', $C['legal']['inn']) . ', ' . $ogrnLabel . ' ' . val('ОГРН', $C['legal']['ogrn']) . '), адрес: ' . val('АДРЕС', $C['address']);
    $mail = val('EMAIL', $C['email'] ?? '');
    $body = inner_hero('Политика конфиденциальности', 'Редакция от ' . date_ru($C['legalDate']), $items, 'politika') . '
<section class="section"><div class="container"><div class="prose">
<h2>1. Общие положения</h2>
<p>Настоящая политика описывает, как сайт «' . esc($C['brand']) . '» обрабатывает персональные данные посетителей в соответствии с Федеральным законом от 27.07.2006 № 152-ФЗ «О персональных данных». Оператор персональных данных: ' . $op . '. Контакты для обращений: телефоны ' . esc(phone1()['display']) . ', ' . esc(phone2()['display']) . ', e-mail ' . $mail . '.</p>
<h2>2. Какие данные мы собираем</h2>
<p>Через формы на сайте вы можете передать: имя, номер телефона, выбранную услугу, тип объекта, адрес или район, комментарий и удобное время звонка. Дополнительно автоматически могут обрабатываться технические данные: IP-адрес, тип браузера и устройства, посещённые страницы, данные файлов cookie.</p>
<h2>3. Цели обработки</h2>
<ul><li>связаться с вами, ответить на заявку и рассчитать стоимость услуги;</li><li>заключить и исполнить договор на оказание услуг;</li><li>обеспечить работу сайта и улучшать его' . ($ym ? ' (в том числе с помощью Яндекс.Метрики)' : '') . '.</li></ul>
<h2>4. Правовое основание</h2>
<p>Обработка осуществляется на основании вашего согласия, которое вы даёте, отметив соответствующий флажок в форме и нажав кнопку отправки, а также для заключения и исполнения договора.</p>
<h2>5. Срок хранения</h2>
<p>Данные хранятся не дольше, чем этого требуют цели обработки, или до отзыва вами согласия. Заявки, не приведшие к заключению договора, удаляются по достижении цели обработки.</p>
<h2>6. Передача данных третьим лицам</h2>
<p>Мы не продаём и не передаём ваши данные третьим лицам в маркетинговых целях. Заявка отправляется на электронную почту оператора и дублируется в мессенджер Telegram для оперативной обработки. Данные могут обрабатываться хостинг-провайдером сайта' . ($ym ? ' и сервисом Яндекс.Метрика (ООО «Яндекс»)' : '') . ' в объёме, необходимом для предоставления сервиса.</p>
<h2>7. Ваши права</h2>
<p>Вы вправе получить сведения об обработке ваших данных, требовать их уточнения, блокирования или удаления, а также отозвать согласие. Для этого направьте обращение на e-mail ' . $mail . ' или позвоните по указанным телефонам. Мы ответим в сроки, установленные законом.</p>
<h2>8. Защита данных</h2>
<p>Мы принимаем организационные и технические меры для защиты данных от неправомерного доступа, изменения и удаления. Передача данных между вашим браузером и сайтом осуществляется по защищённому соединению (HTTPS).</p>
<h2 id="cookie-policy">9. Файлы cookie</h2>
<p>' . ($ym ? 'Сайт использует файлы cookie для корректной работы и сбора статистики посещаемости с помощью Яндекс.Метрики (включая вебвизор). Метрика запускается только после вашего согласия в баннере.' : 'Сайт использует файлы cookie для корректной работы. Сервисы веб-аналитики на сайте сейчас не подключены.') . ' ' . (!empty($C['geo']) ? 'На странице «Контакты» встроена Яндекс Карта: сервис Яндекса может устанавливать собственные файлы cookie по своим правилам. ' : '') . 'Вы можете отключить cookie в настройках браузера, но некоторые функции сайта могут работать некорректно.</p>
<h2 id="soglasie">10. Согласие на обработку персональных данных</h2>
<p>Отправляя форму на сайте, я свободно, своей волей и в своём интересе даю оператору — ' . val('ЮР_НАЗВАНИЕ', $C['legal']['name']) . ' (ИНН ' . val('ИНН', $C['legal']['inn']) . ') — согласие на обработку моих персональных данных (имя, номер телефона, адрес или район, тип объекта, комментарий, удобное время звонка) в целях обратной связи, расчёта стоимости и оказания услуг, в том числе на их передачу в мессенджер Telegram для оперативной обработки заявки. Согласие действует до достижения целей обработки или до его отзыва. Согласие можно отозвать, направив обращение на ' . $mail . '.</p>
<h2>11. Изменение политики</h2>
<p>Мы можем обновлять политику. Актуальная редакция всегда размещена на этой странице.</p>
</div></div></section>';
    return ['path' => '/politika-konfidencialnosti/', 'title' => 'Политика конфиденциальности | ДЕЗЦЕНТР SANITAR', 'description' => 'Политика обработки персональных данных сайта ДЕЗЦЕНТР SANITAR: какие данные мы собираем, как используем и как защищаем.', 'body' => $body, 'hero' => hero_spec('politika'), 'ld' => [ld_breadcrumbs($items)]];
}

function page_not_found(): array
{
    $body = '<section class="page-hero on-dark">' . hero_bg(hero_spec('notFound')) . '<div class="container">
  <b class="err-code">404</b><h1>Страница не найдена</h1>
  <p class="lead">Возможно, адрес изменился или введён с ошибкой. Перейдите на главную или выберите услугу.</p>
  <div class="page-hero__cta">
    <a class="btn btn--lime" href="/">На главную</a>
    ' . join_map(S::$menu, fn($s) => '<a class="btn btn--ghost" href="/' . $s['slug'] . '/">' . t($s['name']) . '</a>') . '
  </div>
  <p style="margin:18px 0 0">Или позвоните: ' . phone_link(phone1(), '', esc(phone1()['display'])) . '</p>
</div></section>';
    return ['path' => '/404.html', 'title' => 'Страница не найдена | ДЕЗЦЕНТР SANITAR', 'description' => 'Страница не найдена. Перейдите на главную страницу или выберите услугу.', 'body' => $body, 'noindex' => true, 'hero' => hero_spec('notFound'), 'ld' => []];
}
