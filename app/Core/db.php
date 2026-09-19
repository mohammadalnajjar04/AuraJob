<?php
declare(strict_types=1);

/**
 * AuraJob database connection.
 *
 * Provides a shared mysqli connection (utf8mb4). Throws exceptions on any
 * connection or query failure; callers decide how to surface them. Detailed
 * errors are written to the server error log and never rendered raw to users.
 */

require_once __DIR__ . '/config.php';

function hirepeak_db(): mysqli
{
    static $db = null;

    if ($db instanceof mysqli) {
        return $db;
    }

    if (!class_exists('mysqli')) {
        error_log('[hirepeak] mysqli extension is not loaded');
        throw new RuntimeException('The mysqli PHP extension is not enabled.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
        $db->set_charset('utf8mb4');
    } catch (\mysqli_sql_exception $e) {
        error_log('[hirepeak] Database connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database connection unavailable.', 0, $e);
    }

    return $db;
}