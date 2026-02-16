# 🏊 Gli Squaletti Backend API

Backend completo per il sistema di gestione piscina Gli Squaletti.

## 🛠️ Stack Tecnologico

- **Node.js** v16+ - Runtime JavaScript
- **Express.js** v4 - Web framework
- **PostgreSQL** v13+ - Database relazionale
- **bcrypt** - Hash password sicuro
- **jsonwebtoken** - Autenticazione JWT
- **express-validator** - Validazione input
- **helmet** - Security headers
- **cors** - Cross-Origin Resource Sharing
- **morgan** - HTTP request logger

## 📁 Struttura Progetto

```
backend/
├── config/
│   └── database.js          # Configurazione PostgreSQL
├── controllers/
│   ├── authController.js    # Login, register, JWT
│   ├── usersController.js   # Gestione utenti
│   └── entriesController.js # Ingressi e pacchetti
├── middleware/
│   ├── auth.js              # Middleware autenticazione
│   └── validator.js         # Middleware validazione
├── routes/
│   ├── auth.js              # Routes autenticazione
│   ├── users.js             # Routes utenti
│   └── entries.js           # Routes ingressi
├── scripts/
│   ├── migrate.js           # Creazione tabelle DB
│   └── seed.js              # Popolamento dati iniziali
├── .env.example             # Variabili ambiente esempio
├── .gitignore
├── package.json
├── server.js                # Entry point server
├── SETUP.md                 # Guida setup dettagliata
└── README.md                # Questo file
```

## 🚀 Quick Start

```bash
# 1. Installa dipendenze
npm install

# 2. Configura .env
cp .env.example .env
# Modifica .env con i tuoi dati

# 3. Crea database PostgreSQL
createdb gli_squaletti

# 4. Esegui migration
npm run migrate

# 5. Popola database
npm run seed

# 6. Avvia server
npm start
```

📖 **Guida completa**: Leggi [SETUP.md](./SETUP.md) per istruzioni dettagliate.

## 📡 API Endpoints

### Autenticazione

| Method | Endpoint | Descrizione | Auth |
|--------|----------|-------------|------|
| POST | `/api/auth/register` | Registrazione nuovo utente | No |
| POST | `/api/auth/login` | Login con username/password | No |
| POST | `/api/auth/logout` | Logout (client-side) | No |
| GET | `/api/auth/me` | Profilo utente loggato | Yes |
| POST | `/api/auth/change-password` | Cambio password | Yes |

### Utenti

| Method | Endpoint | Descrizione | Ruoli |
|--------|----------|-------------|-------|
| GET | `/api/users` | Lista tutti gli utenti | Admin, Segreteria |
| GET | `/api/users/stats` | Statistiche sistema | Admin, Segreteria |
| GET | `/api/users/:id` | Dettagli utente | Owner, Admin, Segreteria |
| GET | `/api/users/qr/:qrCode` | Utente da QR code | Authenticated |
| PUT | `/api/users/:id` | Aggiorna utente | Owner, Admin, Segreteria |
| DELETE | `/api/users/:id` | Disattiva utente | Admin |

### Ingressi e Pacchetti

| Method | Endpoint | Descrizione | Ruoli |
|--------|----------|-------------|-------|
| POST | `/api/entries/register` | Registra ingresso | Admin, Segreteria |
| POST | `/api/entries/purchase` | Acquista pacchetto | Admin, Segreteria |
| GET | `/api/entries/report/daily` | Report giornaliero | Admin, Segreteria |

## 🔐 Autenticazione

Tutte le route protette richiedono un JWT token nell'header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Flow di autenticazione:

1. **Login**: `POST /api/auth/login` → Ricevi `token`
2. **Richieste protette**: Includi `Authorization: Bearer <token>`
3. **Refresh**: Token scade dopo 7 giorni (configurabile)

## 🗄️ Schema Database

### users
```sql
id UUID PRIMARY KEY
username VARCHAR(100) UNIQUE
email VARCHAR(255) UNIQUE
password_hash VARCHAR(255)
role VARCHAR(20)  -- admin, segreteria, user
name VARCHAR(255)
phone VARCHAR(20)
fiscal_code VARCHAR(16)
birth_date DATE
birth_place VARCHAR(255)
address TEXT
qr_code VARCHAR(50) UNIQUE
active BOOLEAN
first_login BOOLEAN
created_at TIMESTAMP
updated_at TIMESTAMP
```

### packages
```sql
id UUID PRIMARY KEY
user_id UUID → users(id)
package_type VARCHAR(50)  -- single, 10_entries, promo
total_entries INTEGER
remaining_entries INTEGER
purchase_date DATE
expiry_date DATE
price DECIMAL(10,2)
payment_method VARCHAR(50)
active BOOLEAN
created_at TIMESTAMP
updated_at TIMESTAMP
```

### entries
```sql
id UUID PRIMARY KEY
user_id UUID → users(id)
package_id UUID → packages(id)
entry_date DATE
entry_time TIME
staff_name VARCHAR(255)
staff_id UUID → users(id)
remaining_after INTEGER
notes TEXT
created_at TIMESTAMP
```

### payments
```sql
id UUID PRIMARY KEY
user_id UUID → users(id)
package_id UUID → packages(id)
amount DECIMAL(10,2)
payment_method VARCHAR(50)
payment_date DATE
staff_name VARCHAR(255)
staff_id UUID → users(id)
transaction_id VARCHAR(255)
status VARCHAR(50)
notes TEXT
created_at TIMESTAMP
```

## 📊 Esempi di Utilizzo

### Login

```bash
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }'
```

Risposta:
```json
{
  "success": true,
  "message": "Login effettuato con successo",
  "data": {
    "user": {
      "id": "...",
      "username": "admin",
      "role": "admin",
      "name": "Amministratore"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

### Registra Ingresso

```bash
curl -X POST http://localhost:3000/api/entries/register \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "userId": "user-uuid-here"
  }'
```

### Lista Utenti

```bash
curl -X GET http://localhost:3000/api/users?role=user&page=1&limit=10 \
  -H "Authorization: Bearer <TOKEN>"
```

## 🛡️ Sicurezza

- ✅ **Password** hashate con bcrypt (10 rounds)
- ✅ **JWT tokens** con scadenza configurabile
- ✅ **Rate limiting** (100 richieste/15min)
- ✅ **Helmet.js** per security headers
- ✅ **CORS** configurato e restrittivo
- ✅ **Validazione input** con express-validator
- ✅ **SQL injection** prevenuto (parametrized queries)
- ✅ **RBAC** (Role-Based Access Control)

## 🧪 Testing

```bash
# Health check
curl http://localhost:3000/api/health

# Risposta attesa
{
  "success": true,
  "message": "API Gli Squaletti funzionante",
  "timestamp": "2026-02-13T20:00:00.000Z",
  "uptime": 123.45
}
```

## 📝 Variabili d'Ambiente

Copia `.env.example` a `.env` e configura:

```env
# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=gli_squaletti
DB_USER=postgres
DB_PASSWORD=your_password

# JWT
JWT_SECRET=your_super_secret_key
JWT_EXPIRES_IN=7d

# Server
PORT=3000
NODE_ENV=development

# CORS
CORS_ORIGIN=http://localhost:8080

# Rate Limiting
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100
```

## 📦 Scripts

```bash
npm start            # Avvia server
npm run dev          # Avvia con nodemon (auto-reload)
npm run migrate      # Esegui migration database
npm run seed         # Popola database con dati test
npm test             # Esegui test (to be implemented)
```

## 🐛 Troubleshooting

### Errore di connessione al database

```bash
# Verifica che PostgreSQL sia avviato
sudo systemctl status postgresql  # Linux
brew services list                # macOS

# Verifica credenziali in .env
DB_USER=postgres
DB_PASSWORD=<tua_password>
```

### Errore CORS

```bash
# Verifica che CORS_ORIGIN in .env corrisponda all'URL del frontend
CORS_ORIGIN=http://localhost:8080
```

### Token JWT non valido

```javascript
// Cancella localStorage nel browser
localStorage.clear();
// Poi ricarica la pagina
```

## 🔄 Aggiornamento Database

Per aggiornare lo schema:

1. Modifica `scripts/migrate.js`
2. ⚠️ **ATTENZIONE**: Farà DROP delle tabelle esistenti!
3. Esegui: `npm run migrate`
4. Esegui: `npm run seed`

## 📚 Documentazione API Completa

Collezione Postman: _Coming soon_

Swagger/OpenAPI: _Coming soon_

## 🤝 Contribuire

1. Fork il progetto
2. Crea un branch per la tua feature (`git checkout -b feature/NuovaFeature`)
3. Commit le modifiche (`git commit -m 'Aggiunta NuovaFeature'`)
4. Push al branch (`git push origin feature/NuovaFeature`)
5. Apri una Pull Request

## 📄 Licenza

© 2026 CLADAM GROUP - Società Sportiva. Tutti i diritti riservati.

## 📧 Supporto

Per domande o problemi:
- Email: info@glisqualetti.it
- Telefono: 123 456 789

---

**Made with ❤️ for Gli Squaletti** 🏊‍♂️
