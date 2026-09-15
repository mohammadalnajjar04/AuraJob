<?php
declare(strict_types=1);

/**
 * HirePeak authentication & session helpers.
 *
 * Provides a single, simple place for starting the secure session, reading the
 * current authenticated user, logging in/out, and CSRF tokens. Session data
 * stores only identity fields (id, name, email, role) - never passwords,
 * hashes, or database credentials.
 */

require_once __DIR__ . '/config.php';

function hirepeak_session_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    return !empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
}

function hirepeak_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => hirepeak_session_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('hirepeak_session');
    session_start();
}

function hirepeak_user(): ?array
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    $user = $_SESSION['user'];
    foreach (['id', 'name', 'email', 'role'] as $key) {
        if (!array_key_exists($key, $user)) {
            return null;
        }
    }

    return $user;
}

function hirepeak_is_logged_in(): bool
{
    return hirepeak_user() !== null;
}

/**
 * Redirects unauthenticated visitors to login.php and terminates the request.
 * Returns the authenticated user once it is safe to continue.
 */
function hirepeak_require_auth(): array
{
    hirepeak_start_session();
    $user = hirepeak_user();

    if ($user === null) {
        header('Location: /login.php');
        exit;
    }

    return $user;
}

/**
 * Ensures the visitor is authenticated AND has the given role. Unauthenticated
 * visitors are redirected to login.php; authenticated visitors with the wrong
 * role get a 403 access-denied page (never a redirect, so no loops).
 */
function hirepeak_require_role(string $role): array
{
    hirepeak_start_session();
    $user = hirepeak_require_auth();

    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);
        require __DIR__ . '/../Views/access-denied.php';
        exit;
    }

    return $user;
}

function hirepeak_is_applicant(): bool
{
    return (hirepeak_user()['role'] ?? '') === 'applicant';
}

function hirepeak_is_employer(): bool
{
    return (hirepeak_user()['role'] ?? '') === 'employer';
}

/**
 * Maps an authenticated user to their safe role destination. Never trusts any
 * query/body input - the role always comes from the authenticated session.
 */
function hirepeak_dashboard_for_role(?array $user): string
{
    $role = $user['role'] ?? '';

    return match ($role) {
        'applicant' => '/applicant/dashboard.php',
        'employer'  => '/employer/dashboard.php',
        default     => '/index.php',
    };
}

function hirepeak_login(int $id, string $name, string $email, string $role): void
{
    hirepeak_start_session();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'role' => $role,
    ];
}

function hirepeak_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? true)
        );
    }

    session_destroy();
}

function hirepeak_csrf_token(bool $rotate = false): string
{
    hirepeak_start_session();

    if ($rotate || empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function hirepeak_csrf_verify(string $token): bool
{
    hirepeak_start_session();

    return isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}