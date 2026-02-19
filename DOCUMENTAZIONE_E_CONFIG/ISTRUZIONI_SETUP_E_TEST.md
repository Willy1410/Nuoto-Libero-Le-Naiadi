# ISTRUZIONI SETUP E TEST - Nuoto Libero (XAMPP)

Data: 2026-02-16
Ambiente: locale/test only

## 0) Prerequisiti
- XAMPP con Apache e MySQL/MariaDB avviati
- PHP CLI disponibile (incluso in XAMPP)
- Browser desktop + smartphone/tablet per test UI mobile
- Composer (solo se devi reinstallare dipendenze)

## 1) Posizione progetto in htdocs (o VirtualHost)

### Opzione A - htdocs (consigliata)
1. Copia il progetto in:
   - `C:\xampp\htdocs\<NOME_CARTELLA_PROGETTO>`
2. Apri il sito:
   - `http://localhost/<NOME_CARTELLA_PROGETTO>/`

### Opzione B - VirtualHost
1. Punta DocumentRoot alla root del progetto.
2. Riavvia Apache.
3. Verifica che root sito e API rispondano.

## 2) Creazione DB da zero (phpMyAdmin)
1. Apri `http://localhost/phpmyadmin`.
2. Vai su tab `SQL`.
3. Incolla tutto il file:
   - `db/CREATE_DATABASE_FROM_ZERO.sql`
4. Esegui.

Lo script crea:
- database `nuoto_libero`
- tabelle, indici, vincoli
- seed minimi
- utenti test

## 3) Credenziali DB nel progetto
File da controllare:
- `api/config.php`

Default locale gia impostati:
- `DB_HOST=localhost`
- `DB_USER=root`
- `DB_PASS=` (vuoto)
- `DB_NAME=nuoto_libero`

Se in XAMPP usi credenziali diverse, modifica questi valori (o variabili ambiente).

## 4) Avvio e test flussi principali (login, area admin, QR/scansione)

### URL base
- Homepage: `http://localhost/<NOME_CARTELLA_PROGETTO>/`
- Login area riservata: `http://localhost/<NOME_CARTELLA_PROGETTO>/login.php`

### Login e redirect ruoli
1. Apri `login.php`.
2. Usa credenziali test da:
   - `DOCUMENTAZIONE_E_CONFIG/TEST_CREDENTIALS_LOCAL.txt`
3. Verifica redirect:
   - admin -> `piscina-php/dashboard-admin.php`
   - ufficio -> `piscina-php/dashboard-ufficio.php`
   - bagnino -> `piscina-php/dashboard-bagnino.php`
   - utente -> `piscina-php/dashboard-utente.php`

### Test admin
- In `piscina-php/dashboard-admin.php` verifica:
  - statistiche dashboard
  - tab documenti pending
  - tab pratiche pending con bottone conferma pratica
  - report giornaliero
  - accesso a `CMS Contenuti` (pagina `piscina-php/dashboard-contenuti.php`)

### Test ufficio/segreteria
- In `piscina-php/dashboard-ufficio.php` verifica:
  - lista acquisti pending
  - conferma pratica (genera QR e invia mail di conferma)
  - revisione documenti
  - accesso a `CMS Contenuti`

### Test QR / scansione (bagnino)
- In `piscina-php/dashboard-bagnino.php` verifica:
  - avvio camera da telefono/tablet
  - scansione QR automatica (fallback manuale disponibile)
  - registrazione check-in
  - storico check-in giornata

### Test area utente (richiesta + QR PDF)
- In piscina-php/dashboard-utente.php verifica:
  - invio richiesta pacchetto con finalizzazione in struttura
  - stato pratica: pending fino a conferma ufficio/admin
  - visualizzazione QR in dashboard dopo conferma
  - download PDF QR da api/qr.php?action=download&acquisto_id=...

## 5) Test bug UI richiesti

### Bug 1 - Hamburger scrollabile (mobile/tablet)
1. Apri homepage da telefono/tablet.
2. Apri menu hamburger.
3. Verifica che il menu scrolli fino all'ultima voce (Area Riservata inclusa).

### Bug 2 - Bottone Area Riservata
1. Sempre da mobile/tablet, verifica il bottone `Area Riservata` nel menu.
2. Controlla che lo sfondo azzurro copra tutta la scritta.

## 6) Configurazione email SMTP + test invio

### Configurazione
1. Apri `config/mail.php`.
2. Imposta:
   - `enabled => true`
   - host/porta/username/password SMTP test
   - `from_email`, `admin_email`

Dettaglio completo:
- `DOCUMENTAZIONE_E_CONFIG/CONFIG_EMAIL.md`

### Test invio
1. Apri `contatti.php` e invia un messaggio.
2. Verifica risposta positiva UI.
3. Controlla log:
   - `logs/mail.log`

Nota: se SMTP non configurato, gli endpoint rispondono con errore gestito (comportamento previsto).

## 7) Checklist rapida finale
- [ ] Apache e MySQL avviati
- [ ] `db/CREATE_DATABASE_FROM_ZERO.sql` importato senza errori
- [ ] Login con utenti test funzionante
- [ ] Redirect ruolo corretto (admin/ufficio/bagnino/utente)
- [ ] Dashboard admin caricata
- [ ] Test check-in QR lato bagnino
- [ ] Menu hamburger mobile scrollabile
- [ ] Bottone Area Riservata con sfondo corretto
- [ ] SMTP test configurato e invio contatti funzionante
- [ ] Log mail scritto in `logs/mail.log`
- [ ] Nessun flusso pagamento online attivo nel sito
- [ ] Flusso richiesta/finalizzazione in struttura verificato

## Troubleshooting veloce
1. API login da errore 500:
   - verifica MySQL avviato
   - verifica import SQL completato
   - verifica credenziali DB in `api/config.php`
2. Email non parte:
   - verifica `config/mail.php` (`enabled`, host, credenziali)
   - controlla `logs/mail.log`
3. Dashboard vuote:
   - verifica token in localStorage
   - verifica seed utenti presenti nel DB
