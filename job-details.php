<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/html.php';

hirepeak_start_session();

$pageActive = 'jobs';
$pageScripts = ['src/js/main.js'];

$rawId = (string) ($_GET['id'] ?? '');
if ($rawId === '' || preg_match('/^[1-9][0-9]*$/D', $rawId) !== 1) {
    hirepeak_job_not_found();
}
$jobId = (int) $rawId;

$job = null;
$related = [];
$isOpen = false;
$owner = false;
$viewerRole = '';

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.description, j.requirements, j.location,
                j.salary_min, j.salary_max, j.employment_type, j.work_mode,
                j.experience_level, j.application_deadline, j.status,
                j.created_at, j.employer_id,
                c.name AS company_name, c.industry,
                c.description AS company_description,
                c.location AS company_location,
                ct.name AS category_name,
                (j.status = "active"
                    AND (j.application_deadline IS NULL
                         OR j.application_deadline >= CURDATE())) AS is_open
         FROM jobs j
         INNER JOIN companies c ON c.id = j.company_id
         INNER JOIN categories ct ON ct.id = j.category_id
         WHERE j.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $jobId);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($job === null) {
        hirepeak_job_not_found();
    }

$isOpen = (bool) ($job['is_open'] ?? false);
$viewerRole = (string) (hirepeak_user()['role'] ?? '');
$viewerId = (int) (hirepeak_user()['id'] ?? 0);
$owner = $viewerRole === 'employer' && $viewerId === (int) $job['employer_id'];
$savedThis = false;
$savedRelated = [];
$saveCsrf = '';

if ($viewerRole === 'applicant' && $viewerId > 0) {
    $saveCsrf = hirepeak_csrf_token();
    try {
        $allSaved = hirepeak_saved_job_ids($db, $viewerId);
        foreach ($allSaved as $sid) {
            $savedRelated[$sid] = true;
        }
        $savedThis = isset($savedRelated[$jobId]);
    } catch (\Throwable $e) {
        error_log('[hirepeak] saved-jobs details state failed: ' . $e->getMessage());
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

    if (($job['status'] ?? '') === 'draft' && !$owner) {
        hirepeak_job_not_found();
    }

    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.location, j.employment_type, j.work_mode,
                j.salary_min, j.salary_max, j.created_at,
                c.name AS company_name
         FROM jobs j
         INNER JOIN companies c ON c.id = j.company_id
         WHERE j.category_id = ? AND j.id <> ?
           AND j.status = "active"
           AND (j.application_deadline IS NULL
                OR j.application_deadline >= CURDATE())
         ORDER BY j.created_at DESC
         LIMIT 3'
    );
    $relatedCat = (int) $job['category_id'];
    $stmt->bind_param('ii', $relatedCat, $jobId);
    $stmt->execute();
    $related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (\Throwable $e) {
    error_log('[hirepeak] job details load failed: ' . $e->getMessage());
    hirepeak_job_not_found();
}

$jobTitle = trim((string) $job['title']);
$companyName = trim((string) $job['company_name']);
$jobLocation = trim((string) $job['location']);
$status = (string) $job['status'];
$deadlineStr = (string) ($job['application_deadline'] ?? '');
$salary = hirepeak_salary_range(
    isset($job['salary_min']) ? (string) $job['salary_min'] : '',
    isset($job['salary_max']) ? (string) $job['salary_max'] : ''
);
$postedLabel = hirepeak_relative_time((string) $job['created_at']);
$deadlineLabel = $deadlineStr !== '' ? date('M j, Y', strtotime($deadlineStr)) : '';
$isDraft = $status === 'draft';
$isClosed = $status !== 'draft' && !$isOpen;

$apply = $isDraft ? null : hirepeak_apply_cta($jobId);
$applyCta = $isDraft ? '' : ($apply['cta'] ?? '');
$applyNote = $isDraft ? '' : ($apply['note'] ?? '');

$companyDescription = trim((string) ($job['company_description'] ?? ''));
$companyIndustry = trim((string) ($job['industry'] ?? ''));
$companyLocation = trim((string) ($job['company_location'] ?? ''));
$companyMetaLine = trim($companyIndustry . ($companyLocation !== '' ? ' &middot; ' . $companyLocation : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo hirepeak_esc($jobTitle . ' at ' . $companyName . ' — HirePeak'); ?></title>
  <meta name="description" content="<?php echo hirepeak_esc($jobTitle . ' job at ' . $companyName . ' in ' . $jobLocation . '. Apply on HirePeak.'); ?>">
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

    <?php if ($owner): ?>
      <div class="jd-ownerbar">
        <div class="container jd-ownerbar__inner">
          <p class="jd-ownerbar__text">You're viewing one of your own job postings.
            <a href="edit-job.php?id=<?php echo (int) $jobId; ?>">Edit Job</a>
            <?php if ($status === 'active'): ?>
              <a href="close-job.php?id=<?php echo (int) $jobId; ?>">Close Job</a>
            <?php endif; ?>
            <a href="my-jobs.php">My Jobs</a>
          </p>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($isDraft): ?>
      <div class="jd-band jd-band--draft" role="note">
        <div class="container jd-band__inner">
          <strong>Draft preview.</strong> This job is not public yet — candidates can't see it.
          <a href="edit-job.php?id=<?php echo (int) $jobId; ?>">Edit or publish this draft</a>.
        </div>
      </div>
    <?php elseif ($isClosed): ?>
      <div class="jd-band jd-band--closed" role="note">
        <div class="container jd-band__inner">
          <?php echo hirepeak_job_status_badge($status); ?> Applications are closed for this role.
        </div>
      </div>
    <?php endif; ?>

    <?php if ($saveFlash !== ''): ?>
      <div class="container jd-flash">
        <p class="dash-flash" role="status"><?php echo hirepeak_esc($saveFlash); ?></p>
      </div>
    <?php elseif ($saveFlashError !== ''): ?>
      <div class="container jd-flash">
        <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($saveFlashError); ?></p>
      </div>
    <?php endif; ?>

    <div class="jd-topbar">
      <div class="container jd-topbar__inner">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li><a href="jobs.php">Find Jobs</a></li>
            <li aria-current="page"><?php echo hirepeak_esc($jobTitle); ?></li>
          </ol>
        </nav>
        <a class="jd-back" href="jobs.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5"/>
            <path d="m11 18-6-6 6-6"/>
          </svg>
          Back to jobs
        </a>
      </div>
    </div>

    <section class="jd-section" aria-label="<?php echo hirepeak_esc($jobTitle . ' job details'); ?>">
      <div class="container jd-layout">
        <article class="jd-main">
          <header class="jd-head">
            <div class="jd-head__row">
              <span class="job-card__logo jd-head__logo" aria-hidden="true"><?php echo hirepeak_esc(hirepeak_initials($companyName)); ?></span>
              <div>
                <h1 class="jd-head__title"><?php echo hirepeak_esc($jobTitle); ?></h1>
                <p class="jd-head__company"><?php echo hirepeak_esc($companyName); ?></p>
                <p class="jd-head__location"><?php echo hirepeak_esc($jobLocation); ?> <span aria-hidden="true">&middot;</span> <?php echo hirepeak_esc(hirepeak_work_mode_label((string) $job['work_mode'])); ?></p>
              </div>
            </div>

            <ul class="jd-head__chips" aria-label="Job type and experience level">
              <li class="chip"><?php echo hirepeak_esc(hirepeak_employment_type_label((string) $job['employment_type'])); ?></li>
              <li class="chip"><?php echo hirepeak_esc(hirepeak_experience_level_label((string) $job['experience_level'])); ?></li>
            </ul>

            <div class="jd-head__pay">
              <p class="jd-head__salary"><?php echo $salary; ?></p>
              <?php if ($postedLabel !== ''): ?>
                <p class="jd-head__posted">Posted <?php echo hirepeak_esc($postedLabel); ?></p>
              <?php endif; ?>
            </div>
          </header>

          <section class="jd-prose" aria-labelledby="jd-about">
            <h2 id="jd-about">About the role</h2>
            <?php echo nl2br(hirepeak_esc($job['description'])); ?>
          </section>

          <?php if (trim((string) ($job['requirements'] ?? '')) !== ''): ?>
            <section class="jd-prose" aria-labelledby="jd-requirements">
              <h2 id="jd-requirements">Requirements</h2>
              <?php echo nl2br(hirepeak_esc((string) $job['requirements'])); ?>
            </section>
          <?php endif; ?>
        </article>

        <aside class="jd-aside" aria-label="Apply and job overview">
          <div class="jd-panel jd-apply">
            <?php if (!$isDraft && $deadlineLabel !== '' && $isOpen): ?>
              <p class="jd-apply__deadline">Apply before <strong><?php echo hirepeak_esc($deadlineLabel); ?></strong></p>
            <?php elseif ($isDraft): ?>
              <p class="jd-apply__deadline">Not accepting applications yet</p>
            <?php endif; ?>
            <div class="jd-apply__actions">
              <?php if ($isDraft): ?>
                <a class="btn btn--primary" href="edit-job.php?id=<?php echo (int) $jobId; ?>">Edit Draft</a>
              <?php else: ?>
                <?php echo $applyCta; ?>
              <?php endif; ?>
              <?php if ($viewerRole === 'applicant'): ?>
                <form class="save-form jd-save-aside" method="post" action="save-job.php">
                  <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc($saveCsrf); ?>">
                  <input type="hidden" name="job_id" value="<?php echo (int) $jobId; ?>">
                  <input type="hidden" name="action" value="<?php echo $savedThis ? 'unsave' : 'save'; ?>">
                  <input type="hidden" name="return_to" value="job-details">
                  <button class="btn btn--secondary" type="submit" aria-pressed="<?php echo $savedThis ? 'true' : 'false'; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                    </svg>
                    <?php echo $savedThis ? 'Saved' : 'Save Job'; ?>
                  </button>
                </form>
              <?php elseif ($viewerRole === ''): ?>
                <a class="btn btn--secondary" href="login.php">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                  Sign in to save
                </a>
              <?php endif; ?>
            </div>
            <?php if (!$isDraft): ?>
              <p class="jd-apply__note"><?php echo hirepeak_esc($applyNote); ?></p>
            <?php else: ?>
              <p class="jd-apply__note">Only you can see this draft. Candidates will see it once you publish.</p>
            <?php endif; ?>
          </div>

          <div class="jd-panel jd-facts">
            <h2 class="jd-facts__title">Job overview</h2>
            <dl class="jd-facts__list">
              <div class="jd-fact"><dt>Salary</dt><dd><?php echo $salary; ?></dd></div>
              <div class="jd-fact"><dt>Location</dt><dd><?php echo hirepeak_esc($jobLocation); ?></dd></div>
              <div class="jd-fact"><dt>Job type</dt><dd><?php echo hirepeak_esc(hirepeak_employment_type_label((string) $job['employment_type'])); ?></dd></div>
              <div class="jd-fact"><dt>Work mode</dt><dd><?php echo hirepeak_esc(hirepeak_work_mode_label((string) $job['work_mode'])); ?></dd></div>
              <div class="jd-fact"><dt>Experience</dt><dd><?php echo hirepeak_esc(hirepeak_experience_level_label((string) $job['experience_level'])); ?></dd></div>
              <?php if ($deadlineLabel !== ''): ?>
                <div class="jd-fact"><dt>Deadline</dt><dd><?php echo hirepeak_esc($deadlineLabel); ?></dd></div>
              <?php endif; ?>
              <div class="jd-fact"><dt>Category</dt><dd><?php echo hirepeak_esc((string) $job['category_name']); ?></dd></div>
              <div class="jd-fact"><dt>Job ID</dt><dd>#<?php echo (int) $jobId; ?></dd></div>
            </dl>
          </div>

          <div class="jd-panel jd-company">
            <h2 class="jd-facts__title">About the company</h2>
            <div class="jd-company__head">
              <span class="job-card__logo jd-company__logo" aria-hidden="true"><?php echo hirepeak_esc(hirepeak_initials($companyName)); ?></span>
              <div>
                <p class="jd-company__name"><?php echo hirepeak_esc($companyName); ?></p>
                <?php if ($companyMetaLine !== ''): ?>
                  <p class="jd-company__meta"><?php echo $companyMetaLine; ?></p>
                <?php endif; ?>
              </div>
            </div>
            <?php if ($companyDescription !== ''): ?>
              <p class="jd-company__text"><?php echo hirepeak_esc($companyDescription); ?></p>
            <?php endif; ?>
          </div>
        </aside>
      </div>
    </section>

    <?php if ($related !== []): ?>
      <section class="jd-related" aria-labelledby="related-title">
        <div class="container">
          <div class="section-head">
            <h2 class="section-title" id="related-title">Related jobs</h2>
          </div>

          <div class="job-grid related-grid">
            <?php foreach ($related as $rj): ?>
              <article class="job-card job-card--related">
                <div class="job-card__top">
                  <span class="job-card__logo" aria-hidden="true"><?php echo hirepeak_esc(hirepeak_initials((string) $rj['company_name'])); ?></span>
                  <?php if ($viewerRole === 'applicant'): ?>
                    <?php $rjSaved = isset($savedRelated[(int) $rj['id']]); ?>
                    <?php $rjSaveLabel = 'Save ' . (string) $rj['title'] . ' at ' . (string) $rj['company_name']; ?>
                    <?php $rjSavedLabel = 'Remove ' . (string) $rj['title'] . ' at ' . (string) $rj['company_name'] . ' from saved jobs'; ?>
                    <form class="save-form" method="post" action="save-job.php">
                      <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc($saveCsrf); ?>">
                      <input type="hidden" name="job_id" value="<?php echo (int) $rj['id']; ?>">
                      <input type="hidden" name="action" value="<?php echo $rjSaved ? 'unsave' : 'save'; ?>">
                      <input type="hidden" name="return_to" value="job-details">
                      <button class="icon-button job-card__save" type="submit" aria-pressed="<?php echo $rjSaved ? 'true' : 'false'; ?>" aria-label="<?php echo hirepeak_esc($rjSaved ? $rjSavedLabel : $rjSaveLabel); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                        </svg>
                      </button>
                    </form>
                  <?php elseif ($viewerRole === ''): ?>
                    <a class="icon-button job-card__save" href="login.php" aria-label="Sign in to save <?php echo hirepeak_esc((string) $rj['title']); ?> at <?php echo hirepeak_esc((string) $rj['company_name']); ?>">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                      </svg>
                    </a>
                  <?php endif; ?>
                </div>
                <h3 class="job-card__title"><a href="job-details.php?id=<?php echo (int) $rj['id']; ?>"><?php echo hirepeak_esc((string) $rj['title']); ?></a></h3>
                <p class="job-card__company"><?php echo hirepeak_esc((string) $rj['company_name']); ?> &middot; <?php echo hirepeak_esc((string) $rj['location']); ?> &middot; <?php echo hirepeak_esc(hirepeak_work_mode_label((string) $rj['work_mode'])); ?></p>
                <ul class="job-card__meta" aria-label="Job type and work mode">
                  <li class="chip"><?php echo hirepeak_esc(hirepeak_employment_type_label((string) $rj['employment_type'])); ?></li>
                  <li class="chip"><?php echo hirepeak_esc(hirepeak_work_mode_label((string) $rj['work_mode'])); ?></li>
                </ul>
                <p class="job-card__salary"><?php echo hirepeak_salary_range(isset($rj['salary_min']) ? (string) $rj['salary_min'] : '', isset($rj['salary_max']) ? (string) $rj['salary_max'] : ''); ?></p>
                <p class="job-card__posted">Posted <?php echo hirepeak_esc(hirepeak_relative_time((string) $rj['created_at'])); ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <div class="jd-bar">
    <div class="jd-bar__inner">
      <?php if ($viewerRole === 'applicant'): ?>
        <form class="save-form jd-save-bar" method="post" action="save-job.php">
          <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc($saveCsrf); ?>">
          <input type="hidden" name="job_id" value="<?php echo (int) $jobId; ?>">
          <input type="hidden" name="action" value="<?php echo $savedThis ? 'unsave' : 'save'; ?>">
          <input type="hidden" name="return_to" value="job-details">
          <button class="btn btn--secondary" type="submit" aria-pressed="<?php echo $savedThis ? 'true' : 'false'; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
            </svg>
            <?php echo $savedThis ? 'Saved' : 'Save'; ?>
          </button>
        </form>
      <?php elseif ($viewerRole === ''): ?>
        <a class="btn btn--secondary" href="login.php">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
          </svg>
          Sign in to save
        </a>
      <?php endif; ?>
      <?php if ($isDraft): ?>
        <a class="btn btn--primary" href="edit-job.php?id=<?php echo (int) $jobId; ?>">Edit Draft</a>
      <?php else: ?>
        <?php echo $applyCta; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>