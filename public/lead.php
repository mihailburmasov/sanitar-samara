<?php
// Приём заявок с форм сайта. Ядро либо рядом с корнем (Beget: ../app),
// либо внутри корня (Timeweb изолирует PHP в папке сайта: ./app).
if (is_file(__DIR__ . '/app/lead.php')) { require __DIR__ . '/app/lead.php'; exit; }
if (is_file(__DIR__ . '/../app/lead.php')) { require __DIR__ . '/../app/lead.php'; exit; }
http_response_code(503);
