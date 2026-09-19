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

$jobTitle = '';
$alreadyClosed = false;
$formError = '';

try {
    $db = hirepeak_db();

    $stmt = $db->prepare('SELECT title, status FROM jobs WHERE id = ? AND employer_id = ? LIMIT 1');
    $stmt->bind_param('ii', $jobId, $uid);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($job === null) {
        hirepeak_job_not_found();
    }

    $jobTitle = (string) $job['title'];
    $alreadyClosed = ($job['status'] ?? '') === 'closed';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
            $formError = 'Your session expired. Please try again.';
            hirepeak_csrf_token(true);
        } else {
            $stmt = $db->prepare('SELECT status FROM jobs WHERE id = ? AND employer_id = ? LIMIT 1');
            $stmt->bind_param('ii', $jobId, $uid);
            $stmt->execute();
            $fresh = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($fresh === null) {
                hirepeak_job_not_found();
            }

            if (($fresh['status'] ?? '') === 'closed') {
                $_SESSION['job_flash'] = 'Job is already closed.';
            } else {
                $stmt = $db->prepare('UPDATE jobs SET status = \'closed\' WHERE id = ? AND employer_id = ?');
                $stmt->bind_param('ii', $jobId, $uid);
                $stmt->execute();
                $stmt->close();
                $_SESSION['job_flash'] = 'Job closed successfully.';
            }

            header('Location: ' . hirepeak_url('employer/my-jobs.php?notice=1'));
            exit;
        }
    }
} catch (\Throwable $e) {
    error_log('[AuraJob] close job failed: ' . $e->getMessage());
    hirepeak_job_not_found();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Close Job — AuraJob</title>
  <meta name="description" content="Close a job posting on AuraJob.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="/assets/images/aurajob-mark.png" type="image/png">
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
          <h1 class="dash-title">Close Job</h1>
          <p class="dash-text"><?php echo hirepeak_esc($jobTitle !== '' ? $jobTitle : 'Manage this posting.'); ?></p>
        </div>
        <a class="btn btn--ghost dash-head__cta" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">My Jobs</a>
      </header>

      <?php if ($formError !== ''): ?>
        <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($formError); ?></p>
      <?php endif; ?>

      <?php if ($alreadyClosed): ?>

        <section class="empty-state">
          <div class="empty-state__icon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/>
              <path d="M7 12h10"/>
            </svg>
          </div>
          <h2 class="empty-state__title">This job is already closed.</h2>
          <p class="empty-state__text">No new applications can be submitted. Existing applications are untouched.</p>
          <div class="empty-state__actions">
            <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">Back to My Jobs</a>
            <a class="btn btn--outline" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $jobId); ?>">View Job</a>
          </div>
        </section>

      <?php else: ?>

        <section class="dash-panel dash-panel--full" aria-labelledby="close-job-title">
          <h2 class="panel-head__title" id="close-job-title">Close this job?</h2>
          <p class="close-job__text">
            You're about to close <strong><?php echo hirepeak_esc($jobTitle); ?></strong>.
            Candidates will no longer be able to submit new applications.
          </p>
          <p class="close-job__text">Your published listing and any existing applications stay saved.</p>
          <div class="profile-form__actions">
            <a class="btn btn--ghost" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">Cancel</a>
            <form class="inline-form" action="<?php echo hirepeak_url('employer/close-job.php?id=' . (int) $jobId); ?>" method="post">
              <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">
              <button class="btn btn--danger" type="submit">Close Job</button>
            </form>
          </div>
        </section>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>