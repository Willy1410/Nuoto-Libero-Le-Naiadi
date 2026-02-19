<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$outputDir = __DIR__ . '/../assets/documenti';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$outputFile = $outputDir . '/informativa-privacy-cladam.pdf';
$today = date('d/m/Y');

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Nuoto Libero Le Naiadi');
$pdf->SetAuthor('CLADAM GROUP S.S.D. a r.l.');
$pdf->SetTitle('Informativa Privacy - CLADAM GROUP S.S.D. a r.l.');
$pdf->SetSubject('Informativa privacy ai sensi del Regolamento UE 2016/679 (GDPR)');
$pdf->SetKeywords('privacy, GDPR, informativa, CLADAM GROUP');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(14, 14, 14);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$pdf->SetFillColor(236, 253, 245);
$pdf->SetDrawColor(14, 116, 144);
$pdf->Rect(14, 14, 182, 24, 'DF');

$pdf->SetXY(17, 19);
$pdf->SetFont('dejavusans', 'B', 13);
$pdf->MultiCell(176, 7, 'INFORMATIVA PRIVACY', 0, 'L');
$pdf->SetFont('dejavusans', '', 10);
$pdf->SetX(17);
$pdf->MultiCell(176, 6, 'ai sensi del Regolamento (UE) 2016/679 - Art. 13 GDPR', 0, 'L');

$pdf->Ln(10);
$pdf->SetFont('dejavusans', '', 10);
$pdf->SetTextColor(91, 33, 182);
$pdf->MultiCell(
    0,
    6,
    'BOZZA OPERATIVA DA VALIDARE E FIRMARE A CURA DEL TITOLARE DEL TRATTAMENTO.',
    0,
    'L'
);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$html = <<<HTML
<style>
  h2 { font-size: 11pt; margin: 8px 0 3px 0; color: #0f172a; }
  p { font-size: 9.7pt; line-height: 1.38; margin: 0 0 4px 0; }
  ul { margin: 2px 0 5px 14px; padding: 0; }
  li { font-size: 9.7pt; margin-bottom: 2px; }
  table { border-collapse: collapse; width: 100%; margin: 4px 0 6px 0; }
  td, th { border: 1px solid #cbd5e1; padding: 5px; font-size: 9.2pt; }
  th { background-color: #f8fafc; font-weight: bold; }
  .note { font-size: 8.7pt; color: #475569; }
</style>

<h2>1. Titolare del trattamento</h2>
<p><strong>CLADAM GROUP S.S.D. a r.l.</strong><br>
Sede legale: Via Federico Fellini, 2 - 65010 Spoltore (PE)<br>
PEC: cladamgroup@pec.it</p>

<h2>2. Tipologie di dati trattati</h2>
<ul>
  <li>Dati identificativi e di contatto (es. nome, cognome, email, telefono).</li>
  <li>Dati anagrafici e amministrativi necessari a iscrizione, gestione pratiche e accessi.</li>
  <li>Dati documentali obbligatori per attivazione e mantenimento del servizio.</li>
  <li>Dati tecnici di utilizzo dei servizi digitali (log, sicurezza applicativa, tracciamenti tecnici essenziali).</li>
</ul>

<h2>3. Finalita del trattamento</h2>
<ul>
  <li>Gestione richieste di contatto, iscrizioni, pratiche amministrative e rapporti con l'utenza.</li>
  <li>Erogazione dei servizi, gestione accessi, presenze e processi organizzativi interni.</li>
  <li>Adempimento di obblighi normativi, fiscali, contabili e di sicurezza.</li>
  <li>Tutela del Titolare in sede stragiudiziale e giudiziaria, prevenzione abusi e frodi.</li>
  <li>Comunicazioni informative/operative e, previo consenso, comunicazioni promozionali.</li>
</ul>

<h2>4. Base giuridica del trattamento (art. 6 GDPR)</h2>
<ul>
  <li>Esecuzione di misure precontrattuali e/o contrattuali richieste dall'interessato.</li>
  <li>Adempimento di obblighi legali a cui e soggetto il Titolare.</li>
  <li>Legittimo interesse del Titolare (es. sicurezza, organizzazione, tutela diritti).</li>
  <li>Consenso dell'interessato nei casi in cui sia espressamente richiesto.</li>
</ul>

<h2>5. Modalita del trattamento</h2>
<p>Il trattamento avviene con strumenti cartacei e informatici, secondo principi di liceita, correttezza, trasparenza, minimizzazione e limitazione della conservazione, adottando misure tecniche e organizzative adeguate a tutela dei dati personali.</p>

<h2>6. Natura del conferimento</h2>
<p>Il conferimento dei dati contrassegnati come obbligatori e necessario per la corretta gestione delle richieste e/o del rapporto di servizio. L'eventuale mancato conferimento puo comportare l'impossibilita di dare seguito alle richieste dell'interessato.</p>

<h2>7. Destinatari e categorie di destinatari</h2>
<p>I dati possono essere trattati da personale autorizzato del Titolare e da soggetti terzi nominati Responsabili del trattamento (es. fornitori IT, hosting, consulenti amministrativi/fiscali), esclusivamente per finalita coerenti con la presente informativa.</p>

<h2>8. Trasferimento dati verso Paesi extra SEE</h2>
<p>Ove necessario, eventuali trasferimenti verso Paesi terzi avvengono nel rispetto degli artt. 44 e ss. GDPR, adottando adeguate garanzie (es. decisioni di adeguatezza, clausole contrattuali standard o altre basi previste dalla normativa).</p>

<h2>9. Periodo di conservazione</h2>
<table>
  <tr>
    <th>Categoria dati</th>
    <th>Tempo di conservazione indicativo</th>
  </tr>
  <tr>
    <td>Dati di contatto e richieste informative</td>
    <td>Fino a 24 mesi dalla chiusura della richiesta, salvo esigenze di tutela legale</td>
  </tr>
  <tr>
    <td>Dati amministrativi/contrattuali</td>
    <td>Per la durata del rapporto e successivamente secondo termini di legge applicabili</td>
  </tr>
  <tr>
    <td>Dati tecnici di sicurezza/log</td>
    <td>Per il periodo strettamente necessario alle finalita di sicurezza e controllo</td>
  </tr>
</table>

<h2>10. Diritti dell'interessato</h2>
<p>L'interessato puo esercitare i diritti di cui agli artt. 15-22 GDPR (accesso, rettifica, cancellazione, limitazione, opposizione, portabilita ove applicabile), nonche revocare il consenso in qualsiasi momento senza pregiudicare la liceita del trattamento basata sul consenso prima della revoca.</p>

<h2>11. Reclamo all'Autorita di controllo</h2>
<p>Resta salvo il diritto di proporre reclamo all'Autorita Garante per la protezione dei dati personali, secondo le modalita previste dalla normativa vigente.</p>

<h2>12. Aggiornamenti dell'informativa</h2>
<p>Il Titolare puo aggiornare la presente informativa in caso di evoluzioni normative od organizzative. La versione aggiornata viene resa disponibile sui canali ufficiali.</p>

<p class="note">Versione documento: bozza operativa pre-firma.<br>Data predisposizione: {$today}</p>
HTML;

$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Ln(4);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->MultiCell(0, 6, 'Presa visione e consenso (spazio firma)', 0, 'L');
$pdf->SetFont('dejavusans', '', 9.7);
$pdf->MultiCell(0, 6, '[ ] Dichiaro di aver letto e compreso la presente informativa privacy.', 0, 'L');
$pdf->MultiCell(0, 6, '[ ] Acconsento al trattamento dei dati personali per le finalita connesse alla gestione della richiesta/servizio.', 0, 'L');
$pdf->MultiCell(0, 6, '[ ] Acconsento a comunicazioni informative/promozionali (facoltativo).', 0, 'L');
$pdf->Ln(6);
$pdf->MultiCell(0, 6, 'Luogo e data: _________________________________', 0, 'L');
$pdf->Ln(8);
$pdf->MultiCell(0, 6, 'Firma interessato/a: _________________________________', 0, 'L');
$pdf->Ln(8);
$pdf->MultiCell(0, 6, 'Firma Titolare/Delegato: _________________________________', 0, 'L');

$pdf->Output($outputFile, 'F');

echo 'PDF generato: ' . $outputFile . PHP_EOL;

