<?php
declare(strict_types=1);

/**
 * Notifications center — every in-app notification for the signed-in user
 * (applicant or employer), newest first.
 *
 * - Only authenticated users can view this page; guests are redirected to
 *   login.php. Both roles share the page because both receive notifications
 *   (applicants about their application status, employers about new
 *   applications).
 * - Read state comes from the stored notifications.is_read column only.
 * - The only mutations are Mark as read (one) and Mark all as read, both are
 *   POST + CSRF, scoped in SQL by the authenticated session user id, and both
 *   end in a 303 PRG redirect so a refresh can never repeat a mutation. There
 *   are no GET mutations.
 */

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/db.php';
require dirname(__DIR__) . '/app/Support/html.php';
require dirname(__DIR__) . '/app/Services/notifications.php';

$user = hirepeak_require_auth();
$uid  = (int) $user['id'];

$dashboardDest = hirepeak_dashboard_for_role($user);
$loadError = false;
$flash = '';
$error = '';

try {
    $db = hirepeak_db();

    /* PRG flashes from the mark-read POST handler below. */
    if (isset($_SESSION['hirepeak_notif_flash']) && $_SESSION['hirepeak_notif_flash'] !== '') {
        $flash = (string) $_SESSION['hirepeak_notif_flash'];
        unset($_SESSION['hirepeak_notif_flash']);
    }
    if (isset($_SESSION['hirepeak_notif_error']) && $_SESSION['hirepeak_notif_error'] !== '') {
        $error = (string) $_SESSION['hirepeak_notif_error'];
        unset($_SESSION['hirepeak_notif_error']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['hirepeak_notif_error'] = 'Your session expired. Please try again.';
            header('Location: ' . hirepeak_url('notifications.php'), true, 303);
            exit;
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'read') {
            $rawId = (string) ($_POST['id'] ?? '');
            if ($rawId === '' || preg_match('/^[1-9][0-9]*$/D', $rawId) !== 1) {
                $_SESSION['hirepeak_notif_error'] = 'That notification is no longer available.';
                header('Location: ' . hirepeak_url('notifications.php'), true, 303);
                exit;
            }
            $notificationId = (int) $rawId;

            $ok = hirepeak_notification_mark_one_read($db, $uid, $notificationId);
            $_SESSION['hirepeak_notif_flash'] = $ok ? 'Notification marked as read.' : 'Could not update that notification. Please try again.';
        } elseif ($action === 'read_all') {
            $ok = hirepeak_notification_mark_all_read($db, $uid);
            $_SESSION['hirepeak_notif_flash'] = $ok ? 'All notifications marked as read.' : 'Could not update your notifications. Please try again.';
        } else {
            $_SESSION['hirepeak_notif_error'] = 'Invalid request.';
        }

        header('Location: ' . hirepeak_url('notifications.php'), true, 303);
        exit;
    }

    $notifications = hirepeak_notification_recent($db, $uid, 50);
    $unread = hirepeak_notification_unread_count($db, $uid);
} catch (\Throwable $e) {
    error_log('[AuraJob] notifications load failed: ' . $e->getMessage());
    $loadError = true;
    $notifications = [];
    $unread = 0;
}

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications — AuraJob</title>
  <meta name="description" content="Your recent updates on AuraJob.">
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

      <div class="track-topbar notifications-topbar">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <ol>
            <li><a href="<?php echo hirepeak_esc($dashboardDest); ?>">Dashboard</a></li>
            <li aria-current="page">Notifications</li>
          </ol>
        </nav>
        <a class="jd-back" href="<?php echo hirepeak_esc($dashboardDest); ?>">Back to Dashboard</a>
      </div>

      <?php if ($loadError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="notif-error-title">
          <h1 id="notif-error-title">Something went wrong on our side.</h1>
          <p>We couldn't load your notifications right now. Please try again in a moment.</p>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('notifications.php'); ?>">Try again</a>
        </section>

      <?php else: ?>

        <header class="dash-head notifications-head">
          <div>
            <p class="dash-eyebrow">Updates</p>
            <h1 class="dash-title">Notifications</h1>
            <?php if ($unread > 0): ?>
              <p class="dash-text"><?php echo (int) $unread; ?> unread <?php echo $unread === 1 ? 'notification' : 'notifications'; ?>.</p>
            <?php else: ?>
              <p class="dash-text">You're all caught up.</p>
            <?php endif; ?>
          </div>
          <?php if ($unread > 0): ?>
            <form class="dash-head__cta" action="<?php echo hirepeak_url('notifications.php'); ?>" method="post">
              <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">
              <input type="hidden" name="action" value="read_all">
              <button class="btn btn--ghost" type="submit">Mark all as read</button>
            </form>
          <?php endif; ?>
        </header>

        <?php if ($flash !== ''): ?>
          <p class="dash-flash" role="status"><?php echo hirepeak_esc($flash); ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($error); ?></p>
        <?php endif; ?>

        <section class="dash-panel dash-panel--full notifications-panel" aria-labelledby="notifications-list-title">
          <div class="panel-head">
            <h2 class="panel-head__title" id="notifications-list-title">All notifications</h2>
            <?php if ($notifications !== []): ?>
              <span class="panel-head__count"><?php echo (int) count($notifications); ?></span>
            <?php endif; ?>
          </div>

          <?php if ($notifications === []): ?>
            <div class="empty-state">
              <div class="empty-state__icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                  <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                </svg>
              </div>
              <h3 class="empty-state__title">No notifications yet.</h3>
              <p class="empty-state__text">You'll see updates here when an employer reviews your application or a new application arrives.</p>
              <div class="empty-state__actions">
                <a class="btn btn--primary" href="<?php echo hirepeak_esc($dashboardDest); ?>">Back to Dashboard</a>
              </div>
            </div>
          <?php else: ?>
            <ul class="notif-list">
              <?php foreach ($notifications as $n => $item): ?>
                <?php
                $type = (string) ($item['type'] ?? 'system');
                $isNew = (bool) ($item['is_new'] ?? false);
                $createdAt = (string) ($item['created_at'] ?? '');
                ?>
                <li class="notif-row<?php echo $isNew ? ' is-unread' : ''; ?>">
                  <span class="notif-row__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                      <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                  </span>
                  <div class="notif-row__body">
                    <p class="notif-row__text"><?php echo hirepeak_esc((string) ($item['message'] ?? '')); ?></p>
                    <p class="notif-row__meta">
                      <?php if ($isNew): ?>
                        <span class="notif-row__new">New</span>
                      <?php endif; ?>
                      <span class="notif-row__time"><?php echo hirepeak_esc(hirepeak_relative_time($createdAt)); ?></span>
                    </p>
                  </div>
                  <?php if ($isNew): ?>
                    <div class="notif-row__actions">
                      <form class="inline-form" action="<?php echo hirepeak_url('notifications.php'); ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">
                        <input type="hidden" name="action" value="read">
                        <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                        <button class="btn btn--outline btn--sm" type="submit">Mark as read</button>
                      </form>
                    </div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__) . '/app/Views/footer.php'; ?>
</body>
</html>