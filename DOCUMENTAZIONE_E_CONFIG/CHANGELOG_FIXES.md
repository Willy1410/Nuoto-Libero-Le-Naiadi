# CHANGELOG FIXES - Nuoto Libero

Data aggiornamento: 2026-02-16
Branch lavoro: `audit-fix-cleanup`

## FASE 2 - Cleanup e riordino

### Modifiche principali
- Consolidata API attiva in `api/` (spostata dalla vecchia collocazione).
- Rimossi stack non operativi/duplicati (Node legacy e Supabase legacy).
- Rimossi file storici/obsoleti non necessari al sito finale.
- Creata struttura ordinata di supporto:
  - `config/`
  - `db/`
  - `logs/`
  - `DOCUMENTAZIONE_E_CONFIG/`

### File/cartelle coinvolte
- `api/` (allineamento percorso API)
- `piscina-php/*.html` (aggiornamento riferimenti a `/api`)
- rimozione definitiva cartelle legacy non usate

### Motivo
Ridurre conflitti tra versioni e lasciare un solo stack coerente e manutenibile.

---

## FASE 3 - Fix UI mobile/tablet (conservativo)

### Modifiche principali
- Fix menu hamburger scrollabile su mobile/tablet.
- Fix bottone "Area Riservata" con sfondo pienamente esteso al testo.

### File coinvolti
- `css/style.css`
- `js/main.js`

### Motivo
Correzione bug UX richiesti senza alterare palette e layout generale.

---

## FASE 4 - Email reale in locale (PHPMailer)

### Modifiche principali
- Integrato PHPMailer via Composer.
- Aggiunta configurazione locale email con placeholder di test.
- Aggiunto endpoint contatti con validazioni e invio email reale.
- Aggiunto logging mail locale.

### File coinvolti
- `composer.json`
- `composer.lock`
- `vendor/`
- `config/mail.php`
- `api/config.php`
- `api/contact.php`
- `js/main.js`
- `logs/mail.log`

### Motivo
Abilitare test reale invio email su XAMPP in modo robusto e tracciabile.

---

## FASE 5 - Pagamenti test + Bonifico

### Modifiche principali
- Predisposizione configurazioni test Stripe/PayPal (senza chiavi reali).
- Aggiunto flusso bonifico lato UI.
- Aggiunto endpoint notifica bonifico con invio email admin.

### File coinvolti
- `config/payments.php`
- `api/bonifico-notify.php`
- `pacchetti.html`
- `js/payment.js`

### Motivo
Completare il flusso pagamenti locale con opzione bonifico tracciata.

---

## FASE 6 - Database da zero + allineamento codice

### Modifiche principali
- Creato SQL unico bootstrap DB completo.
- Allineata configurazione DB di default a `nuoto_libero`.
- Corrette incoerenze API su ID UUID e query statistiche.

### File coinvolti
- `db/CREATE_DATABASE_FROM_ZERO.sql`
- `db/README_DB.md`
- `api/config.php`
- `api/pacchetti.php`
- `api/documenti.php`
- `api/stats.php`
- `api/auth.php`

### Motivo
Garantire setup ripetibile da zero e coerenza runtime API/DB.

---

## FASE 7-9 - Documentazione finale + setup test locale

### Modifiche principali
- Centralizzata tutta la documentazione finale in `DOCUMENTAZIONE_E_CONFIG/`.
- Creati file richiesti:
  - `REPORT_AUDIT.md`
  - `CHANGELOG_FIXES.md`
  - `ISTRUZIONI_SETUP_E_TEST.md`
  - `TEST_CREDENTIALS_LOCAL.txt`
  - `CONFIG_EMAIL.md`
  - `CONFIG_PAGAMENTI_STRIPE_PAYPAL.md`
  - `DB_SETUP.md`
- Allineato `README.md` alla struttura finale.

### Motivo
Fornire istruzioni operative univoche e pulite per setup/test locale.

---

## Pulizia finale extra (strascichi mock)

### Modifiche principali
- `login.html` collegato all'API PHP reale (`/api/auth.php?action=login`) con redirect alle dashboard operative in `piscina-php/`.
- Rimossi definitivamente file mock root non usati (`dashboard-*`, `qr-*`, `check-entry`, `user-detail`, `js/auth.js`, `js/api-client.js`).
- Rimossa definitivamente la cartella `_archive_OLD_NOT_USED/` dopo verifica tecnica.

### Motivo
Eliminare completamente copie vecchie/backup e lasciare solo il progetto finale operativo.

---

## Aggiornamento operativo - Acquisto, QR e CMS contenuti

### Modifiche principali
- Flusso acquisto in `dashboard-utente` riscritto con scelta metodo pagamento prima dell'invio.
- Gestione stato pagamento migliorata in `api/pacchetti.php`:
  - carta/paypal => conferma immediata con generazione QR
  - bonifico/in struttura => pending con conferma successiva da ufficio/admin
- Nuovo endpoint QR PDF: `api/qr.php` (libreria open-source `tecnickcom/tcpdf`).
- Dashboard bagnino aggiornata con scanner camera (libreria `html5-qrcode`) + fallback manuale.
- Antidoppio check-in lato API con lock transazionale in `api/checkin.php`.
- Nuova area CMS contenuti:
  - API: `api/contenuti.php`
  - mappa campi: `config/cms_map.php`
  - editor: `piscina-php/dashboard-contenuti.html`
  - loader pubblico: `js/cms-loader.js`
- Redirect della vecchia login secondaria `piscina-php/login.html` alla login principale `../login.html`.

### File coinvolti
- `api/pacchetti.php`
- `api/checkin.php`
- `api/qr.php`
- `api/contenuti.php`
- `api/config.php`
- `config/cms_map.php`
- `js/cms-loader.js`
- `composer.json`
- `composer.lock`
- `piscina-php/dashboard-utente.html`
- `piscina-php/dashboard-bagnino.html`
- `piscina-php/dashboard-admin.html`
- `piscina-php/dashboard-ufficio.html`
- `piscina-php/dashboard-contenuti.html`
- `piscina-php/login.html`
- `index.html`, `chi-siamo.html`, `orari-tariffe.html`, `galleria.html`, `moduli.html`, `pacchetti.html`, `contatti.html`, `cookie.html`, `privacy.html`, `termini.html`

### Motivo
Rendere il flusso acquisto/QR realmente utilizzabile in produzione locale: pagamento tracciato, QR stampabile in PDF e check-in bagnino collegato, piu controllo CMS sui contenuti del sito.
