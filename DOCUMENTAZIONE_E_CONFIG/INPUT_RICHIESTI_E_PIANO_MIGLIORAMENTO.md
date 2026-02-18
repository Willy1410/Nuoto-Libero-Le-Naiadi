# INPUT RICHIESTI E PIANO MIGLIORAMENTO

Data: 2026-02-17
Progetto: Nuoto Libero

## 1) Dati che mi devi fornire (obbligatori)

### Branding e contenuti
- Nome societa ufficiale e forma legale (es. SSD, SRL, ecc.)
- Logo ufficiale in formato SVG/PNG alta qualita
- Colori brand ufficiali (HEX)
- Testi finali (hero, descrizioni pacchetti, contatti, policy)

### Contatti e operativita
- Email amministrativa principale
- Email segreteria/ufficio
- Numero WhatsApp/telefono ufficiale
- Indirizzo completo sede
- Giorni/orari reali di apertura

### Email SMTP (produzione)
- Provider SMTP (Gmail Workspace, SendGrid, Mailgun, Brevo, ecc.)
- Host SMTP
- Porta SMTP
- Username SMTP
- Password/App Password SMTP
- Indirizzo mittente (from_email)
- Nome mittente (from_name)

### Pagamenti
- Metodo reale da attivare (Stripe, PayPal, Bonifico, In struttura)
- Stripe: `publishable_key`, `secret_key`, `webhook_secret`
- PayPal: `client_id`, `client_secret`, `webhook_id`
- Bonifico: intestatario, IBAN, banca, causale

### Dati legali
- Privacy policy definitiva
- Cookie policy definitiva
- Termini e condizioni definitivi
- Eventuale partita IVA/codice fiscale societa

## 2) Libreria QR: opzioni consigliate

### Opzione A (ATTIVA ORA, consigliata): QR server-side locale
- Libreria: `tecnickcom/tcpdf` (gia installata nel progetto)
- Uso: genera PDF + SVG QR in `api/qr.php`
- Vantaggio: nessun costo ricorrente, pieno controllo locale

### Scansione QR (ATTIVA ORA, gratuita)
- Libreria: `html5-qrcode` (open source)
- Uso: scanner camera nella dashboard bagnino
- CDN primaria: `https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js`
- CDN fallback: `https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js`

### Opzione C (a pagamento, API esterna)
- Provider: QR Code Generator PRO API
- Pagamento: `https://www.qr-code-generator.com/pricing/`
- API docs: `https://www.qr-code-generator.com/qr-code-api/`
- Cosa devi darmi: account API, API key/token, piano scelto

## 3) CMS visivo: opzioni per autonomia tipo "Wix/WordPress"

### Opzione 0 (ATTIVA ORA, gratuita): CMS interno dashboard admin/ufficio
- Pagine: gestione contenuti da `dashboard-contenuti.html`
- Editor: `Quill` gratuito locale (`assets/vendor/quill/`)
- Vantaggio: nessun costo licenza, accesso immediato da admin e ufficio

### Opzione 1 (gratis/self-hosted): GrapesJS nel pannello admin
- Sito: `https://grapesjs.com/`
- Repo: `https://github.com/GrapesJS/grapesjs`
- Costo licenza base: gratuito (MIT)
- Cosa mi devi dare:
  - elenco pagine da rendere visual-editable
  - regole ruoli (admin/ufficio)
  - conferma se salvare su DB o su file

### Opzione 2 (a pagamento): GrapesJS Studio SDK
- Pagamento: `https://grapesjs.com/pricing`
- Cosa mi devi dare:
  - account/piano attivo
  - chiave/licenza SDK
  - eventuale dominio autorizzato

### Opzione 3 (a pagamento SaaS con visual editor): Storyblok / Builder.io / Sanity
- Storyblok pricing: `https://www.storyblok.com/pricing`
- Builder.io pricing: `https://www.builder.io/pricing`
- Sanity pricing: `https://www.sanity.io/pricing`
- Cosa mi devi dare:
  - account del provider scelto
  - API token (management + delivery)
  - Space/Project ID
  - webhook secret
  - ruoli utenti da creare (admin/ufficio)

## 4) Cose che il sito deve avere per essere "perfetto"

### Sicurezza
- HTTPS con certificato valido
- Password policy forte e rotazione periodica
- Backup giornaliero DB + file uploads
- Log centralizzato errori con retention
- Blocchi anti brute-force e rate limit API

### Affidabilita
- Ambiente staging separato da produzione
- Deploy con pipeline (CI/CD)
- Healthcheck endpoint e monitor uptime
- Rollback rapido versione precedente

### Qualita prodotto
- Test automatici API principali (login, acquisto, conferma, QR, check-in)
- Test UI mobile/tablet su device reali
- Performance check (LCP, CLS, TTFB)
- Accessibilita base (contrasti, focus, ARIA)

### Operativita business
- Dashboard con audit trail completo (chi ha confermato cosa e quando)
- Esportazioni CSV/Excel robuste
- Notifiche email affidabili con tracciamento invio
- Checklist operativa giornaliera per segreteria/bagnino

## 5) Decisioni che mi servono subito
- Quale opzione CMS visivo scegli (1, 2 o 3)
- Se il QR deve essere sempre rigenerato alla conferma manuale (attuale: SI)
- Se attiviamo notifiche email reali gia da ora o solo in staging
- Se vuoi mantenere il database seed di test o passare a dati puliti
