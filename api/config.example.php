<?php
// Скопируйте этот файл в config.php и заполните. config.php закрыт от прямого доступа (см. .htaccess).
// Вместо файла можно задать переменные окружения: MAIL_TO, MAIL_FROM, SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_SECURE, TG_BOT_TOKEN, TG_CHAT_ID.
return [
    // Куда приходят заявки
    'mail_to'     => '',              // например: zayavki@ваш-домен.ru
    'mail_from'   => '',              // адрес отправителя; лучше ящик на вашем домене
    // SMTP (если оставить smtp_host пустым, письмо уйдёт стандартной функцией mail() хостинга)
    'smtp_host'   => '',              // например: smtp.yandex.ru
    'smtp_port'   => '465',
    'smtp_secure' => 'ssl',           // ssl (порт 465), tls (порт 587) или пусто
    'smtp_user'   => '',
    'smtp_pass'   => '',
    // Telegram: токен бота от @BotFather, chat_id — от @userinfobot или из getUpdates
    'tg_bot_token' => '',
    'tg_chat_id'   => '',
];
