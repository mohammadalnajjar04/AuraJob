<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/auth.php';
require dirname(__DIR__) . '/app/Core/db.php';
require dirname(__DIR__) . '/app/Support/html.php';
require dirname(__DIR__) . '/app/Services/crypto.php';
require dirname(__DIR__) . '/app/Services/notifications.php';

$user = hirepeak_require_role('applicant');
$uid = (int) $user['id'];

/*
 * Apply — a single-page review + confirm flow for one job.
 *
 * - Authorization never trusts client input: the applicant id comes from the
 *   authenticated session, the job id is revalidated server-side on GET and
 *   POST, and role/status are never read from the request.
 * - Eligibility (job exists, is active, deadline not passed) is enforced on
 *   both GET and POST against the stored job row.
 * - Duplicates are prevented by the schema UNIQUE(applicant_id, job_id). A
 *   pre-check hides the form for anyone who already applied, and a
 *   duplicate-key race during INSERT resolves to the existing application
 *   (redirected to its tracking page) instead of surfacing an SQL error.
 * - PRG: a successful INSERT sets a one-time flash and 303s to the tracking
 *   page so a refresh can never resubmit.
 * - The applications table only carries applicant_id/job_id/status plus
 *   timestamps; there is no cover letter or upload, so this page is a calm
 *   confirmation of the job plus the applicant's real profile data.
 */

$rawId = (string) ($_GET['id'] ?? '');

/* Wholestring guard: only plain positive integers reach the database. Anything
 * else (empty, abc, -1, fractions, trailing junk, SQL injection shapes) is a
 * clean 404 without any query being run. */
if ($rawId === '' || preg_match('/^[1-9][0-9]*$/D', $rawId) !== 1) {
    hirepeak_apply_not_found();
}
$jobId = (int) $rawId;

/*
 * Calm 404 for any missing / malformed job id.
 */
function hirepeak_apply_not_found(): void
{
    $pageActive = '';
    $pageScripts = ['/assets/js/main.js'];
    http_response_code(404);
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job not found — AuraJob</title>
  <meta name="description" content="We couldn't find this job.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="/assets/images/aurajob-mark.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <?php require dirname(__DIR__) . '/app/Views/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="dash-main">
    <div class="container">
      <section class="empty-state">
        <div class="empty-state__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <path d="m15 9-6 6"/>
            <path d="m9 9 6 6"/>
          </svg>
        </div>
        <h1 class="empty-state__title">Job not found</h1>
        <p class="empty-state__text">We couldn't find this job. It may have been removed, or the link may be incorrect.</p>
        <div class="empty-state__actions">
          <a class="btn btn--primary" href="<?php echo hirepeak_url('jobs.php'); ?>">Find Jobs</a>
          <a class="btn btn--outline" href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Back to Dashboard</a>
        </div>
      </section>
    </div>
  </main>

  <?php require dirname(__DIR__) . '/app/Views/footer.php'; ?>
</body>
</html><?php
    exit;
}

$state = 'review'; // 'error' | 'closed' | 'already' | 'review'
$formError = '';
$job = null;
$profile = null;
$completeness = ['percent' => 0, 'filled' => 0, 'total' => 0, 'missing' => []];
$existingId = null;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT j.id, j.title, j.location, j.salary_min, j.salary_max,
                j.employment_type, j.work_mode, j.experience_level, j.status,
                j.application_deadline, j.employer_id,
                c.name AS company_name, ct.name AS category_name,
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
        hirepeak_apply_not_found();
    }

    $deadlineStr = (string) ($job['application_deadline'] ?? '');
    $jobOpen = (bool) ($job['is_open'] ?? false);

    if (!$jobOpen) {
        $state = 'closed';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            /* Non-mutating POST: land on a clean GET so refresh never re-posts. */
            header('Location: ' . hirepeak_url('apply.php?id=' . $jobId), true, 303);
            exit;
        }
    }

    $stmt = $db->prepare(
        'SELECT professional_title, bio, phone, location, experience_years, skills
         FROM applicant_profiles
         WHERE user_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $completeness = hirepeak_profile_completeness($profile ?: null);

    $stmt = $db->prepare(
        'SELECT id FROM applications WHERE applicant_id = ? AND job_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $uid, $jobId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing !== null && $state === 'review') {
        $existingId = (int) $existing['id'];
        $state = 'already';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Location: ' . hirepeak_url('applicant/tracking.php?id=' . $existingId), true, 303);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $state === 'review') {
        if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
            $formError = 'Your session expired. Please try again.';
            hirepeak_csrf_token(true);
        } else {
            try {
                $stmt = $db->prepare('INSERT INTO applications (applicant_id, job_id) VALUES (?, ?)');
                $stmt->bind_param('ii', $uid, $jobId);
                $stmt->execute();
                $newId = (int) $db->insert_id;
                $stmt->close();

                /* In-app notice for the job owner: "new application" event. */
                $ownerId = (int) ($job['employer_id'] ?? 0);
                $jobTitleNotif = trim((string) ($job['title'] ?? ''));
                if ($ownerId > 0 && $jobTitleNotif !== '') {
                    hirepeak_notification_create(
                        $db,
                        $ownerId,
                        'application_new',
                        'A new application was submitted for ' . $jobTitleNotif . '.'
                    );
                }

                $_SESSION['hirepeak_apply_flash'] = 'Application submitted successfully.';
                header('Location: ' . hirepeak_url('applicant/tracking.php?id=' . $newId), true, 303);
                exit;
            } catch (\mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    /* Duplicate-key race: another request already created the row. */
                    $stmt = $db->prepare('SELECT id FROM applications WHERE applicant_id = ? AND job_id = ? LIMIT 1');
                    $stmt->bind_param('ii', $uid, $jobId);
                    $stmt->execute();
                    $dup = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($dup !== null) {
                        header('Location: ' . hirepeak_url('applicant/tracking.php?id=' . (int) $dup['id']), true, 303);
                        exit;
                    }
                    $formError = 'We couldn\'t submit your application right now. Please try again in a moment.';
                } else {
                    throw $e;
                }
            } catch (\Throwable $e) {
                error_log('[hirepeak] application submit failed: ' . $e->getMessage());
                $formError = 'We couldn\'t submit your application right now. Please try again in a moment.';
            }
        }
    }
} catch (\Throwable $e) {
    error_log('[hirepeak] application review load failed: ' . $e->getMessage());
    $state = 'error';
    $job = null;
    $profile = null;
}

/* Display values shared by the review + state branches. */
$jobTitle = '';
$companyName = '';
$jobLocation = '';
if ($job !== null) {
    $jobTitle = trim((string) ($job['title'] ?? ''));
    $companyName = trim((string) ($job['company_name'] ?? ''));
    $jobLocation = trim((string) ($job['location'] ?? ''));
}
$email = (string) ($user['email'] ?? '');
$name = trim((string) ($user['name'] ?? ''));

$profileRows = []; // [label => escaped value]
$bio = '';
$phoneHint = false;
if ($profile !== null) {
    $title = trim((string) ($profile['professional_title'] ?? ''));
    if ($title !== '') {
        $profileRows['Professional title'] = hirepeak_esc($title);
    }
    $location = trim((string) ($profile['location'] ?? ''));
    if ($location !== '') {
        $profileRows['Location'] = hirepeak_esc($location);
    }
    $years = $profile['experience_years'] ?? '';
    if ($years !== null && trim((string) $years) !== '') {
        $profileRows['Experience'] = hirepeak_esc((int) $years === 1 ? '1 year' : (int) $years . ' years');
    }
    $skills = trim((string) ($profile['skills'] ?? ''));
    if ($skills !== '') {
        $profileRows['Skills'] = hirepeak_esc($skills);
    }
    $phoneRaw = (string) ($profile['phone'] ?? '');
    if ($phoneRaw !== '') {
        /* Never expose ciphertext: broken v1: payloads are hidden, legacy
         * plaintext rows are shown as-is. */
        $phone = hirepeak_phone_decrypt($phoneRaw)
            ?? (str_starts_with($phoneRaw, 'v1:') ? null : $phoneRaw);
        if ($phone !== null && trim($phone) !== '') {
            $profileRows['Phone'] = hirepeak_esc(trim($phone));
            $phoneHint = true;
        }
    }
    $bio = trim((string) ($profile['bio'] ?? ''));
}

$salary = $job !== null
    ? hirepeak_salary_range(
        isset($job['salary_min']) ? (string) $job['salary_min'] : '',
        isset($job['salary_max']) ? (string) $job['salary_max'] : ''
    )
    : 'Salary not disclosed';

$deadlineLabel = $job !== null && $deadlineStr !== ''
    ? date('M j, Y', strtotime($deadlineStr))
    : '';

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo hirepeak_esc($jobTitle !== '' ? 'Apply to ' . $jobTitle . ' — AuraJob' : 'Apply — AuraJob'); ?></title>
  <meta name="description" content="Review and submit your application on AuraJob.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="/assets/images/aurajob-mark.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <?php require dirname(__DIR__) . '/app/Views/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="dash-main">
    <div class="container">

      <?php if ($state === 'error'): ?>

        <div class="track-topbar">
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
              <li><a href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Dashboard</a></li>
              <li><a href="<?php echo hirepeak_url('jobs.php'); ?>">Find Jobs</a></li>
            </ol>
          </nav>
          <a class="jd-back" href="<?php echo hirepeak_url('jobs.php'); ?>">Back to jobs</a>
        </div>

        <section class="dash-alert" role="alert" aria-labelledby="apply-error-title">
          <h1 id="apply-error-title">Something went wrong on our side.</h1>
          <p>We couldn't load this application request right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('apply.php?id=' . $jobId); ?>">Try again</a>
        </section>

      <?php elseif ($state === 'closed'): ?>

        <div class="track-topbar">
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
              <li><a href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Dashboard</a></li>
              <li><a href="<?php echo hirepeak_url('jobs.php'); ?>">Find Jobs</a></li>
              <li aria-current="page"><?php echo hirepeak_esc($jobTitle !== '' ? $jobTitle : 'Job'); ?></li>
            </ol>
          </nav>
          <a class="jd-back" href="<?php echo hirepeak_url('job-details.php?id=' . $jobId); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 12H5"/>
              <path d="m11 18-6-6 6-6"/>
            </svg>
            Back to Job
          </a>
        </div>

        <section class="empty-state">
          <div class="empty-state__icon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/>
              <path d="M7 12h10"/>
            </svg>
          </div>
          <h1 class="empty-state__title">Applications are closed for this role.</h1>
          <p class="empty-state__text">This job is no longer accepting applications.</p>
          <div class="empty-state__actions">
            <a class="btn btn--primary" href="<?php echo hirepeak_url('job-details.php?id=' . $jobId); ?>">Back to Job</a>
            <a class="btn btn--outline" href="<?php echo hirepeak_url('jobs.php'); ?>">Find Jobs</a>
          </div>
        </section>

      <?php elseif ($state === 'already'): ?>

        <div class="track-topbar">
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
              <li><a href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Dashboard</a></li>
              <li><a href="<?php echo hirepeak_url('jobs.php'); ?>">Find Jobs</a></li>
              <li aria-current="page"><?php echo hirepeak_esc($jobTitle !== '' ? $jobTitle : 'Job'); ?></li>
            </ol>
          </nav>
          <a class="jd-back" href="<?php echo hirepeak_url('job-details.php?id=' . $jobId); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 12H5"/>
              <path d="m11 18-6-6 6-6"/>
            </svg>
            Back to Job
          </a>
        </div>

        <section class="empty-state">
          <div class="empty-state__icon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/>
              <path d="m8 12.5 2.5 2.5 5-6"/>
            </svg>
          </div>
          <h1 class="empty-state__title">You've already applied for this role.</h1>
          <p class="empty-state__text">You can track its progress from My Applications.</p>
          <div class="empty-state__actions">
            <a class="btn btn--primary" href="<?php echo hirepeak_url('applicant/tracking.php?id=' . (int) $existingId); ?>">Track Application</a>
            <a class="btn btn--outline" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">My Applications</a>
          </div>
        </section>

      <?php else: ?>

        <div class="track-topbar">
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
              <li><a href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Dashboard</a></li>
              <li><a href="<?php echo hirepeak_url('jobs.php'); ?>">Find Jobs</a></li>
              <li aria-current="page"><?php echo hirepeak_esc($jobTitle); ?></li>
            </ol>
          </nav>
          <a class="jd-back" href="<?php echo hirepeak_url('job-details.php?id=' . $jobId); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 12H5"/>
              <path d="m11 18-6-6 6-6"/>
            </svg>
            Back to Job
          </a>
        </div>

        <header class="dash-head">
          <div>
            <p class="dash-eyebrow">Apply</p>
            <h1 class="dash-title">Review your application</h1>
            <p class="dash-text">Check your details before submitting.</p>
          </div>
          <a class="btn btn--ghost dash-head__cta" href="<?php echo hirepeak_url('job-details.php?id=' . $jobId); ?>">Back to job</a>
        </header>

        <?php if ($formError !== ''): ?>
          <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($formError); ?></p>
        <?php endif; ?>

        <div class="apply-layout">

          <section class="dash-panel apply-panel" aria-labelledby="apply-job-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="apply-job-title">Job</h2>
            </div>

            <div class="apply-job-head">
              <span class="apply-job-head__logo <?php echo hirepeak_esc(hirepeak_company_avatar_class($companyName)); ?>" aria-hidden="true"><?php echo hirepeak_esc(hirepeak_initials($companyName)); ?></span>
              <div class="apply-job-head__text">
                <h3 class="apply-job-head__title"><?php echo hirepeak_esc($jobTitle); ?></h3>
                <p class="apply-job-head__company">
                  <?php echo hirepeak_esc($companyName); ?>
                  <?php if ($jobLocation !== ''): ?>
                    <span class="job-card__dot" aria-hidden="true">&middot;</span> <?php echo hirepeak_esc($jobLocation); ?>
                  <?php endif; ?>
                </p>
              </div>
            </div>

            <dl class="jd-facts__list">
              <div class="jd-fact"><dt>Salary</dt><dd><?php echo $salary; ?></dd></div>
              <div class="jd-fact"><dt>Job type</dt><dd><?php echo hirepeak_esc(hirepeak_employment_type_label((string) $job['employment_type'])); ?></dd></div>
              <div class="jd-fact"><dt>Work mode</dt><dd><?php echo hirepeak_esc(hirepeak_work_mode_label((string) $job['work_mode'])); ?></dd></div>
              <div class="jd-fact"><dt>Experience</dt><dd><?php echo hirepeak_esc(hirepeak_experience_level_label((string) $job['experience_level'])); ?></dd></div>
              <?php if ($deadlineLabel !== ''): ?>
                <div class="jd-fact"><dt>Deadline</dt><dd><?php echo hirepeak_esc($deadlineLabel); ?></dd></div>
              <?php endif; ?>
              <div class="jd-fact"><dt>Category</dt><dd><?php echo hirepeak_esc((string) ($job['category_name'] ?? '—')); ?></dd></div>
            </dl>
          </section>

          <section class="dash-panel apply-panel" aria-labelledby="apply-details-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="apply-details-title">Your details</h2>
            </div>

            <dl class="jd-facts__list">
              <div class="jd-fact"><dt>Name</dt><dd><?php echo hirepeak_esc($name); ?></dd></div>
              <div class="jd-fact"><dt>Email</dt><dd><?php echo hirepeak_esc($email); ?></dd></div>
              <?php foreach ($profileRows as $label => $value): ?>
                <div class="jd-fact">
                  <dt><?php echo hirepeak_esc($label); ?></dt>
                  <dd><?php echo $value; ?></dd>
                </div>
              <?php endforeach; ?>
              <?php if ($phoneHint): ?>
                <div class="jd-fact"><dt></dt><dd class="apply-details__note">Phone is stored encrypted for your privacy.</dd></div>
              <?php endif; ?>
            </dl>

            <?php if ($bio !== ''): ?>
              <p class="apply-details__label">Bio</p>
              <p class="apply-bio"><?php echo nl2br(hirepeak_esc($bio)); ?></p>
            <?php endif; ?>

            <p class="apply-completeness">
              Your profile is <strong><?php echo (int) $completeness['percent']; ?>%</strong> complete.
              <a href="<?php echo hirepeak_url('applicant/profile.php'); ?>">Update Profile</a>
            </p>
          </section>

        </div>

        <form class="apply-submit" action="<?php echo hirepeak_url('apply.php?id=' . $jobId); ?>" method="post" data-apply-form>
          <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">

          <section class="dash-panel apply-submit__panel" aria-labelledby="apply-submit-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="apply-submit-title">Ready to submit?</h2>
            </div>
            <p class="apply-submit__text">
              Your application will be sent to <?php echo hirepeak_esc($companyName); ?> for <?php echo hirepeak_esc($jobTitle); ?>.
              You can track its progress right after submitting.
            </p>
            <div class="apply-actions">
              <a class="btn btn--ghost" href="<?php echo hirepeak_url('job-details.php?id=' . $jobId); ?>" data-apply-cancel>Back to job</a>
              <button class="btn btn--primary" type="submit" data-apply-submit>Submit application</button>
            </div>
          </section>
        </form>

      <?php endif; ?>

    </div>
  </main>

  <?php require dirname(__DIR__) . '/app/Views/footer.php'; ?>
</body>
</html>