<?php
declare(strict_types=1);

/**
 * AuraJob access-denied page (HTTP 403).
 *
 * Shown to authenticated users who attempt a page for a role they don't hold.
 * Included by hirepeak_require_role(); safe to render directly (it also sets
 * the 403 status itself). No implementation details are exposed.
 */

require_once __DIR__ . '/../Core/config.php';
require_once __DIR__ . '/../Core/auth.php';
require_once __DIR__ . '/../Support/html.php';

hirepeak_start_session();

http_response_code(403);

$deniedUser = hirepeak_user();
$deniedDest = hirepeak_dashboard_for_role($deniedUser);
$pageActive = '';
$pageScripts = ['/assets/js/main.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Access denied — AuraJob</title>
  <meta name="description" content="You don't have access to this page.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="/assets/images/aurajob-mark.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <?php require __DIR__ . '/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="denied-main">
    <div class="container">
      <div class="denied-card">
        <h1 class="denied-title">Access denied</h1>
        <p class="denied-text">You don't have access to this page.</p>
        <div class="denied-actions">
          <a class="btn btn--primary" href="<?php echo htmlspecialchars($deniedDest, ENT_QUOTES, 'UTF-8'); ?>">Back to dashboard</a>
          <a class="btn btn--ghost" href="<?php echo hirepeak_url('index.php'); ?>">Back to AuraJob</a>
        </div>
      </div>
    </div>
  </main>

  <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>