# Setup Database Locale (XAMPP)

## 1) Apri phpMyAdmin
- URL: `http://localhost/phpmyadmin`

## 2) Crea DB da zero
1. Apri tab `SQL`
2. Incolla tutto il file `db/CREATE_DATABASE_FROM_ZERO.sql`
3. Esegui

Attenzione:
- Lo script contiene `DROP DATABASE IF EXISTS nuoto_libero;`
- Rieseguirlo resetta completamente i dati del DB locale

## 3) Cosa crea lo script
- database `nuoto_libero`
- tabelle, indici, vincoli
- seed minimi
- utenti test locali

## 4) Configura connessione progetto
File: `api/config.php`

Default locale:
- `DB_HOST=localhost`
- `DB_USER=root`
- `DB_PASS=` (vuota, tipico XAMPP)
- `DB_NAME=nuoto_libero`

Se usi credenziali diverse, aggiorna i valori.

## 5) Credenziali test seed
- `admin@piscina.it` / `password123`
- `ufficio@piscina.it` / `password123`
- `bagnino@piscina.it` / `password123`
- `mario.rossi@email.it` / `password123`
- `laura.bianchi@email.it` / `password123`
- `giuseppe.verdi@email.it` / `password123`
- `anna.ferrari@email.it` / `password123`

## 6) Verifica rapida SQL
```sql
USE nuoto_libero;
SELECT email, ruolo_id, attivo FROM profili ORDER BY email;
SELECT stato_pagamento, COUNT(*) AS tot FROM acquisti GROUP BY stato_pagamento;
```

## 7) Verifica applicativa
- Apri `http://localhost/nuoto-libero/login.html`
- Esegui login con utente test
- Verifica redirect dashboard per ruolo

## 8) Troubleshooting
1. Login API restituisce errore 500
- MySQL non avviato oppure DB non importato
- credenziali DB non coerenti in `api/config.php`

2. Tabelle mancanti
- riesegui interamente `db/CREATE_DATABASE_FROM_ZERO.sql`
