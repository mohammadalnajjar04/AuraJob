<?php
declare(strict_types=1);

/**
 * AuraJob application-layer encryption helpers.
 *
 * The design docs (database/ERD.md) target applicant_profiles.phone for
 * reversible encryption using PHP/OpenSSL AES-256-GCM: it is personally
 * identifying, not used for lookups, and must be shown back to the user or to
 * employers later. The key lives in the application config (.env,
 * APP_ENCRYPTION_KEY) — never in the database and never committed to the repo.
 *
 * Payload format: "v1:" . base64(nonce(12) . tag(16) . ciphertext)
 */

require_once __DIR__ . '/../Core/config.php';

const HIREPEAK_PHONE_NONCE_LEN = 12;
const HIREPEAK_PHONE_TAG_LEN = 16;

/**
 * Loads and normalizes the AES-256-GCM application key from APP_ENCRYPTION_KEY.
 *
 * Accepts a 32-byte raw key, 64-char hex, or 44-char base64 representation of
 * the same 256-bit key. Throws when missing/malformed so phone data is never
 * silently stored in plaintext.
 */
function hirepeak_app_encryption_key(): string
{
    static $key = null;

    if ($key !== null) {
        return $key;
    }

    $raw = (string) hirepeak_env('APP_ENCRYPTION_KEY', '');

    if ($raw === '') {
        throw new RuntimeException('APP_ENCRYPTION_KEY is not configured.');
    }

    if (preg_match('/^[0-9a-fA-F]{64}$/', $raw) === 1) {
        $decoded = hex2bin($raw);
    } elseif (strlen($raw) === 44 && preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $raw) === 1) {
        $decoded = base64_decode($raw, true);
    } else {
        $decoded = $raw;
    }

    if (is_string($decoded) && strlen($decoded) === 32) {
        return $key = $decoded;
    }

    throw new RuntimeException('APP_ENCRYPTION_KEY must be a 256-bit key (32 bytes, 64 hex, or 44 base64 chars).');
}

/**
 * Encrypts a plaintext phone number with AES-256-GCM and returns a versioned,
 * compact, base64 payload. Never returns plaintext.
 */
function hirepeak_phone_encrypt(string $plaintext): string
{
    $key = hirepeak_app_encryption_key();
    $nonce = random_bytes(HIREPEAK_PHONE_NONCE_LEN);
    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        '',
        HIREPEAK_PHONE_TAG_LEN
    );

    if ($ciphertext === false) {
        throw new RuntimeException('Phone encryption failed.');
    }

    return 'v1:' . base64_encode($nonce . $tag . $ciphertext);
}

/**
 * Decrypts a versioned phone payload. Returns null for anything that is not a
 * "v1:" payload (e.g. legacy plaintext rows from before encryption) so callers
 * can fall back gracefully without breaking.
 */
function hirepeak_phone_decrypt(?string $payload): ?string
{
    if ($payload === null || $payload === '' || !str_starts_with($payload, 'v1:')) {
        return null;
    }

    $raw = base64_decode(substr($payload, 3), true);
    if ($raw === false || strlen($raw) < HIREPEAK_PHONE_NONCE_LEN + HIREPEAK_PHONE_TAG_LEN) {
        return null;
    }

    $nonce = substr($raw, 0, HIREPEAK_PHONE_NONCE_LEN);
    $tag = substr($raw, HIREPEAK_PHONE_NONCE_LEN, HIREPEAK_PHONE_TAG_LEN);
    $ciphertext = substr($raw, HIREPEAK_PHONE_NONCE_LEN + HIREPEAK_PHONE_TAG_LEN);

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', hirepeak_app_encryption_key(), OPENSSL_RAW_DATA, $nonce, $tag);

    return $plaintext === false ? null : $plaintext;
}