<?php
/**
 * API STATISTICHE E ADMIN
 * File: api/stats.php
 * 
 * Endpoints (solo ufficio/admin):
 * GET /api/stats.php?action=dashboard - Dashboard generale
 * GET /api/stats.php?action=report-daily - Report giornaliero
 * GET /api/stats.php?action=export-users - Export utenti Excel
 */

require_once 'config.php';

$currentUser = requireRole(3); // Ufficio minimo

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
        getDashboardStats();
        break;
    case 'report-daily':
        getDailyReport();
        break;
    case 'export-users':
        exportUsers();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}

/**
 * DASHBOARD STATS
 */
function getDashboardStats() {
    global $pdo;
    
    try {
        // Totale utenti
        $stmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM profili p
            JOIN ruoli r ON p.ruolo_id = r.id
            WHERE r.nome = 'utente' AND p.attivo = TRUE
        ");
        $totaleUtenti = $stmt->fetch()['count'];
        
        // Totale check-in oggi
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM check_ins WHERE DATE(timestamp) = CURDATE()");
        $checkinOggi = $stmt->fetch()['count'];
        
        // Totale check-in mese corrente
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM check_ins WHERE YEAR(timestamp) = YEAR(NOW()) AND MONTH(timestamp) = MONTH(NOW())");
        $checkinMese = $stmt->fetch()['count'];
        
        // Incassi mese corrente
        $stmt = $pdo->query("SELECT COALESCE(SUM(importo_pagato), 0) as totale FROM acquisti WHERE stato_pagamento = 'confirmed' AND YEAR(data_conferma) = YEAR(NOW()) AND MONTH(data_conferma) = MONTH(NOW())");
        $incassiMese = $stmt->fetch()['totale'];
        
        // Acquisti pending
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM acquisti WHERE stato_pagamento = 'pending'");
        $acquistiPending = $stmt->fetch()['count'];
        
        // Documenti da revisionare
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM documenti_utente WHERE stato = 'pending'");
        $documentiPending = $stmt->fetch()['count'];
        
        // Pacchetti in scadenza (prossimi 30 giorni)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM acquisti WHERE stato_pagamento = 'confirmed' AND data_scadenza BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $pacchettiScadenza = $stmt->fetch()['count'];
        
        // Ultimi 10 check-in
        $stmt = $pdo->query("
            SELECT c.timestamp, c.fascia_oraria,
                   prof.nome, prof.cognome,
                   p.nome as pacchetto_nome
            FROM check_ins c
            JOIN profili prof ON c.user_id = prof.id
            JOIN acquisti a ON c.acquisto_id = a.id
            JOIN pacchetti p ON a.pacchetto_id = p.id
            ORDER BY c.timestamp DESC
            LIMIT 10
        ");
        $ultimiCheckin = $stmt->fetchAll();
        
        // Check-in per fascia oraria (ultimi 7 giorni)
        $stmt = $pdo->query("
            SELECT DATE(timestamp) as data,
                   fascia_oraria,
                   COUNT(*) as count
            FROM check_ins
            WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(timestamp), fascia_oraria
            ORDER BY data DESC
        ");
        $checkinPerFascia = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'totale_utenti' => (int)$totaleUtenti,
                'checkin_oggi' => (int)$checkinOggi,
                'checkin_mese' => (int)$checkinMese,
                'incassi_mese' => (float)$incassiMese,
                'acquisti_pending' => (int)$acquistiPending,
                'documenti_pending' => (int)$documentiPending,
                'pacchetti_in_scadenza' => (int)$pacchettiScadenza
            ],
            'ultimi_checkin' => $ultimiCheckin,
            'checkin_per_fascia' => $checkinPerFascia
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore recupero statistiche', 'error' => $e->getMessage()]);
    }
}

/**
 * REPORT GIORNALIERO
 */
function getDailyReport() {
    global $pdo;
    
    $data = $_GET['data'] ?? date('Y-m-d');
    
    try {
        // Check-in del giorno
        $stmt = $pdo->prepare("
            SELECT c.*,
                   prof.nome, prof.cognome, prof.telefono,
                   p.nome as pacchetto_nome,
                   bagnino.nome as bagnino_nome,
                   bagnino.cognome as bagnino_cognome
            FROM check_ins c
            JOIN profili prof ON c.user_id = prof.id
            JOIN profili bagnino ON c.bagnino_id = bagnino.id
            JOIN acquisti a ON c.acquisto_id = a.id
            JOIN pacchetti p ON a.pacchetto_id = p.id
            WHERE DATE(c.timestamp) = ?
            ORDER BY c.timestamp ASC
        ");
        $stmt->execute([$data]);
        $checkins = $stmt->fetchAll();
        
        // Conteggi
        $mattina = 0;
        $pomeriggio = 0;
        foreach ($checkins as $c) {
            if ($c['fascia_oraria'] === 'mattina') $mattina++;
            else $pomeriggio++;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'totale' => count($checkins),
            'mattina' => $mattina,
            'pomeriggio' => $pomeriggio,
            'checkins' => $checkins
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore report giornaliero', 'error' => $e->getMessage()]);
    }
}

/**
 * EXPORT UTENTI (ritorna CSV)
 */
function exportUsers() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT p.id, p.email, p.nome, p.cognome, p.telefono, p.data_nascita,
                   p.citta, p.cap, p.codice_fiscale, p.attivo, p.email_verificata,
                   p.created_at, r.nome as ruolo
            FROM profili p
            JOIN ruoli r ON p.ruolo_id = r.id
            ORDER BY p.created_at DESC
        ");
        $users = $stmt->fetchAll();
        
        // CSV header
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="utenti_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header colonne
        fputcsv($output, ['ID', 'Email', 'Nome', 'Cognome', 'Telefono', 'Data Nascita', 'Città', 'CAP', 'Codice Fiscale', 'Ruolo', 'Attivo', 'Email Verificata', 'Data Registrazione']);
        
        // Dati
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['email'],
                $user['nome'],
                $user['cognome'],
                $user['telefono'],
                $user['data_nascita'],
                $user['citta'],
                $user['cap'],
                $user['codice_fiscale'],
                $user['ruolo'],
                $user['attivo'] ? 'Sì' : 'No',
                $user['email_verificata'] ? 'Sì' : 'No',
                $user['created_at']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore export utenti', 'error' => $e->getMessage()]);
    }
}
