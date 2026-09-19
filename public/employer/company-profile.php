<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';

$user = hirepeak_require_role('employer');
$uid = (int) $user['id'];

/*
 * Company profile editor.
 *
 * - Identity always comes from the authenticated session (never query/body
 *   input), so an employer can only ever read/write their own companies row.
 * - One company row per employer is guaranteed by the UNIQUE(user_id)
 *   constraint and by the create-then-update flow below.
 * - The form carries no id/hidden ownership fields; tampering is impossible.
 */

$errors = [];
$formError = '';
$flash = '';
$company = null;
$hasError = false;

try {
    $db = hirepeak_db();

    if (isset($_GET['updated'], $_SESSION['company_flash']) && $_SESSION['company_flash'] !== '') {
        $flash = (string) $_SESSION['company_flash'];
        unset($_SESSION['company_flash']);
    }

    $stmt = $db->prepare(
        'SELECT name, description, industry, location, website,
                contact_email, contact_phone
         FROM companies
         WHERE user_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    $defaults = [
        'name'          => $company['name'] ?? '',
        'description'   => $company['description'] ?? '',
        'industry'      => $company['industry'] ?? '',
        'location'      => $company['location'] ?? '',
        'website'       => $company['website'] ?? '',
        'contact_email' => $company['contact_email'] ?? '',
        'contact_phone' => $company['contact_phone'] ?? '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old = [
            'name'          => trim((string) ($_POST['company_name'] ?? '')),
            'description'   => trim((string) ($_POST['description'] ?? '')),
            'industry'      => trim((string) ($_POST['industry'] ?? '')),
            'location'      => trim((string) ($_POST['location'] ?? '')),
            'website'       => trim((string) ($_POST['website'] ?? '')),
            'contact_email' => trim((string) ($_POST['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
        ];

        if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
            $formError = 'Your session expired. Please try again.';
            hirepeak_csrf_token(true);
        } else {
            if ($old['name'] === '') {
                $errors['company_name'] = 'Company name is required.';
            } elseif (hirepeak_utf8_length($old['name']) > 150) {
                $errors['company_name'] = 'Company name must be 150 characters or fewer.';
            }

            if ($old['description'] !== '' && hirepeak_utf8_length($old['description']) > 4000) {
                $errors['description'] = 'Description must be 4000 characters or fewer.';
            }

            if ($old['industry'] !== '' && hirepeak_utf8_length($old['industry']) > 100) {
                $errors['industry'] = 'Industry must be 100 characters or fewer.';
            }

            if ($old['location'] !== '' && hirepeak_utf8_length($old['location']) > 150) {
                $errors['location'] = 'Location must be 150 characters or fewer.';
            }

            if ($old['website'] !== '') {
                if (hirepeak_utf8_length($old['website']) > 255) {
                    $errors['website'] = 'Website must be 255 characters or fewer.';
                } elseif (filter_var($old['website'], FILTER_VALIDATE_URL) === false
                    || !preg_match('{^https?://}i', $old['website'])) {
                    $errors['website'] = 'Enter a valid website URL starting with http:// or https://.';
                }
            }

            if ($old['contact_email'] !== '') {
                if (hirepeak_utf8_length($old['contact_email']) > 190) {
                    $errors['contact_email'] = 'Contact email must be 190 characters or fewer.';
                } elseif (filter_var($old['contact_email'], FILTER_VALIDATE_EMAIL) === false) {
                    $errors['contact_email'] = 'Enter a valid email address.';
                }
            }

            if ($old['contact_phone'] !== '') {
                if (hirepeak_utf8_length($old['contact_phone']) > 30 || preg_match('/^[0-9+\s().-]+$/', $old['contact_phone']) !== 1) {
                    $errors['contact_phone'] = 'Enter a valid phone number.';
                } else {
                    $digits = preg_replace('/\D/', '', $old['contact_phone']);
                    if ($digits === null || strlen($digits) < 6 || strlen($digits) > 15) {
                        $errors['contact_phone'] = 'Phone number must contain 6–15 digits.';
                    }
                }
            }

            if (!$errors) {
                $name = $old['name'];
                $description = $old['description'] !== '' ? $old['description'] : null;
                $industry = $old['industry'] !== '' ? $old['industry'] : null;
                $location = $old['location'] !== '' ? $old['location'] : null;
                $website = $old['website'] !== '' ? $old['website'] : null;
                $contactEmail = $old['contact_email'] !== '' ? $old['contact_email'] : null;
                $contactPhone = $old['contact_phone'] !== '' ? $old['contact_phone'] : null;

                $created = false;

                if ($company === null) {
                    try {
                        $stmt = $db->prepare(
                            'INSERT INTO companies
                               (user_id, name, description, industry, location,
                                website, contact_email, contact_phone)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->bind_param(
                            'isssssss',
                            $uid, $name, $description, $industry, $location,
                            $website, $contactEmail, $contactPhone
                        );
                        $stmt->execute();
                        $stmt->close();
                        $created = true;
                    } catch (\mysqli_sql_exception $e) {
                        // 1062 = UNIQUE(user_id) hit by a concurrent request creating the row.
                        if ($e->getCode() !== 1062) {
                            throw $e;
                        }
                    }
                }

                if ($company !== null || !$created) {
                    $stmt = $db->prepare(
                        'UPDATE companies
                         SET name = ?, description = ?, industry = ?, location = ?,
                             website = ?, contact_email = ?, contact_phone = ?
                         WHERE user_id = ?'
                    );
                    $stmt->bind_param(
                        'sssssssi',
                        $name, $description, $industry, $location,
                        $website, $contactEmail, $contactPhone, $uid
                    );
                    $stmt->execute();
                    $stmt->close();
                }

                $_SESSION['company_flash'] = 'Your company profile has been updated.';
                header('Location: ' . hirepeak_url('employer/company-profile.php?updated=1'));
                exit;
            }

            $company = $old; // re-render with the submitted values on validation failure
        }
    } else {
        $old = $defaults;
    }
} catch (\Throwable $e) {
    error_log('[hirepeak] company profile load/save failed: ' . $e->getMessage());
    $hasError = true;
}

$pageActive = '';
$pageScripts = ['/assets/js/main.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Profile — HirePeak</title>
  <meta name="description" content="Keep your HirePeak company profile accurate so candidates know who they're applying to.">
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

      <header class="dash-head dash-head--profile">
        <div>
          <p class="dash-eyebrow">Employer account</p>
          <h1 class="dash-title">Company Profile</h1>
          <p class="dash-text">Keep your company information accurate so candidates know who they're applying to.</p>
        </div>
        <a class="btn btn--ghost dash-head__cta" href="<?php echo hirepeak_url('employer/dashboard.php'); ?>">Back to dashboard</a>
      </header>

      <?php if ($hasError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="profile-error-title">
          <h2 id="profile-error-title">We couldn't load your company profile right now.</h2>
          <p>Please try again in a moment.</p>
        </section>
      <?php else: ?>

        <?php if ($flash !== ''): ?>
          <p class="dash-flash" role="status"><?php echo hirepeak_esc($flash); ?></p>
        <?php endif; ?>

        <?php if ($formError !== ''): ?>
          <p class="dash-alert dash-alert--error" role="alert"><?php echo hirepeak_esc($formError); ?></p>
        <?php endif; ?>

        <div class="profile-layout profile-layout--account">

          <div class="profile-main-col">
            <form class="profile-form" id="company-form" action="<?php echo hirepeak_url('employer/company-profile.php'); ?>" method="post">
              <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">

              <section class="profile-section profile-section--grid" aria-labelledby="profile-section-details">
                <h2 class="profile-section__title" id="profile-section-details">Company details</h2>

                <div class="field">
                  <label class="field__label" for="profile-company_name">Company name</label>
                  <input class="field__input" id="profile-company_name" name="company_name" type="text"
                         maxlength="150" autocomplete="organization" required
                         placeholder="e.g. Sprint Valley"
                         value="<?php echo hirepeak_esc((string) ($old['name'] ?? '')); ?>"
                         <?php echo isset($errors['company_name']) ? ' aria-invalid="true" aria-describedby="profile-company_name-error"' : ''; ?>>
                  <?php echo hirepeak_error_slot($errors, 'company_name', 'profile'); ?>
                </div>

                <div class="field">
                  <label class="field__label" for="profile-industry">Industry</label>
                  <input class="field__input" id="profile-industry" name="industry" type="text"
                         maxlength="100" autocomplete="organization"
                         placeholder="e.g. Technology"
                         value="<?php echo hirepeak_esc((string) ($old['industry'] ?? '')); ?>"
                         <?php echo isset($errors['industry']) ? ' aria-invalid="true" aria-describedby="profile-industry-error"' : ''; ?>>
                  <?php echo hirepeak_error_slot($errors, 'industry', 'profile'); ?>
                </div>

                <div class="field">
                  <label class="field__label" for="profile-location">Location</label>
                  <input class="field__input" id="profile-location" name="location" type="text"
                         maxlength="150" autocomplete="address-level2"
                         placeholder="e.g. Amman, Jordan"
                         value="<?php echo hirepeak_esc((string) ($old['location'] ?? '')); ?>"
                         <?php echo isset($errors['location']) ? ' aria-invalid="true" aria-describedby="profile-location-error"' : ''; ?>>
                  <?php echo hirepeak_error_slot($errors, 'location', 'profile'); ?>
                </div>

                <div class="field">
                  <label class="field__label" for="profile-website">Website</label>
                  <input class="field__input" id="profile-website" name="website" type="url" inputmode="url"
                         maxlength="255" autocomplete="url"
                         placeholder="https://example.com"
                         value="<?php echo hirepeak_esc((string) ($old['website'] ?? '')); ?>"
                         <?php echo isset($errors['website']) ? ' aria-invalid="true" aria-describedby="profile-website-error"' : ''; ?>>
                  <p class="field__hint">Must start with http:// or https://</p>
                  <?php echo hirepeak_error_slot($errors, 'website', 'profile'); ?>
                </div>
              </section>

              <section class="profile-section" aria-labelledby="profile-section-overview">
                <h2 class="profile-section__title" id="profile-section-overview">About your company</h2>

                <div class="field">
                  <label class="field__label" for="profile-description">Description</label>
                  <textarea class="field__input field__textarea" id="profile-description" name="description" rows="6"
                            maxlength="4000" placeholder="A short introduction to your company and what you do."<?php echo isset($errors['description']) ? ' aria-invalid="true" aria-describedby="profile-description-error"' : ''; ?>><?php echo hirepeak_esc((string) ($old['description'] ?? '')); ?></textarea>
                  <p class="field__hint">Up to 4000 characters.</p>
                  <?php echo hirepeak_error_slot($errors, 'description', 'profile'); ?>
                </div>
              </section>

              <section class="profile-section profile-section--grid" aria-labelledby="profile-section-contact">
                <h2 class="profile-section__title" id="profile-section-contact">Contact information</h2>

                <div class="field">
                  <label class="field__label" for="profile-contact_email">Contact email</label>
                  <input class="field__input" id="profile-contact_email" name="contact_email" type="email" inputmode="email"
                         maxlength="190" autocomplete="email"
                         placeholder="e.g. hiring@sprintvalley.example"
                         value="<?php echo hirepeak_esc((string) ($old['contact_email'] ?? '')); ?>"
                         <?php echo isset($errors['contact_email']) ? ' aria-invalid="true" aria-describedby="profile-contact_email-error"' : ''; ?>>
                  <p class="field__hint">A public email candidates can use to reach you.</p>
                  <?php echo hirepeak_error_slot($errors, 'contact_email', 'profile'); ?>
                </div>

                <div class="field">
                  <label class="field__label" for="profile-contact_phone">Contact phone</label>
                  <input class="field__input" id="profile-contact_phone" name="contact_phone" type="tel" inputmode="tel"
                         maxlength="30" autocomplete="tel"
                         placeholder="e.g. +962 7 9000 0000"
                         value="<?php echo hirepeak_esc((string) ($old['contact_phone'] ?? '')); ?>"
                         <?php echo isset($errors['contact_phone']) ? ' aria-invalid="true" aria-describedby="profile-contact_phone-error"' : ''; ?>>
                  <?php echo hirepeak_error_slot($errors, 'contact_phone', 'profile'); ?>
                </div>

                <div class="field field--static field--wide">
                  <span class="field__label" id="profile-account-email-label">Account email</span>
                  <p class="field__static-value" id="profile-account-email"><?php echo hirepeak_esc((string) ($user['email'] ?? '')); ?></p>
                  <p class="field__hint">Your login email can't be changed here.</p>
                </div>
              </section>

              <div class="profile-form__actions">
                <a class="btn btn--ghost" href="<?php echo hirepeak_url('employer/dashboard.php'); ?>">Cancel</a>
                <button class="btn btn--primary" type="submit">Save changes</button>
              </div>
            </form>
          </div>

          <aside class="profile-aside">
            <section class="profile-card" aria-labelledby="profile-completeness-title">
              <h2 class="profile-card__title" id="profile-completeness-title">Company completeness</h2>

              <?php $completeness = hirepeak_company_completeness($company); ?>
              <div class="completeness">
                <div class="completeness__top">
                  <p class="completeness__figure"><?php echo (int) $completeness['percent']; ?>%</p>
                  <p class="completeness__status">
                    <?php echo $completeness['percent'] === 100 ? 'Profile complete.' : 'complete'; ?>
                  </p>
                </div>

                <div
                  class="completeness__bar"
                  role="progressbar"
                  aria-labelledby="profile-completeness-title"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  aria-valuenow="<?php echo (int) $completeness['percent']; ?>"
                >
                  <span class="completeness__bar-fill" style="width: <?php echo (int) $completeness['percent']; ?>%"></span>
                </div>
                <p class="sr-only"><?php echo (int) $completeness['percent']; ?> percent complete</p>

                <?php if ($completeness['missing'] !== []): ?>
                  <p class="completeness__hint">A complete company profile helps candidates trust your listings.</p>
                  <ul class="completeness__todo">
                    <?php foreach ($completeness['missing'] as $label): ?>
                      <li><?php echo hirepeak_esc($label); ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p class="completeness__hint">Your company profile is fully filled in. Nice work.</p>
                <?php endif; ?>
              </div>
            </section>

            <section class="profile-card" aria-labelledby="profile-quicklinks-title">
              <h2 class="profile-card__title" id="profile-quicklinks-title">Quick links</h2>
              <ul class="profile-quicklinks">
                <li><a href="<?php echo hirepeak_url('employer/dashboard.php'); ?>">Dashboard</a></li>
                <li><a href="<?php echo hirepeak_url('index.php'); ?>">HirePeak home</a></li>
              </ul>
            </section>
          </aside>

        </div>

      <?php endif; ?>
    </div>
  </main>

  <?php require dirname(__DIR__, 2) . '/app/Views/footer.php'; ?>
</body>
</html>