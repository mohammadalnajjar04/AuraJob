<?php
declare(strict_types=1);

/**
 * Shared job post/edit form: validation rules plus the exact HTML both
 * post-job.php and edit-job.php render, so the two editors can never drift.
 *
 * Callers include this file (it only defines functions) and then call
 * hirepeak_job_form_render() with the current values / errors / context.
 */

require_once __DIR__ . '/html.php';

const HIREPEAK_JOB_DESCRIPTION_MAX = 20000;
const HIREPEAK_JOB_REQUIREMENTS_MAX = 10000;

function hirepeak_job_field_defaults(): array
{
    return [
        'title'                => '',
        'category_id'          => '',
        'location'             => '',
        'employment_type'      => '',
        'work_mode'            => '',
        'experience_level'     => '',
        'salary_min'           => '',
        'salary_max'           => '',
        'description'          => '',
        'requirements'         => '',
        'application_deadline' => '',
    ];
}

function hirepeak_job_clean_input(): array
{
    $values = hirepeak_job_field_defaults();
    foreach ($values as $key => $_) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    return $values;
}

/**
 * @return array{errors: array<string,string>, values: array<string,mixed>}
 */
function hirepeak_job_validate(array $in, string $publish, \mysqli $db, string $today): array
{
    $errors = [];
    $enums = hirepeak_job_enum_values();

    if ($publish !== 'draft' && $publish !== 'active') {
        $errors['publish'] = 'Choose to save the job as a draft or publish it.';
    }

    if ($in['title'] === '') {
        $errors['title'] = 'Job title is required.';
    } elseif (hirepeak_utf8_length($in['title']) > 150) {
        $errors['title'] = 'Job title must be 150 characters or fewer.';
    }

    if ($in['location'] === '') {
        $errors['location'] = 'Location is required.';
    } elseif (hirepeak_utf8_length($in['location']) > 150) {
        $errors['location'] = 'Location must be 150 characters or fewer.';
    }

    if ($in['category_id'] === '' || preg_match('/^[1-9][0-9]*$/D', $in['category_id']) !== 1) {
        $errors['category_id'] = 'Choose a category.';
    } else {
        $categoryId = (int) $in['category_id'];
        $stmt = $db->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc() !== null;
        $stmt->close();
        if (!$exists) {
            $errors['category_id'] = 'Choose a valid category.';
        }
    }

    foreach (['employment_type', 'work_mode', 'experience_level'] as $enumField) {
        if ($in[$enumField] === '' || !in_array($in[$enumField], $enums[$enumField], true)) {
            $errors[$enumField] = 'Pick a value from the list.';
        }
    }

    if ($in['description'] === '') {
        $errors['description'] = 'Describe the role — candidates need to know what it involves.';
    } elseif (hirepeak_utf8_length($in['description']) > HIREPEAK_JOB_DESCRIPTION_MAX) {
        $errors['description'] = 'Description must be ' . HIREPEAK_JOB_DESCRIPTION_MAX . ' characters or fewer.';
    }

    if ($in['requirements'] !== '' && hirepeak_utf8_length($in['requirements']) > HIREPEAK_JOB_REQUIREMENTS_MAX) {
        $errors['requirements'] = 'Requirements must be ' . HIREPEAK_JOB_REQUIREMENTS_MAX . ' characters or fewer.';
    }

    $salaryMin = null;
    $salaryMax = null;
    foreach (['salary_min', 'salary_max'] as $field) {
        if ($in[$field] === '') {
            continue;
        }
        $raw = (string) $in[$field];
        if (preg_match('/^\d{1,10}(\.\d{1,2})?$/', $raw) !== 1 || (float) $raw < 0) {
            $errors[$field] = 'Enter a valid salary in JOD with at most 2 decimal places.';
            continue;
        }
        $number = (float) $raw;
        if ($number > 999999999999.99) {
            $errors[$field] = 'That salary is too large.';
            continue;
        }
        if ($field === 'salary_min') {
            $salaryMin = $raw;
        } else {
            $salaryMax = $raw;
        }
    }

    if ($salaryMin !== null && $salaryMax !== null && (float) $salaryMax < (float) $salaryMin) {
        $errors['salary_max'] = 'Maximum salary must be at least the minimum.';
    }

    $deadline = null;
    if ($in['application_deadline'] !== '') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $in['application_deadline']) !== 1) {
            $errors['application_deadline'] = 'Enter a valid date.';
        } else {
            $parsed = \DateTime::createFromFormat('Y-m-d', $in['application_deadline']);
            if ($parsed === false || $parsed->format('Y-m-d') !== $in['application_deadline']) {
                $errors['application_deadline'] = 'Enter a valid date.';
            } elseif ($publish === 'active' && $in['application_deadline'] < $today) {
                $errors['application_deadline'] = 'When publishing, the deadline must be in the future.';
            } else {
                $deadline = $in['application_deadline'];
            }
        }
    }

    $values = [
        'title'                => $in['title'],
        'category_id'          => $in['category_id'] !== '' ? (int) $in['category_id'] : 0,
        'location'             => $in['location'],
        'employment_type'      => in_array($in['employment_type'], $enums['employment_type'], true) ? $in['employment_type'] : '',
        'work_mode'            => in_array($in['work_mode'], $enums['work_mode'], true) ? $in['work_mode'] : '',
        'experience_level'     => in_array($in['experience_level'], $enums['experience_level'], true) ? $in['experience_level'] : '',
        'salary_min'           => $salaryMin,
        'salary_max'           => $salaryMax,
        'description'          => $in['description'],
        'requirements'         => $in['requirements'] !== '' ? $in['requirements'] : null,
        'application_deadline' => $deadline,
    ];

    return ['errors' => $errors, 'values' => $values];
}

/**
 * Renders the complete post/edit job form. $ctx keys:
 *   action, csrf, mode ('post'|'edit'), id, values, errors,
 *   categories, currentStatus.
 */
function hirepeak_job_form_render(array $ctx): string
{
    $mode = (string) ($ctx['mode'] ?? 'post');
    $action = hirepeak_url((string) ($ctx['action'] ?? 'employer/post-job.php'));
    $csrf = (string) ($ctx['csrf'] ?? '');
    $v = $ctx['values'] ?? hirepeak_job_field_defaults();
    $e = $ctx['errors'] ?? [];
    $cats = $ctx['categories'] ?? [];
    $currentStatus = (string) ($ctx['currentStatus'] ?? '');

    $error = static function (string $field) use ($e): string {
        return hirepeak_error_slot($e, $field, 'job');
    };
    $invalid = static function (string $field) use ($e): string {
        return isset($e[$field]) ? ' aria-invalid="true" aria-describedby="job-' . $field . '-error"' : '';
    };
    $value = static function (string $field) use ($v): string {
        return hirepeak_esc((string) ($v[$field] ?? ''));
    };

    $makeSelect = static function (array $options, string $label, string $name, string $id, string $placeholder, $selected, $errorFn, $invalidFn): string {
        $selStr = (string) $selected;
        $matched = false;
        $opts = '';
        foreach ($options as $oval => $olabel) {
            $isSel = $oval === $selected || (string) $oval === $selStr;
            if ($isSel) {
                $matched = true;
            }
            $opts .= '<option value="' . hirepeak_esc((string) $oval) . '"'
                . ($isSel ? ' selected' : '')
                . '>' . hirepeak_esc($olabel) . '</option>';
        }
        $opts = '<option value="" disabled' . ($selStr === '' || !$matched ? ' selected' : '') . '>' . hirepeak_esc($placeholder) . '</option>' . $opts;
        return '<div class="field">'
            . '<label class="field__label" for="' . $id . '">' . hirepeak_esc($label) . '</label>'
            . '<select class="field__input field__select" id="' . $id . '" name="' . $name . '" required' . $invalidFn($name) . '>' . $opts . '</select>'
            . $errorFn($name)
            . '</div>';
    };

    $enums = hirepeak_job_enum_values();

    $categoryOptions = [];
    foreach ($cats as $cat) {
        $categoryOptions[$cat['id']] = $cat['name'];
    }
    $employmentOptions = [];
    foreach ($enums['employment_type'] as $opt) {
        $employmentOptions[$opt] = hirepeak_employment_type_label($opt);
    }
    $modeOptions = [];
    foreach ($enums['work_mode'] as $opt) {
        $modeOptions[$opt] = hirepeak_work_mode_label($opt);
    }
    $experiencedOptions = [];
    foreach ($enums['experience_level'] as $opt) {
        $experiencedOptions[$opt] = hirepeak_experience_level_label($opt);
    }

    $statusCopy = '';
    if ($mode === 'edit') {
        $badge = hirepeak_esc(hirepeak_job_status_label($currentStatus));
        if ($currentStatus === 'closed') {
            $statusCopy = 'This job is currently marked as Closed. Publishing it will reopen it to new applications.';
        } elseif ($currentStatus === 'draft') {
            $statusCopy = 'This job is currently a draft and is not visible to candidates. Publishing it will list it in Find Jobs.';
        } else {
            $statusCopy = 'This job is currently live in Find Jobs. Saving it keeps it live.';
        }
    } else {
        $statusCopy = 'Drafts stay private on your My Jobs page. Publishing a job lists it in Find Jobs for candidates.';
    }

    $html = '<form class="profile-form job-form" action="' . hirepeak_esc($action) . '" method="post">'
        . '<input type="hidden" name="csrf_token" value="' . hirepeak_esc($csrf) . '">';

    $html .= '<section class="profile-section job-form__section job-form__section--basic" aria-labelledby="job-section-basic">'
        . '<h2 class="profile-section__title" id="job-section-basic">1. Basic information</h2>'
        . '<div class="field">'
        . '<label class="field__label" for="job-title">Job title</label>'
        . '<input class="field__input" id="job-title" name="title" type="text" maxlength="150"'
        . ' autocomplete="off" required placeholder="e.g. Backend Engineer"' . $invalid('title')
        . ' value="' . $value('title') . '">'
        . $error('title')
        . '</div>'
        . $makeSelect($categoryOptions, 'Category', 'category_id', 'job-category_id', 'Select a category', $v['category_id'] ?? '', $error, $invalid)
        . '</section>';

    $html .= '<section class="profile-section job-form__section job-form__section--role" aria-labelledby="job-section-role">'
        . '<h2 class="profile-section__title" id="job-section-role">2. Role details</h2>'
        . '<div class="field job-form__field--wide">'
        . '<label class="field__label" for="job-location">Location</label>'
        . '<input class="field__input" id="job-location" name="location" type="text" maxlength="150"'
        . ' autocomplete="address-level2" required placeholder="e.g. Amman, Jordan"' . $invalid('location')
        . ' value="' . $value('location') . '">'
        . $error('location')
        . '</div>'
        . $makeSelect($employmentOptions, 'Employment type', 'employment_type', 'job-employment_type', 'Select an employment type', $v['employment_type'] ?? '', $error, $invalid)
        . $makeSelect($experiencedOptions, 'Experience level', 'experience_level', 'job-experience_level', 'Select an experience level', $v['experience_level'] ?? '', $error, $invalid)
        . '</section>';

    $html .= '<section class="profile-section job-form__section job-form__section--arrangement" aria-labelledby="job-section-arrangement">'
        . '<h2 class="profile-section__title" id="job-section-arrangement">3. Work arrangement</h2>'
        . $makeSelect($modeOptions, 'Work mode', 'work_mode', 'job-work_mode', 'Select a work mode', $v['work_mode'] ?? '', $error, $invalid)
        . '</section>';

    $html .= '<section class="profile-section job-form__section job-form__section--comp" aria-labelledby="job-section-comp">'
        . '<h2 class="profile-section__title" id="job-section-comp">4. Compensation</h2>'
        . '<p class="field__hint">Optional. Ranges are shown to candidates as JOD per month.</p>'
        . '<div class="field-grid">'
        . '<div class="field">'
        . '<label class="field__label" for="job-salary_min">Minimum salary (JOD)</label>'
        . '<input class="field__input" id="job-salary_min" name="salary_min" type="number" min="0" step="0.01" inputmode="decimal"'
        . ' placeholder="e.g. 500"' . $invalid('salary_min')
        . ' value="' . $value('salary_min') . '">'
        . $error('salary_min')
        . '</div>'
        . '<div class="field">'
        . '<label class="field__label" for="job-salary_max">Maximum salary (JOD)</label>'
        . '<input class="field__input" id="job-salary_max" name="salary_max" type="number" min="0" step="0.01" inputmode="decimal"'
        . ' placeholder="e.g. 900"' . $invalid('salary_max')
        . ' value="' . $value('salary_max') . '">'
        . $error('salary_max')
        . '</div>'
        . '</div>'
        . '</section>';

    $html .= '<section class="profile-section job-form__section job-form__section--description" aria-labelledby="job-section-description">'
        . '<h2 class="profile-section__title" id="job-section-description">5. Description</h2>'
        . '<div class="field">'
        . '<label class="field__label" for="job-description">Role description</label>'
        . '<textarea class="field__input field__textarea" id="job-description" name="description" rows="6"'
        . ' maxlength="' . HIREPEAK_JOB_DESCRIPTION_MAX . '" required placeholder="What will the candidate do?"'
        . $invalid('description') . '>' . $value('description') . '</textarea>'
        . '<p class="field__hint">Up to ' . HIREPEAK_JOB_DESCRIPTION_MAX . ' characters.</p>'
        . $error('description')
        . '</div>'
        . '<div class="field">'
        . '<label class="field__label" for="job-requirements">Requirements</label>'
        . '<textarea class="field__input field__textarea" id="job-requirements" name="requirements" rows="4"'
        . ' maxlength="' . HIREPEAK_JOB_REQUIREMENTS_MAX . '" placeholder="Skills and experience you are looking for."'
        . $invalid('requirements') . '>' . $value('requirements') . '</textarea>'
        . '<p class="field__hint">Optional. Up to ' . HIREPEAK_JOB_REQUIREMENTS_MAX . ' characters.</p>'
        . $error('requirements')
        . '</div>'
        . '</section>';

    $html .= '<section class="profile-section job-form__section job-form__section--publish" aria-labelledby="job-section-publish">'
        . '<h2 class="profile-section__title" id="job-section-publish">6. Publishing</h2>'
        . '<div class="field">'
        . '<label class="field__label" for="job-application_deadline">Application deadline</label>'
        . '<input class="field__input" id="job-application_deadline" name="application_deadline" type="date"'
        . $invalid('application_deadline')
        . ' value="' . $value('application_deadline') . '">'
        . '<p class="field__hint">Optional. Applications close at the end of this day. Past dates can only be saved with drafts.</p>'
        . $error('application_deadline')
        . '</div>'
        . '<div class="field">'
        . '<span class="field__label" id="job-status-label">Job status</span>'
        . ($mode === 'edit' ? '<p class="field__static-value">' . hirepeak_job_status_badge($currentStatus) . '</p>' : '')
        . '<p class="field__hint">' . hirepeak_esc($statusCopy) . '</p>'
        . $error('publish')
        . '</div>'
        . '</section>';

    $html .= '<div class="profile-form__actions job-form__actions">'
        . '<a class="btn btn--ghost" href="' . hirepeak_url('employer/my-jobs.php') . '">Cancel</a>'
        . '<button class="btn btn--outline" type="submit" name="publish" value="draft">Save Draft</button>'
        . '<button class="btn btn--primary" type="submit" name="publish" value="active">Publish Job</button>'
        . '</div>'
        . '</form>';

    return $html;
}