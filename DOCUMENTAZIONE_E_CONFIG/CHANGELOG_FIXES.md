# CHANGELOG_FIXES.md

Data aggiornamento: 2026-02-16
Branch: `audit-fix-cleanup`

## Blocco 1 - Correzioni critiche login/API
- Fix path API login (`login.html` -> `api/auth.php?action=login`).
- Eliminata login duplicata `piscina-php/login.html`.
- Migliorata gestione errori JSON lato login.

File:
- `login.html`
- `piscina-php/login.html` (rimosso)

## Blocco 2 - Hardening backend e log errori
- Configurato logging errori progetto su `logs/error.log`.
- Harden CORS/session/auth helpers e output errori.
- Ridotto leak di dettagli tecnici in risposta API.

File:
- `api/config.php`
- `logs/error.log`

## Blocco 3 - Autenticazione avanzata
- Aggiunto reset password completo con token one-time e scadenza.
- Aggiunto cooldown login contro tentativi ripetuti.
- Mantenuto hashing password con `password_hash/password_verify`.

File:
- `api/auth.php`
- `reset-password.html`
- `db/CREATE_DATABASE_FROM_ZERO.sql` (tabella `password_reset_tokens`)

## Blocco 4 - CMS admin/segreteria + API gestionali
- Nuovo endpoint `api/admin.php` per gestione utenti.
- Rifatti endpoint pacchetti/check-in/documenti/stats.
- Dashboard admin e ufficio rese operative (no placeholder).

File:
- `api/admin.php` (nuovo)
- `api/pacchetti.php`
- `api/checkin.php`
- `api/documenti.php`
- `api/stats.php`
- `piscina-php/dashboard-admin.html`
- `piscina-php/dashboard-ufficio.html`

## Blocco 5 - QR camera + permessi
- Dashboard bagnino con camera reale (`getUserMedia`).
- Scan automatico con `BarcodeDetector` (quando disponibile) + fallback manuale.
- Pagina pubblica `qr-view.html` per sola lettura se non loggato.
- Check-in write solo ruoli autorizzati.

File:
- `piscina-php/dashboard-bagnino.html`
- `qr-view.html` (nuovo)
- `api/checkin.php`

## Blocco 6 - Dashboard utente
- QR utente renderizzato a canvas.
- Pulsanti apertura vista QR e stampa.
- Storici acquisti/documenti/check-in allineati alle API aggiornate.

File:
- `piscina-php/dashboard-utente.html`

## Blocco 7 - Email SMTP e notifiche automatiche
- SMTP Gmail predisposto in `config/mail.php`.
- Notifiche automatiche:
  - 1 ingresso residuo
  - scadenza ~7 giorni.
- Template HTML email uniformati.

File:
- `config/mail.php`
- `api/config.php`
- `api/checkin.php`
- `api/contact.php`
- `api/bonifico-notify.php`
- `db/CREATE_DATABASE_FROM_ZERO.sql` (tabella `notifiche_email`)

## Blocco 8 - Encoding e contenuti
- Corretto mojibake su pagine principali/dashboard/documentazione.
- Uniformata codifica UTF-8 nei file modificati.

File (principali):
- `index.html`
- `chi-siamo.html`
- `orari-tariffe.html`
- `galleria.html`
- `moduli.html`
- `pacchetti.html`
- `contatti.html`
- `privacy.html`
- `termini.html`
- `cookie.html`
- `ringraziamento.html`
- `assets/regolamento-piscina.html`
- `assets/modulo-iscrizione.html`

## Blocco 9 - WhatsApp e coerenza commerciale
- Aggiornato numero WhatsApp in contatti/footer:
  - `+39 320 300 9040`
  - `https://wa.me/393203009040`

File:
- pagine HTML principali (footer + contatti)

## Blocco 10 - Database e documentazione
- Aggiornato script DB unico da zero con seed coerente.
- Aggiornate istruzioni setup/test e credenziali locali.
- Aggiunta guida dedicata test telefono + QR.

File:
- `db/CREATE_DATABASE_FROM_ZERO.sql`
- `db/README_DB.md`
- `DOCUMENTAZIONE_E_CONFIG/REPORT_AUDIT.md`
- `DOCUMENTAZIONE_E_CONFIG/CHANGELOG_FIXES.md`
- `DOCUMENTAZIONE_E_CONFIG/ISTRUZIONI_SETUP_E_TEST.md`
- `DOCUMENTAZIONE_E_CONFIG/TEST_CREDENTIALS_LOCAL.txt`
- `DOCUMENTAZIONE_E_CONFIG/CONFIG_EMAIL.md`
- `DOCUMENTAZIONE_E_CONFIG/CONFIG_PAGAMENTI_STRIPE_PAYPAL.md`
- `DOCUMENTAZIONE_E_CONFIG/DB_SETUP.md`
- `DOCUMENTAZIONE_E_CONFIG/GUIDA_TEST_TELEFONO_E_QR.md` (nuovo)

## Blocco 11 - Moduli scaricabili CMS (Admin/Ufficio)
- Aggiunta gestione moduli con upload/sostituzione/eliminazione.
- Endpoint pubblico stabile per download ultima versione via slug.
- Hardening upload (MIME + estensione) e storage dedicato con accesso diretto bloccato.

File:
- `api/moduli.php` (nuovo)
- `api/moduli-download.php` (nuovo)
- `uploads/moduli/.htaccess`
- `uploads/moduli/_archive/.gitkeep`
- `piscina-php/dashboard-admin.html`
- `piscina-php/dashboard-ufficio.html`
- `moduli.html`
- `db/CREATE_DATABASE_FROM_ZERO.sql` (tabella `moduli`)
- `db/README_DB.md`
- `DOCUMENTAZIONE_E_CONFIG/ISTRUZIONI_SETUP_E_TEST.md`
