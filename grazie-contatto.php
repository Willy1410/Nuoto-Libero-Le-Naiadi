<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function contactSafe(string $value, int $maxLen = 120): string
{
    $value = trim($value);
    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$name = contactSafe((string)($_GET['name'] ?? 'Utente'), 120);
$subject = contactSafe((string)($_GET['subject'] ?? 'Richiesta informazioni'), 160);
$email = contactSafe((string)($_GET['email'] ?? ''), 255);
if ($email === '') {
    $email = '-';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Messaggio contatto inviato correttamente a Gli Squaletti.">
    <meta name="robots" content="noindex,nofollow">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="Messaggio Inviato - Gli Squaletti">
    <meta property="og:description" content="Il tuo messaggio e stato inviato alla segreteria.">
    <meta property="og:url" content="<?= htmlspecialchars(appBaseUrl() . '/grazie-contatto.php', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Messaggio Inviato - Gli Squaletti</title>
    <link rel="icon" type="image/png" href="https://www.genspark.ai/api/files/s/s3WpPfgP">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(145deg, #0f766e, #0ea5a0);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #0f172a;
        }
        .card {
            width: min(760px, 100%);
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 42px rgba(2, 6, 23, 0.28);
            padding: 28px;
        }
        .status {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: #fff;
            font-size: 34px;
            margin: 0 auto 14px;
        }
        h1 {
            margin: 0;
            text-align: center;
            color: #0f766e;
            font-size: 30px;
        }
        .lead {
            text-align: center;
            margin: 8px 0 20px;
            color: #475569;
        }
        .summary {
            border: 1px solid #ccfbf1;
            border-radius: 12px;
            padding: 14px;
            background: #f0fdfa;
            font-size: 14px;
            line-height: 1.6;
        }
        .summary strong { color: #115e59; }
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
        .btn-secondary { background: #fff; color: #0f766e; border: 2px solid #0f766e; }
        .note {
            margin-top: 14px;
            color: #475569;
            font-size: 13px;
            text-align: center;
        }
    </style>
</head>
<body>
<main class="card" role="main">
    <div class="status">&#9993;</div>
    <h1>Messaggio inviato correttamente</h1>
    <p class="lead">Grazie <?= $name; ?>, la segreteria ha ricevuto la tua richiesta.</p>

    <section class="summary" aria-label="Riepilogo invio contatto">
        <div><strong>Azione eseguita:</strong> invio modulo contatto dal sito.</div>
        <div><strong>Oggetto:</strong> <?= $subject; ?></div>
        <div><strong>Email indicata:</strong> <?= $email; ?></div>
        <div><strong>Esito:</strong> richiesta in lavorazione.</div>
    </section>

    <div class="cta">
        <a class="btn btn-primary" href="index.php">Torna alla home</a>
        <a class="btn btn-secondary" href="contatti.php">Invia un altro messaggio</a>
        <a class="btn btn-secondary" href="pacchetti.php">Vai ai pacchetti</a>
    </div>

    <p class="note">Risposta media: entro 24 ore lavorative.</p>
</main>
</body>
</html>
