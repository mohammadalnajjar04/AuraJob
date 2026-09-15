<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/auth.php';
require dirname(__DIR__) . '/app/Core/db.php';
require dirname(__DIR__) . '/app/Support/html.php';

/*
 * Save / Unsave a job for the signed-in applicant — real persistence in the
 * saved_jobs junction table. Identity always comes from the authenticated
 * session; nothing in the request can pick the owner row.
 *
 * Security model:
 *  - POST only. GET requests never mutate; they bounce to jobs.php.
 *  - CSRF is verified before any read of a rejected/mutating path.
 *  - Only the 'applicant' role may save. Guests are sent to login (or get a
 *    401 JSON response), employers get a 403 (never a save affordance).
 *  - job_id must be a plain positive integer (whole-string guard) and must
 *    reference an existing job that is not a draft — drafts are only visible to
 *    their owning employer, so they are not savable by applicants. This keeps
 *    Save behavior consistent with Job Details visibility for active/closed jobs.
 *  - Duplicate saves are absorbed by the composite PRIMARY KEY
 *    (applicant_id, job_id); the DELETE is scoped to BOTH columns so an
 *    applicant can only ever remove their own saved row.
 *  - Responses: standard form POSTs use PRG (303 back to a safe, server-chosen
 *    page with a one-time flash). fetch/XHR requests receive JSON. JS is never
 *    the security layer — both request styles run the exact same server checks.
 */

hirepeak_start_session();

function hirepeak_save_wants_json(): bool
{
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        || stripos($accept, 'application/json') !== false;
}

function hirepeak_save_fail(int $status, string $message, string $redirect): void
{
    if (hirepeak_save_wants_json()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'error' => $message]);
        exit;
    }

    $_SESSION['hp_save_flash_error'] = $message;

    if ($redirect !== '') {
        header('Location: ' . hirepeak_url($redirect), true, 303);
        exit;
    }

    http_response_code($status);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    hirepeak_save_fail(405, 'Only POST requests are allowed.', 'jobs.php');
}

$user = hirepeak_user();

if ($user === null) {
    hirepeak_save_fail(401, 'Sign in to save jobs.', 'login.php');
}

if (($user['role'] ?? '') !== 'applicant') {
    hirepeak_save_fail(403, 'Only applicant accounts can save jobs.', 'jobs.php');
}

$uid = (int) $user['id'];

if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    hirepeak_csrf_token(true);
    hirepeak_save_fail(400, 'Your session expired. Please try again.', 'jobs.php');
}

$action = (string) ($_POST['action'] ?? '');
if (!in_array($action, ['save', 'unsave'], true)) {
    hirepeak_save_fail(400, 'Unknown action.', 'jobs.php');
}

/* Whole-string guard: only plain positive integers ever reach the database,
 * exactly like the apply flow. Anything else is a clean rejection with no query. */
$rawJob = (string) ($_POST['job_id'] ?? '');
if (preg_match('/^[1-9][0-9]*$/D', $rawJob) !== 1) {
    hirepeak_save_fail(400, 'Invalid job.', 'jobs.php');
}
$jobId = (int) $rawJob;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare('SELECT status FROM jobs WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $jobId);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (\Throwable $e) {
    error_log('[hirepeak] saved-jobs job lookup failed: ' . $e->getMessage());
    hirepeak_save_fail(500, 'Something went wrong. Please try again.', 'jobs.php');
}

if ($job === null || ($job['status'] ?? '') === 'draft') {
    hirepeak_save_fail(404, 'That job is not available to save.', 'jobs.php');
}

try {
    $db = hirepeak_db();

    if ($action === 'save') {
        try {
            $stmt = $db->prepare('INSERT INTO saved_jobs (applicant_id, job_id) VALUES (?, ?)');
            $stmt->bind_param('ii', $uid, $jobId);
            $stmt->execute();
            $stmt->close();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() !== 1062) {
                throw $e;
            }
            /* Duplicate-key race: the composite PRIMARY KEY already held the row. */
        }
    } else {
        $stmt = $db->prepare('DELETE FROM saved_jobs WHERE applicant_id = ? AND job_id = ?');
        $stmt->bind_param('ii', $uid, $jobId);
        $stmt->execute();
        $stmt->close();
    }
} catch (\Throwable $e) {
    error_log('[hirepeak] saved-jobs write failed: ' . $e->getMessage());
    hirepeak_save_fail(500, 'We couldn\'t update your saved jobs right now. Please try again.', 'jobs.php');
}

/* Server-chosen redirect targets only — never a raw client URL, so no open
 * redirect. Defaults to the jobs listing. */
$returnTo = (string) ($_POST['return_to'] ?? '');
$dest = hirepeak_url('jobs.php');
if ($returnTo === 'job-details') {
    $dest = hirepeak_url('job-details.php?id=' . $jobId);
} elseif ($returnTo === 'saved') {
    $dest = hirepeak_url('applicant/saved-jobs.php');
} elseif ($returnTo === 'dashboard') {
    $dest = hirepeak_url('applicant/dashboard.php');
}

if (hirepeak_save_wants_json()) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => true, 'saved' => $action === 'save', 'id' => $jobId]);
    exit;
}

$_SESSION['hp_save_flash'] = $action === 'save'
    ? 'Job saved. You can find it in Saved Jobs.'
    : 'Job removed from your saved jobs.';

header('Location: ' . $dest, true, 303);
exit;