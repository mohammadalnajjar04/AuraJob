<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/html.php';

hirepeak_start_session();

$pageActive = 'jobs';
$pageScripts = ['src/js/main.js', 'src/js/jobs.js'];

$jobs = [];
$dbError = false;

try {
    $db = hirepeak_db();
    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.location, j.salary_min, j.salary_max,
                j.employment_type, j.work_mode, j.experience_level,
                j.created_at, j.description, j.requirements,
                c.name AS company_name, ct.name AS category_name
         FROM jobs j
         INNER JOIN companies c ON c.id = j.company_id
         INNER JOIN categories ct ON ct.id = j.category_id
         WHERE j.status = "active"
           AND (j.application_deadline IS NULL
                OR j.application_deadline >= CURDATE())
         ORDER BY j.created_at DESC'
    );
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (\Throwable $e) {
    error_log('[hirepeak] jobs listing load failed: ' . $e->getMessage());
    $dbError = true;
}

$resultCount = count($jobs);

/* Saved-job state is rendered server-side from saved_jobs (never from JS), and
 * only ever considered for signed-in applicants. */
$viewerRole = (string) (hirepeak_user()['role'] ?? '');
$viewerId = (int) (hirepeak_user()['id'] ?? 0);
$savedSet = [];
$saveCsrf = '';

if ($viewerRole === 'applicant' && $viewerId > 0) {
    $saveCsrf = hirepeak_csrf_token();
    try {
        foreach (hirepeak_saved_job_ids($db, $viewerId) as $savedJobId) {
            $savedSet[$savedJobId] = true;
        }
    } catch (\Throwable $e) {
        error_log('[hirepeak] saved-jobs listing state failed: ' . $e->getMessage());
        $savedSet = [];
    }
}

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

$filterValues = [
    'category'   => [],
    'employment' => [],
    'mode'       => [],
    'experience' => [],
    'location'   => [],
];
foreach ($jobs as $job) {
    if ((string) $job['category_name'] !== '') {
        $filterValues['category'][] = (string) $job['category_name'];
    }
    $filterValues['employment'][] = hirepeak_employment_type_label((string) $job['employment_type']);
    $filterValues['mode'][] = hirepeak_work_mode_label((string) $job['work_mode']);
    $filterValues['experience'][] = hirepeak_experience_level_label((string) $job['experience_level']);
    if ((string) $job['location'] !== '') {
        $filterValues['location'][] = (string) $job['location'];
    }
}
foreach ($filterValues as $group => $values) {
    $filterValues[$group] = array_values(array_unique($values));
}

$filterGroups = [
    'category'   => 'Category',
    'employment' => 'Employment type',
    'mode'       => 'Work mode',
    'experience' => 'Experience level',
    'location'   => 'Location',
];

$totalPages = max(1, (int) ceil($resultCount / 5));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Find Jobs — HirePeak</title>
  <meta name="description" content="Search for jobs on HirePeak. Filter roles by category, work mode, employment type, experience level, and location.">
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

  <main id="main">
    <section class="jobs-hero">
      <div class="container jobs-hero__inner">
        <h1 class="jobs-hero__title">Find your next opportunity</h1>
        <p class="jobs-hero__sub">Explore roles that match your skills, goals, and preferred way of working.</p>

        <form class="hero-search jobs-search" role="search" method="get" action="jobs.php" data-jobs-search>
          <label class="sr-only" for="jobs-keyword">Job title, skill, or company</label>
          <div class="hero-search__field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="7"/>
              <path d="m20 20-3.5-3.5"/>
            </svg>
            <input id="jobs-keyword" name="keyword" type="search" placeholder="Job title, skill, or company" autocomplete="off">
          </div>

          <div class="hero-search__divider" aria-hidden="true"></div>

          <label class="sr-only" for="jobs-location">Location</label>
          <div class="hero-search__field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <input id="jobs-location" name="location" type="text" placeholder="City, or remote" autocomplete="off">
          </div>

          <button class="btn btn--primary hero-search__submit" type="submit">
            Search Jobs
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </button>
        </form>
      </div>
    </section>

    <section class="jobs" aria-labelledby="results-count">
      <div class="container jobs-layout">
        <aside class="jobs-sidebar" id="filter-panel" aria-label="Filters">
          <div class="jobs-sidebar__head">
            <h2 class="jobs-sidebar__title">Filters</h2>
            <button class="jobs-sidebar__clear" type="button">Clear all</button>
          </div>

          <form class="jobs-filters">
            <?php foreach ($filterGroups as $group => $title): ?>
              <?php if ($filterValues[$group] !== []): ?>
                <fieldset class="jobs-filter">
                  <legend class="jobs-filter__title"><?php echo hirepeak_esc($title); ?></legend>
                  <div class="jobs-filter__options">
                    <?php foreach ($filterValues[$group] as $value): ?>
                      <label class="jobs-check"><input type="checkbox" name="<?php echo hirepeak_esc($group); ?>" value="<?php echo hirepeak_esc($value); ?>"><span><?php echo hirepeak_esc($value); ?></span></label>
                    <?php endforeach; ?>
                  </div>
                </fieldset>
              <?php endif; ?>
            <?php endforeach; ?>
          </form>
        </aside>

        <div class="jobs-results">
          <div class="jobs-toolbar">
            <div class="jobs-toolbar__row">
              <h2 class="jobs-count" id="results-count" aria-live="polite"><?php echo (int) $resultCount; ?> opportunities</h2>
              <div class="jobs-toolbar__controls">
                <button class="btn btn--outline jobs-filter-trigger" type="button" aria-expanded="false" aria-controls="filter-drawer">
                  Filters <span class="jobs-filter-trigger__badge" hidden>0</span>
                </button>
                <label class="jobs-sort">
                  <span class="sr-only">Sort results</span>
                  <select>
                    <option value="newest">Newest</option>
                    <option value="salary-desc">Salary: High to Low</option>
                    <option value="salary-asc">Salary: Low to High</option>
                  </select>
                </label>
              </div>
            </div>
          </div>

          <div class="active-filters" data-active-filters hidden>
            <ul aria-label="Active filters"></ul>
            <button class="active-filters__clear" type="button" data-clear-all>Clear all</button>
          </div>

          <?php if ($saveFlash !== ''): ?>
            <p class="dash-flash jobs__flash" role="status"><?php echo hirepeak_esc($saveFlash); ?></p>
          <?php endif; ?>
          <?php if ($saveFlashError !== ''): ?>
            <p class="dash-alert dash-alert--error jobs__flash" role="alert"><?php echo hirepeak_esc($saveFlashError); ?></p>
          <?php endif; ?>

          <?php if ($dbError): ?>
            <section class="dash-alert" role="alert" aria-labelledby="jobs-error-title">
              <h2 id="jobs-error-title">We couldn't load jobs right now.</h2>
              <p>Please try again in a moment.</p>
            </section>
          <?php else: ?>

            <div class="jobs-list">

              <?php foreach ($jobs as $job): ?>
                <?php
                $salaryText = hirepeak_salary_range(
                    isset($job['salary_min']) ? (string) $job['salary_min'] : '',
                    isset($job['salary_max']) ? (string) $job['salary_max'] : ''
                );
                $salaryLead = $salaryText;
                $salaryPeriod = '';
                if (str_ends_with($salaryText, '/ month')) {
                    $salaryLead = trim(substr($salaryText, 0, -8));
                    $salaryPeriod = '/ month';
                }
                $cardMeta = hirepeak_employment_type_label((string) $job['employment_type'])
                    . ' <span class="job-card__dot" aria-hidden="true">&middot;</span> '
                    . hirepeak_experience_level_label((string) $job['experience_level']);
                $companyLine = (string) $job['company_name']
                    . ' <span class="job-card__dot" aria-hidden="true">&middot;</span> '
                    . (string) $job['location']
                    . ' <span class="job-card__dot" aria-hidden="true">&middot;</span> '
                    . hirepeak_work_mode_label((string) $job['work_mode']);
                $posted = hirepeak_relative_time((string) $job['created_at']);
                ?>
                <article class="job-card job-card--row" data-keywords="<?php echo hirepeak_esc(hirepeak_job_keywords($job)); ?>" data-job-id="<?php echo (int) $job['id']; ?>">
                  <div class="job-card--row__main">
                    <span class="job-card__logo" aria-hidden="true"><?php echo hirepeak_esc(hirepeak_initials((string) $job['company_name'])); ?></span>
                    <div class="job-card--row__body">
                      <h3 class="job-card__title"><a href="job-details.php?id=<?php echo (int) $job['id']; ?>"><?php echo hirepeak_esc((string) $job['title']); ?></a></h3>
                      <p class="job-card--row__company"><?php echo $companyLine; ?></p>
                      <p class="job-card--row__meta"><?php echo $cardMeta; ?></p>
                      <p class="job-card--row__salary"><?php echo $salaryLead; ?><?php echo $salaryPeriod !== '' ? ' <span class="job-card--row__period">' . $salaryPeriod . '</span>' : ''; ?></p>
                      <p class="job-card--row__foot"><?php echo hirepeak_esc((string) $job['category_name']); ?> <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted <?php echo hirepeak_esc($posted); ?></p>
                    </div>
                  </div>
                  <div class="job-card--row__actions">
                    <?php if ($viewerRole === 'applicant'): ?>
                      <?php $saved = isset($savedSet[(int) $job['id']]); ?>
                      <?php $saveLabel = 'Save ' . (string) $job['title'] . ' at ' . (string) $job['company_name']; ?>
                      <?php $savedLabel = 'Remove ' . (string) $job['title'] . ' at ' . (string) $job['company_name'] . ' from saved jobs'; ?>
                      <form class="save-form" method="post" action="save-job.php" data-save-job data-job-id="<?php echo (int) $job['id']; ?>" data-save-label="<?php echo hirepeak_esc($saveLabel); ?>" data-saved-label="<?php echo hirepeak_esc($savedLabel); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc($saveCsrf); ?>">
                        <input type="hidden" name="job_id" value="<?php echo (int) $job['id']; ?>">
                        <input type="hidden" name="action" value="<?php echo $saved ? 'unsave' : 'save'; ?>">
                        <button class="icon-button job-card__save" type="submit" data-bookmark aria-pressed="<?php echo $saved ? 'true' : 'false'; ?>" aria-label="<?php echo hirepeak_esc($saved ? $savedLabel : $saveLabel); ?>">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                          </svg>
                        </button>
                      </form>
                    <?php elseif ($viewerRole === ''): ?>
                      <a class="icon-button job-card__save" href="login.php" aria-label="Sign in to save <?php echo hirepeak_esc((string) $job['title']); ?> at <?php echo hirepeak_esc((string) $job['company_name']); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                        </svg>
                      </a>
                    <?php endif; ?>
                    <a class="btn btn--outline btn--sm" href="job-details.php?id=<?php echo (int) $job['id']; ?>">View Job</a>
                  </div>
                </article>
              <?php endforeach; ?>

            </div>

            <div class="jobs-empty" hidden>
              <div class="jobs-empty__icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="7"/>
                  <path d="m21 21-4.35-4.35"/>
                  <path d="M8.5 8.5l5 5"/>
                  <path d="M13.5 8.5l-5 5"/>
                </svg>
              </div>
              <h3 class="jobs-empty__title">No jobs match those filters.</h3>
              <p class="jobs-empty__text">Try adjusting your search or clearing some filters.</p>
              <button class="btn btn--primary" type="button">Clear Filters</button>
            </div>

            <nav class="pagination" aria-label="Results pagination">
              <p class="pagination__meta">Showing 1&ndash;<?php echo min(5, $resultCount); ?> of <?php echo (int) $resultCount; ?></p>
              <div class="pagination__controls">
                <button class="pagination__btn" type="button" data-page-prev aria-label="Previous page" disabled>Previous</button>
                <div class="pagination__nums" data-page-nums>
                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <button class="pagination__num<?php echo $i === 1 ? ' is-current' : ''; ?>" type="button"<?php echo $i === 1 ? ' aria-current="page"' : ''; ?>><?php echo $i; ?></button>
                  <?php endfor; ?>
                </div>
                <button class="pagination__btn" type="button" data-page-next aria-label="Next page">Next</button>
              </div>
            </nav>

          <?php endif; ?>
        </div>
      </div>
    </section>

    <div class="scrim filter-drawer__scrim" data-filter-scrim hidden></div>

    <aside class="filter-drawer" id="filter-drawer" data-filter-drawer aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="filter-drawer-title" tabindex="-1">
      <div class="filter-drawer__head">
        <h2 class="filter-drawer__title" id="filter-drawer-title">Filters</h2>
        <button class="icon-button" type="button" data-filter-close id="filter-drawer-close" aria-label="Close filters">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M6 6l12 12"/>
            <path d="M18 6 6 18"/>
          </svg>
        </button>
      </div>
      <div class="filter-drawer__body" data-filter-drawer-body></div>
      <div class="filter-drawer__foot">
        <button class="btn btn--ghost" type="button" data-filter-reset>Reset</button>
        <button class="btn btn--primary" type="button" data-filter-apply>Show results</button>
      </div>
    </aside>
  </main>

  <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>