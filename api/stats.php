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
    case 'report-daily-csv':
        exportDailyReportCsv();
        break;
    case 'report-daily-pdf':
        exportDailyReportPdf();
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
    case 'export-users-pdf':
        exportUsersPdf();
        break;
    case 'export-purchases':
        exportPurchasesCsv();
        break;
    case 'export-purchases-pdf':
        exportPurchasesPdf();
        break;
    case 'export-checkins':
        exportCheckinsCsv();
        break;
    case 'export-checkins-pdf':
        exportCheckinsPdf();
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
                    u.nome, u.cognome, u.telefono,
                    p.nome AS pacchetto_nome,
                    a.qr_code
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
    $date = resolveReportDate();

    try {
        $report = fetchDailyReportData($date);
        sendJson(200, [
            'success' => true,
            'data' => $date,
            'totale' => $report['totale'],
            'mattina' => $report['mattina'],
            'pomeriggio' => $report['pomeriggio'],
            'checkins' => $report['rows'],
        ]);
    } catch (Throwable $e) {
        error_log('getDailyReport error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore report giornaliero']);
    }
}

function exportDailyReportPdf(): void
{
    $date = resolveReportDate();

    try {
        $report = fetchDailyReportData($date);
    } catch (Throwable $e) {
        error_log('exportDailyReportPdf data error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento dati report PDF']);
    }

    if (!class_exists('TCPDF')) {
        sendJson(500, ['success' => false, 'message' => 'TCPDF non disponibile. Eseguire composer install.']);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Gli Squaletti');
    $pdf->SetAuthor('Dashboard Ufficio/Admin');
    $pdf->SetTitle('Report giornaliero check-in ' . $date);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'Report Giornaliero Check-in', 0, 1, 'L');

    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 7, 'Data: ' . $date, 0, 1, 'L');
    $pdf->Cell(
        0,
        7,
        'Totale: ' . $report['totale'] . ' | Mattina: ' . $report['mattina'] . ' | Pomeriggio: ' . $report['pomeriggio'],
        0,
        1,
        'L'
    );
    $pdf->Ln(2);

    $rowsHtml = '';
    foreach ($report['rows'] as $row) {
        $time = date('H:i', strtotime((string)$row['timestamp']));
        $rowsHtml .= '<tr>'
            . '<td>' . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars((string)$row['nome'] . ' ' . (string)$row['cognome'], ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars((string)($row['telefono'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars((string)($row['qr_code'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars((string)($row['fascia_oraria'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars((string)($row['pacchetto_nome'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars((string)$row['bagnino_nome'] . ' ' . (string)$row['bagnino_cognome'], ENT_QUOTES, 'UTF-8') . '</td>'
            . '</tr>';
    }

    if ($rowsHtml === '') {
        $rowsHtml = '<tr><td colspan="7">Nessun check-in registrato</td></tr>';
    }

    $html = '<table border="1" cellpadding="5">'
        . '<thead>'
        . '<tr style="background-color:#f2f2f2;">'
        . '<th width="8%"><strong>Ora</strong></th>'
        . '<th width="18%"><strong>Utente</strong></th>'
        . '<th width="13%"><strong>Telefono</strong></th>'
        . '<th width="21%"><strong>QR</strong></th>'
        . '<th width="10%"><strong>Fascia</strong></th>'
        . '<th width="17%"><strong>Pacchetto</strong></th>'
        . '<th width="13%"><strong>Bagnino</strong></th>'
        . '</tr>'
        . '</thead>'
        . '<tbody>' . $rowsHtml . '</tbody>'
        . '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    header_remove('Content-Type');
    header('Content-Type: application/pdf');
    header('Cache-Control: private, max-age=0, must-revalidate');
    $pdf->Output('report_checkin_' . $date . '.pdf', 'D');
    exit();
}

function resolveReportDate(): string
{
    $date = sanitizeText((string)($_GET['data'] ?? date('Y-m-d')), 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return date('Y-m-d');
    }

    return $date;
}

function fetchDailyReportData(string $date): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT c.id, c.timestamp, c.fascia_oraria, c.note,
                u.nome, u.cognome, u.telefono,
                p.nome AS pacchetto_nome, a.qr_code, a.id AS acquisto_id,
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
        if ((string)$row['fascia_oraria'] === 'mattina') {
            $mattina++;
        } else {
            $pomeriggio++;
        }
    }

    return [
        'rows' => $rows,
        'totale' => count($rows),
        'mattina' => $mattina,
        'pomeriggio' => $pomeriggio,
    ];
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
                    p.created_at, r.nome AS ruolo,
                    COALESCE((SELECT SUM(a.ingressi_rimanenti) FROM acquisti a WHERE a.user_id = p.id AND a.stato_pagamento = "confirmed"), 0) AS ingressi_rimanenti,
                    COALESCE((SELECT COUNT(*) FROM check_ins c WHERE c.user_id = p.id), 0) AS totale_checkin
             FROM profili p
             JOIN ruoli r ON r.id = p.ruolo_id
             ORDER BY p.created_at DESC'
        );
        $users = $stmt->fetchAll();

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
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
                $user['ingressi_rimanenti'],
                $user['totale_checkin'],
                $user['created_at'],
            ];
        }

        outputCsv(
            'utenti_' . date('Y-m-d') . '.csv',
            ['ID', 'Email', 'Nome', 'Cognome', 'Telefono', 'Data Nascita', 'Citta', 'CAP', 'Codice Fiscale', 'Ruolo', 'Attivo', 'Email Verificata', 'Ingressi Rimanenti', 'Totale Check-in', 'Data Registrazione'],
            $rows
        );
    } catch (Throwable $e) {
        error_log('exportUsers error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore export utenti']);
    }
}

function exportDailyReportCsv(): void
{
    $date = resolveReportDate();

    try {
        $report = fetchDailyReportData($date);
        $rows = [];
        foreach ($report['rows'] as $row) {
            $rows[] = [
                date('H:i', strtotime((string)$row['timestamp'])),
                (string)$row['nome'] . ' ' . (string)$row['cognome'],
                (string)($row['telefono'] ?? ''),
                (string)($row['qr_code'] ?? ''),
                (string)$row['fascia_oraria'],
                (string)$row['pacchetto_nome'],
                (string)$row['bagnino_nome'] . ' ' . (string)$row['bagnino_cognome'],
                (string)($row['note'] ?? ''),
            ];
        }

        outputCsv(
            'report_checkin_' . $date . '.csv',
            ['Ora', 'Utente', 'Telefono', 'QR Code', 'Fascia', 'Pacchetto', 'Bagnino', 'Note'],
            $rows
        );
    } catch (Throwable $e) {
        error_log('exportDailyReportCsv error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore export CSV report giornaliero']);
    }
}

function exportUsersPdf(): void
{
    global $pdo;

    try {
        $stmt = $pdo->query(
            'SELECT p.id, p.email, p.nome, p.cognome, p.telefono, r.nome AS ruolo, p.attivo,
                    p.created_at,
                    COALESCE((SELECT SUM(a.ingressi_rimanenti) FROM acquisti a WHERE a.user_id = p.id AND a.stato_pagamento = "confirmed"), 0) AS ingressi_rimanenti
             FROM profili p
             JOIN ruoli r ON r.id = p.ruolo_id
             ORDER BY p.created_at DESC'
        );
        $rows = $stmt->fetchAll();

        $tableRows = [];
        foreach ($rows as $row) {
            $tableRows[] = [
                (string)$row['nome'] . ' ' . (string)$row['cognome'],
                (string)$row['email'],
                (string)$row['telefono'],
                (string)$row['ruolo'],
                (int)$row['ingressi_rimanenti'],
                (int)$row['attivo'] === 1 ? 'Si' : 'No',
                (string)$row['created_at'],
            ];
        }

        outputPdfTable(
            'Export utenti',
            ['Nome', 'Email', 'Telefono', 'Ruolo', 'Ingressi', 'Attivo', 'Registrazione'],
            $tableRows,
            'utenti_' . date('Y-m-d') . '.pdf'
        );
    } catch (Throwable $e) {
        error_log('exportUsersPdf error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore export PDF utenti']);
    }
}

function exportPurchasesCsv(): void
{
    global $pdo;

    try {
        $stmt = $pdo->query(
            'SELECT a.id, a.data_acquisto, a.data_conferma, a.metodo_pagamento, a.stato_pagamento,
                    a.riferimento_pagamento, a.note_pagamento, a.importo_pagato, a.ingressi_rimanenti, a.data_scadenza, a.qr_code,
                    p.nome AS pacchetto_nome,
                    u.nome AS user_nome, u.cognome AS user_cognome, u.email AS user_email
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = a.user_id
             ORDER BY a.data_acquisto DESC'
        );
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['id'],
                (string)$row['user_nome'] . ' ' . (string)$row['user_cognome'],
                $row['user_email'],
                $row['pacchetto_nome'],
                $row['metodo_pagamento'],
                $row['stato_pagamento'],
                number_format((float)$row['importo_pagato'], 2, '.', ''),
                $row['ingressi_rimanenti'],
                $row['qr_code'],
                $row['riferimento_pagamento'],
                $row['data_acquisto'],
                $row['data_conferma'],
                $row['data_scadenza'],
                $row['note_pagamento'],
            ];
        }

        outputCsv(
            'acquisti_' . date('Y-m-d') . '.csv',
            ['ID Acquisto', 'Utente', 'Email', 'Pacchetto', 'Metodo', 'Stato', 'Importo', 'Ingressi Rimanenti', 'QR Code', 'Riferimento', 'Data Acquisto', 'Data Conferma', 'Scadenza', 'Note'],
            $rows
        );
    } catch (Throwable $e) {
        error_log('exportPurchasesCsv error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore export acquisti CSV']);
    }
}

function exportPurchasesPdf(): void
{
    global $pdo;

    try {
        $stmt = $pdo->query(
            'SELECT a.id, a.data_acquisto, a.stato_pagamento, a.importo_pagato, a.ingressi_rimanenti, a.qr_code,
                    p.nome AS pacchetto_nome,
                    u.nome AS user_nome, u.cognome AS user_cognome
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = a.user_id
             ORDER BY a.data_acquisto DESC'
        );
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                (string)$row['user_nome'] . ' ' . (string)$row['user_cognome'],
                (string)$row['pacchetto_nome'],
                (string)$row['stato_pagamento'],
                number_format((float)$row['importo_pagato'], 2, ',', '.'),
                (string)$row['ingressi_rimanenti'],
                (string)($row['qr_code'] ?? ''),
                (string)$row['data_acquisto'],
            ];
        }

        outputPdfTable(
            'Export acquisti',
            ['Utente', 'Pacchetto', 'Stato', 'Importo EUR', 'Ingressi', 'QR', 'Data acquisto'],
            $rows,
            'acquisti_' . date('Y-m-d') . '.pdf'
        );
    } catch (Throwable $e) {
        error_log('exportPurchasesPdf error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore export acquisti PDF']);
    }
}

function exportCheckinsCsv(): void
{
    global $pdo;

    try {
        $stmt = $pdo->query(
            'SELECT c.id, c.timestamp, c.fascia_oraria, c.note,
                    a.qr_code, p.nome AS pacchetto_nome,
                    u.nome AS user_nome, u.cognome AS user_cognome, u.telefono AS user_telefono,
                    b.nome AS bagnino_nome, b.cognome AS bagnino_cognome
             FROM check_ins c
             JOIN acquisti a ON a.id = c.acquisto_id
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = c.user_id
             JOIN profili b ON b.id = c.bagnino_id
             ORDER BY c.timestamp DESC'
        );

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['id'],
                $row['timestamp'],
                (string)$row['user_nome'] . ' ' . (string)$row['user_cognome'],
                $row['user_telefono'],
                $row['pacchetto_nome'],
                $row['qr_code'],
                $row['fascia_oraria'],
                (string)$row['bagnino_nome'] . ' ' . (string)$row['bagnino_cognome'],
                $row['note'],
            ];
        }

        outputCsv(
            'checkins_' . date('Y-m-d') . '.csv',
            ['ID Check-in', 'Timestamp', 'Utente', 'Telefono', 'Pacchetto', 'QR Code', 'Fascia', 'Bagnino', 'Note'],
            $rows
        );
    } catch (Throwable $e) {
        error_log('exportCheckinsCsv error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore export check-in CSV']);
    }
}

function exportCheckinsPdf(): void
{
    global $pdo;

    try {
        $stmt = $pdo->query(
            'SELECT c.timestamp, c.fascia_oraria, a.qr_code, p.nome AS pacchetto_nome,
                    u.nome AS user_nome, u.cognome AS user_cognome,
                    b.nome AS bagnino_nome, b.cognome AS bagnino_cognome
             FROM check_ins c
             JOIN acquisti a ON a.id = c.acquisto_id
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = c.user_id
             JOIN profili b ON b.id = c.bagnino_id
             ORDER BY c.timestamp DESC'
        );
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                (string)$row['timestamp'],
                (string)$row['user_nome'] . ' ' . (string)$row['user_cognome'],
                (string)$row['pacchetto_nome'],
                (string)($row['qr_code'] ?? ''),
                (string)$row['fascia_oraria'],
                (string)$row['bagnino_nome'] . ' ' . (string)$row['bagnino_cognome'],
            ];
        }

        outputPdfTable(
            'Export check-in',
            ['Timestamp', 'Utente', 'Pacchetto', 'QR', 'Fascia', 'Bagnino'],
            $rows,
            'checkins_' . date('Y-m-d') . '.pdf'
        );
    } catch (Throwable $e) {
        error_log('exportCheckinsPdf error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore export check-in PDF']);
    }
}

function outputCsv(string $filename, array $header, array $rows): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');

    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, $header, ';');
    foreach ($rows as $row) {
        fputcsv($output, $row, ';');
    }
    fclose($output);
    exit();
}

function outputPdfTable(string $title, array $header, array $rows, string $filename): void
{
    if (!class_exists('TCPDF')) {
        sendJson(500, ['success' => false, 'message' => 'TCPDF non disponibile. Eseguire composer install.']);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Gli Squaletti');
    $pdf->SetAuthor('Dashboard Staff');
    $pdf->SetTitle($title);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    $pdf->SetFont('dejavusans', 'B', 15);
    $pdf->Cell(0, 10, $title, 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 7, 'Generato il: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    $pdf->Ln(1);

    $colCount = max(1, count($header));
    $colWidth = round(100 / $colCount, 2);

    $thead = '<tr style="background-color:#f2f2f2;">';
    foreach ($header as $col) {
        $thead .= '<th width="' . $colWidth . '%"><strong>' . htmlspecialchars((string)$col, ENT_QUOTES, 'UTF-8') . '</strong></th>';
    }
    $thead .= '</tr>';

    $tbody = '';
    foreach ($rows as $row) {
        $tbody .= '<tr>';
        foreach ($row as $value) {
            $tbody .= '<td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        $tbody .= '</tr>';
    }
    if ($tbody === '') {
        $tbody = '<tr><td colspan="' . $colCount . '">Nessun dato disponibile</td></tr>';
    }

    $html = '<table border="1" cellpadding="4"><thead>' . $thead . '</thead><tbody>' . $tbody . '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    header_remove('Content-Type');
    header('Content-Type: application/pdf');
    header('Cache-Control: private, max-age=0, must-revalidate');
    $pdf->Output($filename, 'D');
    exit();
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
