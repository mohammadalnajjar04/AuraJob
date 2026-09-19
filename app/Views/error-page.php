<?php
declare(strict_types=1);

/**
 * AuraJob shared branded HTTP error page.
 *
 * Caller supplies the display values and sets the HTTP status before including.
 * No exception text, SQL message, filesystem path, or other internal detail is
 * ever rendered to the visitor.
 */

require_once __DIR__ . '/../Support/html.php';

$errorCode = isset($errorCode) ? (string) $errorCode : 'Error';
$errorEyebrow = isset($errorEyebrow) ? (string) $errorEyebrow : 'Something went wrong';
$errorTitle = isset($errorTitle) ? (string) $errorTitle : 'We couldn\'t complete that request.';
$errorText = isset($errorText) ? (string) $errorText : 'Please try again or return to AuraJob.';
$errorPrimaryLabel = isset($errorPrimaryLabel) ? (string) $errorPrimaryLabel : 'Back to AuraJob';
$errorPrimaryHref = isset($errorPrimaryHref) ? (string) $errorPrimaryHref : hirepeak_url('index.php');
$errorSecondaryLabel = isset($errorSecondaryLabel) ? (string) $errorSecondaryLabel : '';
$errorSecondaryHref = isset($errorSecondaryHref) ? (string) $errorSecondaryHref : '';

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo hirepeak_esc($errorCode . ' — ' . $errorTitle . ' — AuraJob'); ?></title>
  <meta name="description" content="<?php echo hirepeak_esc($errorText); ?>">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="<?php echo hirepeak_url('assets/images/aurajob-mark.png'); ?>" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo hirepeak_url('assets/css/style.css'); ?>">
</head>
<body>
  <?php require __DIR__ . '/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="error-page">
    <div class="container error-page__container">
      <section class="error-card" aria-labelledby="error-page-title">
        <div class="error-card__visual" aria-hidden="true">
          <span class="error-card__halo"></span>
          <img src="<?php echo hirepeak_url('assets/images/aurajob-mark.png'); ?>" width="64" height="64" alt="">
        </div>

        <p class="error-card__eyebrow"><?php echo hirepeak_esc($errorEyebrow); ?></p>
        <p class="error-card__code" aria-hidden="true"><?php echo hirepeak_esc($errorCode); ?></p>
        <h1 class="error-card__title" id="error-page-title"><?php echo hirepeak_esc($errorTitle); ?></h1>
        <p class="error-card__text"><?php echo hirepeak_esc($errorText); ?></p>

        <div class="error-card__actions">
          <a class="btn btn--primary" href="<?php echo hirepeak_esc($errorPrimaryHref); ?>">
            <?php echo hirepeak_esc($errorPrimaryLabel); ?>
          </a>
          <?php if ($errorSecondaryLabel !== '' && $errorSecondaryHref !== ''): ?>
            <a class="btn btn--outline" href="<?php echo hirepeak_esc($errorSecondaryHref); ?>">
              <?php echo hirepeak_esc($errorSecondaryLabel); ?>
            </a>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </main>

  <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
