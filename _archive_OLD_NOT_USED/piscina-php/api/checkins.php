<?php
// =====================================================
// CHECKINS CONTROLLER - Scanner QR e Registrazione
// =====================================================

require_once '../config.php';

session_start();

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'verifica':
        if ($method === 'POST') verificaQR($db);
        break;
    
    case 'registra':
        if ($method === 'POST') registraIngresso($db);
        break;
    
    case 'presenze-oggi':
        if ($method === 'GET') getPresenzeOggi($db);
        break;
    
    case 'storico':
        if ($method === 'GET') getStorico($db);
        break;
    
    default:
        jsonResponse(false, 'Azione non valida', null, 404);
}

// =====================================================
// VERIFICA QR CODE
// =====================================================
function verificaQR($db) {
    checkRole(2); // Minimo bagnino
    
    $data = json_decode(file_get_contents('php://input'), true);
    $qr_code = sanitizeInput($data['qr_code'] ?? '');
    
    if (!$qr_code) {
        jsonResponse(false, 'QR code mancante', null, 400);
    }
    
    try {
        // Find acquisto
        $stmt = $db->prepare("
            SELECT a.*, u.nome, u.cognome, p.nome as pacchetto_nome
            FROM acquisti a
            JOIN utenti u ON a.user_id = u.id
            JOIN pacchetti p ON a.pacchetto_id = p.id
            WHERE a.qr_code = :qr
        ");
        $stmt->execute([':qr' => $qr_code]);
        $acquisto = $stmt->fetch();
        
        if (!$acquisto) {
            jsonResponse(false, 'QR Code non valido', ['valid' => false], 404);
        }
        
        // Verifica stato pagamento
        if ($acquisto['stato_pagamento'] !== 'confermato') {
            jsonResponse(false, 'Pagamento non confermato', [
                'valid' => false,
                'reason' => 'payment_not_confirmed',
                'user' => $acquisto['nome'] . ' ' . $acquisto['cognome']
            ], 400);
        }
        
        // Verifica ingressi
        if ($acquisto['ingressi_rimanenti'] <= 0) {
            jsonResponse(false, 'Ingressi esauriti', [
                'valid' => false,
                'reason' => 'no_entries',
                'user' => $acquisto['nome'] . ' ' . $acquisto['cognome']
            ], 400);
        }
        
        // Verifica scadenza
        if (strtotime($acquisto['data_scadenza']) < time()) {
            jsonResponse(false, 'Pacchetto scaduto', [
                'valid' => false,
                'reason' => 'expired',
                'user' => $acquisto['nome'] . ' ' . $acquisto['cognome'],
                'data_scadenza' => $acquisto['data_scadenza']
            ], 400);
        }
        
        // Verifica doppio check-in (4 ore)
        $ultimo_checkin = verificaDoppioCheckin($db, $acquisto['user_id']);
        if ($ultimo_checkin) {
            $minuti_fa = round((time() - strtotime($ultimo_checkin['timestamp'])) / 60);
            jsonResponse(false, 'Check-in già effettuato', [
                'valid' => false,
                'reason' => 'duplicate_checkin',
                'user' => $acquisto['nome'] . ' ' . $acquisto['cognome'],
                'ultimo_checkin' => $ultimo_checkin['timestamp'],
                'minuti_fa' => $minuti_fa
            ], 400);
        }
        
        // QR valido!
        jsonResponse(true, 'QR Code valido', [
            'valid' => true,
            'acquisto_id' => $acquisto['id'],
            'user_id' => $acquisto['user_id'],
            'user' => $acquisto['nome'] . ' ' . $acquisto['cognome'],
            'pacchetto' => $acquisto['pacchetto_nome'],
            'ingressi_rimanenti' => $acquisto['ingressi_rimanenti']
        ]);
        
    } catch(PDOException $e) {
        error_log("Verifica QR error: " . $e->getMessage());
        jsonResponse(false, 'Errore verifica QR', null, 500);
    }
}

// =====================================================
// REGISTRA INGRESSO
// =====================================================
function registraIngresso($db) {
    checkRole(2); // Minimo bagnino
    
    $data = json_decode(file_get_contents('php://input'), true);
    $qr_code = sanitizeInput($data['qr_code'] ?? '');
    
    if (!$qr_code) {
        jsonResponse(false, 'QR code mancante', null, 400);
    }
    
    try {
        $db->beginTransaction();
        
        // Find acquisto
        $stmt = $db->prepare("
            SELECT a.*, u.nome, u.cognome
            FROM acquisti a
            JOIN utenti u ON a.user_id = u.id
            WHERE a.qr_code = :qr
        ");
        $stmt->execute([':qr' => $qr_code]);
        $acquisto = $stmt->fetch();
        
        if (!$acquisto || $acquisto['stato_pagamento'] !== 'confermato' || 
            $acquisto['ingressi_rimanenti'] <= 0 || 
            strtotime($acquisto['data_scadenza']) < time()) {
            $db->rollBack();
            jsonResponse(false, 'QR non valido', null, 400);
        }
        
        // Verifica doppio check-in
        if (verificaDoppioCheckin($db, $acquisto['user_id'])) {
            $db->rollBack();
            jsonResponse(false, 'Check-in già effettuato', null, 400);
        }
        
        // Registra check-in
        $fascia = getFasciaOraria();
        $stmt = $db->prepare("
            INSERT INTO check_ins (user_id, acquisto_id, bagnino_id, fascia_oraria)
            VALUES (:user_id, :acquisto_id, :bagnino_id, :fascia)
        ");
        $stmt->execute([
            ':user_id' => $acquisto['user_id'],
            ':acquisto_id' => $acquisto['id'],
            ':bagnino_id' => $_SESSION['user_id'],
            ':fascia' => $fascia
        ]);
        $checkin_id = $db->lastInsertId();
        
        // Scala ingresso
        $nuovi_ingressi = $acquisto['ingressi_rimanenti'] - 1;
        $stmt = $db->prepare("
            UPDATE acquisti
            SET ingressi_rimanenti = :nuovi
            WHERE id = :id
        ");
        $stmt->execute([
            ':nuovi' => $nuovi_ingressi,
            ':id' => $acquisto['id']
        ]);
        
        logActivity($db, $_SESSION['user_id'], 'check_in_registrato', 'check_ins', $checkin_id, [
            'user' => $acquisto['nome'] . ' ' . $acquisto['cognome'],
            'fascia' => $fascia
        ]);
        
        $db->commit();
        
        jsonResponse(true, 'Check-in registrato', [
            'user' => $acquisto['nome'] . ' ' . $acquisto['cognome'],
            'ingressi_rimanenti' => $nuovi_ingressi,
            'fascia' => $fascia
        ], 201);
        
    } catch(PDOException $e) {
        $db->rollBack();
        error_log("Registra ingresso error: " . $e->getMessage());
        jsonResponse(false, 'Errore registrazione ingresso', null, 500);
    }
}

// =====================================================
// PRESENZE OGGI
// =====================================================
function getPresenzeOggi($db) {
    checkRole(2); // Minimo bagnino
    
    try {
        $oggi = date('Y-m-d');
        
        // Stats
        $stmt = $db->prepare("
            SELECT fascia_oraria, COUNT(*) as count
            FROM check_ins
            WHERE DATE(timestamp) = :oggi
            GROUP BY fascia_oraria
        ");
        $stmt->execute([':oggi' => $oggi]);
        $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Lista
        $stmt = $db->prepare("
            SELECT c.*, u.nome, u.cognome, a.ingressi_rimanenti
            FROM check_ins c
            JOIN utenti u ON c.user_id = u.id
            JOIN acquisti a ON c.acquisto_id = a.id
            WHERE DATE(c.timestamp) = :oggi
            ORDER BY c.timestamp DESC
        ");
        $stmt->execute([':oggi' => $oggi]);
        $presenze = $stmt->fetchAll();
        
        jsonResponse(true, 'Presenze oggi', [
            'data' => $oggi,
            'totale' => array_sum($stats),
            'mattina' => $stats['mattina'] ?? 0,
            'pomeriggio' => $stats['pomeriggio'] ?? 0,
            'presenze' => $presenze
        ]);
        
    } catch(PDOException $e) {
        error_log("Presenze oggi error: " . $e->getMessage());
        jsonResponse(false, 'Errore recupero presenze', null, 500);
    }
}

// =====================================================
// STORICO
// =====================================================
function getStorico($db) {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $data = isset($_GET['data']) ? sanitizeInput($_GET['data']) : null;
    
    // Check permission
    if ($user_id && $user_id != $_SESSION['user_id']) {
        checkRole(2);
    } elseif (!$user_id) {
        $user_id = $_SESSION['user_id'];
    }
    
    try {
        $where = ["c.user_id = :user_id"];
        $params = [':user_id' => $user_id];
        
        if ($data) {
            $where[] = "DATE(c.timestamp) = :data";
            $params[':data'] = $data;
        }
        
        $where_sql = implode(' AND ', $where);
        
        $stmt = $db->prepare("
            SELECT c.*, u.nome as bagnino_nome, u.cognome as bagnino_cognome
            FROM check_ins c
            LEFT JOIN utenti u ON c.bagnino_id = u.id
            WHERE {$where_sql}
            ORDER BY c.timestamp DESC
        ");
        $stmt->execute($params);
        $storico = $stmt->fetchAll();
        
        jsonResponse(true, 'Storico recuperato', ['storico' => $storico]);
        
    } catch(PDOException $e) {
        error_log("Storico error: " . $e->getMessage());
        jsonResponse(false, 'Errore recupero storico', null, 500);
    }
}

?>
