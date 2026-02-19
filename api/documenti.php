<?php
declare(strict_types=1);

/**
 * API documenti utente
 */

require_once __DIR__ . '/config.php';

$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? '');

if ($method === 'GET' && $action === '') {
    getMyDocuments();
} elseif ($method === 'POST' && $action === '') {
    uploadDocument();
} elseif ($method === 'GET' && $action === 'pending') {
    getPendingDocuments();
} elseif ($method === 'PATCH' && $action === 'review') {
    reviewDocument();
} elseif ($method === 'GET' && $action === 'types') {
    getDocumentTypes();
} else {
    sendJson(404, ['success' => false, 'message' => 'Endpoint non trovato']);
}

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

function buildDocumentDownloadUrl(string $documentId): string
{
    return getAppBaseUrl() . '/api/documenti-download.php?id=' . rawurlencode($documentId);
}

function buildPrefillDocumentUrl(int $tipoDocumentoId): string
{
    return getAppBaseUrl() . '/api/documenti-prefill.php?tipo_documento_id=' . rawurlencode((string)$tipoDocumentoId);
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

function isMedicalDocumentType(string $name): bool
{
    $name = mb_strtolower(trim($name), 'UTF-8');
    return str_contains($name, 'certificato') && str_contains($name, 'medic');
}

function inferModuloSlugCandidates(array $docType): array
{
    $candidates = [];
    $nome = (string)($docType['nome'] ?? '');
    $templateUrl = trim((string)($docType['template_url'] ?? ''));

    if ($templateUrl !== '') {
        $templatePath = parse_url($templateUrl, PHP_URL_PATH);
        if (is_string($templatePath) && $templatePath !== '') {
            $base = pathinfo($templatePath, PATHINFO_FILENAME);
            $slug = normalizeModuloSlug((string)$base);
            if ($slug !== '') {
                $candidates[] = $slug;
            }
        }
    }

    $normalizedName = normalizeModuloSlug($nome);
    if ($normalizedName !== '') {
        $candidates[] = $normalizedName;
    }

    $aliases = [
        'modulo-iscrizione' => ['modulo-iscrizione'],
        'regolamento-interno' => ['regolamento-piscina'],
        'privacy-gdpr' => ['informativa-privacy', 'privacy-gdpr'],
        'documento-identita' => ['documento-identita', 'modulo-documento-identita'],
    ];
    foreach (($aliases[$normalizedName] ?? []) as $alias) {
        $aliasSlug = normalizeModuloSlug($alias);
        if ($aliasSlug !== '') {
            $candidates[] = $aliasSlug;
        }
    }

    if (isMedicalDocumentType($nome)) {
        $candidates[] = 'certificato-medico-info';
    }

    $unique = [];
    foreach ($candidates as $candidate) {
        if ($candidate !== '') {
            $unique[$candidate] = true;
        }
    }

    return array_keys($unique);
}

function resolveModuloDownloadUrl(array $docType, array $activeModuloSlugs): ?string
{
    $baseUrl = getAppBaseUrl();
    foreach (inferModuloSlugCandidates($docType) as $slug) {
        if (isset($activeModuloSlugs[$slug])) {
            return $baseUrl . '/api/moduli-download.php?slug=' . rawurlencode($slug);
        }
    }

    $templateUrl = trim((string)($docType['template_url'] ?? ''));
    if ($templateUrl === '') {
        return null;
    }

    if (str_starts_with($templateUrl, 'http://') || str_starts_with($templateUrl, 'https://')) {
        return $templateUrl;
    }

    if (str_starts_with($templateUrl, '/')) {
        return $baseUrl . $templateUrl;
    }

    return null;
}

function getDocumentTypes(): void
{
    global $pdo;

    try {
        $stmt = $pdo->query('SELECT * FROM tipi_documento ORDER BY ordine ASC');
        sendJson(200, ['success' => true, 'types' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        error_log('getDocumentTypes error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento tipi documento']);
    }
}

function getMyDocuments(): void
{
    global $pdo;

    $currentUser = requireAuth();

    try {
        $stmt = $pdo->prepare(
            'SELECT d.id, d.tipo_documento_id, d.file_url, d.file_name, d.stato, d.note_revisione,
                    d.data_caricamento, d.data_revisione, d.scadenza,
                    t.nome AS tipo_nome, t.obbligatorio, t.template_url
             FROM documenti_utente d
             JOIN tipi_documento t ON t.id = d.tipo_documento_id
             WHERE d.user_id = ?
             ORDER BY d.data_caricamento DESC'
        );
        $stmt->execute([$currentUser['user_id']]);
        $documents = array_map(static function (array $row): array {
            $row['file_url'] = buildDocumentDownloadUrl((string)$row['id']);
            return $row;
        }, $stmt->fetchAll());

        $stmt = $pdo->prepare(
            'SELECT tipo_documento_id
             FROM documenti_utente
             WHERE user_id = ?
               AND stato = "approved"
             GROUP BY tipo_documento_id'
        );
        $stmt->execute([$currentUser['user_id']]);
        $approvedByType = [];
        foreach ($stmt->fetchAll() as $row) {
            $approvedByType[(int)$row['tipo_documento_id']] = true;
        }

        $activeModuloSlugs = [];
        try {
            $moduliStmt = $pdo->query('SELECT slug FROM moduli WHERE is_active = 1');
            foreach ($moduliStmt->fetchAll() as $moduloRow) {
                $slug = normalizeModuloSlug((string)($moduloRow['slug'] ?? ''));
                if ($slug !== '') {
                    $activeModuloSlugs[$slug] = true;
                }
            }
        } catch (Throwable $e) {
            // Tabella moduli non presente o non accessibile: fallback su template_url statici.
            $activeModuloSlugs = [];
        }

        $stmt = $pdo->prepare(
            'SELECT t.id, t.nome, t.descrizione, t.obbligatorio, t.template_url, t.ordine,
                    d.id AS last_document_id, d.file_name AS last_file_name, d.stato AS last_stato,
                    d.note_revisione AS last_note_revisione, d.data_caricamento AS last_data_caricamento
             FROM tipi_documento t
             LEFT JOIN documenti_utente d
               ON d.id = (
                    SELECT d2.id
                    FROM documenti_utente d2
                    WHERE d2.user_id = ?
                      AND d2.tipo_documento_id = t.id
                    ORDER BY d2.data_caricamento DESC
                    LIMIT 1
               )
             ORDER BY t.ordine ASC, t.nome ASC'
        );
        $stmt->execute([$currentUser['user_id']]);
        $typeRows = $stmt->fetchAll();

        $typesOverview = [];
        $missingRequired = [];

        foreach ($typeRows as $typeRow) {
            $tipoId = (int)$typeRow['id'];
            $isMedical = isMedicalDocumentType((string)$typeRow['nome']);
            $hasApproved = isset($approvedByType[$tipoId]);
            $moduloUrl = $isMedical ? null : resolveModuloDownloadUrl($typeRow, $activeModuloSlugs);
            $prefillUrl = $isMedical ? null : buildPrefillDocumentUrl($tipoId);

            $entry = [
                'id' => $tipoId,
                'nome' => $typeRow['nome'],
                'descrizione' => $typeRow['descrizione'],
                'obbligatorio' => (int)$typeRow['obbligatorio'],
                'template_url' => $typeRow['template_url'],
                'is_medical' => $isMedical,
                'has_approved' => $hasApproved,
                'modulo_download_url' => $moduloUrl,
                'prefill_download_url' => $prefillUrl,
                'ultimo_documento' => $typeRow['last_document_id'] ? [
                    'id' => $typeRow['last_document_id'],
                    'file_name' => $typeRow['last_file_name'],
                    'stato' => $typeRow['last_stato'],
                    'note_revisione' => $typeRow['last_note_revisione'],
                    'data_caricamento' => $typeRow['last_data_caricamento'],
                    'file_url' => buildDocumentDownloadUrl((string)$typeRow['last_document_id']),
                ] : null,
            ];

            $typesOverview[] = $entry;

            if ((int)$typeRow['obbligatorio'] === 1 && !$hasApproved) {
                $missingRequired[] = $entry;
            }
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM tipi_documento t
             LEFT JOIN documenti_utente d
               ON d.tipo_documento_id = t.id
              AND d.user_id = ?
              AND d.stato = "approved"
             WHERE t.obbligatorio = 1
               AND d.id IS NULL'
        );
        $stmt->execute([$currentUser['user_id']]);
        $missing = (int)$stmt->fetch()['total'];

        sendJson(200, [
            'success' => true,
            'documenti' => $documents,
            'documenti_obbligatori_mancanti' => $missing,
            'documenti_mancanti' => $missingRequired,
            'tipi_documento' => $typesOverview,
        ]);
    } catch (Throwable $e) {
        error_log('getMyDocuments error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento documenti']);
    }
}

function uploadDocument(): void
{
    global $pdo;

    $currentUser = requireAuth();
    enforceRateLimit('documenti-upload', 25, 900, getClientIp() . '|' . (string)$currentUser['user_id']);

    if (!isset($_FILES['file']) || !isset($_POST['tipo_documento_id'])) {
        sendJson(400, ['success' => false, 'message' => 'File o tipo documento mancante']);
    }

    $tipoDocumentoId = (int)$_POST['tipo_documento_id'];
    $file = $_FILES['file'];

    if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        sendJson(400, ['success' => false, 'message' => 'Errore upload file']);
    }

    if (!isset($file['size']) || (int)$file['size'] > UPLOAD_MAX_SIZE) {
        sendJson(400, ['success' => false, 'message' => 'File troppo grande (max 5MB)']);
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        sendJson(400, ['success' => false, 'message' => 'Upload non valido']);
    }

    $originalName = isset($file['name']) ? sanitizeText((string)$file['name'], 255) : 'file';
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_TYPES, true)) {
        sendJson(400, ['success' => false, 'message' => 'Tipo file non consentito (solo PDF/JPG/PNG)']);
    }

    $mimeByExt = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if ($detectedMime === '' || !in_array($detectedMime, $mimeByExt[$ext], true)) {
        sendJson(400, ['success' => false, 'message' => 'MIME file non valido']);
    }

    try {
        $stmt = $pdo->prepare('SELECT id, nome FROM tipi_documento WHERE id = ? LIMIT 1');
        $stmt->execute([$tipoDocumentoId]);
        $tipo = $stmt->fetch();
        if (!$tipo) {
            sendJson(404, ['success' => false, 'message' => 'Tipo documento non trovato']);
        }

        $documentId = generateUuid();
        $downloadUrl = buildDocumentDownloadUrl($documentId);

        if (documentBlobStorageSupported()) {
            $blob = file_get_contents($tmpPath);
            if (!is_string($blob) || $blob === '') {
                sendJson(500, ['success' => false, 'message' => 'Errore lettura file caricato']);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO documenti_utente
                (id, user_id, tipo_documento_id, file_url, file_name, file_mime, file_size, file_blob, stato)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")'
            );
            $stmt->execute([
                $documentId,
                $currentUser['user_id'],
                $tipoDocumentoId,
                'db_blob',
                $originalName,
                $detectedMime,
                (int)$file['size'],
                $blob,
            ]);
        } else {
            $uploadDir = UPLOAD_DIR . 'documenti/' . $currentUser['user_id'] . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            if (!is_string($safeName) || $safeName === '') {
                $safeName = 'documento.' . $ext;
            }

            $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
            $destination = $uploadDir . $storedName;

            if (!move_uploaded_file($tmpPath, $destination)) {
                sendJson(500, ['success' => false, 'message' => 'Errore salvataggio file']);
            }

            $relativePath = 'uploads/documenti/' . $currentUser['user_id'] . '/' . $storedName;
            $stmt = $pdo->prepare(
                'INSERT INTO documenti_utente (id, user_id, tipo_documento_id, file_url, file_name, stato)
                 VALUES (?, ?, ?, ?, ?, "pending")'
            );
            $stmt->execute([$documentId, $currentUser['user_id'], $tipoDocumentoId, $relativePath, $originalName]);
        }

        logActivity((string)$currentUser['user_id'], 'upload_documento', 'Upload documento: ' . $tipo['nome'], 'documenti_utente', $documentId);

        sendJson(201, [
            'success' => true,
            'message' => 'Documento caricato con successo',
            'file_url' => $downloadUrl,
        ]);
    } catch (Throwable $e) {
        error_log('uploadDocument error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento documento']);
    }
}

function getPendingDocuments(): void
{
    global $pdo;

    requireRole(3);

    try {
        $stmt = $pdo->query(
            'SELECT d.id, d.user_id, d.tipo_documento_id, d.file_url, d.file_name, d.stato, d.note_revisione,
                    d.data_caricamento, d.scadenza,
                    t.nome AS tipo_nome, t.obbligatorio,
                    u.nome AS user_nome, u.cognome AS user_cognome, u.email AS user_email
             FROM documenti_utente d
             JOIN tipi_documento t ON t.id = d.tipo_documento_id
             JOIN profili u ON u.id = d.user_id
             WHERE d.stato = "pending"
             ORDER BY d.data_caricamento ASC'
        );

        $documents = array_map(static function (array $row): array {
            $row['file_url'] = buildDocumentDownloadUrl((string)$row['id']);
            return $row;
        }, $stmt->fetchAll());

        sendJson(200, ['success' => true, 'documenti' => $documents]);
    } catch (Throwable $e) {
        error_log('getPendingDocuments error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento documenti pending']);
    }
}

function reviewDocument(): void
{
    global $pdo;

    $staff = requireRole(3);
    enforceRateLimit('documenti-review', 120, 900, getClientIp() . '|' . (string)$staff['user_id']);

    $documentId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    $data = getJsonInput();

    $state = sanitizeText((string)($data['stato'] ?? ''), 20);
    $note = sanitizeText((string)($data['note_revisione'] ?? ''), 1000);

    if ($documentId === '' || !in_array($state, ['approved', 'rejected'], true)) {
        sendJson(400, ['success' => false, 'message' => 'Dati revisione non validi']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT d.id, d.user_id, t.nome AS tipo_nome, u.nome AS user_nome, u.cognome AS user_cognome, u.email AS user_email
             FROM documenti_utente d
             JOIN tipi_documento t ON t.id = d.tipo_documento_id
             JOIN profili u ON u.id = d.user_id
             WHERE d.id = ?
             LIMIT 1'
        );
        $stmt->execute([$documentId]);
        $doc = $stmt->fetch();

        if (!$doc) {
            sendJson(404, ['success' => false, 'message' => 'Documento non trovato']);
        }

        $stmt = $pdo->prepare(
            'UPDATE documenti_utente
             SET stato = ?, note_revisione = NULLIF(?, ""), revisionato_da = ?, data_revisione = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$state, $note, $staff['user_id'], $documentId]);

        logActivity((string)$staff['user_id'], 'revisione_documento', 'Documento ' . $state, 'documenti_utente', $documentId);

        $stateText = $state === 'approved' ? 'approvato' : 'rifiutato';
        $body = '<p>Ciao <strong>' . htmlspecialchars((string)$doc['user_nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>il documento <strong>' . htmlspecialchars((string)$doc['tipo_nome'], ENT_QUOTES, 'UTF-8') . '</strong> e stato <strong>' . $stateText . '</strong>.</p>';
        if ($state === 'rejected' && $note !== '') {
            $body .= '<p>Motivazione: ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        sendTemplateEmail(
            (string)$doc['user_email'],
            trim((string)$doc['user_nome'] . ' ' . (string)$doc['user_cognome']),
            'Revisione documento',
            'Esito revisione documento',
            $body
        );

        sendJson(200, ['success' => true, 'message' => 'Documento aggiornato']);
    } catch (Throwable $e) {
        error_log('reviewDocument error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore revisione documento']);
    }
}
