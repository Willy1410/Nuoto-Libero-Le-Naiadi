<?php
/**
 * API DOCUMENTI
 * File: api/documenti.php
 * 
 * Endpoints:
 * GET    /api/documenti.php - Lista documenti dell'utente corrente
 * POST   /api/documenti.php - Upload documento
 * GET    /api/documenti.php?action=pending - Documenti da revisionare (ufficio)
 * PATCH  /api/documenti.php?action=review&id=xxx - Revisiona documento (ufficio)
 * GET    /api/documenti.php?action=types - Tipi documento obbligatori
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && empty($action)) {
    getMyDocuments();
} elseif ($method === 'POST' && empty($action)) {
    uploadDocument();
} elseif ($method === 'GET' && $action === 'pending') {
    getPendingDocuments();
} elseif ($method === 'PATCH' && $action === 'review') {
    reviewDocument();
} elseif ($method === 'GET' && $action === 'types') {
    getDocumentTypes();
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint non trovato']);
}

/**
 * GET TIPI DOCUMENTO
 */
function getDocumentTypes() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM tipi_documento ORDER BY ordine ASC");
    $stmt->execute();
    $types = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'types' => $types]);
}

/**
 * GET MY DOCUMENTS
 */
function getMyDocuments() {
    global $pdo;
    
    $currentUser = requireAuth();
    
    $stmt = $pdo->prepare("
        SELECT d.*, t.nome as tipo_nome, t.obbligatorio, t.template_url
        FROM documenti_utente d
        JOIN tipi_documento t ON d.tipo_documento_id = t.id
        WHERE d.user_id = ?
        ORDER BY d.data_caricamento DESC
    ");
    $stmt->execute([$currentUser['user_id']]);
    $documenti = $stmt->fetchAll();
    
    // Conta documenti obbligatori mancanti
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM tipi_documento t
        LEFT JOIN documenti_utente d ON t.id = d.tipo_documento_id AND d.user_id = ? AND d.stato = 'approved'
        WHERE t.obbligatorio = TRUE AND d.id IS NULL
    ");
    $stmt->execute([$currentUser['user_id']]);
    $mancanti = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'documenti' => $documenti,
        'documenti_obbligatori_mancanti' => (int)$mancanti['count']
    ]);
}

/**
 * UPLOAD DOCUMENTO
 */
function uploadDocument() {
    global $pdo;
    
    $currentUser = requireAuth();
    
    // Verifica se è un upload file
    if (!isset($_FILES['file']) || !isset($_POST['tipo_documento_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File o tipo documento mancante']);
        return;
    }
    
    $tipo_documento_id = (int)$_POST['tipo_documento_id'];
    $file = $_FILES['file'];
    
    // Validazione file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Errore upload file']);
        return;
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File troppo grande (max 5MB)']);
        return;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_TYPES)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo file non consentito (solo PDF, JPG, PNG)']);
        return;
    }
    
    // Verifica tipo documento
    $stmt = $pdo->prepare("SELECT * FROM tipi_documento WHERE id = ?");
    $stmt->execute([$tipo_documento_id]);
    $tipo = $stmt->fetch();
    
    if (!$tipo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tipo documento non trovato']);
        return;
    }
    
    try {
        // Crea directory upload se non esiste
        $upload_dir = UPLOAD_DIR . 'documenti/' . $currentUser['user_id'] . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Genera nome file univoco
        $file_name = uniqid() . '_' . $file['name'];
        $file_path = $upload_dir . $file_name;
        
        // Sposta file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception('Errore spostamento file');
        }
        
        // URL relativo
        $file_url = '/uploads/documenti/' . $currentUser['user_id'] . '/' . $file_name;
        
        // Inserisci documento
        $stmt = $pdo->prepare("
            INSERT INTO documenti_utente (id, user_id, tipo_documento_id, file_url, file_name, stato)
            VALUES (UUID(), ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$currentUser['user_id'], $tipo_documento_id, $file_url, $file['name']]);
        
        // Log attività
        logActivity($currentUser['user_id'], 'upload_documento', "Caricato documento: {$tipo['nome']}", 'documenti_utente', $pdo->lastInsertId());
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Documento caricato con successo',
            'file_url' => $file_url
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore upload documento', 'error' => $e->getMessage()]);
    }
}

/**
 * GET PENDING DOCUMENTS (ufficio)
 */
function getPendingDocuments() {
    global $pdo;
    
    $currentUser = requireRole(3); // Ufficio minimo
    
    $stmt = $pdo->prepare("
        SELECT d.*, 
               t.nome as tipo_nome,
               t.obbligatorio,
               prof.nome as user_nome,
               prof.cognome as user_cognome,
               prof.email as user_email
        FROM documenti_utente d
        JOIN tipi_documento t ON d.tipo_documento_id = t.id
        JOIN profili prof ON d.user_id = prof.id
        WHERE d.stato = 'pending'
        ORDER BY d.data_caricamento ASC
    ");
    $stmt->execute();
    $documenti = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'documenti' => $documenti]);
}

/**
 * REVIEW DOCUMENT (ufficio)
 */
function reviewDocument() {
    global $pdo;
    
    $currentUser = requireRole(3); // Ufficio minimo
    
    $documento_id = $_GET['id'] ?? '';
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stato = $data['stato'] ?? ''; // 'approved' o 'rejected'
    $note_revisione = sanitizeInput($data['note_revisione'] ?? '');
    
    if (!$documento_id || !in_array($stato, ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi']);
        return;
    }
    
    try {
        // Verifica documento
        $stmt = $pdo->prepare("
            SELECT d.*, 
                   prof.nome as user_nome,
                   prof.cognome as user_cognome,
                   prof.email as user_email,
                   t.nome as tipo_nome
            FROM documenti_utente d
            JOIN profili prof ON d.user_id = prof.id
            JOIN tipi_documento t ON d.tipo_documento_id = t.id
            WHERE d.id = ?
        ");
        $stmt->execute([$documento_id]);
        $documento = $stmt->fetch();
        
        if (!$documento) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Documento non trovato']);
            return;
        }
        
        // Aggiorna stato
        $stmt = $pdo->prepare("
            UPDATE documenti_utente 
            SET stato = ?, note_revisione = ?, revisionato_da = ?, data_revisione = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$stato, $note_revisione, $currentUser['user_id'], $documento_id]);
        
        // Log attività
        logActivity($currentUser['user_id'], 'revisione_documento', "Documento $stato: {$documento['tipo_nome']}", 'documenti_utente', $documento_id);
        
        // Invia email notifica
        $stato_it = $stato === 'approved' ? 'approvato' : 'rifiutato';
        $htmlContent = "
            <h1>Revisione documento</h1>
            <p>Ciao {$documento['user_nome']},</p>
            <p>Il tuo documento <strong>{$documento['tipo_nome']}</strong> è stato <strong>$stato_it</strong>.</p>
        ";
        
        if ($stato === 'rejected' && $note_revisione) {
            $htmlContent .= "<p><strong>Motivazione:</strong> $note_revisione</p>";
            $htmlContent .= "<p>Ti preghiamo di caricare nuovamente il documento corretto.</p>";
        } else {
            $htmlContent .= "<p>Grazie per la collaborazione!</p>";
        }
        
        sendEmail($documento['user_email'], "{$documento['user_nome']} {$documento['user_cognome']}", "Revisione documento - {$documento['tipo_nome']}", $htmlContent);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Documento revisionato con successo'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore revisione documento', 'error' => $e->getMessage()]);
    }
}
