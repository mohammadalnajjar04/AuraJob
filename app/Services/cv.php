<?php
declare(strict_types=1);

/**
 * AuraJob applicant CV storage helpers.
 *
 * CV files are stored outside public/ under storage/uploads/cvs and are served
 * only through authenticated, ownership-scoped download endpoints.
 */

const HIREPEAK_CV_MAX_BYTES = 5 * 1024 * 1024;

function hirepeak_cv_storage_dir(): string
{
    return dirname(__DIR__, 2) . '/storage/uploads/cvs';
}

/**
 * @return array{ok: bool, error: string}
 */
function hirepeak_validate_cv_upload(array $file): array
{
    $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => ''];
    }

    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'The CV upload could not be completed. Please try again.'];
    }

    $tmp = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    $size = isset($file['size']) ? (int) $file['size'] : 0;

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'The uploaded CV could not be verified.'];
    }

    if ($size <= 0 || $size > HIREPEAK_CV_MAX_BYTES) {
        return ['ok' => false, 'error' => 'CV must be a PDF no larger than 5 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);

    if ($mime !== 'application/pdf') {
        return ['ok' => false, 'error' => 'Upload your CV as a PDF file.'];
    }

    $handle = @fopen($tmp, 'rb');
    $signature = $handle !== false ? (string) fread($handle, 5) : '';
    if ($handle !== false) {
        fclose($handle);
    }

    if ($signature !== '%PDF-') {
        return ['ok' => false, 'error' => 'Upload a valid PDF file.'];
    }

    return ['ok' => true, 'error' => ''];
}

function hirepeak_store_cv(array $file): string
{
    $dir = hirepeak_cv_storage_dir();

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create the CV storage directory.');
    }

    $filename = bin2hex(random_bytes(16)) . '.pdf';
    $absolute = $dir . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $absolute)) {
        throw new RuntimeException('Unable to store the uploaded CV.');
    }

    return 'cvs/' . $filename;
}

function hirepeak_delete_cv(?string $relativePath): void
{
    $relativePath = trim((string) $relativePath);

    if (preg_match('#^cvs/[a-f0-9]{32}\.pdf$#D', $relativePath) !== 1) {
        return;
    }

    $absolute = dirname(__DIR__, 2) . '/storage/uploads/' . $relativePath;
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

function hirepeak_resolve_cv(?string $relativePath): ?string
{
    $relativePath = trim((string) $relativePath);

    if (preg_match('#^cvs/[a-f0-9]{32}\.pdf$#D', $relativePath) !== 1) {
        return null;
    }

    $base = realpath(hirepeak_cv_storage_dir());
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

function hirepeak_send_cv_download(string $absolutePath, string $downloadName = 'AuraJob-CV.pdf'): never
{
    $size = filesize($absolutePath);
    if ($size === false) {
        http_response_code(404);
        exit;
    }

    $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $downloadName) ?: 'AuraJob-CV.pdf';
    if (!str_ends_with(strtolower($safeName), '.pdf')) {
        $safeName .= '.pdf';
    }

    header('Content-Type: application/pdf');
    header('Content-Length: ' . (string) $size);
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Cache-Control: private, no-store');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');

    readfile($absolutePath);
    exit;
}
