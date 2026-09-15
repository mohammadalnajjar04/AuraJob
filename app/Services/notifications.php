<?php
declare(strict_types=1);

/**
 * Shared in-app notification helpers.
 *
 * Everything here is a pure function on a passed-in mysqli connection; loading
 * this file does nothing by itself. All writes are prepared statements, all
 * reads/writes are scoped by the authenticated user id supplied by the caller
 * (never taken from request input), and the type column is restricted to the
 * real ENUM values from database/schema.sql:
 *
 *   notifications.type ENUM('application_status','application_new','system','job_update')
 *
 * Message wording lives here in exactly one place so the apply flow, the
 * status-update flow, and the notifications page never drift apart.
 */

/**
 * The real schema ENUM values for notifications.type. Anything outside this
 * list is rejected before it can reach a query.
 *
 * @return list<string>
 */
function hirepeak_notification_types(): array
{
    return ['application_status', 'application_new', 'system', 'job_update'];
}

/**
 * Inserts one notification for $userId. Non-fatal by design: a failure to
 * record an update is logged and reported to the caller (false) so the apply /
 * status-update flow can still complete cleanly.
 */
function hirepeak_notification_create(\mysqli $db, int $userId, string $type, string $message): bool
{
    if ($userId <= 0) {
        return false;
    }

    $types = hirepeak_notification_types();
    $type = in_array($type, $types, true) ? $type : 'system';
    $message = trim($message);

    if ($message === '') {
        return false;
    }

    try {
        $stmt = $db->prepare('INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, ?, ?, 0)');
        $stmt->bind_param('iss', $userId, $type, $message);
        $stmt->execute();
        $affected = (int) $db->affected_rows;
        $stmt->close();
        return $affected === 1;
    } catch (\Throwable $e) {
        error_log('[hirepeak] notification create failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Number of unread notifications for a user. Used by the header badge and the
 * notifications page. Never trusts a session-less caller: $userId always comes
 * from the authenticated session.
 */
function hirepeak_notification_unread_count(\mysqli $db, int $userId): int
{
    try {
        $stmt = $db->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $count = (int) $stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        return $count;
    } catch (\Throwable $e) {
        error_log('[hirepeak] notification count failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Most recent notifications for a user, newest first. Each row carries the
 * stored columns plus a computed bool 'is_new' the page can use for visual
 * distinction (avoiding boolean casts per row in markup).
 *
 * @return list<array<string, mixed>>
 */
function hirepeak_notification_recent(\mysqli $db, int $userId, int $limit = 50): array
{
    $limit = $limit > 0 ? $limit : 50;

    try {
        $stmt = $db->prepare(
            'SELECT id, user_id, type, message, is_read, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT ?'
        );
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $list = [];
        foreach ($rows as $row) {
            $row['is_new'] = !(bool) ((int) ($row['is_read'] ?? 0));
            $list[] = $row;
        }
        return $list;
    } catch (\Throwable $e) {
        error_log('[hirepeak] notification list failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * Marks a single notification read, but only when it belongs to $userId.
 * Returns true when an unread row owned by the caller was actually updated
 * (or was already read), false when the row is missing or owned by someone
 * else — the ownership scope is in the SQL, never in the caller's logic.
 */
function hirepeak_notification_mark_one_read(\mysqli $db, int $userId, int $notificationId): bool
{
    try {
        $stmt = $db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        );
        $stmt->bind_param('ii', $notificationId, $userId);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (\Throwable $e) {
        error_log('[hirepeak] notification mark-one failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Marks every notification for $userId as read. Scoped entirely by the session
 * user id so one user can never touch another user's rows.
 */
function hirepeak_notification_mark_all_read(\mysqli $db, int $userId): bool
{
    try {
        $stmt = $db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (\Throwable $e) {
        error_log('[hirepeak] notification mark-all failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * User-facing "what happened to your application" sentence fragment for a
 * status transition. The full message is "Your application for {job title} "
 * followed by this fragment. Only the reachable schema transitions that an
 * employer can move TO are expected here (see applies-form.php); 'applied' is
 * never a target so it has no entry.
 */
function hirepeak_notification_application_status_message(string $status): string
{
    return match ($status) {
        'under_review' => 'is now Under Review.',
        'interview'    => 'moved to Interview.',
        'accepted'     => 'was accepted.',
        'rejected'     => 'is no longer moving forward.',
        default        => 'changed to ' . hirepeak_application_status_label($status) . '.',
    };
}