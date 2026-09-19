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

const HIREPEAK_ALLOWED_ROLES = ['applicant', 'employer'];

hirepeak_csrf_token();

$errors = [];
$formError = '';
$old = ['name' => '', 'email' => '', 'role' => 'applicant'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim((string) ($_POST['name'] ?? ''));
    $old['email'] = trim((string) ($_POST['email'] ?? ''));
    $old['role'] = in_array($_POST['role'] ?? '', HIREPEAK_ALLOWED_ROLES, true) ? $_POST['role'] : 'applicant';

    if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
        $formError = 'Your session expired. Please try again.';
        hirepeak_csrf_token(true);
    } else {
        $name = $old['name'];
        $email = strtolower($old['email']);
        $role = (string) ($_POST['role'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        $terms = ($_POST['terms'] ?? '') === '1';

        if ($name === '') {
            $errors['name'] = 'Enter your full name.';
        } elseif (preg_match('/^.{2,100}$/u', $name) !== 1) {
            $errors['name'] = 'Full name must be 2–100 characters.';
        }

        if ($email === '') {
            $errors['email'] = 'Enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (strlen($email) > 190) {
            $errors['email'] = 'Email must be 190 characters or fewer.';
        }

        if (!in_array($role, HIREPEAK_ALLOWED_ROLES, true)) {
            $errors['role'] = 'Select an account type to continue.';
            $role = 'applicant';
            $old['role'] = 'applicant';
        }

        if ($password === '') {
            $errors['password'] = 'Enter a password.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (strlen($password) > 72) {
            $errors['password'] = 'Password must be 72 characters or fewer.';
        }

        if ($confirm === '') {
            $errors['confirm'] = 'Re-enter your password.';
        } elseif ($password !== '' && $confirm !== $password) {
            $errors['confirm'] = 'Passwords do not match.';
        }

        if (!$terms) {
            $errors['terms'] = 'Please accept the Terms and Privacy Policy.';
        }

        if (!$errors) {
            try {
                $db = hirepeak_db();
                $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors['email'] = 'An account with this email already exists.';
                }
                $stmt->close();
            } catch (\Throwable $e) {
                error_log('[hirepeak] Duplicate email check failed: ' . $e->getMessage());
                $formError = 'Registration is temporarily unavailable. Please try again shortly.';
            }
        }

        if (!$errors && $formError === '') {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, \'active\')');
                $stmt->bind_param('ssss', $name, $email, $hash, $role);
                $stmt->execute();
                $stmt->close();

                $_SESSION['register_flash'] = 'Your account has been created. Sign in to continue.';
                session_regenerate_id(true);
                header('Location: ' . hirepeak_url('login.php?registered=1'));
                exit;
            } catch (\mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $errors['email'] = 'An account with this email already exists.';
                } else {
                    error_log('[hirepeak] User insert failed: ' . $e->getMessage());
                    $formError = 'Registration is temporarily unavailable. Please try again shortly.';
                }
            } catch (\Throwable $e) {
                error_log('[hirepeak] User insert failed: ' . $e->getMessage());
                $formError = 'Registration is temporarily unavailable. Please try again shortly.';
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
  <title>Create Account — HirePeak</title>
  <meta name="description" content="Create your HirePeak account. Choose how you'll use HirePeak and get started.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='1' y='1' width='22' height='22' rx='7' fill='%234F46E5'/%3E%3Cpath d='M12 4.8 18 16H6Z' fill='%23FFFFFF' stroke='%23FFFFFF' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M12 6.6 8.8 16H5.9Z' fill='%23EEF2FF' stroke='%23EEF2FF' stroke-width='3' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-body auth-body--register">
  <?php require dirname(__DIR__) . '/app/Views/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="auth-main">
    <div class="auth-shell">
      <div class="auth-card auth-card--register">
        <p class="auth-kicker">Join HirePeak</p>
        <h1 class="auth-title">Create your HirePeak account</h1>
        <p class="auth-sub">Choose how you'll use HirePeak and get started.</p>

        <form class="auth-form" id="register-form" action="<?php echo hirepeak_url('register.php'); ?>" method="post" data-auth-form>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

          <fieldset class="auth-roles" id="reg-roles"<?php echo isset($errors['role']) ? ' aria-invalid="true"' : ''; ?>>
            <legend class="auth-roles__legend">I want to</legend>

            <label class="role-card">
              <input type="radio" name="role" value="applicant" required<?php echo $old['role'] === 'applicant' ? ' checked' : ''; ?>>
              <span class="role-card__dot" aria-hidden="true"></span>
              <span class="role-card__body">
                <span class="role-card__title">Applicant</span>
                <span class="role-card__desc">Find opportunities and track your applications.</span>
              </span>
            </label>

            <label class="role-card">
              <input type="radio" name="role" value="employer"<?php echo $old['role'] === 'employer' ? ' checked' : ''; ?>>
              <span class="role-card__dot" aria-hidden="true"></span>
              <span class="role-card__body">
                <span class="role-card__title">Employer</span>
                <span class="role-card__desc">Post jobs and manage candidates.</span>
              </span>
            </label>
          </fieldset>
          <p class="auth-roles__note" id="reg-role-note" aria-live="polite"></p>
          <?php echo hirepeak_error_slot($errors, 'role', 'reg', 'auth-roles__error'); ?>

          <div class="field">
            <label class="field__label" for="reg-name">Full name</label>
            <input class="field__input" id="reg-name" name="name" type="text" autocomplete="name" minlength="2" maxlength="100" pattern=".{2,100}" required placeholder="Jordan Kamel" value="<?php echo htmlspecialchars($old['name'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo isset($errors['name']) ? ' aria-invalid="true" aria-describedby="reg-name-error"' : ''; ?>>
            <?php echo hirepeak_error_slot($errors, 'name', 'reg'); ?>
          </div>

          <div class="field">
            <label class="field__label" for="reg-email">Email</label>
            <input class="field__input" id="reg-email" name="email" type="email" inputmode="email" autocomplete="email" maxlength="190" required placeholder="you@example.com" value="<?php echo htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo isset($errors['email']) ? ' aria-invalid="true" aria-describedby="reg-email-error"' : ''; ?>>
            <?php echo hirepeak_error_slot($errors, 'email', 'reg'); ?>
          </div>

          <div class="field">
            <div class="field__label-row">
              <label class="field__label" for="reg-password">Password</label>
            </div>
            <div class="field__control">
              <input class="field__input field__input--password" id="reg-password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required placeholder="Create a password"<?php echo isset($errors['password']) ? ' aria-invalid="true" aria-describedby="reg-password-error"' : ''; ?>>
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
            <?php echo hirepeak_error_slot($errors, 'password', 'reg'); ?>
          </div>

          <div class="field">
            <div class="field__label-row">
              <label class="field__label" for="reg-confirm">Confirm password</label>
            </div>
            <div class="field__control">
              <input class="field__input field__input--password" id="reg-confirm" name="confirm" type="password" autocomplete="new-password" minlength="8" maxlength="72" required placeholder="Re-enter your password"<?php echo isset($errors['confirm']) ? ' aria-invalid="true" aria-describedby="reg-confirm-error"' : ''; ?>>
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
            <?php echo hirepeak_error_slot($errors, 'confirm', 'reg'); ?>
          </div>

          <label class="auth-check">
            <input type="checkbox" id="reg-terms" name="terms" value="1" required<?php echo ($_POST['terms'] ?? '') === '1' ? ' checked' : ''; ?>>
            <span>I agree to the <a class="auth-check__link" href="#">Terms</a> and <a class="auth-check__link" href="#">Privacy Policy</a></span>
          </label>
          <?php echo hirepeak_error_slot($errors, 'terms', 'reg'); ?>

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

        <p class="auth-switch">Already have an account? <a href="<?php echo hirepeak_url('login.php'); ?>">Sign In</a></p>
      </div>

      <p class="auth-foot">HirePeak &middot; <a href="#">Terms</a> &middot; <a href="#">Privacy</a></p>
    </div>
  </main>

  <?php require dirname(__DIR__) . '/app/Views/footer.php'; ?>
</body>
</html>