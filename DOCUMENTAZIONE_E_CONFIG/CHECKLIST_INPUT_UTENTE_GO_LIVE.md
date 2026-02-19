# CHECKLIST INPUT UTENTE GO-LIVE (SENZA PAGAMENTI ONLINE)

Data creazione: 2026-02-19
Stato progetto: pagamenti online rimossi, finalizzazione pratiche solo in struttura.

Riferimenti utili interni:
- README: README.md
- Setup/Test: DOCUMENTAZIONE_E_CONFIG/ISTRUZIONI_SETUP_E_TEST.md
- Config email: DOCUMENTAZIONE_E_CONFIG/CONFIG_EMAIL.md
- Variabili ambiente: .env.example

====================================================================
1) DATI LEGALI E BRAND (OBBLIGATORI)
====================================================================
[ ] Ragione sociale completa
[ ] Forma legale (SSD/SRL/ASD/...)
[ ] Partita IVA e/o Codice Fiscale
[ ] Nome pubblico da mostrare sul sito
[ ] Logo ufficiale (SVG + PNG trasparente)
[ ] Favicon ufficiale (almeno 32x32 e 180x180)
[ ] Palette colori brand (HEX)
[ ] Font brand (se diversi da quelli attuali)
[ ] Eventuale claim/sottotitolo ufficiale

Nota:
[ ] Fornire file immagine originali da hostare localmente (ora il sito usa URL esterni GensparkSpace).

====================================================================
2) CONTATTI E OPERATIVITA
====================================================================
[ ] Email amministrativa principale
[ ] Email segreteria
[ ] Email privacy (se distinta)
[ ] Email no-reply (se prevista)
[ ] Numero telefono ufficiale
[ ] Numero WhatsApp ufficiale (se usato)
[ ] Indirizzo completo sede (via, civico, CAP, citta, provincia)
[ ] Giorni e orari reali (feriali/festivi)
[ ] Eventuale contatto emergenze

====================================================================
3) ACCESSI TECNICI PRODUZIONE
====================================================================
[ ] Dominio principale produzione
[ ] Eventuale dominio/subdominio staging
[ ] Accesso hosting (SSH/SFTP/cPanel/Plesk)
[ ] Cartella webroot di deploy confermata
[ ] Credenziali database produzione (host, porta, db, user, password)
[ ] Conferma versione PHP in produzione (>= 8.0)

Link utili:
- SSL test: https://www.ssllabs.com/ssltest/
- DNS check: https://dnschecker.org/

====================================================================
4) VALORI .ENV DA COMPILARE (PRODUZIONE)
====================================================================
[ ] APP_ENV=production
[ ] SITE_MODE=full (o landing in emergenza)
[ ] APP_BASE_URL=https://tuo-dominio
[ ] DB_HOST
[ ] DB_PORT
[ ] DB_NAME
[ ] DB_USER
[ ] DB_PASS
[ ] JWT_SECRET (random lungo e robusto)
[ ] CORS_ALLOWED_ORIGINS (solo domini autorizzati)
[ ] MAIL_ENABLED=true/false
[ ] MAIL_FROM_EMAIL
[ ] MAIL_FROM_NAME
[ ] MAIL_ADMIN_EMAIL
[ ] MAIL_ADMIN_NAME
[ ] MAIL_SMTP_HOST
[ ] MAIL_SMTP_PORT
[ ] MAIL_SMTP_USER
[ ] MAIL_SMTP_PASS
[ ] MAIL_SMTP_ENCRYPTION
[ ] MAIL_SMTP_AUTH
[ ] MAIL_SMTP_TIMEOUT

====================================================================
5) EMAIL SMTP (GO-LIVE)
====================================================================
[ ] Provider SMTP scelto
[ ] Host/porta/encryption verificati
[ ] Credenziali SMTP testate
[ ] Casella FROM autorizzata
[ ] Casella ADMIN ricezione notifiche verificata
[ ] SPF configurato su DNS
[ ] DKIM configurato su DNS
[ ] DMARC configurato su DNS

Link utili:
- Google App Password: https://support.google.com/accounts/answer/185833
- SPF checker: https://mxtoolbox.com/spf.aspx
- DKIM checker: https://mxtoolbox.com/dkim.aspx
- DMARC checker: https://mxtoolbox.com/dmarc.aspx

====================================================================
6) CONTENUTI LEGALI DEFINITIVI
====================================================================
[ ] Testo finale Privacy Policy
[ ] Testo finale Cookie Policy
[ ] Testo finale Termini e Condizioni
[ ] Data ultimo aggiornamento policy
[ ] Contatto ufficiale referente privacy

====================================================================
7) UTENTI E RUOLI OPERATIVI
====================================================================
[ ] Lista utenti iniziali (admin/ufficio/bagnino)
[ ] Email reali per ogni ruolo
[ ] Policy password iniziali e cambio password
[ ] Conferma se mantenere o pulire i dati seed/test

====================================================================
8) DECISIONI PRODOTTO ANCORA DA CONFERMARE
====================================================================
[ ] Conferma: QR rigenerato a conferma manuale pratica (attuale: SI)
[ ] Conferma opzione CMS (interno o alternativa)
[ ] Regole autorizzazioni modifica contenuti (chi puo pubblicare)

====================================================================
9) GO-LIVE TECNICO FINALE
====================================================================
[ ] Certificato HTTPS attivo e valido
[ ] Redirect HTTP->HTTPS attivo
[ ] Backup automatico DB e file pianificato
[ ] Monitoraggio uptime attivo
[ ] Test finale mobile/desktop completato
[ ] Console browser senza errori bloccanti

Link utili:
- Performance/SEO: https://pagespeed.web.dev/

====================================================================
10) MATERIALE OPZIONALE (MA CONSIGLIATO)
====================================================================
[ ] Coordinate Google Maps ufficiali
[ ] Link social ufficiali
[ ] Foto originali impianto (con diritti di utilizzo)
[ ] Testi commerciali finali (hero, CTA, microcopy)

====================================================================
NOTE
====================================================================
- Attualmente il progetto NON usa pagamenti online.
- Le pratiche vengono inviate online e finalizzate in struttura.
- Quando hai raccolto i dati, passami tutto in un unico file o cartella.
