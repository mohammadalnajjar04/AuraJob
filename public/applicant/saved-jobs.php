<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';

$user = hirepeak_require_role('applicant');
$uid = (int) $user['id'];

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];

$savedJobs = [];
$loadError = false;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT s.saved_at,
                j.id, j.title, j.location, j.salary_min, j.salary_max,
                j.employment_type, j.work_mode, j.experience_level,
                j.status, j.created_at,
                c.name AS company_name
         FROM saved_jobs s
         INNER JOIN jobs j ON j.id = s.job_id
         INNER JOIN companies c ON c.id = j.company_id
         WHERE s.applicant_id = ?
         ORDER BY s.saved_at DESC'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $savedJobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (\Throwable $e) {
    error_log('[AuraJob] saved-jobs page load failed: ' . $e->getMessage());
    $loadError = true;
}

$saveCsrf = hirepeak_csrf_token();

$saveFlash = '';
if (isset($_SESSION['hp_save_flash']) && $_SESSION['hp_save_flash'] !== '') {
    $saveFlash = (string) $_SESSION['hp_save_flash'];
    unset($_SESSION['hp_save_flash']);
}
$saveFlashError = '';
if (isset($_SESSION['hp_save_flash_error']) && $_SESSION['hp_save_flash_error'] !== '') {
    $saveFlashError = (string) $_SESSION['hp_save_flash_error'];
    unset($_SESSION['hp_save_flash_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Saved Jobs — AuraJob</title>
  <meta name="description" content="View and manage your saved jobs on AuraJob.">
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

      <?php if ($loadError): ?>
        <div class="track-topbar">
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
              <li><a href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Dashboard</a></li>
            </ol>
          </nav>
          <a class="jd-back" href="<?php echo hirepeak_url('jobs.php'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 12H5"/>
              <path d="m11 18-6-6 6-6"/>
            </svg>
            Find Jobs
          </a>
        </div>

        <section class="dash-alert" role="alert" aria-labelledby="saved-load-error-title">
          <h1 id="saved-load-error-title">Something went wrong on our side.</h1>
          <p>We couldn't load your saved jobs right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('applicant/saved-jobs.php'); ?>">Try again</a>
        </section>

      <?php else: ?>

        <header class="dash-head">
          <div>
            <p class="dash-eyebrow">Saved jobs</p>
            <h1 class="dash-title">Saved Jobs</h1>
            <p class="dash-text"><?php echo (int) count($savedJobs); ?> job<?php echo (int) count($savedJobs) === 1 ? '' : 's'; ?> saved.</p>
          </div>
          <a class="btn btn--primary dash-head__cta" href="<?php echo hirepeak_url('jobs.php'); ?>">
            Find Jobs
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </a>
        </header>

        <?php if ($saveFlash !== ''): ?>
          <p class="dash-flash" role="status"><?php echo hirepeak_esc($saveFlash); ?></p>
        <?php endif; ?>
        <?php if ($saveFlashError !== ''): ?>
          <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($saveFlashError); ?></p>
        <?php endif; ?>

        <?php if ($savedJobs === []): ?>
          <section class="empty-state">
            <div class="empty-state__icon" aria-hidden="true">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
              </svg>
            </div>
            <h2 class="empty-state__title">No saved jobs yet.</h2>
            <p class="empty-state__text">Save roles you're interested in and come back to them anytime.</p>
            <div class="empty-state__actions">
              <a class="btn btn--primary" href="<?php echo hirepeak_url('jobs.php'); ?>">Explore Jobs</a>
            </div>
          </section>

        <?php else: ?>

          <div class="mini-grid saved-jobs-grid">
            <?php foreach ($savedJobs as $job): ?>
              <div class="saved-job">
                <?php echo hirepeak_dashboard_job_card($job); ?>
                <form class="save-form saved-job__remove" method="post" action="<?php echo hirepeak_url('save-job.php'); ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc($saveCsrf); ?>">
                  <input type="hidden" name="job_id" value="<?php echo (int) ($job['id'] ?? 0); ?>">
                  <input type="hidden" name="action" value="unsave">
                  <input type="hidden" name="return_to" value="saved">
                  <button class="btn btn--ghost btn--sm saved-job__remove-btn" type="submit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="m15 9-6 6"/>
                      <path d="m9 9 6 6"/>
                    </svg>
                    Unsave
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>

        <?php endif; ?>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>