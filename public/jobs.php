<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/auth.php';
require dirname(__DIR__) . '/app/Core/db.php';
require dirname(__DIR__) . '/app/Support/html.php';

hirepeak_start_session();

$pageActive = 'jobs';
$pageScripts = ['/assets/js/main.js', '/assets/js/jobs.js'];

$jobs = [];
$dbError = false;
$resultCount = 0;
$totalPages = 1;
$currentPage = 1;

/* ---- GET input parsing (whitelisted only; nothing raw reaches SQL) ----------- */

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$locationSearch = trim((string) ($_GET['location'] ?? ''));

$enumEmployment = ['full_time', 'part_time', 'contract', 'internship', 'freelance'];
$enumMode = ['onsite', 'remote', 'hybrid'];
$enumExperience = ['entry', 'junior', 'mid', 'senior', 'lead'];
$sortOptions = ['newest', 'salary-desc', 'salary-asc'];
$sortLabels = [
    'newest'      => 'Newest',
    'salary-desc' => 'Salary: High to Low',
    'salary-asc'  => 'Salary: Low to High',
];

$sort = (string) ($_GET['sort'] ?? '');
if (!in_array($sort, $sortOptions, true)) {
    $sort = 'newest';
}

/* Multi-select params: silently drop anything outside the schema ENUMs /
 * database whitelists. Values are only used as bound IN (...) parameters. */
$categorySel = [];
$employmentSel = [];
$modeSel = [];
$experienceSel = [];
$locationsSel = [];

foreach ((array) ($_GET['employment'] ?? []) as $value) {
    if (is_string($value) && in_array($value, $enumEmployment, true)) {
        $employmentSel[] = $value;
    }
}
foreach ((array) ($_GET['mode'] ?? []) as $value) {
    if (is_string($value) && in_array($value, $enumMode, true)) {
        $modeSel[] = $value;
    }
}
foreach ((array) ($_GET['experience'] ?? []) as $value) {
    if (is_string($value) && in_array($value, $enumExperience, true)) {
        $experienceSel[] = $value;
    }
}

/** Builds the jobs listing URL for the current filter state, optionally adding
 * a page number. Arrays become repeated name[]=value pairs so multi-select
 * filters survive pagination, sorting, and chip removal intact. */
$jobsUrl = static function (array $state, ?int $page = null): string {
    $pairs = [];
    foreach ($state as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $one) {
                $pairs[] = rawurlencode($key) . '[]=' . rawurlencode((string) $one);
            }
        } elseif ((string) $value !== '') {
            $pairs[] = rawurlencode($key) . '=' . rawurlencode((string) $value);
        }
    }
    if ($page !== null) {
        $pairs[] = 'page=' . rawurlencode((string) $page);
    }
    return hirepeak_url('jobs.php' . ($pairs !== [] ? '?' . implode('&', $pairs) : ''));
};

try {
    $db = hirepeak_db();

    /* Database-backed whitelists so category/location filters only ever match
     * values that actually exist in the schema. */
    $validCategories = [];
    foreach (hirepeak_categories($db) as $category) {
        $validCategories[] = $category['name'];
    }
    $validLocations = [];
    $result = $db->query(
        'SELECT DISTINCT location FROM jobs '
        . "WHERE status = 'active' "
        . 'AND (application_deadline IS NULL OR application_deadline >= CURDATE()) '
        . "AND location <> '' ORDER BY location"
    );
    while ($row = $result->fetch_assoc()) {
        $validLocations[] = (string) $row['location'];
    }
    $result->close();

    foreach ((array) ($_GET['category'] ?? []) as $value) {
        if (is_string($value) && in_array($value, $validCategories, true)) {
            $categorySel[] = $value;
        }
    }
    foreach ((array) ($_GET['locations'] ?? []) as $value) {
        if (is_string($value) && in_array($value, $validLocations, true)) {
            $locationsSel[] = $value;
        }
    }

    /* ---- WHERE clause ----------------------------------------------------- */
    $where = [
        "j.status = 'active'",
        '(j.application_deadline IS NULL OR j.application_deadline >= CURDATE())',
    ];
    $params = [];
    $types = '';

    $addLikeParam = static function (string $value) use (&$params, &$types): void {
        $params[] = $value;
        $types .= 's';
    };

    /* Keyword: any whole term matching title / company / category / description. */
    $keywordTerms = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $keywordOrs = [];
    foreach ($keywordTerms as $term) {
        $keywordOrs[] = '(j.title LIKE ? OR c.name LIKE ? OR ct.name LIKE ? OR j.description LIKE ?)';
        $like = '%' . $term . '%';
        $addLikeParam($like);
        $addLikeParam($like);
        $addLikeParam($like);
        $addLikeParam($like);
    }
    if ($keywordOrs !== []) {
        $where[] = '(' . implode(' OR ', $keywordOrs) . ')';
    }

    /* Location text search. "remote" / "anywhere" / "worldwide" also match
     * remote-mode jobs, preserving the pre-existing Remote/Worldwide behaviour. */
    $locationTerms = preg_split('/\s+/', $locationSearch, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $locationOrs = [];
    foreach ($locationTerms as $term) {
        $locationOrs[] = 'j.location LIKE ?';
        $addLikeParam('%' . $term . '%');
        if (in_array(strtolower($term), ['remote', 'anywhere', 'worldwide'], true)) {
            $locationOrs[] = "j.work_mode = 'remote'";
        }
    }
    if ($locationOrs !== []) {
        $where[] = '(' . implode(' OR ', $locationOrs) . ')';
    }

    /* Sidebar multi-select filters — every value was whitelisted above. */
    $inGroup = static function (array $group, string $column) use (&$where, &$params, &$types, &$addLikeParam): void {
        if ($group === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($group), '?'));
        $where[] = $column . ' IN (' . $placeholders . ')';
        foreach ($group as $value) {
            $addLikeParam($value);
        }
    };
    $inGroup($categorySel, 'ct.name');
    $inGroup($employmentSel, 'j.employment_type');
    $inGroup($modeSel, 'j.work_mode');
    $inGroup($experienceSel, 'j.experience_level');

    if ($locationsSel !== []) {
        $placeholders = implode(',', array_fill(0, count($locationsSel), '?'));
        $cond = 'j.location IN (' . $placeholders . ')';
        foreach ($locationsSel as $value) {
            $addLikeParam($value);
        }
        /* Selecting "Remote" also matches remote-mode roles, as before. */
        if (in_array('Remote', $locationsSel, true)) {
            $cond = '(' . $cond . " OR j.work_mode = 'remote')";
        }
        $where[] = $cond;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $fromSql = 'FROM jobs j '
        . 'INNER JOIN companies c ON c.id = j.company_id '
        . 'INNER JOIN categories ct ON ct.id = j.category_id ';

    /* ---- Count ------------------------------------------------------------ */
    $stmt = $db->prepare('SELECT COUNT(*) AS total ' . $fromSql . $whereSql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultCount = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    /* ---- Pagination -------------------------------------------------------- */
    $pageSize = 5;
    $totalPages = max(1, (int) ceil($resultCount / $pageSize));
    $pageRaw = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT);
    $currentPage = ($pageRaw === false || $pageRaw < 1) ? 1 : (int) $pageRaw;
    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
    }
    $offset = ($currentPage - 1) * $pageSize;

    /* ---- Sort (whitelist only; static ORDER BY strings) -------------------- */
    $salarySort = 'COALESCE(j.salary_max, j.salary_min)';
    $orderBy = match ($sort) {
        'salary-desc' => 'ORDER BY (' . $salarySort . ' IS NULL) ASC, ' . $salarySort . ' DESC, j.created_at DESC, j.id DESC',
        'salary-asc'  => 'ORDER BY (' . $salarySort . ' IS NULL) ASC, ' . $salarySort . ' ASC, j.created_at DESC, j.id DESC',
        default       => 'ORDER BY j.created_at DESC, j.id DESC',
    };

    /* ---- Results ----------------------------------------------------------- */
    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.location, j.salary_min, j.salary_max,
                j.employment_type, j.work_mode, j.experience_level,
                j.created_at, j.description, j.requirements,
                c.name AS company_name, ct.name AS category_name
         ' . $fromSql . $whereSql . ' ' . $orderBy . ' LIMIT ? OFFSET ?'
    );
    $params[] = $pageSize;
    $types .= 'i';
    $params[] = $offset;
    $types .= 'i';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (\Throwable $e) {
    error_log('[hirepeak] jobs listing load failed: ' . $e->getMessage());
    $dbError = true;
}

$shownStart = $resultCount > 0 ? ($currentPage - 1) * $pageSize + 1 : 0;
$shownEnd = $resultCount > 0 ? min($currentPage * $pageSize, $resultCount) : 0;
$countLabel = $resultCount === 1 ? 'opportunity' : 'opportunities';

/* Full query state for URL building (all forms / links share it). */
$queryState = [
    'keyword'     => $keyword,
    'location'    => $locationSearch,
    'category'    => $categorySel,
    'employment'  => $employmentSel,
    'mode'        => $modeSel,
    'experience'  => $experienceSel,
    'locations'   => $locationsSel,
    'sort'        => $sort !== 'newest' ? $sort : '',
];

/* URL that removes a single chip value from the current query state. */
$withoutChip = static function (string $key, string $value) use ($queryState, $jobsUrl): string {
    $state = $queryState;
    if (is_array($state[$key] ?? null)) {
        $state[$key] = array_values(array_filter($state[$key], static fn (string $v): bool => $v !== $value));
    } else {
        $state[$key] = '';
    }
    return $jobsUrl($state);
};

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
    $filterValues['employment'][] = (string) $job['employment_type'];
    $filterValues['mode'][] = (string) $job['work_mode'];
    $filterValues['experience'][] = (string) $job['experience_level'];
    if ((string) $job['location'] !== '') {
        $filterValues['location'][] = (string) $job['location'];
    }
}
foreach ($filterValues as $group => $values) {
    $filterValues[$group] = array_values(array_unique($values));
}

/* Sidebar checkbox group => visible label / bound value mapping. Employment,
 * work mode, and experience bind the raw schema ENUM value (whitelisted for
 * the query) but display the human label. Category and location values are the
 * actual stored strings. */
$filterGroups = [
    'category'   => 'Category',
    'employment' => 'Employment type',
    'mode'       => 'Work mode',
    'experience' => 'Experience level',
    'location'   => 'Location',
];
$groupParam = [
    'category'   => 'category',
    'employment' => 'employment',
    'mode'       => 'mode',
    'experience' => 'experience',
    'location'   => 'locations',
];
$groupSelected = [
    'category'   => $categorySel,
    'employment' => $employmentSel,
    'mode'       => $modeSel,
    'experience' => $experienceSel,
    'location'   => $locationsSel,
];
$groupLabel = static function (string $group, string $value): string {
    return match ($group) {
        'employment' => hirepeak_employment_type_label($value),
        'mode'       => hirepeak_work_mode_label($value),
        'experience' => hirepeak_experience_level_label($value),
        default      => $value,
    };
};

/* ---- Active filter chips (server-rendered from GET state) ------------------- */
$activeChips = [];
if ($keyword !== '') {
    $activeChips[] = ['label' => 'Keyword: ' . $keyword, 'url' => $withoutChip('keyword', $keyword)];
}
if ($locationSearch !== '') {
    $activeChips[] = ['label' => 'Location: ' . $locationSearch, 'url' => $withoutChip('location', $locationSearch)];
}
foreach ($categorySel as $value) {
    $activeChips[] = ['label' => $value, 'url' => $withoutChip('category', $value)];
}
foreach ($employmentSel as $value) {
    $activeChips[] = ['label' => hirepeak_employment_type_label($value), 'url' => $withoutChip('employment', $value)];
}
foreach ($modeSel as $value) {
    $activeChips[] = ['label' => hirepeak_work_mode_label($value), 'url' => $withoutChip('mode', $value)];
}
foreach ($experienceSel as $value) {
    $activeChips[] = ['label' => hirepeak_experience_level_label($value), 'url' => $withoutChip('experience', $value)];
}
foreach ($locationsSel as $value) {
    $activeChips[] = ['label' => $value, 'url' => $withoutChip('locations', $value)];
}

/* Badge on the mobile Filters trigger: number of active checkbox filters. */
$activeFilterCount = count($categorySel)
    + count($employmentSel)
    + count($modeSel)
    + count($experienceSel)
    + count($locationsSel);
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
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="jobs-page">
  <?php require dirname(__DIR__) . '/app/Views/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main">
    <section class="jobs-hero">
      <div class="container jobs-hero__inner">
        <h1 class="jobs-hero__title">Find your next opportunity</h1>
        <p class="jobs-hero__sub">Explore roles that match your skills, goals, and preferred way of working.</p>

        <form class="hero-search jobs-search" role="search" method="get" action="<?php echo hirepeak_url('jobs.php'); ?>" data-jobs-search>
          <label class="sr-only" for="jobs-keyword">Job title, skill, or company</label>
          <div class="hero-search__field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="7"/>
              <path d="m20 20-3.5-3.5"/>
            </svg>
            <input id="jobs-keyword" name="keyword" type="search" placeholder="Job title, skill, or company" autocomplete="off" value="<?php echo hirepeak_esc($keyword); ?>">
          </div>

          <div class="hero-search__divider" aria-hidden="true"></div>

          <label class="sr-only" for="jobs-location">Location</label>
          <div class="hero-search__field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <input id="jobs-location" name="location" type="text" placeholder="City, or remote" autocomplete="off" value="<?php echo hirepeak_esc($locationSearch); ?>">
          </div>

          <?php foreach ($categorySel as $value): ?>
            <input type="hidden" name="category[]" value="<?php echo hirepeak_esc($value); ?>">
          <?php endforeach; ?>
          <?php foreach ($employmentSel as $value): ?>
            <input type="hidden" name="employment[]" value="<?php echo hirepeak_esc($value); ?>">
          <?php endforeach; ?>
          <?php foreach ($modeSel as $value): ?>
            <input type="hidden" name="mode[]" value="<?php echo hirepeak_esc($value); ?>">
          <?php endforeach; ?>
          <?php foreach ($experienceSel as $value): ?>
            <input type="hidden" name="experience[]" value="<?php echo hirepeak_esc($value); ?>">
          <?php endforeach; ?>
          <?php foreach ($locationsSel as $value): ?>
            <input type="hidden" name="locations[]" value="<?php echo hirepeak_esc($value); ?>">
          <?php endforeach; ?>
          <input type="hidden" name="sort" value="<?php echo hirepeak_esc($sort); ?>">

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
            <a class="jobs-sidebar__clear" href="<?php echo hirepeak_esc($jobsUrl([])); ?>">Clear all</a>
          </div>

          <form class="jobs-filters" method="get" action="<?php echo hirepeak_url('jobs.php'); ?>" data-jobs-filters>
            <input type="hidden" name="keyword" value="<?php echo hirepeak_esc($keyword); ?>">
            <input type="hidden" name="location" value="<?php echo hirepeak_esc($locationSearch); ?>">
            <input type="hidden" name="sort" value="<?php echo hirepeak_esc($sort); ?>">
            <?php foreach ($filterGroups as $group => $title): ?>
              <?php if ($filterValues[$group] !== []): ?>
                <fieldset class="jobs-filter">
                  <legend class="jobs-filter__title"><?php echo hirepeak_esc($title); ?></legend>
                  <div class="jobs-filter__options">
                    <?php foreach ($filterValues[$group] as $value): ?>
                      <label class="jobs-check"><input type="checkbox" name="<?php echo hirepeak_esc($groupParam[$group]); ?>[]" value="<?php echo hirepeak_esc($value); ?>"<?php echo in_array($value, $groupSelected[$group], true) ? ' checked' : ''; ?>><span><?php echo hirepeak_esc($groupLabel($group, $value)); ?></span></label>
                    <?php endforeach; ?>
                  </div>
                </fieldset>
              <?php endif; ?>
            <?php endforeach; ?>
            <button class="btn btn--primary jobs-filters__apply" type="submit">Apply Filters</button>
          </form>
        </aside>

        <div class="jobs-results">
          <div class="jobs-toolbar">
            <div class="jobs-toolbar__row">
              <h2 class="jobs-count" id="results-count"><?php echo (int) $resultCount; ?> <?php echo hirepeak_esc($countLabel); ?></h2>
              <div class="jobs-toolbar__controls">
                <button class="btn btn--outline jobs-filter-trigger" type="button" aria-expanded="false" aria-controls="filter-drawer">
                  Filters <?php if ($activeFilterCount > 0): ?><span class="jobs-filter-trigger__badge"><?php echo (int) $activeFilterCount; ?></span><?php endif; ?>
                </button>
                <form class="jobs-sort" method="get" action="<?php echo hirepeak_url('jobs.php'); ?>" data-jobs-sort>
                  <input type="hidden" name="keyword" value="<?php echo hirepeak_esc($keyword); ?>">
                  <input type="hidden" name="location" value="<?php echo hirepeak_esc($locationSearch); ?>">
                  <?php foreach ($categorySel as $value): ?>
                    <input type="hidden" name="category[]" value="<?php echo hirepeak_esc($value); ?>">
                  <?php endforeach; ?>
                  <?php foreach ($employmentSel as $value): ?>
                    <input type="hidden" name="employment[]" value="<?php echo hirepeak_esc($value); ?>">
                  <?php endforeach; ?>
                  <?php foreach ($modeSel as $value): ?>
                    <input type="hidden" name="mode[]" value="<?php echo hirepeak_esc($value); ?>">
                  <?php endforeach; ?>
                  <?php foreach ($experienceSel as $value): ?>
                    <input type="hidden" name="experience[]" value="<?php echo hirepeak_esc($value); ?>">
                  <?php endforeach; ?>
                  <?php foreach ($locationsSel as $value): ?>
                    <input type="hidden" name="locations[]" value="<?php echo hirepeak_esc($value); ?>">
                  <?php endforeach; ?>
                  <label class="jobs-sort__label">
                    <span class="sr-only">Sort results</span>
                    <select name="sort">
                      <?php foreach ($sortOptions as $option): ?>
                        <option value="<?php echo hirepeak_esc($option); ?>"<?php echo $sort === $option ? ' selected' : ''; ?>><?php echo hirepeak_esc($sortLabels[$option]); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <button class="btn btn--outline jobs-sort__submit" type="submit">Sort</button>
                </form>
              </div>
            </div>
          </div>

          <?php if ($activeChips !== []): ?>
          <div class="active-filters">
            <ul aria-label="Active filters">
              <?php foreach ($activeChips as $chip): ?>
                <li><a class="active-filters__chip" href="<?php echo hirepeak_esc($chip['url']); ?>" aria-label="Remove <?php echo hirepeak_esc($chip['label']); ?> filter"><?php echo hirepeak_esc($chip['label']); ?><span class="active-filters__x" aria-hidden="true">&times;</span></a></li>
              <?php endforeach; ?>
            </ul>
            <a class="active-filters__clear" href="<?php echo hirepeak_url('jobs.php'); ?>">Clear all</a>
          </div>
          <?php endif; ?>

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
                $companyLine = hirepeak_esc((string) $job['company_name'])
                    . ' <span class="job-card__dot" aria-hidden="true">&middot;</span> '
                    . hirepeak_esc((string) $job['location'])
                    . ' <span class="job-card__dot" aria-hidden="true">&middot;</span> '
                    . hirepeak_esc(hirepeak_work_mode_label((string) $job['work_mode']));
                $posted = hirepeak_relative_time((string) $job['created_at']);
                ?>
                <article class="job-card job-card--row" data-keywords="<?php echo hirepeak_esc(hirepeak_job_keywords($job)); ?>" data-job-id="<?php echo (int) $job['id']; ?>">
                  <div class="job-card--row__main">
                    <span class="job-card__logo" aria-hidden="true"><?php echo hirepeak_esc(hirepeak_initials((string) $job['company_name'])); ?></span>
                    <div class="job-card--row__body">
                      <h3 class="job-card__title"><a href="<?php echo hirepeak_url('job-details.php?id=' . (int) $job['id']); ?>"><?php echo hirepeak_esc((string) $job['title']); ?></a></h3>
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
                      <form class="save-form" method="post" action="<?php echo hirepeak_url('save-job.php'); ?>" data-save-job data-job-id="<?php echo (int) $job['id']; ?>" data-save-label="<?php echo hirepeak_esc($saveLabel); ?>" data-saved-label="<?php echo hirepeak_esc($savedLabel); ?>">
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
                      <a class="icon-button job-card__save" href="<?php echo hirepeak_url('login.php'); ?>" aria-label="Sign in to save <?php echo hirepeak_esc((string) $job['title']); ?> at <?php echo hirepeak_esc((string) $job['company_name']); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                        </svg>
                      </a>
                    <?php endif; ?>
                    <a class="btn btn--outline btn--sm" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $job['id']); ?>">View Job</a>
                  </div>
                </article>
              <?php endforeach; ?>

            </div>

            <?php if ($resultCount === 0): ?>
            <div class="jobs-empty">
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
              <a class="btn btn--primary" href="<?php echo hirepeak_url('jobs.php'); ?>">Clear Filters</a>
            </div>
            <?php endif; ?>

            <?php if ($resultCount > 0): ?>
            <nav class="pagination" aria-label="Results pagination">
              <p class="pagination__meta">Showing <?php echo (int) $shownStart; ?>&ndash;<?php echo (int) $shownEnd; ?> of <?php echo (int) $resultCount; ?> <?php echo hirepeak_esc($countLabel); ?></p>
              <div class="pagination__controls">
                <?php if ($currentPage > 1): ?>
                  <a class="pagination__btn" href="<?php echo hirepeak_esc($jobsUrl($queryState, $currentPage - 1)); ?>" data-page-prev aria-label="Previous page">Previous</a>
                <?php else: ?>
                  <span class="pagination__btn is-disabled" aria-disabled="true">Previous</span>
                <?php endif; ?>
                <div class="pagination__nums">
                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $currentPage): ?>
                      <span class="pagination__num is-current" aria-current="page"><?php echo $i; ?></span>
                    <?php else: ?>
                      <a class="pagination__num" href="<?php echo hirepeak_esc($jobsUrl($queryState, $i)); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                  <?php endfor; ?>
                </div>
                <?php if ($currentPage < $totalPages): ?>
                  <a class="pagination__btn" href="<?php echo hirepeak_esc($jobsUrl($queryState, $currentPage + 1)); ?>" data-page-next aria-label="Next page">Next</a>
                <?php else: ?>
                  <span class="pagination__btn is-disabled" aria-disabled="true">Next</span>
                <?php endif; ?>
              </div>
            </nav>
            <?php endif; ?>

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
        <a class="btn btn--ghost" href="<?php echo hirepeak_url('jobs.php'); ?>">Reset</a>
        <button class="btn btn--primary" type="button" data-filter-apply>Show results</button>
      </div>
    </aside>
  </main>

  <?php require dirname(__DIR__) . '/app/Views/footer.php'; ?>
</body>
</html>