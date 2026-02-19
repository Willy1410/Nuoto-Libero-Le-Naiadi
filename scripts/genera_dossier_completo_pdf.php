<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$sourcePath = $root . '/DOCUMENTAZIONE_E_CONFIG/DOSSIER_COMPLETO_TEST_LINK_PASSWORD_CHECKLIST_MANCANZE.md';
$outputPath = $root . '/DOCUMENTAZIONE_E_CONFIG/DOSSIER_COMPLETO_TEST_LINK_PASSWORD_CHECKLIST_MANCANZE.pdf';

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
    fwrite(STDERR, "Errore: contenuto dossier non trovato in {$sourcePath}\n");
    exit(1);
}

$body = str_replace(["\r\n", "\r"], "\n", $body);

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Nuoto Libero Le Naiadi');
$pdf->SetAuthor('Codex');
$pdf->SetTitle('Dossier Completo Test e Go-Live');
$pdf->SetSubject('Test, credenziali, link, checklist mancanze');
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(true);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 7, 'Nuoto Libero Le Naiadi - Dossier Completo', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Generato il: ' . date('Y-m-d H:i'), 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('courier', '', 8.4);
$pdf->MultiCell(0, 4.3, $body, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);

$pdf->Output($outputPath, 'F');

fwrite(STDOUT, "PDF generato: {$outputPath}\n");

