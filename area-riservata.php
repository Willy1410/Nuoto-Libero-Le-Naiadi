<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!appIsLandingMode()) {
    header('Location: login.php', true, 302);
    exit;
}

$allowedDemo = ['overview', 'admin', 'ufficio', 'bagnino', 'utente'];
$demo = strtolower((string)($_GET['demo'] ?? 'overview'));
if (!in_array($demo, $allowedDemo, true)) {
    $demo = 'overview';
}

$demoLabels = [
    'overview' => 'Panoramica',
    'admin' => 'Demo Admin',
    'ufficio' => 'Demo Ufficio',
    'bagnino' => 'Demo Bagnino',
    'utente' => 'Demo Utente',
];

$demoBlocks = [
    'overview' => [
        'title' => 'Area riservata vetrina',
        'description' => 'Questa area e una demo controllata: mostra i percorsi principali senza esporre il sito completo.',
        'actions' => ['Vai alla demo Admin', 'Vai alla demo Ufficio', 'Vai alla demo Utente'],
    ],
    'admin' => [
        'title' => 'Pannello Admin (clone)',
        'description' => 'Anteprima processi: approvazioni iscrizioni, controllo pratiche e monitoraggio operativo.',
        'actions' => ['Apri pratiche in attesa', 'Visualizza report giornaliero', 'Esporta elenco utenti'],
    ],
    'ufficio' => [
        'title' => 'Pannello Ufficio (clone)',
        'description' => 'Anteprima segreteria: verifica documenti, gestione richieste e comunicazioni agli utenti.',
        'actions' => ['Apri richieste contatto', 'Controlla documenti', 'Invia riepilogo giornaliero'],
    ],
    'bagnino' => [
        'title' => 'Pannello Bagnino (clone)',
        'description' => 'Anteprima ingresso vasca: check-in e consultazione presenze in tempo reale.',
        'actions' => ['Apri scanner demo', 'Controlla accessi odierni', 'Segna ingresso manuale'],
    ],
    'utente' => [
        'title' => 'Pannello Utente (clone)',
        'description' => 'Anteprima utente: stato iscrizione, documenti da completare e richieste assistenza.',
        'actions' => ['Controlla stato iscrizione', 'Aggiorna documenti', 'Richiedi supporto segreteria'],
    ],
];

$currentBlock = $demoBlocks[$demo];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Area riservata demo vetrina Nuoto libero Le Naiadi.">
    <meta name="robots" content="noindex,nofollow">
    <title>Area Riservata Demo - Nuoto libero Le Naiadi</title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="shortcut icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --c-bg-start: #0b6da8;
            --c-bg-end: #00a8e8;
            --c-accent: #f59e0b;
            --c-text: #0f172a;
            --c-muted: #475569;
            --c-white: #ffffff;
            --c-line: #dbeafe;
            --c-panel: #f8fbff;
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
        .layout {
            width: min(980px, 100%);
            background: var(--c-white);
            border-radius: 22px;
            box-shadow: 0 24px 44px rgba(2, 6, 23, 0.35);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1.08fr;
        }
        .left {
            padding: 34px;
            background: linear-gradient(145deg, rgba(10, 67, 104, 0.96), rgba(7, 122, 170, 0.96));
            color: #e2f5ff;
        }
        .logo {
            width: 74px;
            height: 74px;
            border-radius: 16px;
            background: #fff;
            display: grid;
            place-items: center;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .left h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }
        .left p {
            margin: 0;
            line-height: 1.6;
            color: rgba(240, 249, 255, 0.92);
            font-size: 15px;
        }
        .badge-row {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .badge-row span {
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.45);
        }
        .right {
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }
        .headline h2 {
            margin: 0;
            font-size: 26px;
            color: #0f172a;
        }
        .headline p {
            margin: 4px 0 0;
            color: var(--c-muted);
            font-size: 14px;
        }
        .mini-login {
            border: 1px solid var(--c-line);
            border-radius: 14px;
            padding: 12px;
            background: var(--c-panel);
        }
        .mini-login .field {
            margin-bottom: 8px;
        }
        .mini-login label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }
        .mini-login input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 9px 10px;
            font-family: inherit;
            font-size: 13px;
            background: #fff;
        }
        .btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-primary {
            width: 100%;
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            box-shadow: 0 9px 18px rgba(2, 132, 199, 0.24);
        }
        .btn-ghost {
            color: #0369a1;
            background: #fff;
            border: 1px solid #7dd3fc;
        }
        .btn-amber {
            color: #1e293b;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
        }
        .links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .links a {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            border: 2px solid #0ea5e9;
            color: #0369a1;
            background: #fff;
        }
        .switcher {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .switcher a {
            text-decoration: none;
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #bfdbfe;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            background: #fff;
        }
        .switcher a.active {
            background: #e0f2fe;
            border-color: #38bdf8;
            color: #075985;
        }
        .panel {
            border: 1px solid var(--c-line);
            border-radius: 14px;
            padding: 14px;
            background: #fff;
        }
        .panel h3 {
            margin: 0;
            font-size: 17px;
        }
        .panel p {
            margin: 6px 0 12px;
            color: var(--c-muted);
            font-size: 13px;
            line-height: 1.5;
        }
        .actions {
            display: grid;
            gap: 8px;
        }
        .status-box {
            display: none;
            font-size: 12px;
            border-radius: 9px;
            padding: 8px 9px;
        }
        .status-box.ok {
            display: block;
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .status-box.note {
            display: block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .mode-hint {
            font-size: 12px;
            color: #64748b;
        }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .left, .right { padding: 24px; }
            .headline h2 { font-size: 23px; }
            .switcher { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main class="layout" role="main">
    <section class="left" aria-label="Brand Nuoto libero Le Naiadi">
        <div class="logo">
            <img src="assets/brand/squalo_nuoto_colore.svg" alt="Logo Nuoto libero Le Naiadi">
        </div>
        <h1>Area riservata</h1>
        <p>Versione vetrina attiva: i pulsanti restano nel percorso demo e non aprono il sito principale.</p>
        <div class="badge-row">
            <span>Demo controllata</span>
            <span>Nessun accesso live</span>
            <span>Stile landing</span>
        </div>
    </section>

    <section class="right" aria-label="Area riservata demo">
        <header class="headline">
            <h2><?= htmlspecialchars($demoLabels[$demo], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Simulazione interfaccia riservata. Le azioni sono bloccate in modalita vetrina.</p>
        </header>

        <form id="demoLogin" class="mini-login" novalidate>
            <div class="field">
                <label for="demoEmail">Email demo</label>
                <input id="demoEmail" type="email" placeholder="nome@dominio.it" autocomplete="off">
            </div>
            <div class="field">
                <label for="demoPassword">Password demo</label>
                <input id="demoPassword" type="password" placeholder="********" autocomplete="off">
            </div>
            <button class="btn btn-primary" type="submit">Accedi alla demo</button>
        </form>

        <div class="links">
            <a href="landing.php">Torna alla home</a>
            <a href="landing.php#landingContactForm">Richiedi contatto</a>
            <a href="landing-privacy.php">Privacy vetrina</a>
            <a href="login.php?staff_access=1">Accesso staff</a>
        </div>

        <nav class="switcher" aria-label="Selettore pannelli demo">
            <a href="area-riservata.php?demo=overview" class="<?= $demo === 'overview' ? 'active' : ''; ?>">Panoramica</a>
            <a href="area-riservata.php?demo=admin" class="<?= $demo === 'admin' ? 'active' : ''; ?>">Admin</a>
            <a href="area-riservata.php?demo=ufficio" class="<?= $demo === 'ufficio' ? 'active' : ''; ?>">Ufficio</a>
            <a href="area-riservata.php?demo=bagnino" class="<?= $demo === 'bagnino' ? 'active' : ''; ?>">Bagnino</a>
            <a href="area-riservata.php?demo=utente" class="<?= $demo === 'utente' ? 'active' : ''; ?>">Utente</a>
        </nav>

        <section class="panel">
            <h3><?= htmlspecialchars($currentBlock['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?= htmlspecialchars($currentBlock['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="actions">
<?php foreach ($currentBlock['actions'] as $action): ?>
                <button type="button" class="btn btn-ghost demo-action" data-action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?></button>
<?php endforeach; ?>
                <button type="button" class="btn btn-amber demo-action" data-action="Richiedi attivazione account reale">Richiedi attivazione account</button>
            </div>
        </section>

        <div id="demoStatus" class="status-box note">Demo attiva: i pulsanti restano nell'area riservata clone.</div>
        <div class="mode-hint">SITE_MODE corrente: <strong><?= htmlspecialchars(appSiteMode(), ENT_QUOTES, 'UTF-8'); ?></strong></div>
    </section>
</main>

<script>
(function () {
    const statusBox = document.getElementById('demoStatus');
    const loginForm = document.getElementById('demoLogin');
    const actionButtons = document.querySelectorAll('.demo-action');

    function setStatus(message, kind) {
        if (!statusBox) return;
        statusBox.className = 'status-box ' + (kind === 'ok' ? 'ok' : 'note');
        statusBox.textContent = message;
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function (event) {
            event.preventDefault();
            setStatus('Accesso demo eseguito. Per l\'abilitazione reale usa "Richiedi contatto" dalla landing.', 'ok');
        });
    }

    actionButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const action = button.getAttribute('data-action') || 'Azione demo';
            setStatus(action + ': operazione disponibile solo nella piattaforma completa.', 'note');
        });
    });
})();
</script>
</body>
</html>

