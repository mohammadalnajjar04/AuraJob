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
 * Server-side Apply Now state for a job detail page. Authorization is based on
 * the authenticated session role - never on client-side state.
 *
 * @return array{cta: string, note: string}
 */
function hirepeak_apply_cta(): array
{
    $user = hirepeak_user();

    if ($user === null) {
        return [
            'cta' => '<a class="btn btn--primary" href="login.php">Apply Now</a>',
            'note' => 'Sign in to apply for this role.',
        ];
    }

    if (($user['role'] ?? '') === 'employer') {
        return [
            'cta' => '<button class="btn btn--primary" type="button" disabled>Apply Now</button>',
            'note' => 'Employer accounts post jobs on HirePeak and can\'t apply for roles.',
        ];
    }

    return [
        'cta' => '<button class="btn btn--primary" type="button" disabled>Apply Now</button>',
        'note' => 'Application flow coming next.',
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