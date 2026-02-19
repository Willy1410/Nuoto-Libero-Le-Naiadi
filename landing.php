<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gli Squaletti: sito in aggiornamento. Contattaci per informazioni su nuoto libero e iscrizioni.">
    <meta name="robots" content="noindex,nofollow">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="Gli Squaletti - Sito in aggiornamento">
    <meta property="og:description" content="Stiamo preparando il nuovo sito. Contattaci per supporto immediato.">
    <meta property="og:url" content="<?= htmlspecialchars(appBaseUrl() . '/landing.php', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="https://www.genspark.ai/api/files/s/s3WpPfgP">
    <title>Gli Squaletti - Sito in aggiornamento</title>
    <link rel="icon" type="image/png" href="https://www.genspark.ai/api/files/s/s3WpPfgP">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --c-bg-start: #0b6da8;
            --c-bg-end: #00a8e8;
            --c-accent: #f59e0b;
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
        .landing-card {
            width: min(920px, 100%);
            background: var(--c-white);
            border-radius: 22px;
            box-shadow: 0 24px 44px rgba(2, 6, 23, 0.35);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
        }
        .brand-side {
            padding: 34px;
            background: linear-gradient(145deg, rgba(10, 67, 104, 0.96), rgba(7, 122, 170, 0.96));
            color: #e2f5ff;
        }
        .brand-logo {
            width: 74px;
            height: 74px;
            border-radius: 16px;
            background: #ffffff;
            display: grid;
            place-items: center;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .brand-title { margin: 0 0 8px; font-size: 30px; }
        .brand-copy { margin: 0; font-size: 15px; color: rgba(240, 249, 255, 0.92); line-height: 1.6; }
        .brand-badges {
            margin-top: 24px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .brand-badges span {
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.45);
        }
        .info-side {
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 18px;
        }
        .info-side h1 {
            margin: 0;
            font-size: 29px;
            line-height: 1.25;
            color: #0f172a;
        }
        .info-side p {
            margin: 0;
            color: var(--c-muted);
            line-height: 1.55;
        }
        .contact-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 9px;
        }
        .contact-list li {
            display: flex;
            gap: 10px;
            align-items: baseline;
            font-size: 14px;
        }
        .contact-list strong { min-width: 86px; }
        .contact-list a { color: #0369a1; text-decoration: none; font-weight: 600; }
        .cta-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .cta-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 999px;
            font-size: 14px;
            text-decoration: none;
            font-weight: 700;
        }
        .cta-primary {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: #fff;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.25);
        }
        .cta-secondary {
            border: 2px solid #0ea5e9;
            color: #0369a1;
            background: #fff;
        }
        .mode-hint {
            margin-top: 8px;
            font-size: 12px;
            color: #64748b;
        }
        @media (max-width: 860px) {
            .landing-card { grid-template-columns: 1fr; }
            .brand-side, .info-side { padding: 24px; }
            .info-side h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <main class="landing-card" role="main">
        <section class="brand-side" aria-label="Brand Gli Squaletti">
            <div class="brand-logo">
                <img src="https://www.genspark.ai/api/files/s/s3WpPfgP" alt="Logo Gli Squaletti">
            </div>
            <h2 class="brand-title">Gli Squaletti</h2>
            <p class="brand-copy">Stiamo finalizzando gli ultimi dettagli del nuovo sito. Le attivita operative proseguono regolarmente con supporto diretto della segreteria.</p>
            <div class="brand-badges">
                <span>Nuoto libero</span>
                <span>Piscina Naiadi</span>
                <span>Supporto diretto</span>
            </div>
        </section>
        <section class="info-side" aria-label="Aggiornamento sito">
            <h1>Sito in aggiornamento</h1>
            <p>La piattaforma completa sara online a breve. Nel frattempo puoi contattarci subito per iscrizioni, documenti e informazioni operative.</p>
            <ul class="contact-list">
                <li><strong>Email</strong><a href="mailto:info@glisqualetti.it">info@glisqualetti.it</a></li>
                <li><strong>Telefono</strong><a href="tel:+393203009040">+39 320 300 9040</a></li>
                <li><strong>Sede</strong><span>Via Federico Fellini, 2 - Spoltore (PE)</span></li>
            </ul>
            <div class="cta-row">
                <a class="cta-btn cta-primary" href="contatti.php">Contattaci ora</a>
                <a class="cta-btn cta-secondary" href="login.php">Area riservata</a>
            </div>
            <div class="mode-hint">Modalita corrente: <strong><?= htmlspecialchars(appSiteMode(), ENT_QUOTES, 'UTF-8'); ?></strong></div>
        </section>
    </main>
</body>
</html>
