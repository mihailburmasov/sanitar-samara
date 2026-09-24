<?php
// Сборка сайта из командной строки: php app/bin/build.php
// Превью в подпапке: SITE_URL=https://user.github.io SITE_BASE=/repo NOINDEX=1 OUT_DIR=dist-pages php app/bin/build.php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

try {
    $r = build_site();
    echo "Собрано страниц: {$r['pages']}, изменено файлов: {$r['changed']}, удалено: {$r['removed']}, {$r['ms']} мс  (адрес " . site() . ")\n";
    if (Build::$warnings) echo "\nПредупреждения:\n - " . implode("\n - ", Build::$warnings) . "\n";
    if (S::$placeholders) echo "\nНе заполнено (плейсхолдеры {{...}}):\n - " . implode("\n - ", array_keys(S::$placeholders)) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Ошибка сборки: ' . $e->getMessage() . "\n");
    exit(1);
}
