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

$rawId = (string) ($_GET['id'] ?? '');
if ($rawId === '' || preg_match('/^[1-9][0-9]*$/D', $rawId) !== 1) {
    hirepeak_job_not_found();
}
$jobId = (int) $rawId;

$errors = [];
$formError = '';
$flash = '';
$currentStatus = '';

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        'SELECT id, title, category_id, location, description, requirements,
                salary_min, salary_max, employment_type, work_mode,
                experience_level, application_deadline, status, created_at
         FROM jobs
         WHERE id = ? AND employer_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $jobId, $uid);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($job === null) {
        hirepeak_job_not_found();
    }

    $currentStatus = (string) $job['status'];

    if (isset($_GET['notice'], $_SESSION['job_flash']) && $_SESSION['job_flash'] !== '') {
        $flash = (string) $_SESSION['job_flash'];
        unset($_SESSION['job_flash']);
    }

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
                $stmt = $db->prepare(
                    'UPDATE jobs
                     SET title = ?, category_id = ?, location = ?, description = ?,
                         requirements = ?, salary_min = ?, salary_max = ?,
                         employment_type = ?, work_mode = ?, experience_level = ?,
                         application_deadline = ?, status = ?
                     WHERE id = ? AND employer_id = ?'
                );
                $stmt->bind_param(
                    'sissssssssssii',
                    $clean['title'],
                    $clean['category_id'],
                    $clean['location'],
                    $clean['description'],
                    $clean['requirements'],
                    $clean['salary_min'],
                    $clean['salary_max'],
                    $clean['employment_type'],
                    $clean['work_mode'],
                    $clean['experience_level'],
                    $clean['application_deadline'],
                    $publish,
                    $jobId,
                    $uid
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
        $job = $values; // re-render with submitted values on validation failure
    } else {
        $job = [
            'title'                => (string) $job['title'],
            'category_id'          => (string) $job['category_id'],
            'location'             => (string) $job['location'],
            'description'          => (string) $job['description'],
            'requirements'         => (string) ($job['requirements'] ?? ''),
            'salary_min'           => (string) ($job['salary_min'] ?? ''),
            'salary_max'           => (string) ($job['salary_max'] ?? ''),
            'employment_type'      => (string) $job['employment_type'],
            'work_mode'            => (string) $job['work_mode'],
            'experience_level'     => (string) $job['experience_level'],
            'application_deadline' => (string) ($job['application_deadline'] ?? ''),
            'status'               => (string) $job['status'],
            'created_at'           => (string) ($job['created_at'] ?? ''),
        ];
    }
} catch (\Throwable $e) {
    error_log('[hirepeak] edit job load/save failed: ' . $e->getMessage());
    hirepeak_job_not_found();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Job — HirePeak</title>
  <meta name="description" content="Update your job listing on HirePeak.">
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

      <header class="dash-head">
        <div>
          <p class="dash-eyebrow">Employer account</p>
          <h1 class="dash-title">Edit Job</h1>
          <p class="dash-text">Update your listing for <?php echo hirepeak_esc(trim((string) ($job['title'] ?? ''))); ?>.</p>
        </div>
        <a class="btn btn--ghost dash-head__cta" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">My Jobs</a>
      </header>

      <?php if ($flash !== ''): ?>
        <p class="dash-flash" role="status"><?php echo hirepeak_esc($flash); ?></p>
      <?php endif; ?>

      <?php if ($formError !== ''): ?>
        <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($formError); ?></p>
      <?php endif; ?>

      <?php
      echo hirepeak_job_form_render([
          'mode'          => 'edit',
          'action'        => 'employer/edit-job.php?id=' . $jobId,
          'csrf'          => hirepeak_csrf_token(),
          'id'            => $jobId,
          'values'        => $job,
          'errors'        => $errors,
          'categories'    => hirepeak_categories($db),
          'currentStatus' => $currentStatus,
      ]);
      ?>

    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>