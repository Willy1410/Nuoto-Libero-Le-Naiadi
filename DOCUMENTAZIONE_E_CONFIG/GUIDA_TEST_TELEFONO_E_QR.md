# GUIDA_TEST_TELEFONO_E_QR.md

Guida pratica completa per testare QR + fotocamera telefono in locale.

## 1) Crea utente cliente
Opzioni:
- usa un utente seed (`mario.rossi@email.it`) oppure
- crea utente nuovo da dashboard admin (`piscina-php/dashboard-admin.php`, tab Utenti).

## 2) Acquista un pacchetto
Dal profilo utente (`piscina-php/dashboard-utente.php`):
1. sezione `Acquista pacchetto`
2. clic `Acquista`
3. il sistema registra acquisto con codice QR univoco

## 3) Verifica generazione QR
Nella dashboard utente:
- sezione `Il tuo QR`
- vedi canvas QR + codice testuale
- pulsanti:
  - `Apri vista QR`
  - `Stampa QR`

## 4) Download/Stampa QR
Metodo consigliato:
1. apri dashboard utente
2. clic `Stampa QR`
3. si apre pagina di stampa con codice
4. stampa su carta o salva in PDF

## 5) Setup telefono bagnino (stessa rete locale)
1. collega telefono e PC alla stessa Wi-Fi
2. sul PC trova IP locale (PowerShell):
   - `ipconfig`
   - usa IPv4 della scheda attiva (es. `192.168.1.45`)
3. sul telefono apri:
   - `http://192.168.1.45/<NOME_CARTELLA_PROGETTO>/login.php`
4. login come bagnino:
   - `bagnino@piscina.it / password123`
5. apri `piscina-php/dashboard-bagnino.php`
6. la camera prova ad avviarsi automaticamente (se HTTPS disponibile)
7. inquadra il QR stampato: il sistema compila i dati utente in automatico
8. premi solo `Conferma check-in`

Nota importante:
- Se apri il sito dal telefono con URL `http://192.168.x.x/...`, molti browser bloccano la camera live.
- La dashboard ora passa automaticamente al fallback `Scatta foto QR` (gratuito) quando la camera live non e consentita.
- Per avere camera live continua su telefono, usa HTTPS.

Opzioni HTTPS gratuite:
- Cloudflare Tunnel: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/get-started/create-local-tunnel/
- ngrok (piano free): https://ngrok.com/docs/getting-started/
- localhost HTTPS con certificato locale (`mkcert`): https://github.com/FiloSottile/mkcert

## 6) Verifica regole permessi
### A) Non loggato
Apri dal telefono:
- `http://192.168.1.45/<NOME_CARTELLA_PROGETTO>/qr-view.php?qr=CODICE`

Risultato atteso:
- solo dati utente/pacchetto in sola lettura
- nessun check-in write

### B) Loggato bagnino
Stessa pagina `qr-view.php?qr=CODICE` o dashboard bagnino.

Risultato atteso:
- compare pulsante `Conferma check-in`
- check-in scala 1 ingresso

## 7) Verifica storico nel CMS
In dashboard admin:
1. apri utente -> `Dettaglio`
2. controlla:
   - storico check-in aggiornato
   - ingressi rimanenti aggiornati

In dashboard bagnino:
- tabella `Check-in di oggi` aggiornata

## 8) Troubleshooting
### Camera non parte
- verifica permesso fotocamera nel browser
- prova Chrome/Edge mobile
- se usi IP locale (`http://192.168.x.x`), molte versioni mobile richiedono HTTPS per la camera live
- usa fallback `Scatta foto QR` o input manuale QR
- se vuoi live camera continua, usa uno dei tunnel HTTPS gratuiti sopra

### Endpoint non raggiungibile da telefono
- verifica IP locale corretto
- verifica firewall Windows
- verifica Apache attivo su XAMPP

### QR non valido
- controlla che il pacchetto sia `confirmed`
- controlla scadenza/ingressi rimanenti

### Errore API
- controlla `logs/error.log`
- controlla token login nel browser

## 9) Log utili durante test
- errori backend: `logs/error.log`
- invio email: `logs/mail.log`

