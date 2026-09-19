<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Services/cv.php';

$user = hirepeak_require_role('applicant');
$uid = (int) $user['id'];

try {
    $db = hirepeak_db();
    $stmt = $db->prepare(
        'SELECT cv_path
         FROM applicant_profiles
         WHERE user_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    $absolute = hirepeak_resolve_cv($row['cv_path'] ?? null);
    if ($absolute === null) {
        http_response_code(404);
        exit;
    }

    hirepeak_send_cv_download($absolute, 'AuraJob-My-CV.pdf');
} catch (\Throwable $e) {
    error_log('[aurajob] applicant CV download failed: ' . $e->getMessage());
    http_response_code(404);
    exit;
}
