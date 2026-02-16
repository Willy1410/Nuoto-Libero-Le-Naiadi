<?php

/**
 * QR code endpoint
 * GET /api/qr.php?action=download&acquisto_id=...
 */

require_once __DIR__ . '/config.php';

$action = (string)($_GET['action'] ?? 'download');
if ($action !== 'download') {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Azione non valida']);
    exit();
}

$currentUser = requireAuth();
$acquistoId = sanitizeInput($_GET['acquisto_id'] ?? '');

if ($acquistoId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID acquisto mancante']);
    exit();
}

try {
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT a.id, a.user_id, a.qr_code, a.stato_pagamento, a.ingressi_rimanenti, a.data_scadenza,
                a.data_acquisto, a.data_conferma,
                p.nome AS pacchetto_nome,
                prof.nome AS user_nome,
                prof.cognome AS user_cognome,
                prof.email AS user_email
         FROM acquisti a
         JOIN pacchetti p ON p.id = a.pacchetto_id
         JOIN profili prof ON prof.id = a.user_id
         WHERE a.id = ?
         LIMIT 1'
    );
    $stmt->execute([$acquistoId]);
    $acquisto = $stmt->fetch();

    if (!$acquisto) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Acquisto non trovato']);
        exit();
    }

    $roleLevel = getUserRoleLevel((string)$currentUser['user_id']);
    $isOwner = (string)$acquisto['user_id'] === (string)$currentUser['user_id'];
    if (!$isOwner && $roleLevel < 3) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accesso negato']);
        exit();
    }

    if ((string)$acquisto['stato_pagamento'] !== 'confirmed' || empty($acquisto['qr_code'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'QR non disponibile: acquisto non confermato']);
        exit();
    }

    if (!class_exists('TCPDF')) {
        throw new RuntimeException('TCPDF non disponibile. Eseguire composer install.');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Nuoto Libero');
    $pdf->SetAuthor('Gli Squaletti');
    $pdf->SetTitle('QR Pacchetto - ' . (string)$acquisto['pacchetto_nome']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'Gli Squaletti - QR Pacchetto', 0, 1, 'C');

    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 7, 'Cliente: ' . (string)$acquisto['user_nome'] . ' ' . (string)$acquisto['user_cognome'], 0, 1, 'C');
    $pdf->Cell(0, 7, 'Pacchetto: ' . (string)$acquisto['pacchetto_nome'], 0, 1, 'C');
    $pdf->Cell(0, 7, 'Codice: ' . (string)$acquisto['qr_code'], 0, 1, 'C');
    $pdf->Cell(0, 7, 'Ingressi rimanenti: ' . (int)$acquisto['ingressi_rimanenti'], 0, 1, 'C');

    if (!empty($acquisto['data_scadenza'])) {
        $pdf->Cell(0, 7, 'Scadenza: ' . (string)$acquisto['data_scadenza'], 0, 1, 'C');
    }

    $style = [
        'border' => 1,
        'padding' => 2,
        'fgcolor' => [0, 0, 0],
        'bgcolor' => [255, 255, 255],
    ];

    $pdf->write2DBarcode((string)$acquisto['qr_code'], 'QRCODE,H', 65, 90, 80, 80, $style, 'N');

    $pdf->SetY(178);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(
        0,
        6,
        'Presenta questo QR all\'ingresso. Il bagnino potra verificare il pacchetto e registrare il check-in.',
        0,
        'C'
    );

    $safeCode = preg_replace('/[^A-Z0-9\-]/i', '-', (string)$acquisto['qr_code']) ?: 'QR';

    header_remove('Content-Type');
    header('Content-Type: application/pdf');
    header('Cache-Control: private, max-age=0, must-revalidate');

    $pdf->Output('QR_' . $safeCode . '.pdf', 'D');
    exit();
} catch (Throwable $e) {
    error_log('QR PDF generation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante la generazione del PDF QR',
    ]);
}


