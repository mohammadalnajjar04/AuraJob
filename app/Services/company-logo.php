<?php
declare(strict_types=1);

/**
 * AuraJob company-logo storage helpers.
 *
 * Uploaded logos live outside the public web root under
 * storage/uploads/company-logos and are served through public/company-logo.php.
 */

const HIREPEAK_COMPANY_LOGO_MAX_BYTES = 2 * 1024 * 1024;

function hirepeak_company_logo_storage_dir(): string
{
    return dirname(__DIR__, 2) . '/storage/uploads/company-logos';
}

/**
 * @return array{ok: bool, error: string, mime?: string, extension?: string}
 */
function hirepeak_validate_company_logo_upload(array $file): array
{
    $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => ''];
    }

    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'The logo upload could not be completed. Please try again.'];
    }

    $tmp = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    $size = isset($file['size']) ? (int) $file['size'] : 0;

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'The uploaded logo could not be verified.'];
    }

    if ($size <= 0 || $size > HIREPEAK_COMPANY_LOGO_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Logo must be 2 MB or smaller.'];
    }

    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false || !isset($imageInfo[0], $imageInfo[1])) {
        return ['ok' => false, 'error' => 'Upload a valid PNG, JPEG, or WebP image.'];
    }

    $width = (int) $imageInfo[0];
    $height = (int) $imageInfo[1];

    if ($width < 64 || $height < 64 || $width > 4096 || $height > 4096) {
        return ['ok' => false, 'error' => 'Logo dimensions must be between 64×64 and 4096×4096 pixels.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);

    $allowed = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Logo must be PNG, JPEG, or WebP.'];
    }

    return [
        'ok' => true,
        'error' => '',
        'mime' => $mime,
        'extension' => $allowed[$mime],
    ];
}

/**
 * Store a validated logo and return the DB-safe relative path.
 */
function hirepeak_store_company_logo(array $file, string $extension): string
{
    $dir = hirepeak_company_logo_storage_dir();

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create the company-logo storage directory.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $absolute = $dir . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $absolute)) {
        throw new RuntimeException('Unable to store the uploaded company logo.');
    }

    return 'company-logos/' . $filename;
}

function hirepeak_delete_company_logo(?string $relativePath): void
{
    $relativePath = trim((string) $relativePath);

    if (preg_match('#^company-logos/[a-f0-9]{32}\.(?:png|jpg|webp)$#D', $relativePath) !== 1) {
        return;
    }

    $absolute = dirname(__DIR__, 2) . '/storage/uploads/' . $relativePath;

    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

/**
 * Resolve a stored relative company-logo path to a safe absolute file path.
 */
function hirepeak_resolve_company_logo(?string $relativePath): ?string
{
    $relativePath = trim((string) $relativePath);

    if (preg_match('#^company-logos/[a-f0-9]{32}\.(?:png|jpg|webp)$#D', $relativePath) !== 1) {
        return null;
    }

    $base = realpath(hirepeak_company_logo_storage_dir());
    if ($base === false) {
        return null;
    }

    $absolute = realpath(dirname(__DIR__, 2) . '/storage/uploads/' . $relativePath);
    if ($absolute === false || !is_file($absolute)) {
        return null;
    }

    $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncmp($absolute, $prefix, strlen($prefix)) !== 0) {
        return null;
    }

    return $absolute;
}
