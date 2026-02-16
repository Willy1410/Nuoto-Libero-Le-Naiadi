<?php
declare(strict_types=1);

/**
 * Download pubblico modulo corrente tramite slug stabile
 * GET /api/moduli-download.php?slug=modulo-iscrizione
 */

require_once __DIR__ . '/config.php';

const MODULI_UPLOAD_DIR = PROJECT_ROOT . '/uploads/moduli';

function normalizeModuloSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    if (strlen($value) > 120) {
        $value = substr($value, 0, 120);
        $value = rtrim($value, '-');
    }
    return $value;
}

function sendDownloadFile(string $path, string $mime, string $downloadName, ?int $knownSize = null): void
{
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'File non disponibile';
        exit();
    }

    $size = $knownSize ?? filesize($path);
    if ($size === false) {
        $size = 0;
    }

    header_remove('Content-Type');
    header('X-Content-Type-Options: nosniff');
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    if ((int)$size > 0) {
        header('Content-Length: ' . (string)$size);
    }

    $fp = fopen($path, 'rb');
    if ($fp === false) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Errore apertura file';
        exit();
    }
    fpassthru($fp);
    fclose($fp);
    exit();
}

$slug = normalizeModuloSlug((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Parametro slug mancante';
    exit();
}

try {
    $stmt = $pdo->prepare(
        'SELECT slug, filename, original_name, mime, size
         FROM moduli
         WHERE slug = ? AND is_active = 1
         ORDER BY updated_at DESC
         LIMIT 1'
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch();

    if ($row) {
        $filename = (string)$row['filename'];
        $path = MODULI_UPLOAD_DIR . '/' . $filename;
        $mime = (string)$row['mime'];
        $downloadName = (string)$row['original_name'];
        sendDownloadFile($path, $mime !== '' ? $mime : 'application/octet-stream', $downloadName !== '' ? $downloadName : ($slug . '.pdf'), (int)$row['size']);
    }

    // Fallback statico (solo se nessun modulo caricato da CMS)
    $fallback = [
        'modulo-iscrizione' => [PROJECT_ROOT . '/assets/modulo-iscrizione.html', 'text/html; charset=UTF-8', 'modulo-iscrizione.html'],
        'regolamento-piscina' => [PROJECT_ROOT . '/assets/regolamento-piscina.html', 'text/html; charset=UTF-8', 'regolamento-piscina.html'],
    ];

    if (isset($fallback[$slug])) {
        [$path, $mime, $name] = $fallback[$slug];
        sendDownloadFile($path, $mime, $name);
    }

    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Modulo non disponibile';
    exit();
} catch (Throwable $e) {
    error_log('moduli-download error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Errore download modulo';
    exit();
}
