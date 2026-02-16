<?php
declare(strict_types=1);

/**
 * Gestione moduli scaricabili (solo Admin/Ufficio/Segreteria)
 *
 * GET    /api/moduli.php                  -> lista moduli attivi
 * POST   /api/moduli.php                  -> upload nuovo / sostituzione
 * DELETE /api/moduli.php?id={id}          -> elimina modulo attivo
 */

require_once __DIR__ . '/config.php';

const MODULI_UPLOAD_DIR = PROJECT_ROOT . '/uploads/moduli';
const MODULI_ARCHIVE_DIR = MODULI_UPLOAD_DIR . '/_archive';
const MAX_MODULO_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    listModuli();
} elseif ($method === 'POST') {
    uploadOrReplaceModulo();
} elseif ($method === 'DELETE') {
    deleteModulo();
} else {
    sendJson(405, ['success' => false, 'message' => 'Metodo non consentito']);
}

function ensureModuliDirs(): void
{
    if (!is_dir(MODULI_UPLOAD_DIR)) {
        mkdir(MODULI_UPLOAD_DIR, 0755, true);
    }
    if (!is_dir(MODULI_ARCHIVE_DIR)) {
        mkdir(MODULI_ARCHIVE_DIR, 0755, true);
    }
}

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

function safeOriginalFilename(string $name): string
{
    $name = str_replace(['\\', '/'], '', $name);
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
    $name = trim($name);
    if ($name === '') {
        return 'modulo';
    }
    return mb_substr($name, 0, 255);
}

function archiveModuloFileIfExists(string $filename): void
{
    if ($filename === '') {
        return;
    }

    $source = MODULI_UPLOAD_DIR . '/' . $filename;
    if (!is_file($source)) {
        return;
    }

    $archiveName = date('Ymd_His') . '_' . $filename;
    $target = MODULI_ARCHIVE_DIR . '/' . $archiveName;
    if (!@rename($source, $target)) {
        error_log('moduli archive warning: impossibile archiviare file ' . $filename);
    }
}

function listModuli(): void
{
    global $pdo;

    requireRole(3);

    try {
        $stmt = $pdo->query(
            'SELECT m.id, m.slug, m.nome, m.original_name, m.mime, m.size, m.version_num, m.updated_at,
                    m.updated_by, p.nome AS uploader_nome, p.cognome AS uploader_cognome
             FROM moduli m
             JOIN profili p ON p.id = m.updated_by
             WHERE m.is_active = 1
             ORDER BY m.nome ASC'
        );

        $rows = $stmt->fetchAll();
        $base = getAppBaseUrl();

        $moduli = array_map(static function (array $row) use ($base): array {
            $row['size_kb'] = round(((int)$row['size']) / 1024, 2);
            $row['download_url'] = $base . '/api/moduli-download.php?slug=' . rawurlencode((string)$row['slug']);
            return $row;
        }, $rows);

        sendJson(200, ['success' => true, 'moduli' => $moduli]);
    } catch (Throwable $e) {
        error_log('listModuli error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento moduli']);
    }
}

function uploadOrReplaceModulo(): void
{
    global $pdo;

    $staff = requireRole(3);
    ensureModuliDirs();

    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        sendJson(400, ['success' => false, 'message' => 'File mancante']);
    }

    $slug = normalizeModuloSlug((string)($_POST['slug'] ?? ''));
    $nome = sanitizeText((string)($_POST['nome'] ?? ''), 150);
    $replace = filter_var($_POST['replace'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($slug === '') {
        sendJson(400, ['success' => false, 'message' => 'Slug modulo obbligatorio']);
    }
    if ($nome === '') {
        $nome = ucwords(str_replace('-', ' ', $slug));
    }

    $file = $_FILES['file'];
    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        sendJson(400, ['success' => false, 'message' => 'Errore upload file']);
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        sendJson(400, ['success' => false, 'message' => 'Upload non valido']);
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > MAX_MODULO_FILE_SIZE) {
        sendJson(400, ['success' => false, 'message' => 'Dimensione file non valida (max 10MB)']);
    }

    $originalName = safeOriginalFilename((string)($file['name'] ?? 'modulo'));
    $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));

    $allowedByExt = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];

    if (!array_key_exists($extension, $allowedByExt)) {
        sendJson(400, ['success' => false, 'message' => 'Formato non consentito. Ammessi: PDF, DOC, DOCX']);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if ($detectedMime === '' || !in_array($detectedMime, $allowedByExt[$extension], true)) {
        sendJson(400, ['success' => false, 'message' => 'MIME file non valido o sospetto']);
    }

    $random = bin2hex(random_bytes(12));
    $storedFilename = date('YmdHis') . '_' . $random . '.' . $extension;
    $destination = MODULI_UPLOAD_DIR . '/' . $storedFilename;

    $oldRow = null;
    $oldVersion = 0;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT id, filename, version_num FROM moduli WHERE slug = ? AND is_active = 1 LIMIT 1 FOR UPDATE');
        $stmt->execute([$slug]);
        $oldRow = $stmt->fetch();

        if ($oldRow && !$replace) {
            $pdo->rollBack();
            sendJson(409, ['success' => false, 'message' => 'Modulo gia esistente. Usa la sostituzione.']);
        }

        if (!move_uploaded_file($tmpPath, $destination)) {
            $pdo->rollBack();
            sendJson(500, ['success' => false, 'message' => 'Impossibile salvare il file caricato']);
        }

        if ($oldRow) {
            $oldVersion = (int)$oldRow['version_num'];
            $stmt = $pdo->prepare('UPDATE moduli SET is_active = 0 WHERE id = ?');
            $stmt->execute([$oldRow['id']]);
        }

        $newVersion = $oldVersion + 1;
        $stmt = $pdo->prepare(
            'INSERT INTO moduli
            (slug, nome, filename, original_name, mime, size, version_num, updated_at, updated_by, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1)'
        );
        $stmt->execute([
            $slug,
            $nome,
            $storedFilename,
            $originalName,
            $detectedMime,
            $size,
            $newVersion,
            $staff['user_id'],
        ]);

        $newId = (int)$pdo->lastInsertId();
        $pdo->commit();

        if ($oldRow && !empty($oldRow['filename'])) {
            archiveModuloFileIfExists((string)$oldRow['filename']);
        }

        logActivity((string)$staff['user_id'], 'modulo_upload', 'Upload/sostituzione modulo ' . $slug, 'moduli', (string)$newId);

        sendJson(201, [
            'success' => true,
            'message' => $oldRow ? 'Modulo sostituito correttamente' : 'Modulo caricato correttamente',
            'modulo' => [
                'id' => $newId,
                'slug' => $slug,
                'nome' => $nome,
                'version_num' => $newVersion,
                'updated_at' => date('Y-m-d H:i:s'),
                'download_url' => getAppBaseUrl() . '/api/moduli-download.php?slug=' . rawurlencode($slug),
            ],
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (is_file($destination)) {
            @unlink($destination);
        }
        error_log('uploadOrReplaceModulo error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore durante salvataggio modulo']);
    }
}

function deleteModulo(): void
{
    global $pdo;

    $staff = requireRole(3);
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJson(400, ['success' => false, 'message' => 'ID modulo non valido']);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT id, slug, filename FROM moduli WHERE id = ? AND is_active = 1 LIMIT 1 FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            sendJson(404, ['success' => false, 'message' => 'Modulo non trovato o gia eliminato']);
        }

        $stmt = $pdo->prepare('UPDATE moduli SET is_active = 0, updated_at = NOW(), updated_by = ? WHERE id = ?');
        $stmt->execute([$staff['user_id'], $id]);

        $pdo->commit();

        archiveModuloFileIfExists((string)$row['filename']);
        logActivity((string)$staff['user_id'], 'modulo_delete', 'Eliminazione modulo ' . $row['slug'], 'moduli', (string)$id);

        sendJson(200, ['success' => true, 'message' => 'Modulo eliminato']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('deleteModulo error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore eliminazione modulo']);
    }
}
