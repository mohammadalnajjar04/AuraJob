<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/Core/config.php';
require dirname(__DIR__, 2) . '/app/Core/auth.php';
require dirname(__DIR__, 2) . '/app/Core/db.php';
require dirname(__DIR__, 2) . '/app/Support/html.php';
require dirname(__DIR__, 2) . '/app/Services/crypto.php';
require dirname(__DIR__, 2) . '/app/Services/cv.php';

$user = hirepeak_require_role('applicant');
$uid = (int) $user['id'];

/*
 * Applicant profile editor.
 *
 * - Identity always comes from the authenticated session (never query/body
 *   input), so an applicant can only ever read/write their own profile row.
 * - One profile row per user is guaranteed by the UNIQUE(user_id) constraint
 *   and by the create-then-update flow below.
 * - Phone is stored encrypted (AES-256-GCM via app/Services/crypto.php); legacy
 *   plaintext rows are displayed as-is and migrated to ciphertext on save.
 */

$errors = [];
$formError = '';
$flash = '';
$profile = null;
$hasError = false;

try {
    $db = hirepeak_db();

    if (isset($_GET['updated'], $_SESSION['profile_flash']) && $_SESSION['profile_flash'] !== '') {
        $flash = (string) $_SESSION['profile_flash'];
        unset($_SESSION['profile_flash']);
    }

    $stmt = $db->prepare(
        'SELECT id, professional_title, bio, phone, location, experience_years, skills, cv_path
         FROM applicant_profiles
         WHERE user_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    $defaults = [
        'professional_title' => $profile['professional_title'] ?? '',
        'bio'                => $profile['bio'] ?? '',
        'phone'              => hirepeak_phone_decrypt($profile['phone'] ?? null)
            ?? (string) ($profile['phone'] ?? ''),
        'location'           => $profile['location'] ?? '',
        'experience_years'   => $profile['experience_years'] ?? '',
        'skills'             => $profile['skills'] ?? '',
        'cv_path'            => $profile['cv_path'] ?? null,
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old = [
            'professional_title' => trim((string) ($_POST['professional_title'] ?? '')),
            'bio'                => trim((string) ($_POST['bio'] ?? '')),
            'phone'              => trim((string) ($_POST['phone'] ?? '')),
            'location'           => trim((string) ($_POST['location'] ?? '')),
            'experience_years'   => trim((string) ($_POST['experience_years'] ?? '')),
            'skills'             => trim((string) ($_POST['skills'] ?? '')),
        ];

        if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
            $formError = 'Your session expired. Please try again.';
            hirepeak_csrf_token(true);
        } else {
            if ($old['professional_title'] !== '' && hirepeak_utf8_length($old['professional_title']) > 100) {
                $errors['professional_title'] = 'Professional title must be 100 characters or fewer.';
            }

            if ($old['bio'] !== '' && hirepeak_utf8_length($old['bio']) > 4000) {
                $errors['bio'] = 'Bio must be 4000 characters or fewer.';
            }

            if ($old['phone'] !== '') {
                if (hirepeak_utf8_length($old['phone']) > 30 || preg_match('/^[0-9+\s().-]+$/', $old['phone']) !== 1) {
                    $errors['phone'] = 'Enter a valid phone number.';
                } else {
                    $digits = preg_replace('/\D/', '', $old['phone']);
                    if ($digits === null || strlen($digits) < 6 || strlen($digits) > 15) {
                        $errors['phone'] = 'Phone number must contain 6–15 digits.';
                    }
                }
            }

            if ($old['location'] !== '' && hirepeak_utf8_length($old['location']) > 120) {
                $errors['location'] = 'Location must be 120 characters or fewer.';
            }

            if ($old['experience_years'] !== '') {
                if (preg_match('/^\d{1,3}$/', $old['experience_years']) !== 1 || (int) $old['experience_years'] > 50) {
                    $errors['experience_years'] = 'Years of experience must be a number from 0–50.';
                }
            }

            if ($old['skills'] !== '' && hirepeak_utf8_length($old['skills']) > 4000) {
                $errors['skills'] = 'Skills must be 4000 characters or fewer.';
            }

            $cvUpload = $_FILES['cv_file'] ?? null;
            $cvUploadCheck = ['ok' => false, 'error' => ''];

            if (is_array($cvUpload)) {
                $cvUploadCheck = hirepeak_validate_cv_upload($cvUpload);
                if ($cvUploadCheck['error'] !== '') {
                    $errors['cv_file'] = $cvUploadCheck['error'];
                }
            }

            if (!$errors) {
                $professionalTitle = $old['professional_title'] !== '' ? $old['professional_title'] : null;
                $bio = $old['bio'] !== '' ? $old['bio'] : null;
                $phone = $old['phone'] !== '' ? hirepeak_phone_encrypt($old['phone']) : null;
                $location = $old['location'] !== '' ? $old['location'] : null;
                $experienceYears = $old['experience_years'] !== '' ? (int) $old['experience_years'] : null;
                $skills = $old['skills'] !== '' ? $old['skills'] : null;

                $previousCvPath = $profile !== null ? (string) ($profile['cv_path'] ?? '') : '';
                $cvPath = $previousCvPath !== '' ? $previousCvPath : null;
                $storedNewCv = null;
                $removeCv = isset($_POST['remove_cv']) && $_POST['remove_cv'] === '1';

                if (is_array($cvUpload) && ($cvUploadCheck['ok'] ?? false) === true) {
                    $storedNewCv = hirepeak_store_cv($cvUpload);
                    $cvPath = $storedNewCv;
                } elseif ($removeCv) {
                    $cvPath = null;
                }

                $created = false;

                try {

                if ($profile === null) {
                    try {
                        $stmt = $db->prepare(
                            'INSERT INTO applicant_profiles
                               (user_id, professional_title, bio, phone, location, experience_years, skills, cv_path)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->bind_param(
                            'issssiss',
                            $uid, $professionalTitle, $bio, $phone, $location, $experienceYears, $skills, $cvPath
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

                if ($profile !== null || !$created) {
                    $stmt = $db->prepare(
                        'UPDATE applicant_profiles
                         SET professional_title = ?, bio = ?, phone = ?, location = ?,
                             experience_years = ?, skills = ?, cv_path = ?
                         WHERE user_id = ?'
                    );
                    $stmt->bind_param(
                        'ssssissi',
                        $professionalTitle, $bio, $phone, $location, $experienceYears, $skills, $cvPath, $uid
                    );
                    $stmt->execute();
                    $stmt->close();
                }

                if ($previousCvPath !== '' && $previousCvPath !== (string) $cvPath) {
                    hirepeak_delete_cv($previousCvPath);
                }

                } catch (\Throwable $saveError) {
                    if ($storedNewCv !== null) {
                        hirepeak_delete_cv($storedNewCv);
                    }
                    throw $saveError;
                }

                $_SESSION['profile_flash'] = 'Your profile has been updated.';
                header('Location: ' . hirepeak_url('applicant/profile.php?updated=1'));
                exit;
            }

            $profile = $old; // re-render with the submitted values on validation failure
            $profile['cv_path'] = $defaults['cv_path'] ?? null;
        }
    } else {
        $old = $defaults;
    }
} catch (\Throwable $e) {
    error_log('[AuraJob] applicant profile load/save failed: ' . $e->getMessage());
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
  <title>My Profile — AuraJob</title>
  <meta name="description" content="Keep your AuraJob applicant profile up to date so employers can understand your background.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="/assets/images/aurajob-mark.png" type="image/png">
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
          <p class="dash-eyebrow">Applicant profile</p>
          <h1 class="dash-title">My Profile</h1>
          <p class="dash-text">Keep your information up to date so employers can understand your background.</p>
        </div>
        <a class="btn btn--ghost dash-head__cta" href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Back to dashboard</a>
      </header>

      <?php if ($hasError): ?>
        <section class="dash-alert" role="alert" aria-labelledby="profile-error-title">
          <h2 id="profile-error-title">We couldn't load your profile right now.</h2>
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
            <form class="profile-form" id="profile-form" action="<?php echo hirepeak_url('applicant/profile.php'); ?>" method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc(hirepeak_csrf_token()); ?>">

              <section class="profile-section profile-section--grid" aria-labelledby="profile-section-about">
                <h2 class="profile-section__title" id="profile-section-about">About you</h2>

                <div class="field">
                  <label class="field__label" for="profile-professional_title">Professional title</label>
                  <input class="field__input" id="profile-professional_title" name="professional_title" type="text"
                         maxlength="100" autocomplete="organization-title"
                         placeholder="e.g. Senior PHP Developer"
                         value="<?php echo hirepeak_esc((string) ($old['professional_title'] ?? '')); ?>"
                         <?php echo isset($errors['professional_title']) ? ' aria-invalid="true" aria-describedby="profile-professional_title-error"' : ''; ?>>
                  <p class="field__hint">Your headline, like "Senior PHP Developer".</p>
                  <?php echo hirepeak_error_slot($errors, 'professional_title', 'profile'); ?>
                </div>

                <div class="field">
                  <label class="field__label" for="profile-location">Location</label>
                  <input class="field__input" id="profile-location" name="location" type="text"
                         maxlength="120" autocomplete="address-level2"
                         placeholder="e.g. Amman, Jordan"
                         value="<?php echo hirepeak_esc((string) ($old['location'] ?? '')); ?>"
                         <?php echo isset($errors['location']) ? ' aria-invalid="true" aria-describedby="profile-location-error"' : ''; ?>>
                  <?php echo hirepeak_error_slot($errors, 'location', 'profile'); ?>
                </div>

                <div class="field field--wide">
                  <label class="field__label" for="profile-experience_years">Years of experience</label>
                  <input class="field__input field__input--number" id="profile-experience_years" name="experience_years"
                         type="number" inputmode="numeric" min="0" max="50" autocomplete="off"
                         placeholder="e.g. 4"
                         value="<?php echo hirepeak_esc((string) ($old['experience_years'] ?? '')); ?>"
                         <?php echo isset($errors['experience_years']) ? ' aria-invalid="true" aria-describedby="profile-experience_years-error"' : ''; ?>>
                  <?php echo hirepeak_error_slot($errors, 'experience_years', 'profile'); ?>
                </div>
              </section>

              <section class="profile-section" aria-labelledby="profile-section-summary">
                <h2 class="profile-section__title" id="profile-section-summary">Professional summary</h2>

                <div class="field">
                  <label class="field__label" for="profile-bio">Bio</label>
                  <textarea class="field__input field__textarea" id="profile-bio" name="bio" rows="6"
                            maxlength="4000" placeholder="A short professional summary of your experience and goals."<?php echo isset($errors['bio']) ? ' aria-invalid="true" aria-describedby="profile-bio-error"' : ''; ?>><?php echo hirepeak_esc((string) ($old['bio'] ?? '')); ?></textarea>
                  <p class="field__hint">Up to 4000 characters.</p>
                  <?php echo hirepeak_error_slot($errors, 'bio', 'profile'); ?>
                </div>

                <div class="field">
                  <label class="field__label" for="profile-skills">Skills</label>
                  <textarea class="field__input field__textarea" id="profile-skills" name="skills" rows="3"
                            maxlength="4000" placeholder="e.g. PHP, MySQL, JavaScript"<?php echo isset($errors['skills']) ? ' aria-invalid="true" aria-describedby="profile-skills-error"' : ''; ?>><?php echo hirepeak_esc((string) ($old['skills'] ?? '')); ?></textarea>
                  <p class="field__hint">Separate skills with commas.</p>
                  <?php echo hirepeak_error_slot($errors, 'skills', 'profile'); ?>
                </div>
              </section>

              <section class="profile-section profile-section--grid" aria-labelledby="profile-section-contact">
                <h2 class="profile-section__title" id="profile-section-contact">Contact information</h2>

                <div class="field">
                  <label class="field__label" for="profile-phone">Phone</label>
                  <input class="field__input" id="profile-phone" name="phone" type="tel" inputmode="tel"
                         maxlength="30" autocomplete="tel"
                         placeholder="e.g. +962 7 9000 0000"
                         value="<?php echo hirepeak_esc((string) ($old['phone'] ?? '')); ?>"
                         <?php echo isset($errors['phone']) ? ' aria-invalid="true" aria-describedby="profile-phone-error"' : ''; ?>>
                  <p class="field__hint">Stored encrypted for your privacy.</p>
                  <?php echo hirepeak_error_slot($errors, 'phone', 'profile'); ?>
                </div>

                <div class="field field--static">
                  <span class="field__label" id="profile-email-label">Email</span>
                  <p class="field__static-value" id="profile-email"><?php echo hirepeak_esc((string) ($user['email'] ?? '')); ?></p>
                  <p class="field__hint">Your login email can't be changed here.</p>
                </div>
              </section>

              <section class="profile-section cv-section" aria-labelledby="profile-section-cv">
                <div class="profile-section__head">
                  <div>
                    <h2 class="profile-section__title" id="profile-section-cv">Resume / CV</h2>
                    <p class="profile-section__text">Upload one PDF CV. Employers can download it only when reviewing an application you submitted.</p>
                  </div>
                  <?php if (!empty($profile['cv_path'])): ?>
                    <a class="btn btn--ghost btn--small" href="<?php echo hirepeak_url('applicant/cv.php'); ?>">Download current CV</a>
                  <?php endif; ?>
                </div>

                <div class="field">
                  <label class="field__label" for="profile-cv_file">CV file</label>
                  <input
                    class="field__input field__input--file"
                    id="profile-cv_file"
                    name="cv_file"
                    type="file"
                    accept="application/pdf,.pdf"
                    <?php echo isset($errors['cv_file']) ? ' aria-invalid="true" aria-describedby="profile-cv_file-hint profile-cv_file-error"' : ' aria-describedby="profile-cv_file-hint"'; ?>
                  >
                  <p class="field__hint" id="profile-cv_file-hint">PDF only, maximum 5 MB. Uploading a new CV replaces the current one.</p>
                  <?php echo hirepeak_error_slot($errors, 'cv_file', 'profile'); ?>
                </div>

                <?php if (!empty($profile['cv_path'])): ?>
                  <label class="check-row cv-section__remove">
                    <input type="checkbox" name="remove_cv" value="1">
                    <span>Remove current CV</span>
                  </label>
                <?php endif; ?>
              </section>

              <div class="profile-form__actions">
                <a class="btn btn--ghost" href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Cancel</a>
                <button class="btn btn--primary" type="submit">Save changes</button>
              </div>
            </form>
          </div>

          <aside class="profile-aside">
            <section class="profile-card" aria-labelledby="profile-completeness-title">
              <h2 class="profile-card__title" id="profile-completeness-title">Profile completeness</h2>

              <?php $completeness = hirepeak_profile_completeness($profile); ?>
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
                  <p class="completeness__hint">A complete profile helps employers find you.</p>
                  <ul class="completeness__todo">
                    <?php foreach ($completeness['missing'] as $label): ?>
                      <li><?php echo hirepeak_esc($label); ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p class="completeness__hint">Your profile is fully filled in. Nice work.</p>
                <?php endif; ?>
              </div>
            </section>

            <section class="profile-card" aria-labelledby="profile-quicklinks-title">
              <h2 class="profile-card__title" id="profile-quicklinks-title">Quick links</h2>
              <ul class="profile-quicklinks">
                <li><a href="<?php echo hirepeak_url('applicant/dashboard.php'); ?>">Dashboard</a></li>
                <li><a href="<?php echo hirepeak_url('applicant/applications.php'); ?>">My applications</a></li>
                <li><a href="<?php echo hirepeak_url('jobs.php'); ?>">Find jobs</a></li>
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