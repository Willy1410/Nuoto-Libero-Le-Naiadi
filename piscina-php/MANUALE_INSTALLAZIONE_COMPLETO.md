# 📘 MANUALE INSTALLAZIONE COMPLETO
## Sistema Gestione Piscina - PHP + MySQL + phpMyAdmin

---

## 🎯 INDICE

1. [Requisiti Sistema](#requisiti)
2. [Download e Installazione XAMPP](#installazione-xampp)
3. [Creazione Database](#creazione-database)
4. [Installazione Progetto](#installazione-progetto)
5. [Configurazione](#configurazione)
6. [Test Iniziale](#test-iniziale)
7. [Credenziali di Accesso](#credenziali)
8. [Struttura Progetto](#struttura)
9. [API Endpoints](#api-endpoints)
10. [Troubleshooting](#troubleshooting)

---

## 📋 1. REQUISITI SISTEMA <a name="requisiti"></a>

- **Sistema Operativo**: Windows 7+, macOS 10.13+, o Linux
- **RAM**: Minimo 2GB (consigliato 4GB+)
- **Spazio disco**: 500MB liberi
- **Browser**: Chrome, Firefox, Safari o Edge (versione recente)

---

## 💻 2. DOWNLOAD E INSTALLAZIONE XAMPP <a name="installazione-xampp"></a>

### 2.1 Download XAMPP

1. Vai su: **https://www.apachefriends.org/it/download.html**
2. Scarica la versione per il tuo sistema operativo
3. Scegli la versione con PHP 8.0 o superiore

### 2.2 Installazione su Windows

1. **Esegui** il file `xampp-windows-x64-installer.exe`
2. Se appare Windows Defender, clicca **"Ulteriori informazioni"** → **"Esegui comunque"**
3. Seleziona i componenti da installare:
   - ☑️ Apache
   - ☑️ MySQL
   - ☑️ PHP
   - ☑️ phpMyAdmin
4. Scegli cartella installazione (default: `C:\xampp`)
5. Clicca **"Next"** e attendi il completamento

### 2.3 Installazione su macOS

```bash
# Apri il file DMG scaricato
# Trascina XAMPP nella cartella Applicazioni
# Apri XAMPP Manager
```

### 2.4 Avvio Servizi

1. Apri **XAMPP Control Panel**
2. Clicca **"Start"** su **Apache**
3. Clicca **"Start"** su **MySQL**
4. Verifica che entrambi diventino **VERDI**

**Errori comuni:**
- **Porta 80 occupata**: Cambia porta Apache a 8080
- **Porta 3306 occupata**: Chiudi altri MySQL o Skype

---

## 🗄️ 3. CREAZIONE DATABASE <a name="creazione-database"></a>

### 3.1 Accedi a phpMyAdmin

1. Apri browser
2. Vai su: **http://localhost/phpmyadmin**
3. Username: `root`
4. Password: *(lascia vuoto per XAMPP default)*

### 3.2 Importa Database

#### METODO 1: Import diretto (RACCOMANDATO)

1. In phpMyAdmin, clicca sulla tab **"SQL"** in alto
2. Apri il file `piscina-php/database/IMPORTA_QUESTO_DB.sql` con un editor di testo
3. **COPIA TUTTO** il contenuto del file
4. **INCOLLA** nella textarea di phpMyAdmin
5. Clicca **"Esegui"** in basso a destra
6. Attendi il messaggio: **"Database creato con successo!"**

#### METODO 2: Upload file

1. Clicca sulla tab **"Importa"**
2. Clicca **"Scegli file"**
3. Seleziona `piscina-php/database/IMPORTA_QUESTO_DB.sql`
4. Clicca **"Esegui"**

### 3.3 Verifica Creazione

1. Nel menu laterale sinistro dovresti vedere il database **`piscina_gestione`**
2. Cliccaci sopra
3. Dovresti vedere **13 tabelle**:
   - `activity_log`
   - `acquisti`
   - `check_ins`
   - `comunicazioni`
   - `contenuti_sito`
   - `documenti_utente`
   - `gallery`
   - `pacchetti`
   - `prenotazioni`
   - `profili`
   - `ruoli`
   - `tipi_documento`

### 3.4 Verifica Dati Iniziali

Clicca su **`profili`** → Tab **"Sfoglia"**

Dovresti vedere **7 utenti**:
- 1 Admin
- 1 Ufficio
- 1 Bagnino
- 4 Utenti test

---

## 📁 4. INSTALLAZIONE PROGETTO <a name="installazione-progetto"></a>

### 4.1 Trova la Cartella `htdocs`

**Windows**: `C:\xampp\htdocs`
**macOS**: `/Applications/XAMPP/htdocs`
**Linux**: `/opt/lampp/htdocs`

### 4.2 Copia Progetto

1. **Copia** l'intera cartella `piscina-php/`
2. **Incolla** dentro `htdocs/`
3. Risultato: `C:\xampp\htdocs\piscina-php\`

### 4.3 Crea Cartella Upload

All'interno di `piscina-php/` crea:

```
piscina-php/
├── uploads/
│   ├── documenti/
│   ├── gallery/
│   └── temp/
```

**Su Windows**: Tasto destro → Nuova cartella

### 4.4 Imposta Permessi (macOS/Linux)

```bash
cd /Applications/XAMPP/htdocs/piscina-php
chmod -R 755 uploads/
chmod -R 755 api/
```

---

## ⚙️ 5. CONFIGURAZIONE <a name="configurazione"></a>

### 5.1 Modifica Config Database

1. Apri `piscina-php/api/config.php`
2. Controlla queste righe (di solito già corrette):

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Lascia vuoto per XAMPP
define('DB_NAME', 'piscina_gestione');
```

3. Se hai impostato una password MySQL diversa, modificala

### 5.2 Modifica JWT Secret (IMPORTANTE)

```php
// Cambia questa stringa con una casuale lunga
define('JWT_SECRET', 'TUA_CHIAVE_SEGRETA_CASUALE_MOLTO_LUNGA_12345678');
```

### 5.3 Configurazione Email (Opzionale)

Se vuoi abilitare l'invio email:

1. Registrati su **Brevo** (ex Sendinblue): https://www.brevo.com
2. Ottieni **API Key**
3. Modifica in `config.php`:

```php
define('BREVO_API_KEY', 'xkeysib-TUA_CHIAVE_API');
define('BREVO_FROM_EMAIL', 'noreply@tuodominio.it');
```

---

## ✅ 6. TEST INIZIALE <a name="test-iniziale"></a>

### 6.1 Verifica Connessione Database

Apri browser e vai su:

```
http://localhost/piscina-php/api/auth.php?action=me
```

**Risposta attesa:**

```json
{
  "success": false,
  "message": "Non autenticato"
}
```

✅ Se vedi questo messaggio, **il backend funziona!**

❌ Se vedi errore di connessione database, controlla i parametri in `config.php`

### 6.2 Test Login

Apri:

```
http://localhost/piscina-php/login.html
```

Credenziali:
- **Email**: `admin@piscina.it`
- **Password**: `password123`

Clicca **"Accedi"**

✅ Se vieni reindirizzato alla dashboard, **tutto funziona!**

### 6.3 Test API con Browser

Testa questi URL direttamente:

```
http://localhost/piscina-php/api/pacchetti.php
http://localhost/piscina-php/api/documenti.php?action=types
```

Dovresti vedere dati JSON.

---

## 🔑 7. CREDENZIALI DI ACCESSO <a name="credenziali"></a>

### 7.1 Credenziali Test

Tutti usano la password: **`password123`**

| Ruolo | Email | Dashboard |
|-------|-------|-----------|
| **Admin** | admin@piscina.it | dashboard-admin.html |
| **Ufficio** | ufficio@piscina.it | dashboard-ufficio.html |
| **Bagnino** | bagnino@piscina.it | dashboard-bagnino.html |
| **Utente** | mario.rossi@email.it | dashboard-utente.html |
| **Utente** | laura.bianchi@email.it | dashboard-utente.html |

### 7.2 Cambio Password

Dopo il primo accesso, è **fortemente consigliato** cambiare tutte le password.

---

## 📂 8. STRUTTURA PROGETTO <a name="struttura"></a>

```
piscina-php/
│
├── api/
│   ├── config.php         # Configurazione generale
│   ├── auth.php           # Login, registrazione, JWT
│   ├── pacchetti.php      # Gestione pacchetti
│   ├── checkin.php        # Check-in QR code
│   ├── documenti.php      # Upload documenti
│   └── stats.php          # Statistiche e report
│
├── database/
│   └── IMPORTA_QUESTO_DB.sql  # Schema MySQL completo
│
├── uploads/
│   ├── documenti/         # File utenti
│   ├── gallery/           # Immagini gallery
│   └── temp/              # File temporanei
│
├── css/
│   └── style.css          # Stili globali
│
├── js/
│   ├── api-client.js      # Client API (fetch)
│   ├── auth.js            # Gestione autenticazione
│   └── dashboard.js       # Logica dashboard
│
├── login.html             # Pagina login
├── dashboard-admin.html   # Dashboard amministratore
├── dashboard-ufficio.html # Dashboard ufficio
├── dashboard-bagnino.html # Dashboard bagnino
├── dashboard-utente.html  # Dashboard utente
│
└── README.md              # Questo file
```

---

## 🌐 9. API ENDPOINTS <a name="api-endpoints"></a>

### Base URL:
```
http://localhost/piscina-php/api/
```

### 9.1 Autenticazione

#### POST `/auth.php?action=register`
Registra nuovo utente.

**Body:**
```json
{
  "email": "utente@email.it",
  "password": "password123",
  "nome": "Mario",
  "cognome": "Rossi",
  "telefono": "3331234567",
  "codice_fiscale": "RSSMRA85C15H501A"
}
```

#### POST `/auth.php?action=login`
Login utente.

**Body:**
```json
{
  "email": "admin@piscina.it",
  "password": "password123"
}
```

**Risposta:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "uuid",
    "email": "admin@piscina.it",
    "nome": "Andrea",
    "ruolo": "admin",
    "livello": 4
  }
}
```

#### GET `/auth.php?action=me`
Dati utente corrente.

**Headers:**
```
Authorization: Bearer <token>
```

### 9.2 Pacchetti

#### GET `/pacchetti.php`
Lista pacchetti disponibili.

#### POST `/pacchetti.php`
Acquista pacchetto (utente autenticato).

**Body:**
```json
{
  "pacchetto_id": 2,
  "metodo_pagamento": "bonifico",
  "riferimento_pagamento": "BNF12345"
}
```

#### GET `/pacchetti.php?action=my-purchases`
I miei acquisti.

#### PATCH `/pacchetti.php?action=confirm&id=xxx`
Conferma pagamento (ufficio/admin).

### 9.3 Check-in

#### GET `/checkin.php?qr=PSC-xxx-xxx`
Verifica QR code (bagnino).

#### POST `/checkin.php`
Registra check-in (bagnino).

**Body:**
```json
{
  "qr_code": "PSC-xxx-xxx",
  "note": ""
}
```

#### GET `/checkin.php?action=today`
Check-in di oggi (bagnino).

### 9.4 Documenti

#### GET `/documenti.php?action=types`
Tipi documento obbligatori.

#### POST `/documenti.php`
Upload documento.

**Form-data:**
```
file: [File]
tipo_documento_id: 1
```

#### GET `/documenti.php?action=pending`
Documenti da revisionare (ufficio).

#### PATCH `/documenti.php?action=review&id=xxx`
Approva/rifiuta documento (ufficio).

**Body:**
```json
{
  "stato": "approved",
  "note_revisione": "Documento OK"
}
```

### 9.5 Statistiche

#### GET `/stats.php?action=dashboard`
Dashboard generale (ufficio/admin).

#### GET `/stats.php?action=report-daily&data=2026-02-15`
Report giornaliero.

#### GET `/stats.php?action=export-users`
Export utenti CSV.

---

## 🔧 10. TROUBLESHOOTING <a name="troubleshooting"></a>

### Errore: "Connection refused"

**Causa**: MySQL non avviato.

**Soluzione**:
1. Apri XAMPP Control Panel
2. Clicca "Start" su MySQL
3. Attendi che diventi verde

### Errore: "Access denied for user 'root'"

**Causa**: Password MySQL errata.

**Soluzione**:
1. Apri `api/config.php`
2. Verifica `DB_PASS`
3. Di default XAMPP usa password vuota: `''`

### Errore: "Table doesn't exist"

**Causa**: Database non importato.

**Soluzione**:
1. Vai su phpMyAdmin
2. Elimina database `piscina_gestione` (se esiste)
3. Reimporta `IMPORTA_QUESTO_DB.sql`

### Errore 404 su API

**Causa**: Progetto non nella cartella giusta.

**Soluzione**:
1. Verifica che il progetto sia in `htdocs/piscina-php/`
2. URL corretto: `http://localhost/piscina-php/api/auth.php`

### Errore CORS

**Causa**: Browser blocca richieste cross-origin.

**Soluzione**:
- Apri sempre con `http://localhost` (non file:///)
- Verifica header CORS in `config.php`

### Upload file non funziona

**Causa**: Permessi cartella.

**Soluzione Windows**:
1. Tasto destro su `uploads/`
2. Proprietà → Sicurezza
3. Aggiungi permessi scrittura per "Everyone"

**Soluzione macOS/Linux**:
```bash
chmod -R 755 uploads/
```

### Email non vengono inviate

**Causa**: API Key Brevo non configurata.

**Soluzione**:
1. Registrati su https://www.brevo.com
2. Ottieni API Key
3. Inseriscila in `config.php`
4. Oppure disabilita invio email (facoltativo)

---

## 📞 SUPPORTO

Per problemi o domande:

- **Email**: info@piscina.it
- **Telefono**: 123 456 789

---

## 🎉 CONGRATULAZIONI!

Hai installato con successo il **Sistema Gestione Piscina**!

### Prossimi passi:

1. ✅ Cambia password utenti di default
2. ✅ Configura email (Brevo API)
3. ✅ Personalizza logo e colori
4. ✅ Aggiungi altri pacchetti
5. ✅ Testa workflow completo
6. ✅ Backup database settimanale

---

**Versione**: 1.0.0  
**Data**: 2026-02-15  
**Autore**: Sistema Gestione Piscina Team
