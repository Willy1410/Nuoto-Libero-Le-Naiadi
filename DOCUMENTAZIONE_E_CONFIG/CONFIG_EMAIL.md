# CONFIG EMAIL (SMTP locale)

## File coinvolti
- `config/mail.php`
- `api/config.php`
- `api/contact.php`
- `api/bonifico-notify.php`
- `logs/mail.log`

## 1) Configura `config/mail.php`
Imposta almeno:

```php
return [
    'enabled' => true,
    'from_email' => 'noreply@nuotolibero.local',
    'from_name' => 'Nuoto Libero (Test Locale)',
    'admin_email' => 'admin@nuotolibero.local',
    'admin_name' => 'Admin Nuoto Libero',
    'smtp' => [
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'username' => 'TUO_SMTP_USERNAME',
        'password' => 'TUO_SMTP_PASSWORD',
        'encryption' => 'tls',
        'auth' => true,
        'timeout' => 10,
    ],
];
```

## 2) Endpoint che usano la mail
- `POST /api/contact.php`
- `POST /api/bonifico-notify.php`

## 3) Come testare
1. Apri `contatti.html` e invia il form.
2. Apri `pacchetti.html`, scegli bonifico, invia notifica.
3. Controlla `logs/mail.log`.

## 4) Comportamento se SMTP non configurato
- Risposta API con messaggio esplicito (503 o 500 gestito)
- Evento scritto in `logs/mail.log`

## 5) Sicurezza minima locale
- Non inserire credenziali reali produzione.
- Non committare segreti reali.
- Usa account SMTP sandbox/test.
