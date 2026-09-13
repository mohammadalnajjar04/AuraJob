<?php
declare(strict_types=1);

/**
 * HirePeak shared output helpers.
 */

require_once __DIR__ . '/auth.php';

/**
 * Escapes a value for safe HTML output. Shared by the header and all pages;
 * every piece of user-controlled output goes through this.
 */
function hirepeak_esc(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Profile completeness definition — the single source of truth for which
 * applicant_profiles fields count toward the displayed percentage.
 *
 * Field labels double as "Add your ..." action hints for the applicant. The
 * dashboard and the profile page both use this so the number never diverges.
 *
 * @return array<string, string> profile column => missing-field hint
 */
function hirepeak_profile_completeness_fields(): array
{
    return [
        'professional_title' => 'Add your professional title',
        'bio'                => 'Add your bio',
        'phone'              => 'Add your phone number',
        'location'           => 'Add your location',
        'experience_years'   => 'Add your years of experience',
        'skills'             => 'Add your skills',
    ];
}

/**
 * Computes profile completeness from an applicant_profiles row (or null when
 * no row exists yet).
 *
 * @return array{percent: int, filled: int, total: int, missing: list<string>}
 */
function hirepeak_profile_completeness(?array $profile): array
{
    $fields = hirepeak_profile_completeness_fields();
    $filled = 0;
    $missing = [];

    foreach ($fields as $field => $missingLabel) {
        if ($profile !== null) {
            $value = $profile[$field] ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                $filled++;
                continue;
            }
        }
        $missing[] = $missingLabel;
    }

    $total = count($fields);
    $percent = $total > 0 ? (int) round($filled / $total * 100) : 0;

    return [
        'percent' => $percent,
        'filled'  => $filled,
        'total'   => $total,
        'missing' => $missing,
    ];
}

/**
 * Company profile completeness definition — which companies columns count
 * toward the displayed percentage. The employer dashboard and the company
 * profile page share this so the number never diverges.
 *
 * @return array<string, string> companies column => missing-field hint
 */
function hirepeak_company_completeness_fields(): array
{
    return [
        'name'          => 'Add your company name',
        'industry'      => 'Add your industry',
        'location'      => 'Add your location',
        'website'       => 'Add your company website',
        'description'   => 'Add a company description',
        'contact_email' => 'Add a contact email',
        'contact_phone' => 'Add a contact phone',
    ];
}

/**
 * Computes company profile completeness from a companies row (or null when no
 * row exists yet). The name column is NOT NULL once a row exists, so it only
 * ever shows as missing before the company is created.
 *
 * @return array{percent: int, filled: int, total: int, missing: list<string>}
 */
function hirepeak_company_completeness(?array $company): array
{
    $fields = hirepeak_company_completeness_fields();
    $filled = 0;
    $missing = [];

    foreach ($fields as $field => $missingLabel) {
        if ($company !== null) {
            $value = $company[$field] ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                $filled++;
                continue;
            }
        }
        $missing[] = $missingLabel;
    }

    $total = count($fields);
    $percent = $total > 0 ? (int) round($filled / $total * 100) : 0;

    return [
        'percent' => $percent,
        'filled'  => $filled,
        'total'   => $total,
        'missing' => $missing,
    ];
}

/**
 * Renders a hidden field-error slot, or a visible one when the field is in
 * $errors. The element id is "<idPrefix>-<field>-error" so client-side
 * validation and aria-describedby keep pointing at the same element.
 */
function hirepeak_error_slot(array $errors, string $field, string $idPrefix, string $extraClass = ''): string
{
    $classes = trim('field__error ' . $extraClass);
    $id = $idPrefix . '-' . $field . '-error';

    if (!isset($errors[$field]) || $errors[$field] === '') {
        return '<p class="' . $classes . '" id="' . $id . '" hidden></p>';
    }

    $message = htmlspecialchars((string) $errors[$field], ENT_QUOTES, 'UTF-8');
    return '<p class="' . $classes . '" id="' . $id . '" role="alert">' . $message . '</p>';
}

/**
 * Server-side Apply Now state for a job detail page. Every branch is derived
 * from the stored job in the database plus the authenticated session role —
 * never from client-side state. Closed/draft/expired jobs, guest visitors,
 * employer accounts, and applicants who already applied each get a distinct,
 * honest CTA.
 *
 * @param int $jobId the job whose apply state is being rendered
 *
 * @return array{cta: string, note: string}
 */
function hirepeak_apply_cta(int $jobId): array
{
    require_once __DIR__ . '/db.php';

    $dbDown = false;
    $job = null;

    try {
        $db = hirepeak_db();
        $stmt = $db->prepare('SELECT id, status, application_deadline, (status = "active" AND (application_deadline IS NULL OR application_deadline >= CURDATE())) AS is_open FROM jobs WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $jobId);
        $stmt->execute();
        $job = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (\Throwable $e) {
        error_log('[hirepeak] apply CTA job lookup failed: ' . $e->getMessage());
        $dbDown = true;
    }

    if ($dbDown) {
        return [
            'cta' => '<button class="btn btn--primary" type="button" disabled>Apply Now</button>',
            'note' => 'Applications are temporarily unavailable. Please try again later.',
        ];
    }

    if ($job === null) {
        return [
            'cta' => '<button class="btn btn--primary" type="button" disabled>Apply Now</button>',
            'note' => 'This job is no longer available.',
        ];
    }

    $status = (string) ($job['status'] ?? '');
    $deadline = (string) ($job['application_deadline'] ?? '');
    $closed = !(bool) ($job['is_open'] ?? false);

    if ($closed) {
        $note = $status === 'draft'
            ? 'This job isn\'t accepting applications yet.'
            : 'Applications are closed for this role.';
        return [
            'cta' => '<button class="btn btn--primary" type="button" disabled>Apply Now</button>',
            'note' => $note,
        ];
    }

    $user = hirepeak_user();

    if ($user === null) {
        return [
            'cta' => '<a class="btn btn--primary" href="login.php">Sign in to Apply</a>',
            'note' => 'Sign in to apply for this role.',
        ];
    }

    if (($user['role'] ?? '') === 'employer') {
        return [
            'cta' => '<button class="btn btn--primary" type="button" disabled>Apply Now</button>',
            'note' => 'Employer accounts post jobs on HirePeak and can\'t apply for roles.',
        ];
    }

    $existing = null;

    try {
        $uid = (int) $user['id'];
        $db = hirepeak_db();
        $stmt = $db->prepare('SELECT id FROM applications WHERE applicant_id = ? AND job_id = ? LIMIT 1');
        $stmt->bind_param('ii', $uid, $jobId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (\Throwable $e) {
        error_log('[hirepeak] apply CTA existing-application check failed: ' . $e->getMessage());
        $existing = null;
    }

    if ($existing !== null) {
        return [
            'cta' => '<a class="btn btn--primary" href="application-tracking.php?id=' . (int) $existing['id'] . '">Track Application</a>',
            'note' => 'You\'ve already applied for this role.',
        ];
    }

    return [
        'cta' => '<a class="btn btn--primary" href="apply.php?id=' . $jobId . '">Apply Now</a>',
        'note' => $deadline !== ''
            ? 'Applications close on ' . date('M j, Y', strtotime($deadline)) . '.'
            : 'Submitting sends your application straight to the employer.',
    ];
}

/**
 * Centralized application status vocabulary.
 *
 * Keys are the exact ENUM values stored in applications.status
 * (see database/schema.sql). Later pages (My Applications, tracking) reuse
 * these labels so every status label/badge stays consistent.
 *
 * @return array<string, string> status => user-facing label
 */
function hirepeak_application_statuses(): array
{
    return [
        'applied'      => 'Applied',
        'under_review' => 'Under Review',
        'interview'    => 'Interview',
        'accepted'     => 'Accepted',
        'rejected'     => 'Rejected',
    ];
}

/**
 * User-facing label for a stored application status. Falls back safely to the
 * raw value when it is not a known status so output never breaks.
 */
function hirepeak_application_status_label(string $status): string
{
    $map = hirepeak_application_statuses();
    return $map[$status] ?? $status;
}

/**
 * CSS modifier class for a stored application status. The label is always
 * rendered with the badge — color is never the only signal.
 */
function hirepeak_application_status_class(string $status): string
{
    return match ($status) {
        'applied'      => 'status-badge--applied',
        'under_review' => 'status-badge--review',
        'interview'    => 'status-badge--interview',
        'accepted'     => 'status-badge--accepted',
        'rejected'     => 'status-badge--rejected',
        default        => 'status-badge--neutral',
    };
}

/**
 * Escaped status badge markup for the given stored status.
 */
function hirepeak_application_status_badge(string $status): string
{
    $label = hirepeak_esc(hirepeak_application_status_label($status));
    $class = hirepeak_application_status_class($status);
    return '<span class="status-badge ' . $class . '">' . $label . '</span>';
}

/**
 * Job status badge mapping for the employer dashboard.
 */
function hirepeak_job_status_class(string $status): string
{
    return match ($status) {
        'active' => 'status-badge--active',
        'closed' => 'status-badge--closed',
        'draft'  => 'status-badge--draft',
        default  => 'status-badge--neutral',
    };
}

function hirepeak_job_status_label(string $status): string
{
    return match ($status) {
        'active' => 'Active',
        'closed' => 'Closed',
        'draft'  => 'Draft',
        default  => ucfirst($status),
    };
}

function hirepeak_job_status_badge(string $status): string
{
    $label = hirepeak_esc(hirepeak_job_status_label($status));
    $class = hirepeak_job_status_class($status);
    return '<span class="status-badge ' . $class . '">' . $label . '</span>';
}

/**
 * Exact ENUM value sets from database/schema.sql for the jobs table. The post
 * and edit forms validate against (and render their options from) these exact
 * lists — anything else is rejected server-side, never emitted.
 *
 * @return array<string, list<string>> enum column => allowed values
 */
function hirepeak_job_enum_values(): array
{
    return [
        'employment_type'  => ['full_time', 'part_time', 'contract', 'internship', 'freelance'],
        'work_mode'        => ['onsite', 'remote', 'hybrid'],
        'experience_level' => ['entry', 'junior', 'mid', 'senior', 'lead'],
        'status'           => ['active', 'closed', 'draft'],
    ];
}

/**
 * Space-separated lowercase keyword tokens for the public job card
 * data-keywords attribute (drives the client-side search box).
 */
function hirepeak_job_keywords(array $job): string
{
    $sources = [
        (string) ($job['title'] ?? ''),
        (string) ($job['company_name'] ?? ''),
        (string) ($job['category_name'] ?? ''),
        (string) ($job['location'] ?? ''),
        (string) ($job['description'] ?? ''),
        (string) ($job['requirements'] ?? ''),
    ];

    $tokens = [];
    foreach ($sources as $source) {
        $words = preg_split('/[^A-Za-z0-9]+/', strtolower($source), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($words as $word) {
            if ($word !== '' && !in_array($word, $tokens, true)) {
                $tokens[] = $word;
            }
            if (count($tokens) >= 60) {
                break 2;
            }
        }
    }

    return implode(' ', $tokens);
}

/**
 * Server-side "today" (the MySQL host's calendar date) so every past-deadline
 * comparison on post/edit/public pages uses exactly the same boundary the
 * "applications open" SQL check uses (Sprint 6 fix).
 */
function hirepeak_today_sql(\mysqli $db): string
{
    $result = $db->query('SELECT CURDATE() AS today');
    $row = $result->fetch_assoc();
    $result->close();
    $today = (string) ($row['today'] ?? '');
    return $today !== '' ? $today : date('Y-m-d');
}

/**
 * All categories as [id, name] ordered by name, for the job form selects.
 *
 * @return list<array{id: int, name: string}>
 */
function hirepeak_categories(\mysqli $db): array
{
    $result = $db->query('SELECT id, name FROM categories ORDER BY name ASC');
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $list[] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
    }
    $result->close();
    return $list;
}

/**
 * Calm, indexed-404 page for jobs that are missing, unreachable as a draft,
 * or not owned by the requesting employer. Emits a complete response and exits.
 */
function hirepeak_job_not_found(): void
{
    $pageActive = '';
    $pageScripts = ['src/js/main.js'];
    http_response_code(404);
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job not found — HirePeak</title>
  <meta name="description" content="We couldn't find this job.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='1' y='1' width='22' height='22' rx='7' fill='%234F46E5'/%3E%3Cpath d='M12 4.8 18 16H6Z' fill='%23FFFFFF' stroke='%23FFFFFF' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M12 6.6 8.8 16H5.9Z' fill='%23EEF2FF' stroke='%23EEF2FF' stroke-width='3' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="src/css/style.css">
</head>
<body>
  <?php require __DIR__ . '/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main" class="dash-main">
    <div class="container">
      <section class="empty-state">
        <div class="empty-state__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <path d="m15 9-6 6"/>
            <path d="m9 9 6 6"/>
          </svg>
        </div>
        <h1 class="empty-state__title">Job not found</h1>
        <p class="empty-state__text">We couldn't find this job. It may have been removed, or the link may be incorrect.</p>
        <div class="empty-state__actions">
          <a class="btn btn--primary" href="jobs.php">Find Jobs</a>
        </div>
      </section>
    </div>
  </main>

  <?php require __DIR__ . '/footer.php'; ?>
</body>
</html><?php
    exit;
}

/**
 * Maps the compact ENUM job attributes to user-facing labels.
 */
function hirepeak_employment_type_label(string $type): string
{
    return match ($type) {
        'full_time'  => 'Full Time',
        'part_time'  => 'Part Time',
        'contract'   => 'Contract',
        'internship' => 'Internship',
        'freelance'  => 'Freelance',
        default      => $type,
    };
}

function hirepeak_work_mode_label(string $mode): string
{
    return match ($mode) {
        'onsite' => 'On-site',
        'remote' => 'Remote',
        'hybrid' => 'Hybrid',
        default  => $mode,
    };
}

function hirepeak_experience_level_label(string $level): string
{
    return match ($level) {
        'entry' => 'Entry Level',
        'junior' => 'Junior',
        'mid'    => 'Mid Level',
        'senior' => 'Senior',
        'lead'   => 'Lead',
        default  => $level,
    };
}

/**
 * Deterministic initials from a company/job name for the letter logo.
 */
function hirepeak_initials(string $name): string
{
    $clean = trim($name);
    if ($clean === '') {
        return 'HP';
    }

    $words = preg_split('/\s+/', str_replace(['-', '_', '&', '.', ','], ' ', $clean), -1, PREG_SPLIT_NO_EMPTY);
    if ($words === false || count($words) === 0) {
        return 'HP';
    }

    // mbstring isn't guaranteed on shared/minimal PHP installs; fall back to
    // plain ASCII casing so the letter logos never hard-crash the page.
    $firstChar = static function (string $word): string {
        return \function_exists('mb_substr')
            ? mb_substr($word, 0, 1, 'UTF-8')
            : substr($word, 0, 1);
    };
    $upper = static function (string $char): string {
        return \function_exists('mb_strtoupper')
            ? mb_strtoupper($char, 'UTF-8')
            : strtoupper($char);
    };

    $letters = $upper($firstChar((string) $words[0]));
    if (count($words) > 1) {
        $letters .= $upper($firstChar((string) $words[1]));
    }

    return $letters;
}

/**
 * String length for validation limits. Uses mbstring when available and falls
 * back to plain strlen so the profile editor never hard-crashes on minimal
 * PHP installs without the mbstring extension.
 */
function hirepeak_utf8_length(string $value): int
{
    return \function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/**
 * Salary range string for a job row (expects salary_min / salary_max keys).
 * "Salary not disclosed" when both are absent. Rendered with JOD to stay
 * consistent with the rest of the product; locale/currency support is later.
 */
function hirepeak_salary_range(?string $min, ?string $max): string
{
    $minV = $min !== null && $min !== '' ? (float) $min : null;
    $maxV = $max !== null && $max !== '' ? (float) $max : null;

    if ($minV === null && $maxV === null) {
        return 'Salary not disclosed';
    }

    $fmt = static function (float $n): string {
        return number_format($n, $n === floor($n) ? 0 : 2);
    };

    if ($maxV === null || $maxV === $minV) {
        $value = $fmt($minV ?? $maxV);
        return ($minV === null ? 'Up to ' : '') . $value . ' JOD / month';
    }

    return $fmt($minV) . ' &ndash; ' . $fmt($maxV) . ' JOD / month';
}

/**
 * Short relative time label, e.g. "2 days ago". Falls back to an absolute
 * date for anything older than a month so preview rows stay readable.
 */
function hirepeak_relative_time(?string $timestamp): string
{
    if ($timestamp === null || $timestamp === '') {
        return '';
    }

    $time = strtotime($timestamp);
    if ($time === false) {
        return '';
    }

    $diff = time() - $time;

    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return $m === 1 ? '1 minute ago' : $m . ' minutes ago';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return $h === 1 ? '1 hour ago' : $h . ' hours ago';
    }
    if ($diff < 2592000) {
        $d = (int) floor($diff / 86400);
        return $d === 1 ? '1 day ago' : $d . ' days ago';
    }

    return date('M j, Y', $time);
}

/**
 * Compact hired job card used by dashboard previews (Saved Jobs, Recommended).
 *
 * @param array $job expects keys: id, title, company_name, location,
 *                    employment_type, work_mode, salary_min, salary_max,
 *                    created_at, and optionally status (for saved jobs).
 */
function hirepeak_dashboard_job_card(array $job): string
{
    $id = (int) ($job['id'] ?? 0);
    $title = hirepeak_esc((string) ($job['title'] ?? 'Untitled role'));
    $company = hirepeak_esc((string) ($job['company_name'] ?? ''));
    $location = hirepeak_esc((string) ($job['location'] ?? ''));
    $companyLine = trim($company . ($location !== '' ? ' &middot; ' . $location : ''));
    $type = hirepeak_employment_type_label((string) ($job['employment_type'] ?? ''));
    $mode = hirepeak_work_mode_label((string) ($job['work_mode'] ?? ''));
    $salary = hirepeak_salary_range(
        isset($job['salary_min']) ? (string) $job['salary_min'] : '',
        isset($job['salary_max']) ? (string) $job['salary_max'] : ''
    );
    $posted = hirepeak_relative_time(isset($job['created_at']) ? (string) $job['created_at'] : null);
    $logo = hirepeak_esc(hirepeak_initials($company !== '' ? $company : $title));
    $closed = isset($job['status']) && $job['status'] !== 'active';
    $url = 'job-details.php?id=' . $id;

    $chips = '';
    if (!$closed) {
        if ($type !== '') {
            $chips .= '<li class="chip">' . hirepeak_esc($type) . '</li>';
        }
        if ($mode !== '') {
            $chips .= '<li class="chip">' . hirepeak_esc($mode) . '</li>';
        }
    }

    $closedChip = $closed ? '<li class="chip chip--closed">Closed</li>' : '';

    return '<article class="mini-card">'
        . '<div class="mini-card__top">'
        . '<span class="mini-card__logo" aria-hidden="true">' . $logo . '</span>'
        . '</div>'
        . '<h3 class="mini-card__title"><a href="' . $url . '">' . $title . '</a></h3>'
        . '<p class="mini-card__company">' . $companyLine . '</p>'
        . '<ul class="mini-card__chips">' . $chips . $closedChip . '</ul>'
        . '<p class="mini-card__salary">' . $salary . '</p>'
        . '<p class="mini-card__posted">' . ($closed ? 'No longer accepting applications' : (($posted !== '' ? 'Posted ' . $posted : '&nbsp;'))) . '</p>'
        . '<a class="btn btn--outline btn--sm mini-card__cta" href="' . $url . '">View Job</a>'
        . '</article>';
}