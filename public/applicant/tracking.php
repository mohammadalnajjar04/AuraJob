<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';

$user = hirepeak_require_role('applicant');
$uid = (int) $user['id'];

/*
 * Application Tracking — read-only detail view for one of the applicant's own
 * applications. Ownership is enforced entirely in SQL (WHERE a.id = ? AND
 * a.applicant_id = ?), so a foreign application id produces the exact same
 * 404 as a nonexistent one: no cross-account information is ever leaked.
 */

/* One-time PRG flash from apply.php (consumed on first render). */
$flash = '';
if (isset($_SESSION['hirepeak_apply_flash']) && $_SESSION['hirepeak_apply_flash'] !== '') {
    $flash = (string) $_SESSION['hirepeak_apply_flash'];
    unset($_SESSION['hirepeak_apply_flash']);
}

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];

$rawId = (string) ($_GET['id'] ?? '');

/* Wholestring guard: only plain positive integers reach the database. Anything
 * else (empty, abc, -1, fractions, trailing junk, SQL injection shapes) is a
 * clean 404 without any query being run. */
if ($rawId === '' || preg_match('/^[1-9][0-9]*$/D', $rawId) !== 1) {
    hirepeak_tracking_not_found();
}
$applicationId = (int) $rawId;

$application = null;
$loadError = false;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT a.id AS application_id, a.status, a.applied_at, a.updated_at,
                j.id AS job_id, j.title AS job_title, j.location AS job_location,
                j.salary_min, j.salary_max, j.employment_type, j.work_mode,
                j.experience_level, j.status AS job_status, j.application_deadline,
                c.name AS company_name, ct.name AS category_name
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN companies c ON c.id = j.company_id
         INNER JOIN categories ct ON ct.id = j.category_id
         WHERE a.id = ? AND a.applicant_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $applicationId, $uid);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (\Throwable $e) {
    error_log('[hirepeak] application tracking load failed: ' . $e->getMessage());
    $loadError = true;
    $application = null;
}

if (!$loadError && $application === null) {
    hirepeak_tracking_not_found();
}

/*
 * Deterministic timeline based on the single stored status.
 *
 * The stored status is interpreted as "this stage has been reached": reaching
 * a later stage implies every earlier stage also happened (the schema stores
 * no per-stage history, so no intermediate dates are invented). Applied is
 * always the completed start. Rejected is a calm terminal branch rendered
 * outside the happy path — it never claims Under Review/Interview took place
 * because that information does not exist in the database.
 */

$status = (string) ($application['status'] ?? '');

$order = ['applied', 'under_review', 'interview', 'accepted'];

$stepCopy = [
    'applied' => [
        'label'    => 'Applied',
        'done'     => 'Your application was submitted.',
        'current'  => 'Your application is with the employer.',
        'upcoming' => '',
    ],
    'under_review' => [
        'label'    => 'Under Review',
        'done'     => 'The employer reviewed your application.',
        'current'  => 'The employer is reviewing your application.',
        'upcoming' => 'If the employer reviews your application, this step updates.',
    ],
    'interview' => [
        'label'    => 'Interview',
        'done'     => 'The employer invited you to an interview.',
        'current'  => 'The employer invited you to an interview.',
        'upcoming' => 'An interview invitation may come next.',
    ],
    'accepted' => [
        'label'    => 'Accepted',
        'done'     => 'The employer accepted your application.',
        'current'  => 'The employer accepted your application.',
        'upcoming' => 'A decision will appear here when the employer acts.',
    ],
];

$timelineSteps = [];
$isRejected = $status === 'rejected';

if ($isRejected) {
    $timelineSteps[] = [
        'key'     => 'applied',
        'label'   => $stepCopy['applied']['label'],
        'state'   => 'done',
        'hint'    => $stepCopy['applied']['done'],
        'current' => false,
    ];
    $timelineSteps[] = [
        'key'     => 'terminal',
        'label'   => 'Application closed',
        'state'   => 'terminal',
        'hint'    => 'This application is no longer moving forward.',
        'current' => true,
    ];
} else {
    /* accepted is the final, successful outcome: every stage is completed. */
    $position = $status === 'accepted'
        ? count($order)
        : array_search($status, $order, true);
    $position = $position === false ? 0 : $position;

    foreach ($order as $index => $key) {
        $state = 'upcoming';
        if ($index < $position) {
            $state = 'done';
        } elseif ($index === $position) {
            $state = 'current';
        }
        $timelineSteps[] = [
            'key'     => $key,
            'label'   => $stepCopy[$key]['label'],
            'state'   => $state,
            'hint'    => $stepCopy[$key][$state],
            'current' => $index === $position,
        ];
    }
}

$stateLabel = [
    'done'     => 'Completed',
    'current'  => 'Current',
    'upcoming' => 'Upcoming',
    'terminal' => 'Closed',
];

function hirepeak_track_state_icon(string $state): string
{
    return match ($state) {
        'done'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
        'current'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="6"/></svg>',
        'upcoming' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="7.5"/></svg>',
        default    => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4"/></svg>',
    };
}

function hirepeak_track_icon_classes(array $step): string
{
    $classes = ['track-step__icon', 'track-step__icon--' . $step['state']];
    if ($step['state'] === 'current' && isset($step['key']) && $step['key'] !== 'terminal') {
        $classes[] = 'track-step__icon--' . $step['key'];
    }
    return implode(' ', $classes);
}

function hirepeak_track_formatted_date(?string $timestamp): string
{
    $time = $timestamp !== null ? strtotime($timestamp) : false;
    return $time ? date('M j, Y', $time) : '';
}

/*
 * Calm 404 for any missing / malformed / foreign / nonexistent application id.
 */
function hirepeak_tracking_not_found(): void
{
    $pageActive = '';
    $pageScripts = ['/assets/js/main.js'];
    http_response_code(404);
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application not found — AuraJob</title>
  <meta name="description" content="We couldn't find this application.">
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
      <section class="empty-state">
        <div class="empty-state__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <path d="m15 9-6 6"/>
            <path d="m9 9 6 6"/>
          </svg>
        </div>
        <h1 class="empty-state__title">Application not found</h1>
        <p class="empty-state__text">We couldn't find this application. It may have been removed, or the link may be incorrect.</p>
        <div class="empty-state__actions">
          <a class="btn btn--primary" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">Back to My Applications</a>
          <a class="btn btn--outline" href="<?php echo hirepeak_url('jobs.php'); ?>">Find Jobs</a>
        </div>
      </section>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html><?php
    exit;
}

$jobId = (int) $application['job_id'];
$companyName = (string) $application['company_name'];
$jobTitle = (string) $application['job_title'];
$jobLocation = (string) $application['job_location'];
$jobClosed = ($application['job_status'] ?? '') !== 'active';
$appliedLabel = hirepeak_track_formatted_date($application['applied_at'] ?? null);
$salary = hirepeak_salary_range(
    isset($application['salary_min']) ? (string) $application['salary_min'] : '',
    isset($application['salary_max']) ? (string) $application['salary_max'] : ''
);

$nextStep = match ($status) {
    'applied' => [
        'heading' => 'What happens next?',
        'body'    => 'Your application is with the employer. Nothing is needed from you right now — the next step is the employer reviewing your application.',
        'action'  => 'Keep an eye on this page for updates.',
    ],
    'under_review' => [
        'heading' => 'What happens next?',
        'body'    => 'The employer is reviewing your application. If they\'d like to move forward, they\'ll invite you to an interview and this page will update.',
        'action'  => 'No action is needed from you right now.',
    ],
    'interview' => [
        'heading' => 'What happens next?',
        'body'    => 'The employer has invited you to the interview stage. Interview details or further updates will appear here, or come directly from the employer.',
        'action'  => 'Keep an eye on your inbox and this page.',
    ],
    'accepted' => [
        'heading' => 'Application successful',
        'body'    => 'Your application was successful. Any offer letter or further instructions will come directly from the employer.',
        'action'  => 'Revisit this page if you need a summary of the application.',
    ],
    default => [
        'heading' => 'Application closed',
        'body'    => 'This application is no longer moving forward. You can keep exploring other roles that fit you.',
        'action'  => '',
    ],
};

$showSalary = $salary !== 'Salary not disclosed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Tracking — AuraJob</title>
  <meta name="description" content="Track the progress of your <?php echo hirepeak_esc($jobTitle); ?> application with AuraJob.">
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

      <div class="track-topbar">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <ol>
            <li><a href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Dashboard</a></li>
            <li><a href="<?php echo hirepeak_url('applicant/applications.php'); ?>">My Applications</a></li>
            <li aria-current="page">Application Tracking</li>
          </ol>
        </nav>
        <a class="jd-back" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5"/>
            <path d="m11 18-6-6 6-6"/>
          </svg>
          Back to My Applications
        </a>
      </div>

      <?php if ($flash !== ''): ?>
        <p class="dash-flash" role="status"><?php echo hirepeak_esc($flash); ?></p>
      <?php endif; ?>

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="track-error-title">
          <h1 id="track-error-title">Something went wrong on our side.</h1>
          <p>We couldn't load this application right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">Back to My Applications</a>
        </section>
      <?php else: ?>

        <header class="dash-head">
          <div>
            <p class="dash-eyebrow">Applicant</p>
            <h1 class="dash-title">Application Tracking</h1>
            <p class="dash-text">Follow where this application stands.</p>
          </div>
          <a class="btn btn--primary dash-head__cta" href="<?php echo hirepeak_url('jobs.php'); ?>">
            Find Jobs
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </a>
        </header>

        <article class="track-card track-hero">
          <div class="track-hero__identity">
            <span class="track-hero__logo <?php echo hirepeak_esc(hirepeak_company_avatar_class($companyName)); ?>" aria-hidden="true"><?php echo hirepeak_esc(hirepeak_initials($companyName)); ?></span>
            <div class="track-hero__text">
              <h2 class="track-hero__title"><?php echo hirepeak_esc($jobTitle); ?></h2>
              <p class="track-hero__company">
                <?php echo hirepeak_esc($companyName); ?>
                <?php if (trim($jobLocation) !== ''): ?>
                  <span class="job-card__dot" aria-hidden="true">&middot;</span> <?php echo hirepeak_esc($jobLocation); ?>
                <?php endif; ?>
              </p>
              <ul class="track-hero__chips">
                <li class="chip"><?php echo hirepeak_esc(hirepeak_employment_type_label((string) $application['employment_type'])); ?></li>
                <li class="chip"><?php echo hirepeak_esc(hirepeak_work_mode_label((string) $application['work_mode'])); ?></li>
                <?php if ($jobClosed): ?>
                  <li class="chip chip--closed">Closed</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>

          <div class="track-hero__status">
            <?php echo hirepeak_application_status_badge($status); ?>
            <?php if ($appliedLabel !== ''): ?>
              <p class="track-hero__applied">Applied <?php echo hirepeak_esc($appliedLabel); ?></p>
            <?php endif; ?>
            <a class="btn btn--outline btn--sm" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $jobId); ?>">View Job</a>
          </div>
        </article>

        <?php if ($jobClosed): ?>
          <p class="track-closed-note">This job is no longer accepting applications.</p>
        <?php endif; ?>

        <section class="dash-panel dash-panel--full" aria-labelledby="track-progress-title">
          <div class="panel-head">
            <h2 class="panel-head__title" id="track-progress-title">Application progress</h2>
            <?php if (!$isRejected): ?>
              <p class="panel-head__note">Each stage reflects your stored application status. Stages ahead show what could come next if the employer moves forward.</p>
            <?php else: ?>
              <p class="panel-head__note">This application is closed. The outcome is final and this page won't change further.</p>
            <?php endif; ?>
          </div>

          <ol class="track-steps">
            <?php foreach ($timelineSteps as $step): ?>
              <?php
              $stepKey = $step['key'];
              $stepState = $step['state'];
              $classes = ['track-step', 'track-step--' . $stepState];
              if ($stepState === 'current' && $stepKey !== 'terminal') {
                  $classes[] = 'track-step--stage-' . $stepKey;
              }
              ?>
              <li class="<?php echo implode(' ', $classes); ?>"<?php echo $step['current'] ? ' aria-current="step"' : ''; ?>>
                <span class="<?php echo hirepeak_track_icon_classes($step); ?>"><?php echo hirepeak_track_state_icon($stepState); ?></span>
                <div class="track-step__body">
                  <p class="track-step__label"><?php echo hirepeak_esc($step['label']); ?></p>
                  <p class="track-step__state" aria-hidden="true"><?php echo hirepeak_esc($stateLabel[$stepState]); ?></p>
                  <?php if (($step['hint'] ?? '') !== ''): ?>
                    <p class="track-step__hint"><?php echo hirepeak_esc($step['hint']); ?></p>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        </section>

        <div class="track-grid">
          <section class="dash-panel track-summary" aria-labelledby="track-summary-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="track-summary-title">Application summary</h2>
            </div>
            <dl class="jd-facts__list">
              <div class="jd-fact">
                <dt>Status</dt>
                <dd><?php echo hirepeak_esc(hirepeak_application_status_label($status)); ?></dd>
              </div>
              <div class="jd-fact">
                <dt>Applied</dt>
                <dd><?php echo hirepeak_esc($appliedLabel !== '' ? $appliedLabel : '—'); ?></dd>
              </div>
              <div class="jd-fact">
                <dt>Job type</dt>
                <dd><?php echo hirepeak_esc(hirepeak_employment_type_label((string) $application['employment_type'])); ?></dd>
              </div>
              <div class="jd-fact">
                <dt>Work mode</dt>
                <dd><?php echo hirepeak_esc(hirepeak_work_mode_label((string) $application['work_mode'])); ?></dd>
              </div>
              <div class="jd-fact">
                <dt>Location</dt>
                <dd><?php echo hirepeak_esc(trim($jobLocation) !== '' ? $jobLocation : '—'); ?></dd>
              </div>
              <div class="jd-fact">
                <dt>Category</dt>
                <dd><?php echo hirepeak_esc((string) ($application['category_name'] ?? '—')); ?></dd>
              </div>
              <?php if ($showSalary): ?>
                <div class="jd-fact">
                  <dt>Salary</dt>
                  <dd><?php echo $salary; ?></dd>
                </div>
              <?php endif; ?>
            </dl>
          </section>

          <section class="dash-panel track-next" aria-labelledby="track-next-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="track-next-title"><?php echo hirepeak_esc($nextStep['heading']); ?></h2>
            </div>
            <p class="track-next__body"><?php echo hirepeak_esc($nextStep['body']); ?></p>
            <?php if ($status === 'rejected'): ?>
              <div class="track-next__actions">
                <a class="btn btn--primary" href="<?php echo hirepeak_url('jobs.php'); ?>">Explore Jobs</a>
                <a class="btn btn--outline" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">Back to My Applications</a>
              </div>
            <?php else: ?>
              <?php if (($nextStep['action'] ?? '') !== ''): ?>
                <p class="track-next__hint"><?php echo hirepeak_esc($nextStep['action']); ?></p>
              <?php endif; ?>
            <?php endif; ?>
          </section>
        </div>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>