# ISTRUZIONI_SETUP_E_TEST.md

Data: 2026-02-16
Ambiente target: XAMPP locale (Apache + PHP + MySQL/MariaDB)

## 1) Posizionamento progetto
Percorso consigliato:
- `C:\xampp\htdocs\nuoto-libero`

URL base:
- `http://localhost/nuoto-libero/`

Login unico:
- `http://localhost/nuoto-libero/login.html`

## 2) Configurazione log errori (obbligatoria)
Cartella/file:
- `logs/error.log`

La configurazione è già nel bootstrap API (`api/config.php`):
- `error_reporting = E_ALL`
- `display_errors = Off`
- `log_errors = On`
- `error_log = /logs/error.log`

Come leggere il log:
- apri `logs/error.log`

Come svuotarlo in sicurezza (test locali):
1. Ferma eventuali test in corso.
2. Apri PowerShell nella root progetto.
3. Esegui:
   - `Clear-Content .\logs\error.log`

## 3) Creazione DB da zero
File SQL unico:
- `db/CREATE_DATABASE_FROM_ZERO.sql`

Import da phpMyAdmin:
1. Apri `http://localhost/phpmyadmin`.
2. Tab `SQL`.
3. Incolla/esegui tutto il contenuto di `db/CREATE_DATABASE_FROM_ZERO.sql`.

Il file crea:
- database `nuoto_libero`
- tabelle complete (utenti, ruoli, pacchetti, acquisti, check-in, documenti)
- tabelle sicurezza/notifiche (`password_reset_tokens`, `notifiche_email`)
- indici, vincoli, seed e utenti test

## 4) Config DB nel progetto
File:
- `api/config.php`

Default locale:
- `DB_HOST=localhost`
- `DB_USER=root`
- `DB_PASS=` (vuota in XAMPP standard)
- `DB_NAME=nuoto_libero`

Se usi credenziali diverse, modifica variabili ambiente o valori locali.

## 5) Login, dashboard e ruoli
Dopo login, redirect automatico:
- admin -> `piscina-php/dashboard-admin.html`
- ufficio/segreteria -> `piscina-php/dashboard-ufficio.html`
- bagnino -> `piscina-php/dashboard-bagnino.html`
- utente -> `piscina-php/dashboard-utente.html`

Credenziali test:
- vedi `DOCUMENTAZIONE_E_CONFIG/TEST_CREDENTIALS_LOCAL.txt`

## 6) Reset password
Flusso implementato:
1. In `login.html` clicca `Hai dimenticato la password?`
2. Inserisci email account.
3. Ricevi mail con link `reset-password.html?token=...`
4. Imposta nuova password.

Token reset:
- univoco
- scadenza 60 minuti
- one-time use

## 7) Test hamburger mobile + bottone Area Riservata
### Hamburger
1. Apri homepage da mobile/tablet.
2. Apri menu hamburger.
3. Verifica scroll interno completo fino all’ultima voce.

### Bottone Area Riservata
1. Sempre su mobile/tablet, osserva il pulsante `Area Riservata`.
2. Lo sfondo deve coprire tutta la scritta.

## 8) Email SMTP Gmail (locale test)
File:
- `config/mail.php`

Parametri già predisposti:
- host: `smtp.gmail.com`
- porta: `587`
- encryption: `tls`
- password app: `yyvb ckzs zvpi rwdb` (test locale)

Dato ancora necessario:
- imposta `GMAIL_SMTP_USER` (casella Gmail da usare)

Esempio PowerShell prima di avviare test:
- `$env:GMAIL_SMTP_USER='tuacasella@gmail.com'`

Log email:
- `logs/mail.log`

## 9) Pagamenti Stripe/PayPal + bonifico
File:
- `config/payments.php`

Solo placeholder test:
- Stripe (`pk_test`, `sk_test`, `whsec`)
- PayPal sandbox (`client_id`, `client_secret`, `webhook_id`)

Bonifico:
- dati visualizzati da config (intestatario, IBAN, causale)
- notifica inviata via `api/bonifico-notify.php` all’email amministrazione

## 10) QR, scansione telefono e check-in
- Vista QR pubblica/read-only: `qr-view.html?qr=CODICE`
- Scan bagnino con camera: `piscina-php/dashboard-bagnino.html`

Permessi:
- non loggato: sola lettura
- bagnino loggato: check-in abilitato
- admin/ufficio: funzioni gestione complete su dashboard

## 11) Smoke test consigliato
1. Homepage e pagine principali caricabili.
2. Login admin e apertura dashboard admin.
3. Login bagnino e check-in QR.
4. Login utente e visualizzazione QR/storici.
5. Reset password completo via email.
6. Report dashboard con grafico e breakdown pagamenti.
7. Verifica `logs/error.log` e `logs/mail.log`.

## 12) Troubleshooting rapido
- Errore `JSON.parse` su login:
  - verificare che chiami `api/auth.php?action=login` sotto `/nuoto-libero`
- Errore DB:
  - verificare import SQL + MySQL avviato
- Email non inviata:
  - verificare `GMAIL_SMTP_USER`, connessione SMTP e `logs/mail.log`
- Camera non disponibile:
  - usare browser mobile compatibile e HTTPS/localhost; fallback input manuale nel dashboard bagnino