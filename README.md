# Nuoto Libero - Progetto Finale (XAMPP)

Repository pulito e consolidato su stack PHP + MySQL/MariaDB per ambiente locale.

## Stack attivo
- Frontend statico (HTML/CSS/JS) in root
- API PHP in `api/`
- Dashboard area riservata operative in `piscina-php/`
- Database bootstrap completo in `db/CREATE_DATABASE_FROM_ZERO.sql`
- Invio email tramite PHPMailer (configurazione locale)

## Struttura principale
- `api/` endpoint backend PHP
- `assets/` file statici
- `config/` configurazioni locali (`mail.php`, `payments.php`)
- `css/` stylesheet
- `db/` script DB e guida
- `DOCUMENTAZIONE_E_CONFIG/` documentazione finale operativa
- `js/` script frontend
- `logs/` log locali (`mail.log`)
- `piscina-php/` login/dashboard per ruoli
- `vendor/` dipendenze Composer (PHPMailer)

## Setup rapido
1. Metti il progetto in `C:\xampp\htdocs\nuoto-libero`
2. Avvia Apache + MySQL su XAMPP
3. Importa `db/CREATE_DATABASE_FROM_ZERO.sql` in phpMyAdmin
4. Verifica `api/config.php` (host/user/pass/db)
5. Apri `http://localhost/nuoto-libero/login.html`

## Documentazione ufficiale del progetto
- `DOCUMENTAZIONE_E_CONFIG/ISTRUZIONI_SETUP_E_TEST.md`
- `DOCUMENTAZIONE_E_CONFIG/TEST_CREDENTIALS_LOCAL.txt`
- `DOCUMENTAZIONE_E_CONFIG/REPORT_AUDIT.md`
- `DOCUMENTAZIONE_E_CONFIG/CHANGELOG_FIXES.md`
- `DOCUMENTAZIONE_E_CONFIG/CONFIG_EMAIL.md`
- `DOCUMENTAZIONE_E_CONFIG/CONFIG_PAGAMENTI_STRIPE_PAYPAL.md`
- `db/README_DB.md`

## Nota
Solo ambiente locale/test. Non usare credenziali o chiavi di produzione.
