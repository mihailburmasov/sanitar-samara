<?php
// Отправка заявки на почту. Кому — в админке (storage/settings.json).
// Если в серверном app/config.local.php задан ящик сайта ('smtp' => ['user' => ..., 'pass' => ...]),
// письмо уходит через SMTP от его имени (так его подписывает хостинг и меньше шансов попасть в спам);
// не вышло — запасной путь через mail() с тем же отправителем.
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
    if (!$emails) return $res;
    $smtp = cfg('smtp');
    $host = preg_replace('~^www\.~', '', preg_replace('~[^a-z0-9.-]~i', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $from = !empty($smtp['user']) ? $smtp['user'] : ($set['mailFrom'] ?: 'noreply@' . $host);
    $fromHdr = '=?UTF-8?B?' . base64_encode('Сайт SANITAR') . "?= <$from>";
    $subj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $body = chunk_split(base64_encode($text));
    $mime = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
    if (!empty($smtp['user']) && !empty($smtp['pass'])) {
        $domain = substr((string)strrchr($from, '@'), 1);
        $msg = 'Date: ' . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(12)) . "@$domain>\r\nFrom: $fromHdr\r\nTo: " . implode(', ', $emails) . "\r\nSubject: $subj\r\n$mime\r\n\r\n$body";
        $err = smtp_send($smtp, $from, $emails, $msg);
        $res['smtp'] = $err === '' ? true : $err;
        if ($err === '') return $res;
    }
    if (function_exists('mail')) {
        // -f — адрес возврата совпадает с отправителем, иначе хостинг подставит служебный и проверка SPF не сойдётся с доменом
        $extra = filter_var($from, FILTER_VALIDATE_EMAIL) ? '-f' . $from : '';
        $res['mail'] = @mail(implode(', ', $emails), $subj, $body, "From: $fromHdr\r\n$mime", $extra);
    }
    return $res;
}

// Отправка через SMTP с входом по логину ящика (по умолчанию smtp.beget.com:465, SSL). Возвращает '' или текст ошибки.
function smtp_send(array $s, string $from, array $to, string $msg): string
{
    if (!function_exists('curl_init')) return 'нет curl';
    $fp = fopen('php://temp', 'r+');
    fwrite($fp, $msg);
    rewind($fp);
    $ch = curl_init('smtps://' . ($s['host'] ?? 'smtp.beget.com') . ':' . (int)($s['port'] ?? 465));
    curl_setopt_array($ch, [CURLOPT_USERNAME => $s['user'], CURLOPT_PASSWORD => $s['pass'], CURLOPT_MAIL_FROM => "<$from>",
        CURLOPT_MAIL_RCPT => array_map(fn($e) => "<$e>", $to), CURLOPT_UPLOAD => true, CURLOPT_INFILE => $fp, CURLOPT_INFILESIZE => strlen($msg),
        CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_RETURNTRANSFER => true]);
    curl_exec($ch);
    $err = curl_errno($ch) ? curl_error($ch) : '';
    curl_close($ch);
    fclose($fp);
    return $err;
}
