<?php
declare(strict_types=1);

/**
 * HirePeak configuration loader.
 *
 * Reads KEY=VALUE pairs from the project root .env file (if present) into the
 * process environment so the rest of the application can read them via
 * getenv(). No real secrets are hard-coded here; missing or empty variables
 * fall back to safe local development defaults.
 */

function hirepeak_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return is_string($value) && $value !== '' ? $value : $default;
}

function hirepeak_load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            continue;
        }

        $key = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));

        if ($key === '' || $value === '') {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

if (!defined('HIREPEAK_CONFIG_EARLY')) {
    define('HIREPEAK_CONFIG_EARLY', true);

    hirepeak_load_env(dirname(__DIR__) . '/.env');

    if (!defined('DB_HOST')) {
        define('DB_HOST', hirepeak_env('DB_HOST', '127.0.0.1'));
    }
    if (!defined('DB_PORT')) {
        define('DB_PORT', hirepeak_env('DB_PORT', '3306'));
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', hirepeak_env('DB_NAME', ''));
    }
    if (!defined('DB_USER')) {
        define('DB_USER', hirepeak_env('DB_USER', ''));
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', hirepeak_env('DB_PASS', ''));
    }
}