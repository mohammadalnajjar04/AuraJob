<?php
declare(strict_types=1);

/**
 * HirePeak shared output helpers.
 */

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