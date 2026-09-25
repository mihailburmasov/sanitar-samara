<?php
// Приём заявок с форм сайта: проверка, запись в журнал (storage/leads), отправка на почту.
// Ответ — JSON {ok}. Заявка сохраняется в журнале, даже если почта не сработала: её видно в админке.
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin/ops.php';
require_once __DIR__ . '/lead-deliver.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function lead_reply(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_FLAGS);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') lead_reply(405, ['ok' => false, 'error' => 'Только POST']);

// Запросы принимаем только с нашего сайта (если браузер передал Origin)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== preg_replace('~:\d+$~', '', (string)($_SERVER['HTTP_HOST'] ?? ''))) lead_reply(403, ['ok' => false, 'error' => 'origin']);

// Значение поля: без тегов и управляющих символов, с ограничением длины
$field = function (string $k, int $max): string {
    $v = is_string($_POST[$k] ?? null) ? $_POST[$k] : '';
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', strip_tags($v)) ?? '';
    return mb_substr(trim($v), 0, $max);
};

// бот заполнил скрытое поле — делаем вид, что всё хорошо
if ($field('website', 200) !== '') lead_reply(200, ['ok' => true]);
// форма заполнена быстрее 3 секунд — бот
if ((int)($_POST['t'] ?? 0) < 3000) lead_reply(422, ['ok' => false, 'error' => 'Проверьте данные и отправьте ещё раз']);
if (empty($_POST['consent'])) lead_reply(422, ['ok' => false, 'error' => 'Нужно согласие на обработку данных']);

$name = $field('name', 80);
$digits = preg_replace('~\D~', '', $field('phone', 30));
if (strlen($digits) === 11 && $digits[0] === '8') $digits = '7' . substr($digits, 1);
if (mb_strlen($name) < 2) lead_reply(422, ['ok' => false, 'error' => 'Укажите имя']);
if (strlen($digits) !== 11 || $digits[0] !== '7') lead_reply(422, ['ok' => false, 'error' => 'Укажите телефон']);

$sources = ['full' => 'форма заявки', 'mini' => 'быстрый расчёт', 'modal' => 'кнопка «Вызвать специалиста»'];
$lead = [
    'date' => date('Y-m-d H:i:s'),
    'name' => $name,
    'phone' => '+7 (' . substr($digits, 1, 3) . ') ' . substr($digits, 4, 3) . '-' . substr($digits, 7, 2) . '-' . substr($digits, 9, 2),
    'service' => $field('service', 80),
    'object' => $field('object', 80),
    'address' => $field('address', 200),
    'callTime' => $field('call_time', 80),
    'message' => $field('message', 1000),
    'subject' => $sources[$field('source', 20)] ?? 'заявка с сайта',
    'page' => $field('page', 200),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
];
$lead = array_filter($lead, fn($v) => $v !== '');

// Заявка всегда пишется в журнал. Лимит (30 в час с одного адреса — офис за одним IP тоже пройдёт)
// только придерживает уведомления на почту, чтобы бот не завалил их спамом.
try {
    $notify = with_lock(function () use (&$lead) {
        $f = storage_path('lead-rate.json');
        $all = is_file($f) ? read_json($f) : [];
        foreach ($all as $ip => $list) { $all[$ip] = array_values(array_filter($list, fn($t) => $t > time() - 3600)); if (!$all[$ip]) unset($all[$ip]); }
        $ip = $lead['ip'] ?? '';
        $notify = count($all[$ip] ?? []) < 30;
        $all[$ip][] = time();
        write_file_atomic($f, json_pretty($all));
        if (!$notify) $lead['muted'] = true;
        ensure_dir(storage_path('leads'));
        if (file_put_contents(storage_path('leads/' . date('Y-m') . '.jsonl'), json_encode($lead, JSON_FLAGS) . "\n", FILE_APPEND | LOCK_EX) === false) throw new RuntimeException('journal');
        return $notify;
    });
} catch (Throwable $e) {
    error_log('lead: ' . $e->getMessage());
    lead_reply(500, ['ok' => false, 'error' => 'Не удалось сохранить заявку']);
}
if (!$notify) lead_reply(200, ['ok' => true]);

$labels = ['name' => 'Имя', 'phone' => 'Телефон', 'service' => 'Услуга', 'object' => 'Тип объекта', 'address' => 'Адрес/район', 'callTime' => 'Удобное время звонка', 'message' => 'Комментарий', 'subject' => 'Форма', 'page' => 'Страница'];
$lines = ['Новая заявка с сайта ДЕЗЦЕНТР SANITAR'];
foreach ($labels as $k => $label) if (isset($lead[$k])) $lines[] = "$label: {$lead[$k]}";
$lines[] = 'Время: ' . date('d.m.Y H:i');

// Посетителю отвечаем сразу (заявка уже в журнале), письмо уходит после ответа
$body = json_encode(['ok' => true]);
ignore_user_abort(true);
http_response_code(200);
header('Content-Length: ' . strlen($body));
header('Connection: close');
echo $body;
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
else { while (ob_get_level() > 0) ob_end_flush(); flush(); }
lead_deliver(implode("\n", $lines), 'Заявка с сайта: ' . $lead['name'] . ', ' . $lead['phone']);
