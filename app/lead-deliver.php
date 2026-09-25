<?php
// Отправка заявки на почту. Настройки — в админке (storage/settings.json).
declare(strict_types=1);

function lead_deliver(string $text, string $subject): array
{
    $set = settings_get();
    $res = [];
    $emails = $set['leadEmails'];
    if (!$emails) {
        $c = read_json(cfg('data_dir') . '/company.json');
        if (!empty($c['email'])) $emails = [$c['email']];
    }
    if ($emails && function_exists('mail')) {
        $host = preg_replace('~[^a-z0-9.-]~i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        $from = $set['mailFrom'] ?: 'noreply@' . preg_replace('~^www\.~', '', $host);
        $headers = "From: =?UTF-8?B?" . base64_encode('Сайт SANITAR') . "?= <$from>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
        $res['mail'] = @mail(implode(', ', $emails), '=?UTF-8?B?' . base64_encode($subject) . '?=', chunk_split(base64_encode($text)), $headers);
    }
    return $res;
}

