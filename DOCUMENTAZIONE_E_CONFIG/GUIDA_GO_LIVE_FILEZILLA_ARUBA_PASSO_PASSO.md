# GUIDA GO-LIVE CON FILEZILLA SU ARUBA (PASSO PASSO)

Documento operativo per pubblicare il sito "Nuoto Libero Le Naiadi" su Aruba usando FileZilla.
Livello: base assoluto (nessuna esperienza tecnica richiesta).

Data: 2026-02-20

============================================================
1) OBIETTIVO
============================================================

Portare online il sito copiando i file dal tuo PC al server Aruba in modo sicuro,
senza perdere nulla e con controllo finale HTTPS.

============================================================
2) COSA TI SERVE PRIMA DI INIZIARE
============================================================

Prepara questi dati su un foglio (o blocco note):

- Dominio: www.nuotoliberolenaiadi.it
- Account Aruba (username): formato tipo 123456@aruba.it
- Password account Aruba
- Tipo hosting: Linux oppure Windows+Linux
- Dati database Aruba (gia importato da te, quindi qui ok)

NOTE IMPORTANTI:
- L'account da usare per Area Clienti e FTP e l'account Aruba (tipo 123456@aruba.it).
- Non condividere mai la password in chat/email.

============================================================
3) LINK UFFICIALI DA APRIRE (SALVALI)
============================================================

A) Download FileZilla Client
https://filezilla-project.org/download.php?show_all=1

B) Accesso Area Clienti Hosting Aruba
https://managehosting.aruba.it

C) Pannello di controllo dominio (da Area Clienti)
https://admin.aruba.it

D) Guida ufficiale Aruba configurazione FTP/FTPS
https://guide.hosting.aruba.it/hosting/pubblicazione-sito-web/pubblicazione-sito-e-configurazione-client-ftp.aspx

E) Guida ufficiale Aruba accesso Area Clienti
https://guide.hosting.aruba.it/area-clienti/accesso/accesso-area-clienti-hosting.aspx

F) Guida Aruba File Manager (alternativa a FileZilla)
https://guide.hosting.aruba.it/hosting/servizi-inclusi-creazione-sito-web/operazioni-eseguibili-file-manager.aspx

============================================================
4) PRIMO CONTROLLO: DOVE FARE LOGIN SU ARUBA
============================================================

1. Apri il browser (Chrome/Edge/Firefox).
2. Clicca la barra in alto (dove scrivi i siti).
3. Scrivi: https://managehosting.aruba.it e premi INVIO.
4. Inserisci:
   - Username Aruba (tipo 123456@aruba.it)
   - Password Aruba
5. Entra in Area Clienti.
6. Apri il servizio Hosting del dominio "nuotoliberolenaiadi.it".

Se non ricordi username/password, usa i link di recupero presenti nella pagina login.

============================================================
5) INSTALLAZIONE FILEZILLA (DAL PC)
============================================================

1. Vai su https://filezilla-project.org/download.php?show_all=1
2. Scarica FileZilla Client per Windows.
3. Apri il file scaricato.
4. Clicca sempre "Avanti" (Next) con installazione standard.
5. Alla fine apri FileZilla.

============================================================
6) PARAMETRI FILEZILLA DA INSERIRE (ARUBA)
============================================================

Da guida Aruba ufficiale:
- Host standard: ftp.nomedominio.estensione
- Porta standard: 21
- Protocollo: FTP
- Crittografia: "Usa FTP esplicito su TLS se disponibile"
- User: account Aruba tipo 123456@aruba.it
- Password: password account Aruba

Per il tuo dominio, usa di base:
- Host: ftp.nuotoliberolenaiadi.it

Se hai servizio Windows+Linux:
- Spazio Windows: ftpwin.nomedominio.estensione
- Spazio Linux:  ftplnx.nomedominio.estensione

Modalita FTPS consigliata (piu sicura):
- Host: ftps://ftp.nomedominio.estensione
- Porta: 990
- User/password Aruba

============================================================
7) CONFIGURAZIONE FILEZILLA (PASSO-PASSO)
============================================================

1. Apri FileZilla.
2. In alto clicca "File".
3. Clicca "Gestore siti".
4. Clicca "Nuovo sito".
5. Dai un nome: "Aruba - Nuoto Libero".
6. Inserisci i campi:
   - Host: ftp.nuotoliberolenaiadi.it
   - Porta: 21
   - Protocollo: FTP
   - Crittografia: FTP esplicito su TLS se disponibile
   - Tipo accesso: Normale
   - Utente: (tuo account Aruba, tipo 123456@aruba.it)
   - Password: (tua password Aruba)
7. Clicca "Connetti".
8. Se appare avviso certificato, conferma/accetta.

Se non si collega:
- prova FTPS porta 990
- verifica user/password
- verifica host (ftp, ftplnx o ftpwin)
- controlla se Aruba limita accesso FTP per IP

============================================================
8) COME LEGGERE LA SCHERMATA FILEZILLA
============================================================

- SINISTRA = file del tuo PC (locale)
- DESTRA  = file del server Aruba (remoto)

Tu devi trascinare da SINISTRA verso DESTRA.

============================================================
9) BACKUP SICUREZZA PRIMA DI SOVRASCRIVERE
============================================================

1. Nella parte destra (server), entra nella cartella del dominio.
2. Crea cartella backup con data, es.: backup_2026_02_20
3. Sposta dentro il backup i file vecchi del sito (se presenti).

Se non vuoi spostare, almeno scarica in locale i file correnti del server
(cosi puoi tornare indietro in caso di problemi).

============================================================
10) COSA CARICARE SUL SERVER (PROGETTO)
============================================================

Dal tuo PC, cartella progetto:
C:\xampp\htdocs\Nuoto-Libero-Le-Naiadi

Carica sul server i file/cartelle del sito, in particolare:
- file PHP in root (index.php, login.php, landing.php, ecc.)
- cartelle: api, css, js, assets, piscina-php, config, uploads, logs, db (se utile per gestione), DOCUMENTAZIONE_E_CONFIG (facoltativa)
- .htaccess
- favicon e manifest

ATTENZIONE SU .env:
- in produzione deve contenere valori Aruba corretti.
- APP_BASE_URL deve essere https://www.nuotoliberolenaiadi.it

============================================================
11) DOVE CARICARE ESATTAMENTE I FILE
============================================================

Regola pratica:
- carica nella cartella principale del dominio (web root), NON fuori dalla cartella dominio.

Se ricevi "Mancano i permessi per scrivere nella cartella":
- significa che sei nella cartella sbagliata o in backup.
- torna alla cartella del dominio e riprova.

============================================================
12) CONTROLLI SUBITO DOPO L'UPLOAD
============================================================

Apri nel browser:
- https://www.nuotoliberolenaiadi.it/
- https://www.nuotoliberolenaiadi.it/login.php
- https://www.nuotoliberolenaiadi.it/landing.php

Verifica:
- home apre correttamente
- logo/favicons visibili
- login funziona
- pagine principali rispondono

============================================================
13) CONTROLLO HTTPS (OBBLIGATORIO)
============================================================

Test da browser:
1. apri http://www.nuotoliberolenaiadi.it
2. deve reindirizzare automaticamente a https://www.nuotoliberolenaiadi.it

Stato attuale verificato:
- HTTP -> 301 verso HTTPS
- HTTPS -> 200 OK

============================================================
14) PIANO DI EMERGENZA (SE QUALCOSA NON VA)
============================================================

1. Rimetti i file precedenti dal backup server.
2. Ricontrolla .env produzione.
3. Verifica permessi cartelle (644 file, 755 cartelle).
4. Riprova apertura sito.

============================================================
15) CHECKLIST FINALE DA SPUNTARE
============================================================

[ ] Login Aruba Area Clienti riuscito
[ ] Connessione FileZilla riuscita
[ ] Backup server fatto
[ ] Upload completato senza errori
[ ] .env produzione corretto
[ ] Home in HTTPS funzionante
[ ] Login e dashboard funzionanti
[ ] Form contatti funzionante
[ ] Nessun errore critico visibile

============================================================
16) DATI DA COMPILARE A MANO (PERSONALIZZAZIONE)
============================================================

Compila qui i tuoi valori reali:

- Username Aruba: ______________________________
- Tipo hosting (Linux o Windows+Linux): _________
- Host FTP usato: _______________________________
- Porta usata: __________________________________
- Protocollo usato (FTP/FTPS): __________________
- Cartella remota usata per upload: _____________
- Data e ora go-live completato: ________________

Fine guida.