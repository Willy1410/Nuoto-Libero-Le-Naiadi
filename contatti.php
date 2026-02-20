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
    <meta name="description" content="Contatti nuoto libero Nuoto libero Le Naiadi: telefono, email e supporto per iscrizioni, documenti e pacchetti ingressi piscina.">
    <meta name="keywords" content="contatti piscina, iscrizione nuoto libero, segreteria nuoto, assistenza clienti piscina">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="/contatti.php">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="Contatti - Nuoto libero Le Naiadi Nuoto Libero">
    <meta property="og:description" content="Contatta la segreteria per iscrizione e gestione pacchetti ingressi.">
    <meta property="og:url" content="/contatti.php">
    <meta property="og:image" content="assets/brand/squalo_nuoto_colore.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <title>Contatti Nuoto Libero - Nuoto libero Le Naiadi</title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="shortcut icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Cookie Banner -->
    <div id="cookieBanner" class="cookie-banner">
        <div class="cookie-content">
            <p><i class="fas fa-cookie-bite"></i> Questo sito utilizza cookie per migliorare l'esperienza utente. <a href="cookie.php">Maggiori informazioni</a></p>
            <button id="acceptCookies" class="btn btn-primary">Accetta</button>
        </div>
    </div>

    <!-- Header -->
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
                        <li><a href="chi-siamo.php">Chi Siamo</a></li>
                        <li><a href="orari-tariffe.php">Orari & Tariffe</a></li>
<li><a href="moduli.php">Moduli</a></li>
                        <li><a href="pacchetti.php">Abbonamenti</a></li>
                        <li><a href="contatti.php" class="active">Contatti</a></li>
                        <li><a href="login.php" class="btn-login"><i class="fas fa-user"></i> Area Riservata</a></li>
                    </ul>
                    <a href="pacchetti.php" class="btn btn-primary btn-header">Richiedi Iscrizione</a>
                    <button class="hamburger" id="hamburger" aria-label="Menu">
                        <span></span><span></span><span></span>
                    </button>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <h1><i class="fas fa-envelope"></i> Contatti</h1>
            <p>Siamo a tua disposizione per ogni informazione</p>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="contact-info-section">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Indirizzo</h3>
                    <p>Viale della Riviera, 343<br>65123 Pescara (PE)</p>
                    <a href="https://maps.google.com/?q=Viale+della+Riviera+343,+65123+Pescara+PE" target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fas fa-directions"></i> Ottieni Indicazioni
                    </a>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Telefono</h3>
                    <p><a href="tel:+393311931737">+39 331 1931 737</a></p>
                    <p class="small-text">Lun-Ven: 09:00-20:00<br>Sab-Dom: 09:00-18:00</p>
                    <a href="tel:+393311931737" class="btn btn-secondary btn-sm">
                        <i class="fas fa-phone-alt"></i> Chiama Ora
                    </a>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email</h3>
                    <p><a href="mailto:info@nuotoliberolenaiadi.it">info@nuotoliberolenaiadi.it</a></p>
                    <p class="small-text">Risposta entro 24 ore</p>
                    <a href="mailto:info@nuotoliberolenaiadi.it" class="btn btn-secondary btn-sm">
                        <i class="fas fa-envelope"></i> Scrivi Email
                    </a>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3>WhatsApp</h3>
                    <p><a href="https://wa.me/393311931737">+39 331 1931 737</a></p>
                    <p class="small-text">Risposta rapida durante gli orari di apertura</p>
                    <a href="https://wa.me/393311931737" target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fab fa-whatsapp"></i> Chatta su WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section">
        <div class="container">
            <div class="contact-form-wrapper">
                <div class="form-intro">
                    <h2>Inviaci un Messaggio</h2>
                    <p>Compila il form qui sotto e ti risponderemo il prima possibile. Per richieste urgenti ti consigliamo di chiamarci direttamente.</p>
                </div>

                <form id="contactForm" class="contact-form">
                    <!-- Honeypot anti-spam: deve restare vuoto -->
                    <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactName">Nome e Cognome *</label>
                            <input type="text" id="contactName" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="contactEmail">Email *</label>
                            <input type="email" id="contactEmail" name="email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactPhone">Telefono</label>
                            <input type="tel" id="contactPhone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="contactSubject">Oggetto *</label>
                            <select id="contactSubject" name="subject" required>
                                <option value="">Seleziona...</option>
                                <option value="informazioni">Richiesta Informazioni</option>
                                <option value="pacchetti">Informazioni sui Pacchetti</option>
                                <option value="certificato">Certificato Medico</option>
                                <option value="problemi">Problemi Tecnici</option>
                                <option value="feedback">Feedback e Suggerimenti</option>
                                <option value="altro">Altro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contactMessage">Messaggio *</label>
                        <textarea id="contactMessage" name="message" rows="6" required placeholder="Scrivi qui il tuo messaggio..."></textarea>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="contactPrivacy" required>
                            <span>Accetto l'<a href="assets/documenti/informativa-privacy-cladam.pdf" target="_blank" rel="noopener">Informativa Privacy (PDF)</a> *</span>
                        </label>
                    </div>

                    <div id="formMessage" class="form-message"></div>

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Invia Messaggio
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2 class="section-title">Come Raggiungerci</h2>
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps?q=Viale+della+Riviera+343,+65123+Pescara+PE&output=embed" 
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <div class="map-info">
                <div class="map-info-grid">
                    <div class="map-info-item">
                        <i class="fas fa-car"></i>
                        <h4>In Auto</h4>
                        <p>Raggiungici in auto impostando l'indirizzo della sede. Parcheggi disponibili nelle aree limitrofe.</p>
                    </div>
                    <div class="map-info-item">
                        <i class="fas fa-bus"></i>
                        <h4>Con i Mezzi Pubblici</h4>
                        <p>La zona e servita da fermate autobus urbane. Verifica la linea piu comoda da "Viale della Riviera".</p>
                    </div>
                    <div class="map-info-item">
                        <i class="fas fa-bicycle"></i>
                        <h4>In Bicicletta</h4>
                        <p>Percorso comodo dalla ciclabile del lungomare. Disponibili punti di sosta nelle vicinanze.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Opening Hours Section -->
    <section class="opening-hours-section">
        <div class="container">
            <h2 class="section-title">Orari Reception</h2>
            <div class="hours-grid">
                <div class="hours-card">
                    <h3>Giorni Feriali</h3>
                    <div class="hours-item">
                        <span>Luned&igrave; - Venerd&igrave;</span>
                        <strong>07:00 - 22:00</strong>
                    </div>
                    <p class="hours-note">Reception sempre disponibile durante l'orario di apertura</p>
                </div>
                <div class="hours-card">
                    <h3>Weekend</h3>
                    <div class="hours-item">
                        <span>Sabato</span>
                        <strong>08:00 - 19:00</strong>
                    </div>
                    <div class="hours-item">
                        <span>Domenica</span>
                        <strong>09:00 - 19:00</strong>
                    </div>
                    <p class="hours-note">Orari ridotti nei festivi - controlla sempre il sito</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Domande Frequenti</h2>
            
            <div class="faq-container">
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Posso venire a visitare la struttura prima di iscrivermi?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Certo! Vieni durante gli orari di apertura e chiedi alla reception una visita guidata. &Egrave; completamente gratuita e senza impegno.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>C'&egrave; un numero per emergenze?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Durante gli orari di apertura, chiama il numero principale. Per emergenze mediche, contatta sempre il 118.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Quanto tempo impiegate a rispondere alle email?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Rispondiamo solitamente entro 24 ore nei giorni lavorativi. Per richieste urgenti, ti consigliamo di chiamarci o contattarci su WhatsApp.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-box">
                <h2>Pronto a Tuffarti?</h2>
                <p>Richiedi iscrizione e attiva il tuo pacchetto ingressi con supporto segreteria.</p>
                <a href="pacchetti.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-ticket-alt"></i> Richiedi Iscrizione
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="assets/brand/squalo_nuoto_colore.svg" alt="Nuoto libero Le Naiadi Logo">
                        <h3>Nuoto libero Le Naiadi</h3>
                    </div>
                    <p>La tua piscina per il nuoto libero. Allenati quando vuoi, come vuoi.</p>
                </div>
                <div class="footer-col">
                    <h4>Contatti</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Viale della Riviera, 343, 65123 Pescara PE</li>
                        <li><i class="fas fa-phone"></i> <a href="tel:+393311931737">+39 331 1931 737</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="mailto:info@nuotoliberolenaiadi.it">info@nuotoliberolenaiadi.it</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Link Rapidi</h4>
                    <ul class="footer-links">
                        <li><a href="orari-tariffe.php">Orari & Tariffe</a></li>
<li><a href="moduli.php">Moduli</a></li>
                        <li><a href="pacchetti.php">Abbonamenti</a></li>
                        <li><a href="contatti.php">Contatti</a></li>
                    </ul>
                </div>
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
                <p>&copy; 2026 Nuoto libero Le Naiadi. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>

    <script src="js/cms-loader.js"></script>
    <script src="js/ui-modal.js"></script>
    <script src="js/main.js"></script>
</body>
</html>


