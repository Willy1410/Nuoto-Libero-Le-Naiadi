<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function safeText(string $value, int $maxLen = 120): string
{
    $value = trim($value);
    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function safeAmount(string $value): string
{
    $num = (float)str_replace(',', '.', trim($value));
    return number_format($num, 2, ',', '.');
}

$enrollmentId = safeText((string)($_GET['id'] ?? ''), 50);
if ($enrollmentId === '') {
    $enrollmentId = 'N/D';
}
$packageName = safeText((string)($_GET['package'] ?? 'Pacchetto 10 ingressi'), 120);
$packageFee = safeAmount((string)($_GET['package_fee'] ?? '90'));
$mandatoryFee = safeAmount((string)($_GET['mandatory_fee'] ?? '20'));
$total = safeAmount((string)($_GET['total'] ?? '110'));
$email = safeText((string)($_GET['email'] ?? ''), 255);
if ($email === '') {
    $email = '-';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Richiesta iscrizione inviata correttamente. Riepilogo pratica e prossimi passi.">
    <meta name="robots" content="noindex,nofollow">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="Richiesta Iscrizione Ricevuta - Gli Squaletti">
    <meta property="og:description" content="La tua richiesta e stata registrata. Completa la finalizzazione in struttura.">
    <meta property="og:url" content="<?= htmlspecialchars(appBaseUrl() . '/grazie-iscrizione.php', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Richiesta Iscrizione Ricevuta - Gli Squaletti</title>
    <link rel="icon" type="image/png" href="https://public.gensparkspace.com/api/files/s/s3WpPfgP">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at top right, rgba(14, 165, 233, 0.28), transparent 35%),
                        linear-gradient(145deg, #0b6da8, #00a8e8);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #0f172a;
        }
        .card {
            width: min(820px, 100%);
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 42px rgba(2, 6, 23, 0.35);
            padding: 28px;
        }
        .status {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            font-size: 38px;
            margin: 0 auto 16px;
        }
        h1 {
            margin: 0;
            text-align: center;
            color: #0369a1;
            font-size: 30px;
        }
        .lead {
            text-align: center;
            margin: 8px 0 20px;
            color: #475569;
        }
        .box {
            border: 1px solid #dbe4ef;
            border-radius: 14px;
            padding: 16px;
            background: #f8fbff;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px dashed #cbd5e1;
            font-size: 14px;
        }
        .row:last-child { border-bottom: 0; }
        .steps {
            margin: 16px 0 0;
            padding-left: 18px;
            color: #334155;
            line-height: 1.6;
            font-size: 14px;
        }
        .cta {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn {
            text-decoration: none;
            border-radius: 999px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; }
        .btn-secondary { background: #fff; color: #0369a1; border: 2px solid #0ea5e9; }
        .help {
            margin-top: 16px;
            padding: 12px;
            border-radius: 10px;
            background: #ecfeff;
            color: #0f766e;
            font-size: 13px;
            text-align: center;
        }
    </style>
</head>
<body>
<main class="card" role="main">
    <div class="status">&#10003;</div>
    <h1>Richiesta registrata con successo</h1>
    <p class="lead">La tua richiesta di finalizzazione iscrizione e stata inviata alla segreteria.</p>

    <section class="box" aria-label="Riepilogo pratica">
        <div class="row"><strong>Riferimento pratica</strong><span><?= $enrollmentId; ?></span></div>
        <div class="row"><strong>Pacchetto</strong><span><?= $packageName; ?></span></div>
        <div class="row"><strong>Quota iscrizione EPS + tesseramento</strong><span>EUR <?= $mandatoryFee; ?></span></div>
        <div class="row"><strong>Quota pacchetto</strong><span>EUR <?= $packageFee; ?></span></div>
        <div class="row"><strong>Totale previsto</strong><span><strong>EUR <?= $total; ?></strong></span></div>
        <div class="row"><strong>Email di riferimento</strong><span><?= $email; ?></span></div>
    </section>

    <ol class="steps">
        <li>La segreteria verifica la richiesta e i dati inseriti.</li>
        <li>Riceverai indicazioni operative via email per la finalizzazione in struttura.</li>
        <li>Al completamento verranno attivati credenziali e accesso ai servizi riservati.</li>
    </ol>

    <div class="cta">
        <a class="btn btn-primary" href="index.php">Torna alla home</a>
        <a class="btn btn-secondary" href="login.php">Vai alla dashboard</a>
        <a class="btn btn-secondary" href="contatti.php">Contattaci</a>
    </div>

    <p class="help">Per urgenze: <a href="tel:+393203009040">+39 320 300 9040</a> | <a href="mailto:info@glisqualetti.it">info@glisqualetti.it</a></p>
</main>
</body>
</html>
