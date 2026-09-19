<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';
require dirname(__DIR__, 2) . '/app/Support/job-form.php';

$user = hirepeak_require_role('employer');
$uid = (int) $user['id'];

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];

$errors = [];
$formError = '';
$values = hirepeak_job_field_defaults();
$company = null;
$showBlocker = false;
$loadError = false;

try {
    $db = hirepeak_db();

    $stmt = $db->prepare('SELECT id FROM companies WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if ($company === null) {
        $showBlocker = true;
    } else {
        $today = hirepeak_today_sql($db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values = hirepeak_job_clean_input();
            $publish = (string) ($_POST['publish'] ?? '');

            if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
                $formError = 'Your session expired. Please try again.';
                hirepeak_csrf_token(true);
            } else {
                $result = hirepeak_job_validate($values, $publish, $db, $today);
                $errors = $result['errors'];
                $clean = $result['values'];

                if (!$errors) {
                    $companyId = (int) $company['id'];
                    $stmt = $db->prepare(
                        'INSERT INTO jobs
                           (company_id, employer_id, category_id, title, description,
                            requirements, location, salary_min, salary_max,
                            employment_type, work_mode, experience_level,
                            application_deadline, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->bind_param(
                        'iiisssssssssss',
                        $companyId,
                        $uid,
                        $clean['category_id'],
                        $clean['title'],
                        $clean['description'],
                        $clean['requirements'],
                        $clean['location'],
                        $clean['salary_min'],
                        $clean['salary_max'],
                        $clean['employment_type'],
                        $clean['work_mode'],
                        $clean['experience_level'],
                        $clean['application_deadline'],
                        $publish
                    );
                    $stmt->execute();
                    $stmt->close();

                    $_SESSION['job_flash'] = $publish === 'active'
                        ? 'Job published successfully.'
                        : 'Draft saved successfully.';
                    header('Location: ' . hirepeak_url('employer/my-jobs.php?notice=1'));
                    exit;
                }
            }
        }
    }
} catch (\Throwable $e) {
    error_log('[hirepeak] post job load/save failed: ' . $e->getMessage());
    $loadError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post a Job — HirePeak</title>
  <meta name="description" content="Create and publish a job listing on HirePeak.">
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

  <main id="main" class="dash-main">
    <div class="container">

      <header class="dash-head dash-head--job-editor">
        <div>
          <p class="dash-eyebrow">Employer account</p>
          <h1 class="dash-title">Post a Job</h1>
          <p class="dash-text">Create a listing for your next hire. Save it as a draft or publish it right away.</p>
        </div>
        <a class="btn btn--ghost dash-head__cta" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">My Jobs</a>
      </header>

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="post-error-title">
          <h2 id="post-error-title">We couldn't load the job form right now.</h2>
          <p>Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/post-job.php'); ?>">Try again</a>
        </section>

      <?php elseif ($showBlocker): ?>

        <section class="empty-state">
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
          <h1 class="empty-state__title">Complete your company profile first.</h1>
          <p class="empty-state__text">Candidates need to know who is hiring before you publish a role.</p>
          <div class="empty-state__actions">
            <a class="btn btn--primary" href="<?php echo hirepeak_url('employer/company-profile.php'); ?>">Complete Company Profile</a>
            <a class="btn btn--outline" href="<?php echo hirepeak_url('employer/dashboard.php'); ?>">Back to Dashboard</a>
          </div>
        </section>

      <?php else: ?>

        <?php if ($formError !== ''): ?>
          <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($formError); ?></p>
        <?php endif; ?>

        <?php
        echo hirepeak_job_form_render([
            'mode'          => 'post',
            'action'        => 'employer/post-job.php',
            'csrf'          => hirepeak_csrf_token(),
            'id'            => 0,
            'values'        => $values,
            'errors'        => $errors,
            'categories'    => hirepeak_categories($db),
            'currentStatus' => '',
        ]);
        ?>

      <?php endif; ?>

    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>