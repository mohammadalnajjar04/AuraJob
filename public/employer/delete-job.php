<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';

$user = hirepeak_require_role('employer');
$uid = (int) $user['id'];

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];

$rawId = (string) ($_GET['id'] ?? '');
if ($rawId === '' || preg_match('/^[1-9][0-9]*$/D', $rawId) !== 1) {
    hirepeak_job_not_found();
}
$jobId = (int) $rawId;

$job = null;
$formError = '';

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.status, COUNT(a.id) AS application_count
         FROM jobs j
         LEFT JOIN applications a ON a.job_id = j.id
         WHERE j.id = ? AND j.employer_id = ?
         GROUP BY j.id, j.title, j.status
         LIMIT 1'
    );
    $stmt->bind_param('ii', $jobId, $uid);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if ($job === null) {
        hirepeak_job_not_found();
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
            $formError = 'Your session expired. Please try again.';
            hirepeak_csrf_token(true);
        } elseif (($job['status'] ?? '') === 'active') {
            $formError = 'Close this job before deleting it.';
        } else {
            $stmt = $db->prepare(
                "DELETE FROM jobs
                 WHERE id = ? AND employer_id = ? AND status IN ('draft','closed')"
            );
            $stmt->bind_param('ii', $jobId, $uid);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected !== 1) {
                $formError = 'This job changed before it could be deleted. Please review it again.';
            } else {
                $_SESSION['job_flash'] = 'Job deleted successfully.';
                header('Location: ' . hirepeak_url('employer/my-jobs.php?notice=1'));
                exit;
            }
        }
    }
} catch (\Throwable $e) {
    error_log('[AuraJob] delete job failed: ' . $e->getMessage());
    $formError = 'Something went wrong deleting this job. Please try again.';
}

$jobStatus = (string) ($job['status'] ?? '');
$appCount = (int) ($job['application_count'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delete Job — AuraJob</title>
  <meta name="description" content="Delete an AuraJob job posting.">
  <meta name="theme-color" content="#ffffff">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <?php require dirname(__DIR__, 2) . '/app/Views/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="dash-main">
    <div class="container">
      <header class="dash-head">
        <div>
          <p class="dash-eyebrow">Employer account</p>
          <h1 class="dash-title">Delete Job</h1>
          <p class="dash-text"><?php echo hirepeak_esc((string) $job['title']); ?></p>
        </div>
        <a class="btn btn--ghost dash-head__cta" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">My Jobs</a>
      </header>

      <?php if ($formError !== ''): ?>
        <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($formError); ?></p>
      <?php endif; ?>

      <?php if ($jobStatus === 'active'): ?>
        <section class="empty-state">
          <h2 class="empty-state__title">Close this job before deleting it.</h2>
          <p class="empty-state__text">Active listings must be closed first so you don't accidentally remove a live role.</p>
          <div class="empty-state__actions">
            <a class="btn btn--danger" href="<?php echo hirepeak_url('employer/close-job.php?id=' . $jobId); ?>">Close Job</a>
            <a class="btn btn--outline" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">Back to My Jobs</a>
          </div>
        </section>
      <?php else: ?>
        <section class="dash-panel dash-panel--full" aria-labelledby="delete-job-title">
          <h2 class="panel-head__title" id="delete-job-title">Permanently delete this job?</h2>
          <p class="close-job__text">You're about to permanently delete <strong><?php echo hirepeak_esc((string) $job['title']); ?></strong>.</p>
          <?php if ($appCount > 0): ?>
            <p class="close-job__text">This job has <?php echo $appCount === 1 ? '1 application' : $appCount . ' applications'; ?>. Those application records and saved-job links will also be permanently removed.</p>
          <?php else: ?>
            <p class="close-job__text">Any saved-job links for this posting will also be removed automatically.</p>
          <?php endif; ?>
          <p class="close-job__text"><strong>This action cannot be undone.</strong></p>
          <div class="profile-form__actions">
            <a class="btn btn--ghost" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">Keep Job</a>
            <form class="inline-form" action="<?php echo hirepeak_url('employer/delete-job.php?id=' . $jobId); ?>" method="post">
              <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">
              <button class="btn btn--danger" type="submit">Delete Job</button>
            </form>
          </div>
        </section>
      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>
