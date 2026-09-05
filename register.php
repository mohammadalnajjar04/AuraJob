<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — HirePeak</title>
  <meta name="description" content="Create your HirePeak account. Choose how you'll use HirePeak and get started.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='1' y='1' width='22' height='22' rx='7' fill='%234F46E5'/%3E%3Cpath d='M12 4.8 18 16H6Z' fill='%23FFFFFF' stroke='%23FFFFFF' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M12 6.6 8.8 16H5.9Z' fill='%23EEF2FF' stroke='%23EEF2FF' stroke-width='3' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="src/css/style.css">
</head>
<body class="auth-body">
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
      <a class="brand" href="index.php" aria-label="HirePeak home">
        <svg class="brand__mark" width="26" height="26" aria-hidden="true">
          <use href="#mark-peak"/>
        </svg>
        <span class="brand__name">HirePeak</span>
      </a>

      <div class="site-header__actions">
        <a class="btn btn--primary btn--sm site-header__mobile-cta" href="register.php">Get Started</a>
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
          <li><a class="site-nav__link" href="index.php">Home</a></li>
          <li><a class="site-nav__link" href="jobs.php">Find Jobs</a></li>
          <li><a class="site-nav__link" href="index.php#for-employers">For Employers</a></li>
        </ul>

        <div class="site-nav__auth">
          <a class="btn btn--ghost" href="login.php">Sign In</a>
          <a class="btn btn--primary" href="register.php">Create Account</a>
        </div>
      </nav>
    </div>
  </header>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="auth-main">
    <div class="auth-shell">
      <div class="auth-card">
        <h1 class="auth-title">Create your HirePeak account</h1>
        <p class="auth-sub">Choose how you'll use HirePeak and get started.</p>

        <form class="auth-form" id="register-form" action="register.php" method="post" data-auth-form novalidate>
          <fieldset class="auth-roles" id="reg-roles">
            <legend class="auth-roles__legend">I want to</legend>

            <label class="role-card">
              <input type="radio" name="role" value="applicant" checked>
              <span class="role-card__dot" aria-hidden="true"></span>
              <span class="role-card__body">
                <span class="role-card__title">Applicant</span>
                <span class="role-card__desc">Find opportunities and track your applications.</span>
              </span>
            </label>

            <label class="role-card">
              <input type="radio" name="role" value="employer">
              <span class="role-card__dot" aria-hidden="true"></span>
              <span class="role-card__body">
                <span class="role-card__title">Employer</span>
                <span class="role-card__desc">Post jobs and manage candidates.</span>
              </span>
            </label>
          </fieldset>
          <p class="auth-roles__note" id="reg-role-note" aria-live="polite"></p>
          <p class="field__error auth-roles__error" id="reg-role-error" hidden></p>

          <div class="field">
            <label class="field__label" for="reg-name">Full name</label>
            <input class="field__input" id="reg-name" name="name" type="text" autocomplete="name" placeholder="Jordan Kamel">
            <p class="field__error" id="reg-name-error" hidden></p>
          </div>

          <div class="field">
            <label class="field__label" for="reg-email">Email</label>
            <input class="field__input" id="reg-email" name="email" type="email" inputmode="email" autocomplete="email" placeholder="you@example.com">
            <p class="field__error" id="reg-email-error" hidden></p>
          </div>

          <div class="field">
            <div class="field__label-row">
              <label class="field__label" for="reg-password">Password</label>
            </div>
            <div class="field__control">
              <input class="field__input field__input--password" id="reg-password" name="password" type="password" autocomplete="new-password" placeholder="Create a password">
              <button class="field__toggle" type="button" data-password-toggle aria-label="Show password" aria-controls="reg-password" aria-pressed="false">
                <svg class="field__toggle-icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="field__toggle-icon-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="m2 2 20 20"/>
                  <path d="M6.7 6.7C5.1 8 3.9 9.8 3 12c1.3 3.5 5 7 9 7 1.4 0 2.8-.4 4-1.1M9.5 5.4A9.4 9.4 0 0 1 12 5c5 0 8.5 5 9 7a17 17 0 0 1-3 4.4M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                </svg>
              </button>
            </div>
            <p class="field__hint">Use at least 8 characters.</p>
            <p class="field__error" id="reg-password-error" hidden></p>
          </div>

          <div class="field">
            <div class="field__label-row">
              <label class="field__label" for="reg-confirm">Confirm password</label>
            </div>
            <div class="field__control">
              <input class="field__input field__input--password" id="reg-confirm" name="confirm" type="password" autocomplete="new-password" placeholder="Re-enter your password">
              <button class="field__toggle" type="button" data-password-toggle aria-label="Show password" aria-controls="reg-confirm" aria-pressed="false">
                <svg class="field__toggle-icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="field__toggle-icon-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="m2 2 20 20"/>
                  <path d="M6.7 6.7C5.1 8 3.9 9.8 3 12c1.3 3.5 5 7 9 7 1.4 0 2.8-.4 4-1.1M9.5 5.4A9.4 9.4 0 0 1 12 5c5 0 8.5 5 9 7a17 17 0 0 1-3 4.4M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                </svg>
              </button>
            </div>
            <p class="field__error" id="reg-confirm-error" hidden></p>
          </div>

          <label class="auth-check">
            <input type="checkbox" id="reg-terms" name="terms" value="1">
            <span>I agree to the <a class="auth-check__link" href="#">Terms</a> and <a class="auth-check__link" href="#">Privacy Policy</a></span>
          </label>
          <p class="field__error" id="reg-terms-error" hidden></p>

          <div class="auth-success" id="register-success" role="status" hidden>
            <svg class="auth-success__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 6 9 17l-5-5"/>
            </svg>
            <span class="auth-success__text"></span>
          </div>

          <button class="btn btn--primary auth-submit" type="submit" data-auth-submit>
            <span class="btn__label">Create Account</span>
          </button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Sign In</a></p>
      </div>

      <p class="auth-foot">HirePeak &middot; <a href="#">Terms</a> &middot; <a href="#">Privacy</a></p>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-bottom">
        <p>&copy; <span id="year">2026</span> HirePeak. All rights reserved.</p>
        <ul class="footer-bottom__legal">
          <li><a href="#">Terms</a></li>
          <li><a href="#">Privacy</a></li>
        </ul>
      </div>
    </div>
  </footer>

  <script src="src/js/main.js"></script>
  <script src="src/js/auth.js"></script>
</body>
</html>