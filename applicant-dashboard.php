<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

$user = hirepeak_require_role('applicant');

$pageActive = '';
$pageScripts = ['src/js/main.js'];

$firstName = trim((string) ($user['name'] ?? ''));
$firstName = $firstName !== '' ? explode(' ', $firstName)[0] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — HirePeak</title>
  <meta name="description" content="Your HirePeak applicant dashboard.">
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
      <section class="dash-card" aria-labelledby="dash-title">
        <p class="dash-eyebrow">Applicant dashboard</p>
        <h1 class="dash-title" id="dash-title">Welcome, <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="dash-text">Your applicant dashboard is ready for the next build.</p>
      </section>
    </div>
  </main>

  <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>