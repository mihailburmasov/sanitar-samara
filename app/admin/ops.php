<?php
// Операции админки: услуги, медиатека, корзина, история правок, заявки, настройки.
declare(strict_types=1);

// =====================================================================
// УСЛУГИ
// =====================================================================
function translit_slug(string $s): string
{
    static $map = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'];
    $s = strtr(mb_strtolower($s), $map);
    $s = trim(preg_replace('~[^a-z0-9]+~', '-', $s), '-');
    return substr($s, 0, 60) ?: 'usluga';
}

// Адреса, которые заняты страницами сайта (услуга с таким адресом их бы перекрыла)
const RESERVED_SLUGS = ['ceny', 'dokumenty', 'otzyvy', 'kontakty', 'politika-konfidencialnosti', 'admin', 'images', 'video', 'fonts'];

function services_all(): array
{
    $files = glob(cfg('data_dir') . '/services/*.json') ?: [];
    $order = array_flip(doc_get('structure.json')['servicesOrder'] ?? []);
    $list = [];
    foreach ($files as $f) {
        $s = read_json($f);
        $list[] = ['slug' => $s['slug'], 'title' => $s['name'], 'icon' => $s['icon'] ?? 'spray', 'short' => $s['short'] ?? '', 'hidden' => !empty($s['hidden']), 'menu' => !empty($s['menu'])];
    }
    usort($list, fn($a, $b) => [$order[$a['slug']] ?? PHP_INT_MAX, $a['slug']] <=> [$order[$b['slug']] ?? PHP_INT_MAX, $b['slug']]);
    return $list;
}

function unique_slug(string $base): string
{
    $slug = $base; $n = 2;
    while (in_array($slug, RESERVED_SLUGS, true) || is_file(data_path("services/$slug.json")) || trash_find('service', $slug)) $slug = $base . '-' . $n++;
    return $slug;
}

function service_create(string $title): string
{
    $title = trim($title);
    if (mb_strlen($title) < 3) throw new InvalidArgumentException('Введите название услуги');
    $slug = unique_slug(translit_slug($title));
    $svc = [
        'slug' => $slug, 'name' => $title, 'menu' => false, 'hidden' => true, 'parent' => '', 'formService' => '',
        'heroBg' => ['art' => 'fog-lawn', 'pos' => 'center'],
        'metaTitle' => $title . ' в Самаре | ДЕЗЦЕНТР SANITAR',
        'metaDescription' => $title . ' в Самаре и Самарской области. Профессиональные препараты, гарантия, расчёт по заявке.',
        'h1' => $title . ' в Самаре',
        'lead' => '',
        'chip' => 'Стоимость — по запросу, рассчитаем по заявке',
        'intro' => '', 'how' => '',
        'price' => ['ask' => 'Цена зависит от объекта и объёма работ.', 'askButton' => true],
        'prep' => [], 'warranty' => [], 'reviewTags' => ['general'], 'faq' => [],
        'related' => [['href' => '/ceny/', 'label' => 'Цены']],
    ];
    $st = doc_get('structure.json');
    $st['servicesOrder'][] = $slug;
    commit_changes(["services/$slug.json" => $svc, 'structure.json' => $st]);
    return $slug;
}

function service_duplicate(string $slug): string
{
    $src = doc_get("services/$slug.json");
    $new = unique_slug($slug . '-kopiya');
    $src['slug'] = $new;
    $src['name'] .= ' (копия)';
    $src['hidden'] = true;
    $st = doc_get('structure.json');
    $pos = array_search($slug, $st['servicesOrder'], true);
    array_splice($st['servicesOrder'], $pos === false ? count($st['servicesOrder']) : $pos + 1, 0, [$new]);
    commit_changes(["services/$new.json" => $src, 'structure.json' => $st]);
    return $new;
}

// Что мешает удалить услугу: страницы, для которых она — раздел
function service_usage(string $slug): array
{
    $uses = [];
    foreach (services_all() as $o) {
        if ($o['slug'] === $slug) continue;
        $d = doc_get("services/{$o['slug']}.json");
        if (($d['parent'] ?? '') === $slug) $uses[] = 'раздел для страницы «' . $d['name'] . '»';
    }
    return $uses;
}

function service_delete(string $slug): void
{
    if ($uses = service_usage($slug)) throw new InvalidArgumentException('Услуга используется: ' . implode(', ', $uses) . '. Сначала выберите там другой раздел.');
    $svc = doc_get("services/$slug.json");
    $changes = ["services/$slug.json" => null];
    $st = doc_get('structure.json');
    $st['servicesOrder'] = array_values(array_diff($st['servicesOrder'], [$slug]));
    $changes['structure.json'] = $st;
    // ссылки «Смотрите также» и выбор в форме на удалённую услугу убираем; прочие ссылки на неё сборка просто не покажет
    foreach (services_all() as $o) {
        if ($o['slug'] === $slug) continue;
        $d = doc_get("services/{$o['slug']}.json");
        $before = $d;
        $d['related'] = array_values(array_filter($d['related'] ?? [], fn($l) => $l['href'] !== "/$slug/"));
        if (($d['formService'] ?? '') === $slug) $d['formService'] = '';
        if ($d !== $before) $changes["services/{$o['slug']}.json"] = $d;
    }
    trash_put('service', $slug, $svc['name'], ['data' => $svc]);
    commit_changes($changes);
}

function services_reorder(array $slugs): void
{
    $st = doc_get('structure.json');
    $all = array_column(services_all(), 'slug');
    $slugs = array_values(array_intersect($slugs, $all));
    foreach ($all as $s) if (!in_array($s, $slugs, true)) $slugs[] = $s;
    $st['servicesOrder'] = $slugs;
    commit_changes(['structure.json' => $st]);
}

// =====================================================================
// МЕДИАТЕКА: data/photos.json, файлы загрузок — public/images/foto
// =====================================================================
const UPLOAD_DIR = 'images/foto';
function pub_path(string $rel): string { return rtrim(cfg('public_dir'), '/\\') . '/' . $rel; }

function photo_cat_labels(): array
{
    $labels = doc_get('structure.json')['photoCats'] ?? [];
    foreach (doc_get('photos.json') as $key => $_) $labels[$key] ??= $key;
    return $labels;
}

// Где используется фото
function photo_usage(string $id): array
{
    $uses = [];
    foreach (doc_get('structure.json')['hero'] as $page => $spec) if (($spec['photo'] ?? '') === $id) $uses[] = 'фон первого экрана (' . $page . ')';
    foreach (doc_get('site.json')['common']['docs'] as $d) if ($d['photo'] === $id) $uses[] = 'документ «' . $d['title'] . '»';
    foreach (services_all() as $o) {
        $d = doc_get("services/{$o['slug']}.json");
        if (($d['heroBg']['photo'] ?? '') === $id) $uses[] = 'фон услуги «' . $d['name'] . '»';
        foreach (['introFigure', 'howFigure'] as $k) if (($d[$k]['photo'] ?? '') === $id) $uses[] = 'фото в тексте услуги «' . $d['name'] . '»';
    }
    return $uses;
}

function photos_payload(): array
{
    $cats = [];
    $labels = photo_cat_labels();
    foreach (doc_get('photos.json') as $key => $list) $cats[] = ['key' => $key, 'label' => $labels[$key], 'photos' => $list];
    return ['cats' => $cats, 'arts' => array_keys(doc_get('hero.json')), 'base' => S::$base];
}

function next_photo_id(string $cat): string
{
    $max = 0;
    foreach (doc_get('photos.json')[$cat] ?? [] as $p) if (preg_match('~-(\d+)$~', $p['id'], $m)) $max = max($max, (int)$m[1]);
    do { $id = $cat . '-' . str_pad((string)++$max, 2, '0', STR_PAD_LEFT); } while (isset(S::$photoIndex[$id]) || is_file(pub_path(UPLOAD_DIR . "/$id.webp")));
    return $id;
}

// Загрузка: уменьшение и перевод в WebP (крупное 1280 px и превью 640 px)
function photo_upload(string $cat, array $file, string $alt): array
{
    if (!isset(doc_get('photos.json')[$cat])) throw new InvalidArgumentException('Папка не найдена');
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException(($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE ? 'Файл слишком большой' : 'Файл не загрузился');
    if ($file['size'] > 30 * 1024 * 1024) throw new InvalidArgumentException('Файл больше 30 МБ');
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpeg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) throw new InvalidArgumentException('Нужна картинка JPG, PNG или WebP');
    if ($info[0] * $info[1] > 60_000_000) throw new InvalidArgumentException('Слишком большое разрешение');
    @ini_set('memory_limit', '512M');
    $src = match ($types[$info[2]]) { 'jpeg' => @imagecreatefromjpeg($file['tmp_name']), 'png' => @imagecreatefrompng($file['tmp_name']), 'webp' => @imagecreatefromwebp($file['tmp_name']) };
    if (!$src) throw new InvalidArgumentException('Не удалось прочитать картинку');
    if ($types[$info[2]] === 'jpeg' && function_exists('exif_read_data')) {
        $o = (int)(@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
        $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rot) { $r = imagerotate($src, $rot, 0); if ($r) { imagedestroy($src); $src = $r; } }
    }
    $id = next_photo_id($cat);
    ensure_dir(pub_path(UPLOAD_DIR));
    [$w, $h] = webp_fit($src, 1280, 72, pub_path(UPLOAD_DIR . "/$id.webp"));
    [$sw, $sh] = webp_fit($src, 640, 64, pub_path(UPLOAD_DIR . "/$id-sm.webp"));
    imagedestroy($src);
    $alt = trim($alt) ?: 'Фото работ';
    $entry = ['id' => $id, 'file' => UPLOAD_DIR . "/$id.webp", 'sm' => UPLOAD_DIR . "/$id-sm.webp", 'w' => $w, 'h' => $h, 'sw' => $sw, 'sh' => $sh, 'alt' => mb_substr($alt, 0, 300)];
    $photos = doc_get('photos.json');
    $photos[$cat][] = $entry;
    try { commit_changes(['photos.json' => $photos]); }
    catch (Throwable $e) { @unlink(pub_path($entry['file'])); @unlink(pub_path($entry['sm'])); throw $e; }
    return $entry;
}

function webp_fit($src, int $max, int $q, string $dest): array
{
    $w = imagesx($src); $h = imagesy($src);
    $k = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false); imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $tmp = $dest . '.tmp';
    if (!imagewebp($dst, $tmp, $q)) throw new RuntimeException('Не удалось сохранить WebP');
    imagedestroy($dst);
    rename($tmp, $dest);
    return [$nw, $nh];
}

// Подпись и папка
function photo_update(string $id, array $v): void
{
    $photos = doc_get('photos.json');
    foreach ($photos as $cat => $list) foreach ($list as $i => $p) {
        if ($p['id'] !== $id) continue;
        $p['alt'] = mb_substr(trim((string)($v['alt'] ?? $p['alt'])), 0, 300) ?: $p['alt'];
        $to = (string)($v['cat'] ?? $cat);
        if (!isset($photos[$to])) throw new InvalidArgumentException('Папка не найдена');
        if ($to === $cat) $photos[$cat][$i] = $p;
        else { array_splice($photos[$cat], $i, 1); $photos[$to][] = $p; }
        commit_changes(['photos.json' => $photos]);
        return;
    }
    throw new InvalidArgumentException('Фото не найдено');
}

function photo_move(string $id, int $dir): void
{
    $photos = doc_get('photos.json');
    foreach ($photos as $cat => $list) foreach ($list as $i => $p) {
        if ($p['id'] !== $id) continue;
        $j = $i + $dir;
        if ($j < 0 || $j >= count($list)) return;
        [$photos[$cat][$i], $photos[$cat][$j]] = [$photos[$cat][$j], $photos[$cat][$i]];
        commit_changes(['photos.json' => $photos]);
        return;
    }
}

function photo_files(array $p): array { return array_values(array_filter([$p['file'] ?? null, $p['sm'] ?? null], fn($f) => $f && is_file(pub_path($f)))); }

function photo_delete(string $id): void
{
    if ($uses = photo_usage($id)) throw new InvalidArgumentException('Фото используется: ' . implode(', ', array_unique($uses)) . '. Сначала замените его там.');
    $photos = doc_get('photos.json');
    foreach ($photos as $cat => $list) foreach ($list as $i => $p) {
        if ($p['id'] !== $id) continue;
        array_splice($photos[$cat], $i, 1);
        $files = photo_files($p);
        $tid = trash_put('photo', $id, $p['alt'], ['entry' => $p, 'cat' => $cat, 'files' => $files]);
        foreach ($files as $rel) rename(pub_path($rel), storage_path("trash/$tid/" . basename($rel)));
        try { commit_changes(['photos.json' => $photos]); }
        catch (Throwable $e) { foreach ($files as $rel) rename(storage_path("trash/$tid/" . basename($rel)), pub_path($rel)); trash_forget($tid); throw $e; }
        return;
    }
    throw new InvalidArgumentException('Фото не найдено');
}

function photo_cat_add(string $label): string
{
    $label = trim($label);
    if (mb_strlen($label) < 2) throw new InvalidArgumentException('Введите название папки');
    $key = translit_slug($label);
    $photos = doc_get('photos.json');
    $n = 2; $base = $key;
    while (isset($photos[$key])) $key = $base . '-' . $n++;
    $photos[$key] = [];
    $st = doc_get('structure.json');
    $st['photoCats'][$key] = $label;
    commit_changes(['photos.json' => $photos, 'structure.json' => $st]);
    return $key;
}

// =====================================================================
// КОРЗИНА: удалённое не стирается, а переезжает сюда. Автоочистки нет.
// =====================================================================
function trash_index(): array
{
    $f = storage_path('trash/index.json');
    return is_file($f) ? read_json($f) : [];
}
function trash_save_index(array $idx): void { write_file_atomic(storage_path('trash/index.json'), json_pretty(array_values($idx))); }

function trash_put(string $type, string $ref, string $title, array $payload): string
{
    $tid = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    ensure_dir(storage_path("trash/$tid"));
    write_file_atomic(storage_path("trash/$tid/item.json"), json_pretty(['type' => $type, 'ref' => $ref, 'title' => $title] + $payload));
    $idx = trash_index();
    array_unshift($idx, ['id' => $tid, 'type' => $type, 'ref' => $ref, 'title' => $title, 'date' => date('c')]);
    trash_save_index($idx);
    return $tid;
}
function trash_forget(string $tid): void { trash_save_index(array_filter(trash_index(), fn($t) => $t['id'] !== $tid)); }
function trash_find(string $type, string $ref): ?array
{
    foreach (trash_index() as $t) if ($t['type'] === $type && $t['ref'] === $ref) return $t;
    return null;
}

function trash_restore(string $tid): string
{
    if (!preg_match('~^[0-9a-f-]+$~', $tid) || !is_file(storage_path("trash/$tid/item.json"))) throw new InvalidArgumentException('Не найдено в корзине');
    $item = read_json(storage_path("trash/$tid/item.json"));
    if ($item['type'] === 'service') {
        $slug = $item['ref'];
        if (is_file(data_path("services/$slug.json"))) throw new InvalidArgumentException('Услуга с таким адресом уже есть');
        $svc = $item['data'];
        $svc['hidden'] = true; // возвращаем скрытой: проверить и включить
        if (!empty($svc['parent']) && !is_file(data_path("services/{$svc['parent']}.json"))) $svc['parent'] = '';
        $st = doc_get('structure.json');
        $st['servicesOrder'][] = $slug;
        commit_changes(["services/$slug.json" => $svc, 'structure.json' => $st]);
        $msg = 'Услуга возвращена скрытой — проверьте её и снимите галочку «Скрыть с сайта».';
    } else {
        $photos = doc_get('photos.json');
        $cat = isset($photos[$item['cat']]) ? $item['cat'] : array_key_first($photos);
        foreach ($item['files'] as $rel) { ensure_dir(dirname(pub_path($rel))); rename(storage_path("trash/$tid/" . basename($rel)), pub_path($rel)); }
        $photos[$cat][] = $item['entry'];
        commit_changes(['photos.json' => $photos]);
        $msg = 'Фото возвращено в медиатеку.';
    }
    trash_forget($tid);
    rrmdir(storage_path("trash/$tid"));
    return $msg;
}

// =====================================================================
// ИСТОРИЯ ПРАВОК
// =====================================================================
function file_label(string $rel): string
{
    static $names = ['site.json' => 'Тексты страниц', 'company.json' => 'Компания и контакты', 'structure.json' => 'Порядок услуг и фоны страниц', 'photos.json' => 'Фото', 'reviews.json' => 'Отзывы', 'prices.json' => 'Цены', 'videos.json' => 'Видео', 'hero.json' => 'Готовые фоны'];
    if (isset($names[$rel])) return $names[$rel];
    if (str_starts_with($rel, 'services/')) {
        $f = data_path($rel);
        $t = is_file($f) ? (read_json($f)['name'] ?? $rel) : basename($rel, '.json');
        return 'Услуга «' . $t . '»';
    }
    return $rel;
}

function revisions_list(int $limit = 200): array
{
    $out = [];
    foreach (glob(storage_path('revisions/*'), GLOB_ONLYDIR) ?: [] as $dir) {
        $rel = str_replace('__', '/', basename($dir));
        foreach (glob("$dir/*.json") ?: [] as $f) {
            $name = basename($f, '.json');
            if (!preg_match('~^(\d{4}-\d\d-\d\d)_(\d\d)-(\d\d)-(\d\d)~', $name, $m)) continue;
            $out[] = ['id' => basename($dir) . '/' . $name, 'file' => $rel, 'label' => file_label($rel), 'date' => "{$m[1]} {$m[2]}:{$m[3]}:{$m[4]}"];
        }
    }
    usort($out, fn($a, $b) => strcmp($b['date'], $a['date']));
    return array_slice($out, 0, $limit);
}

// Вернуть версию файла, какой она была ДО указанной правки
function revision_restore(string $id): void
{
    if (!preg_match('~^([a-z0-9_.-]+)/([0-9_a-z-]+)$~i', $id, $m)) throw new InvalidArgumentException('Неверная версия');
    $f = storage_path("revisions/{$m[1]}/{$m[2]}.json");
    if (!is_file($f)) throw new InvalidArgumentException('Версия не найдена');
    $rel = str_replace('__', '/', $m[1]);
    $data = read_json($f);
    commit_changes([$rel => $data], 'restore');
}

// =====================================================================
// ЗАЯВКИ (пишет app/lead.php)
// =====================================================================
function leads_list(int $limit = 300): array
{
    $out = [];
    $files = glob(storage_path('leads/*.jsonl')) ?: [];
    rsort($files);
    foreach ($files as $f) {
        $lines = array_reverse(file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
        foreach ($lines as $l) { $j = json_decode($l, true); if ($j) $out[] = $j; if (count($out) >= $limit) break 2; }
    }
    return $out;
}

// =====================================================================
// НАСТРОЙКИ (storage/settings.json — не публикуется и не попадает в git)
// =====================================================================
function settings_get(): array
{
    $f = storage_path('settings.json');
    $s = is_file($f) ? read_json($f) : [];
    return $s + ['leadEmails' => [], 'mailFrom' => ''];
}
function settings_save(array $v): void
{
    $emails = array_values(array_filter(array_map('trim', (array)($v['leadEmails'] ?? []))));
    foreach ($emails as $e) if (!filter_var($e, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException("Неверный адрес почты: $e");
    $from = trim((string)($v['mailFrom'] ?? ''));
    if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Неверный адрес отправителя');
    write_file_atomic(storage_path('settings.json'), json_pretty(['leadEmails' => $emails, 'mailFrom' => $from]));
}
