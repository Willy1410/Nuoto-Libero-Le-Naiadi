<?php
declare(strict_types=1);

/**
 * Download documento utente protetto
 * GET /api/documenti-download.php?id=...
 */

require_once __DIR__ . '/config.php';

function documentBlobStorageSupported(): bool
{
    static $supported = null;
    if ($supported !== null) {
        return $supported;
    }

    global $pdo;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM documenti_utente LIKE 'file_blob'");
        $supported = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $supported = false;
    }

    return $supported;
}

$currentUser = requireAuth();
$documentId = sanitizeText((string)($_GET['id'] ?? ''), 36);

if ($documentId === '') {
    http_response_code(400);
    header_remove('Content-Type');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ID documento mancante';
    exit();
}

try {
    if (documentBlobStorageSupported()) {
        $stmt = $pdo->prepare(
            'SELECT d.id, d.user_id, d.file_url, d.file_name, d.file_mime, d.file_size, d.file_blob
             FROM documenti_utente d
             WHERE d.id = ?
             LIMIT 1'
        );
    } else {
        $stmt = $pdo->prepare(
            'SELECT d.id, d.user_id, d.file_url, d.file_name
             FROM documenti_utente d
             WHERE d.id = ?
             LIMIT 1'
        );
    }
    $stmt->execute([$documentId]);
    $doc = $stmt->fetch();

    if (!$doc) {
        http_response_code(404);
        header_remove('Content-Type');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Documento non trovato';
        exit();
    }

    $roleLevel = getUserRoleLevel((string)$currentUser['user_id']);
    $isOwner = (string)$doc['user_id'] === (string)$currentUser['user_id'];
    if (!$isOwner && $roleLevel < 3) {
        http_response_code(403);
        header_remove('Content-Type');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Accesso negato';
        exit();
    }

    $downloadName = trim((string)($doc['file_name'] ?? ''));
    if ($downloadName === '') {
        $downloadName = 'documento_' . $documentId;
    }

    $blob = documentBlobStorageSupported() ? ($doc['file_blob'] ?? null) : null;
    if (is_string($blob) && $blob !== '') {
        $mime = trim((string)($doc['file_mime'] ?? ''));
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }
        $size = (int)($doc['file_size'] ?? strlen($blob));
        outputBytes($blob, $mime, $downloadName, $size);
    }

    $legacyPath = resolveLegacyStoredPath((string)($doc['file_url'] ?? ''));
    if ($legacyPath === null || !is_file($legacyPath) || !is_readable($legacyPath)) {
        http_response_code(404);
        header_remove('Content-Type');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'File documento non disponibile';
        exit();
    }

    $mime = trim((string)($doc['file_mime'] ?? ''));
    if ($mime === '') {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string)finfo_file($finfo, $legacyPath) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
    }
    if ($mime === '') {
        $mime = 'application/octet-stream';
    }

    outputFile($legacyPath, $mime, $downloadName);
} catch (Throwable $e) {
    error_log('documenti-download error: ' . $e->getMessage());
    http_response_code(500);
    header_remove('Content-Type');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Errore download documento';
    exit();
}

function resolveLegacyStoredPath(string $stored): ?string
{
    $stored = trim($stored);
    if ($stored === '' || $stored === 'db_blob') {
        return null;
    }

    $candidate = $stored;

    if (preg_match('#^https?://#i', $stored)) {
        $path = (string)parse_url($stored, PHP_URL_PATH);
        $candidate = $path;
    }

    if (str_starts_with($candidate, '/')) {
        $uploadsPos = strpos($candidate, '/uploads/');
        if ($uploadsPos !== false) {
            $candidate = substr($candidate, $uploadsPos + 1);
        } else {
            $candidate = ltrim($candidate, '/');
        }
    }

    $candidate = str_replace('\\', '/', $candidate);
    if (str_starts_with($candidate, './')) {
        $candidate = substr($candidate, 2);
    }

    if (!str_starts_with($candidate, 'uploads/')) {
        return null;
    }

    $absolute = PROJECT_ROOT . '/' . $candidate;
    $real = realpath($absolute);
    if ($real === false) {
        return $absolute;
    }

    $uploadsRoot = realpath(PROJECT_ROOT . '/uploads');
    if ($uploadsRoot === false) {
        return $real;
    }

    $realNorm = str_replace('\\', '/', $real);
    $uploadsNorm = rtrim(str_replace('\\', '/', $uploadsRoot), '/');
    if (!str_starts_with($realNorm, $uploadsNorm . '/')) {
        return null;
    }

    return $real;
}

function outputBytes(string $bytes, string $mime, string $downloadName, int $size): void
{
    header_remove('Content-Type');
    header('X-Content-Type-Options: nosniff');
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
    if ($size > 0) {
        header('Content-Length: ' . (string)$size);
    }

    echo $bytes;
    exit();
}

function outputFile(string $path, string $mime, string $downloadName): void
{
    $size = filesize($path);
    if ($size === false) {
        $size = 0;
    }

    header_remove('Content-Type');
    header('X-Content-Type-Options: nosniff');
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
    if ($size > 0) {
        header('Content-Length: ' . (string)$size);
    }

    $fp = fopen($path, 'rb');
    if ($fp === false) {
        http_response_code(500);
        header_remove('Content-Type');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Errore apertura file';
        exit();
    }

    fpassthru($fp);
    fclose($fp);
    exit();
}
