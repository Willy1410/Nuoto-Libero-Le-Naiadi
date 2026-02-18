# GUIDA_TEST_TELEFONO_E_QR.md

Guida pratica completa per testare QR + fotocamera telefono in locale.

## 1) Crea utente cliente
Opzioni:
- usa un utente seed (`mario.rossi@email.it`) oppure
- crea utente nuovo da dashboard admin (`piscina-php/dashboard-admin.html`, tab Utenti).

## 2) Acquista un pacchetto
Dal profilo utente (`piscina-php/dashboard-utente.html`):
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
   - `http://192.168.1.45/<NOME_CARTELLA_PROGETTO>/login.html`
4. login come bagnino:
   - `bagnino@piscina.it / password123`
5. apri `piscina-php/dashboard-bagnino.html`
6. clic `Avvia camera`
7. inquadra il QR stampato

## 6) Verifica regole permessi
### A) Non loggato
Apri dal telefono:
- `http://192.168.1.45/<NOME_CARTELLA_PROGETTO>/qr-view.html?qr=CODICE`

Risultato atteso:
- solo dati utente/pacchetto in sola lettura
- nessun check-in write

### B) Loggato bagnino
Stessa pagina `qr-view.html?qr=CODICE` o dashboard bagnino.

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
- se usi IP locale, molte versioni mobile richiedono HTTPS per la camera live
- usa fallback input manuale QR

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
