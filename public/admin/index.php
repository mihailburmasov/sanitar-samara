<?php
// Вход в админку. Ядро лежит либо вне корня сайта (Beget: ../../app),
// либо внутри корня (Timeweb изолирует PHP в папке сайта: ../app) — здесь только передача запроса.
if (is_file(__DIR__ . '/../app/admin/index.php')) { require __DIR__ . '/../app/admin/index.php'; exit; }
if (is_file(__DIR__ . '/../../app/admin/index.php')) { require __DIR__ . '/../../app/admin/index.php'; exit; }
http_response_code(404);
