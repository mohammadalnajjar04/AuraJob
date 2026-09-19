<?php
declare(strict_types=1);

/**
 * HirePeak shared site header (public nav + authenticated user menu).
 *
 * Renders the SVG sprite defs, skip link, and the .site-header navigation.
 * Pages set $pageActive ('home' | 'jobs' | 'employers' | '') before including this file to
 * mark the active nav item. The authenticated/guest right-hand state is derived
 * server-side from the session and never from client input.
 */

require_once __DIR__ . '/../Core/config.php';
require_once __DIR__ . '/../Core/auth.php';
require_once __DIR__ . '/../Support/html.php';
require_once __DIR__ . '/../Core/db.php';
require_once __DIR__ . '/../Services/notifications.php';

hirepeak_start_session();

$pageActive = $pageActive ?? '';

$hpUser = hirepeak_user();
$hpLogged = hirepeak_is_logged_in();
$hpDashboard = '';
$hpFirstName = '';
$hpFullName = '';

if ($hpUser !== null) {
    $hpDashboard = hirepeak_dashboard_for_role($hpUser);
    $hpFullName = trim((string) ($hpUser['name'] ?? ''));
    $hpFirstName = $hpFullName !== '' ? explode(' ', $hpFullName)[0] : '';
}

$hpCsrf = $hpLogged ? hirepeak_csrf_token() : '';

/*
 * Unread badge for the notifications bell. Read straight from the database for
 * the authenticated session user; a DB hiccup degrades to "no badge" rather
 * than a broken header.
 */
$hpUnread = 0;
if ($hpLogged && $hpUser !== null) {
    try {
        $hpDb = hirepeak_db();
        $hpUnread = hirepeak_notification_unread_count($hpDb, (int) $hpUser['id']);
    } catch (\Throwable $e) {
        error_log('[hirepeak] header unread badge failed: ' . $e->getMessage());
        $hpUnread = 0;
    }
}

$hpBell = '';
if ($hpLogged) {
    $hpBell = '<a class="bell" href="' . hirepeak_url('notifications.php') . '" aria-label="Notifications'
        . ($hpUnread > 0 ? ', ' . $hpUnread . ' unread' : '') . '">'
        . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>'
        . '<path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>'
        . '</svg>'
        . ($hpUnread > 0 ? '<span class="bell__badge" aria-hidden="true">' . (int) $hpUnread . '</span>' : '')
        . '</a>';
}

$hpNavItems = [
    ['key' => 'home', 'label' => 'Home', 'href' => hirepeak_url('index.php'), 'target' => ''],
    ['key' => 'jobs', 'label' => 'Find Jobs', 'href' => hirepeak_url('jobs.php'), 'target' => ''],
    ['key' => 'employers', 'label' => 'For Employers', 'href' => hirepeak_url('index.php#for-employers'), 'target' => ''],
];
?>
<svg class="svg-defs" aria-hidden="true">
  <defs>
    <symbol id="mark-peak" viewBox="0 0 24 24">
      <rect x="1" y="1" width="22" height="22" rx="7" fill="#4F46E5"/>
      <path d="M12 4.8 18 16H6Z" fill="#FFFFFF" stroke="#FFFFFF" stroke-width="3" stroke-linejoin="round"/>
      <path d="M12 6.6 8.8 16H5.9Z" fill="#EEF2FF" stroke="#EEF2FF" stroke-width="3" stroke-linejoin="round"/>
    </symbol>
  </defs>
</svg>

<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
  <div class="container site-header__inner">
    <a class="brand" href="<?php echo hirepeak_url('index.php'); ?>" aria-label="HirePeak home">
      <svg class="brand__mark" width="26" height="26" aria-hidden="true">
        <use href="#mark-peak"/>
      </svg>
      <span class="brand__name">HirePeak</span>
    </a>

    <div class="site-header__actions">
      <?php if ($hpLogged): ?>
        <a class="btn btn--primary btn--sm site-header__mobile-cta" href="<?php echo hirepeak_esc($hpDashboard); ?>">Dashboard</a>
      <?php else: ?>
        <a class="btn btn--primary btn--sm site-header__mobile-cta" href="<?php echo hirepeak_url('register.php'); ?>">Get Started</a>
      <?php endif; ?>
      <button class="icon-button nav-toggle" type="button" data-drawer-toggle aria-expanded="false" aria-controls="site-nav" aria-label="Open menu">
        <svg class="nav-toggle__icon-open" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <path d="M4 7h16"/>
          <path d="M4 12h16"/>
          <path d="M4 17h16"/>
        </svg>
        <svg class="nav-toggle__icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <path d="M6 6l12 12"/>
          <path d="M18 6 6 18"/>
        </svg>
      </button>
    </div>

    <nav class="site-nav" id="site-nav" data-drawer aria-hidden="true" aria-label="Main">
      <div class="site-nav__head">
        <span class="brand">
          <svg class="brand__mark" width="26" height="26" aria-hidden="true">
            <use href="#mark-peak"/>
          </svg>
          <span class="brand__name">HirePeak</span>
        </span>
        <button class="icon-button" type="button" data-drawer-close aria-label="Close menu">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M6 6l12 12"/>
            <path d="M18 6 6 18"/>
          </svg>
        </button>
      </div>

      <ul class="site-nav__links">
        <?php foreach ($hpNavItems as $hpItem): ?>
          <li>
            <a class="site-nav__link<?php echo $pageActive === $hpItem['key'] ? ' is-active' : ''; ?>" data-nav-key="<?php echo hirepeak_esc($hpItem['key']); ?>" href="<?php echo hirepeak_esc($hpItem['href']); ?>"<?php echo $pageActive === $hpItem['key'] ? ' aria-current="page"' : ''; ?>><?php echo hirepeak_esc($hpItem['label']); ?></a>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="site-nav__auth">
        <?php if ($hpLogged && $hpUser !== null): ?>
          <?php echo $hpBell; ?>
          <a class="btn btn--ghost" href="<?php echo hirepeak_esc($hpDashboard); ?>">Dashboard</a>

          <div class="account-menu">
            <details class="account-menu__box">
              <summary class="account-menu__trigger">
                <span class="account-menu__name"><?php echo hirepeak_esc($hpFirstName); ?></span>
                <svg class="account-menu__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="m6 9 6 6 6-6"/>
                </svg>
              </summary>
              <div class="account-menu__panel">
                <p class="account-menu__label">Signed in as <strong><?php echo hirepeak_esc($hpFullName); ?></strong></p>
                <a class="account-menu__item" href="<?php echo hirepeak_esc($hpDashboard); ?>">Dashboard</a>
                <?php if (($hpUser['role'] ?? '') === 'applicant'): ?>
                  <a class="account-menu__item" href="<?php echo hirepeak_url('applicant/applications.php'); ?>">My Applications</a>
                  <a class="account-menu__item" href="<?php echo hirepeak_url('applicant/profile.php'); ?>">My Profile</a>
                <?php elseif (($hpUser['role'] ?? '') === 'employer'): ?>
                  <a class="account-menu__item" href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">My Jobs</a>
                  <a class="account-menu__item" href="<?php echo hirepeak_url('employer/company-profile.php'); ?>">Company Profile</a>
                <?php endif; ?>
                <form class="account-menu__item account-menu__logout" method="post" action="<?php echo hirepeak_url('logout.php'); ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc($hpCsrf); ?>">
                  <button class="account-menu__logout-btn" type="submit">Logout</button>
                </form>
              </div>
            </details>
          </div>
        <?php else: ?>
          <a class="btn btn--ghost" href="<?php echo hirepeak_url('login.php'); ?>">Sign In</a>
          <a class="btn btn--primary" href="<?php echo hirepeak_url('register.php'); ?>">Create Account</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>