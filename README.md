# Nuoto Libero Le Naiadi

Stack: frontend PHP/HTML/CSS/JS + API PHP + MySQL (XAMPP locale o hosting LAMP).

## Requisiti
- PHP 8.0+
- Estensioni PHP: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json`
- MySQL/MariaDB 10+
- Composer 2+
- Node.js (opzionale, solo per check sintassi JS)

## Struttura
- `api/` API backend
- `piscina-php/` dashboard ruoli
- `db/` bootstrap e migrazioni DB
- `config/` mapping CMS e config applicativa
- `scripts/` script operativi ripetibili
- `DOCUMENTAZIONE_E_CONFIG/` guide operative

## Setup rapido (locale)
1. `php scripts/install.php`
2. `php scripts/setup-env.php`
3. aggiorna `.env` con i tuoi valori locali
4. `php scripts/init-db.php`
5. `php scripts/dev.php`
6. apri `http://127.0.0.1:8080/login.php`

## Script operativi
- `php scripts/install.php`: installa dipendenze Composer
- `php scripts/setup-env.php`: crea `.env` partendo da `.env.example`
- `php scripts/init-db.php`: inizializza DB da `db/CREATE_DATABASE_FROM_ZERO.sql`
- `php scripts/dev.php`: avvia server PHP locale
- `php scripts/build-prod.php`: check sintassi PHP/JS pre deploy

## Modalita sito
Variabile `.env`:
- `SITE_MODE=full` -> sito completo
- `SITE_MODE=landing` -> homepage di emergenza

## Deploy produzione (sintesi)
1. carica repository su server
2. configura `.env` produzione (DB, JWT, SMTP, URL)
3. esegui `composer install --no-dev --optimize-autoloader`
4. importa DB/migrazioni necessarie
5. esegui `php scripts/build-prod.php`
6. punta il VirtualHost alla root progetto

## Documentazione utile
- `DOCUMENTAZIONE_E_CONFIG/ISTRUZIONI_SETUP_E_TEST.md`
- `DOCUMENTAZIONE_E_CONFIG/CONFIG_EMAIL.md`
- `DOCUMENTAZIONE_E_CONFIG/DIARIO_SESSIONI.md`
- `db/README_DB.md`
