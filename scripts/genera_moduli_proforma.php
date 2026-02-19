<?php
require __DIR__ . '/../vendor/autoload.php';

$outputDir = __DIR__ . '/../DOCUMENTAZIONE_E_CONFIG/ARCHIVIO_INTERNO_MODULI_PDF';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

file_put_contents($outputDir . '/.htaccess', "Deny from all\n");
file_put_contents($outputDir . '/README.txt', "Cartella interna: PDF pro-forma modulistica. Non pubblicare direttamente sul sito.\n");

function createPdf(string $path, string $title, array $blocks): void
{
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Nuoto libero Le Naiadi');
    $pdf->SetAuthor('Nuoto libero Le Naiadi');
    $pdf->SetTitle($title);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 11);

    $pdf->SetFillColor(236, 253, 245);
    $pdf->SetDrawColor(15, 118, 110);
    $pdf->Rect(15, 15, 180, 20, 'DF');
    $pdf->SetXY(18, 20);
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->Cell(174, 8, $title, 0, 1, 'L', false, '', 0, false, 'T', 'M');

    $pdf->Ln(8);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->MultiCell(0, 6, 'PRO-FORMA: documento predisposto per compilazione manuale o digitale.', 0, 'L');
    $pdf->Ln(1);

    foreach ($blocks as $block) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->MultiCell(0, 7, (string)$block['title'], 0, 'L');
        $pdf->SetFont('dejavusans', '', 10);

        foreach (($block['lines'] ?? []) as $line) {
            $pdf->MultiCell(0, 7, (string)$line, 0, 'L');
        }

        $pdf->Ln(2);
    }

    $pdf->Ln(4);
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->MultiCell(0, 6, 'Data compilazione: ________________________   Firma: ________________________', 0, 'L');

    $pdf->Output($path, 'F');
}

$documents = [
    [
        'filename' => 'modulo-iscrizione-pro-forma.pdf',
        'title' => 'Modulo Iscrizione Nuoto Libero - Pro Forma',
        'blocks' => [
            ['title' => 'Dati Anagrafici', 'lines' => [
                'Nome e Cognome: ________________________________________________',
                'Data di nascita: __________________   Codice fiscale: __________________',
                'Indirizzo: _____________________________________________________',
                'CAP/Città: ____________________________________________________',
                'Telefono: _______________________   Email: _______________________',
            ]],
            ['title' => 'Dichiarazioni', 'lines' => [
                'Dichiaro di voler aderire alle attività di nuoto libero non agonistico.',
                'Dichiaro di aver letto regolamento e informative privacy.',
            ]],
        ],
    ],
    [
        'filename' => 'regolamento-piscina-presa-visione-pro-forma.pdf',
        'title' => 'Presa Visione Regolamento Piscina - Pro Forma',
        'blocks' => [
            ['title' => 'Dati Utente', 'lines' => [
                'Nome e Cognome: ________________________________________________',
                'Codice fiscale: ________________________________________________',
            ]],
            ['title' => 'Conferma Presa Visione', 'lines' => [
                'Confermo di aver letto integralmente il regolamento interno piscina.',
                'Mi impegno al rispetto di norme di sicurezza, igiene e comportamento.',
            ]],
        ],
    ],
    [
        'filename' => 'informativa-privacy-consenso-pro-forma.pdf',
        'title' => 'Informativa Privacy e Consensi - Pro Forma',
        'blocks' => [
            ['title' => 'Dati Interessato', 'lines' => [
                'Nome e Cognome: ________________________________________________',
                'Email: ____________________________ Telefono: __________________',
            ]],
            ['title' => 'Consensi', 'lines' => [
                '[ ] Acconsento al trattamento dati per gestione iscrizione e attività.',
                '[ ] Acconsento a comunicazioni informative e promemoria documentali.',
                '[ ] Acconsento a comunicazioni promozionali (facoltativo).',
            ]],
        ],
    ],
    [
        'filename' => 'certificato-medico-indicazioni-pro-forma.pdf',
        'title' => 'Certificato Medico Non Agonistico - Scheda Indicazioni',
        'blocks' => [
            ['title' => 'Dati Utente', 'lines' => [
                'Nome e Cognome: ________________________________________________',
                'Data di nascita: __________________   Codice fiscale: __________________',
            ]],
            ['title' => 'Checklist Documentale', 'lines' => [
                '[ ] Certificato in corso di validità allegato',
                '[ ] Data rilascio certificato: __________________',
                '[ ] Data scadenza certificato: __________________',
                '[ ] Firma medico e timbro verificati',
            ]],
        ],
    ],
    [
        'filename' => 'liberatoria-minori-pro-forma.pdf',
        'title' => 'Liberatoria Minori - Pro Forma',
        'blocks' => [
            ['title' => 'Dati Minore', 'lines' => [
                'Nome e Cognome minore: _________________________________________',
                'Data di nascita: __________________',
            ]],
            ['title' => 'Dati Genitore/Tutore', 'lines' => [
                'Nome e Cognome: ________________________________________________',
                'Documento identità: ____________________________________________',
                'Telefono di reperibilità: _______________________________________',
            ]],
            ['title' => 'Dichiarazione', 'lines' => [
                'Autorizzo la partecipazione del minore alle attività di nuoto libero',
                'nelle modalità e negli orari previsti dalla struttura.',
            ]],
        ],
    ],
    [
        'filename' => 'listino-pacchetti-ingressi-pro-forma.pdf',
        'title' => 'Listino Pacchetti Ingressi - Pro Forma',
        'blocks' => [
            ['title' => 'Tariffe', 'lines' => [
                'Singolo ingresso: __________________ EUR',
                'Pacchetto 10 ingressi: __________________ EUR',
                'Pacchetto 20 ingressi: __________________ EUR',
                'Mensile: __________________ EUR',
                'Quota associativa/annuale: __________________ EUR',
            ]],
            ['title' => 'Note amministrative', 'lines' => [
                'Validità pacchetti: ____________________________________________',
                'Modalità contributo ammesse: ___________________________________',
                'Riferimenti segreteria: ________________________________________',
            ]],
        ],
    ],
];

foreach ($documents as $doc) {
    createPdf(
        $outputDir . '/' . $doc['filename'],
        $doc['title'],
        $doc['blocks']
    );
}

echo "PDF pro-forma generati in: {$outputDir}\n";

