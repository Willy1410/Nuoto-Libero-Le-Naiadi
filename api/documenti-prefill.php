<?php
declare(strict_types=1);

/**
 * Generazione PDF precompilato per documenti utente.
 * GET /api/documenti-prefill.php?tipo_documento_id={id}
 */

require_once __DIR__ . '/config.php';

$currentUser = requireAuth();
$tipoDocumentoId = (int)($_GET['tipo_documento_id'] ?? 0);

if ($tipoDocumentoId <= 0) {
    sendPlainError(400, 'Tipo documento non valido');
}

if (!class_exists('TCPDF')) {
    sendPlainError(500, 'TCPDF non disponibile. Eseguire composer install.');
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, nome, descrizione, obbligatorio
         FROM tipi_documento
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([$tipoDocumentoId]);
    $tipo = $stmt->fetch();

    if (!$tipo) {
        sendPlainError(404, 'Tipo documento non trovato');
    }

    if (isMedicalDocumentType((string)$tipo['nome'])) {
        sendPlainError(400, 'Il certificato medico non puo essere precompilato. Carica il documento rilasciato dal medico.');
    }

    $stmt = $pdo->prepare(
        'SELECT id, nome, cognome, email, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale
         FROM profili
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([$currentUser['user_id']]);
    $profilo = $stmt->fetch();

    if (!$profilo) {
        sendPlainError(404, 'Profilo utente non trovato');
    }

    outputPrefilledPdf($profilo, $tipo);
} catch (Throwable $e) {
    error_log('documenti-prefill error: ' . $e->getMessage());
    sendPlainError(500, 'Errore generazione documento precompilato');
}

function sendPlainError(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    header_remove('Content-Type');
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit();
}

function normalizeSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value;
}

function isMedicalDocumentType(string $name): bool
{
    $name = mb_strtolower(trim($name), 'UTF-8');
    return str_contains($name, 'certificato') && str_contains($name, 'medic');
}

function formatDateOrDash(?string $value): string
{
    $raw = trim((string)$value);
    if ($raw === '' || $raw === '0000-00-00') {
        return '-';
    }

    try {
        return (new DateTime($raw))->format('d/m/Y');
    } catch (Throwable $e) {
        return $raw;
    }
}

function outputPrefilledPdf(array $profilo, array $tipo): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Nuoto Libero');
    $pdf->SetAuthor('Gli Squaletti');
    $pdf->SetTitle('Documento Precompilato - ' . (string)$tipo['nome']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 12, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 8, 'Documento precompilato', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 8, (string)$tipo['nome'], 0, 1, 'C');
    $pdf->Ln(1);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(
        0,
        6,
        "I campi qui sotto sono stati precompilati automaticamente con i dati del tuo profilo. Verifica i dati, firma il modulo e consegnalo in struttura oppure caricalo firmato nella tua area riservata.",
        0,
        'L'
    );
    $pdf->Ln(2);

    $rows = [
        ['Nome', (string)$profilo['nome']],
        ['Cognome', (string)$profilo['cognome']],
        ['Email', (string)$profilo['email']],
        ['Telefono', trim((string)($profilo['telefono'] ?? '')) !== '' ? (string)$profilo['telefono'] : '-'],
        ['Data di nascita', formatDateOrDash((string)($profilo['data_nascita'] ?? ''))],
        ['Indirizzo', trim((string)($profilo['indirizzo'] ?? '')) !== '' ? (string)$profilo['indirizzo'] : '-'],
        ['Citta', trim((string)($profilo['citta'] ?? '')) !== '' ? (string)$profilo['citta'] : '-'],
        ['CAP', trim((string)($profilo['cap'] ?? '')) !== '' ? (string)$profilo['cap'] : '-'],
        ['Codice fiscale', trim((string)($profilo['codice_fiscale'] ?? '')) !== '' ? (string)$profilo['codice_fiscale'] : '-'],
        ['Data generazione', date('d/m/Y H:i')],
    ];

    foreach ($rows as $row) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(42, 7, $row[0] . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, $row[1], 0, 1, 'L');
    }

    $pdf->Ln(2);
    $slug = normalizeSlug((string)$tipo['nome']);
    $description = trim((string)($tipo['descrizione'] ?? ''));

    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'Sezione dichiarazioni', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    if (str_contains($slug, 'iscrizione')) {
        $pdf->MultiCell(
            0,
            6,
            "Con il presente modulo richiedo l'iscrizione al servizio di nuoto libero e dichiaro di aver fornito dati corretti e aggiornati.",
            0,
            'L'
        );
        $pdf->Ln(1);
        $pdf->Cell(0, 6, '[ ] Dichiaro di aver letto il regolamento interno', 0, 1, 'L');
        $pdf->Cell(0, 6, '[ ] Autorizzo il trattamento dei dati personali', 0, 1, 'L');
    } elseif (str_contains($slug, 'privacy')) {
        $pdf->MultiCell(
            0,
            6,
            "Confermo di aver letto l'informativa privacy e di aver compreso finalita e modalita del trattamento dei dati personali.",
            0,
            'L'
        );
        $pdf->Ln(1);
        $pdf->Cell(0, 6, '[ ] Presto il consenso al trattamento per finalita amministrative', 0, 1, 'L');
        $pdf->Cell(0, 6, '[ ] Presto il consenso a comunicazioni di servizio', 0, 1, 'L');
    } elseif (str_contains($slug, 'regolamento')) {
        $pdf->MultiCell(
            0,
            6,
            "Dichiaro di aver preso visione del regolamento della piscina e di impegnarmi al rispetto delle norme di sicurezza e comportamento.",
            0,
            'L'
        );
    } else {
        if ($description !== '') {
            $pdf->MultiCell(0, 6, 'Descrizione documento: ' . $description, 0, 'L');
            $pdf->Ln(1);
        }
        $pdf->MultiCell(
            0,
            6,
            'Verifica i dati precompilati e completa eventuali campi mancanti prima della firma.',
            0,
            'L'
        );
    }

    $pdf->Ln(8);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(90, 8, 'Luogo e data: ______________________________', 0, 0, 'L');
    $pdf->Cell(0, 8, 'Firma: ______________________________', 0, 1, 'L');

    $fullName = trim((string)$profilo['nome'] . '_' . (string)$profilo['cognome']);
    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', $fullName) ?: 'utente';
    $safeTipo = preg_replace('/[^A-Za-z0-9_-]/', '-', normalizeSlug((string)$tipo['nome'])) ?: 'documento';

    header_remove('Content-Type');
    header('Content-Type: application/pdf');
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');

    $pdf->Output('prefill_' . $safeTipo . '_' . $safeName . '.pdf', 'D');
    exit();
}
