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

$firstName = trim((string) ($user['name'] ?? ''));
$firstName = $firstName !== '' ? explode(' ', $firstName)[0] : '';

/*
 * All dashboard data is scoped to the authenticated session employer id.
 * Queries use prepared statements and never echo database errors.
 */

$kpis = null;
$company = null;
$companyPercent = 0;
$companyMissing = [];
$recentJobs = [];
$recentApplications = [];
$loadError = null;

$hasJobs = false;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT COUNT(*) AS total,
                COALESCE(SUM(status = \'active\'), 0) AS active
         FROM jobs
         WHERE employer_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $jobRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $db->prepare(
        'SELECT COUNT(*) AS total
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE j.employer_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $appRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $db->prepare(
        'SELECT COUNT(*) AS total
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE j.employer_id = ? AND a.status = \'applied\''
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $attentionRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $kpis = [
        'totalJobs'      => (int) ($jobRow['total'] ?? 0),
        'activeJobs'     => (int) ($jobRow['active'] ?? 0),
        'totalApps'      => (int) ($appRow['total'] ?? 0),
        'needsAttention' => (int) ($attentionRow['total'] ?? 0),
    ];

    $stmt = $db->prepare(
        'SELECT id, name, description, industry, location, website,
                contact_email, contact_phone
         FROM companies
         WHERE user_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    $completeness = hirepeak_company_completeness($company);
    $companyPercent = $completeness['percent'];
    $companyMissing = $completeness['missing'];

    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.status, j.location, j.employment_type,
                j.work_mode, j.created_at,
                COUNT(a.id) AS applications
         FROM jobs j
         LEFT JOIN applications a ON a.job_id = j.id
         WHERE j.employer_id = ?
         GROUP BY j.id, j.title, j.status, j.location, j.employment_type,
                  j.work_mode, j.created_at
         ORDER BY j.created_at DESC
         LIMIT 5'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $recentJobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $hasJobs = $recentJobs !== [];

    $stmt = $db->prepare(
        'SELECT a.id AS application_id, a.status, a.applied_at,
                u.name AS applicant_name,
                j.id AS job_id, j.title AS job_title
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN users u ON u.id = a.applicant_id
         WHERE j.employer_id = ?
         ORDER BY a.applied_at DESC
         LIMIT 5'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $recentApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Throwable $e) {
    error_log('[hirepeak] employer dashboard load failed: ' . $e->getMessage());
    $loadError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employer Dashboard — HirePeak</title>
  <meta name="description" content="Track your job postings and incoming applications on HirePeak.">
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

  <main id="main" class="dash-main employer-dashboard">
    <div class="container">

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="dash-error-title">
          <h1 id="dash-error-title">Something went wrong on our side.</h1>
          <p>We couldn't load your dashboard right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/dashboard.php'); ?>">Try again</a>
        </section>
      <?php else: ?>

        <header class="dash-head">
          <div>
            <p class="dash-eyebrow">Employer dashboard</p>
            <h1 class="dash-title">Welcome back, <?php echo hirepeak_esc($firstName); ?>.</h1>
            <p class="dash-text">Here's what's happening with your hiring activity.</p>
          </div>
          <div class="dash-head__cta-wrap">
            <a class="btn btn--primary dash-head__cta" href="<?php echo hirepeak_url('employer/post-job.php'); ?>">Post a Job</a>
          </div>
        </header>

        <section class="kpi-grid" aria-label="Your hiring summary">
          <article class="kpi-card">
            <p class="kpi-card__label">Total Jobs</p>
            <p class="kpi-card__value"><?php echo (int) $kpis['totalJobs']; ?></p>
          </article>
          <article class="kpi-card">
            <p class="kpi-card__label">Active Jobs</p>
            <p class="kpi-card__value"><?php echo (int) $kpis['activeJobs']; ?></p>
          </article>
          <article class="kpi-card">
            <p class="kpi-card__label">Total Applications</p>
            <p class="kpi-card__value"><?php echo (int) $kpis['totalApps']; ?></p>
          </article>
          <article class="kpi-card">
            <p class="kpi-card__label">Needs Attention</p>
            <p class="kpi-card__value"><?php echo (int) $kpis['needsAttention']; ?></p>
            <p class="kpi-card__note">Applications awaiting review</p>
          </article>
        </section>

        <div class="dash-grid">
          <section class="dash-panel" aria-labelledby="jobs-title">
            <div class="panel-head">
              <div class="panel-head__title-group">
                <h2 class="panel-head__title" id="jobs-title">Recent Jobs</h2>
                <?php if ($hasJobs): ?>
                  <span class="panel-head__count"><?php echo (int) count($recentJobs); ?></span>
                <?php endif; ?>
              </div>
              <?php if ($hasJobs): ?>
                <a class="panel-head__link" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">View all jobs</a>
              <?php endif; ?>
            </div>

            <?php if (!$hasJobs): ?>
              <div class="empty-state">
                <div class="empty-state__icon" aria-hidden="true">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="7" width="18" height="14" rx="2"/>
                    <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <path d="M3 12h18"/>
                  </svg>
                </div>
                <h3 class="empty-state__title">No jobs posted yet.</h3>
                <p class="empty-state__text">Here's how to start hiring on HirePeak:</p>
                <ol class="empty-steps">
                  <li>
                    <?php if ($company === null): ?>
                      <a href="<?php echo hirepeak_url('employer/company-profile.php'); ?>">Complete your company profile</a> to get started.
                    <?php else: ?>
                      Your company profile is set up.
                    <?php endif; ?>
                  </li>
                  <li>Post your first job and put it in front of candidates.</li>
                  <li>Review applicants — they'll appear here once your jobs go live.</li>
                </ol>
                <?php if ($company === null): ?>
                  <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/company-profile.php'); ?>">Complete Company Profile</a>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <ul class="app-list">
                <?php foreach ($recentJobs as $job): ?>
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
                      </p>
                    </div>
                    <div class="app-row__actions">
                      <a class="btn btn--outline btn--sm app-row__action" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $job['id']); ?>">View Job</a>
                      <a class="btn btn--outline btn--sm app-row__action" href="<?php echo hirepeak_url('employer/edit-job.php?id=' . (int) $job['id']); ?>">Manage</a>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </section>

          <aside class="dash-panel" aria-labelledby="company-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="company-title">Company profile</h2>
              <?php if ($company !== null): ?>
                <a class="panel-head__link" href="<?php echo hirepeak_url('employer/company-profile.php'); ?>">Edit</a>
              <?php endif; ?>
            </div>

            <?php if ($company === null): ?>
              <div class="empty-state">
                <div class="empty-state__icon" aria-hidden="true">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21h18"/>
                    <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                    <path d="M9 7h2"/>
                    <path d="M13 7h2"/>
                    <path d="M9 11h2"/>
                    <path d="M13 11h2"/>
                    <path d="M9 15h2"/>
                    <path d="M13 15h2"/>
                  </svg>
                </div>
                <h3 class="empty-state__title">You haven't set up your company yet.</h3>
                <p class="empty-state__text">Add your company name and details so candidates know who they're applying to.</p>
                <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/company-profile.php'); ?>">Complete your company profile</a>
              </div>
            <?php else: ?>
              <div class="completeness">
                <div class="completeness__top">
                  <p class="completeness__figure"><?php echo (int) $companyPercent; ?>%</p>
                  <p class="completeness__status">
                    <?php echo $companyPercent === 100 ? 'Profile complete.' : 'complete'; ?>
                  </p>
                </div>

                <div
                  class="completeness__bar"
                  role="progressbar"
                  aria-labelledby="company-title"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  aria-valuenow="<?php echo (int) $companyPercent; ?>"
                >
                  <span class="completeness__bar-fill" style="width: <?php echo (int) $companyPercent; ?>%"></span>
                </div>
                <p class="sr-only"><?php echo (int) $companyPercent; ?> percent complete</p>

                <?php if ($companyMissing !== []): ?>
                  <p class="completeness__hint">A complete company profile helps candidates trust your listings.</p>
                  <ul class="completeness__todo">
                    <?php foreach ($companyMissing as $label): ?>
                      <li><?php echo hirepeak_esc($label); ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p class="completeness__hint">Your company profile is fully filled in. Nice work.</p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </aside>
        </div>

        <section class="dash-panel dash-panel--full" aria-labelledby="applications-title">
          <div class="panel-head">
            <h2 class="panel-head__title" id="applications-title">Recent Applications</h2>
            <?php if ($recentApplications !== []): ?>
              <span class="panel-head__count"><?php echo (int) count($recentApplications); ?></span>
            <?php endif; ?>
          </div>

          <?php if ($recentApplications === []): ?>
            <div class="empty-state">
              <div class="empty-state__icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="16" rx="2"/>
                  <path d="M3 9h18"/>
                  <path d="m9 17 2 2 4-4"/>
                </svg>
              </div>
              <h3 class="empty-state__title">No applications yet.</h3>
              <p class="empty-state__text">When candidates apply to your jobs, they'll show up here for review.</p>
            </div>
          <?php else: ?>
            <ul class="app-list">
              <?php foreach ($recentApplications as $app): ?>
                <li class="app-row">
                  <div class="app-row__main">
                    <h3 class="app-row__title"><?php echo hirepeak_esc((string) $app['applicant_name']); ?></h3>
                    <p class="app-row__company">
                      Applied to <a class="job-inline-link" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $app['job_id']); ?>"><?php echo hirepeak_esc((string) $app['job_title']); ?></a>
                    </p>
                    <p class="app-row__meta">
                      <?php echo hirepeak_application_status_badge((string) $app['status']); ?>
                      <span class="app-row__date">Applied <?php echo hirepeak_esc(hirepeak_relative_time((string) $app['applied_at'])); ?></span>
                    </p>
                  </div>
                  <div class="app-row__actions">
                    <a class="btn btn--outline btn--sm app-row__action" href="<?php echo hirepeak_url('employer/application.php?id=' . (int) $app['application_id']); ?>">Review</a>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>