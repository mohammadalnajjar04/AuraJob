<?php
declare(strict_types=1);

/**
 * Job Applicants — employer-only applicant pipeline for one of your jobs.
 *
 * Read-only review list for a single owned job (Part 3A). Status changes are
 * deliberately NOT on this page yet — Part 3B adds the POST/CSRF/ownership-
 * scoped update through the shared helper in app/Support/applies-form.php. This
 * page only ever lists applications scoped (via JOIN on jobs.employer_id) to
 * jobs the signed-in employer actually owns, so no cross-owner rows can leak
 * and a foreign job id resolves to a safe not-found page.
 */

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';
require dirname(__DIR__, 2) . '/app/Support/applies-form.php';

$user = hirepeak_require_role('employer');
$uid  = (int) $user['id'];

$jobId = (int) ($_GET['job_id'] ?? 0);
$jobId = $jobId > 0 ? $jobId : 0;

$db      = null;
$loadError = false;
$job     = null;
$pipeline = [];
$applications = [];

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.location, j.status,
                j.employment_type, j.work_mode,
                j.created_at, j.application_deadline
         FROM jobs j
         WHERE j.id = ? AND j.employer_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $jobId, $uid);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if ($job === null) {
        hirepeak_job_not_found();
    }

    $pipeline = hirepeak_application_statuses();

    $statusFilter = (string) ($_GET['status'] ?? '');
    if ($statusFilter !== '' && !isset($pipeline[$statusFilter])) {
        $statusFilter = '';
    }

    $order = ($_GET['sort'] ?? '') === 'oldest' ? 'ASC' : 'DESC';
    $where = 'a.job_id = ?';
    $params = [$jobId];
    $types  = 'i';

    if ($statusFilter !== '') {
        $where .= ' AND a.status = ?';
        $params[] = $statusFilter;
        $types  .= 's';
    }

    $countStmt = $db->prepare(
        'SELECT a.status, COUNT(*) AS c
         FROM applications a
         WHERE a.job_id = ?
         GROUP BY a.status'
    );
    $countStmt->bind_param('i', $jobId);
    $countStmt->execute();
    $countRows = $countStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $countStmt->close();

    $counts = ['total' => 0];
    foreach ($pipeline as $status => $label) {
        $counts[$status] = 0;
    }
    foreach ($countRows as $row) {
        $counts['total'] += (int) $row['c'];
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = (int) $row['c'];
        }
    }

    $stmt = $db->prepare(
        'SELECT a.id, a.applicant_id, a.status, a.applied_at,
                a.cover_letter,
                ap.professional_title,
                ap.location AS applicant_location,
                ap.experience_years,
                u.name AS applicant_name,
                u.email AS applicant_email
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN users u ON u.id = a.applicant_id
         LEFT JOIN applicant_profiles ap ON ap.user_id = a.applicant_id
         WHERE j.employer_id = ? AND ' . $where . '
         ORDER BY a.applied_at ' . $order
    );
    $stmt->bind_param('i' . $types, ...[...[$uid, ...$params]]);
    $stmt->execute();
    $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (\Throwable $e) {
    error_log('[hirepeak] job applicants load failed: ' . $e->getMessage());
    $loadError = true;
}

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $job !== null ? hirepeak_esc((string) $job['title']) . ' — Applicants' : 'Applicants'; ?> — HirePeak</title>
  <meta name="description" content="Review every applicant for your job posting on HirePeak.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='1' y='1' width='22' height='22' rx='7' fill='%234F46E5'/%3E%3Cpath d='M12 4.8 18 16H6Z' fill='%23FFFFFF' stroke='%23FFFFFF' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M12 6.6 8.8 16H5.9Z' fill='%23EEF2FF' stroke='%23EEF2FF' stroke-width='3' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <?php require dirname(__DIR__, 2) . '/app/Views/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="dash-main employer-applicants-page">
    <div class="container">

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="applicants-error-title">
          <h1 id="applicants-error-title">Something went wrong on our side.</h1>
          <p>We couldn't load the applicants for this job right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">Back to My Jobs</a>
        </section>

      <?php else: ?>

        <header class="dash-head dash-head--applicants">
          <div>
            <p class="dash-eyebrow">Employer account</p>
            <h1 class="dash-title">Applicants</h1>
            <p class="dash-text">Review everyone who applied to
              <a class="job-inline-link" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $job['id']); ?>"><?php echo hirepeak_esc((string) $job['title']); ?></a>.</p>
          </div>
          <div class="dash-head__cta-wrap">
            <a class="btn btn--ghost" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">Back to My Jobs</a>
          </div>
        </header>

        <div class="myjobs-toolbar applicants-toolbar">
          <nav class="jobs-tabs" aria-label="Filter applicants by status">
            <a class="jobs-tab<?php echo $statusFilter === '' ? ' is-active' : ''; ?>" href="<?php echo hirepeak_url('employer/applicants.php?job_id=' . (int) $jobId); ?>">All <span class="jobs-tab__count"><?php echo (int) $counts['total']; ?></span></a>
            <?php foreach ($pipeline as $status => $label): ?>
              <a class="jobs-tab<?php echo $statusFilter === $status ? ' is-active' : ''; ?>" href="<?php echo hirepeak_url('employer/applicants.php?job_id=' . (int) $jobId . '&status=' . rawurlencode((string) $status)); ?>"><?php echo hirepeak_esc((string) $label); ?> <span class="jobs-tab__count"><?php echo (int) $counts[$status]; ?></span></a>
            <?php endforeach; ?>
          </nav>
          <div class="myjobs-sort" role="group" aria-label="Sort applicants">
            <span class="field__label">Sort</span>
            <a class="btn btn--ghost btn--sm<?php echo $order === 'DESC' ? ' is-active' : ''; ?>" href="<?php echo hirepeak_url('employer/applicants.php?job_id=' . (int) $jobId . ($statusFilter !== '' ? '&status=' . rawurlencode((string) $statusFilter) : '')); ?>">Newest</a>
            <a class="btn btn--ghost btn--sm<?php echo $order === 'ASC' ? ' is-active' : ''; ?>" href="<?php echo hirepeak_url('employer/applicants.php?job_id=' . (int) $jobId . '&sort=oldest' . ($statusFilter !== '' ? '&status=' . rawurlencode((string) $statusFilter) : '')); ?>">Oldest</a>
          </div>
        </div>

        <?php if ($applications === []): ?>

          <section class="empty-state">
            <div class="empty-state__icon" aria-hidden="true">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/>
                <path d="m15 9-6 6"/>
                <path d="m9 9 6 6"/>
              </svg>
            </div>
            <h2 class="empty-state__title">No <?php echo $statusFilter !== '' ? hirepeak_esc(strtolower((string) $pipeline[$statusFilter])) : ''; ?>applicants yet.</h2>
            <p class="empty-state__text"><?php echo $statusFilter !== '' ? 'Try another filter to see more of this pipeline.' : 'When candidates apply, they\'ll show up here for review.'; ?></p>
            <div class="empty-state__actions">
              <a class="btn btn--outline" href="<?php echo hirepeak_url('employer/applicants.php?job_id=' . (int) $jobId); ?>">View all applicants</a>
            </div>
          </section>

        <?php else: ?>

          <ul class="app-list app-list--candidates">
            <?php foreach ($applications as $application): ?>
              <li class="app-row candidate-card">
                <div class="app-row__main">
                  <h3 class="app-row__title">
                    <a href="<?php echo hirepeak_url('employer/application.php?id=' . (int) $application['id']); ?>"><?php echo hirepeak_esc((string) $application['applicant_name']); ?></a>
                  </h3>
                  <p class="app-row__company">
                    <?php echo hirepeak_esc((string) ($application['professional_title'] ?? '')); ?>
                    <?php if (($application['applicant_location'] ?? '') !== ''): ?>
                      <span class="app-row__dot" aria-hidden="true">&middot;</span>
                      <?php echo hirepeak_esc((string) $application['applicant_location']); ?>
                    <?php endif; ?>
                    <?php if (($application['experience_years'] ?? '') !== ''): ?>
                      <span class="app-row__dot" aria-hidden="true">&middot;</span>
                      <?php echo (int) $application['experience_years']; ?> yrs exp
                    <?php endif; ?>
                  </p>
                  <p class="app-row__meta">
                    <?php echo hirepeak_application_status_badge((string) $application['status']); ?>
                    <span class="app-row__date">Applied <?php echo hirepeak_esc(hirepeak_relative_time((string) $application['applied_at'])); ?></span>
                  </p>
                </div>
                <div class="app-row__actions">
                  <a class="btn btn--outline btn--sm app-row__action app-row__action--primary" href="<?php echo hirepeak_url('employer/application.php?id=' . (int) $application['id']); ?>">Review</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>

        <?php endif; ?>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>
