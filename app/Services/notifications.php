<?php
declare(strict_types=1);

/**
 * AuraJob in-app notifications service.
 *
 * This file intentionally demonstrates the project's PHP OOP usage while
 * preserving the public helper functions already used across the application.
 * Callers can continue using the hirepeak_notification_* helpers; those helpers
 * now delegate the database work to AuraJobNotificationService.
 */

interface AuraJobNotificationServiceInterface
{
    public function create(int $userId, string $type, string $message): bool;

    public function unreadCount(int $userId): int;

    /** @return list<array<string, mixed>> */
    public function recent(int $userId, int $limit = 50): array;

    public function markOneRead(int $userId, int $notificationId): bool;

    public function markAllRead(int $userId): bool;
}

/**
 * Database-backed notification service.
 *
 * OOP concepts used here:
 * - interface implementation
 * - constructor dependency injection
 * - private typed property
 * - class constant encapsulation
 * - instance methods with typed parameters/returns
 */
final class AuraJobNotificationService implements AuraJobNotificationServiceInterface
{
    /** @var list<string> */
    private const TYPES = ['application_status', 'application_new', 'system', 'job_update'];

    public function __construct(private \mysqli $db)
    {
    }

    /** @return list<string> */
    public static function types(): array
    {
        return self::TYPES;
    }

    public function create(int $userId, string $type, string $message): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $type = in_array($type, self::TYPES, true) ? $type : 'system';
        $message = trim($message);

        if ($message === '') {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, ?, ?, 0)'
            );
            $stmt->bind_param('iss', $userId, $type, $message);
            $stmt->execute();
            $affected = (int) $this->db->affected_rows;
            $stmt->close();
            return $affected === 1;
        } catch (\Throwable $e) {
            error_log('[AuraJob] notification create failed: ' . $e->getMessage());
            return false;
        }
    }

    public function unreadCount(int $userId): int
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0'
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $count = (int) $stmt->get_result()->fetch_assoc()['c'];
            $stmt->close();
            return $count;
        } catch (\Throwable $e) {
            error_log('[AuraJob] notification count failed: ' . $e->getMessage());
            return 0;
        }
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $userId, int $limit = 50): array
    {
        $limit = $limit > 0 ? $limit : 50;

        try {
            $stmt = $this->db->prepare(
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
            error_log('[AuraJob] notification list failed: ' . $e->getMessage());
            return [];
        }
    }

    public function markOneRead(int $userId, int $notificationId): bool
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
            );
            $stmt->bind_param('ii', $notificationId, $userId);
            $stmt->execute();
            $stmt->close();
            return true;
        } catch (\Throwable $e) {
            error_log('[AuraJob] notification mark-one failed: ' . $e->getMessage());
            return false;
        }
    }

    public function markAllRead(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
            return true;
        } catch (\Throwable $e) {
            error_log('[AuraJob] notification mark-all failed: ' . $e->getMessage());
            return false;
        }
    }
}

/** @return list<string> */
function hirepeak_notification_types(): array
{
    return AuraJobNotificationService::types();
}

/**
 * Procedural compatibility wrapper used by the existing application flows.
 */
function hirepeak_notification_create(\mysqli $db, int $userId, string $type, string $message): bool
{
    return (new AuraJobNotificationService($db))->create($userId, $type, $message);
}

function hirepeak_notification_unread_count(\mysqli $db, int $userId): int
{
    return (new AuraJobNotificationService($db))->unreadCount($userId);
}

/** @return list<array<string, mixed>> */
function hirepeak_notification_recent(\mysqli $db, int $userId, int $limit = 50): array
{
    return (new AuraJobNotificationService($db))->recent($userId, $limit);
}

function hirepeak_notification_mark_one_read(\mysqli $db, int $userId, int $notificationId): bool
{
    return (new AuraJobNotificationService($db))->markOneRead($userId, $notificationId);
}

function hirepeak_notification_mark_all_read(\mysqli $db, int $userId): bool
{
    return (new AuraJobNotificationService($db))->markAllRead($userId);
}

/**
 * User-facing sentence fragment for an application status transition.
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
