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

$flash = '';
if (isset($_GET['notice'], $_SESSION['job_flash']) && $_SESSION['job_flash'] !== '') {
    $flash = (string) $_SESSION['job_flash'];
    unset($_SESSION['job_flash']);
}

$statusFilter = (string) ($_GET['status'] ?? '');
$allowedStatuses = hirepeak_job_enum_values()['status'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$sort = (string) ($_GET['sort'] ?? 'newest');
if ($sort !== 'oldest') {
    $sort = 'newest';
}

$statusLabels = [
    'active' => 'Active',
    'draft'  => 'Draft',
    'closed' => 'Closed',
];

$company = null;
$jobRows = [];
$counts = ['total' => 0, 'active' => 0, 'draft' => 0, 'closed' => 0];
$loadError = false;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare('SELECT id FROM companies WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    $stmt = $db->prepare('SELECT status, COUNT(*) AS c FROM jobs WHERE employer_id = ? GROUP BY status');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $grouped = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $counts['total'] = 0;
    foreach ($allowedStatuses as $status) {
        $counts[$status] = 0;
    }
    foreach ($grouped as $row) {
        $counts['total'] += (int) $row['c'];
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = (int) $row['c'];
        }
    }

    $where = 'j.employer_id = ?';
    $params = [$uid];
    $types = 'i';

    if ($statusFilter !== '') {
        $where .= ' AND j.status = ?';
        $params[] = $statusFilter;
        $types .= 's';
    }

    $order = $sort === 'oldest' ? 'ORDER BY j.created_at ASC' : 'ORDER BY j.created_at DESC';

    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.status, j.location, j.employment_type,
                j.work_mode, j.created_at, j.application_deadline,
                COUNT(a.id) AS applications
         FROM jobs j
         LEFT JOIN applications a ON a.job_id = j.id
         WHERE ' . $where . '
         GROUP BY j.id, j.title, j.status, j.location, j.employment_type,
                  j.work_mode, j.created_at, j.application_deadline
         ' . $order
    );
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $jobRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (\Throwable $e) {
    error_log('[hirepeak] my jobs load failed: ' . $e->getMessage());
    $loadError = true;
}

$deadlineLabel = static function (?string $ts): string {
    if ($ts === null || $ts === '') {
        return '';
    }
    return date('M j, Y', strtotime($ts));
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Jobs — AuraJob</title>
  <meta name="description" content="Manage your open roles, drafts, and hiring activity on AuraJob.">
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

  <main id="main" class="dash-main myjobs-page">
    <div class="container">

      <header class="dash-head">
        <div>
          <p class="dash-eyebrow">Employer account</p>
          <h1 class="dash-title">My Jobs</h1>
          <p class="dash-text">Manage your open roles, drafts, and hiring activity.</p>
        </div>
        <a class="btn btn--primary dash-head__cta" href="<?php echo hirepeak_url('employer/post-job.php'); ?>">Post a Job</a>
      </header>

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="myjobs-error-title">
          <h2 id="myjobs-error-title">We couldn't load your jobs right now.</h2>
          <p>Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">Try again</a>
        </section>
      <?php else: ?>

        <?php if ($flash !== ''): ?>
          <p class="dash-flash" role="status"><?php echo hirepeak_esc($flash); ?></p>
        <?php endif; ?>

        <?php if ($company === null): ?>
          <section class="dash-alert" role="note" aria-labelledby="myjobs-company-title">
            <h2 id="myjobs-company-title">Posting jobs requires a company profile.</h2>
            <p>Set yours up first so candidates know who is hiring, then come back to publish roles.</p>
            <a class="btn btn--outline" href="<?php echo hirepeak_url('employer/company-profile.php'); ?>">Complete Company Profile</a>
          </section>
        <?php endif; ?>

        <?php if ($counts['total'] === 0): ?>

          <section class="empty-state">
            <div class="empty-state__icon" aria-hidden="true">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="7" width="18" height="14" rx="2"/>
                <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                <path d="M3 12h18"/>
              </svg>
            </div>
            <h2 class="empty-state__title">No jobs yet.</h2>
            <p class="empty-state__text">Post your first role and start connecting with candidates.</p>
            <?php if ($company !== null): ?>
              <div class="empty-state__actions">
                <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/post-job.php'); ?>">Post a Job</a>
              </div>
            <?php endif; ?>
          </section>

        <?php else: ?>

          <div class="myjobs-toolbar" aria-label="Job management controls">
            <nav class="jobs-tabs" aria-label="Filter jobs by status">
              <a class="jobs-tab<?php echo $statusFilter === '' ? ' is-active' : ''; ?>" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">All <span class="jobs-tab__count"><?php echo (int) $counts['total']; ?></span></a>
              <?php foreach ($allowedStatuses as $status): ?>
                <a class="jobs-tab<?php echo $statusFilter === $status ? ' is-active' : ''; ?>" href="<?php echo hirepeak_url('employer/my-jobs.php?status=' . rawurlencode($status)); ?>"><?php echo hirepeak_esc($statusLabels[$status]); ?> <span class="jobs-tab__count"><?php echo (int) $counts[$status]; ?></span></a>
              <?php endforeach; ?>
            </nav>
            <div class="myjobs-sort" role="group" aria-label="Sort jobs">
              <span class="field__label">Sort</span>
              <a class="btn btn--ghost btn--sm<?php echo $sort === 'newest' ? ' is-active' : ''; ?>" href="<?php echo hirepeak_url('employer/my-jobs.php' . ($statusFilter !== '' ? '?status=' . rawurlencode($statusFilter) : '')); ?>">Newest</a>
              <a class="btn btn--ghost btn--sm<?php echo $sort === 'oldest' ? ' is-active' : ''; ?>" href="<?php echo hirepeak_url('employer/my-jobs.php?sort=oldest' . ($statusFilter !== '' ? '&status=' . rawurlencode($statusFilter) : '')); ?>">Oldest</a>
            </div>
          </div>

          <?php if ($jobRows === []): ?>

            <section class="empty-state">
              <div class="empty-state__icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="9"/>
                  <path d="m15 9-6 6"/>
                  <path d="m9 9 6 6"/>
                </svg>
              </div>
              <h2 class="empty-state__title">No <?php echo hirepeak_esc(strtolower($statusLabels[$statusFilter] ?? 'matching')); ?> jobs.</h2>
              <p class="empty-state__text">Try another filter to see more of your postings.</p>
              <div class="empty-state__actions">
                <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">View all jobs</a>
                <a class="btn btn--outline" href="<?php echo hirepeak_url('employer/post-job.php'); ?>">Post a Job</a>
              </div>
            </section>

          <?php else: ?>

            <ul class="app-list jobs-list--manage">
              <?php foreach ($jobRows as $job): ?>
                <li class="app-row">
                  <div class="app-row__main">
                    <h3 class="app-row__title">
                      <a href="<?php echo hirepeak_url('job-details.php?id=' . (int) $job['id']); ?>"><?php echo hirepeak_esc((string) $job['title']); ?></a>
                    </h3>
                    <p class="app-row__company">
                      <?php echo hirepeak_esc((string) $job['location']); ?>
                      <span class="job-card__dot" aria-hidden="true">&middot;</span>
                      <?php echo hirepeak_esc(hirepeak_employment_type_label((string) $job['employment_type'])); ?>
                      <span class="job-card__dot" aria-hidden="true">&middot;</span>
                      <?php echo hirepeak_esc(hirepeak_work_mode_label((string) $job['work_mode'])); ?>
                    </p>
                    <p class="app-row__meta">
                      <?php echo hirepeak_job_status_badge((string) $job['status']); ?>
                      <span class="app-row__date">Posted <?php echo hirepeak_esc(hirepeak_relative_time((string) $job['created_at'])); ?></span>
                      <span class="app-row__count"><?php echo (int) $job['applications'] === 1 ? '1 application' : (int) $job['applications'] . ' applications'; ?></span>
                      <?php if ($deadlineLabel((string) ($job['application_deadline'] ?? '')) !== ''): ?>
                        <span class="app-row__date">Deadline <?php echo hirepeak_esc($deadlineLabel((string) $job['application_deadline'])); ?></span>
                      <?php endif; ?>
                    </p>
                  </div>
                  <div class="app-row__actions">
                    <a class="btn btn--outline btn--sm app-row__action app-row__action--primary" href="<?php echo hirepeak_url('employer/applicants.php?job_id=' . (int) $job['id']); ?>">View Applicants</a>
                    <?php if (($job['status'] ?? '') !== 'draft'): ?>
                      <a class="btn btn--outline btn--sm app-row__action" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $job['id']); ?>">View</a>
                    <?php endif; ?>
                    <a class="btn btn--outline btn--sm app-row__action" href="<?php echo hirepeak_url('employer/edit-job.php?id=' . (int) $job['id']); ?>"><?php echo ($job['status'] ?? '') === 'draft' ? 'Edit / Publish' : 'Edit'; ?></a>
                    <?php if (($job['status'] ?? '') === 'active'): ?>
                      <a class="btn btn--outline btn--sm app-row__action app-row__action--danger" href="<?php echo hirepeak_url('employer/close-job.php?id=' . (int) $job['id']); ?>">Close</a>
                    <?php else: ?>
                      <a class="btn btn--outline btn--sm app-row__action app-row__action--danger" href="<?php echo hirepeak_url('employer/delete-job.php?id=' . (int) $job['id']); ?>">Delete</a>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>

          <?php endif; ?>

        <?php endif; ?>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>