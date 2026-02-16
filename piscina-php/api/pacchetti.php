<?php
/**
 * API PACCHETTI
 * File: api/pacchetti.php
 * 
 * Endpoints:
 * GET    /api/pacchetti.php - Lista pacchetti disponibili
 * POST   /api/pacchetti.php - Acquista pacchetto (utente)
 * GET    /api/pacchetti.php?action=my-purchases - I miei acquisti
 * PATCH  /api/pacchetti.php?action=confirm&id=xxx - Conferma pagamento (ufficio/admin)
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && empty($action)) {
    getPacchetti();
} elseif ($method === 'POST' && empty($action)) {
    acquistaPacchetto();
} elseif ($method === 'GET' && $action === 'my-purchases') {
    getMyPurchases();
} elseif ($method === 'PATCH' && $action === 'confirm') {
    confirmPayment();
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint non trovato']);
}

/**
 * GET PACCHETTI DISPONIBILI
 */
function getPacchetti() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM pacchetti WHERE attivo = TRUE ORDER BY ordine ASC, prezzo ASC");
    $stmt->execute();
    $pacchetti = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'pacchetti' => $pacchetti]);
}

/**
 * ACQUISTA PACCHETTO
 */
function acquistaPacchetto() {
    global $pdo;
    
    $currentUser = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $pacchetto_id = (int)($data['pacchetto_id'] ?? 0);
    $metodo_pagamento = $data['metodo_pagamento'] ?? 'bonifico';
    $riferimento_pagamento = sanitizeInput($data['riferimento_pagamento'] ?? '');
    $note_pagamento = sanitizeInput($data['note_pagamento'] ?? '');
    
    if (!$pacchetto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Pacchetto non specificato']);
        return;
    }
    
    // Verifica pacchetto
    $stmt = $pdo->prepare("SELECT * FROM pacchetti WHERE id = ? AND attivo = TRUE");
    $stmt->execute([$pacchetto_id]);
    $pacchetto = $stmt->fetch();
    
    if (!$pacchetto) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pacchetto non trovato']);
        return;
    }
    
    try {
        // Crea acquisto
        $stmt = $pdo->prepare("
            INSERT INTO acquisti 
            (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento, ingressi_rimanenti, importo_pagato)
            VALUES (UUID(), ?, ?, ?, 'pending', ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $currentUser['user_id'],
            $pacchetto_id,
            $metodo_pagamento,
            $riferimento_pagamento,
            $note_pagamento,
            $pacchetto['num_ingressi'],
            $pacchetto['prezzo']
        ]);
        
        $acquisto_id = $pdo->lastInsertId();
        
        // Recupera acquisto completo
        $stmt = $pdo->prepare("
            SELECT a.*, p.nome as pacchetto_nome, p.descrizione as pacchetto_descrizione
            FROM acquisti a
            JOIN pacchetti p ON a.pacchetto_id = p.id
            WHERE a.id = (SELECT id FROM acquisti ORDER BY created_at DESC LIMIT 1)
        ");
        $stmt->execute();
        $acquisto = $stmt->fetch();
        
        // Log attività
        logActivity($currentUser['user_id'], 'acquisto_pacchetto', "Acquisto pacchetto: {$pacchetto['nome']}", 'acquisti', $acquisto['id']);
        
        // Invia email istruzioni pagamento
        $stmt = $pdo->prepare("SELECT nome, cognome, email FROM profili WHERE id = ?");
        $stmt->execute([$currentUser['user_id']]);
        $user = $stmt->fetch();
        
        $htmlContent = "
            <h1>Grazie per il tuo acquisto!</h1>
            <p>Hai richiesto l'acquisto di: <strong>{$pacchetto['nome']}</strong></p>
            <p>Importo: <strong>€ " . number_format($pacchetto['prezzo'], 2, ',', '.') . "</strong></p>
            <p>Ingressi inclusi: <strong>{$pacchetto['num_ingressi']}</strong></p>
            <hr>
            <h2>Istruzioni per il pagamento:</h2>
            <p><strong>Metodo:</strong> $metodo_pagamento</p>
            <p>Riferimento ordine: <code>{$acquisto['id']}</code></p>
            <p>Una volta effettuato il pagamento, il personale confermerà l'acquisto e riceverai il tuo codice QR.</p>
        ";
        sendEmail($user['email'], "{$user['nome']} {$user['cognome']}", 'Conferma acquisto - Piscina Naiadi', $htmlContent);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Acquisto registrato, in attesa di conferma pagamento',
            'acquisto' => $acquisto
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'acquisto', 'error' => $e->getMessage()]);
    }
}

/**
 * GET MY PURCHASES
 */
function getMyPurchases() {
    global $pdo;
    
    $currentUser = requireAuth();
    
    $stmt = $pdo->prepare("
        SELECT a.*, p.nome as pacchetto_nome, p.descrizione as pacchetto_descrizione, p.validita_giorni
        FROM acquisti a
        JOIN pacchetti p ON a.pacchetto_id = p.id
        WHERE a.user_id = ?
        ORDER BY a.data_acquisto DESC
    ");
    $stmt->execute([$currentUser['user_id']]);
    $acquisti = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'acquisti' => $acquisti]);
}

/**
 * CONFERMA PAGAMENTO (solo ufficio/admin)
 */
function confirmPayment() {
    global $pdo;
    
    $currentUser = requireRole(3); // Ufficio o Admin
    
    $acquisto_id = $_GET['id'] ?? '';
    
    if (!$acquisto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID acquisto non specificato']);
        return;
    }
    
    try {
        // Verifica acquisto
        $stmt = $pdo->prepare("
            SELECT a.*, p.validita_giorni 
            FROM acquisti a 
            JOIN pacchetti p ON a.pacchetto_id = p.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$acquisto_id]);
        $acquisto = $stmt->fetch();
        
        if (!$acquisto) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Acquisto non trovato']);
            return;
        }
        
        if ($acquisto['stato_pagamento'] === 'confirmed') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acquisto già confermato']);
            return;
        }
        
        // Genera QR code
        $qr_code = generateQRCode($acquisto_id);
        
        // Calcola data scadenza
        $data_scadenza = date('Y-m-d', strtotime("+{$acquisto['validita_giorni']} days"));
        
        // Conferma acquisto
        $stmt = $pdo->prepare("
            UPDATE acquisti 
            SET stato_pagamento = 'confirmed', 
                qr_code = ?,
                data_scadenza = ?,
                confermato_da = ?,
                data_conferma = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$qr_code, $data_scadenza, $currentUser['user_id'], $acquisto_id]);
        
        // Log attività
        logActivity($currentUser['user_id'], 'conferma_pagamento', "Pagamento confermato per acquisto $acquisto_id", 'acquisti', $acquisto_id);
        
        // Invia email con QR code
        $stmt = $pdo->prepare("SELECT nome, cognome, email FROM profili WHERE id = ?");
        $stmt->execute([$acquisto['user_id']]);
        $user = $stmt->fetch();
        
        $htmlContent = "
            <h1>Pagamento confermato!</h1>
            <p>Il tuo acquisto è stato confermato e il tuo codice QR è pronto.</p>
            <p><strong>Codice QR:</strong> <code>$qr_code</code></p>
            <p><strong>Ingressi rimanenti:</strong> {$acquisto['ingressi_rimanenti']}</p>
            <p><strong>Valido fino al:</strong> $data_scadenza</p>
            <p>Scarica il tuo QR code dalla dashboard e presentalo all'ingresso.</p>
        ";
        sendEmail($user['email'], "{$user['nome']} {$user['cognome']}", 'Codice QR pronto - Piscina Naiadi', $htmlContent);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Pagamento confermato',
            'qr_code' => $qr_code
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore conferma pagamento', 'error' => $e->getMessage()]);
    }
}
