# REPORT_AUDIT.md

Data audit completo: 2026-02-16
Percorso analizzato: `C:\xampp\htdocs\nuoto-libero`
Branch: `audit-fix-cleanup`

## 1) Esito sintetico
Audit completo eseguito su frontend, backend PHP/API, configurazioni, dashboard, SQL e documentazione.

Problemi principali trovati prima dei fix:
1. Login rotto per path API errato e risposta HTML al posto di JSON.
2. Doppia pagina login (`login.html` + `piscina-php/login.html`) con flussi incoerenti.
3. Mojibake diffuso (testi con caratteri corrotti) in dashboard e pagine statiche.
4. Dashboard admin/segreteria con funzioni placeholder e API incomplete.
5. Mancanza di reset password con token one-time.
6. Scan QR non pienamente conforme (niente camera reale, permessi non separati read-only/write).
7. Log errori progetto non formalizzato su file dedicato.
8. Schema DB incompleto rispetto alle funzioni richieste (reset password, log notifiche email).

## 2) Fix critici applicati

### Sicurezza / autenticazione
- Harden configurazione API in `api/config.php`:
  - `error_reporting(E_ALL)`
  - `display_errors=Off`
  - `log_errors=On`
  - `error_log=/logs/error.log`
  - session cookie hardening
  - risposte errore senza leak SQL al client.
- Implementato reset password completo in `api/auth.php`:
  - `forgot-password`
  - `validate-reset-token`
  - `reset-password`
  - token univoco hashato, scadenza, uso singolo.
- Login con cooldown base anti brute force (tentativi ripetuti).
- Rimossa login duplicata `piscina-php/login.html`.

### Backend funzionale
- Nuovo endpoint admin completo `api/admin.php`:
  - lista utenti con filtri/ricerca
  - dettaglio utente (pacchetti, check-in, pagamenti)
  - crea/modifica utente
  - attiva/disattiva
  - elimina con conferma.
- Rifatti endpoint:
  - `api/pacchetti.php` (pending, conferma, annullo, rinnovo)
  - `api/checkin.php` (verifica QR read-only pubblica, check-in protetto)
  - `api/documenti.php` (upload/review robusti)
  - `api/stats.php` (dashboard, report daily, serie temporale, breakdown pagamenti, export).

### QR e permessi
- QR disponibile con verifica pubblica read-only via `qr-view.html`.
- Check-in consentito solo a ruoli autorizzati (bagnino/staff) tramite API.
- Antidoppio scan/check-in lato backend (finestra temporale + controllo fascia giornaliera).
- Dashboard bagnino aggiornata con camera reale (`getUserMedia`) e scanner automatico (BarcodeDetector se disponibile) + fallback manuale.

### Email
- SMTP Gmail locale configurabile in `config/mail.php` (con app password test fornita).
- Template HTML email coerenti con palette sito.
- Notifiche automatiche backend:
  - quando resta 1 ingresso
  - quando mancano circa 7 giorni alla scadenza.

### UI / encoding
- Fix completo JSON.parse/login path.
- Dashboard admin/segreteria/bagnino/utente riscritte con testo pulito.
- Corretto mojibake nei file del progetto (UTF-8 coerente).
- Aggiornato contatto WhatsApp su sito/footer:
  - numero: `+39 320 300 9040`
  - link: `https://wa.me/393203009040`.

### Database
- Script unico aggiornato `db/CREATE_DATABASE_FROM_ZERO.sql` con:
  - tabelle core
  - `password_reset_tokens`
  - `notifiche_email`
  - indici/vincoli
  - seed utenti/ruoli/acquisti/check-in/documenti.

## 3) Rischi residui (non bloccanti)
1. `BarcodeDetector` non è supportato da tutti i browser mobile: resta fallback input manuale.
2. Per invio Gmail reale serve impostare una casella Gmail valida in `GMAIL_SMTP_USER` (la sola app password non basta).
3. I grafici report sono implementati in SVG custom (no librerie premium), adeguati per test/gestione ma non BI avanzata.

## 4) Verifiche tecniche eseguite
- Lint PHP: OK su tutti i file in `api/` e `config/`.
- Verifica presenza log errori: `logs/error.log` creato.
- Verifica login duplicato rimosso: `piscina-php/login.html` eliminato.
- Verifica endpoint principali: rotte allineate a `../api` nelle dashboard e `api` nel login principale.

## 5) Aggiornamento moduli CMS
- Aggiunto modulo CMS in dashboard `admin` e `ufficio/segreteria` con:
  - upload modulo
  - sostituzione versione
  - eliminazione con conferma
  - lista moduli con metadati
- Aggiunto endpoint pubblico stabile download:
  - `api/moduli-download.php?slug={slug}`
- Hardening sicurezza upload:
  - estensioni consentite: `pdf`, `doc`, `docx`
  - verifica MIME lato server
  - storage dedicato `uploads/moduli/`
  - accesso diretto ai file bloccato da `uploads/moduli/.htaccess`
