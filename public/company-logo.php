<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Services/company-logo.php';

$file = isset($_GET['file']) ? (string) $_GET['file'] : '';
$relative = 'company-logos/' . basename($file);

$absolute = hirepeak_resolve_company_logo($relative);
if ($absolute === null) {
    http_response_code(404);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($absolute);

if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
    http_response_code(404);
    exit;
}

$size = filesize($absolute);
if ($size === false) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) $size);
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');

readfile($absolute);
