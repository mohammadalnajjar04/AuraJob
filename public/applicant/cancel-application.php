<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';

$user = hirepeak_require_role('applicant');
$uid = (int) $user['id'];

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];

$rawId = (string) ($_GET['id'] ?? '');
if ($rawId === '' || preg_match('/^[1-9][0-9]*$/D', $rawId) !== 1) {
    http_response_code(404);
    $application = null;
} else {
    $applicationId = (int) $rawId;
    $application = null;
}

$formError = '';
$cancellableStatuses = ['applied', 'under_review', 'interview'];

try {
    if (isset($applicationId)) {
        $db = hirepeak_db();
        $stmt = $db->prepare(
            'SELECT a.id, a.status, a.applied_at,
                    j.id AS job_id, j.title AS job_title,
                    c.name AS company_name
             FROM applications a
             INNER JOIN jobs j ON j.id = a.job_id
             INNER JOIN companies c ON c.id = j.company_id
             WHERE a.id = ? AND a.applicant_id = ?
             LIMIT 1'
        );
        $stmt->bind_param('ii', $applicationId, $uid);
        $stmt->execute();
        $application = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();

        if ($application === null) {
            http_response_code(404);
        } elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
                $formError = 'Your session expired. Please try again.';
                hirepeak_csrf_token(true);
            } elseif (!in_array((string) $application['status'], $cancellableStatuses, true)) {
                $formError = 'This application can no longer be cancelled.';
            } else {
                $stmt = $db->prepare(
                    "DELETE FROM applications
                     WHERE id = ? AND applicant_id = ?
                       AND status IN ('applied','under_review','interview')"
                );
                $stmt->bind_param('ii', $applicationId, $uid);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();

                if ($affected !== 1) {
                    $formError = 'This application changed before it could be cancelled. Please review it again.';
                } else {
                    $_SESSION['application_flash'] = 'Application cancelled successfully.';
                    header('Location: ' . hirepeak_url('applicant/applications.php?notice=1'));
                    exit;
                }
            }
        }
    }
} catch (\Throwable $e) {
    error_log('[hirepeak] cancel application failed: ' . $e->getMessage());
    $formError = 'Something went wrong cancelling this application. Please try again.';
}

$notFound = $application === null;
if ($notFound) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $notFound ? 'Application not found' : 'Cancel Application'; ?> — AuraJob</title>
  <meta name="description" content="Cancel an AuraJob job application.">
  <meta name="theme-color" content="#ffffff">
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
      <?php if ($notFound): ?>
        <section class="empty-state">
          <h1 class="empty-state__title">Application not found</h1>
          <p class="empty-state__text">We couldn't find that application, or it doesn't belong to your account.</p>
          <div class="empty-state__actions">
            <a class="btn btn--primary" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">Back to My Applications</a>
          </div>
        </section>
      <?php else: ?>
        <?php
        $status = (string) $application['status'];
        $canCancel = in_array($status, $cancellableStatuses, true);
        ?>
        <header class="dash-head">
          <div>
            <p class="dash-eyebrow">Applicant</p>
            <h1 class="dash-title">Cancel Application</h1>
            <p class="dash-text"><?php echo hirepeak_esc((string) $application['job_title']); ?> at <?php echo hirepeak_esc((string) $application['company_name']); ?></p>
          </div>
          <a class="btn btn--ghost dash-head__cta" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">My Applications</a>
        </header>

        <?php if ($formError !== ''): ?>
          <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($formError); ?></p>
        <?php endif; ?>

        <?php if (!$canCancel): ?>
          <section class="empty-state">
            <h2 class="empty-state__title">This application can no longer be cancelled.</h2>
            <p class="empty-state__text">Applications that are already accepted or rejected are final and remain in your history.</p>
            <div class="empty-state__actions">
              <a class="btn btn--primary" href="<?php echo hirepeak_url('applicant/tracking.php?id=' . (int) $application['id']); ?>">Track Application</a>
              <a class="btn btn--outline" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">Back to My Applications</a>
            </div>
          </section>
        <?php else: ?>
          <section class="dash-panel dash-panel--full" aria-labelledby="cancel-application-title">
            <h2 class="panel-head__title" id="cancel-application-title">Cancel this application?</h2>
            <p class="close-job__text">This permanently removes your application for <strong><?php echo hirepeak_esc((string) $application['job_title']); ?></strong>.</p>
            <p class="close-job__text">The employer will no longer see it in their applicant list. This action cannot be undone.</p>
            <div class="profile-form__actions">
              <a class="btn btn--ghost" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">Keep Application</a>
              <form class="inline-form" action="<?php echo hirepeak_url('applicant/cancel-application.php?id=' . (int) $application['id']); ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">
                <button class="btn btn--danger" type="submit">Cancel Application</button>
              </form>
            </div>
          </section>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>
