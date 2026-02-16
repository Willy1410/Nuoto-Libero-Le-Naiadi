-- =====================================================
-- SCHEMA DATABASE COMPLETO - Sistema Gestione Piscina
-- =====================================================
-- Supabase PostgreSQL con Row Level Security (RLS)
-- Data: 2026-02-15
-- =====================================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =====================================================
-- 1. TABELLA RUOLI
-- =====================================================
CREATE TABLE ruoli (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  nome VARCHAR(50) UNIQUE NOT NULL,
  descrizione TEXT,
  livello INTEGER NOT NULL UNIQUE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Inserisci 4 ruoli
INSERT INTO ruoli (nome, descrizione, livello) VALUES
('utente', 'Utente base: acquista pacchetti, prenota turni, carica documenti', 1),
('bagnino', 'Bagnino: scansiona QR, registra ingressi, visualizza presenze', 2),
('ufficio', 'Ufficio: approva documenti/pagamenti, statistiche, report', 3),
('admin', 'Amministratore: accesso completo, gestione sistema', 4);

-- =====================================================
-- 2. TABELLA PROFILI (extends auth.users)
-- =====================================================
CREATE TABLE profili (
  id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  ruolo_id UUID REFERENCES ruoli(id) NOT NULL DEFAULT (SELECT id FROM ruoli WHERE nome = 'utente'),
  nome VARCHAR(255),
  cognome VARCHAR(255),
  telefono VARCHAR(20),
  data_nascita DATE,
  indirizzo TEXT,
  citta VARCHAR(100),
  cap VARCHAR(10),
  codice_fiscale VARCHAR(16) UNIQUE,
  note TEXT,
  attivo BOOLEAN DEFAULT true,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Trigger: crea profilo automaticamente quando utente si registra
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO public.profili (id, ruolo_id)
  VALUES (NEW.id, (SELECT id FROM ruoli WHERE nome = 'utente'));
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- Trigger: aggiorna updated_at
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER profili_updated_at
  BEFORE UPDATE ON profili
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- =====================================================
-- 3. TABELLA TIPI DOCUMENTO
-- =====================================================
CREATE TABLE tipi_documento (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  nome VARCHAR(100) UNIQUE NOT NULL,
  descrizione TEXT,
  obbligatorio BOOLEAN DEFAULT false,
  template_url TEXT,
  ordine INTEGER,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Inserisci 5 documenti obbligatori
INSERT INTO tipi_documento (nome, descrizione, obbligatorio, ordine) VALUES
('Modulo Iscrizione', 'Modulo di iscrizione compilato e firmato', true, 1),
('Certificato Medico', 'Certificato medico per attività sportiva non agonistica (validità 1 anno)', true, 2),
('Regolamento Interno', 'Regolamento piscina firmato per accettazione', true, 3),
('Privacy GDPR', 'Consenso al trattamento dati personali', true, 4),
('Documento Identità', 'Copia documento identità (carta identità o patente)', true, 5);

-- =====================================================
-- 4. TABELLA DOCUMENTI UTENTE
-- =====================================================
CREATE TABLE documenti_utente (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES profili(id) ON DELETE CASCADE NOT NULL,
  tipo_documento_id UUID REFERENCES tipi_documento(id) NOT NULL,
  file_url TEXT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  stato VARCHAR(20) DEFAULT 'in_attesa' CHECK (stato IN ('da_inviare', 'in_attesa', 'approvato', 'rifiutato')),
  note_revisione TEXT,
  data_caricamento TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  data_revisione TIMESTAMP WITH TIME ZONE,
  revisionato_da UUID REFERENCES profili(id),
  scadenza DATE
);

CREATE INDEX idx_documenti_user ON documenti_utente(user_id);
CREATE INDEX idx_documenti_stato ON documenti_utente(stato);

-- =====================================================
-- 5. TABELLA PACCHETTI
-- =====================================================
CREATE TABLE pacchetti (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  nome VARCHAR(100) NOT NULL,
  descrizione TEXT,
  num_ingressi INTEGER NOT NULL,
  prezzo DECIMAL(10,2) NOT NULL,
  validita_giorni INTEGER NOT NULL,
  attivo BOOLEAN DEFAULT true,
  ordine INTEGER,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Inserisci pacchetti standard
INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, ordine) VALUES
('Ingresso Singolo', 'Singolo ingresso valido per una sessione', 1, 12.00, 30, 1),
('10 Ingressi', 'Pacchetto 10 ingressi (risparmio €20)', 10, 100.00, 180, 2),
('Promo Iscrizione', 'Iscrizione società + 3 lezioni omaggio', 3, 30.00, 180, 3);

CREATE TRIGGER pacchetti_updated_at
  BEFORE UPDATE ON pacchetti
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- =====================================================
-- 6. TABELLA ACQUISTI
-- =====================================================
CREATE TABLE acquisti (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES profili(id) ON DELETE CASCADE NOT NULL,
  pacchetto_id UUID REFERENCES pacchetti(id) NOT NULL,
  data_acquisto TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  metodo_pagamento VARCHAR(50) CHECK (metodo_pagamento IN ('bonifico', 'contanti', 'carta')),
  stato_pagamento VARCHAR(20) DEFAULT 'in_attesa' CHECK (stato_pagamento IN ('in_attesa', 'confermato', 'rifiutato')),
  riferimento_pagamento TEXT,
  note_pagamento TEXT,
  qr_code VARCHAR(100) UNIQUE,
  ingressi_rimanenti INTEGER,
  data_scadenza DATE,
  confermato_da UUID REFERENCES profili(id),
  data_conferma TIMESTAMP WITH TIME ZONE,
  importo_pagato DECIMAL(10,2),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_acquisti_user ON acquisti(user_id);
CREATE INDEX idx_acquisti_qr ON acquisti(qr_code);
CREATE INDEX idx_acquisti_stato ON acquisti(stato_pagamento);

CREATE TRIGGER acquisti_updated_at
  BEFORE UPDATE ON acquisti
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- =====================================================
-- 7. TABELLA PRENOTAZIONI
-- =====================================================
CREATE TABLE prenotazioni (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  acquisto_id UUID REFERENCES acquisti(id) ON DELETE CASCADE NOT NULL,
  user_id UUID REFERENCES profili(id) ON DELETE CASCADE NOT NULL,
  data_turno DATE NOT NULL,
  giorno_settimana VARCHAR(10) CHECK (giorno_settimana IN ('lunedi', 'mercoledi', 'venerdi')),
  fascia_oraria VARCHAR(20) CHECK (fascia_oraria IN ('mattina', 'pomeriggio', 'sera')),
  orario_inizio TIME NOT NULL,
  orario_fine TIME NOT NULL,
  stato VARCHAR(20) DEFAULT 'confermata' CHECK (stato IN ('confermata', 'completata', 'cancellata', 'non_presentato')),
  note TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_prenotazioni_user ON prenotazioni(user_id);
CREATE INDEX idx_prenotazioni_data ON prenotazioni(data_turno);
CREATE INDEX idx_prenotazioni_stato ON prenotazioni(stato);

CREATE TRIGGER prenotazioni_updated_at
  BEFORE UPDATE ON prenotazioni
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- =====================================================
-- 8. TABELLA CHECK-INS
-- =====================================================
CREATE TABLE check_ins (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  prenotazione_id UUID REFERENCES prenotazioni(id),
  acquisto_id UUID REFERENCES acquisti(id) NOT NULL,
  user_id UUID REFERENCES profili(id) ON DELETE CASCADE NOT NULL,
  timestamp TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  bagnino_id UUID REFERENCES profili(id) NOT NULL,
  fascia_oraria VARCHAR(20) CHECK (fascia_oraria IN ('mattina', 'pomeriggio')),
  note TEXT
);

CREATE INDEX idx_checkins_user ON check_ins(user_id);
CREATE INDEX idx_checkins_timestamp ON check_ins(timestamp);
CREATE INDEX idx_checkins_bagnino ON check_ins(bagnino_id);

-- =====================================================
-- 9. TABELLA COMUNICAZIONI
-- =====================================================
CREATE TABLE comunicazioni (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  titolo VARCHAR(255) NOT NULL,
  messaggio TEXT NOT NULL,
  tipo VARCHAR(20) CHECK (tipo IN ('info', 'avviso', 'urgente', 'manutenzione')),
  destinatari VARCHAR(20) CHECK (destinatari IN ('tutti', 'utenti', 'staff')),
  priorita INTEGER DEFAULT 1 CHECK (priorita BETWEEN 1 AND 3),
  pubblicata BOOLEAN DEFAULT false,
  data_inizio TIMESTAMP WITH TIME ZONE,
  data_fine TIMESTAMP WITH TIME ZONE,
  creata_da UUID REFERENCES profili(id) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_comunicazioni_pubblicata ON comunicazioni(pubblicata);
CREATE INDEX idx_comunicazioni_destinatari ON comunicazioni(destinatari);

CREATE TRIGGER comunicazioni_updated_at
  BEFORE UPDATE ON comunicazioni
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- =====================================================
-- 10. TABELLA CONTENUTI SITO (CMS)
-- =====================================================
CREATE TABLE contenuti_sito (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  sezione VARCHAR(50) NOT NULL,
  chiave VARCHAR(100) UNIQUE NOT NULL,
  valore TEXT,
  tipo VARCHAR(20) CHECK (tipo IN ('text', 'html', 'image', 'url')),
  ordine INTEGER,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_contenuti_sezione ON contenuti_sito(sezione);

-- Inserisci contenuti default
INSERT INTO contenuti_sito (sezione, chiave, valore, tipo, ordine) VALUES
('home', 'hero_title', 'Nuoto Libero alla Piscina Comunale', 'text', 1),
('home', 'hero_subtitle', 'Prenota il tuo turno e vieni a nuotare', 'text', 2),
('orari', 'lunedi_mattina', '07:00-09:00', 'text', 1),
('orari', 'lunedi_pomeriggio', '13:00-14:00', 'text', 2),
('orari', 'mercoledi_mattina', '07:00-09:00', 'text', 3),
('orari', 'mercoledi_pomeriggio', '13:00-14:00', 'text', 4),
('orari', 'venerdi_mattina', '07:00-09:00', 'text', 5),
('orari', 'venerdi_pomeriggio', '13:00-14:00', 'text', 6),
('contatti', 'indirizzo', 'Via Federico Fellini, 2', 'text', 1),
('contatti', 'citta', 'Spoltore (PE)', 'text', 2),
('contatti', 'telefono', '+39 123 456 789', 'text', 3),
('contatti', 'email', 'info@piscina.it', 'text', 4);

CREATE TRIGGER contenuti_updated_at
  BEFORE UPDATE ON contenuti_sito
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- =====================================================
-- 11. TABELLA GALLERY
-- =====================================================
CREATE TABLE gallery (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  titolo VARCHAR(255),
  descrizione TEXT,
  image_url TEXT NOT NULL,
  ordine INTEGER,
  visibile BOOLEAN DEFAULT true,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_gallery_visibile ON gallery(visibile);

CREATE TRIGGER gallery_updated_at
  BEFORE UPDATE ON gallery
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- =====================================================
-- 12. TABELLA ACTIVITY LOG
-- =====================================================
CREATE TABLE activity_log (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES profili(id) ON DELETE SET NULL,
  azione VARCHAR(100) NOT NULL,
  entita VARCHAR(50),
  entita_id UUID,
  dettagli JSONB,
  ip_address VARCHAR(50),
  timestamp TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_timestamp ON activity_log(timestamp);
CREATE INDEX idx_activity_azione ON activity_log(azione);

-- =====================================================
-- FUNZIONI UTILITY
-- =====================================================

-- Funzione: ottieni livello ruolo utente corrente
CREATE OR REPLACE FUNCTION get_user_role_level()
RETURNS INTEGER AS $$
  SELECT r.livello
  FROM profili p
  JOIN ruoli r ON p.ruolo_id = r.id
  WHERE p.id = auth.uid();
$$ LANGUAGE sql SECURITY DEFINER;

-- Funzione: verifica documenti completi
CREATE OR REPLACE FUNCTION documenti_completi(p_user_id UUID)
RETURNS BOOLEAN AS $$
  SELECT COUNT(DISTINCT du.tipo_documento_id) >= 
         (SELECT COUNT(*) FROM tipi_documento WHERE obbligatorio = true)
  FROM documenti_utente du
  WHERE du.user_id = p_user_id 
    AND du.stato = 'approvato';
$$ LANGUAGE sql;

-- Funzione: genera QR code univoco
CREATE OR REPLACE FUNCTION genera_qr_code()
RETURNS TEXT AS $$
  SELECT 'PSC-' || substring(uuid_generate_v4()::text, 1, 8) || '-' || extract(epoch from now())::bigint;
$$ LANGUAGE sql;

-- =====================================================
-- ROW LEVEL SECURITY (RLS)
-- =====================================================

-- Abilita RLS su tutte le tabelle
ALTER TABLE profili ENABLE ROW LEVEL SECURITY;
ALTER TABLE documenti_utente ENABLE ROW LEVEL SECURITY;
ALTER TABLE pacchetti ENABLE ROW LEVEL SECURITY;
ALTER TABLE acquisti ENABLE ROW LEVEL SECURITY;
ALTER TABLE prenotazioni ENABLE ROW LEVEL SECURITY;
ALTER TABLE check_ins ENABLE ROW LEVEL SECURITY;
ALTER TABLE comunicazioni ENABLE ROW LEVEL SECURITY;
ALTER TABLE contenuti_sito ENABLE ROW LEVEL SECURITY;
ALTER TABLE gallery ENABLE ROW LEVEL SECURITY;
ALTER TABLE activity_log ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- POLICY: PROFILI
-- =====================================================

-- SELECT: utente vede proprio profilo, staff vede tutti
CREATE POLICY "profili_select" ON profili
FOR SELECT USING (
  id = auth.uid() OR get_user_role_level() >= 2
);

-- UPDATE: utente aggiorna solo proprio, staff aggiorna tutti
CREATE POLICY "profili_update" ON profili
FOR UPDATE USING (
  id = auth.uid() OR get_user_role_level() >= 3
);

-- =====================================================
-- POLICY: DOCUMENTI UTENTE
-- =====================================================

-- SELECT: utente vede propri, ufficio/admin vedono tutti
CREATE POLICY "documenti_select" ON documenti_utente
FOR SELECT USING (
  user_id = auth.uid() OR get_user_role_level() >= 3
);

-- INSERT: solo utente può caricare propri documenti
CREATE POLICY "documenti_insert" ON documenti_utente
FOR INSERT WITH CHECK (
  user_id = auth.uid()
);

-- UPDATE: solo ufficio/admin può aggiornare (approvazione)
CREATE POLICY "documenti_update" ON documenti_utente
FOR UPDATE USING (
  get_user_role_level() >= 3
);

-- =====================================================
-- POLICY: PACCHETTI
-- =====================================================

-- SELECT: tutti vedono pacchetti attivi
CREATE POLICY "pacchetti_select" ON pacchetti
FOR SELECT USING (attivo = true OR get_user_role_level() >= 4);

-- INSERT/UPDATE/DELETE: solo admin
CREATE POLICY "pacchetti_modify" ON pacchetti
FOR ALL USING (get_user_role_level() = 4);

-- =====================================================
-- POLICY: ACQUISTI
-- =====================================================

-- SELECT: utente vede propri, staff vede tutti
CREATE POLICY "acquisti_select" ON acquisti
FOR SELECT USING (
  user_id = auth.uid() OR get_user_role_level() >= 2
);

-- INSERT: utente crea propri acquisti
CREATE POLICY "acquisti_insert" ON acquisti
FOR INSERT WITH CHECK (
  user_id = auth.uid() OR get_user_role_level() >= 3
);

-- UPDATE: ufficio/admin conferma pagamenti, utente NO
CREATE POLICY "acquisti_update" ON acquisti
FOR UPDATE USING (
  get_user_role_level() >= 3
);

-- =====================================================
-- POLICY: PRENOTAZIONI
-- =====================================================

-- SELECT: utente vede proprie, staff vede tutte
CREATE POLICY "prenotazioni_select" ON prenotazioni
FOR SELECT USING (
  user_id = auth.uid() OR get_user_role_level() >= 2
);

-- INSERT: utente prenota per sé, staff per tutti
CREATE POLICY "prenotazioni_insert" ON prenotazioni
FOR INSERT WITH CHECK (
  user_id = auth.uid() OR get_user_role_level() >= 2
);

-- UPDATE: utente cancella proprie, staff gestisce tutte
CREATE POLICY "prenotazioni_update" ON prenotazioni
FOR UPDATE USING (
  user_id = auth.uid() OR get_user_role_level() >= 2
);

-- =====================================================
-- POLICY: CHECK-INS
-- =====================================================

-- SELECT: utente vede propri, staff vede tutti
CREATE POLICY "checkins_select" ON check_ins
FOR SELECT USING (
  user_id = auth.uid() OR get_user_role_level() >= 2
);

-- INSERT: solo bagnino/ufficio/admin registra
CREATE POLICY "checkins_insert" ON check_ins
FOR INSERT WITH CHECK (
  get_user_role_level() >= 2
);

-- =====================================================
-- POLICY: COMUNICAZIONI
-- =====================================================

-- SELECT: utenti vedono pubblicate per loro, staff vede tutte
CREATE POLICY "comunicazioni_select" ON comunicazioni
FOR SELECT USING (
  (pubblicata = true AND (
    destinatari = 'tutti' OR 
    (destinatari = 'utenti' AND get_user_role_level() = 1) OR
    (destinatari = 'staff' AND get_user_role_level() >= 2)
  )) OR
  get_user_role_level() >= 3
);

-- INSERT/UPDATE: solo ufficio/admin
CREATE POLICY "comunicazioni_modify" ON comunicazioni
FOR ALL USING (get_user_role_level() >= 3);

-- =====================================================
-- POLICY: CONTENUTI SITO (CMS)
-- =====================================================

-- SELECT: tutti leggono
CREATE POLICY "contenuti_select" ON contenuti_sito
FOR SELECT USING (true);

-- UPDATE: solo admin modifica
CREATE POLICY "contenuti_update" ON contenuti_sito
FOR UPDATE USING (get_user_role_level() = 4);

-- =====================================================
-- POLICY: GALLERY
-- =====================================================

-- SELECT: tutti vedono immagini visibili, admin vede tutte
CREATE POLICY "gallery_select" ON gallery
FOR SELECT USING (
  visibile = true OR get_user_role_level() = 4
);

-- INSERT/UPDATE/DELETE: solo admin
CREATE POLICY "gallery_modify" ON gallery
FOR ALL USING (get_user_role_level() = 4);

-- =====================================================
-- POLICY: ACTIVITY LOG
-- =====================================================

-- SELECT: utente vede propri log, admin vede tutti
CREATE POLICY "activity_select" ON activity_log
FOR SELECT USING (
  user_id = auth.uid() OR get_user_role_level() = 4
);

-- INSERT: tutti possono loggare proprie azioni
CREATE POLICY "activity_insert" ON activity_log
FOR INSERT WITH CHECK (true);

-- =====================================================
-- STORAGE BUCKETS
-- =====================================================

-- Bucket: documenti-utenti (privato)
INSERT INTO storage.buckets (id, name, public) 
VALUES ('documenti-utenti', 'documenti-utenti', false)
ON CONFLICT DO NOTHING;

-- Policy: utente carica solo propri documenti
CREATE POLICY "documenti_upload" ON storage.objects
FOR INSERT WITH CHECK (
  bucket_id = 'documenti-utenti' AND
  auth.uid()::text = (storage.foldername(name))[1]
);

-- Policy: utente vede propri, staff vede tutti
CREATE POLICY "documenti_select_storage" ON storage.objects
FOR SELECT USING (
  bucket_id = 'documenti-utenti' AND (
    auth.uid()::text = (storage.foldername(name))[1] OR
    get_user_role_level() >= 3
  )
);

-- Bucket: gallery-images (pubblico)
INSERT INTO storage.buckets (id, name, public) 
VALUES ('gallery-images', 'gallery-images', true)
ON CONFLICT DO NOTHING;

-- Policy: solo admin carica gallery
CREATE POLICY "gallery_upload" ON storage.objects
FOR INSERT WITH CHECK (
  bucket_id = 'gallery-images' AND
  get_user_role_level() = 4
);

-- Policy: tutti leggono gallery
CREATE POLICY "gallery_select_storage" ON storage.objects
FOR SELECT USING (bucket_id = 'gallery-images');

-- Bucket: documenti-template (pubblico)
INSERT INTO storage.buckets (id, name, public) 
VALUES ('documenti-template', 'documenti-template', true)
ON CONFLICT DO NOTHING;

-- Policy: tutti leggono template
CREATE POLICY "template_select_storage" ON storage.objects
FOR SELECT USING (bucket_id = 'documenti-template');

-- =====================================================
-- FINE MIGRATION
-- =====================================================
