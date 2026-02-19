<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$sourcePath = $root . '/DOCUMENTAZIONE_E_CONFIG/CHECKLIST_INPUT_UTENTE_GO_LIVE.md';
$outputPath = $root . '/DOCUMENTAZIONE_E_CONFIG/CHECKLIST_INPUT_UTENTE_GO_LIVE.pdf';

$autoloadPath = $root . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

if (!class_exists('TCPDF')) {
    $tcpdfPath = $root . '/vendor/tecnickcom/tcpdf/tcpdf.php';
    if (!file_exists($tcpdfPath)) {
        fwrite(STDERR, "Errore: TCPDF non trovato in vendor/tecnickcom/tcpdf/tcpdf.php\n");
        exit(1);
    }
    require_once $tcpdfPath;
}

$body = @file_get_contents($sourcePath);
if ($body === false || trim($body) === '') {
    fwrite(STDERR, "Errore: contenuto checklist non trovato in {$sourcePath}\n");
    exit(1);
}

$body = str_replace(["\r\n", "\r"], "\n", $body);

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Nuoto Libero Le Naiadi');
$pdf->SetAuthor('Codex');
$pdf->SetTitle('Checklist Input Utente Go-Live');
$pdf->SetSubject('Checklist dati richiesti');
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(true);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Nuoto Libero - Checklist Input Richiesti', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Generato il: ' . date('Y-m-d H:i'), 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('courier', '', 8.8);
$pdf->MultiCell(0, 4.5, $body, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);

$pdf->Output($outputPath, 'F');

fwrite(STDOUT, "PDF generato: {$outputPath}\n");
