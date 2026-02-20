<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
appEnforceFullSiteAccess();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cookie policy del sito Nuoto libero Le Naiadi per nuoto libero e area riservata utenti.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="/cookie.php">
    <title>Cookie Policy - Nuoto libero Le Naiadi</title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="shortcut icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header" id="header">
        <nav class="navbar">
            <div class="container">
                <div class="nav-wrapper">
                    <a href="index.php" class="logo">
                        <img src="assets/brand/squalo_nuoto_colore.svg" alt="Nuoto libero Le Naiadi Logo">
                        <span>Nuoto libero Le Naiadi</span>
                    </a>
                    <ul class="nav-menu" id="navMenu">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="orari-tariffe.php">Orari & Tariffe</a></li>
                        <li><a href="moduli.php">Moduli</a></li>
                        <li><a href="pacchetti.php">Abbonamenti</a></li>
                        <li><a href="contatti.php">Contatti</a></li>
                    </ul>
                    <a href="pacchetti.php" class="btn btn-primary btn-header">Richiedi Iscrizione</a>
                    <button class="hamburger" id="hamburger"><span></span><span></span><span></span></button>
                </div>
            </div>
        </nav>
    </header>

    <section class="page-hero">
        <div class="container">
            <h1><i class="fas fa-cookie-bite"></i> Cookie Policy</h1>
            <p>Informativa sull'utilizzo dei cookie</p>
        </div>
    </section>

    <section class="legal-content">
        <div class="container">
            <div class="legal-box">
                <p><em>Ultimo aggiornamento: 18/02/2026</em></p>

                <h2>1. Cosa sono i cookie</h2>
                <p>I cookie sono piccoli file di testo memorizzati nel browser per migliorare funzionalit&agrave;, sicurezza e usabilit&agrave; del sito.</p>

                <h2>2. Cookie utilizzati</h2>
                <h3>2.1 Tecnici</h3>
                <ul>
                    <li><strong>cookie_consent</strong>: memorizza la preferenza sul banner cookie.</li>
                    <li><strong>session</strong>: mantiene la sessione attiva nell'area riservata.</li>
                </ul>

                <h3>2.2 Analitici</h3>
                <p>Possono essere utilizzati strumenti analitici aggregati per migliorare contenuti e performance del sito.</p>

                <h2>3. Gestione preferenze</h2>
                <p>Puoi accettare/rifiutare i cookie non essenziali dal banner o dal browser. La disattivazione dei cookie tecnici pu&ograve; ridurre le funzionalit&agrave; disponibili.</p>

                <h2>4. Durata</h2>
                <table class="cookie-table">
                    <thead>
                        <tr>
                            <th>Cookie</th>
                            <th>Tipo</th>
                            <th>Durata</th>
                            <th>Finalit&agrave;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>cookie_consent</td>
                            <td>Tecnico</td>
                            <td>12 mesi</td>
                            <td>Gestione consenso cookie</td>
                        </tr>
                        <tr>
                            <td>session</td>
                            <td>Tecnico</td>
                            <td>Sessione</td>
                            <td>Autenticazione area riservata</td>
                        </tr>
                    </tbody>
                </table>

                <h2>5. Contatti</h2>
                <p>Per chiarimenti su cookie e privacy: <strong>info@nuotoliberolenaiadi.it</strong></p>

                <div class="legal-actions">
                    <a href="index.php" class="btn btn-primary">Torna alla Home</a>
                    <a href="privacy.php" class="btn btn-secondary">Privacy Policy</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 Nuoto libero Le Naiadi. Tutti i diritti riservati. | <a href="privacy.php">Privacy</a> | <a href="cookie.php">Cookie</a> | <a href="termini.php">Termini</a></p>
            </div>
        </div>
    </footer>

    <script src="js/cms-loader.js"></script>
    <script src="js/ui-modal.js"></script>
    <script src="js/main.js"></script>
</body>
</html>

