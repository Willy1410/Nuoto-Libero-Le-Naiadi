# 📚 AGGIORNAMENTO OPZIONE B - Gli Squaletti Backend Express

## 🎯 Come Aggiungere le Funzionalità del Prompt al Progetto Esistente

Il progetto "Gli Squaletti" ha già un backend Express/PostgreSQL funzionante con 3 ruoli (Admin, Segreteria, Utente). Per implementare le funzionalità richieste nel prompt AI developer, segui questa guida.

---

## 📋 FUNZIONALITÀ DA AGGIUNGERE

### 1️⃣ Aggiungi Ruolo "Bagnino" (livello 2)

**Database Migration:**
```sql
-- backend/scripts/migrate.js
ALTER TABLE ruoli ADD CONSTRAINT ruoli_livello_check CHECK (livello IN (1, 2, 3, 4));

INSERT INTO ruoli (id, nome, descrizione, livello) VALUES
(uuid_generate_v4(), 'bagnino', 'Bagnino: scansiona QR, registra ingressi', 2)
ON CONFLICT DO NOTHING;
```

**Seed Data:**
```sql
-- Crea utente bagnino test
INSERT INTO users (id, username, email, password_hash, role_id, name) VALUES
(uuid_generate_v4(), 'bagnino', 'bagnino@glisqualetti.it', 
  crypt('bagnino123', gen_salt('bf')),
  (SELECT id FROM ruoli WHERE nome = 'bagnino'),
  'Luca Verdi');
```

**Backend Route:**
```javascript
// backend/routes/bagnino.js
const express = require('express');
const router = express.Router();
const authMiddleware = require('../middleware/auth');

// Solo bagnini (livello >= 2)
router.use(authMiddleware);
router.use((req, res, next) => {
  if (req.user.livello < 2) {
    return res.status(403).json({ error: 'Permessi insufficienti' });
  }
  next();
});

// GET presenze oggi
router.get('/presenze-oggi', async (req, res) => {
  const today = new Date().toISOString().split('T')[0];
  const { rows } = await db.query(`
    SELECT c.*, u.nome, u.cognome, a.ingressi_rimanenti
    FROM check_ins c
    JOIN users u ON c.user_id = u.id
    JOIN acquisti a ON c.acquisto_id = a.id
    WHERE DATE(c.timestamp) = $1
    ORDER BY c.timestamp DESC
  `, [today]);
  res.json({ success: true, data: rows });
});

module.exports = router;
```

**Frontend:**
```html
<!-- bagnino/dashboard.html -->
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard Bagnino</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
  <div class="dashboard-container">
    <aside class="sidebar">
      <h2>Bagnino</h2>
      <nav>
        <a href="/bagnino/dashboard.html">Dashboard</a>
        <a href="/bagnino/scanner.html">Scanner QR</a>
        <a href="/bagnino/presenze-oggi.html">Presenze Oggi</a>
        <a href="#" onclick="logout()">Logout</a>
      </nav>
    </aside>
    <main>
      <h1>Dashboard Bagnino</h1>
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Presenze Oggi</h3>
          <p id="presenzeOggi">0</p>
        </div>
      </div>
      <div id="listapresenze"></div>
    </main>
  </div>
  <script src="/js/api-client.js"></script>
  <script>
    async function loadDashboard() {
      const { data } = await api.get('/bagnino/presenze-oggi');
      document.getElementById('presenzeOggi').textContent = data.length;
      // Render lista...
    }
    loadDashboard();
  </script>
</body>
</html>
```

---

### 2️⃣ Sistema Documenti Obbligatori

**Database Migration:**
```sql
CREATE TABLE tipi_documento (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  nome VARCHAR(100) UNIQUE NOT NULL,
  descrizione TEXT,
  obbligatorio BOOLEAN DEFAULT false,
  template_url TEXT,
  ordine INTEGER
);

INSERT INTO tipi_documento (nome, descrizione, obbligatorio, ordine) VALUES
('Modulo Iscrizione', 'Modulo iscrizione compilato e firmato', true, 1),
('Certificato Medico', 'Certificato medico sportivo non agonistico', true, 2),
('Regolamento Interno', 'Regolamento piscina firmato', true, 3),
('Privacy GDPR', 'Consenso trattamento dati', true, 4),
('Documento Identità', 'Copia carta identità o patente', true, 5);

CREATE TABLE documenti_utente (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  tipo_documento_id UUID REFERENCES tipi_documento(id),
  file_url TEXT NOT NULL,
  file_name VARCHAR(255),
  stato VARCHAR(20) DEFAULT 'in_attesa' CHECK (stato IN ('da_inviare', 'in_attesa', 'approvato', 'rifiutato')),
  note_revisione TEXT,
  data_caricamento TIMESTAMP DEFAULT NOW(),
  data_revisione TIMESTAMP,
  revisionato_da UUID REFERENCES users(id),
  scadenza DATE
);
```

**Backend Routes:**
```javascript
// backend/routes/documenti.js
const multer = require('multer');
const path = require('path');

// Configura storage
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, 'uploads/documenti/');
  },
  filename: (req, file, cb) => {
    const userId = req.user.id;
    const timestamp = Date.now();
    const ext = path.extname(file.originalname);
    cb(null, `${userId}-${timestamp}${ext}`);
  }
});

const upload = multer({ 
  storage,
  limits: { fileSize: 5 * 1024 * 1024 }, // 5MB
  fileFilter: (req, file, cb) => {
    const allowed = ['.pdf', '.jpg', '.jpeg', '.png'];
    const ext = path.extname(file.originalname).toLowerCase();
    if (allowed.includes(ext)) {
      cb(null, true);
    } else {
      cb(new Error('Formato file non valido'));
    }
  }
});

// POST /documenti/upload
router.post('/upload', authMiddleware, upload.single('file'), async (req, res) => {
  const { tipo_documento_id } = req.body;
  const file_url = `/uploads/documenti/${req.file.filename}`;
  
  const { rows } = await db.query(`
    INSERT INTO documenti_utente (user_id, tipo_documento_id, file_url, file_name, stato)
    VALUES ($1, $2, $3, $4, 'in_attesa')
    RETURNING *
  `, [req.user.id, tipo_documento_id, file_url, req.file.originalname]);
  
  // TODO: Invia email notifica ufficio
  
  res.json({ success: true, data: rows[0] });
});

// PUT /documenti/:id/approve (solo ufficio/admin)
router.put('/:id/approve', authMiddleware, async (req, res) => {
  if (req.user.livello < 3) {
    return res.status(403).json({ error: 'Permessi insufficienti' });
  }
  
  const { id } = req.params;
  const { stato, note_revisione } = req.body;
  
  const { rows } = await db.query(`
    UPDATE documenti_utente
    SET stato = $1, note_revisione = $2, data_revisione = NOW(), revisionato_da = $3
    WHERE id = $4
    RETURNING *
  `, [stato, note_revisione, req.user.id, id]);
  
  // TODO: Invia email notifica utente
  
  res.json({ success: true, data: rows[0] });
});
```

---

### 3️⃣ Sistema Prenotazioni Turni (Lun/Mer/Ven)

**Database:**
```sql
CREATE TABLE prenotazioni (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES users(id),
  acquisto_id UUID REFERENCES acquisti(id),
  data_turno DATE NOT NULL,
  giorno_settimana VARCHAR(10) CHECK (giorno_settimana IN ('lunedi', 'mercoledi', 'venerdi')),
  fascia_oraria VARCHAR(20) CHECK (fascia_oraria IN ('mattina', 'pomeriggio')),
  orario_inizio TIME,
  orario_fine TIME,
  stato VARCHAR(20) DEFAULT 'confermata' CHECK (stato IN ('confermata', 'completata', 'cancellata')),
  created_at TIMESTAMP DEFAULT NOW()
);
```

**Backend:**
```javascript
// POST /prenotazioni
router.post('/', authMiddleware, async (req, res) => {
  const { acquisto_id, data_turno, fascia_oraria } = req.body;
  
  // Valida giorno (solo Lun/Mer/Ven)
  const dayOfWeek = new Date(data_turno).getDay(); // 0=Dom, 1=Lun, 3=Mer, 5=Ven
  if (![1, 3, 5].includes(dayOfWeek)) {
    return res.status(400).json({ error: 'Giorno non valido' });
  }
  
  const orari = {
    mattina: ['07:00', '09:00'],
    pomeriggio: ['13:00', '14:00']
  };
  
  const { rows } = await db.query(`
    INSERT INTO prenotazioni (user_id, acquisto_id, data_turno, fascia_oraria, orario_inizio, orario_fine)
    VALUES ($1, $2, $3, $4, $5, $6)
    RETURNING *
  `, [req.user.id, acquisto_id, data_turno, fascia_oraria, ...orari[fascia_oraria]]);
  
  // TODO: Invia email conferma + reminder 24h
  
  res.json({ success: true, data: rows[0] });
});
```

**Frontend (Flatpickr):**
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<input type="text" id="dataPrenotazione" placeholder="Seleziona data">

<script>
flatpickr("#dataPrenotazione", {
  locale: "it",
  minDate: "today",
  disable: [
    function(date) {
      // Abilita solo Lun(1), Mer(3), Ven(5)
      return ![1, 3, 5].includes(date.getDay());
    }
  ]
});
</script>
```

---

### 4️⃣ CMS Modifica Contenuti

**Database:**
```sql
CREATE TABLE contenuti_sito (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  sezione VARCHAR(50),
  chiave VARCHAR(100) UNIQUE,
  valore TEXT,
  tipo VARCHAR(20) CHECK (tipo IN ('text', 'html', 'image', 'url')),
  ordine INTEGER,
  updated_at TIMESTAMP DEFAULT NOW()
);

INSERT INTO contenuti_sito (sezione, chiave, valore, tipo) VALUES
('home', 'hero_title', 'Nuoto Libero alla Piscina Naiadi', 'text'),
('home', 'hero_subtitle', 'Prenota il tuo turno', 'text'),
('orari', 'lunedi_mattina', '07:00-09:00', 'text');
```

**Backend:**
```javascript
// GET /cms/contenuti/:sezione
router.get('/contenuti/:sezione', async (req, res) => {
  const { rows } = await db.query(`
    SELECT * FROM contenuti_sito WHERE sezione = $1 ORDER BY ordine
  `, [req.params.sezione]);
  res.json({ success: true, data: rows });
});

// PUT /cms/contenuti/:id (solo admin)
router.put('/contenuti/:id', authMiddleware, async (req, res) => {
  if (req.user.livello < 4) {
    return res.status(403).json({ error: 'Solo admin' });
  }
  
  const { valore } = req.body;
  const { rows } = await db.query(`
    UPDATE contenuti_sito SET valore = $1, updated_at = NOW()
    WHERE id = $2 RETURNING *
  `, [valore, req.params.id]);
  
  res.json({ success: true, data: rows[0] });
});
```

---

### 5️⃣ Export Excel/PDF

**Backend (ExcelJS):**
```javascript
const ExcelJS = require('exceljs');

router.get('/export/utenti', authMiddleware, async (req, res) => {
  if (req.user.livello < 3) return res.status(403).json({ error: 'Permessi insufficienti' });
  
  const { rows } = await db.query('SELECT nome, cognome, email, telefono FROM users');
  
  const workbook = new ExcelJS.Workbook();
  const worksheet = workbook.addWorksheet('Utenti');
  
  worksheet.columns = [
    { header: 'Nome', key: 'nome', width: 20 },
    { header: 'Cognome', key: 'cognome', width: 20 },
    { header: 'Email', key: 'email', width: 30 },
    { header: 'Telefono', key: 'telefono', width: 15 }
  ];
  
  worksheet.addRows(rows);
  
  res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  res.setHeader('Content-Disposition', 'attachment; filename=utenti.xlsx');
  
  await workbook.xlsx.write(res);
  res.end();
});
```

---

### 6️⃣ PWA (Progressive Web App)

**Manifest:**
```json
// public/manifest.json
{
  "name": "Gli Squaletti - Gestione Piscina",
  "short_name": "Piscina",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#0EA5E9",
  "theme_color": "#0EA5E9",
  "icons": [
    { "src": "/assets/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/assets/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

**Service Worker:**
```javascript
// public/service-worker.js
const CACHE_NAME = 'piscina-v1';

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      cache.addAll(['/', '/css/style.css', '/js/main.js'])
    )
  );
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request).then((response) => response || fetch(e.request))
  );
});
```

**HTML:**
```html
<!-- In tutte le pagine -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0EA5E9">
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
  }
</script>
```

---

## 📦 Dipendenze da Installare

```bash
cd backend
npm install multer exceljs
```

---

## ✅ Checklist Implementazione Opzione B

- [ ] Aggiungi ruolo Bagnino (database + routes + frontend)
- [ ] Implementa sistema documenti (5 tipi + upload + approvazione)
- [ ] Aggiungi prenotazioni turni (solo Lun/Mer/Ven + calendario)
- [ ] Implementa CMS per modifica contenuti sito
- [ ] Aggiungi gallery admin con upload immagini
- [ ] Implementa export Excel/PDF
- [ ] Converti in PWA (manifest + service worker)
- [ ] Setup email transazionali (Nodemailer + Brevo/SendGrid)
- [ ] Testing completo tutti i flussi

---

## 🎯 Tempo Stimato: 1-2 settimane

Con il backend già funzionante, aggiungere queste feature richiede **7-10 giorni lavorativi**.

---

*Guida creata il 15 Febbraio 2026*
