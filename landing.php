<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sito in aggiornamento. Contattaci per informazioni su nuoto libero e iscrizioni.">
    <meta name="robots" content="noindex,nofollow">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="Nuoto Libero Le Naiadi - Sito in aggiornamento">
    <meta property="og:description" content="Stiamo preparando il nuovo sito. Contattaci per supporto immediato.">
    <meta property="og:url" content="<?= htmlspecialchars(appBaseUrl() . '/landing.php', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="https://public.gensparkspace.com/api/files/s/s3WpPfgP">
    <title>Nuoto Libero le Naiadi - Sito in aggiornamento</title>
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
            width: min(940px, 100%);
            background: var(--c-white);
            border-radius: 22px;
            box-shadow: 0 24px 44px rgba(2, 6, 23, 0.35);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.02fr 0.98fr;
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
        .brand-map {
            margin-top: 18px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 14px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.08);
        }
        .brand-map iframe {
            width: 100%;
            height: 170px;
            border: 0;
            border-radius: 10px;
        }
        .brand-map p {
            margin: 8px 0 0;
            font-size: 12px;
            line-height: 1.45;
            color: rgba(240, 249, 255, 0.92);
        }
        .info-side {
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
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
        .hours-box {
            border: 1px solid #dbeafe;
            border-radius: 12px;
            background: #f8fbff;
            padding: 10px 12px;
        }
        .hours-grid {
            display: grid;
            gap: 8px;
        }
        .hours-box h3 {
            margin: 0 0 6px;
            font-size: 14px;
            color: #0f172a;
        }
        .hours-box p {
            margin: 0 0 4px;
            font-size: 13px;
            color: #334155;
        }
        .hours-note {
            border: 1px solid #bae6fd;
            border-radius: 12px;
            background: #f0f9ff;
            padding: 10px 12px;
        }
        .hours-note p {
            margin: 0 0 4px;
            font-size: 13px;
            color: #334155;
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
        .quick-form {
            margin-top: 8px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            padding: 14px;
            background: #f8fbff;
        }
        .quick-form h3 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #0f172a;
        }
        .quick-form .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .quick-form .field {
            margin-bottom: 8px;
        }
        .quick-form label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            color: #334155;
            font-weight: 600;
        }
        .quick-form input,
        .quick-form select,
        .quick-form textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 9px 10px;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            background: #fff;
        }
        .quick-form input:focus,
        .quick-form select:focus,
        .quick-form textarea:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.15);
        }
        .conditional-field {
            display: none;
        }
        .conditional-field.open {
            display: block;
        }
        .quick-form textarea {
            min-height: 86px;
            resize: vertical;
        }
        .quick-form .privacy {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            font-size: 12px;
            color: #475569;
            margin-bottom: 10px;
        }
        .quick-form .privacy input {
            width: auto;
            margin-top: 2px;
        }
        .quick-form .submit-btn {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            box-shadow: 0 9px 18px rgba(2, 132, 199, 0.24);
        }
        .quick-form .submit-btn:disabled {
            opacity: .75;
            cursor: wait;
        }
        .form-feedback {
            margin-top: 9px;
            font-size: 12px;
            border-radius: 9px;
            padding: 8px 9px;
            display: none;
        }
        .form-feedback.ok {
            display: block;
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .form-feedback.error {
            display: block;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .hp-field {
            position: absolute;
            left: -9999px;
            top: auto;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }
        .mode-hint {
            margin-top: 8px;
            font-size: 12px;
            color: #64748b;
        }
        @media (max-width: 860px) {
            .landing-card { grid-template-columns: 1fr; }
            .brand-side, .info-side { padding: 24px; }
            .brand-map iframe { height: 150px; }
            .info-side h1 { font-size: 24px; }
            .quick-form .row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="landing-card" role="main">
        <section class="brand-side" aria-label="Brand Nuoto libero Le Naiadi">
            <h2 class="brand-title">Nuoto Libero le Naiadi</h2>
            <p class="brand-copy">Stiamo finalizzando gli ultimi dettagli del nuovo sito. A breve saremo operativi.</p>

            <div class="brand-map" aria-label="Mappa sede piscina">
                <iframe
                    src="https://www.google.com/maps?q=Viale+della+Riviera+343,+65123+Pescara+PE&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Mappa Nuoto Libero Le Naiadi"></iframe>
                <p>Viale della Riviera, 343, 65123 Pescara PE</p>
            </div>
        </section>
        <section class="info-side" aria-label="Aggiornamento sito">
            <h1>Sito in aggiornamento</h1>
            <p>La piattaforma completa sara online a breve. Siamo presenti in piscina negli orari sottoindicati. Per altre esigenze o urgenze riceviamo solo su appuntamento.</p>
            <ul class="contact-list">
                <li><strong>Email</strong><a href="mailto:info@nuotoliberolenaiadi.it">info@nuotoliberolenaiadi.it</a></li>
                <li><strong>Telefono</strong><a href="tel:+393311931737">+39 331 1931 737</a></li>
                <li><strong>WhatsApp</strong><a href="https://wa.me/393311931737" target="_blank" rel="noopener">+39 331 1931 737</a></li>
                <li><strong>Sede</strong><span>Viale della Riviera, 343, 65123 Pescara PE</span></li>
            </ul>
            <div class="cta-row">
                <a class="cta-btn cta-primary" href="https://wa.me/393311931737?text=Salve%2C%20desidero%20ricevere%20informazioni%20su%20Nuoto%20Libero%20Le%20Naiadi%20e%20valutare%20un%20appuntamento.%20Resto%20in%20attesa%20di%20un%20vostro%20gentile%20riscontro." target="_blank" rel="noopener">Contattaci ora</a>
                <a class="cta-btn cta-secondary" href="area-riservata.php">Area riservata</a>
            </div>
            <div class="hours-grid" aria-label="Giorni e orari di apertura">
                <div class="hours-box">
                    <h3>Orari presenza in piscina (nuoto libero)</h3>
                    <p><strong>Lunedi, Mercoledi, Venerdi:</strong></p>
                    <p>06:30 - 09:00</p>
                    <p>13:00 - 14:00</p>
                </div>
                <div class="hours-note">
                    <p><strong>Per tutti gli altri orari:</strong> riceviamo solo su appuntamento.</p>
                    <p>Puoi usare "Contattaci ora" su WhatsApp oppure compilare il modulo qui sotto.</p>
                </div>
            </div>
            <form id="landingContactForm" class="quick-form" novalidate>
                <h3>Richiedi informazioni o appuntamento</h3>
                <div class="row">
                    <div class="field">
                        <label for="landingFirstName">Nome *</label>
                        <input id="landingFirstName" name="first_name" type="text" maxlength="80" required>
                    </div>
                    <div class="field">
                        <label for="landingLastName">Cognome *</label>
                        <input id="landingLastName" name="last_name" type="text" maxlength="80" required>
                    </div>
                </div>
                <div class="row">
                    <div class="field">
                        <label for="landingEmail">Email *</label>
                        <input id="landingEmail" name="email" type="email" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label for="landingPhone">Telefono</label>
                        <input id="landingPhone" name="phone" type="tel" maxlength="40">
                    </div>
                </div>
                <div class="row">
                    <div class="field">
                        <label for="landingSubject">Oggetto *</label>
                        <select id="landingSubject" name="subject" required>
                            <option value="">Seleziona...</option>
                            <option value="informazioni-iscrizione">Informazioni iscrizione</option>
                            <option value="orari-corsi">Orari nuoto libero</option>
                            <option value="costi">Costi e pacchetti</option>
                            <option value="problemi-account">Richiesta appuntamento</option>
                            <option value="altro">Altro</option>
                        </select>
                    </div>
                </div>
                <div class="field conditional-field" id="landingOtherFieldWrap">
                    <label for="landingSubjectOther">Dettaglio altro *</label>
                    <input id="landingSubjectOther" name="subject_other" type="text" maxlength="220">
                </div>
                <div class="field">
                    <label for="landingMessage">Messaggio *</label>
                    <textarea id="landingMessage" name="message" maxlength="4000" required placeholder="Scrivi qui la tua richiesta (informazioni, appuntamento, supporto documenti, ecc.)."></textarea>
                </div>
                <input class="hp-field" type="text" name="website" id="landingWebsite" tabindex="-1" autocomplete="off" aria-hidden="true">
                <label class="privacy" for="landingPrivacy">
                    <input id="landingPrivacy" name="privacy" type="checkbox" required>
                    <span>Accetto l'<a href="assets/documenti/informativa-privacy-cladam.pdf" target="_blank" rel="noopener">informativa privacy</a> *</span>
                </label>
                <button id="landingSubmitBtn" class="submit-btn" type="submit">Invia richiesta</button>
                <div id="landingFormFeedback" class="form-feedback" role="status" aria-live="polite"></div>
            </form>
            <div class="mode-hint">Modalita corrente: <strong><?= htmlspecialchars(appSiteMode(), ENT_QUOTES, 'UTF-8'); ?></strong></div>
            <div class="mode-hint">Copyright CLADAM GROUP S.S.D. A R.L.</div>
        </section>
    </main>
    <script>
        (function () {
            const form = document.getElementById('landingContactForm');
            if (!form) return;

            const submitBtn = document.getElementById('landingSubmitBtn');
            const feedback = document.getElementById('landingFormFeedback');
            const subjectSelect = document.getElementById('landingSubject');
            const otherWrap = document.getElementById('landingOtherFieldWrap');
            const otherInput = document.getElementById('landingSubjectOther');

            function setFeedback(message, kind) {
                if (!feedback) return;
                feedback.className = 'form-feedback ' + (kind === 'ok' ? 'ok' : 'error');
                feedback.textContent = message;
            }

            function clearFeedback() {
                if (!feedback) return;
                feedback.className = 'form-feedback';
                feedback.textContent = '';
            }

            function syncOtherSubjectField() {
                const isOther = !!subjectSelect && subjectSelect.value === 'altro';
                if (otherWrap) {
                    otherWrap.classList.toggle('open', isOther);
                }
                if (otherInput) {
                    otherInput.required = isOther;
                    if (!isOther) {
                        otherInput.value = '';
                    }
                }
            }

            if (subjectSelect) {
                subjectSelect.addEventListener('change', syncOtherSubjectField);
                syncOtherSubjectField();
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearFeedback();

                const firstName = (document.getElementById('landingFirstName')?.value || '').trim();
                const lastName = (document.getElementById('landingLastName')?.value || '').trim();

                const payload = {
                    first_name: firstName,
                    last_name: lastName,
                    name: `${firstName} ${lastName}`.trim(),
                    email: (document.getElementById('landingEmail')?.value || '').trim(),
                    phone: (document.getElementById('landingPhone')?.value || '').trim(),
                    subject: (document.getElementById('landingSubject')?.value || '').trim(),
                    subject_other: (document.getElementById('landingSubjectOther')?.value || '').trim(),
                    message: (document.getElementById('landingMessage')?.value || '').trim(),
                    privacy: !!document.getElementById('landingPrivacy')?.checked,
                    website: (document.getElementById('landingWebsite')?.value || '').trim()
                };

                if (!payload.first_name || !payload.last_name || !payload.email || !payload.subject || !payload.message) {
                    setFeedback('Compila tutti i campi obbligatori.', 'error');
                    return;
                }
                if (payload.subject === 'altro' && !payload.subject_other) {
                    setFeedback('Specifica il dettaglio per il campo "Altro".', 'error');
                    return;
                }
                if (!payload.privacy) {
                    setFeedback('Devi accettare la privacy per inviare la richiesta.', 'error');
                    return;
                }

                const originalText = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Invio in corso...';
                }

                try {
                    const response = await fetch('api/contact.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Invio non riuscito');
                    }

                    const params = new URLSearchParams({
                        name: payload.name,
                        subject: payload.subject === 'altro'
                            ? `Altro - ${payload.subject_other}`
                            : ((subjectSelect && subjectSelect.selectedOptions[0] && subjectSelect.selectedOptions[0].textContent) || payload.subject),
                        email: payload.email
                    });
                    window.location.href = 'grazie-contatto.php?from=landing&' + params.toString();
                } catch (error) {
                    setFeedback((error && error.message) ? error.message : 'Errore invio richiesta. Riprova o usa i contatti diretti.', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText || 'Invia richiesta';
                    }
                }
            });
        })();
    </script>
</body>
</html>



