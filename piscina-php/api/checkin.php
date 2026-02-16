<?php
/**
 * API CHECK-IN
 * File: api/checkin.php
 * 
 * Endpoints:
 * POST /api/checkin.php - Registra check-in tramite QR (bagnino)
 * GET  /api/checkin.php?qr=xxx - Verifica QR code
 * GET  /api/checkin.php?action=history - Storico check-in utente
 * GET  /api/checkin.php?action=today - Check-in di oggi (bagnino)
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST' && empty($action)) {
    registraCheckIn();
} elseif ($method === 'GET' && isset($_GET['qr'])) {
    verificaQR();
} elseif ($method === 'GET' && $action === 'history') {
    getHistory();
} elseif ($method === 'GET' && $action === 'today') {
    getTodayCheckIns();
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint non trovato']);
}

/**
 * VERIFICA QR CODE
 */
function verificaQR() {
    global $pdo;
    
    $currentUser = requireRole(2); // Bagnino minimo
    $qr_code = $_GET['qr'] ?? '';
    
    if (!$qr_code) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Codice QR mancante']);
        return;
    }
    
    try {
        // Recupera acquisto
        $stmt = $pdo->prepare("
            SELECT a.*, 
                   p.nome as pacchetto_nome,
                   prof.nome as user_nome,
                   prof.cognome as user_cognome,
                   prof.telefono as user_telefono
            FROM acquisti a
            JOIN pacchetti p ON a.pacchetto_id = p.id
            JOIN profili prof ON a.user_id = prof.id
            WHERE a.qr_code = ? AND a.stato_pagamento = 'confirmed'
        ");
        $stmt->execute([$qr_code]);
        $acquisto = $stmt->fetch();
        
        if (!$acquisto) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'QR code non valido o acquisto non confermato'
            ]);
            return;
        }
        
        // Verifica scadenza
        if ($acquisto['data_scadenza'] && $acquisto['data_scadenza'] < date('Y-m-d')) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Pacchetto scaduto',
                'data_scadenza' => $acquisto['data_scadenza']
            ]);
            return;
        }
        
        // Verifica ingressi rimanenti
        if ($acquisto['ingressi_rimanenti'] <= 0) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Ingressi esauriti',
                'ingressi_rimanenti' => 0
            ]);
            return;
        }
        
        // Verifica doppio check-in nella stessa fascia oraria (ultimi 4 ore)
        $fascia_corrente = getFasciaOraria(date('H:i:s'));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM check_ins
            WHERE acquisto_id = ?
              AND DATE(timestamp) = CURDATE()
              AND fascia_oraria = ?
        ");
        $stmt->execute([$acquisto['id'], $fascia_corrente]);
        $check = $stmt->fetch();
        
        if ($check['count'] > 0) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Check-in già effettuato in questa fascia oraria oggi',
                'fascia_oraria' => $fascia_corrente
            ]);
            return;
        }
        
        // QR valido
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'valid' => true,
            'message' => 'QR code valido',
            'acquisto' => [
                'id' => $acquisto['id'],
                'pacchetto_nome' => $acquisto['pacchetto_nome'],
                'ingressi_rimanenti' => $acquisto['ingressi_rimanenti'],
                'data_scadenza' => $acquisto['data_scadenza']
            ],
            'utente' => [
                'nome' => $acquisto['user_nome'],
                'cognome' => $acquisto['user_cognome'],
                'telefono' => $acquisto['user_telefono']
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore verifica QR', 'error' => $e->getMessage()]);
    }
}

/**
 * REGISTRA CHECK-IN
 */
function registraCheckIn() {
    global $pdo;
    
    $currentUser = requireRole(2); // Bagnino minimo
    $data = json_decode(file_get_contents('php://input'), true);
    
    $qr_code = $data['qr_code'] ?? '';
    $note = sanitizeInput($data['note'] ?? '');
    
    if (!$qr_code) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Codice QR mancante']);
        return;
    }
    
    try {
        // Recupera acquisto
        $stmt = $pdo->prepare("
            SELECT a.*, prof.email, prof.nome, prof.cognome
            FROM acquisti a
            JOIN profili prof ON a.user_id = prof.id
            WHERE a.qr_code = ? AND a.stato_pagamento = 'confirmed'
        ");
        $stmt->execute([$qr_code]);
        $acquisto = $stmt->fetch();
        
        if (!$acquisto) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'QR code non valido']);
            return;
        }
        
        // Verifica scadenza
        if ($acquisto['data_scadenza'] && $acquisto['data_scadenza'] < date('Y-m-d')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Pacchetto scaduto']);
            return;
        }
        
        // Verifica ingressi rimanenti
        if ($acquisto['ingressi_rimanenti'] <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ingressi esauriti']);
            return;
        }
        
        // Verifica doppio check-in nella stessa fascia
        $fascia_corrente = getFasciaOraria(date('H:i:s'));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM check_ins
            WHERE acquisto_id = ?
              AND DATE(timestamp) = CURDATE()
              AND fascia_oraria = ?
        ");
        $stmt->execute([$acquisto['id'], $fascia_corrente]);
        $check = $stmt->fetch();
        
        if ($check['count'] > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Check-in già effettuato oggi in questa fascia oraria']);
            return;
        }
        
        // Inserisci check-in
        $stmt = $pdo->prepare("
            INSERT INTO check_ins (id, acquisto_id, user_id, bagnino_id, fascia_oraria, note)
            VALUES (UUID(), ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $acquisto['id'],
            $acquisto['user_id'],
            $currentUser['user_id'],
            $fascia_corrente,
            $note
        ]);
        
        // Decrementa ingressi rimanenti
        $nuovi_ingressi = $acquisto['ingressi_rimanenti'] - 1;
        $stmt = $pdo->prepare("UPDATE acquisti SET ingressi_rimanenti = ? WHERE id = ?");
        $stmt->execute([$nuovi_ingressi, $acquisto['id']]);
        
        // Log attività
        logActivity($currentUser['user_id'], 'check_in', "Check-in registrato per {$acquisto['nome']} {$acquisto['cognome']}", 'check_ins', $acquisto['id']);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Check-in registrato con successo',
            'ingressi_rimanenti' => $nuovi_ingressi
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore registrazione check-in', 'error' => $e->getMessage()]);
    }
}

/**
 * STORICO CHECK-IN UTENTE
 */
function getHistory() {
    global $pdo;
    
    $currentUser = requireAuth();
    
    $stmt = $pdo->prepare("
        SELECT c.*, 
               a.qr_code,
               p.nome as pacchetto_nome,
               prof.nome as bagnino_nome,
               prof.cognome as bagnino_cognome
        FROM check_ins c
        JOIN acquisti a ON c.acquisto_id = a.id
        JOIN pacchetti p ON a.pacchetto_id = p.id
        JOIN profili prof ON c.bagnino_id = prof.id
        WHERE c.user_id = ?
        ORDER BY c.timestamp DESC
        LIMIT 100
    ");
    $stmt->execute([$currentUser['user_id']]);
    $checkins = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'checkins' => $checkins]);
}

/**
 * CHECK-IN DI OGGI (per bagnino)
 */
function getTodayCheckIns() {
    global $pdo;
    
    $currentUser = requireRole(2); // Bagnino minimo
    
    $stmt = $pdo->prepare("
        SELECT c.*,
               prof.nome as user_nome,
               prof.cognome as user_cognome,
               prof.telefono as user_telefono,
               p.nome as pacchetto_nome
        FROM check_ins c
        JOIN profili prof ON c.user_id = prof.id
        JOIN acquisti a ON c.acquisto_id = a.id
        JOIN pacchetti p ON a.pacchetto_id = p.id
        WHERE DATE(c.timestamp) = CURDATE()
        ORDER BY c.timestamp DESC
    ");
    $stmt->execute();
    $checkins = $stmt->fetchAll();
    
    // Statistiche
    $mattina = 0;
    $pomeriggio = 0;
    foreach ($checkins as $c) {
        if ($c['fascia_oraria'] === 'mattina') $mattina++;
        else $pomeriggio++;
    }
    
    echo json_encode([
        'success' => true,
        'checkins' => $checkins,
        'stats' => [
            'totale' => count($checkins),
            'mattina' => $mattina,
            'pomeriggio' => $pomeriggio
        ]
    ]);
}
