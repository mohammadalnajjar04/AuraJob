<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/html.php';

$user = hirepeak_require_role('applicant');
$uid = (int) $user['id'];

$pageActive = '';
$pageScripts = ['src/js/main.js'];

$firstName = trim((string) ($user['name'] ?? ''));
$firstName = $firstName !== '' ? explode(' ', $firstName)[0] : '';

/*
 * All dashboard data is scoped to the authenticated session user id. Queries
 * use prepared statements and never echo database errors.
 */

$kpis = null;
$profile = null;
$profilePercent = 0;
$profileMissing = [];
$recentApplications = [];
$savedJobs = [];
$recommended = [];
$recNote = '';
$loadError = null;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT COUNT(*) AS total,
                COALESCE(SUM(status = \'applied\'), 0) AS applied,
                COALESCE(SUM(status = \'under_review\'), 0) AS under_review,
                COALESCE(SUM(status = \'interview\'), 0) AS interview,
                COALESCE(SUM(status = \'accepted\'), 0) AS accepted
         FROM applications
         WHERE applicant_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $kpiRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $kpis = [
        'total'       => (int) ($kpiRow['total'] ?? 0),
        'underReview' => (int) ($kpiRow['under_review'] ?? 0),
        'interview'   => (int) ($kpiRow['interview'] ?? 0),
        'offers'      => (int) ($kpiRow['accepted'] ?? 0),
    ];

    $stmt = $db->prepare(
        'SELECT id, professional_title, bio, phone, location, experience_years, skills
         FROM applicant_profiles
         WHERE user_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $completeness = hirepeak_profile_completeness($profile);
    $profilePercent = $completeness['percent'];
    $profileMissing = $completeness['missing'];

    $stmt = $db->prepare(
        'SELECT a.id AS application_id, a.status, a.applied_at,
                j.id AS job_id, j.title AS job_title, j.location AS job_location,
                c.name AS company_name
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN companies c ON c.id = j.company_id
         WHERE a.applicant_id = ?
         ORDER BY a.applied_at DESC
         LIMIT 5'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $recentApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $db->prepare(
        'SELECT s.saved_at,
                j.id AS job_id, j.title, j.location, j.salary_min, j.salary_max,
                j.employment_type, j.work_mode, j.experience_level, j.status, j.created_at,
                c.name AS company_name
         FROM saved_jobs s
         INNER JOIN jobs j ON j.id = s.job_id
         INNER JOIN companies c ON c.id = j.company_id
         WHERE s.applicant_id = ?
         ORDER BY s.saved_at DESC
         LIMIT 3'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $savedJobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    /*
     * Recommended for you — normal product logic, not AI:
     * 1. Any categories the applicant has already applied to or saved signal
     *    interest; jobs in those categories rank first.
     * 2. A job matching the applicant's profile location ranks above equal
     *    recency ties.
     * 3. Everything already applied to is excluded.
     * 4. Without any profile/history, fall back to the newest active jobs.
     */

    $interestCategoryIds = [];

    $stmt = $db->prepare(
        'SELECT DISTINCT j.category_id
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE a.applicant_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $interestCategoryIds[(int) $row['category_id']] = true;
    }
    $stmt->close();

    $stmt = $db->prepare(
        'SELECT DISTINCT j.category_id
         FROM saved_jobs s
         INNER JOIN jobs j ON j.id = s.job_id
         WHERE s.applicant_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $interestCategoryIds[(int) $row['category_id']] = true;
    }
    $stmt->close();

    $interests = array_keys($interestCategoryIds);
    $profileLocation = $profile !== null ? trim((string) ($profile['location'] ?? '')) : '';

    $orderTerms = [];
    $bindTypes = 'i'; // uid for the NOT IN subquery
    $bindValues = [$uid];

    if ($interests !== []) {
        $orderTerms[] = '(j.category_id IN (' . implode(',', array_fill(0, count($interests), '?')) . ')) DESC';
        foreach ($interests as $catId) {
            $bindTypes .= 'i';
            $bindValues[] = (int) $catId;
        }
    }

    if ($profileLocation !== '') {
        $orderTerms[] = '(LOWER(j.location) = LOWER(?)) DESC';
        $bindTypes .= 's';
        $bindValues[] = $profileLocation;
    }

    $orderSql = $orderTerms === [] ? '' : implode(', ', $orderTerms) . ', ';

    $sql = 'SELECT j.id, j.title, j.location, j.salary_min, j.salary_max,
                   j.employment_type, j.work_mode, j.experience_level, j.status, j.created_at,
                   c.name AS company_name, ct.name AS category_name
            FROM jobs j
            INNER JOIN companies c ON c.id = j.company_id
            INNER JOIN categories ct ON ct.id = j.category_id
            WHERE j.status = \'active\'
              AND j.id NOT IN (SELECT a.job_id FROM applications a WHERE a.applicant_id = ?)
            ORDER BY ' . $orderSql . 'j.created_at DESC
            LIMIT 3';

    $stmt = $db->prepare($sql);
    array_unshift($bindValues, $bindTypes);
    $stmt->bind_param(...$bindValues);
    $stmt->execute();
    $recommended = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $recNote = $interests !== [] || $profileLocation !== ''
        ? 'Ranked by jobs like the ones you\'ve applied to or saved.'
        : 'The newest active jobs on HirePeak.';
} catch (Throwable $e) {
    error_log('[hirepeak] applicant dashboard load failed: ' . $e->getMessage());
    $loadError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applicant Dashboard — HirePeak</title>
  <meta name="description" content="Track your applications, saved jobs, and profile progress on HirePeak.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='1' y='1' width='22' height='22' rx='7' fill='%234F46E5'/%3E%3Cpath d='M12 4.8 18 16H6Z' fill='%23FFFFFF' stroke='%23FFFFFF' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M12 6.6 8.8 16H5.9Z' fill='%23EEF2FF' stroke='%23EEF2FF' stroke-width='3' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="src/css/style.css">
</head>
<body>
  <?php require __DIR__ . '/includes/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="dash-main">
    <div class="container">

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="dash-error-title">
          <h1 id="dash-error-title">Something went wrong on our side.</h1>
          <p>We couldn't load your dashboard right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="applicant-dashboard.php">Try again</a>
        </section>
      <?php else: ?>

        <header class="dash-head">
          <div>
            <p class="dash-eyebrow">Applicant dashboard</p>
            <h1 class="dash-title">Welcome back, <?php echo hirepeak_esc($firstName); ?>.</h1>
            <p class="dash-text">Here's what's happening with your job search.</p>
          </div>
          <a class="btn btn--primary dash-head__cta" href="jobs.php">
            Find Jobs
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </a>
        </header>

        <section class="kpi-grid" aria-label="Your application summary">
          <article class="kpi-card">
            <p class="kpi-card__label">Total Applications</p>
            <p class="kpi-card__value"><?php echo (int) $kpis['total']; ?></p>
          </article>
          <article class="kpi-card">
            <p class="kpi-card__label">Under Review</p>
            <p class="kpi-card__value"><?php echo (int) $kpis['underReview']; ?></p>
          </article>
          <article class="kpi-card">
            <p class="kpi-card__label">Interviews</p>
            <p class="kpi-card__value"><?php echo (int) $kpis['interview']; ?></p>
          </article>
          <article class="kpi-card">
            <p class="kpi-card__label">Offers</p>
            <p class="kpi-card__value"><?php echo (int) $kpis['offers']; ?></p>
            <p class="kpi-card__note">Accepted applications</p>
          </article>
        </section>

        <div class="dash-grid">
          <section class="dash-panel" aria-labelledby="recent-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="recent-title">Recent Applications</h2>
              <?php if ($recentApplications !== []): ?>
                <span class="panel-head__count"><?php echo (int) count($recentApplications); ?></span>
                <a class="panel-head__link" href="my-applications.php">View all</a>
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
                <p class="empty-state__text">When you apply for jobs, you'll track their progress here.</p>
                <a class="btn btn--primary" href="jobs.php">Browse Jobs</a>
              </div>
            <?php else: ?>
              <ul class="app-list">
                <?php foreach ($recentApplications as $app): ?>
                  <li class="app-row">
                    <div class="app-row__main">
                      <h3 class="app-row__title">
                        <a href="job-details.php?id=<?php echo (int) $app['job_id']; ?>"><?php echo hirepeak_esc((string) $app['job_title']); ?></a>
                      </h3>
                      <p class="app-row__company">
                        <?php echo hirepeak_esc((string) $app['company_name']); ?>
                        <?php if (trim((string) ($app['job_location'] ?? '')) !== ''): ?>
                          <span class="job-card__dot" aria-hidden="true">&middot;</span> <?php echo hirepeak_esc((string) $app['job_location']); ?>
                        <?php endif; ?>
                      </p>
                      <p class="app-row__meta">
                        <?php echo hirepeak_application_status_badge((string) $app['status']); ?>
                        <span class="app-row__date">Applied <?php echo hirepeak_esc(hirepeak_relative_time((string) $app['applied_at'])); ?></span>
                      </p>
                    </div>
                    <a class="btn btn--outline btn--sm app-row__action" href="job-details.php?id=<?php echo (int) $app['job_id']; ?>">View Job</a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </section>

          <aside class="dash-panel" aria-labelledby="profile-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="profile-title">Complete your profile</h2>
              <a class="panel-head__link" href="applicant-profile.php">Edit profile</a>
            </div>

            <div class="completeness">
              <div class="completeness__top">
                <p class="completeness__figure"><?php echo (int) $profilePercent; ?>%</p>
                <p class="completeness__status">
                  <?php echo $profilePercent === 100 ? 'Profile complete.' : 'complete'; ?>
                </p>
              </div>

              <?php $progressId = 'profile-progress'; ?>
              <div
                class="completeness__bar"
                role="progressbar"
                aria-labelledby="profile-title"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="<?php echo (int) $profilePercent; ?>"
              >
                <span class="completeness__bar-fill" style="width: <?php echo (int) $profilePercent; ?>%"></span>
              </div>
              <p class="sr-only" id="<?php echo $progressId; ?>"><?php echo (int) $profilePercent; ?> percent complete</p>

              <?php if ($profileMissing !== []): ?>
                <p class="completeness__hint">A complete profile helps employers find you.</p>
                <ul class="completeness__todo">
                  <?php foreach ($profileMissing as $label): ?>
                    <li><?php echo hirepeak_esc($label); ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="completeness__hint">Your profile is fully filled in. Nice work.</p>
              <?php endif; ?>
            </div>
          </aside>
        </div>

        <section class="dash-panel dash-panel--full" aria-labelledby="saved-title">
          <div class="panel-head">
            <h2 class="panel-head__title" id="saved-title">Saved Jobs</h2>
            <span class="panel-head__count"><?php echo (int) count($savedJobs); ?></span>
          </div>

          <?php if ($savedJobs === []): ?>
            <div class="empty-state">
              <div class="empty-state__icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                </svg>
              </div>
              <h3 class="empty-state__title">No saved jobs yet.</h3>
              <p class="empty-state__text">Save roles you're interested in and come back to them anytime.</p>
              <a class="btn btn--primary" href="jobs.php">Explore Jobs</a>
            </div>
          <?php else: ?>
            <div class="mini-grid">
              <?php foreach ($savedJobs as $job): ?>
                <?php echo hirepeak_dashboard_job_card($job); ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="dash-panel dash-panel--full" aria-labelledby="recommended-title">
          <div class="panel-head">
            <h2 class="panel-head__title" id="recommended-title">Recommended for you</h2>
            <p class="panel-head__note"><?php echo hirepeak_esc($recNote); ?></p>
          </div>

          <?php if ($recommended === []): ?>
            <div class="empty-state">
              <div class="empty-state__icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2 15 8.5 22 9.3 17 14.2 18.2 21 12 17.8 5.8 21 7 14.2 2 9.3 9 8.5Z"/>
                </svg>
              </div>
              <h3 class="empty-state__title">No recommendations yet.</h3>
              <p class="empty-state__text">Explore active jobs and save ones you like — we'll refresh suggestions from there.</p>
              <a class="btn btn--primary" href="jobs.php">Explore Jobs</a>
            </div>
          <?php else: ?>
            <div class="mini-grid">
              <?php foreach ($recommended as $job): ?>
                <?php echo hirepeak_dashboard_job_card($job); ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

      <?php endif; ?>
    </div>
  </main>

  <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>