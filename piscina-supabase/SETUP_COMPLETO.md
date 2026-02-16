# 🚀 GUIDA SETUP COMPLETO - Sistema Gestione Piscina

## ✅ STATO ATTUALE IMPLEMENTAZIONE

### Completato ✅

1. **README.md completo** con architettura sistema
2. **Schema Database Supabase** (`supabase/migrations/001_initial_schema.sql`)
   - 12 tabelle con relazioni complete
   - Row Level Security (RLS) configurato
   - Trigger automatici (profilo, updated_at)
   - Funzioni utility (role_level, documenti_completi, genera_qr)
   - Storage buckets configurati
3. **Seed Data** (`supabase/seed.sql`)
   - 4 ruoli predefiniti
   - 7 utenti test con dati completi
   - Pacchetti, acquisti, prenotazioni sample
   - Documenti con stati diversi per testing
   - Comunicazioni e gallery popolate
4. **Supabase Client JS** (`js/supabase-client.js`)
   - Configurazione client
   - Utility auth (login, logout, requireAuth)
   - Utility formatters (date, badge, errors)
   - Check documenti completi
   - Controllo doppio check-in
   - Activity logging
5. **Pagina Login** (`login.html`)
   - Login + Registrazione in tabs
   - Integrazione Supabase Auth
   - Redirect dinamico per ruolo
   - Validazione frontend

---

## 📋 DA COMPLETARE

### 🔴 PRIORITÀ ALTA

#### 1. **Pagine Pubbliche Mancanti**
- [ ] `index.html` - Landing page con hero + FAQ
- [ ] `reset-password.html` - Form reset password
- [ ] Pagine legali (privacy, cookie, termini)

#### 2. **Dashboard Utente** (`utente/`)
- [ ] `dashboard.html` - Overview ingressi + QR + comunicazioni
- [ ] `profilo.html` - Modifica dati personali
- [ ] `documenti.html` - Upload 5 documenti obbligatori
- [ ] `pacchetti.html` - Acquisto pacchetti con form pagamento
- [ ] `prenotazioni.html` - Calendario Lun/Mer/Ven + prenota turno
- [ ] `qr-code.html` - Visualizza/scarica QR personale
- [ ] `storico.html` - Storico check-ins

#### 3. **Dashboard Bagnino** (`bagnino/`)
- [ ] `dashboard.html` - Presenze giornaliere + statistiche base
- [ ] `scanner.html` - Scanner QR con html5-qrcode + check-in
- [ ] `presenze-oggi.html` - Lista presenze filtrate per fascia

#### 4. **Dashboard Ufficio** (`ufficio/`)
- [ ] `dashboard.html` - Statistiche + pagamenti/documenti pending
- [ ] `pagamenti.html` - Lista acquisti in_attesa + conferma
- [ ] `documenti.html` - Revisione documenti (approva/rifiuta)
- [ ] `utenti.html` - Lista utenti con filtri + cerca
- [ ] `prenotazioni.html` - Gestione prenotazioni (cancella, modifica)
- [ ] `statistiche.html` - Grafici Chart.js (presenze, incassi)
- [ ] `comunicazioni.html` - Crea/invia comunicazioni
- [ ] `export.html` - Export Excel/PDF con filtri

#### 5. **Dashboard Admin** (`admin/`)
- [ ] `dashboard.html` - Analytics complete + alert sistema
- [ ] `utenti.html` - CRUD utenti completo
- [ ] `staff.html` - Gestione bagnini/ufficio
- [ ] `pacchetti.html` - Crea/modifica pacchetti
- [ ] `cms.html` - Modifica contenuti sito (tabella contenuti_sito)
- [ ] `gallery.html` - Upload immagini drag & drop + riordino
- [ ] `settings.html` - Configurazioni sistema
- [ ] `reset-password-utente.html` - Reset password altri utenti
- [ ] `logs.html` - Activity log con filtri

---

### 🟡 PRIORITÀ MEDIA

#### 6. **Sistema Prenotazioni Completo**
- [ ] Componente calendario JavaScript (solo Lun/Mer/Ven)
- [ ] Logica disponibilità slot orari
- [ ] Conferma prenotazione + email
- [ ] Reminder 24h (Supabase Edge Function)

#### 7. **Sistema Comunicazioni**
- [ ] Pagina comunicazioni pubbliche (per utenti)
- [ ] Badge notifiche non lette
- [ ] Email massiva (opzionale con Brevo)

#### 8. **Export Dati**
- [ ] Libreria SheetJS per Excel
- [ ] Libreria jsPDF per PDF
- [ ] Filtri avanzati (data, utente, pacchetto)

---

### 🟢 PRIORITÀ BASSA

#### 9. **PWA (Progressive Web App)**
- [ ] `manifest.json` - Manifest app
- [ ] `service-worker.js` - Cache statica + runtime
- [ ] Icone PWA (72x72, 192x192, 512x512)
- [ ] Installazione prompt

#### 10. **Supabase Edge Functions**
- [ ] `send-email` - Invio email via Brevo
- [ ] `generate-qr` - Generazione QR code server-side
- [ ] `send-reminder` - Reminder prenotazioni 24h

#### 11. **CSS Styling**
- [ ] `css/style.css` - Stili generali (palette, typography, grid)
- [ ] `css/auth.css` - Stili pagine login/registrazione
- [ ] `css/dashboard.css` - Stili dashboard responsive

---

## 🛠️ COME CONTINUARE

### Opzione 1: Implementazione Manuale

Segui questo ordine:

1. **Setup Supabase Locale**
   ```bash
   cd piscina-supabase
   supabase init
   supabase start
   supabase db reset  # Esegue migration + seed
   ```

2. **Aggiorna `js/supabase-client.js`**
   - Sostituisci `SUPABASE_URL` con output `supabase start`
   - Sostituisci `SUPABASE_ANON_KEY` con anon key generata

3. **Crea Dashboard Utente** (esempio: `utente/dashboard.html`)
   ```html
   <!DOCTYPE html>
   <html lang="it">
   <head>
     <meta charset="UTF-8">
     <title>Dashboard Utente</title>
     <link rel="stylesheet" href="/css/style.css">
     <link rel="stylesheet" href="/css/dashboard.css">
   </head>
   <body>
     <div class="dashboard-container">
       <aside class="sidebar">
         <!-- Menu navigazione -->
       </aside>
       <main class="main-content">
         <!-- Contenuto dashboard -->
       </main>
     </div>
     <script type="module">
       import { supabase, requireAuth, ROLES } from '/js/supabase-client.js'
       
       // Proteggi pagina
       const user = await requireAuth(ROLES.UTENTE)
       if (!user) return
       
       // Fetch dati utente
       const { data, error } = await supabase
         .from('acquisti')
         .select('*, pacchetto:pacchetti(*)')
         .eq('user_id', user.id)
         .eq('stato_pagamento', 'confermato')
         .order('data_acquisto', { ascending: false })
       
       // Renderizza dati
       // ...
     </script>
   </body>
   </html>
   ```

4. **Implementa Scanner QR** (`bagnino/scanner.html`)
   - Usa libreria `html5-qrcode` da CDN
   - Scansiona QR → estrai user_id
   - Verifica permessi + ingressi rimanenti
   - Registra check-in con `checkDoppioCheckIn()`

5. **Implementa Upload Documenti** (`utente/documenti.html`)
   - Form con select tipo_documento + file input
   - Upload a Supabase Storage bucket `documenti-utenti/{user_id}/{file}`
   - Insert record in `documenti_utente` (stato=in_attesa)

6. **Implementa Approvazione Documenti** (`ufficio/documenti.html`)
   - Fetch documenti WHERE stato=in_attesa
   - Preview documento (Supabase Storage URL firmato)
   - Bottoni Approva/Rifiuta → UPDATE stato + note_revisione

7. **Implementa Conferma Pagamenti** (`ufficio/pagamenti.html`)
   - Fetch acquisti WHERE stato_pagamento=in_attesa
   - Conferma → genera QR + UPDATE stato=confermato + invia email

8. **Implementa CMS** (`admin/cms.html`)
   - Fetch `contenuti_sito` GROUP BY sezione
   - Form dinamico per ogni chiave
   - UPDATE valore on save

9. **Implementa Gallery** (`admin/gallery.html`)
   - Upload immagine a Storage bucket `gallery-images`
   - Insert record in `gallery`
   - Drag & drop per riordino → UPDATE ordine

10. **Implementa Prenotazioni** (`utente/prenotazioni.html`)
    - Calendario JavaScript custom (o libreria Flatpickr)
    - Filter giorni: solo Lun/Mer/Ven
    - Insert `prenotazioni` → email conferma

11. **Implementa Export Excel** (`ufficio/export.html`)
    ```javascript
    import * as XLSX from 'https://cdn.sheetjs.com/xlsx-latest/package/xlsx.mjs'
    
    // Fetch dati
    const { data } = await supabase.from('acquisti').select('*')
    
    // Crea workbook
    const ws = XLSX.utils.json_to_sheet(data)
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Acquisti')
    
    // Download
    XLSX.writeFile(wb, 'export-acquisti.xlsx')
    ```

12. **Implementa PWA**
    - Crea `manifest.json`
    - Crea `service-worker.js` con Workbox
    - Aggiungi `<link rel="manifest">` in tutte le pagine

---

### Opzione 2: Generazione Automatica

Chiedi all'AI di generare una pagina alla volta:

**Esempio prompt:**
> "Crea la dashboard utente completa (`utente/dashboard.html`) con:
> - Sidebar navigazione
> - Card ingressi rimanenti + scadenza
> - Badge stato documenti
> - QR code visibile
> - Lista comunicazioni recenti
> - Integrazione Supabase per fetch dati"

Ripeti per ogni pagina mancante.

---

## 📦 LIBRERIE DA INCLUDERE (CDN)

### Frontend Essenziali

```html
<!-- Supabase JS -->
<script type="module">
  import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
</script>

<!-- QR Code Generator -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

<!-- QR Code Scanner -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- SheetJS (Excel) -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/xlsx.mjs" type="module"></script>

<!-- jsPDF (PDF) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Flatpickr (Calendario) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/it.js"></script>
```

---

## 🧪 TESTING

### Test Checklist

1. **Registrazione**
   - [ ] Crea account → email conferma → login
2. **Upload Documenti**
   - [ ] Utente carica 5 documenti → stato in_attesa
3. **Approvazione Documenti**
   - [ ] Ufficio approva documenti → badge verde utente
4. **Acquisto Pacchetto**
   - [ ] Utente acquista → in_attesa → ufficio conferma → QR generato
5. **Prenotazione**
   - [ ] Utente prenota Lun mattina → conferma email
6. **Scanner QR**
   - [ ] Bagnino scansiona QR → check-in → scala ingresso
7. **Doppio Check-in**
   - [ ] Scansiona stesso QR entro 4h stessa fascia → bloccato
8. **Fascia Diversa**
   - [ ] Scansiona stesso QR pomeriggio → permesso
9. **RLS**
   - [ ] Utente NON vede altri utenti
   - [ ] Bagnino NON accede pagamenti
   - [ ] Admin vede tutto
10. **CMS**
    - [ ] Admin modifica testo → frontend aggiornato
11. **Gallery**
    - [ ] Admin upload immagine → visibile in gallery
12. **Export**
    - [ ] Ufficio export Excel → file scaricato con dati
13. **PWA**
    - [ ] Installabile Android → funziona offline

---

## 🚀 DEPLOYMENT PRODUCTION

### 1. Supabase Cloud

1. Crea progetto su [supabase.com](https://supabase.com)
2. Link progetto locale:
   ```bash
   supabase link --project-ref your-project-ref
   ```
3. Push migration:
   ```bash
   supabase db push
   ```
4. Esegui seed manualmente da Supabase Studio SQL Editor
5. Configura Storage buckets:
   - `documenti-utenti` (privato)
   - `gallery-images` (pubblico)
   - `documenti-template` (pubblico)

### 2. Frontend (Netlify/Vercel)

1. Push progetto su GitHub
2. Connetti repo a Netlify/Vercel
3. Configura build settings:
   - Build command: (nessuno, static site)
   - Publish directory: `/`
4. Aggiungi variabili ambiente:
   ```
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_ANON_KEY=eyJ...
   ```
5. Deploy! ✅

### 3. Edge Functions

Deploy email sender:
```bash
supabase functions deploy send-email --no-verify-jwt
supabase secrets set BREVO_API_KEY=your_brevo_key
```

---

## 📚 DOCUMENTAZIONE UTILE

- [Supabase Docs](https://supabase.com/docs)
- [Supabase RLS Guides](https://supabase.com/docs/guides/auth/row-level-security)
- [html5-qrcode](https://github.com/mebjas/html5-qrcode)
- [Chart.js](https://www.chartjs.org/docs/latest/)
- [SheetJS](https://docs.sheetjs.com/)
- [jsPDF](https://github.com/parallax/jsPDF)

---

## ❓ DOMANDE FREQUENTI

### Come creo utenti test?

**Opzione 1:** Via Supabase Studio
- Vai su Authentication → Users → Add User
- Email + password + conferma email automaticamente

**Opzione 2:** Via SQL (solo locale)
```sql
INSERT INTO auth.users (id, email, encrypted_password, email_confirmed_at)
VALUES (
  uuid_generate_v4(),
  'test@email.it',
  crypt('Password123!', gen_salt('bf')),
  NOW()
);
```

### Come testo il scanner QR senza telefono?

Usa QR code online generator:
- Vai su [qr-code-generator.com](https://www.qr-code-generator.com/)
- Genera QR con testo: `http://localhost:8080/bagnino/scanner.html?qr=PSC-mario-001-1234567890`
- Scansiona da webcam

### Come gestisco email in locale?

Supabase locale usa [Inbucket](http://localhost:54324) per catturare email.
Apri http://localhost:54324 per vedere email inviate.

---

## ✅ CHECKLIST FINALE

### Prima di Deploy:

- [ ] Tutti i test superati
- [ ] RLS verificato (utenti isolati)
- [ ] SUPABASE_URL e ANON_KEY production configurati
- [ ] Storage buckets creati e policy RLS attive
- [ ] Edge Functions deploy con secrets configurati
- [ ] PWA manifest e service worker testati
- [ ] Performance: immagini ottimizzate, CSS minificato
- [ ] SEO: meta tags, sitemap.xml, robots.txt
- [ ] Accessibilità: ARIA labels, contrasto colori
- [ ] Documentazione admin aggiornata

---

## 🎉 CONCLUSIONE

Hai ora:
- ✅ **Schema database completo** con RLS funzionante
- ✅ **Sistema auth** a 4 ruoli
- ✅ **Seed data** per testing immediato
- ✅ **Client Supabase** con utility pronte
- ✅ **Pagina login** funzionante

**Prossimo step:** Implementa le dashboard una alla volta seguendo la guida sopra.

Buon lavoro! 🚀
