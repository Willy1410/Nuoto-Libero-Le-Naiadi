# DOSSIER COMPLETO GO-LIVE E TEST

Progetto: Nuoto Libero Le Naiadi  
Data: 2026-02-19  
Uso: manuale operativo unico (test + credenziali + link + checklist mancanze)

====================================================================
1) URL PRINCIPALI (LOCALE)
====================================================================
- Home (SITE_MODE=full): http://localhost/Nuoto-Libero-Le-Naiadi/
- Landing: http://localhost/Nuoto-Libero-Le-Naiadi/landing.php
- Landing alias: http://localhost/Nuoto-Libero-Le-Naiadi/landing
- Pagina grazie contatto landing: http://localhost/Nuoto-Libero-Le-Naiadi/grazie-contatto.php?from=landing
- Area riservata login: http://localhost/Nuoto-Libero-Le-Naiadi/login.php
- Area riservata clone (landing mode): http://localhost/Nuoto-Libero-Le-Naiadi/area-riservata.php

Dashboard ruoli:
- Admin: http://localhost/Nuoto-Libero-Le-Naiadi/piscina-php/dashboard-admin.php
- Ufficio: http://localhost/Nuoto-Libero-Le-Naiadi/piscina-php/dashboard-ufficio.php
- Bagnino: http://localhost/Nuoto-Libero-Le-Naiadi/piscina-php/dashboard-bagnino.php
- Utente: http://localhost/Nuoto-Libero-Le-Naiadi/piscina-php/dashboard-utente.php

====================================================================
2) CREDENZIALI TEST (SOLO LOCALE)
====================================================================
ATTENZIONE: usare solo in ambiente locale/test, mai in produzione.

ADMIN
- Email: admin@piscina.it
- Password: password123

UFFICIO
- Email: ufficio@piscina.it
- Password: password123

BAGNINO
- Email: bagnino@piscina.it
- Password: password123

UTENTI
- mario.rossi@email.it / password123
- laura.bianchi@email.it / password123
- giuseppe.verdi@email.it / password123
- anna.ferrari@email.it / password123

Reset password / primo accesso:
- Pagina reset token: http://localhost/Nuoto-Libero-Le-Naiadi/reset-password.php
- Da Admin/Ufficio e possibile inviare richiesta cambio password al cliente.
- Nuovo cliente creato da staff: riceve email con link per impostare la password.

====================================================================
3) CONFIGURAZIONE E FILE CHIAVE
====================================================================
- README: README.md
- Setup/Test: DOCUMENTAZIONE_E_CONFIG/ISTRUZIONI_SETUP_E_TEST.md
- Diario sessioni: DOCUMENTAZIONE_E_CONFIG/DIARIO_SESSIONI.md
- Checklist input business: DOCUMENTAZIONE_E_CONFIG/CHECKLIST_INPUT_UTENTE_GO_LIVE.md
- Manuale Aruba A4: DOCUMENTAZIONE_E_CONFIG/MANUALE_OPUSCOLO_GO_LIVE_ARUBA_WEBHOSTING_EASY_A4.html
- Variabili ambiente: .env / .env.example
- Schema DB: db/CREATE_DATABASE_FROM_ZERO.sql
- Migrazione scanner operativo: db/MIGRATION_2026_02_19_OPERATIONAL_SETTINGS.sql

====================================================================
4) CHECKLIST TEST COMPLETA (OPERATIVA)
====================================================================

A) Smoke iniziale
[ ] Home risponde 200
[ ] Landing risponde 200
[ ] Login risponde 200
[ ] Dashboard Admin/Ufficio/Bagnino/Utente raggiungibili post login

B) Login e ruoli
[ ] Login admin ok e redirect corretto
[ ] Login ufficio ok e redirect corretto
[ ] Login bagnino ok e redirect corretto
[ ] Login utente ok e redirect corretto
[ ] Utente non autorizzato bloccato su endpoint admin (403)

C) Dashboard utente
[ ] Visualizza stato account
[ ] Visualizza QR se acquisto confermato
[ ] Download PDF QR funzionante
[ ] Il mio profilo visualizza dati corretti
[ ] Invio richiesta modifica dati funzionante

D) Workflow approvazione modifica dati (Admin/Ufficio)
[ ] Richiesta compare in tab acquisti > richieste modifica dati
[ ] Azione Approva aggiorna profilo DB
[ ] Audit log registra evento approvazione/rifiuto

E) Dashboard Admin/Ufficio: dettaglio utente
[ ] “Apri”/“Vedi utente” funziona
[ ] Dati utente completi visibili
[ ] Invio promemoria documenti funziona
[ ] Invio richiesta cambio password funziona

F) Gestione scanner QR da Admin/Ufficio
[ ] Tab Impostazioni visibile
[ ] Toggle scanner abilitato/disabilitato funziona
[ ] Modalita H24 funziona
[ ] Finestre orarie salvabili (giorno/fasce)
[ ] Riepilogo orari aggiornato

G) Gestione SITE_MODE da Admin/Ufficio
[ ] Toggle landing/full visibile
[ ] Salvataggio richiede doppia conferma
[ ] SITE_MODE aggiornato in .env
[ ] Se landing: homepage su landing e area riservata clone
[ ] Se full: homepage sito completo

H) Dashboard bagnino
[ ] Avvio camera funzionante in HTTPS
[ ] QR letto da camera compila dati automaticamente
[ ] Dopo lettura QR camera si ferma
[ ] Schermata verifica compare senza click su “Verifica QR”
[ ] “Verifica QR manuale” funziona per fallback
[ ] Alert sempre visibili su desktop/mobile/tablet
[ ] Check-in registrato e contatori aggiornati
[ ] Fuori fascia oraria: blocco check-in con messaggio corretto

I) Landing page
[ ] CTA “Contattaci ora” apre WhatsApp su numero ufficiale
[ ] Email mostrata: info@nuotoliberolenaiadi.it
[ ] Orari nuoto libero visibili e corretti
[ ] Orari ufficio su appuntamento visibili
[ ] Form contatti invia correttamente
[ ] Oggetto tendina funziona
[ ] Se Oggetto=Altro campo condizionale obbligatorio
[ ] Redirect su pagina grazie dedicata
[ ] Bottone “TORNA ALLA HOME” funziona

J) Sicurezza / hardening
[ ] Nessun segreto hardcoded nel codice
[ ] Rate limit endpoint sensibili attivo
[ ] Route protette da ruolo
[ ] Upload/documenti protetti
[ ] Header sicurezza attivi (.htaccess)

K) Console e performance base
[ ] Nessun errore bloccante console su pagine principali
[ ] Nessun log debug residuo nei file principali toccati
[ ] Asset principali caricati senza 404

====================================================================
5) ENDPOINT API PIU IMPORTANTI (RIFERIMENTO)
====================================================================
Auth:
- POST /api/auth.php?action=login
- GET /api/auth.php?action=me
- POST /api/auth.php?action=forgot-password
- POST /api/auth.php?action=reset-password
- POST /api/auth.php?action=profile-update-request
- GET /api/auth.php?action=profile-update-requests

Admin/Ufficio:
- GET /api/admin.php?action=users
- GET /api/admin.php?action=user-detail&id=...
- POST /api/admin.php?action=create-user
- POST /api/admin.php?action=send-password-reset&id=...
- GET /api/admin.php?action=operational-settings
- PATCH /api/admin.php?action=save-operational-settings
- PATCH /api/admin.php?action=set-site-mode
- GET /api/admin.php?action=profile-update-requests
- PATCH /api/admin.php?action=review-profile-update-request&id=...

Check-in / QR:
- GET /api/checkin.php?qr=...
- POST /api/checkin.php
- GET /api/checkin.php?action=today
- GET /api/qr.php?action=svg&acquisto_id=...
- GET /api/qr.php?action=download&acquisto_id=...

Contatti:
- POST /api/contact.php

====================================================================
6) CHECKLIST "COSA MANCA" (PERFEZIONE TOTALE)
====================================================================

A) Mancanze tecniche residue (alta priorita)
[ ] Test manuale completo su dispositivi reali in HTTPS esterno (rete cellulare + Wi-Fi)
[ ] Verifica scanner QR in tutte le combinazioni orarie (off, fasce custom, H24)
[ ] Test end-to-end su Aruba staging con import DB reale di prova
[ ] Piano rollback cronometrato e provato (file + DB + .env)

B) Mancanze business/operativita
[ ] Conferma definitiva orari ufficio reali (ora fittizi)
[ ] Conferma numero WhatsApp ufficiale definitivo
[ ] Conferma testi finali istituzionali e legali
[ ] Consegna branding definitivo (logo/favicons/assets locali originali)

C) Mancanze deliverability email
[ ] SMTP produzione definitivo su Aruba
[ ] SPF configurato
[ ] DKIM configurato
[ ] DMARC configurato
[ ] Test invio/ricezione da form contatti e reset password con caselle reali

D) Mancanze qualità operativa
[ ] Checklist giornaliera segreteria/bagnino stampabile e in uso
[ ] Procedura backup giornaliero automatizzata e verificata
[ ] Verifica periodica log errori e rate limit

====================================================================
7) LINK ESTERNI UTILI (GO-LIVE)
====================================================================
- SSL Test: https://www.ssllabs.com/ssltest/
- DNS Checker: https://dnschecker.org/
- PageSpeed: https://pagespeed.web.dev/
- SPF Check: https://mxtoolbox.com/spf.aspx
- DKIM Check: https://mxtoolbox.com/dkim.aspx
- DMARC Check: https://mxtoolbox.com/dmarc.aspx

====================================================================
8) NOTE FINALI
====================================================================
- Questo dossier e pensato per uso operativo interno.
- Le password riportate qui sono solo di test locale.
- Prima di pubblicazione: cambiare tutte le credenziali e validare .env produzione.

