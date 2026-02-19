<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
if (appIsLandingMode()) {
    require __DIR__ . '/landing.php';
    exit;
}
?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nuoto libero in piscina coperta e piscina 50 metri all'aperto in estate. Associazione sportiva per nuoto non agonistico a Pescara.">
    <meta name="keywords" content="nuoto libero, piscina all'aperto, piscina 50 metri, associazione sportiva nuoto, nuoto non agonistico, corsie nuoto, piscina pescara">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="/index.php">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="it_IT">
    <meta property="og:site_name" content="Gli Squaletti">
    <meta property="og:title" content="Nuoto Libero in Piscina - Gli Squaletti">
    <meta property="og:description" content="Piscina nuoto libero non agonistico, corsie dedicate, orari flessibili e area riservata utenti.">
    <meta property="og:url" content="/index.php">
    <meta property="og:image" content="https://www.genspark.ai/api/files/s/s3WpPfgP">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Nuoto Libero in Piscina - Gli Squaletti">
    <meta name="twitter:description" content="Associazione sportiva nuoto non agonistico, piscina coperta e 50m all'aperto in estate.">
    <meta name="twitter:image" content="https://www.genspark.ai/api/files/s/s3WpPfgP">

    <title>Nuoto Libero in Piscina - Gli Squaletti</title>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SportsActivityLocation",
      "name": "Gli Squaletti - Nuoto Libero",
      "sport": "Swimming",
      "description": "AttivitÃƒÂ  di nuoto libero non agonistico in piscina coperta e piscina 50 metri all'aperto in estate.",
      "url": "/index.php"
    }
    </script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://www.genspark.ai/api/files/s/s3WpPfgP">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Cookie Banner -->
    <div id="cookieBanner" class="cookie-banner">
        <div class="cookie-content">
            <p><i class="fas fa-cookie-bite"></i> Questo sito utilizza cookie per migliorare l'esperienza utente. Continuando la navigazione accetti il loro utilizzo. <a href="cookie.php">Maggiori informazioni</a></p>
            <button id="acceptCookies" class="btn btn-primary">Accetta</button>
        </div>
    </div>

    <!-- Header con menu -->
    <header class="header" id="header">
        <nav class="navbar">
            <div class="container">
                <div class="nav-wrapper">
                    <!-- Logo -->
                    <a href="index.php" class="logo">
                        <img src="https://www.genspark.ai/api/files/s/s3WpPfgP" alt="Gli Squaletti Logo">
                        <span>Gli Squaletti</span>
                    </a>

                    <!-- Menu Desktop -->
                    <ul class="nav-menu" id="navMenu">
                        <li><a href="index.php" class="active">Home</a></li>
                        <li><a href="chi-siamo.php">Chi Siamo</a></li>
                        <li><a href="orari-tariffe.php">Orari & Tariffe</a></li>
<li><a href="moduli.php">Moduli</a></li>
                        <li><a href="pacchetti.php">Pacchetti</a></li>
                        <li><a href="contatti.php">Contatti</a></li>
                        <li><a href="login.php" class="btn-login"><i class="fas fa-user"></i> Area Riservata</a></li>
                    </ul>

                    <!-- CTA Button -->
                    <a href="pacchetti.php" class="btn btn-primary btn-header">Richiedi Iscrizione</a>

                    <!-- Hamburger Menu (Mobile) -->
                    <button class="hamburger" id="hamburger" aria-label="Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-background">
            <img src="https://www.genspark.ai/api/files/s/qroJIpG9" alt="Piscina Olimpionica Naiadi Pescara" loading="lazy">
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <div class="container">
                <h1 class="hero-title">Nuoto Libero alla Piscina Naiadi di Pescara</h1>
                <p class="hero-subtitle">Piscina olimpionica 50m all'aperto d'estate, piscina coperta d'inverno. Corsie dedicate, ambiente professionale</p>
                <div class="hero-buttons">
                    <a href="pacchetti.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-ticket-alt"></i> Richiedi Iscrizione
                    </a>
                    <a href="orari-tariffe.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-clock"></i> Scopri Orari
                    </a>
                </div>
            </div>
        </div>
        <!-- Scroll indicator -->
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- Vantaggi Section -->
    <section class="advantages">
        <div class="container">
            <h2 class="section-title">PerchÃƒÂ© Scegliere Gli Squaletti</h2>
            <p class="section-subtitle">I vantaggi del nuoto libero nella nostra struttura</p>
            
            <div class="advantages-grid">
                <div class="advantage-card">
                    <div class="advantage-icon">
                        <i class="fas fa-swimming-pool"></i>
                    </div>
                    <h3>Corsie Dedicate</h3>
                    <p>Corsie riservate al nuoto libero con divisione per velocitÃƒÂ . Massimo comfort durante l'allenamento.</p>
                </div>

                <div class="advantage-card">
                    <div class="advantage-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h3>Spogliatoi Moderni</h3>
                    <p>Ampi spogliatoi con armadietti, docce calde e asciugacapelli. Tutto il comfort che meriti.</p>
                </div>

                <div class="advantage-card">
                    <div class="advantage-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Ambiente Sicuro</h3>
                    <p>Struttura pulita e costantemente controllata. Acqua trattata secondo le normative piÃƒÂ¹ rigide.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Come Funziona Section -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">Come Funziona</h2>
            <p class="section-subtitle">In 3 semplici passi sei pronto a tuffarti</p>

            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Scegli il Pacchetto</h3>
                    <p>Seleziona il pacchetto piÃƒÂ¹ adatto alle tue esigenze: singolo ingresso o abbonamenti multipli</p>
                </div>

                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Invia Richiesta</h3>
                    <p>Invia la richiesta di adesione e scegli bonifico o contributo in struttura. La segreteria confermer? l'attivazione.</p>
                </div>

                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-swimmer"></i>
                    </div>
                    <h3>Vieni in Piscina</h3>
                    <p>Completa verifica documenti in reception e attiva il tuo pacchetto ingressi.</p>
                </div>
            </div>

            <div class="cta-center">
                <a href="pacchetti.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket"></i> Inizia Ora
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Domande Frequenti</h2>
            <p class="section-subtitle">Tutto quello che devi sapere</p>

            <div class="faq-container">
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Serve il certificato medico?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>SÃƒÂ¬, ÃƒÂ¨ obbligatorio presentare un certificato medico di idoneitÃƒÂ  sportiva non agonistica in corso di validitÃƒÂ . Puoi scaricarlo dalla sezione <a href="moduli.php">Moduli</a>.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Devo prenotare per il nuoto libero?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>No, il nuoto libero non richiede prenotazione. Vieni quando preferisci negli orari indicati. Ti consigliamo comunque di evitare le ore di punta (18:00-20:00).</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Cosa devo portare?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Cuffia (obbligatoria), costume, ciabatte e asciugamano. Se hai occhialini portali pure. Gli armadietti sono disponibili gratuitamente.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Posso regalare un pacchetto?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>S?. La segreteria pu? predisporre pacchetti omaggio o buoni regalo intestati al beneficiario.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Quanto dura la validitÃƒÂ  dei pacchetti?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>I pacchetti da 10 e 20 ingressi hanno validitÃƒÂ  di 6 mesi dalla data di attivazione. Il mensile dura 30 giorni dalla prima entrata.</p>
                    </div>
                </div>
            </div>

            <div class="cta-center">
                <a href="moduli.php" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Scarica i Moduli
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Final Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-box">
                <h2>Pronto a Tuffarti?</h2>
                <p>Richiedi iscrizione e attiva il tuo pacchetto ingressi per il nuoto libero.</p>
                <a href="pacchetti.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-water"></i> Richiedi Iscrizione
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Colonna 1: Info -->
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="https://www.genspark.ai/api/files/s/s3WpPfgP" alt="Gli Squaletti Logo">
                        <h3>Gli Squaletti</h3>
                    </div>
                    <p>La tua piscina per il nuoto libero. Allenati quando vuoi, come vuoi.</p>
                </div>

                <!-- Colonna 2: Contatti -->
                <div class="footer-col">
                    <h4>Contatti</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> <a href="https://maps.google.com/?q=Piscina+Naiadi+Pescara" target="_blank" rel="noopener">Via Federico Fellini, 2 - Spoltore (PE)</a></li>
                        <li><i class="fas fa-phone"></i> <a href="tel:+39123456789">123 456 789</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="mailto:info@glisqualetti.it">info@glisqualetti.it</a></li>
                        <li><i class="fas fa-building"></i> SocietÃƒÂ  Sportiva CLADAM GROUP</li>
                    </ul>
                </div>

                <!-- Colonna 3: Link Rapidi -->
                <div class="footer-col">
                    <h4>Link Rapidi</h4>
                    <ul class="footer-links">
                        <li><a href="chi-siamo.php">Chi Siamo</a></li>
                        <li><a href="orari-tariffe.php">Orari & Tariffe</a></li>
<li><a href="moduli.php">Moduli</a></li>
                        <li><a href="pacchetti.php">Pacchetti</a></li>
                        <li><a href="contatti.php">Contatti</a></li>
                        <li><a href="login.php">Area Riservata</a></li>
                    </ul>
                </div>

                <!-- Colonna 4: Social & Legal -->
                <div class="footer-col">
                    <h4>Seguici</h4>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                    <h4 style="margin-top: 20px;">Informazioni Legali</h4>
                    <ul class="footer-links">
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="cookie.php">Cookie Policy</a></li>
                        <li><a href="termini.php">Termini e Condizioni</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 Gli Squaletti. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/cms-loader.js"></script>
    <script src="js/main.js"></script>
</body>
</html>






