-- =====================================================
-- SEED DATA - Dati iniziali per testing
-- =====================================================

-- =====================================================
-- 1. UTENTI TEST (via Supabase Auth)
-- =====================================================
-- NOTA: Questi utenti devono essere creati tramite Supabase Auth UI o API
-- Gli ID qui sotto sono placeholder, sostituire con ID reali dopo registrazione

-- Admin: admin@piscina.it / Admin123!
-- Ufficio: ufficio@piscina.it / Ufficio123!
-- Bagnino: bagnino@piscina.it / Bagnino123!
-- Utente1: mario.rossi@email.it / User123!
-- Utente2: laura.bianchi@email.it / User123!

-- Per creare utenti via SQL (solo per testing locale):
-- INSERT INTO auth.users (id, email, encrypted_password, email_confirmed_at)
-- VALUES ('uuid-admin', 'admin@piscina.it', crypt('Admin123!', gen_salt('bf')), NOW());

-- =====================================================
-- 2. PROFILI UTENTI TEST
-- =====================================================

-- Profilo Admin (UUID generato casualmente per esempio)
INSERT INTO profili (id, ruolo_id, nome, cognome, telefono, data_nascita, codice_fiscale, attivo)
VALUES (
  'a0000000-0000-0000-0000-000000000001'::uuid,
  (SELECT id FROM ruoli WHERE nome = 'admin'),
  'Amministratore',
  'Sistema',
  '+39 123 456 789',
  '1980-01-01',
  'ADMSST80A01H501Z',
  true
)
ON CONFLICT (id) DO NOTHING;

-- Profilo Ufficio
INSERT INTO profili (id, ruolo_id, nome, cognome, telefono, data_nascita, codice_fiscale, attivo)
VALUES (
  'b0000000-0000-0000-0000-000000000002'::uuid,
  (SELECT id FROM ruoli WHERE nome = 'ufficio'),
  'Maria',
  'Bianchi',
  '+39 345 678 901',
  '1985-05-15',
  'BNCMRA85E55H501X',
  true
)
ON CONFLICT (id) DO NOTHING;

-- Profilo Bagnino
INSERT INTO profili (id, ruolo_id, nome, cognome, telefono, data_nascita, codice_fiscale, attivo)
VALUES (
  'c0000000-0000-0000-0000-000000000003'::uuid,
  (SELECT id FROM ruoli WHERE nome = 'bagnino'),
  'Luca',
  'Verdi',
  '+39 333 222 111',
  '1990-08-20',
  'VRDLCU90M20H501K',
  true
)
ON CONFLICT (id) DO NOTHING;

-- Profilo Utente 1: Mario Rossi
INSERT INTO profili (id, ruolo_id, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo)
VALUES (
  'd0000000-0000-0000-0000-000000000004'::uuid,
  (SELECT id FROM ruoli WHERE nome = 'utente'),
  'Mario',
  'Rossi',
  '+39 333 123 4567',
  '1985-08-01',
  'Via Roma, 123',
  'Pescara',
  '65100',
  'RSSMRA85M01H501Z',
  true
)
ON CONFLICT (id) DO NOTHING;

-- Profilo Utente 2: Laura Bianchi
INSERT INTO profili (id, ruolo_id, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo)
VALUES (
  'e0000000-0000-0000-0000-000000000005'::uuid,
  (SELECT id FROM ruoli WHERE nome = 'utente'),
  'Laura',
  'Bianchi',
  '+39 345 987 6543',
  '1990-04-05',
  'Via Garibaldi, 45',
  'Spoltore',
  '65010',
  'BNCLRA90D45H501K',
  true
)
ON CONFLICT (id) DO NOTHING;

-- Profilo Utente 3: Giuseppe Verdi
INSERT INTO profili (id, ruolo_id, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo)
VALUES (
  'f0000000-0000-0000-0000-000000000006'::uuid,
  (SELECT id FROM ruoli WHERE nome = 'utente'),
  'Giuseppe',
  'Verdi',
  '+39 338 555 1234',
  '1980-01-01',
  'Corso Umberto, 78',
  'Pescara',
  '65100',
  'VRDGPP80A01H501M',
  true
)
ON CONFLICT (id) DO NOTHING;

-- Profilo Utente 4: Anna Ferrari
INSERT INTO profili (id, ruolo_id, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo)
VALUES (
  'g0000000-0000-0000-0000-000000000007'::uuid,
  (SELECT id FROM ruoli WHERE nome = 'utente'),
  'Anna',
  'Ferrari',
  '+39 340 777 8899',
  '1988-12-08',
  'Via Venezia, 56',
  'Montesilvano',
  '65016',
  'FRRNN88T48H501R',
  true
)
ON CONFLICT (id) DO NOTHING;

-- =====================================================
-- 3. ACQUISTI TEST (con QR code)
-- =====================================================

-- Acquisto Mario Rossi: 10 ingressi (8 rimanenti, confermato)
INSERT INTO acquisti (
  id,
  user_id,
  pacchetto_id,
  data_acquisto,
  metodo_pagamento,
  stato_pagamento,
  qr_code,
  ingressi_rimanenti,
  data_scadenza,
  confermato_da,
  data_conferma,
  importo_pagato
)
VALUES (
  'aa000000-0000-0000-0000-00000000000a'::uuid,
  'd0000000-0000-0000-0000-000000000004'::uuid,  -- Mario Rossi
  (SELECT id FROM pacchetti WHERE nome = '10 Ingressi'),
  NOW() - INTERVAL '30 days',
  'bonifico',
  'confermato',
  'PSC-mario-001-' || extract(epoch from now())::bigint,
  8,  -- 8 ingressi rimasti
  CURRENT_DATE + INTERVAL '150 days',
  'b0000000-0000-0000-0000-000000000002'::uuid,  -- Confermato da Ufficio
  NOW() - INTERVAL '29 days',
  100.00
);

-- Acquisto Laura Bianchi: 10 ingressi (2 rimanenti, in scadenza, confermato)
INSERT INTO acquisti (
  id,
  user_id,
  pacchetto_id,
  data_acquisto,
  metodo_pagamento,
  stato_pagamento,
  qr_code,
  ingressi_rimanenti,
  data_scadenza,
  confermato_da,
  data_conferma,
  importo_pagato
)
VALUES (
  'bb000000-0000-0000-0000-00000000000b'::uuid,
  'e0000000-0000-0000-0000-000000000005'::uuid,  -- Laura Bianchi
  (SELECT id FROM pacchetti WHERE nome = '10 Ingressi'),
  NOW() - INTERVAL '150 days',
  'contanti',
  'confermato',
  'PSC-laura-002-' || extract(epoch from now())::bigint,
  2,  -- ATTENZIONE: solo 2 ingressi rimasti!
  CURRENT_DATE + INTERVAL '25 days',  -- Scade tra 25 giorni
  'b0000000-0000-0000-0000-000000000002'::uuid,
  NOW() - INTERVAL '149 days',
  100.00
);

-- Acquisto Giuseppe Verdi: 10 ingressi (0 rimanenti, ESAURITO, confermato)
INSERT INTO acquisti (
  id,
  user_id,
  pacchetto_id,
  data_acquisto,
  metodo_pagamento,
  stato_pagamento,
  qr_code,
  ingressi_rimanenti,
  data_scadenza,
  confermato_da,
  data_conferma,
  importo_pagato
)
VALUES (
  'cc000000-0000-0000-0000-00000000000c'::uuid,
  'f0000000-0000-0000-0000-000000000006'::uuid,  -- Giuseppe Verdi
  (SELECT id FROM pacchetti WHERE nome = '10 Ingressi'),
  NOW() - INTERVAL '180 days',
  'bonifico',
  'confermato',
  'PSC-giuseppe-003-' || extract(epoch from now())::bigint,
  0,  -- ESAURITO!
  CURRENT_DATE - INTERVAL '10 days',  -- SCADUTO!
  'b0000000-0000-0000-0000-000000000002'::uuid,
  NOW() - INTERVAL '179 days',
  100.00
);

-- Acquisto Anna Ferrari: Promo (3 ingressi, 3 rimanenti, confermato)
INSERT INTO acquisti (
  id,
  user_id,
  pacchetto_id,
  data_acquisto,
  metodo_pagamento,
  stato_pagamento,
  qr_code,
  ingressi_rimanenti,
  data_scadenza,
  confermato_da,
  data_conferma,
  importo_pagato
)
VALUES (
  'dd000000-0000-0000-0000-00000000000d'::uuid,
  'g0000000-0000-0000-0000-000000000007'::uuid,  -- Anna Ferrari
  (SELECT id FROM pacchetti WHERE nome = 'Promo Iscrizione'),
  NOW() - INTERVAL '10 days',
  'contanti',
  'confermato',
  'PSC-anna-004-' || extract(epoch from now())::bigint,
  3,  -- 3 ingressi (nessuno ancora usato)
  CURRENT_DATE + INTERVAL '170 days',
  'b0000000-0000-0000-0000-000000000002'::uuid,
  NOW() - INTERVAL '9 days',
  30.00
);

-- Acquisto IN ATTESA (da confermare dall'ufficio)
INSERT INTO acquisti (
  id,
  user_id,
  pacchetto_id,
  data_acquisto,
  metodo_pagamento,
  stato_pagamento,
  riferimento_pagamento,
  note_pagamento,
  importo_pagato
)
VALUES (
  'ee000000-0000-0000-0000-00000000000e'::uuid,
  'g0000000-0000-0000-0000-000000000007'::uuid,  -- Anna Ferrari (vuole comprare altro pacchetto)
  (SELECT id FROM pacchetti WHERE nome = '10 Ingressi'),
  NOW() - INTERVAL '2 days',
  'bonifico',
  'in_attesa',  -- IN ATTESA DI CONFERMA!
  'BONIFICO-20260215-001',
  'Pagamento effettuato il 13/02/2026',
  100.00
);

-- =====================================================
-- 4. PRENOTAZIONI TEST
-- =====================================================

-- Prenotazione Mario Rossi: Lunedì prossimo mattina
INSERT INTO prenotazioni (
  user_id,
  acquisto_id,
  data_turno,
  giorno_settimana,
  fascia_oraria,
  orario_inizio,
  orario_fine,
  stato
)
VALUES (
  'd0000000-0000-0000-0000-000000000004'::uuid,
  'aa000000-0000-0000-0000-00000000000a'::uuid,
  CURRENT_DATE + INTERVAL '1 day' + (7 - EXTRACT(DOW FROM CURRENT_DATE + INTERVAL '1 day'))::int % 7 * INTERVAL '1 day',  -- Prossimo lunedì
  'lunedi',
  'mattina',
  '07:00',
  '09:00',
  'confermata'
);

-- Prenotazione Laura Bianchi: Mercoledì prossimo pomeriggio
INSERT INTO prenotazioni (
  user_id,
  acquisto_id,
  data_turno,
  giorno_settimana,
  fascia_oraria,
  orario_inizio,
  orario_fine,
  stato
)
VALUES (
  'e0000000-0000-0000-0000-000000000005'::uuid,
  'bb000000-0000-0000-0000-00000000000b'::uuid,
  CURRENT_DATE + INTERVAL '3 day',  -- Tra 3 giorni
  'mercoledi',
  'pomeriggio',
  '13:00',
  '14:00',
  'confermata'
);

-- =====================================================
-- 5. CHECK-INS STORICI (ultimi 7 giorni)
-- =====================================================

-- Check-in Mario Rossi (2 ingressi passati)
INSERT INTO check_ins (user_id, acquisto_id, timestamp, bagnino_id, fascia_oraria, note)
VALUES 
  ('d0000000-0000-0000-0000-000000000004'::uuid, 'aa000000-0000-0000-0000-00000000000a'::uuid, NOW() - INTERVAL '5 days', 'c0000000-0000-0000-0000-000000000003'::uuid, 'mattina', 'Check-in regolare'),
  ('d0000000-0000-0000-0000-000000000004'::uuid, 'aa000000-0000-0000-0000-00000000000a'::uuid, NOW() - INTERVAL '2 days', 'c0000000-0000-0000-0000-000000000003'::uuid, 'pomeriggio', 'Check-in regolare');

-- Check-in Laura Bianchi (8 ingressi passati)
INSERT INTO check_ins (user_id, acquisto_id, timestamp, bagnino_id, fascia_oraria)
SELECT 
  'e0000000-0000-0000-0000-000000000005'::uuid,
  'bb000000-0000-0000-0000-00000000000b'::uuid,
  NOW() - (s * INTERVAL '10 days'),
  'c0000000-0000-0000-0000-000000000003'::uuid,
  CASE WHEN s % 2 = 0 THEN 'mattina' ELSE 'pomeriggio' END
FROM generate_series(1, 8) AS s;

-- Check-in Giuseppe Verdi (10 ingressi, TUTTI USATI)
INSERT INTO check_ins (user_id, acquisto_id, timestamp, bagnino_id, fascia_oraria)
SELECT 
  'f0000000-0000-0000-0000-000000000006'::uuid,
  'cc000000-0000-0000-0000-00000000000c'::uuid,
  NOW() - (s * INTERVAL '15 days'),
  'c0000000-0000-0000-0000-000000000003'::uuid,
  CASE WHEN s % 2 = 0 THEN 'mattina' ELSE 'pomeriggio' END
FROM generate_series(1, 10) AS s;

-- =====================================================
-- 6. DOCUMENTI UTENTE (stati diversi per test)
-- =====================================================

-- Mario Rossi: TUTTI DOCUMENTI APPROVATI ✅
INSERT INTO documenti_utente (user_id, tipo_documento_id, file_url, file_name, stato, data_caricamento, data_revisione, revisionato_da)
SELECT 
  'd0000000-0000-0000-0000-000000000004'::uuid,
  td.id,
  'documenti-utenti/mario-rossi/' || td.nome || '.pdf',
  td.nome || '.pdf',
  'approvato',
  NOW() - INTERVAL '25 days',
  NOW() - INTERVAL '24 days',
  'b0000000-0000-0000-0000-000000000002'::uuid  -- Approvato da Ufficio
FROM tipi_documento td
WHERE td.obbligatorio = true;

-- Laura Bianchi: 4 APPROVATI, 1 IN ATTESA ⏳
INSERT INTO documenti_utente (user_id, tipo_documento_id, file_url, file_name, stato, data_caricamento, data_revisione, revisionato_da)
SELECT 
  'e0000000-0000-0000-0000-000000000005'::uuid,
  td.id,
  'documenti-utenti/laura-bianchi/' || td.nome || '.pdf',
  td.nome || '.pdf',
  'approvato',
  NOW() - INTERVAL '20 days',
  NOW() - INTERVAL '19 days',
  'b0000000-0000-0000-0000-000000000002'::uuid
FROM tipi_documento td
WHERE td.obbligatorio = true AND td.ordine <= 4;

-- Laura: Documento Identità in ATTESA
INSERT INTO documenti_utente (user_id, tipo_documento_id, file_url, file_name, stato, data_caricamento)
VALUES (
  'e0000000-0000-0000-0000-000000000005'::uuid,
  (SELECT id FROM tipi_documento WHERE nome = 'Documento Identità'),
  'documenti-utenti/laura-bianchi/Documento Identità.pdf',
  'Documento Identità.pdf',
  'in_attesa',  -- IN ATTESA DI REVISIONE!
  NOW() - INTERVAL '2 days'
);

-- Giuseppe Verdi: 2 APPROVATI, 1 RIFIUTATO, 2 DA INVIARE ❌
INSERT INTO documenti_utente (user_id, tipo_documento_id, file_url, file_name, stato, data_caricamento, data_revisione, revisionato_da)
SELECT 
  'f0000000-0000-0000-0000-000000000006'::uuid,
  td.id,
  'documenti-utenti/giuseppe-verdi/' || td.nome || '.pdf',
  td.nome || '.pdf',
  'approvato',
  NOW() - INTERVAL '100 days',
  NOW() - INTERVAL '99 days',
  'b0000000-0000-0000-0000-000000000002'::uuid
FROM tipi_documento td
WHERE td.ordine IN (1, 3);

-- Giuseppe: Certificato Medico RIFIUTATO
INSERT INTO documenti_utente (user_id, tipo_documento_id, file_url, file_name, stato, note_revisione, data_caricamento, data_revisione, revisionato_da)
VALUES (
  'f0000000-0000-0000-0000-000000000006'::uuid,
  (SELECT id FROM tipi_documento WHERE nome = 'Certificato Medico'),
  'documenti-utenti/giuseppe-verdi/Certificato Medico.pdf',
  'Certificato Medico.pdf',
  'rifiutato',  -- RIFIUTATO!
  'Certificato scaduto. Si prega di caricare un certificato aggiornato.',
  NOW() - INTERVAL '95 days',
  NOW() - INTERVAL '94 days',
  'b0000000-0000-0000-0000-000000000002'::uuid
);

-- Anna Ferrari: NESSUN DOCUMENTO CARICATO (utente nuovo)

-- =====================================================
-- 7. COMUNICAZIONI TEST
-- =====================================================

-- Comunicazione URGENTE per tutti
INSERT INTO comunicazioni (titolo, messaggio, tipo, destinatari, priorita, pubblicata, data_inizio, data_fine, creata_da)
VALUES (
  '⚠️ Chiusura straordinaria 20 febbraio',
  'La piscina resterà chiusa per manutenzione straordinaria il giorno 20 febbraio 2026. Le prenotazioni per quella data sono state cancellate e verranno recuperate.',
  'urgente',
  'tutti',
  3,
  true,
  NOW(),
  CURRENT_DATE + INTERVAL '5 days',
  'b0000000-0000-0000-0000-000000000002'::uuid
);

-- Comunicazione INFO per utenti
INSERT INTO comunicazioni (titolo, messaggio, tipo, destinatari, priorita, pubblicata, data_inizio, creata_da)
VALUES (
  'ℹ️ Nuovi orari da marzo',
  'Da marzo 2026 verranno attivati nuovi orari pomeridiani anche il martedì e giovedì (15:00-17:00). Restate aggiornati!',
  'info',
  'utenti',
  1,
  true,
  NOW(),
  'a0000000-0000-0000-0000-000000000001'::uuid
);

-- Comunicazione per STAFF (non visibile agli utenti)
INSERT INTO comunicazioni (titolo, messaggio, tipo, destinatari, priorita, pubblicata, data_inizio, creata_da)
VALUES (
  '📋 Riunione staff 18 febbraio',
  'Tutti i bagnini e il personale di ufficio sono convocati per una riunione operativa il 18 febbraio alle ore 14:00.',
  'avviso',
  'staff',
  2,
  true,
  NOW(),
  'a0000000-0000-0000-0000-000000000001'::uuid
);

-- =====================================================
-- 8. GALLERY IMMAGINI TEST
-- =====================================================

-- Immagini piscina (placeholder Unsplash)
INSERT INTO gallery (titolo, descrizione, image_url, ordine, visibile)
VALUES 
  ('Piscina Olimpionica', 'La nostra piscina da 50 metri', 'https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?w=800&q=80', 1, true),
  ('Corsie Dedicate', 'Corsie dedicate per nuoto libero', 'https://images.unsplash.com/photo-1519315901367-f34ff9154487?w=800&q=80', 2, true),
  ('Spogliatoi', 'Spogliatoi moderni e attrezzati', 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=800&q=80', 3, true),
  ('Area Relax', 'Zona relax con sdraio', 'https://images.unsplash.com/photo-1545205597-3d9d02c29597?w=800&q=80', 4, true),
  ('Allenamento', 'Nuotatori in allenamento', 'https://images.unsplash.com/photo-1600965962361-9035dbfd1c50?w=800&q=80', 5, false);  -- Nascosta

-- =====================================================
-- 9. ACTIVITY LOG (ultimi giorni)
-- =====================================================

-- Log azioni recenti
INSERT INTO activity_log (user_id, azione, entita, entita_id, dettagli, ip_address, timestamp)
VALUES 
  ('d0000000-0000-0000-0000-000000000004'::uuid, 'login', 'auth', NULL, '{"user_agent": "Chrome"}', '192.168.1.100', NOW() - INTERVAL '1 hour'),
  ('d0000000-0000-0000-0000-000000000004'::uuid, 'prenotazione_creata', 'prenotazioni', 'aa000000-0000-0000-0000-00000000000a'::uuid, '{"data": "2026-02-20", "fascia": "mattina"}', '192.168.1.100', NOW() - INTERVAL '50 minutes'),
  ('b0000000-0000-0000-0000-000000000002'::uuid, 'pagamento_confermato', 'acquisti', 'aa000000-0000-0000-0000-00000000000a'::uuid, '{"importo": 100, "metodo": "bonifico"}', '192.168.1.50', NOW() - INTERVAL '2 days'),
  ('c0000000-0000-0000-0000-000000000003'::uuid, 'check_in_registrato', 'check_ins', NULL, '{"user": "Mario Rossi", "qr": "PSC-mario-001"}', '192.168.1.75', NOW() - INTERVAL '3 hours');

-- =====================================================
-- FINE SEED DATA
-- =====================================================

-- Riepilogo:
-- ✅ 4 ruoli configurati
-- ✅ 7 utenti test (1 admin, 1 ufficio, 1 bagnino, 4 utenti)
-- ✅ 4 acquisti confermati con QR code
-- ✅ 1 acquisto in attesa conferma
-- ✅ 2 prenotazioni future
-- ✅ Check-ins storici (ultimi 7 giorni)
-- ✅ Documenti con stati diversi (approvato, in_attesa, rifiutato, da_inviare)
-- ✅ 3 comunicazioni pubblicate
-- ✅ 5 immagini gallery
-- ✅ Activity log sample

-- Prossimo step: Creare utenti reali via Supabase Auth UI e sostituire gli UUID placeholder
