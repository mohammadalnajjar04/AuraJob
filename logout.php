<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

hirepeak_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Logout requires a POST request.');
}

if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Invalid security token.');
}

hirepeak_logout();

header('Location: index.php');
exit;