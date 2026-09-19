<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Services/cv.php';

$user = hirepeak_require_role('employer');
$uid = (int) $user['id'];

$appIdRaw = (string) ($_GET['id'] ?? '');
if (preg_match('/^[1-9][0-9]*$/D', $appIdRaw) !== 1) {
    http_response_code(404);
    exit;
}
$appId = (int) $appIdRaw;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT ap.cv_path, u.name AS applicant_name
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN users u ON u.id = a.applicant_id
         LEFT JOIN applicant_profiles ap ON ap.user_id = a.applicant_id
         WHERE a.id = ? AND j.employer_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $appId, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if ($row === null) {
        http_response_code(404);
        exit;
    }

    $absolute = hirepeak_resolve_cv($row['cv_path'] ?? null);
    if ($absolute === null) {
        http_response_code(404);
        exit;
    }

    $name = trim((string) ($row['applicant_name'] ?? 'Applicant'));
    hirepeak_send_cv_download($absolute, ($name !== '' ? $name : 'Applicant') . '-CV.pdf');
} catch (\Throwable $e) {
    error_log('[AuraJob] employer CV download failed: ' . $e->getMessage());
    http_response_code(404);
    exit;
}
