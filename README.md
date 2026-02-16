# Gli Squaletti - Piscina Nuoto Libero

## 🏊‍♂️ Descrizione Progetto

Sito web completo per "Gli Squaletti", società sportiva specializzata in nuoto libero presso la **Piscina Naiadi di Pescara**. 

**Proprietà**: Società Sportiva CLADAM GROUP (proprietaria dei marchi "Le SqualOtte" e "Gli Squaletti")

## 🎯 Stato Attuale: MVP VISIVO COMPLETO

✅ **Completato**:
- Design responsive mobile-first con palette aqua/blue
- Sistema di navigazione completo con sticky menu + hamburger mobile
- 9 pagine HTML + dashboard mockup (login, dashboard utente, dashboard admin, ringraziamento)
- Integrazione foto reali Piscina Naiadi Pescara
- Sezione Chi Siamo con info CLADAM GROUP
- Form contatti, galleria con lightbox, downloadable PDFs
- Mockup sistema di login/registrazione
- Mockup dashboard utente con QR code, ingressi rimanenti, form iscrizione
- Mockup dashboard admin per gestione utenti/contenuti/immagini

## 📋 Pagine Implementate

### Pagine Pubbliche
1. **index.html** - Homepage con hero, benefici, FAQ, CTA
2. **chi-siamo.html** - Storia CLADAM GROUP, marchi Le SqualOtte e Gli Squaletti
3. **orari-tariffe.html** - Orari (Lun-Mer-Ven 07:00-09:00, 13:00-14:00) + Prezzi (Singolo 12€, 10 ingressi 100€) + Promo iscrizione 30€
4. **galleria.html** - Galleria foto Piscina Naiadi con filtri e lightbox
5. **moduli.html** - Elenco PDF scaricabili (iscrizione, regolamento, privacy, certificato)
6. **pacchetti.html** - Prezzi pacchetti + integrazione PayPal/Stripe (placeholder)
7. **contatti.html** - Form contatto, mappa Google, info piscina
8. **privacy.html, cookie.html, termini.html** - Pagine legali

### Pagine Area Riservata (MOCKUP)
9. **login.html** - Login page con demo credentials (admin/admin123 per admin, qualsiasi altro per user)
10. **dashboard-user.html** - Dashboard utente con:
    - Ingressi rimanenti
    - QR code personale (placeholder)
    - Form richiesta iscrizione
    - Documenti personali
11. **dashboard-admin.html** - Pannello amministrazione con:
    - Statistiche utenti/incassi
    - Gestione utenti (create, edit, generazione QR)
    - Modifica contenuti sito
    - Upload/gestione immagini
12. **ringraziamento.html** - Thank you page dopo invio form iscrizione

## 🎨 Design & Branding

- **Logo**: Baby shark "Gli Squaletti" (playful, child-friendly)
- **Palette Colori**: Aqua/Cyan + Blue + White (ispirata a https://squalettiacademy.it/)
- **Font**: Poppins (headings) + Roboto (body text)
- **Stile**: Clean, sporty, moderno, spazi bianchi, bottoni arrotondati, ombre leggere
- **Mobile-first**: Responsive su tutti i dispositivi

## 📍 Informazioni Piscina

- **Nome**: Piscina Naiadi
- **Indirizzo**: Via Federico Fellini, 2 - Spoltore (PE)
- **Telefono**: 123 456 789
- **Email**: info@glisqualetti.it
- **Società**: CLADAM GROUP
- **Link**: [Google Maps](https://maps.google.com/?q=Piscina+Naiadi+Pescara)

### Orari Nuoto Libero
- **Giorni**: Lunedì, Mercoledì, Venerdì
- **Mattina**: 07:00 - 09:00
- **Pomeriggio**: 13:00 - 14:00

### Prezzi
- **Ingresso singolo**: 12€
- **10 ingressi**: 100€ (validità 6 mesi) - PIÙ POPOLARE
- **PROMO**: Iscrizione società 30€ + 3 lezioni omaggio (valida fino 31/08/2026)

## 🛠️ Tecnologie Utilizzate (Frontend)

- HTML5 semantico
- CSS3 custom (40KB+ file style.css)
- JavaScript vanilla (main.js + payment.js)
- Font Awesome 6.4.0 per icone
- Google Fonts (Poppins, Roboto)
- PayPal SDK + Stripe.js (placeholder per implementazione)

## 📁 Struttura File

```
gli-squaletti/
├── index.html
├── chi-siamo.html
├── orari-tariffe.html
├── galleria.html
├── moduli.html
├── pacchetti.html
├── contatti.html
├── privacy.html
├── cookie.html
├── termini.html
├── login.html
├── dashboard-user.html
├── dashboard-admin.html
├── ringraziamento.html
├── css/
│   └── style.css
├── js/
│   ├── main.js
│   └── payment.js
├── assets/
│   ├── naiadi-outdoor-1.jpg
│   ├── naiadi-outdoor-2.jpg
│   ├── naiadi-outdoor-3.jpg
│   ├── naiadi-outdoor-4.jpg
│   ├── naiadi-indoor-1.jpg
│   └── README_ASSETS.txt
└── README.md
```

## 🚀 Quick Start

1. **Apri `index.html`** in un browser moderno
2. **Naviga** le varie sezioni del sito
3. **Testa** il mockup login:
   - Username: `admin` Password: `admin123` → Dashboard Admin
   - Qualsiasi altro username/password → Dashboard Utente
4. **Prova** il form richiesta iscrizione → Redirect a ringraziamento.html

## ⚠️ IMPORTANTE: Backend NON Implementato

Questo è un **MOCKUP VISIVO** per mostrare al cliente. Le seguenti funzionalità **richiedono backend**:

### 🔧 Funzionalità da Implementare (Backend Required)

#### 1. Sistema di Autenticazione
- [ ] Database utenti (Node.js/PHP/Python + PostgreSQL/MySQL)
- [ ] Hashing password (bcrypt)
- [ ] JWT/Session management
- [ ] Email verification
- [ ] Password reset/recovery
- [ ] First-login password change

#### 2. Generazione QR Code Dinamici
- [ ] Library QR (qrcode.js, node-qr-image, etc.)
- [ ] Associazione QR → User ID
- [ ] Scanner QR per verifica ingressi
- [ ] Update ingressi rimanenti in real-time

#### 3. Database Schema Necessario

**Tabella `users`**:
```sql
CREATE TABLE users (
  id UUID PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  phone VARCHAR(20),
  fiscal_code VARCHAR(16),
  birth_date DATE,
  birth_place VARCHAR(100),
  address TEXT,
  role ENUM('user', 'admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW(),
  first_login BOOLEAN DEFAULT TRUE,
  active BOOLEAN DEFAULT TRUE
);
```

**Tabella `packages`**:
```sql
CREATE TABLE packages (
  id UUID PRIMARY KEY,
  user_id UUID REFERENCES users(id),
  package_type VARCHAR(50), -- 'single', '10_entries', 'promo'
  total_entries INT,
  remaining_entries INT,
  purchase_date TIMESTAMP,
  expiry_date TIMESTAMP,
  price DECIMAL(10,2),
  qr_code TEXT, -- QR code base64 or URL
  active BOOLEAN DEFAULT TRUE
);
```

**Tabella `entries`** (log accessi):
```sql
CREATE TABLE entries (
  id UUID PRIMARY KEY,
  user_id UUID REFERENCES users(id),
  package_id UUID REFERENCES packages(id),
  entry_date TIMESTAMP DEFAULT NOW(),
  verified_by VARCHAR(100) -- admin/staff name
);
```

**Tabella `documents`**:
```sql
CREATE TABLE documents (
  id UUID PRIMARY KEY,
  user_id UUID REFERENCES users(id),
  document_type VARCHAR(50), -- 'medical_cert', 'registration', 'receipt'
  file_path TEXT,
  upload_date TIMESTAMP,
  expiry_date TIMESTAMP,
  status ENUM('pending', 'valid', 'expired') DEFAULT 'pending'
);
```

#### 4. Payment Integration
- [ ] PayPal REST API configuration (webhook, order capture)
- [ ] Stripe backend integration (payment intent, webhooks)
- [ ] Order confirmation emails
- [ ] Receipt generation (PDF)
- [ ] Payment failure handling

#### 5. CMS/Admin Features
- [ ] File upload (images, PDFs)
- [ ] Content editing API (WYSIWYG editor backend)
- [ ] User management CRUD API
- [ ] Stats/analytics dashboard data

#### 6. Email System
- [ ] SMTP configuration (SendGrid, AWS SES, Mailgun)
- [ ] Email templates (welcome, confirmation, password reset)
- [ ] Automated emails on registration/payment

#### 7. File Storage
- [ ] Upload di certificati medici, documenti
- [ ] Storage (AWS S3, Azure Blob, local file system)
- [ ] File validation (size, type)

## 🔗 API Endpoints da Creare

### Auth
- `POST /api/auth/register` - Registrazione nuovo utente
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `POST /api/auth/forgot-password` - Reset password
- `POST /api/auth/reset-password` - Nuova password
- `POST /api/auth/change-password` - Cambio password primo login

### Users
- `GET /api/users/me` - Dati utente loggato
- `PUT /api/users/me` - Update profilo
- `GET /api/users/:id/packages` - Pacchetti utente
- `GET /api/users/:id/documents` - Documenti utente
- `POST /api/users/:id/documents` - Upload documento

### Admin
- `GET /api/admin/users` - Lista tutti utenti
- `POST /api/admin/users` - Crea utente
- `PUT /api/admin/users/:id` - Modifica utente
- `DELETE /api/admin/users/:id` - Elimina utente
- `POST /api/admin/qr/generate/:userId` - Genera QR per utente
- `GET /api/admin/stats` - Statistiche dashboard

### Packages
- `POST /api/packages/purchase` - Acquisto pacchetto
- `GET /api/packages/:id` - Dettagli pacchetto
- `POST /api/entries/verify` - Verifica QR all'ingresso

### Content (CMS)
- `GET /api/content/:page` - Contenuti pagina
- `PUT /api/content/:page` - Aggiorna contenuti
- `POST /api/images/upload` - Upload immagine
- `DELETE /api/images/:id` - Elimina immagine

## 🧰 Stack Tecnologico Consigliato (Backend)

### Opzione 1: Node.js + Express
```
- Node.js + Express.js
- PostgreSQL / MySQL
- Sequelize ORM
- JWT (jsonwebtoken)
- bcrypt (password hashing)
- nodemailer (email)
- multer (file upload)
- qrcode (QR generation)
- stripe + paypal-rest-sdk
```

### Opzione 2: Python + Django
```
- Python + Django/FastAPI
- PostgreSQL
- Django ORM
- Django REST Framework
- python-jose (JWT)
- passlib (hashing)
- python-multipart (upload)
- qrcode / segno
- stripe-python + paypalrestsdk
```

### Opzione 3: PHP + Laravel
```
- PHP + Laravel
- MySQL
- Eloquent ORM
- Laravel Sanctum/Passport (auth)
- Mail facade
- Storage facade
- SimpleSoftwareIO/simple-qrcode
- Stripe PHP + PayPal SDK
```

## 📝 Configurazione Payment (TODO)

### PayPal
1. Crea account Business su [PayPal Developer](https://developer.paypal.com)
2. Ottieni Client ID (Sandbox + Live)
3. Sostituisci in `pacchetti.html` riga 26: `YOUR_PAYPAL_CLIENT_ID`
4. Configura webhook per order capture

### Stripe
1. Crea account su [Stripe Dashboard](https://dashboard.stripe.com)
2. Ottieni Publishable Key (Test + Live)
3. Sostituisci in `js/payment.js` riga 12: `pk_test_YOUR_STRIPE_PUBLISHABLE_KEY_HERE`
4. Configura webhook per payment success

## 🌐 Deployment Suggerito

### Frontend (Statico)
- **Netlify** / **Vercel** / **GitHub Pages** (gratis, auto-deploy da Git)
- **AWS S3 + CloudFront** (pay-as-you-go)

### Backend + Database
- **Heroku** (gratis/paid con PostgreSQL addon)
- **Railway** / **Render** (Node.js/Python hosting)
- **DigitalOcean** / **AWS EC2** (VPS completo)
- **Supabase** (Backend-as-a-Service con auth + DB + storage)

### Opzione No-Code/Low-Code (Ibrido)
- **Airtable** + **Zapier** + **Memberstack** (gestione dati/utenti senza codice backend)
- Costo mensile ~€50-100, personalizzazione limitata

## 📚 Prossimi Step di Sviluppo

1. ✅ Mockup visivo completo (FATTO)
2. ⬜ Scegliere stack backend (Node.js / Python / PHP)
3. ⬜ Setup database + schema
4. ⬜ Implementare autenticazione (JWT/Session)
5. ⬜ Creare API REST per CRUD utenti/pacchetti
6. ⬜ Integrazione payment gateway (PayPal + Stripe)
7. ⬜ Sistema generazione QR code dinamici
8. ⬜ Upload documenti + storage
9. ⬜ Email automation (SMTP setup)
10. ⬜ Dashboard admin funzionante
11. ⬜ Testing completo (unit + integration tests)
12. ⬜ Deploy production + DNS configuration

## 📄 PDF da Caricare in `assets/`

- `modulo-iscrizione.pdf`
- `regolamento-piscina.pdf`
- `informativa-privacy.pdf`
- `certificato-medico-info.pdf`
- `liberatoria-minori.pdf`
- `listino-prezzi.pdf`

## 🤝 Contatti

- **Email**: info@glisqualetti.it
- **Telefono**: 123 456 789
- **Indirizzo**: Via Federico Fellini, 2 - Spoltore (PE)

## 📜 Licenza

© 2026 CLADAM GROUP - Società Sportiva. Tutti i diritti riservati.

---

**Nota per Sviluppatori**: Questo progetto è un **MVP visivo completo**. Tutte le funzionalità mostrate nelle dashboard sono mockup. Per l'implementazione finale sarà necessario sviluppare un backend completo con database, API REST, sistema di autenticazione, generazione QR dinamici e integrazione payment gateway.

**Tempo Stimato Implementazione Backend**: 4-8 settimane (1 sviluppatore full-time)  
**Budget Stimato**: €3.000 - €8.000 (sviluppo) + €20-100/mese (hosting + database)
