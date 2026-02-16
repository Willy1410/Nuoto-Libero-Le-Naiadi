# 🏊 Sistema Gestione Piscina - PHP + MySQL

**Versione**: 1.0.0  
**Data Release**: 2026-02-15  
**Stack**: PHP 8.0+, MySQL 8.0+, Vanilla JavaScript, HTML5, CSS3

---

## 📦 CONTENUTO PROGETTO

Questo è un sistema completo per la gestione di una piscina con:

- ✅ **4 Ruoli** (Utente, Bagnino, Ufficio, Admin)
- ✅ **Autenticazione JWT**
- ✅ **Gestione Pacchetti** con QR Code
- ✅ **Check-in automatico** con verifica scadenza e duplicati
- ✅ **Upload e revisione documenti** (5 tipi obbligatori)
- ✅ **Sistema di pagamento** con conferma manuale
- ✅ **Dashboard separate** per ogni ruolo
- ✅ **Report e statistiche** in tempo reale
- ✅ **Export dati** (CSV/Excel)
- ✅ **Activity log** completo
- ✅ **Email automatiche** via Brevo (opzionale)

---

## 🚀 INSTALLAZIONE RAPIDA

### 1. Installa XAMPP

Scarica da: **https://www.apachefriends.org**

### 2. Avvia Servizi

Apri XAMPP Control Panel:
- Clicca **START** su Apache
- Clicca **START** su MySQL

### 3. Importa Database

1. Vai su: **http://localhost/phpmyadmin**
2. Tab **SQL**
3. Copia tutto il contenuto di `database/IMPORTA_QUESTO_DB.sql`
4. Incolla e clicca **Esegui**

### 4. Copia Progetto

Copia questa cartella `piscina-php/` dentro:

- **Windows**: `C:\xampp\htdocs\`
- **Mac**: `/Applications/XAMPP/htdocs/`
- **Linux**: `/opt/lampp/htdocs/`

### 5. Crea Cartella Upload

Dentro `piscina-php/` crea:
```
uploads/
├── documenti/
├── gallery/
└── temp/
```

### 6. Testa

Vai su: **http://localhost/piscina-php/login.html**

**Credenziali test**: Vedi sotto ⬇️

---

## 🔑 CREDENZIALI TEST

Tutte usano password: **`password123`**

| Ruolo | Email | Dashboard |
|-------|-------|-----------|
| **Admin** | admin@piscina.it | dashboard-admin.html |
| **Ufficio** | ufficio@piscina.it | dashboard-ufficio.html |
| **Bagnino** | bagnino@piscina.it | dashboard-bagnino.html |
| **Utente** | mario.rossi@email.it | dashboard-utente.html |

---

## 📂 STRUTTURA FILE

```
piscina-php/
│
├── api/                          # Backend PHP
│   ├── config.php               # Configurazione DB + JWT
│   ├── auth.php                 # Login, registrazione
│   ├── pacchetti.php            # Gestione pacchetti
│   ├── checkin.php              # Check-in QR
│   ├── documenti.php            # Upload documenti
│   └── stats.php                # Statistiche e export
│
├── database/
│   └── IMPORTA_QUESTO_DB.sql    # Schema MySQL completo
│
├── uploads/                      # File utente (creare manualmente)
│   ├── documenti/
│   ├── gallery/
│   └── temp/
│
├── login.html                    # Pagina login
├── dashboard-admin.html          # Dashboard amministratore
├── dashboard-ufficio.html        # Dashboard ufficio
├── dashboard-bagnino.html        # Dashboard bagnino
├── dashboard-utente.html         # Dashboard utente
│
├── .htaccess                     # Configurazione Apache
├── MANUALE_INSTALLAZIONE_COMPLETO.md  # Guida dettagliata
└── README.md                     # Questo file
```

---

## 🌐 API DISPONIBILI

Base URL: `http://localhost/piscina-php/api/`

### Autenticazione

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| POST | `/auth.php?action=register` | Registra nuovo utente |
| POST | `/auth.php?action=login` | Login |
| GET | `/auth.php?action=me` | Dati utente corrente (JWT) |
| POST | `/auth.php?action=change-password` | Cambio password |

### Pacchetti

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| GET | `/pacchetti.php` | Lista pacchetti disponibili |
| POST | `/pacchetti.php` | Acquista pacchetto |
| GET | `/pacchetti.php?action=my-purchases` | I miei acquisti |
| PATCH | `/pacchetti.php?action=confirm&id=xxx` | Conferma pagamento (ufficio) |

### Check-in

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| GET | `/checkin.php?qr=xxx` | Verifica QR code (bagnino) |
| POST | `/checkin.php` | Registra check-in (bagnino) |
| GET | `/checkin.php?action=history` | Storico check-in utente |
| GET | `/checkin.php?action=today` | Check-in di oggi (bagnino) |

### Documenti

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| GET | `/documenti.php` | I miei documenti |
| POST | `/documenti.php` | Upload documento |
| GET | `/documenti.php?action=pending` | Documenti da revisionare (ufficio) |
| PATCH | `/documenti.php?action=review&id=xxx` | Approva/rifiuta documento (ufficio) |
| GET | `/documenti.php?action=types` | Tipi documento obbligatori |

### Statistiche

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| GET | `/stats.php?action=dashboard` | Dashboard generale (admin) |
| GET | `/stats.php?action=report-daily&data=2026-02-15` | Report giornaliero |
| GET | `/stats.php?action=export-users` | Export utenti CSV |

---

## ⚙️ CONFIGURAZIONE

### Database

Modifica `api/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Lascia vuoto per XAMPP default
define('DB_NAME', 'piscina_gestione');
```

### JWT Secret

**IMPORTANTE**: Cambia la chiave segreta in `config.php`:

```php
define('JWT_SECRET', 'TUA_CHIAVE_SUPER_SEGRETA_12345');
```

### Email (Opzionale)

Per abilitare l'invio email, registrati su **Brevo** (https://www.brevo.com) e modifica:

```php
define('BREVO_API_KEY', 'xkeysib-TUA_CHIAVE');
define('BREVO_FROM_EMAIL', 'noreply@tuodominio.it');
```

---

## 📊 FUNZIONALITÀ PER RUOLO

### 👤 Utente
- Visualizza ingressi rimanenti
- Scarica QR code
- Acquista pacchetti
- Upload documenti obbligatori
- Storico check-in

### 🏊 Bagnino
- Scansiona QR code
- Registra check-in
- Visualizza presenze giornaliere
- Verifica validità pacchetti

### 📋 Ufficio
- Conferma pagamenti
- Revisiona documenti
- Report giornalieri
- Gestione acquisti pending

### ⚡ Admin
- Tutte le funzioni precedenti
- Gestione utenti
- Statistiche complete
- Export dati (CSV/Excel)
- Activity log

---

## 🔐 SICUREZZA

- ✅ Password hashate con bcrypt
- ✅ Autenticazione JWT
- ✅ Validazione input server-side
- ✅ Protezione CORS
- ✅ File .htaccess per blocco cartelle
- ✅ Activity log per audit
- ✅ Upload limitati a 5MB (PDF, JPG, PNG)

---

## 🐛 PROBLEMI COMUNI

### "Connection refused"

**Soluzione**: Verifica che MySQL sia avviato in XAMPP.

### "Access denied for user 'root'"

**Soluzione**: Controlla password in `config.php` (default XAMPP: vuota).

### "Table doesn't exist"

**Soluzione**: Reimporta `IMPORTA_QUESTO_DB.sql` in phpMyAdmin.

### Upload file non funziona

**Soluzione Windows**: Tasto destro su `uploads/` → Proprietà → Sicurezza → Permessi scrittura.

**Soluzione Mac/Linux**:
```bash
chmod -R 755 uploads/
```

---

## 📚 DOCUMENTAZIONE COMPLETA

Leggi il **MANUALE_INSTALLAZIONE_COMPLETO.md** per:

- Guide passo-passo con screenshot
- Esempi di chiamate API
- Troubleshooting avanzato
- Deployment in produzione
- Best practices sicurezza

---

## 📞 SUPPORTO

- **Email**: info@piscina.it
- **Telefono**: 123 456 789

---

## 🎉 PROSSIMI PASSI

1. ✅ Testa tutte le funzionalità
2. ✅ Cambia password di default
3. ✅ Personalizza logo e colori
4. ✅ Configura email Brevo
5. ✅ Aggiungi pacchetti personalizzati
6. ✅ Backup database settimanale
7. ✅ Deploy in produzione

---

## 📜 LICENZA

© 2026 Sistema Gestione Piscina - Tutti i diritti riservati.

---

**✨ Progetto pronto all'uso! Buona gestione!**
