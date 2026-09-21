<?php
declare(strict_types=1);
/**
 * Приём заявок с сайта ДЕЗЦЕНТР SANITAR.
 * Настройки: скопируйте config.example.php в config.php и заполните, либо задайте переменные окружения
 * MAIL_TO, MAIL_FROM, SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_SECURE (ssl|tls|пусто), TG_BOT_TOKEN, TG_CHAT_ID.
 * Заявка уходит на почту и дублируется в Telegram. Успех, если сработал хотя бы один канал.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function out(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    out(405, ['ok' => false, 'error' => 'method']);
}

// Запросы принимаем только с нашего сайта (если браузер передал Origin)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $oh = parse_url($origin, PHP_URL_HOST);
    $sh = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($oh !== $sh) {
        out(403, ['ok' => false, 'error' => 'origin']);
    }
}

$cfgFile = __DIR__ . '/config.php';
$cfg = is_file($cfgFile) ? (array)(require $cfgFile) : [];
$conf = static function (string $key, string $default = '') use ($cfg): string {
    $v = $cfg[$key] ?? getenv(strtoupper($key));
    return ($v === false || $v === null || $v === '') ? $default : (string)$v;
};

/** Чистит значение поля: без тегов и управляющих символов, с ограничением длины. */
$field = static function (string $key, int $max = 200): string {
    $v = isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
    $v = strip_tags($v);
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '';
    $v = trim(preg_replace('/\s{2,}/u', ' ', $v) ?? '');
    return mb_substr($v, 0, $max);
};

// 1) Антиспам: honeypot — бот заполнил скрытое поле. Отвечаем «успехом», ничего не отправляя.
if ($field('website', 50) !== '') {
    out(200, ['ok' => true]);
}
// 2) Время заполнения формы (мс): быстрее 3 секунд — отбой
if ((int)($_POST['t'] ?? 0) < 3000) {
    out(422, ['ok' => false, 'error' => 'too_fast']);
}
// 3) Согласие на обработку данных — проверяем и на сервере
if (empty($_POST['consent'])) {
    out(422, ['ok' => false, 'error' => 'consent']);
}
// 4) Обязательные поля
$name = $field('name', 80);
if (mb_strlen($name) < 2) {
    out(422, ['ok' => false, 'error' => 'name']);
}
$digits = preg_replace('/\D+/', '', $field('phone', 30)) ?? '';
if (strlen($digits) === 11 && $digits[0] === '8') {
    $digits = '7' . substr($digits, 1);
}
if (strlen($digits) !== 11 || $digits[0] !== '7') {
    out(422, ['ok' => false, 'error' => 'phone']);
}
$phone = '+7 (' . substr($digits, 1, 3) . ') ' . substr($digits, 4, 3) . '-' . substr($digits, 7, 2) . '-' . substr($digits, 9, 2);

// 5) Ограничение частоты: не больше 5 заявок в час с одного IP
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0');
$rlFile = sys_get_temp_dir() . '/sanitar_rl_' . md5($ip) . '.json';
$now = time();
$hits = [];
if (is_file($rlFile)) {
    $hits = json_decode((string)@file_get_contents($rlFile), true);
    $hits = is_array($hits) ? array_values(array_filter($hits, static fn($t) => is_int($t) && $t > $now - 3600)) : [];
}
if (count($hits) >= 5) {
    out(429, ['ok' => false, 'error' => 'rate']);
}
$hits[] = $now;
@file_put_contents($rlFile, json_encode($hits), LOCK_EX);

// Текст заявки
$rows = [
    'Имя' => $name,
    'Телефон' => $phone,
    'Услуга' => $field('service', 80),
    'Тип объекта' => $field('object', 80),
    'Адрес/район' => $field('address', 200),
    'Удобное время звонка' => $field('call_time', 80),
    'Комментарий' => $field('message', 1000),
    'Форма' => $field('source', 20) . ' · ' . $field('page', 120),
];
$lines = ['Новая заявка с сайта ДЕЗЦЕНТР SANITAR'];
foreach ($rows as $label => $value) {
    if ($value !== '' && $value !== ' · ') {
        $lines[] = $label . ': ' . $value;
    }
}
$lines[] = 'Время: ' . date('d.m.Y H:i') . ' (сервер)';
$text = implode("\n", $lines);

$sent = false;

// Telegram
$tgToken = $conf('tg_bot_token');
$tgChat = $conf('tg_chat_id');
if ($tgToken !== '' && $tgChat !== '' && function_exists('curl_init')) {
    $ch = curl_init('https://api.telegram.org/bot' . $tgToken . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['chat_id' => $tgChat, 'text' => $text],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp !== false && $code === 200) {
        $sent = true;
    } else {
        error_log('[sanitar] telegram failed: HTTP ' . $code);
    }
}

// Почта (SMTP, если задан хост; иначе встроенная mail())
$mailTo = $conf('mail_to');
if ($mailTo !== '') {
    $from = $conf('mail_from', $mailTo);
    $subject = 'Заявка с сайта: ' . $name . ', ' . $phone;
    if ($conf('smtp_host') !== '') {
        $ok = smtp_send([
            'host' => $conf('smtp_host'),
            'port' => (int)$conf('smtp_port', '465'),
            'secure' => $conf('smtp_secure', 'ssl'),
            'user' => $conf('smtp_user'),
            'pass' => $conf('smtp_pass'),
        ], $from, $mailTo, $subject, $text);
    } else {
        $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
        $ok = @mail($mailTo, '=?UTF-8?B?' . base64_encode($subject) . '?=', chunk_split(base64_encode($text)), $headers);
    }
    if ($ok) {
        $sent = true;
    } else {
        error_log('[sanitar] mail failed');
    }
}

if (!$sent) {
    out(500, ['ok' => false, 'error' => 'delivery']);
}
out(200, ['ok' => true]);

/** Минимальный SMTP-клиент (AUTH LOGIN, SSL или STARTTLS) без внешних библиотек. */
function smtp_send(array $c, string $from, string $to, string $subject, string $body): bool
{
    $secure = $c['secure'];
    $target = ($secure === 'ssl' ? 'ssl://' : '') . $c['host'] . ':' . $c['port'];
    $fp = @stream_socket_client($target, $errno, $errstr, 10);
    if (!$fp) {
        return false;
    }
    stream_set_timeout($fp, 10);
    $read = static function () use ($fp): string {
        $r = '';
        while (($l = fgets($fp, 515)) !== false) {
            $r .= $l;
            if (isset($l[3]) && $l[3] === ' ') {
                break;
            }
        }
        return $r;
    };
    $cmd = static function (string $s, string $expect) use ($fp, $read): bool {
        fwrite($fp, $s . "\r\n");
        return strncmp($read(), $expect, strlen($expect)) === 0;
    };
    $host = preg_replace('/[^a-z0-9.\-]/i', '', (string)($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
    $read();
    $ok = $cmd('EHLO ' . $host, '250');
    if ($ok && $secure === 'tls') {
        $ok = $cmd('STARTTLS', '220') && stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) && $cmd('EHLO ' . $host, '250');
    }
    if ($ok && $c['user'] !== '') {
        $ok = $cmd('AUTH LOGIN', '334') && $cmd(base64_encode($c['user']), '334') && $cmd(base64_encode($c['pass']), '235');
    }
    $ok = $ok && $cmd('MAIL FROM:<' . $from . '>', '250') && $cmd('RCPT TO:<' . $to . '>', '250') && $cmd('DATA', '354');
    if ($ok) {
        $msg = 'From: ' . $from . "\r\n"
            . 'To: ' . $to . "\r\n"
            . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
            . 'Date: ' . date('r') . "\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($body)) . "\r\n.";
        $ok = $cmd($msg, '250');
    }
    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $ok;
}
