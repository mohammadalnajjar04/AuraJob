<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';

$user = hirepeak_require_role('applicant');
$uid = (int) $user['id'];

$flash = '';
if (isset($_GET['notice'], $_SESSION['application_flash']) && $_SESSION['application_flash'] !== '') {
    $flash = (string) $_SESSION['application_flash'];
    unset($_SESSION['application_flash']);
}

/*
 * My Applications — real joined data from applications + jobs + companies,
 * always scoped to the authenticated session user id. Filtering and sorting
 * are server-side with strict whitelists; no raw query values reach SQL.
 */

$allowedStatuses = hirepeak_application_statuses(); // status => label
$statusFilter = (string) ($_GET['status'] ?? '');
if ($statusFilter !== '' && !array_key_exists($statusFilter, $allowedStatuses)) {
    $statusFilter = '';
}
$sort = (string) ($_GET['sort'] ?? '') === 'oldest' ? 'oldest' : 'newest';

$applications = [];
$loadError = false;

try {
    $db = hirepeak_db();

    $sql = 'SELECT a.id AS application_id, a.status, a.applied_at, a.updated_at,
                   j.id AS job_id, j.title AS job_title, j.location AS job_location,
                   j.salary_min, j.salary_max, j.employment_type, j.work_mode,
                   j.experience_level, j.status AS job_status,
                   c.name AS company_name
            FROM applications a
            INNER JOIN jobs j ON j.id = a.job_id
            INNER JOIN companies c ON c.id = j.company_id
            WHERE a.applicant_id = ?
            ORDER BY a.applied_at DESC, a.id DESC';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($statusFilter !== '') {
        $applications = array_values(array_filter(
            $applications,
            static fn (array $app): bool => ($app['status'] ?? '') === $statusFilter
        ));
    }

    if ($sort === 'oldest') {
        usort($applications, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['applied_at'] ?? ''), (string) ($b['applied_at'] ?? ''));
            if ($cmp === 0) {
                return (int) $a['application_id'] <=> (int) $b['application_id'];
            }
            return $cmp;
        });
    }
} catch (\Throwable $e) {
    error_log('[hirepeak] my applications load failed: ' . $e->getMessage());
    $loadError = true;
    $applications = [];
}

// Counts reflect ALL of the applicant's applications (before filtering).
$counts = [];
$counts['all'] = 0;
foreach ($allowedStatuses as $status => $label) {
    $counts[$status] = 0;
}

if (!$loadError) {
    try {
        $db = hirepeak_db();
        $stmt = $db->prepare(
            'SELECT status, COUNT(*) AS n
             FROM applications
             WHERE applicant_id = ?
             GROUP BY status'
        );
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $counts['all'] += (int) $row['n'];
            $counts[$row['status']] = (int) $row['n'];
        }
        $stmt->close();
    } catch (\Throwable $e) {
        error_log('[hirepeak] my applications counts failed: ' . $e->getMessage());
    }
}

function hirepeak_formatted_app_date(?string $timestamp): string
{
    $time = $timestamp !== null ? strtotime($timestamp) : false;
    return $time ? date('M j, Y', $time) : '';
}

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Applications — AuraJob</title>
  <meta name="description" content="Track every opportunity you've applied for with AuraJob.">
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

  <main id="main" class="dash-main applications-page">
    <div class="container">

      <header class="dash-head">
        <div>
          <p class="dash-eyebrow">Applicant</p>
          <h1 class="dash-title">My Applications<?php echo $counts['all'] > 0 ? ' (' . (int) $counts['all'] . ')' : ''; ?></h1>
          <p class="dash-text">Track every opportunity you've applied for in one place.</p>
        </div>
        <a class="btn btn--primary dash-head__cta" href="<?php echo hirepeak_url('jobs.php'); ?>">
          Find Jobs
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14"/>
            <path d="m13 6 6 6-6 6"/>
          </svg>
        </a>
      </header>

      <?php if ($flash !== ''): ?>
        <p class="dash-flash" role="status"><?php echo hirepeak_esc($flash); ?></p>
      <?php endif; ?>

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="apps-error-title">
          <h2 id="apps-error-title">Something went wrong on our side.</h2>
          <p>We couldn't load your applications right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">Try again</a>
        </section>
      <?php else: ?>

        <?php if ($counts['all'] > 0): ?>
          <nav class="apps-toolbar" aria-label="Application status filter">
            <?php
            $statusItems = [['key' => '', 'label' => 'All']];
            foreach ($allowedStatuses as $key => $label) {
                $statusItems[] = ['key' => $key, 'label' => $label];
            }
            ?>
            <div class="status-pills" role="list">
              <?php foreach ($statusItems as $item): ?>
                <?php
                $count = $item['key'] === '' ? $counts['all'] : $counts[$item['key']];
                $active = $statusFilter === $item['key'];
                $href = hirepeak_url(
                    $item['key'] === ''
                        ? 'applicant/applications.php' . ($sort === 'oldest' ? '?sort=oldest' : '')
                        : 'applicant/applications.php?status=' . rawurlencode($item['key']) . ($sort === 'oldest' ? '&sort=oldest' : '')
                );
                ?>
                <a class="status-pill<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo hirepeak_esc($href); ?>" role="listitem"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                  <?php echo hirepeak_esc($item['label']); ?>
                  <span class="status-pill__count"><?php echo (int) $count; ?></span>
                </a>
              <?php endforeach; ?>
            </div>

            <div class="apps-sort" role="group" aria-label="Sort applications">
              <a class="apps-sort__link<?php echo $sort === 'newest' ? ' is-active' : ''; ?>"
                 href="<?php echo hirepeak_url('applicant/applications.php' . ($statusFilter !== '' ? '?status=' . rawurlencode($statusFilter) : '')); ?>"<?php echo $sort === 'newest' ? ' aria-current="true"' : ''; ?>>Newest</a>
              <span class="apps-sort__divider" aria-hidden="true"></span>
              <a class="apps-sort__link<?php echo $sort === 'oldest' ? ' is-active' : ''; ?>"
                 href="<?php echo hirepeak_url('applicant/applications.php?sort=oldest' . ($statusFilter !== '' ? '&status=' . rawurlencode($statusFilter) : '')); ?>"<?php echo $sort === 'oldest' ? ' aria-current="true"' : ''; ?>>Oldest</a>
            </div>
          </nav>
        <?php endif; ?>

        <?php if ($counts['all'] === 0): ?>
          <section class="empty-state">
            <div class="empty-state__icon" aria-hidden="true">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="16" rx="2"/>
                <path d="M3 9h18"/>
                <path d="m9 17 2 2 4-4"/>
              </svg>
            </div>
            <h2 class="empty-state__title">No applications yet.</h2>
            <p class="empty-state__text">When you apply for a role, you'll be able to track its progress here.</p>
            <a class="btn btn--primary" href="<?php echo hirepeak_url('jobs.php'); ?>">Browse Jobs</a>
          </section>
        <?php elseif ($applications === []): ?>
          <section class="empty-state">
            <div class="empty-state__icon" aria-hidden="true">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 6.5 12 13 2 6.5"/>
                <path d="M22 6.5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2Z"/>
              </svg>
            </div>
            <h2 class="empty-state__title">No applications match that status.</h2>
            <p class="empty-state__text">Try another status, or view all of your applications.</p>
            <a class="btn btn--primary" href="<?php echo hirepeak_url('applicant/applications.php' . ($sort === 'oldest' ? '?sort=oldest' : '')); ?>">View all applications</a>
          </section>
        <?php else: ?>
          <ul class="app-list app-list--applications">
            <?php foreach ($applications as $app): ?>
              <?php
              $jobId = (int) $app['job_id'];
              $appId = (int) $app['application_id'];
              $jobClosed = ($app['job_status'] ?? '') !== 'active';
              $appliedLabel = hirepeak_formatted_app_date((string) $app['applied_at']);
              $updatedTs = (string) ($app['updated_at'] ?? '');
              $appliedTs = (string) ($app['applied_at'] ?? '');
              $showUpdated = $updatedTs !== '' && $appliedTs !== '' && $updatedTs !== $appliedTs;
              $salary = hirepeak_salary_range(
                  isset($app['salary_min']) ? (string) $app['salary_min'] : '',
                  isset($app['salary_max']) ? (string) $app['salary_max'] : ''
              );
              ?>
              <li class="app-row app-row--page">
                <div class="app-row__main">
                  <h2 class="app-row__title">
                    <a href="<?php echo hirepeak_url('job-details.php?id=' . (int) $jobId); ?>"><?php echo hirepeak_esc((string) $app['job_title']); ?></a>
                  </h2>
                  <p class="app-row__company">
                    <?php echo hirepeak_esc((string) $app['company_name']); ?>
                    <?php if (trim((string) ($app['job_location'] ?? '')) !== ''): ?>
                      <span class="job-card__dot" aria-hidden="true">&middot;</span> <?php echo hirepeak_esc((string) $app['job_location']); ?>
                    <?php endif; ?>
                  </p>

                  <ul class="app-row__chips">
                    <?php if ($salary !== 'Salary not disclosed'): ?>
                      <li class="chip"><?php echo hirepeak_esc($salary); ?></li>
                    <?php endif; ?>
                    <li class="chip"><?php echo hirepeak_esc(hirepeak_employment_type_label((string) $app['employment_type'])); ?></li>
                    <li class="chip"><?php echo hirepeak_esc(hirepeak_work_mode_label((string) $app['work_mode'])); ?></li>
                    <li class="chip"><?php echo hirepeak_esc(hirepeak_experience_level_label((string) $app['experience_level'])); ?></li>
                    <?php if ($jobClosed): ?>
                      <li class="chip chip--closed">Closed</li>
                    <?php endif; ?>
                  </ul>

                  <div class="app-row__meta">
                    <?php echo hirepeak_application_status_badge((string) $app['status']); ?>
                    <span class="app-row__date">
                      Applied <?php echo hirepeak_esc($appliedLabel); ?>
                      <?php if ($showUpdated): ?>
                        &middot; Status updated <?php echo hirepeak_esc(hirepeak_relative_time($updatedTs)); ?>
                      <?php endif; ?>
                    </span>
                  </div>
                </div>

                <div class="app-row__actions">
                  <a class="btn btn--outline btn--sm" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $jobId); ?>">View Job</a>
                  <a class="btn btn--primary btn--sm" href="<?php echo hirepeak_url('applicant/tracking.php?id=' . (int) $appId); ?>">Track Application</a>
                  <?php if (in_array((string) ($app['status'] ?? ''), ['applied', 'under_review', 'interview'], true)): ?>
                    <a class="btn btn--outline btn--sm app-row__action--danger" href="<?php echo hirepeak_url('applicant/cancel-application.php?id=' . (int) $appId); ?>">Cancel Application</a>
                  <?php endif; ?>
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