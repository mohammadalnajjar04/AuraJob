<?php
declare(strict_types=1);

/**
 * Employer Application — detail for a single application (with the applicant's
 * profile) on one of your jobs (Part 3A).
 *
 * Status updates (Part 3B) are handled here via the shared helpers in
 * app/Support/applies-form.php: the page is POST-only + CSRF-protected, and all
 * status rules (whitelisted transitions, ownership scoping via jobs.employer_id,
 * stale/current-status guard, terminal accepted/rejected) live in that helper,
 * never duplicated in this page. A successful update sets a one-time flash and
 * 303-redirects back to this same page (PRG), so a refresh cannot repeat it.
 *
 * This page only ever renders an application that is scoped (via JOIN on
 * jobs.employer_id) to a job the signed-in employer actually owns, so a foreign
 * or nonexistent application id resolves to a safe not-found page and no
 * cross-owner applicant data can leak.
 */

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';
require dirname(__DIR__, 2) . '/app/Support/applies-form.php';
require dirname(__DIR__, 2) . '/app/Services/crypto.php';

$user = hirepeak_require_role('employer');
$uid  = (int) $user['id'];

$appId = (int) ($_GET['id'] ?? 0);
$appId = $appId > 0 ? $appId : 0;

$db         = null;
$loadError  = false;
$application = null;
$flash      = '';
$error      = '';

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT a.id, a.job_id, a.status, a.applied_at, a.cover_letter,
                j.title AS job_title,
                j.location AS job_location,
                u.name AS applicant_name,
                u.email AS applicant_email,
                ap.professional_title,
                ap.bio,
                ap.phone,
                ap.location AS applicant_location,
                ap.experience_years,
                ap.skills,
                ap.cv_path
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN users u ON u.id = a.applicant_id
         LEFT JOIN applicant_profiles ap ON ap.user_id = a.applicant_id
         WHERE a.id = ? AND j.employer_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $appId, $uid);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if ($application === null) {
        hirepeak_job_not_found();
    }

    /* PRG flashes from the status-update POST handler below. */
    if (isset($_GET['updated'], $_SESSION['app_status_flash']) && $_SESSION['app_status_flash'] !== '') {
        $flash = (string) $_SESSION['app_status_flash'];
        unset($_SESSION['app_status_flash']);
    }
    if (isset($_GET['status_error'], $_SESSION['app_status_error']) && $_SESSION['app_status_error'] !== '') {
        $error = (string) $_SESSION['app_status_error'];
        unset($_SESSION['app_status_error']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $update = hirepeak_process_application_status_update($db, $appId, $uid);

        if (($update['ok'] ?? false) === true) {
            $_SESSION['app_status_flash'] = $update['flash'] ?? 'Application status updated.';
            header('Location: ' . hirepeak_url('employer/application.php?id=' . $appId . '&updated=1'), true, 303);
            exit;
        }

        $errors = $update['errors'] ?? [];
        $_SESSION['app_status_error'] = $errors !== [] ? $errors[0] : 'Could not update this application.';
        header('Location: ' . hirepeak_url('employer/application.php?id=' . $appId . '&status_error=1'), true, 303);
        exit;
    }

    $phone = null;
    $phoneRaw = (string) ($application['phone'] ?? '');
    if ($phoneRaw !== '') {
        $decrypted = hirepeak_phone_decrypt($phoneRaw);
        if ($decrypted !== null) {
            $phone = trim($decrypted);
        }
        /* Never echo ciphertext: if it can't be decrypted, we simply omit it. */
    }
} catch (\Throwable $e) {
    error_log('[hirepeak] employer application load failed: ' . $e->getMessage());
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
  <title><?php echo $application !== null && trim((string) $application['job_title']) !== '' ? hirepeak_esc((string) $application['applicant_name']) . ' — Application | HirePeak' : 'Application | HirePeak'; ?></title>
  <meta name="description" content="Review this candidate's full application on HirePeak.">
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

  <main id="main" class="dash-main employer-application-page">
    <div class="container">

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="application-error-title">
          <h1 id="application-error-title">Something went wrong on our side.</h1>
          <p>We couldn't load this application right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/applicants.php?job_id=' . (int) $application['job_id']); ?>">Back to Applicants</a>
        </section>

      <?php else: ?>

        <?php
        $jobId     = (int) $application['job_id'];
        $status    = (string) $application['status'];
        $statusLabel = hirepeak_application_status_label($status);
        $nextStatuses = hirepeak_application_transitions()[$status] ?? [];
        $transitionLabel = [
            'under_review' => 'Move to Under Review',
            'interview'    => 'Move to Interview',
            'accepted'     => 'Accept Application',
            'rejected'     => 'Reject Application',
        ];
        $phone     = null;
        $phoneRaw  = (string) ($application['phone'] ?? '');
        if ($phoneRaw !== '') {
            $decrypted = hirepeak_phone_decrypt($phoneRaw);
            if ($decrypted !== null) {
                $phone = trim($decrypted);
            }
        }
        ?>

        <header class="dash-head dash-head--application-review">
          <div>
            <p class="dash-eyebrow">Employer account</p>
            <h1 class="dash-title"><?php echo hirepeak_esc((string) $application['applicant_name']); ?></h1>
            <p class="dash-text">Application for
              <a class="job-inline-link" href="<?php echo hirepeak_url('job-details.php?id=' . (int) $jobId); ?>"><?php echo hirepeak_esc((string) $application['job_title']); ?></a>
              <?php if (trim((string) ($application['job_location'] ?? '')) !== ''): ?>
                &middot; <?php echo hirepeak_esc((string) $application['job_location']); ?>
              <?php endif; ?>
            </p>
          </div>
          <div class="dash-head__cta-wrap">
            <a class="btn btn--ghost" href="<?php echo hirepeak_url('employer/applicants.php?job_id=' . (int) $jobId); ?>">Back to Applicants</a>
          </div>
        </header>

        <?php if ($flash !== ''): ?>
          <p class="dash-flash" role="status"><?php echo hirepeak_esc($flash); ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($error); ?></p>
        <?php endif; ?>

        <section class="dash-panel dash-panel--full application-status-panel" aria-labelledby="application-status-title">
          <div class="panel-head">
            <h2 class="panel-head__title" id="application-status-title">Application Status</h2>
          </div>
          <div class="application-status-panel__body">
            <div class="application-status-panel__current">
              <span class="application-status-panel__label">Current status</span>
              <?php echo hirepeak_application_status_badge($status); ?>
            </div>
            <?php if ($nextStatuses !== []): ?>
              <div class="application-status-panel__actions" aria-label="Available status actions">
                <?php foreach ($nextStatuses as $nextStatus): ?>
                  <form class="inline-form" action="<?php echo hirepeak_url('employer/application.php?id=' . (int) $appId); ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">
                    <input type="hidden" name="status" value="<?php echo hirepeak_esc((string) $nextStatus); ?>">
                    <button class="btn <?php echo $nextStatus === 'rejected' ? 'btn--danger' : 'btn--primary'; ?>" type="submit">
                      <?php echo hirepeak_esc((string) ($transitionLabel[$nextStatus] ?? hirepeak_application_status_label((string) $nextStatus))); ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="application-status-panel__final"><?php echo hirepeak_esc($statusLabel); ?> is a final status — no further status changes are available.</p>
            <?php endif; ?>
          </div>
        </section>

        <div class="jd-layout employer-application-layout">

          <section class="dash-panel jd-main employer-application-card" aria-labelledby="application-overview-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="application-overview-title">Application</h2>
            </div>

            <p class="application-overview__meta">
              <span class="app-row__date">Applied <?php echo hirepeak_esc(hirepeak_relative_time((string) $application['applied_at'])); ?></span>
            </p>

            <dl class="jd-facts__list">
              <?php if ($statusLabel !== ''): ?>
                <div class="jd-fact"><dt>Status</dt><dd><?php echo hirepeak_esc((string) $statusLabel); ?></dd></div>
              <?php endif; ?>
              <div class="jd-fact"><dt>Applied</dt><dd><?php echo hirepeak_esc(hirepeak_relative_time((string) $application['applied_at'])); ?></dd></div>
              <div class="jd-fact"><dt>Job</dt><dd><a href="<?php echo hirepeak_url('job-details.php?id=' . (int) $jobId); ?>"><?php echo hirepeak_esc((string) $application['job_title']); ?></a></dd></div>
            </dl>

            <?php if (trim((string) ($application['cover_letter'] ?? '')) !== ''): ?>
              <div class="jd-prose">
                <h2>Cover letter</h2>
                <p class="apply-details__note"><?php echo hirepeak_esc($application['cover_letter']); ?></p>
              </div>
            <?php endif; ?>
          </section>

          <aside class="dash-panel jd-aside employer-applicant-card" aria-labelledby="applicant-details-title">
            <div class="panel-head">
              <h2 class="panel-head__title" id="applicant-details-title">Applicant</h2>
            </div>

            <p class="app-row__meta">
              <?php echo hirepeak_esc((string) ($application['professional_title'] ?? '')); ?>
              <?php if (trim((string) ($application['applicant_location'] ?? '')) !== ''): ?>
                <span class="row-dot" aria-hidden="true">&middot;</span>
                <?php echo hirepeak_esc((string) $application['applicant_location']); ?>
              <?php endif; ?>
              <?php if ((int) ($application['experience_years'] ?? 0) > 0): ?>
                <span class="row-dot" aria-hidden="true">&middot;</span>
                <?php echo (int) $application['experience_years']; ?> yrs exp
              <?php endif; ?>
            </p>

            <dl class="jd-facts__list">
              <div class="jd-fact"><dt>Name</dt><dd><?php echo hirepeak_esc((string) $application['applicant_name']); ?></dd></div>
              <div class="jd-fact"><dt>Email</dt><dd><a href="mailto:<?php echo hirepeak_esc((string) $application['applicant_email']); ?>"><?php echo hirepeak_esc((string) $application['applicant_email']); ?></a></dd></div>
              <?php if ($phone !== null && $phone !== ''): ?>
                <div class="jd-fact"><dt>Phone</dt><dd><a href="tel:<?php echo hirepeak_esc($phone); ?>"><?php echo hirepeak_esc($phone); ?></a></dd></div>
              <?php endif; ?>
              <?php if (trim((string) ($application['skills'] ?? '')) !== ''): ?>
                <div class="jd-fact"><dt>Skills</dt><dd><?php echo hirepeak_esc((string) $application['skills']); ?></dd></div>
              <?php endif; ?>
            </dl>

            <?php if (trim((string) ($application['bio'] ?? '')) !== ''): ?>
              <div class="jd-prose">
                <h2>About the applicant</h2>
                <p class="apply-details__note"><?php echo hirepeak_esc((string) $application['bio']); ?></p>
              </div>
            <?php endif; ?>
          </aside>

        </div>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>
