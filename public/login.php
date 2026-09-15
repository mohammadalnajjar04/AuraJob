<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/db.php';
require dirname(__DIR__) . '/app/Core/auth.php';
require dirname(__DIR__) . '/app/Support/html.php';

hirepeak_start_session();

if (hirepeak_is_logged_in()) {
    header('Location: ' . hirepeak_dashboard_for_role(hirepeak_user()));
    exit;
}

hirepeak_csrf_token();

$registerFlash = '';
if (isset($_GET['registered'], $_SESSION['register_flash']) && $_SESSION['register_flash'] !== '') {
    $registerFlash = $_SESSION['register_flash'];
    unset($_SESSION['register_flash']);
}

$errors = [];
$formError = '';
$old = ['email' => '', 'remember' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['email'] = trim((string) ($_POST['email'] ?? ''));
    $old['remember'] = ($_POST['remember'] ?? '') === '1';
    $email = strtolower($old['email']);
    $password = (string) ($_POST['password'] ?? '');

    if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
        $formError = 'Your session expired. Please try again.';
        hirepeak_csrf_token(true);
    } else {
        if ($email === '') {
            $errors['email'] = 'Enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (strlen($email) > 190) {
            $errors['email'] = 'Email must be 190 characters or fewer.';
        }

        if ($password === '') {
            $errors['password'] = 'Enter your password.';
        }

        if (!$errors) {
            try {
                $db = hirepeak_db();
                $stmt = $db->prepare("SELECT id, name, email, role, password_hash FROM users WHERE email = ? AND status = 'active' LIMIT 1");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();

                $user = null;
                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($userId, $userName, $userEmail, $userRole, $userHash);
                    $stmt->fetch();
                    $user = [
                        'id' => $userId,
                        'name' => $userName,
                        'email' => $userEmail,
                        'role' => $userRole,
                        'password_hash' => $userHash,
                    ];
                }
                $stmt->close();

                if ($user !== null && password_verify($password, $user['password_hash'])) {
                    hirepeak_login((int) $user['id'], $user['name'], $user['email'], $user['role']);
                    header('Location: ' . hirepeak_dashboard_for_role($user));
                    exit;
                }

                $formError = 'Email or password is incorrect.';
            } catch (\Throwable $e) {
                error_log('[hirepeak] Login verification failed: ' . $e->getMessage());
                $formError = 'Sign in is temporarily unavailable. Please try again shortly.';
            }
        }
    }
}

$pageScripts = ['/assets/js/main.js', '/assets/js/auth.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — HirePeak</title>
  <meta name="description" content="Sign in to your HirePeak account to manage your applications and continue your job search.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='1' y='1' width='22' height='22' rx='7' fill='%234F46E5'/%3E%3Cpath d='M12 4.8 18 16H6Z' fill='%23FFFFFF' stroke='%23FFFFFF' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M12 6.6 8.8 16H5.9Z' fill='%23EEF2FF' stroke='%23EEF2FF' stroke-width='3' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-body">
  <?php require dirname(__DIR__) . '/app/Views/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="auth-main">
    <div class="auth-shell">
      <div class="auth-card">
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Sign in to continue to HirePeak.</p>

        <?php if ($registerFlash !== ''): ?>
        <div class="auth-success auth-flash" role="status">
          <svg class="auth-success__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 6 9 17l-5-5"/>
          </svg>
          <span class="auth-success__text"><?php echo htmlspecialchars($registerFlash, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php endif; ?>

        <form class="auth-form" id="login-form" action="<?php echo hirepeak_url('login.php'); ?>" method="post" data-auth-form novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hirepeak_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

          <div class="field">
            <label class="field__label" for="login-email">Email address</label>
            <input class="field__input" id="login-email" name="email" type="email" inputmode="email" autocomplete="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo isset($errors['email']) ? ' aria-invalid="true" aria-describedby="login-email-error"' : ''; ?>>
            <?php echo hirepeak_error_slot($errors, 'email', 'login'); ?>
          </div>

          <div class="field">
            <div class="field__label-row">
              <label class="field__label" for="login-password">Password</label>
              <a class="field__link" href="#">Forgot password?</a>
            </div>
            <div class="field__control">
              <input class="field__input field__input--password" id="login-password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password"<?php echo isset($errors['password']) ? ' aria-invalid="true" aria-describedby="login-password-error"' : ''; ?>>
              <button class="field__toggle" type="button" data-password-toggle aria-label="Show password" aria-controls="login-password" aria-pressed="false">
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
            <?php echo hirepeak_error_slot($errors, 'password', 'login'); ?>
          </div>

          <label class="auth-check">
            <input type="checkbox" name="remember" value="1"<?php echo $old['remember'] ? ' checked' : ''; ?>>
            <span>Remember me</span>
          </label>

          <?php if ($formError !== ''): ?>
          <div class="auth-alert" role="alert">
            <svg class="auth-alert__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 9v4"/>
              <path d="M12 17h.01"/>
              <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
            </svg>
            <span><?php echo htmlspecialchars($formError, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <?php endif; ?>

          <div class="auth-success" id="login-success" role="status" hidden>
            <svg class="auth-success__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 6 9 17l-5-5"/>
            </svg>
            <span class="auth-success__text"></span>
          </div>

          <button class="btn btn--primary auth-submit" type="submit" data-auth-submit>
            <span class="btn__label">Sign In</span>
          </button>
        </form>

        <p class="auth-switch">New to HirePeak? <a href="<?php echo hirepeak_url('register.php'); ?>">Create an account</a></p>
      </div>

      <p class="auth-foot">HirePeak &middot; <a href="#">Terms</a> &middot; <a href="#">Privacy</a></p>
    </div>
  </main>

  <?php require dirname(__DIR__) . '/app/Views/footer.php'; ?>
</body>
</html>