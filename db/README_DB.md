# Setup Database Locale (XAMPP)

## 1) Apri phpMyAdmin
- URL tipico: `http://localhost/phpmyadmin`

## 2) Crea DB da zero
- Apri tab `SQL`
- Incolla il contenuto di `db/CREATE_DATABASE_FROM_ZERO.sql`
- Esegui

Lo script:
- crea il database `nuoto_libero`
- crea tabelle, indici e vincoli
- inserisce seed minimi e utenti test

## 3) Configura il progetto
File: `api/config.php`

Valori default locali:
- `DB_HOST=localhost`
- `DB_USER=root`
- `DB_PASS=` (vuota, default XAMPP)
- `DB_NAME=nuoto_libero`

## 4) Credenziali test incluse nel seed
- `admin@piscina.it` / `password123`
- `ufficio@piscina.it` / `password123`
- `bagnino@piscina.it` / `password123`
- `mario.rossi@email.it` / `password123`
- `laura.bianchi@email.it` / `password123`
- `giuseppe.verdi@email.it` / `password123`
- `anna.ferrari@email.it` / `password123`

## 5) Query rapida verifica
```sql
USE nuoto_libero;
SELECT email, ruolo_id, attivo FROM profili ORDER BY email;
SELECT stato_pagamento, COUNT(*) AS tot FROM acquisti GROUP BY stato_pagamento;
```
