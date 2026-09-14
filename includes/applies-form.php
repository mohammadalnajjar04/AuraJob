<?php
declare(strict_types=1);

/**
 * Shared application-detail helpers for employer applicant management.
 *
 * Loading this file does nothing by itself — it only declares functions. Both
 * job-applicants.php and employer-application.php render application rows and
 * status controls through these helpers so label text, allowed transitions,
 * CSRF validation, and UPDATE ownership stay defined in exactly one place.
 *
 * IMPORTANT — the schema (database/schema.sql) defines
 *   applications.status ENUM('applied','under_review','interview','accepted','rejected')
 * This include only ever writes one of those real ENUM values, and the only
 * transitions offered to an employer are the whitelisted, forward-only steps
 * below. An employer can never set an arbitrary status value, and the write is
 * always scoped (via JOIN) to applications on jobs that belong to that
 * employer's own session account.
 *
 * Everything here is POST-only + CSRF-protected; there are no GET mutations.
 */

require_once __DIR__ . '/notifications.php';

/**
 * Returns the real schema ENUM statuses in pipeline order, keyed by the
 * stored value so the list page tabs and the detail page stay in sync with
 * what the database actually allows.
 *
 * @return array<string, string> stored value => user-facing label
 */
function hirepeak_application_pipeline(): array
{
    return hirepeak_application_statuses();
}

/**
 * The ONLY status changes an employer may performhol HerePeak; every request
 * that changes an application goes through this whitelist so the app can
 * never invent a transition on the server or accept one from the form.
 *
 * @return array<string, list<string>> current status => allowed next statuses
 */
function hirepeak_application_transitions(): array
{
    return [
        'applied'      => ['under_review', 'rejected'],
        'under_review' => ['interview', 'rejected'],
        'interview'    => ['accepted', 'rejected'],
        'accepted'     => [],
        'rejected'     => [],
    ];
}

/**
 * Whether an employer may move an application from $from to $to. This is the
 * single authority for the transition matrix — forms render the allowed "next"
 * options and the processor rejects anything outside it.
 */
function hirepeak_application_may_transition(string $from, string $to): bool
{
    return in_array($to, hirepeak_application_transitions()[$from] ?? [], true);
}

/**
 * Application application status select markup for the status-update form.
 * Renders a labelled select containing only the real ENUM alternatives plus
 * the soft current value, so an employer can never type an arbitrary status.
 * Labels come from hirepeak_application_statuses(), never raw stored values.
 */
function hirepeak_application_status_select(string $current, string $name = 'status', string $id = 'status'): string
{
    $map = hirepeak_application_statuses();
    $allowed = hirepeak_application_transitions()[$current] ?? [];
    $options = '';

    foreach ($map as $value => $label) {
        $isCurrent = $value === $current;
        $isAllowed = in_array($value, $allowed, true);

        if (!$isCurrent && !$isAllowed) {
            continue;
        }

        $selected = $isCurrent ? ' selected' : '';
        $options .= '<option value="' . hirepeak_esc((string) $value) . '"' . $selected . '>'
            . hirepeak_esc((string) $label) . '</option>';
    }

    if ($options === '') {
        return '';
    }

    return '<div class="field">
        <label class="field__label" for="' . $id . '">Status</label>
        <select class="field__select" id="' . $id . '" name="' . hirepeak_esc($name) . '">'
        . $options .
        '</select>
      </div>';
}

/**
 * Centralised application-status update processor.
 *
 * Ownership and transition rules are enforced server-side here:
 *   - the UPDATE is scoped (via JOIN) to applications whose job is owned by
 *     the authenticated employer session id, so cross-owner writes fail;
 *   - only whitelisted transitions are accepted;
 *   - POST-only + CSRF verified.
 *
 * @return array{ok: bool, flash?: string, errors?: list<string>, stayed?: bool}
 */
function hirepeak_process_application_status_update(\mysqli $db, int $applicationId, int $uid): array
{
    $result = ['ok' => false];

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        $result['errors'] = ['Invalid request.'];
        return $result;
    }

    if (!hirepeak_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
        $result['errors'] = ['Your session expired. Please try again.'];
        return $result;
    }

    $requested = (string) ($_POST['status'] ?? '');
    $allowedStatuses = hirepeak_application_statuses();

    if ($requested === '' || !isset($allowedStatuses[$requested])) {
        $result['errors'] = ['Please choose a valid application status.'];
        return $result;
    }

    try {
        $stmt = $db->prepare(
            'SELECT a.id, a.status, a.updated_at,
                    a.applicant_id,
                    j.id AS job_id, j.employer_id, j.title AS job_title
             FROM applications a
             INNER JOIN jobs j ON j.id = a.job_id
             INNER JOIN users u ON u.id = a.applicant_id
             WHERE a.id = ? AND j.employer_id = ?
             LIMIT 1'
        );
        $stmt->bind_param('ii', $applicationId, $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row === null) {
            $result['errors'] = ['That application is no longer available.'];
            return $result;
        }

        $current = (string) $row['status'];

        if (!hirepeak_application_may_transition($current, $requested)) {
            $result['errors'] = ['That status change is not allowed from ' . hirepeak_application_status_label($current) . '.'];
            return $result;
        }

        $stmt = $db->prepare(
            'UPDATE applications a
             INNER JOIN jobs j ON j.id = a.job_id
             SET a.status = ?, a.updated_at = CURRENT_TIMESTAMP
             WHERE a.id = ? AND j.employer_id = ? AND a.status = ?'
        );
        $stmt->bind_param('siis', $requested, $applicationId, $uid, $current);
        $stmt->execute();
        $affected = (int) $db->affected_rows;
        $stmt->close();

        if ($affected !== 1) {
            $result['errors'] = ['That application changed elsewhere. Please review and try again.'];
            return $result;
        }

        /* In-app notice for the applicant: "status changed" event. */
        $applicantId = (int) ($row['applicant_id'] ?? 0);
        $jobTitle = trim((string) ($row['job_title'] ?? ''));
        if ($applicantId > 0 && $jobTitle !== '') {
            hirepeak_notification_create(
                $db,
                $applicantId,
                'application_status',
                'Your application for ' . $jobTitle . ' ' . hirepeak_notification_application_status_message($requested)
            );
        }

        $result['ok'] = true;
        $result['flash'] = 'Application status updated.';
        return $result;
    } catch (\Throwable $e) {
        error_log('[hirepeak] application status update failed: ' . $e->getMessage());
        $result['errors'] = ['Something went wrong updating this application. Please try again.'];
        return $result;
    }
}
