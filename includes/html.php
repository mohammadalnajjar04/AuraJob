<?php
declare(strict_types=1);

/**
 * HirePeak shared output helpers.
 */

require_once __DIR__ . '/auth.php';

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