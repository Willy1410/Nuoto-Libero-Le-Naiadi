# 🚀 Setup e Avvio Backend - Gli Squaletti

## 📋 Prerequisiti

Prima di iniziare, assicurati di avere installato:

- **Node.js** v16 o superiore ([Download](https://nodejs.org/))
- **PostgreSQL** v13 o superiore ([Download](https://www.postgresql.org/download/))
- **npm** o **yarn** (incluso con Node.js)

---

## 🛠️ STEP 1: Installazione PostgreSQL

### Su Windows:
1. Scarica PostgreSQL Installer da [postgresql.org](https://www.postgresql.org/download/windows/)
2. Esegui l'installer e segui la procedura guidata
3. **Importante**: Annota la password che imposti per l'utente `postgres`
4. Installa pgAdmin (incluso nell'installer) per gestire il database visualmente

### Su macOS:
```bash
# Con Homebrew
brew install postgresql@15
brew services start postgresql@15
```

### Su Linux (Ubuntu/Debian):
```bash
sudo apt update
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

---

## 🗄️ STEP 2: Creazione Database

### Opzione A: Da Terminale

```bash
# Accedi a PostgreSQL
psql -U postgres

# Crea il database
CREATE DATABASE gli_squaletti;

# Esci
\q
```

### Opzione B: Con pgAdmin (Windows)

1. Apri pgAdmin
2. Click destro su "Databases" → "Create" → "Database"
3. Nome: `gli_squaletti`
4. Click "Save"

---

## 📦 STEP 3: Setup Backend

### 1. Naviga nella cartella backend

```bash
cd backend
```

### 2. Installa le dipendenze

```bash
npm install
```

Questo installerà:
- ✅ Express (web server)
- ✅ PostgreSQL client
- ✅ bcrypt (hash password)
- ✅ jsonwebtoken (JWT auth)
- ✅ cors, helmet, morgan, etc.

### 3. Configura le variabili d'ambiente

```bash
# Copia il file di esempio
cp .env.example .env

# Modifica .env con i tuoi dati
```

Apri `.env` e modifica:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_NAME=gli_squaletti
DB_USER=postgres
DB_PASSWORD=TUA_PASSWORD_QUI    # ⚠️ CAMBIA QUESTA!

# JWT Secret
JWT_SECRET=una_chiave_segreta_molto_lunga_e_casuale_12345
JWT_EXPIRES_IN=7d

# Server
PORT=3000
NODE_ENV=development

# CORS (URL del frontend)
CORS_ORIGIN=http://localhost:8080
```

⚠️ **IMPORTANTE**: Cambia `DB_PASSWORD` con la password di PostgreSQL!

---

## 🏗️ STEP 4: Creazione Tabelle Database

```bash
# Esegui le migration
npm run migrate
```

Output atteso:
```
🚀 Avvio migrazione database...

📝 Creazione tabella users...
✅ Tabella users creata

📝 Creazione tabella packages...
✅ Tabella packages creata

📝 Creazione tabella entries...
✅ Tabella entries creata

📝 Creazione tabella payments...
✅ Tabella payments creata

📝 Creazione tabella documents...
✅ Tabella documents creata

📝 Creazione indici...
✅ Indici creati

📝 Creazione trigger updated_at...
✅ Trigger creati

🎉 Migrazione completata con successo!
```

---

## 🌱 STEP 5: Popolamento Database (Seed)

```bash
# Inserisci i dati di test
npm run seed
```

Output atteso:
```
🌱 Avvio seeding database...

👤 Creazione utente Admin...
✅ Admin creato

👤 Creazione utente Segreteria...
✅ Segreteria creata

👥 Creazione utenti test...

  ✅ Mario Rossi (8/10 ingressi)
  ✅ Laura Bianchi (2/10 ingressi)
  ✅ Giuseppe Verdi (0/10 ingressi - SCADUTO)
  ✅ Anna Ferrari (3/3 promo)

🎉 Seeding completato con successo!

📊 Riepilogo:
  - 1 Admin
  - 1 Segreteria
  - 4 Utenti con pacchetti
```

---

## 🚀 STEP 6: Avvio Server

```bash
# Avvio normale
npm start

# Oppure con auto-reload (development)
npm run dev
```

Output atteso:
```
╔═══════════════════════════════════════════╗
║   🏊 Gli Squaletti API Server            ║
║                                           ║
║   🚀 Server avviato su porta 3000        ║
║   🌍 Ambiente: development               ║
║   📡 CORS: http://localhost:8080         ║
║                                           ║
║   ✅ Pronto per le richieste!            ║
╚═══════════════════════════════════════════╝
✅ Database connesso con successo
```

---

## 🧪 STEP 7: Testing API

### Test con curl:

```bash
# Health check
curl http://localhost:3000/api/health

# Login
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Risposta attesa:
{
  "success": true,
  "message": "Login effettuato con successo",
  "data": {
    "user": {
      "id": "...",
      "username": "admin",
      "role": "admin",
      ...
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

### Test con Postman o Insomnia:

1. **Importa collezione** (opzionale: crea una collezione con tutti gli endpoint)
2. **Test login**:
   - Method: POST
   - URL: `http://localhost:3000/api/auth/login`
   - Body (JSON):
     ```json
     {
       "username": "admin",
       "password": "admin123"
     }
     ```
3. **Copia il token** dalla risposta
4. **Test endpoint protetti**:
   - Method: GET
   - URL: `http://localhost:3000/api/users`
   - Headers: `Authorization: Bearer TUO_TOKEN_QUI`

---

## 🌐 STEP 8: Avvio Frontend

### Opzione A: Live Server (VSCode)

1. Installa estensione "Live Server" in VSCode
2. Click destro su `index.html` → "Open with Live Server"
3. Il browser si aprirà automaticamente su `http://localhost:5500` (o porta diversa)

⚠️ **IMPORTANTE**: Aggiorna `js/api-client.js` line 2:
```javascript
const API_BASE_URL = 'http://localhost:3000/api';  // ✅ Porta backend
```

### Opzione B: Python HTTP Server

```bash
# Nella cartella root del progetto (dove c'è index.html)
python -m http.server 8080

# Oppure con Python 2
python -m SimpleHTTPServer 8080
```

Apri browser: `http://localhost:8080`

### Opzione C: Node.js http-server

```bash
# Installa globalmente
npm install -g http-server

# Avvia
http-server -p 8080

# Con CORS abilitato
http-server -p 8080 --cors
```

---

## ✅ STEP 9: Verifica Funzionamento Completo

### Test Login Frontend → Backend:

1. Apri `http://localhost:8080` (o la tua porta)
2. Clicca "Area Riservata"
3. Inserisci credenziali:
   - Username: `admin`
   - Password: `admin123`
4. ✅ **Se tutto funziona**: Verrai reindirizzato a `dashboard-admin.html`

### Credenziali di Test:

```
ADMIN:
Username: admin
Password: admin123

SEGRETERIA:
Username: segreteria
Password: segreteria123

UTENTI:
Username: mario.rossi / laura.bianchi / giuseppe.verdi / anna.ferrari
Password: password123 (tutti)
```

---

## 🐛 Troubleshooting

### Errore: "Database connection failed"

```bash
# Verifica che PostgreSQL sia avviato
# Windows: Apri Services → PostgreSQL 15 Server → Start
# Linux/Mac:
sudo systemctl status postgresql
# O
brew services list | grep postgresql
```

### Errore: "Port 3000 already in use"

```bash
# Cambia porta in .env
PORT=3001

# Oppure ferma processo sulla porta 3000
# Windows
netstat -ano | findstr :3000
taskkill /PID <PID> /F

# Linux/Mac
lsof -i :3000
kill -9 <PID>
```

### Errore: "CORS policy blocked"

Verifica che in `backend/.env`:
```env
CORS_ORIGIN=http://localhost:8080  # URL del tuo frontend
```

E in `js/api-client.js`:
```javascript
const API_BASE_URL = 'http://localhost:3000/api';
```

### Errore: "JWT token invalid"

```bash
# Cancella localStorage nel browser
# F12 → Console → Digita:
localStorage.clear()
# Poi ricarica la pagina
```

---

## 📊 Struttura Database

### Tabelle Create:

1. **users** → Utenti (admin, segreteria, user)
2. **packages** → Pacchetti ingressi
3. **entries** → Log ingressi
4. **payments** → Log pagamenti
5. **documents** → Documenti utenti

### Relazioni:

```
users (1) → (N) packages
users (1) → (N) entries
users (1) → (N) payments
users (1) → (N) documents
packages (1) → (N) entries
```

---

## 🎯 Endpoint API Disponibili

### Auth:
- `POST /api/auth/register` - Registrazione
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Profilo utente (protetto)
- `POST /api/auth/change-password` - Cambio password (protetto)

### Users:
- `GET /api/users` - Lista utenti (admin/segreteria)
- `GET /api/users/stats` - Statistiche (admin/segreteria)
- `GET /api/users/:id` - Dettagli utente
- `GET /api/users/qr/:qrCode` - Utente da QR code
- `PUT /api/users/:id` - Aggiorna utente
- `DELETE /api/users/:id` - Disattiva utente (admin)

### Entries:
- `POST /api/entries/register` - Registra ingresso (segreteria)
- `POST /api/entries/purchase` - Acquista pacchetto (segreteria)
- `GET /api/entries/report/daily` - Report giornaliero (segreteria)

---

## 🔒 Sicurezza Implementata

✅ Password hashate con bcrypt (10 rounds)  
✅ JWT tokens con scadenza (7 giorni configurabile)  
✅ Rate limiting (100 req/15min)  
✅ Helmet.js (security headers)  
✅ CORS configurato  
✅ Validazione input  
✅ SQL injection protection (parametrizzate query)  
✅ Role-based access control (RBAC)  

---

## 📝 Comandi Utili

```bash
# Backend
cd backend
npm install          # Installa dipendenze
npm run migrate      # Crea tabelle
npm run seed         # Popola database
npm start            # Avvia server
npm run dev          # Avvia con auto-reload

# Database
psql -U postgres -d gli_squaletti    # Accedi al database
\dt                                   # Lista tabelle
SELECT * FROM users;                  # Query esempio

# Testing
curl http://localhost:3000/api/health  # Test health
```

---

## 🎉 Progetto Pronto!

Se tutto è andato bene:
- ✅ Backend API funzionante su `http://localhost:3000`
- ✅ Frontend funzionante su `http://localhost:8080`
- ✅ Database PostgreSQL popolato
- ✅ Login frontend → backend funziona
- ✅ JWT authentication attivo

**Prossimi step**: Test completo delle funzionalità (dashboard, registrazione ingressi, report, etc.)

---

**Problemi?** Controlla:
1. PostgreSQL è avviato?
2. `.env` configurato correttamente?
3. Porte 3000 e 8080 libere?
4. CORS_ORIGIN corrisponde all'URL frontend?
