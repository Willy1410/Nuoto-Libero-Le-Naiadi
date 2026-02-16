# 🏊 Sistema Gestione Piscina - Supabase Edition

## 📋 Panoramica

Sistema completo per gestione piscina con nuoto libero, basato su Supabase (Database + Auth + Storage).

**Stack Tecnologico:**
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: Supabase (PostgreSQL + Row Level Security)
- **Autenticazione**: Supabase Auth
- **Storage**: Supabase Storage (documenti + gallery)
- **Email**: Brevo via Supabase Edge Functions
- **PWA**: Manifest + Service Worker

---

## 🔐 Sistema a 4 Ruoli

| Ruolo | Livello | Descrizione |
|-------|---------|-------------|
| 👤 **Utente** | 1 | Acquista pacchetti, carica documenti, prenota turni, vede proprio QR |
| 🏊 **Bagnino** | 2 | Scansiona QR, registra ingressi, vede presenze giornaliere |
| 🏢 **Ufficio** | 3 | Approva documenti, conferma pagamenti, statistiche, report |
| 👑 **Admin** | 4 | Gestione completa: utenti, pacchetti, CMS, gallery, logs |

---

## 📁 Struttura Progetto

```
piscina-supabase/
├── index.html                 # Landing page pubblica
├── registrazione.html         # Form registrazione utente
├── login.html                 # Login tutti i ruoli
├── reset-password.html        # Reset password
│
├── utente/
│   ├── dashboard.html         # Dashboard utente
│   ├── profilo.html           # Modifica profilo
│   ├── documenti.html         # Upload documenti obbligatori
│   ├── pacchetti.html         # Acquisto pacchetti
│   ├── prenotazioni.html      # Prenota turni Lun/Mer/Ven
│   ├── qr-code.html           # Visualizza QR personale
│   └── storico.html           # Storico ingressi
│
├── bagnino/
│   ├── dashboard.html         # Dashboard bagnino
│   ├── scanner.html           # Scanner QR (html5-qrcode)
│   └── presenze-oggi.html     # Lista presenze giornaliere
│
├── ufficio/
│   ├── dashboard.html         # Dashboard ufficio
│   ├── pagamenti.html         # Conferma pagamenti in attesa
│   ├── documenti.html         # Revisione documenti utenti
│   ├── utenti.html            # Gestione utenti
│   ├── prenotazioni.html      # Gestione prenotazioni
│   ├── statistiche.html       # Report + grafici
│   ├── comunicazioni.html     # Invia comunicazioni
│   └── export.html            # Export Excel/PDF
│
├── admin/
│   ├── dashboard.html         # Dashboard admin completa
│   ├── pagamenti.html         # Gestione pagamenti
│   ├── documenti.html         # Revisione documenti
│   ├── utenti.html            # CRUD utenti completo
│   ├── staff.html             # Gestione staff (bagnini/ufficio)
│   ├── pacchetti.html         # Crea/modifica pacchetti
│   ├── prenotazioni.html      # Gestione prenotazioni
│   ├── statistiche.html       # Analytics complete
│   ├── comunicazioni.html     # Sistema comunicazioni
│   ├── cms.html               # Modifica contenuti sito
│   ├── gallery.html           # Upload/gestione immagini
│   ├── settings.html          # Configurazioni sistema
│   ├── reset-password-utente.html  # Reset password altri utenti
│   └── logs.html              # Activity log
│
├── css/
│   ├── style.css              # Stili generali
│   ├── auth.css               # Stili login/registrazione
│   └── dashboard.css          # Stili dashboard
│
├── js/
│   ├── supabase-client.js     # Configurazione Supabase
│   ├── auth.js                # Gestione autenticazione
│   ├── api.js                 # Funzioni API wrapper
│   ├── qr-scanner.js          # Logica scanner QR
│   ├── qr-generator.js        # Generazione QR code
│   ├── calendar.js            # Calendario prenotazioni
│   ├── export.js              # Export Excel/PDF
│   └── utils.js               # Utility functions
│
├── assets/
│   ├── logo.png
│   ├── icons/                 # PWA icons
│   └── templates/             # Template documenti PDF
│
├── supabase/
│   ├── migrations/
│   │   └── 001_initial_schema.sql   # Schema database completo
│   ├── seed.sql               # Dati iniziali
│   ├── functions/
│   │   ├── send-email/        # Edge Function email Brevo
│   │   └── generate-qr/       # Edge Function QR generation
│   └── config.toml            # Configurazione Supabase
│
├── manifest.json              # PWA manifest
├── service-worker.js          # Service worker offline
├── .env.example               # Variabili ambiente esempio
└── README.md                  # Questo file
```

---

## 🗄️ Schema Database Supabase

### Tabelle Principali

#### 1. **ruoli**
```sql
id UUID PRIMARY KEY
nome VARCHAR(50) UNIQUE         -- utente, bagnino, ufficio, admin
descrizione TEXT
livello INTEGER                 -- 1, 2, 3, 4
```

#### 2. **profili**
```sql
id UUID PRIMARY KEY (auth.users.id)
ruolo_id UUID → ruoli(id)
nome VARCHAR(255)
cognome VARCHAR(255)
telefono VARCHAR(20)
data_nascita DATE
indirizzo TEXT
citta VARCHAR(100)
cap VARCHAR(10)
codice_fiscale VARCHAR(16) UNIQUE
note TEXT
attivo BOOLEAN DEFAULT true
created_at TIMESTAMP
updated_at TIMESTAMP
```

#### 3. **tipi_documento**
```sql
id UUID PRIMARY KEY
nome VARCHAR(100) UNIQUE
descrizione TEXT
obbligatorio BOOLEAN DEFAULT false
template_url TEXT              -- URL template PDF
ordine INTEGER
```

**5 Documenti Obbligatori:**
1. Modulo Iscrizione
2. Certificato Medico (validità 1 anno)
3. Regolamento Interno
4. Privacy GDPR
5. Documento Identità

#### 4. **documenti_utente**
```sql
id UUID PRIMARY KEY
user_id UUID → profili(id)
tipo_documento_id UUID → tipi_documento(id)
file_url TEXT                  -- Supabase Storage URL
file_name VARCHAR(255)
stato VARCHAR(20)              -- da_inviare, in_attesa, approvato, rifiutato
note_revisione TEXT
data_caricamento TIMESTAMP
data_revisione TIMESTAMP
revisionato_da UUID → profili(id)
scadenza DATE                  -- per certificati medici
```

#### 5. **pacchetti**
```sql
id UUID PRIMARY KEY
nome VARCHAR(100)
descrizione TEXT
num_ingressi INTEGER
prezzo DECIMAL(10,2)
validita_giorni INTEGER
attivo BOOLEAN DEFAULT true
ordine INTEGER
created_at TIMESTAMP
```

#### 6. **acquisti**
```sql
id UUID PRIMARY KEY
user_id UUID → profili(id)
pacchetto_id UUID → pacchetti(id)
data_acquisto TIMESTAMP
metodo_pagamento VARCHAR(50)   -- bonifico, contanti, carta
stato_pagamento VARCHAR(20)    -- in_attesa, confermato, rifiutato
riferimento_pagamento TEXT
note_pagamento TEXT
qr_code VARCHAR(100) UNIQUE    -- Generato dopo conferma
ingressi_rimanenti INTEGER
data_scadenza DATE
confermato_da UUID → profili(id)
data_conferma TIMESTAMP
importo_pagato DECIMAL(10,2)
```

#### 7. **prenotazioni**
```sql
id UUID PRIMARY KEY
acquisto_id UUID → acquisti(id)
user_id UUID → profili(id)
data_turno DATE
giorno_settimana VARCHAR(10)   -- lunedi, mercoledi, venerdi
fascia_oraria VARCHAR(20)      -- mattina, pomeriggio, sera
orario_inizio TIME
orario_fine TIME
stato VARCHAR(20)              -- confermata, completata, cancellata, non_presentato
note TEXT
created_at TIMESTAMP
```

#### 8. **check_ins**
```sql
id UUID PRIMARY KEY
prenotazione_id UUID → prenotazioni(id)
acquisto_id UUID → acquisti(id)
user_id UUID → profili(id)
timestamp TIMESTAMP
bagnino_id UUID → profili(id)
fascia_oraria VARCHAR(20)      -- mattina, pomeriggio
note TEXT
```

#### 9. **comunicazioni**
```sql
id UUID PRIMARY KEY
titolo VARCHAR(255)
messaggio TEXT
tipo VARCHAR(20)               -- info, avviso, urgente, manutenzione
destinatari VARCHAR(20)        -- tutti, utenti, staff
priorita INTEGER               -- 1, 2, 3
pubblicata BOOLEAN DEFAULT false
data_inizio TIMESTAMP
data_fine TIMESTAMP
creata_da UUID → profili(id)
created_at TIMESTAMP
```

#### 10. **contenuti_sito** (CMS)
```sql
id UUID PRIMARY KEY
sezione VARCHAR(50)            -- home, chi_siamo, orari, contatti, gallery
chiave VARCHAR(100) UNIQUE
valore TEXT
tipo VARCHAR(20)               -- text, html, image, url
ordine INTEGER
updated_at TIMESTAMP
```

#### 11. **gallery**
```sql
id UUID PRIMARY KEY
titolo VARCHAR(255)
descrizione TEXT
image_url TEXT                 -- Supabase Storage URL
ordine INTEGER
visibile BOOLEAN DEFAULT true
created_at TIMESTAMP
```

#### 12. **activity_log**
```sql
id UUID PRIMARY KEY
user_id UUID → profili(id)
azione VARCHAR(100)
entita VARCHAR(50)             -- utente, documento, pagamento, prenotazione
entita_id UUID
dettagli JSONB
ip_address VARCHAR(50)
timestamp TIMESTAMP
```

---

## 🔐 Row Level Security (RLS)

Ogni tabella ha policy RLS per controllare accesso:

### Esempi Policy:

**profili - SELECT**
```sql
-- Utenti vedono solo proprio profilo
CREATE POLICY "utenti_proprio_profilo" ON profili
FOR SELECT USING (id = auth.uid() OR get_user_role_level() >= 2);

-- Staff vede tutti gli utenti
CREATE POLICY "staff_vede_tutti" ON profili
FOR SELECT USING (get_user_role_level() >= 2);
```

**documenti_utente - SELECT**
```sql
-- Utente vede solo propri documenti
CREATE POLICY "utente_propri_documenti" ON documenti_utente
FOR SELECT USING (user_id = auth.uid() OR get_user_role_level() >= 3);
```

**acquisti - UPDATE (conferma pagamento)**
```sql
-- Solo ufficio/admin può confermare
CREATE POLICY "ufficio_conferma_pagamenti" ON acquisti
FOR UPDATE USING (get_user_role_level() >= 3);
```

**check_ins - INSERT**
```sql
-- Solo bagnino/ufficio/admin può registrare
CREATE POLICY "bagnino_registra_ingressi" ON check_ins
FOR INSERT WITH CHECK (get_user_role_level() >= 2);
```

---

## 🚀 Setup Progetto

### 1. Installa Supabase CLI

```bash
npm install -g supabase
```

### 2. Inizializza Progetto Supabase

```bash
cd piscina-supabase
supabase init
```

### 3. Avvia Supabase Locale

```bash
supabase start
```

Output:
```
API URL: http://localhost:54321
Studio URL: http://localhost:54323
anon key: eyJhbG...
service_role key: eyJhbG...
```

### 4. Esegui Migration

```bash
supabase db reset  # Crea tabelle + RLS
```

### 5. Configura Frontend

Copia `.env.example` a `.env`:
```env
SUPABASE_URL=http://localhost:54321
SUPABASE_ANON_KEY=eyJhbG...
BREVO_API_KEY=your_brevo_key
```

### 6. Avvia Frontend

```bash
# Con Python
python -m http.server 8080

# Con Node.js
npx http-server -p 8080

# Con VSCode Live Server
# Right-click index.html → Open with Live Server
```

### 7. Accedi a:
- **Frontend**: http://localhost:8080
- **Supabase Studio**: http://localhost:54323

---

## 🔑 Credenziali Test

Dopo seed database:

| Ruolo | Email | Password |
|-------|-------|----------|
| **Admin** | admin@piscina.it | Admin123! |
| **Ufficio** | ufficio@piscina.it | Ufficio123! |
| **Bagnino** | bagnino@piscina.it | Bagnino123! |
| **Utente 1** | mario.rossi@email.it | User123! |
| **Utente 2** | laura.bianchi@email.it | User123! |

---

## 📊 Flussi Operativi

### Flusso Registrazione + Onboarding

1. Utente → Registra account (`registrazione.html`)
2. Supabase → Email conferma automatica
3. Utente → Conferma email + primo login
4. Sistema → Redirect `utente/documenti.html` (alert "Carica documenti")
5. Utente → Scarica template PDF + compila + upload
6. Sistema → Salva in Storage + notifica Ufficio via email
7. Ufficio → Revisiona documenti (`ufficio/documenti.html`)
8. Ufficio → Approva/Rifiuta con motivo
9. Sistema → Email notifica utente
10. Utente → Badge verde "Documenti OK" in dashboard

### Flusso Acquisto Pacchetto

1. Utente → Seleziona pacchetto (`utente/pacchetti.html`)
2. Utente → Form pagamento: metodo (bonifico/contanti/carta) + note
3. Sistema → Crea record `acquisti` (stato=in_attesa)
4. Sistema → Email istruzioni pagamento (IBAN + causale)
5. Sistema → Email ufficio "Nuovo acquisto in attesa"
6. Utente → Effettua pagamento reale (bonifico/cassa)
7. Ufficio → Verifica estratto conto (`ufficio/pagamenti.html`)
8. Ufficio → Click "Conferma Pagamento"
9. Sistema → Genera QR univoco (`PSC-{uuid}-{timestamp}`)
10. Sistema → Salva QR + aggiorna stato=confermato
11. Sistema → Email QR code + link prenotazioni
12. Utente → Vede QR in `utente/qr-code.html`

### Flusso Prenotazione + Check-in

1. Utente → Apre calendario (`utente/prenotazioni.html`)
2. Sistema → Mostra solo Lun/Mer/Ven
3. Utente → Seleziona data + fascia (mattina/pomeriggio)
4. Sistema → Conferma prenotazione + email
5. Sistema → Edge Function: reminder 24h prima
6. Utente → Arriva piscina con QR
7. Bagnino → Apre `bagnino/scanner.html`
8. Bagnino → Scansiona QR con camera
9. Sistema → Verifica:
   - QR valido + confermato
   - Ingressi rimanenti > 0
   - Scadenza >= oggi
   - NO check-in entro 4h stessa fascia
10. Sistema → Check-in OK:
    - Scala ingresso (`ingressi_rimanenti - 1`)
    - Crea record `check_ins`
    - Aggiorna prenotazione (completata)
    - Log activity
11. Bagnino → Feedback visivo: ✅ Nome + ingressi rimasti + vibrazione

### Logica Fascia Oraria

```javascript
function getFascia() {
  const ora = new Date().getHours();
  return ora < 14 ? 'mattina' : 'pomeriggio';
}

// Controllo doppio check-in
const quattroOreFA = new Date(Date.now() - 4 * 60 * 60 * 1000);
const ultimoCheckIn = await supabase
  .from('check_ins')
  .select('*')
  .eq('user_id', userId)
  .eq('fascia_oraria', getFascia())
  .gte('timestamp', quattroOreFA.toISOString())
  .single();

if (ultimoCheckIn.data) {
  throw new Error('Check-in già effettuato in questa fascia');
}
```

---

## 📧 Email Automatiche (Brevo)

Configurare Supabase Edge Function:

```typescript
// supabase/functions/send-email/index.ts
import { serve } from 'https://deno.land/std@0.177.0/http/server.ts'

serve(async (req) => {
  const { to, subject, htmlContent, templateId } = await req.json()
  
  const response = await fetch('https://api.brevo.com/v3/smtp/email', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'api-key': Deno.env.get('BREVO_API_KEY')!,
    },
    body: JSON.stringify({
      sender: { email: 'noreply@piscina.it', name: 'Piscina Comunale' },
      to: [{ email: to }],
      subject,
      htmlContent,
    }),
  })
  
  return new Response(JSON.stringify({ success: true }), {
    headers: { 'Content-Type': 'application/json' },
  })
})
```

Deploy:
```bash
supabase functions deploy send-email
```

---

## 🎨 Design System

### Palette Colori

```css
:root {
  --primary: #0EA5E9;      /* Sky Blue */
  --secondary: #06B6D4;    /* Cyan */
  --success: #10B981;      /* Green */
  --warning: #F59E0B;      /* Amber */
  --danger: #EF4444;       /* Red */
  --dark: #1E293B;         /* Slate 800 */
  --light: #F8FAFC;        /* Slate 50 */
}
```

### Componenti

**Badge Documenti:**
- 🔴 Da inviare / Rifiutato: `bg-danger`
- 🟠 In attesa: `bg-warning`
- 🟢 Approvato: `bg-success`

**Badge Pagamenti:**
- ⏳ In attesa: `bg-warning`
- ✅ Confermato: `bg-success`
- ❌ Rifiutato: `bg-danger`

---

## 🧪 Testing

### Test Checklist

- [ ] Registrazione → conferma email → login
- [ ] Upload documenti → approvazione ufficio → notifica
- [ ] Acquisto pacchetto → conferma ufficio → QR generato
- [ ] Prenotazione turno → conferma → reminder 24h
- [ ] Scansione QR → check-in → scala ingresso
- [ ] Doppio check-in stessa fascia → bloccato
- [ ] Doppio check-in fascia diversa → permesso
- [ ] Admin modifica CMS → frontend aggiornato
- [ ] Admin upload gallery → immagine visibile
- [ ] Export Excel/PDF → dati corretti
- [ ] PWA installazione → funziona offline
- [ ] RLS: utente NON vede altri utenti
- [ ] RLS: bagnino NON accede pagamenti

---

## 📦 Deployment Production

### Supabase Cloud

1. Crea progetto su [supabase.com](https://supabase.com)
2. Esegui migration:
```bash
supabase link --project-ref your-project-ref
supabase db push
```
3. Configura Storage buckets:
   - `documenti-utenti` (privato)
   - `gallery-images` (pubblico)
   - `documenti-template` (pubblico)
4. Deploy Edge Functions:
```bash
supabase functions deploy send-email
supabase functions deploy generate-qr
```

### Frontend (Netlify/Vercel)

1. Push su GitHub
2. Connetti repo a Netlify/Vercel
3. Configura variabili ambiente:
   - `SUPABASE_URL`
   - `SUPABASE_ANON_KEY`
4. Deploy automatico ✅

---

## 📚 Librerie Utilizzate

- **Supabase JS Client** v2 - Database + Auth
- **html5-qrcode** - Scanner QR camera
- **QRCode.js** - Generazione QR code
- **Chart.js** - Grafici statistiche
- **SheetJS (XLSX)** - Export Excel
- **jsPDF** - Export PDF
- **Brevo (Sendinblue)** - Email transazionali

---

## 🤝 Supporto

- **Email**: info@piscina.it
- **Telefono**: 123 456 789
- **Documentazione**: [Supabase Docs](https://supabase.com/docs)

---

© 2026 Sistema Gestione Piscina - Powered by Supabase
