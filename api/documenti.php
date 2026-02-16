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
        $documents = $stmt->fetchAll();

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

    $originalName = isset($file['name']) ? sanitizeText((string)$file['name'], 255) : 'file';
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_TYPES, true)) {
        sendJson(400, ['success' => false, 'message' => 'Tipo file non consentito (solo PDF/JPG/PNG)']);
    }

    try {
        $stmt = $pdo->prepare('SELECT id, nome FROM tipi_documento WHERE id = ? LIMIT 1');
        $stmt->execute([$tipoDocumentoId]);
        $tipo = $stmt->fetch();
        if (!$tipo) {
            sendJson(404, ['success' => false, 'message' => 'Tipo documento non trovato']);
        }

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

        if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
            sendJson(500, ['success' => false, 'message' => 'Errore salvataggio file']);
        }

        $documentId = generateUuid();
        $fileUrl = '/nuoto-libero/uploads/documenti/' . $currentUser['user_id'] . '/' . $storedName;

        $stmt = $pdo->prepare(
            'INSERT INTO documenti_utente (id, user_id, tipo_documento_id, file_url, file_name, stato)
             VALUES (?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([$documentId, $currentUser['user_id'], $tipoDocumentoId, $fileUrl, $originalName]);

        logActivity((string)$currentUser['user_id'], 'upload_documento', 'Upload documento: ' . $tipo['nome'], 'documenti_utente', $documentId);

        sendJson(201, [
            'success' => true,
            'message' => 'Documento caricato con successo',
            'file_url' => $fileUrl,
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

        sendJson(200, ['success' => true, 'documenti' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        error_log('getPendingDocuments error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento documenti pending']);
    }
}

function reviewDocument(): void
{
    global $pdo;

    $staff = requireRole(3);

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