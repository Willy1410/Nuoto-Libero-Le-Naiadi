# 🚀 GUIDA RAPIDA - Inizia in 5 Minuti

## Benvenuto nel sito Gli Squaletti! 

Questa guida ti porterà dal download al sito online in 5 minuti.

---

## ⚡ Start Rapido - 3 Passi

### 1️⃣ CONFIGURA (2 minuti)

#### A. PayPal
Apri `pacchetti.html`, trova la linea 26:
```html
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=EUR"></script>
```
Sostituisci `YOUR_PAYPAL_CLIENT_ID` con il tuo Client ID.

📍 **Dove trovarlo:** [PayPal Developer](https://developer.paypal.com) > Apps & Credentials

#### B. Stripe  
Apri `js/payment.js`, trova la linea 12:
```javascript
publishableKey: 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY_HERE',
```
Sostituisci con la tua Publishable Key.

📍 **Dove trovarlo:** [Stripe Dashboard](https://dashboard.stripe.com) > Developers > API keys

### 2️⃣ CARICA PDF (1 minuto)

Crea la cartella `assets/` e aggiungi questi PDF:
- `modulo-iscrizione.pdf`
- `regolamento-piscina.pdf`
- `informativa-privacy.pdf`
- `certificato-medico-info.pdf`
- `liberatoria-minori.pdf`
- `listino-prezzi.pdf`

💡 **Tip:** Usa Google Docs o Word per crearli, poi "Salva come PDF"

### 3️⃣ DEPLOY (2 minuti)

**Opzione più facile - Netlify:**
1. Vai su [netlify.com](https://netlify.com)
2. Drag & drop la cartella del progetto
3. Fatto! Sito online 🎉

**Altre opzioni:**
- **Vercel:** `npx vercel` da terminale
- **GitHub Pages:** Push su GitHub > Settings > Pages
- **FTP:** Carica via FileZilla sul tuo hosting

---

## 📋 Checklist Pre-Deploy

Prima di mettere online:

- [ ] ✅ Client ID PayPal configurato
- [ ] ✅ Publishable Key Stripe configurata  
- [ ] ✅ PDF caricati nella cartella assets/
- [ ] ✅ Personalizzato almeno: nome, email, telefono nel footer
- [ ] ✅ Testato su mobile (apri da smartphone)

---

## 🎨 Personalizzazioni Rapide

### Cambiare Colori
Apri `css/style.css`, cerca `:root` (linea 10) e modifica:
```css
--primary-color: #00a8e8;  /* Il tuo colore principale */
```

### Cambiare Prezzi
Apri `pacchetti.html` e `orari-tariffe.html`, cerca le cifre e modificale:
```html
<span class="amount">65</span>  <!-- Nuovo prezzo -->
```
Aggiorna anche `data-price="65.00"` nello stesso elemento.

### Cambiare Orari
Apri `orari-tariffe.html`, cerca la tabella e modifica le celle:
```html
<td>07:00 - 13:00</td>  <!-- Nuovo orario -->
```

---

## 🆘 Risoluzione Problemi Veloci

### Problema: "I pagamenti non funzionano"
✅ **Soluzione:** Hai configurato le chiavi API? Controlla passo 1️⃣

### Problema: "PDF non si scaricano"
✅ **Soluzione:** I file sono nella cartella `assets/` con i nomi esatti?

### Problema: "Menu mobile non si apre"
✅ **Soluzione:** Apri console browser (F12), cerca errori JavaScript

### Problema: "Sito non si vede bene su mobile"
✅ **Soluzione:** Svuota cache browser (Ctrl+Shift+R) e ricarica

---

## 📚 Documentazione Completa

Per guide dettagliate, consulta:
- **README.md** - Documentazione completa (17 KB)
- **PROGETTO_COMPLETATO.md** - Riepilogo finale
- **assets/README_ASSETS.txt** - Guida PDF

---

## 🎯 Test Finale Prima del Lancio

Apri il sito e verifica:

1. ✅ **Home** - Hero si vede bene? CTA funzionano?
2. ✅ **Menu** - Su mobile si apre l'hamburger?
3. ✅ **Galleria** - Lightbox funziona? Filtri ok?
4. ✅ **Pacchetti** - Puoi selezionare e vedere checkout?
5. ✅ **Contatti** - Form si invia? Mappa visibile?
6. ✅ **Footer** - Email e telefono corretti?

---

## 🎉 Sei Pronto!

Se hai completato i 3 passi sopra e la checklist, **il tuo sito è pronto per il lancio!**

### 📞 Hai Bisogno di Aiuto?

Consulta la documentazione completa nel README.md o contatta il supporto.

### 🌟 Prossimi Passi

Dopo il lancio:
1. Configura Google Analytics (opzionale)
2. Monitora le conversioni
3. Raccogli feedback utenti
4. Aggiungi nuove funzionalità dalla roadmap

---

**Buon lancio! 🚀🏊‍♂️**

*Gli Squaletti - Powered by IA*
