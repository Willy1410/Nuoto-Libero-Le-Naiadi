# REPORT AUDIT - Nuoto Libero

Data audit: 2026-02-16
Branch: `audit-fix-cleanup`
Ambiente target: XAMPP locale (Apache + PHP + MySQL/MariaDB)

## 1) Ambito revisione
Audit completo eseguito su frontend, backend PHP/API, configurazioni, SQL e documentazione.

## 2) Snapshot iniziale (prima della pulizia)
Struttura ad alto livello trovata all'avvio:

- `backend/` (stack Node/Express legacy)
- `piscina-supabase/` (stack alternativo Supabase)
- `piscina-php/` (stack PHP con API e dashboard)
- root con frontend marketing + file mock login/dashboard
- documentazione storica multipla e ridondante

## 3) Risultati audit per priorita

### Critici (sicurezza/affidabilita)

1. Stack multipli in conflitto
- Dove: root progetto (`backend/`, `piscina-supabase/`, `piscina-php/`, file root mock)
- Problema: presenza di piu implementazioni concorrenti, con endpoint e flussi incoerenti
- Impatto: alto rischio operativo, deploy errato, debug complesso
- Soluzione applicata: rimozione definitiva degli stack non usati e consolidamento su stack PHP attivo
- Stato: RISOLTO

2. Endpoint legacy rotti
- Dove: vecchi file API PHP (`users.php`, `checkins.php`) nella versione storica
- Problema: riferimenti a funzioni/classi non esistenti (`Database::getInstance`, helper mancanti)
- Impatto: errori runtime, percorsi API non affidabili
- Soluzione applicata: eliminazione endpoint legacy e mantenimento solo API coerente in `api/`
- Stato: RISOLTO

3. Configurazione API non hardenizzata
- Dove: `api/config.php` (versione precedente)
- Problema: gestione errori e auth non robusta
- Impatto: leak informazioni, validazione token fragile
- Soluzione applicata: hardening config (error handling, `hash_equals`, parsing header auth robusto, logging)
- Stato: RISOLTO

### Funzionali (flussi principali)

4. Menu hamburger mobile/tablet non scrollabile
- Dove: `css/style.css`, `js/main.js`
- Problema: menu off-canvas non consentiva scroll interno completo
- Impatto: voci basse non raggiungibili (es. Area Riservata)
- Soluzione applicata: `height/max-height`, `overflow-y:auto`, `-webkit-overflow-scrolling:touch`, body lock controllato via classe CSS
- Stato: RISOLTO

5. Bottone Area Riservata con sfondo non allineato al testo
- Dove: `css/style.css` (`.btn-login`)
- Problema: copertura sfondo non uniforme su mobile
- Impatto: difetto UI
- Soluzione applicata: fix conservativo su `display`, `line-height`, `padding`, `width`
- Stato: RISOLTO

6. Form contatti senza invio reale email
- Dove: `js/main.js` (versione precedente)
- Problema: submit simulato lato client
- Impatto: nessuna email reale inviata
- Soluzione applicata: endpoint reale `api/contact.php` + integrazione PHPMailer
- Stato: RISOLTO

7. Flusso bonifico incompleto
- Dove: `pacchetti.html`, `js/payment.js`
- Problema: mancava notifica amministrazione post-bonifico
- Impatto: processo pagamento non tracciabile
- Soluzione applicata: sezione bonifico + endpoint `api/bonifico-notify.php` + invio email admin
- Stato: RISOLTO

8. Login root puntava a backend non piu presente
- Dove: `login.html`, `js/api-client.js` (legacy)
- Problema: login non coerente con API PHP finale
- Impatto: accesso area riservata non affidabile
- Soluzione applicata: `login.html` collegato a `api/auth.php?action=login` e redirect alle dashboard reali in `piscina-php/`; rimozione file mock
- Stato: RISOLTO

### Qualita/manutenibilita

9. Documentazione storica ridondante
- Dove: root e cartelle legacy
- Problema: molti file storici e istruzioni obsolete
- Impatto: confusione setup
- Soluzione applicata: pulizia definitiva e centralizzazione documentazione in `DOCUMENTAZIONE_E_CONFIG/`
- Stato: RISOLTO

10. Incoerenze schema DB/API
- Dove: `api/pacchetti.php`, `api/documenti.php`, `api/stats.php`, `api/auth.php`
- Problema: uso `lastInsertId` con UUID e assunzioni rigide sui ruoli
- Impatto: possibili bug su acquisti/documenti/statistiche
- Soluzione applicata: UUID generati applicativamente, query ruoli robuste, allineamento con schema SQL finale
- Stato: RISOLTO

### Performance base

11. Asset e codice legacy superflui
- Dove: stack storici e pagine mock
- Problema: peso progetto e rumore operativo
- Impatto: bassa maintainability
- Soluzione applicata: rimozione definitiva file non necessari
- Stato: RISOLTO

## 4) Verifiche tecniche eseguite

- Lint PHP (`php -l`) su file API/config principali: OK
- Check sintassi JS (`node --check`) su `js/main.js` e `js/payment.js`: OK
- Smoke test HTTP locale con `php -S`:
  - `index.html`, `login.html`, `contatti.html`, `pacchetti.html`: 200
  - dashboard area riservata (`piscina-php/*.html`): 200
- Test API login locale: risposta 500 in assenza DB importato/servizio MySQL non pronto

## 5) Rischi residui e note operative

1. Email: fino a configurazione SMTP reale di test in `config/mail.php`, gli endpoint mail rispondono con errore gestito (comportamento voluto).
2. DB: prima del test login API e obbligatorio importare `db/CREATE_DATABASE_FROM_ZERO.sql` su MySQL/MariaDB locale.
3. Asset logo/immagini: parte delle risorse punta a URL esterni; non blocca il funzionamento ma dipende da rete esterna.

## 6) Follow-up implementato (acquisto/QR/CMS)

1. Acquisto pacchetto da area utente
- Problema: acquisto senza scelta metodo e sempre pending.
- Fix: modal metodo pagamento in `piscina-php/dashboard-utente.html` + logica aggiornata in `api/pacchetti.php`.
- Impatto: flusso utente completo con stato coerente.

2. QR non generato/usabile
- Problema: QR solo testuale, nessun PDF stampabile.
- Fix: generazione QR su conferma pagamento e nuovo endpoint PDF `api/qr.php` (TCPDF).
- Impatto: QR stampabile e pronto per scansione bagnino.

3. Scanner bagnino non integrato con camera
- Problema: solo input manuale.
- Fix: scanner camera con `html5-qrcode` in `piscina-php/dashboard-bagnino.html` + fallback manuale.
- Impatto: check-in operativo da telefono.

4. CMS contenuti non disponibile
- Problema: testi sito non modificabili dal CMS.
- Fix: API `api/contenuti.php`, mappa `config/cms_map.php`, pagina editor `piscina-php/dashboard-contenuti.html`, loader pubblico `js/cms-loader.js`.
- Impatto: admin/ufficio possono aggiornare testi principali delle sezioni sito senza editing file.
