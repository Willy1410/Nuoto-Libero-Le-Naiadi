<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Informativa privacy sintetica per la modalita landing Gli Squaletti.">
    <meta name="robots" content="noindex,nofollow">
    <title>Privacy Landing - Gli Squaletti</title>
    <link rel="icon" type="image/png" href="https://public.gensparkspace.com/api/files/s/s3WpPfgP">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --c-bg-start: #0b6da8;
            --c-bg-end: #00a8e8;
            --c-text: #0f172a;
            --c-muted: #475569;
            --c-white: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: var(--c-text);
            background: radial-gradient(circle at 20% 15%, rgba(245,158,11,.22), transparent 35%),
                        linear-gradient(130deg, var(--c-bg-start), var(--c-bg-end));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: min(860px, 100%);
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 24px 44px rgba(2, 6, 23, 0.35);
            padding: 28px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 28px;
            color: #0f172a;
        }
        .lead {
            margin: 0 0 16px;
            color: var(--c-muted);
            line-height: 1.55;
        }
        .box {
            border: 1px solid #dbeafe;
            background: #f8fbff;
            border-radius: 12px;
            padding: 14px;
            line-height: 1.6;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .actions {
            margin-top: 12px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .actions a {
            text-decoration: none;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
        }
        .btn-secondary {
            color: #0369a1;
            background: #fff;
            border: 2px solid #0ea5e9;
        }
    </style>
</head>
<body>
<main class="card" role="main">
    <h1>Informativa privacy (modalita landing)</h1>
    <p class="lead">Questa informativa sintetica riguarda il modulo di contatto rapido presente nella landing page.</p>

    <section class="box">
        <strong>Titolare del trattamento:</strong> CLADAM GROUP S.S.D. a r.l.<br>
        <strong>Sede legale:</strong> Via Federico Fellini, 2 - 65010 Spoltore (PE)<br>
        <strong>PEC:</strong> <a href="mailto:cladamgroup@pec.it">cladamgroup@pec.it</a>
    </section>

    <section class="box">
        <strong>Dati raccolti:</strong> nome, email, telefono (facoltativo), oggetto e messaggio.<br>
        <strong>Finalita:</strong> rispondere alla tua richiesta di informazioni su iscrizioni e attivita.<br>
        <strong>Base giuridica:</strong> consenso espresso con spunta privacy e invio volontario del modulo.
    </section>

    <section class="box">
        <strong>Conservazione:</strong> i dati vengono trattati dal personale autorizzato per il tempo necessario alla gestione della richiesta.<br>
        <strong>Diritti:</strong> puoi chiedere accesso, rettifica o cancellazione scrivendo a <a href="mailto:info@glisqualetti.it">info@glisqualetti.it</a>.
    </section>

    <div class="actions">
        <a class="btn-primary" href="assets/documenti/informativa-privacy-cladam.pdf" target="_blank" rel="noopener">Scarica PDF Privacy</a>
        <a class="btn-primary" href="landing.php">Torna alla landing</a>
        <a class="btn-secondary" href="landing.php#landingContactForm">Torna al form</a>
    </div>
</main>
</body>
</html>
