<?php
declare(strict_types=1);

/**
 * API statistiche/report per admin e segreteria
 */

require_once __DIR__ . '/config.php';

requireRole(3);

$action = (string)($_GET['action'] ?? 'dashboard');

switch ($action) {
    case 'dashboard':
        getDashboardStats();
        break;
    case 'report-daily':
        getDailyReport();
        break;
    case 'report-timeseries':
        getTimeSeriesReport();
        break;
    case 'payment-breakdown':
        getPaymentBreakdown();
        break;
    case 'export-users':
        exportUsers();
        break;
    default:
        sendJson(400, ['success' => false, 'message' => 'Azione non valida']);
}

function getDashboardStats(): void
{
    global $pdo;

    try {
        $totaleUtenti = (int)$pdo->query(
            'SELECT COUNT(*) AS total
             FROM profili p
             JOIN ruoli r ON r.id = p.ruolo_id
             WHERE r.nome = "utente" AND p.attivo = 1'
        )->fetch()['total'];

        $checkinOggi = (int)$pdo->query('SELECT COUNT(*) AS total FROM check_ins WHERE DATE(timestamp) = CURDATE()')->fetch()['total'];

        $checkinMese = (int)$pdo->query(
            'SELECT COUNT(*) AS total
             FROM check_ins
             WHERE YEAR(timestamp) = YEAR(CURDATE())
               AND MONTH(timestamp) = MONTH(CURDATE())'
        )->fetch()['total'];

        $incassiMese = (float)$pdo->query(
            'SELECT COALESCE(SUM(importo_pagato), 0) AS total
             FROM acquisti
             WHERE stato_pagamento = "confirmed"
               AND YEAR(COALESCE(data_conferma, data_acquisto)) = YEAR(CURDATE())
               AND MONTH(COALESCE(data_conferma, data_acquisto)) = MONTH(CURDATE())'
        )->fetch()['total'];

        $acquistiPending = (int)$pdo->query('SELECT COUNT(*) AS total FROM acquisti WHERE stato_pagamento = "pending"')->fetch()['total'];
        $documentiPending = (int)$pdo->query('SELECT COUNT(*) AS total FROM documenti_utente WHERE stato = "pending"')->fetch()['total'];

        $pacchettiInScadenza = (int)$pdo->query(
            'SELECT COUNT(*) AS total
             FROM acquisti
             WHERE stato_pagamento = "confirmed"
               AND data_scadenza BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
        )->fetch()['total'];

        $stmt = $pdo->query(
            'SELECT c.timestamp, c.fascia_oraria,
                    u.nome, u.cognome,
                    p.nome AS pacchetto_nome
             FROM check_ins c
             JOIN profili u ON u.id = c.user_id
             JOIN acquisti a ON a.id = c.acquisto_id
             JOIN pacchetti p ON p.id = a.pacchetto_id
             ORDER BY c.timestamp DESC
             LIMIT 10'
        );
        $latestCheckins = $stmt->fetchAll();

        sendJson(200, [
            'success' => true,
            'stats' => [
                'totale_utenti' => $totaleUtenti,
                'checkin_oggi' => $checkinOggi,
                'checkin_mese' => $checkinMese,
                'incassi_mese' => $incassiMese,
                'acquisti_pending' => $acquistiPending,
                'documenti_pending' => $documentiPending,
                'pacchetti_in_scadenza' => $pacchettiInScadenza,
            ],
            'ultimi_checkin' => $latestCheckins,
        ]);
    } catch (Throwable $e) {
        error_log('getDashboardStats error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore recupero dashboard']);
    }
}

function getDailyReport(): void
{
    global $pdo;

    $date = sanitizeText((string)($_GET['data'] ?? date('Y-m-d')), 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT c.id, c.timestamp, c.fascia_oraria, c.note,
                    u.nome, u.cognome, u.telefono,
                    p.nome AS pacchetto_nome,
                    b.nome AS bagnino_nome, b.cognome AS bagnino_cognome
             FROM check_ins c
             JOIN profili u ON u.id = c.user_id
             JOIN profili b ON b.id = c.bagnino_id
             JOIN acquisti a ON a.id = c.acquisto_id
             JOIN pacchetti p ON p.id = a.pacchetto_id
             WHERE DATE(c.timestamp) = ?
             ORDER BY c.timestamp ASC'
        );
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll();

        $mattina = 0;
        $pomeriggio = 0;
        foreach ($rows as $row) {
            if ($row['fascia_oraria'] === 'mattina') {
                $mattina++;
            } else {
                $pomeriggio++;
            }
        }

        sendJson(200, [
            'success' => true,
            'data' => $date,
            'totale' => count($rows),
            'mattina' => $mattina,
            'pomeriggio' => $pomeriggio,
            'checkins' => $rows,
        ]);
    } catch (Throwable $e) {
        error_log('getDailyReport error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore report giornaliero']);
    }
}

function getTimeSeriesReport(): void
{
    global $pdo;

    $metric = sanitizeText((string)($_GET['metric'] ?? 'entrate'), 20);
    if (!in_array($metric, ['entrate', 'checkin', 'pagamenti'], true)) {
        $metric = 'entrate';
    }

    [$fromDate, $toDate] = resolveRange(
        sanitizeText((string)($_GET['period'] ?? '30d'), 20),
        sanitizeText((string)($_GET['from'] ?? ''), 10),
        sanitizeText((string)($_GET['to'] ?? ''), 10)
    );

    try {
        if ($metric === 'entrate') {
            $stmt = $pdo->prepare(
                'SELECT DATE(COALESCE(data_conferma, data_acquisto)) AS giorno,
                        COALESCE(SUM(importo_pagato), 0) AS valore
                 FROM acquisti
                 WHERE stato_pagamento = "confirmed"
                   AND DATE(COALESCE(data_conferma, data_acquisto)) BETWEEN ? AND ?
                 GROUP BY DATE(COALESCE(data_conferma, data_acquisto))
                 ORDER BY giorno ASC'
            );
        } elseif ($metric === 'checkin') {
            $stmt = $pdo->prepare(
                'SELECT DATE(timestamp) AS giorno, COUNT(*) AS valore
                 FROM check_ins
                 WHERE DATE(timestamp) BETWEEN ? AND ?
                 GROUP BY DATE(timestamp)
                 ORDER BY giorno ASC'
            );
        } else {
            $stmt = $pdo->prepare(
                'SELECT DATE(COALESCE(data_conferma, data_acquisto)) AS giorno, COUNT(*) AS valore
                 FROM acquisti
                 WHERE stato_pagamento = "confirmed"
                   AND DATE(COALESCE(data_conferma, data_acquisto)) BETWEEN ? AND ?
                 GROUP BY DATE(COALESCE(data_conferma, data_acquisto))
                 ORDER BY giorno ASC'
            );
        }

        $stmt->execute([$fromDate, $toDate]);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['giorno']] = (float)$row['valore'];
        }

        $points = [];
        $cursor = strtotime($fromDate);
        $end = strtotime($toDate);
        while ($cursor <= $end) {
            $day = date('Y-m-d', $cursor);
            $points[] = [
                'date' => $day,
                'value' => $map[$day] ?? 0,
            ];
            $cursor = strtotime('+1 day', $cursor);
        }

        sendJson(200, [
            'success' => true,
            'metric' => $metric,
            'from' => $fromDate,
            'to' => $toDate,
            'points' => $points,
        ]);
    } catch (Throwable $e) {
        error_log('getTimeSeriesReport error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento serie temporale']);
    }
}

function getPaymentBreakdown(): void
{
    global $pdo;

    [$fromDate, $toDate] = resolveRange(
        sanitizeText((string)($_GET['period'] ?? '30d'), 20),
        sanitizeText((string)($_GET['from'] ?? ''), 10),
        sanitizeText((string)($_GET['to'] ?? ''), 10)
    );

    try {
        $stmt = $pdo->prepare(
            'SELECT metodo_pagamento,
                    COUNT(*) AS totale_pagamenti,
                    COALESCE(SUM(importo_pagato), 0) AS totale_importo
             FROM acquisti
             WHERE stato_pagamento = "confirmed"
               AND DATE(COALESCE(data_conferma, data_acquisto)) BETWEEN ? AND ?
             GROUP BY metodo_pagamento
             ORDER BY totale_importo DESC'
        );
        $stmt->execute([$fromDate, $toDate]);

        sendJson(200, [
            'success' => true,
            'from' => $fromDate,
            'to' => $toDate,
            'breakdown' => $stmt->fetchAll(),
        ]);
    } catch (Throwable $e) {
        error_log('getPaymentBreakdown error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento pagamenti']);
    }
}

function exportUsers(): void
{
    global $pdo;

    try {
        $stmt = $pdo->query(
            'SELECT p.id, p.email, p.nome, p.cognome, p.telefono, p.data_nascita,
                    p.citta, p.cap, p.codice_fiscale, p.attivo, p.email_verificata,
                    p.created_at, r.nome AS ruolo
             FROM profili p
             JOIN ruoli r ON r.id = p.ruolo_id
             ORDER BY p.created_at DESC'
        );
        $users = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="utenti_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Email', 'Nome', 'Cognome', 'Telefono', 'Data Nascita', 'Citta', 'CAP', 'Codice Fiscale', 'Ruolo', 'Attivo', 'Email Verificata', 'Data Registrazione']);

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
                (int)$user['attivo'] === 1 ? 'Si' : 'No',
                (int)$user['email_verificata'] === 1 ? 'Si' : 'No',
                $user['created_at'],
            ]);
        }

        fclose($output);
        exit();
    } catch (Throwable $e) {
        error_log('exportUsers error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore export utenti']);
    }
}

function resolveRange(string $period, string $from, string $to): array
{
    $today = date('Y-m-d');

    if ($period === '7d') {
        return [date('Y-m-d', strtotime('-6 days')), $today];
    }

    if ($period === '30d') {
        return [date('Y-m-d', strtotime('-29 days')), $today];
    }

    if ($period === '3m') {
        return [date('Y-m-d', strtotime('-3 months +1 day')), $today];
    }

    if ($period === 'custom' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        if ($from > $to) {
            return [$to, $from];
        }
        return [$from, $to];
    }

    return [date('Y-m-d', strtotime('-29 days')), $today];
}