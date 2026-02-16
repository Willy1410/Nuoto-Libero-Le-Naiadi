# 📘 MANUALE INSTALLAZIONE COMPLETO
# Sistema Gestione Piscina - PHP + MySQL

---

## ✅ COSA DEVI FARE (PASSO-PASSO)

### STEP 1: INSTALLA XAMPP

**Windows:**
1. Scarica XAMPP da: https://www.apachefriends.org/download.html
2. Esegui l'installer `xampp-windows-x64-installer.exe`
3. Installa in `C:\xampp\` (percorso default)
4. Avvia **XAMPP Control Panel**
5. Click **Start** su **Apache** e **MySQL** (devono diventare verdi)

**macOS:**
```bash
brew install --cask xampp
# Oppure scarica da apachefriends.org
```

**Linux:**
```bash
chmod +x xampp-linux-x64-installer.run
sudo ./xampp-linux-x64-installer.run
sudo /opt/lampp/lampp start
```

---

### STEP 2: VERIFICA XAMPP FUNZIONA

Apri browser e vai a: **http://localhost/**

Dovresti vedere la pagina di benvenuto di XAMPP.

Se NON funziona:
- Controlla che Apache e MySQL siano verdi nel Control Panel
- Porta 80 potrebbe essere occupata → Cambia porta Apache a 8080
- Su Windows: disattiva Skype o IIS se attivi

---

### STEP 3: COPIA I FILE DEL PROGETTO

**Estrai tutti i file del progetto nella cartella htdocs di XAMPP:**

**Windows:**
```
Copia la cartella: piscina-php
Incolla in: C:\xampp\htdocs\
```

**macOS:**
```
Copia la cartella: piscina-php
Incolla in: /Applications/XAMPP/htdocs/
```

**Linux:**
```bash
sudo cp -r piscina-php /opt/lampp/htdocs/
sudo chmod -R 755 /opt/lampp/htdocs/piscina-php
```

---

### STEP 4: CREA IL DATABASE

#### **Metodo A: Con phpMyAdmin (CONSIGLIATO)**

1. Apri browser: **http://localhost/phpmyadmin**
2. Username: `root` / Password: *lascia vuoto*
3. Click **"Nuovo"** nella barra laterale sinistra
4. Nome database: `piscina_gestione`
5. Collation: seleziona `utf8mb4_unicode_ci`
6. Click **"Crea"**
7. Seleziona il database appena creato nella sidebar
8. Click tab **"SQL"** in alto
9. **Apri il file `database/schema.sql` con un editor di testo (Notepad++, Sublime, VSCode)**
10. **Copia TUTTO il contenuto del file**
11. **Incolla nel box SQL di phpMyAdmin**
12. Click **"Esegui"** in basso a destra
13. Dovresti vedere: **"Query eseguite con successo"** e 13 tabelle create

#### **Metodo B: Da Linea di Comando**

**Windows (da XAMPP Shell):**
```bash
cd C:\xampp\mysql\bin
mysql -u root -e "CREATE DATABASE piscina_gestione CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root piscina_gestione < C:\xampp\htdocs\piscina-php\database\schema.sql
```

**macOS:**
```bash
/Applications/XAMPP/bin/mysql -u root -e "CREATE DATABASE piscina_gestione CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/Applications/XAMPP/bin/mysql -u root piscina_gestione < /Applications/XAMPP/htdocs/piscina-php/database/schema.sql
```

**Linux:**
```bash
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE piscina_gestione CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/opt/lampp/bin/mysql -u root piscina_gestione < /opt/lampp/htdocs/piscina-php/database/schema.sql
```

---

### STEP 5: VERIFICA DATABASE CREATO

1. Torna su **http://localhost/phpmyadmin**
2. Nella sidebar sinistra dovresti vedere `piscina_gestione`
3. Cliccaci sopra
4. Dovresti vedere **13 tabelle**:
   - `ruoli`
   - `utenti`
   - `sessioni`
   - `tipi_documento`
   - `documenti_utente`
   - `pacchetti`
   - `acquisti`
   - `prenotazioni`
   - `check_ins`
   - `comunicazioni`
   - `contenuti_sito`
   - `gallery`
   - `activity_log`
5. Click su tabella `utenti` → dovresti vedere **7 utenti** già inseriti

---

### STEP 6: CREA CARTELLA UPLOAD

**Windows:**
```cmd
cd C:\xampp\htdocs\piscina-php
mkdir uploads
mkdir uploads\documenti
mkdir uploads\gallery
icacls uploads /grant Everyone:(OI)(CI)F
```

**macOS:**
```bash
cd /Applications/XAMPP/htdocs/piscina-php
mkdir -p uploads/documenti
mkdir -p uploads/gallery
chmod -R 755 uploads
```

**Linux:**
```bash
cd /opt/lampp/htdocs/piscina-php
mkdir -p uploads/documenti
mkdir -p uploads/gallery
chmod -R 777 uploads
```

---

### STEP 7: CONFIGURA PASSWORD MYSQL (SE NECESSARIO)

Se hai impostato una password per MySQL root:

1. Apri il file: `api/config.php`
2. Trova la riga:
```php
define('DB_PASS', '');
```
3. Cambia con la tua password:
```php
define('DB_PASS', 'tua_password_qui');
```
4. Salva il file

---

### STEP 8: TESTA CHE FUNZIONA!

Apri browser e vai a:

**Test 1: API Backend**
```
http://localhost/piscina-php/api/auth.php?action=me
```

**Risposta attesa:**
```json
{"success":false,"message":"Non autenticato"}
```

✅ **Se vedi questo messaggio, il backend funziona!**

**Test 2: Login**
```
http://localhost/piscina-php/login.html
```

Dovresti vedere la pagina di login.

---

### STEP 9: PROVA AD ACCEDERE

Usa queste credenziali di test:

| Ruolo | Email | Password |
|-------|-------|----------|
| **Admin** | admin@piscina.it | password123 |
| **Ufficio** | ufficio@piscina.it | password123 |
| **Bagnino** | bagnino@piscina.it | password123 |
| **Utente Mario** | mario.rossi@email.it | password123 |

**Dopo il login dovresti essere reindirizzato alla dashboard corretta!**

---

## 📁 STRUTTURA FILE FINALE

```
C:\xampp\htdocs\piscina-php\     (o percorso macOS/Linux equivalente)
│
├── api/
│   ├── config.php               ✅ Configurazione database
│   ├── auth.php                 ✅ Login, registrazione, logout
│   ├── users.php                ✅ Gestione utenti
│   ├── documenti.php            ✅ Upload e approvazione documenti
│   ├── pacchetti.php            ✅ Acquisto pacchetti
│   ├── prenotazioni.php         ✅ Prenotazioni turni
│   ├── checkins.php             ✅ Scanner QR e check-in
│   ├── comunicazioni.php        ✅ Sistema comunicazioni
│   ├── cms.php                  ✅ Gestione contenuti
│   └── stats.php                ✅ Statistiche
│
├── database/
│   └── schema.sql               ✅ Script creazione database
│
├── uploads/                     ✅ Cartella file caricati
│   ├── documenti/
│   └── gallery/
│
├── frontend/
│   ├── login.html               ✅ Pagina login
│   ├── index.html               ✅ Homepage
│   ├── utente/
│   │   └── dashboard.html       ✅ Dashboard utente
│   ├── bagnino/
│   │   └── dashboard.html       ✅ Scanner QR
│   ├── ufficio/
│   │   └── dashboard.html       ✅ Gestione ufficio
│   ├── admin/
│   │   └── dashboard.html       ✅ Admin completo
│   ├── css/
│   │   ├── style.css            ✅ Stili generali
│   │   └── dashboard.css        ✅ Stili dashboard
│   └── js/
│       ├── api-client.js        ✅ Chiamate API
│       ├── auth.js              ✅ Gestione sessioni
│       └── utils.js             ✅ Utility functions
│
└── README.md                    ✅ Questo manuale
```

---

## 🧪 CODICI QR TEST

Il database include già questi QR code pronti per test scanner:

| Utente | QR Code | Ingressi Rimasti | Stato |
|--------|---------|------------------|-------|
| Mario Rossi | `PSC-MARIO-001` | 8/10 | ✅ Attivo |
| Laura Bianchi | `PSC-LAURA-002` | 2/10 | ⚠️ In scadenza |
| Giuseppe Verdi | `PSC-GIUSEPPE-003` | 0/10 | ❌ Esaurito |
| Anna Ferrari | `PSC-ANNA-004` | 3/3 | ✅ Attivo (promo) |

**Per testare lo scanner:**
1. Login come bagnino: `bagnino@piscina.it` / `password123`
2. Vai alla sezione Scanner QR
3. Inserisci manualmente un QR code (es: `PSC-MARIO-001`)
4. Click "Verifica" → Dovresti vedere i dettagli e registrare l'ingresso

---

## 🔧 TROUBLESHOOTING

### Errore: "Access denied for user 'root'@'localhost'"

**Soluzione:**
1. Apri phpMyAdmin
2. Tab "Privilegi" o "User accounts"
3. Edit user `root`
4. Imposta una password
5. Aggiorna `api/config.php` con la password

---

### Errore: "Table 'piscina_gestione.xxx' doesn't exist"

**Soluzione:**
- Lo script SQL non è stato eseguito correttamente
- Torna allo **STEP 4** e ripeti la creazione del database
- Assicurati di copiare **TUTTO** il file schema.sql

---

### Errore: "Permission denied" upload file

**Windows:**
```cmd
icacls C:\xampp\htdocs\piscina-php\uploads /grant Everyone:(OI)(CI)F
```

**macOS/Linux:**
```bash
chmod -R 777 /path/to/piscina-php/uploads
```

---

### Apache non si avvia (porta 80 occupata)

**Soluzione Windows:**
1. XAMPP Control Panel → Config (Apache) → httpd.conf
2. Cerca `Listen 80`
3. Cambia in `Listen 8080`
4. Cerca `ServerName localhost:80`
5. Cambia in `ServerName localhost:8080`
6. Salva e riavvia Apache
7. Accedi a: `http://localhost:8080/piscina-php/`

---

### Pagina bianca o errore 500

**Soluzione:**
1. Abilita errori PHP in `api/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```
2. Controlla log Apache:
   - Windows: `C:\xampp\apache\logs\error.log`
   - macOS: `/Applications/XAMPP/logs/error_log`
   - Linux: `/opt/lampp/logs/error_log`

---

## 📊 DATABASE - DETTAGLI TABELLE

### Tabella `utenti` (7 utenti seed):
```sql
SELECT id, email, nome, cognome, ruolo_id FROM utenti;
```

| ID | Email | Nome | Cognome | Ruolo |
|----|-------|------|---------|-------|
| 1 | admin@piscina.it | Amministratore | Sistema | 4 (admin) |
| 2 | ufficio@piscina.it | Maria | Bianchi | 3 (ufficio) |
| 3 | bagnino@piscina.it | Luca | Verdi | 2 (bagnino) |
| 4 | mario.rossi@email.it | Mario | Rossi | 1 (utente) |
| 5 | laura.bianchi@email.it | Laura | Bianchi | 1 (utente) |
| 6 | giuseppe.verdi@email.it | Giuseppe | Verdi | 1 (utente) |
| 7 | anna.ferrari@email.it | Anna | Ferrari | 1 (utente) |

### Tabella `acquisti` (5 acquisti seed):
```sql
SELECT id, user_id, qr_code, ingressi_rimanenti, data_scadenza, stato_pagamento FROM acquisti;
```

---

## 🎯 PROSSIMI PASSI

### 1. Personalizza l'applicazione

Modifica `api/config.php`:
```php
define('APP_NAME', 'Il Nome della Tua Piscina');
define('APP_URL', 'http://tuosito.com');
define('SMTP_FROM', 'info@tuapiscina.it');
```

### 2. Configura Email (Opzionale)

Per inviare email vere, installa PHPMailer:
```bash
cd C:\xampp\htdocs\piscina-php
composer require phpmailer/phpmailer
```

Poi modifica `api/config.php` con le credenziali SMTP.

### 3. Aggiungi il tuo logo

Sostituisci il file: `frontend/assets/logo.png`

### 4. Deploy su server reale

Quando sei pronto per la produzione:
1. Esporta database da phpMyAdmin (Export → SQL)
2. Carica file su server web
3. Importa database
4. Aggiorna `config.php` con credenziali server

---

## 📞 SUPPORTO

**Se qualcosa non funziona:**

1. Controlla che Apache e MySQL siano avviati (verdi in XAMPP)
2. Verifica database creato correttamente in phpMyAdmin
3. Controlla che la cartella uploads esista e abbia permessi
4. Leggi i log di errore di Apache
5. Controlla la console browser (F12) per errori JavaScript

**File da controllare:**
- `C:\xampp\apache\logs\error.log` (Windows)
- `C:\xampp\htdocs\piscina-php\api/config.php` (configurazione)
- Console browser (F12 → Console)

---

## ✅ CHECKLIST INSTALLAZIONE

- [ ] XAMPP installato e avviato (Apache + MySQL verdi)
- [ ] File copiati in `htdocs/piscina-php/`
- [ ] Database `piscina_gestione` creato
- [ ] Script `schema.sql` eseguito (13 tabelle create)
- [ ] Cartella `uploads/` creata con permessi
- [ ] Test API funziona: http://localhost/piscina-php/api/auth.php?action=me
- [ ] Login funziona: http://localhost/piscina-php/login.html
- [ ] Login come admin/bagnino/utente → redirect dashboard OK

**Se hai spuntato tutto → IL SISTEMA FUNZIONA! 🎉**

---

## 🎉 HAI FINITO!

Il sistema è pronto per essere usato.

**Accedi con:**
- Admin: admin@piscina.it / password123
- Bagnino: bagnino@piscina.it / password123
- Utente: mario.rossi@email.it / password123

**E prova tutte le funzionalità!**

---

**Data:** 15 Febbraio 2026  
**Versione:** 1.0 COMPLETO  
**Autore:** Sistema Gestione Piscina Team
