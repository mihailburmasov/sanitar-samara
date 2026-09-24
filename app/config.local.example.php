<?php
// Пример настроек для хостинга. Скопируйте в app/config.local.php и поправьте.
// Раскладка на Beget (папка сайта ~/sanitar-samara.ru):
//   ~/sanitar-samara.ru/app          — ядро (этот репозиторий, папка app)
//   ~/sanitar-samara.ru/data         — данные сайта: единственный источник правды
//   ~/sanitar-samara.ru/storage      — пароль, сессии, заявки, история правок, корзина (создаётся сама)
//   ~/sanitar-samara.ru/public_html  — корень сайта: статика из public/ + собранные страницы
return [
    'site_url'    => 'https://sanitar-samara.ru',
    'base_path'   => '',
    'public_dir'  => ROOT_DIR . '/public_html',
    'out_dir'     => ROOT_DIR . '/public_html',
    // Ключ, по которому скрипт заливки просит сервер пересобрать сайт (любая длинная случайная строка)
    'deploy_key'  => '',
];
