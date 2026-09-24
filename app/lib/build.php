<?php
// Сборка сайта: data/*.json -> готовые HTML-страницы, styles.css, sitemap.xml, robots.txt.
// Все файлы сначала собираются в памяти; если по дороге ошибка — на диск ничего не пишется, сайт остаётся прежним.
declare(strict_types=1);

final class Build
{
    public static array $pages = [];    // собранные страницы (для отчёта и проверок)
    public static array $warnings = [];
}

function page_file(string $pth): string
{
    if ($pth === '/') return 'index.html';
    return str_ends_with($pth, '.html') ? ltrim($pth, '/') : ltrim($pth, '/') . 'index.html';
}

// CSS: исходники в app/css, критическая часть встраивается в страницу, остальное — styles.css
function css_clean(string $css): string
{
    $css = preg_replace('~/\*[\s\S]*?\*/~', '', $css);
    $css = preg_replace('~' . WS . '*\n' . WS . '*~u', '', $css);
    $css = preg_replace('~' . WS . '{2,}~u', ' ', $css);
    return preg_replace('~' . WS . '*([{};])' . WS . '*~u', '$1', $css);
}
function css_build(): array
{
    $dir = APP_DIR . '/css';
    $crit0 = file_get_contents("$dir/00-critical.css");
    $rest = file_get_contents("$dir/10-rest.css");
    preg_match_all('~/\*@critical\*/([\s\S]*?)/\*@end\*/~', $rest, $m);
    $rebase = fn($s) => S::$base !== '' ? str_replace('url(/fonts/', 'url(' . S::$base . '/fonts/', $s) : $s;
    return [
        'critical' => $rebase(css_clean($crit0 . "\n" . implode("\n", $m[1]))),
        'full' => $rebase(css_clean($crit0 . "\n" . preg_replace('~/\*@(critical|end)\*/~', '', $rest))),
    ];
}

// Сайт в подпапке (превью на GitHub Pages): корневые ссылки получают префикс
function rebase_html(string $html): string
{
    if (S::$base === '') return $html;
    $B = S::$base;
    $html = preg_replace('~(' . WS . '(?:href|src|action|data-video|data-lightbox)=")/(?!/)~u', '$1' . $B . '/', $html);
    return preg_replace_callback('~(' . WS . '(?:srcset|imagesrcset)=")([^"]*)"~u', fn($m) => $m[1] . preg_replace('~(^|,' . WS . '*)/(?!/)~u', '$1' . $B . '/', $m[2]) . '"', $html);
}

// Собирает все файлы. Возвращает [относительный путь => содержимое].
function render_site(): array
{
    load_site_data();
    Build::$pages = []; Build::$warnings = [];
    validate_data();
    $css = css_build();
    S::$critical = $css['critical'];
    S::$cssVer = substr(md5($css['full']), 0, 10);

    // Сначала тела всех страниц, потом оболочки: так нумеруются формы (f1, f2, …) — как в прежнем сборщике
    $pages = [page_home()];
    foreach (S::$services as $s) $pages[] = page_service($s);
    array_push($pages, page_ceny(), page_dokumenty(), page_otzyvy(), page_kontakty(), page_politika(), page_not_found());

    $files = ['styles.css' => $css['full']];
    foreach ($pages as $p) {
        $html = rebase_html(shell($p));
        $files[page_file($p['path'])] = $html;
        Build::$pages[] = $p['path'];
        page_warnings($p, $html);
    }

    $today = gmdate('Y-m-d');
    $urls = [];
    foreach ($pages as $p) {
        if (!empty($p['noindex'])) continue;
        $pr = $p['path'] === '/' ? '1.0' : (count(explode('/', $p['path'])) <= 3 ? '0.8' : '0.6');
        $urls[] = '  <url><loc>' . url($p['path']) . '</loc><lastmod>' . $today . '</lastmod><priority>' . $pr . '</priority></url>';
    }
    $files['sitemap.xml'] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n" . implode("\n", $urls) . "\n</urlset>\n";
    $files['robots.txt'] = S::$noindex ? "User-agent: *\nDisallow: /\n" : "User-agent: *\nAllow: /\n\nSitemap: " . url('/sitemap.xml') . "\n";
    if (S::$siteUrl === '') S::$placeholders['ДОМЕН'] = true;
    if (empty(S::$company['metrikaId'])) S::$placeholders['YM_ID (необязательно, Метрика выключена)'] = true;
    return $files;
}

// Подсказки для разработчика: один H1, длина title/description, объём текста
function page_warnings(array $p, string $html): void
{
    $h1 = preg_match_all('~<h1[\s>]~', $html);
    if ($h1 !== 1) Build::$warnings[] = "{$p['path']}: H1 = $h1 (нужен ровно один)";
    $tl = mb_strlen(subst($p['title'])); $dl = mb_strlen(subst($p['description']));
    if ($tl > 60) Build::$warnings[] = "{$p['path']}: title $tl симв. (>60)";
    if ($dl > 160) Build::$warnings[] = "{$p['path']}: description $dl симв. (>160)";
    $text = preg_replace('~\s+~u', ' ', preg_replace('~<script[\s\S]*?</script>|<style[\s\S]*?</style>|<svg[\s\S]*?</svg>|<[^>]+>~', ' ', $html));
    if ($p['path'] !== '/404.html' && mb_strlen($text) < 2500) Build::$warnings[] = "{$p['path']}: мало текста (" . mb_strlen($text) . ' симв.)';
}

// Полная сборка с записью на диск. Возвращает краткий отчёт.
function build_site(): array
{
    return with_lock(function () {
        $t0 = microtime(true);
        $files = render_site();
        $out = rtrim(cfg('out_dir'), '/\\');
        ensure_dir($out);

        $pub = realpath(cfg('public_dir'));
        if ($pub && $pub !== realpath($out)) mirror_static($pub, $out, array_keys($files));

        $changed = 0;
        foreach ($files as $rel => $content) {
            $f = "$out/$rel";
            if (is_file($f) && file_get_contents($f) === $content) continue;
            write_file_atomic($f, $content);
            $changed++;
        }
        // Страницы, которые больше не собираются (удалённая услуга), убираем — они производные от данных
        $manifestFile = storage_path('build-manifest-' . substr(md5($out), 0, 8) . '.json');
        $prev = is_file($manifestFile) ? read_json($manifestFile) : [];
        $removed = 0;
        foreach (array_diff($prev, array_keys($files)) as $rel) {
            $f = "$out/$rel";
            if (is_file($f)) { unlink($f); $removed++; }
            $dir = dirname($f);
            if ($dir !== $out && is_dir($dir) && count(scandir($dir)) === 2) rmdir($dir);
        }
        write_file_atomic($manifestFile, json_pretty(array_keys($files)));
        return ['pages' => count(Build::$pages), 'changed' => $changed, 'removed' => $removed, 'ms' => (int)round((microtime(true) - $t0) * 1000)];
    });
}

// Для работы на своём компьютере: статика из public копируется в out, лишнее удаляется.
// Служебные папки и файлы в корне out (.git превью и т.п.) не трогаются.
function mirror_static(string $src, string $dst, array $generated): void
{
    $keep = array_flip($generated);
    $seen = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($src) + 1));
        $seen[$rel] = true;
        $to = "$dst/$rel";
        if (is_file($to) && filesize($to) === $f->getSize() && filemtime($to) >= $f->getMTime()) continue;
        ensure_dir(dirname($to));
        copy($f->getPathname(), $to);
    }
    if (!is_dir($dst)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dst, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($dst) + 1));
        if ($rel[0] === '.' && !isset($seen[$rel])) continue; // .git, .nojekyll в корне превью
        if (str_starts_with($rel, '.git/')) continue;
        if ($f->isDir()) { if (count(scandir($f->getPathname())) === 2) rmdir($f->getPathname()); continue; }
        if (!isset($seen[$rel]) && !isset($keep[$rel])) unlink($f->getPathname());
    }
}
