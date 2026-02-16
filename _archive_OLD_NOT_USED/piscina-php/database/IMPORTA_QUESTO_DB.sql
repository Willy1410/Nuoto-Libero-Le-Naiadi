-- =====================================================
-- SISTEMA GESTIONE PISCINA - DATABASE COMPLETO
-- =====================================================
-- VERSIONE: 1.0
-- DATA: 2026-02-15
-- DESCRIZIONE: Schema completo per sistema gestione piscina
--              con 4 ruoli, documenti, pacchetti, prenotazioni,
--              check-ins, comunicazioni, CMS, gallery
-- =====================================================

-- Elimina database se esiste e ricrea
DROP DATABASE IF EXISTS piscina_gestione;
CREATE DATABASE piscina_gestione 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE piscina_gestione;

-- =====================================================
-- TABELLA: ruoli
-- =====================================================
CREATE TABLE ruoli (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(50) NOT NULL UNIQUE,
  descrizione TEXT,
  livello INT NOT NULL COMMENT '1=Utente, 2=Bagnino, 3=Ufficio, 4=Admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELLA: profili
-- =====================================================
CREATE TABLE profili (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  ruolo_id INT NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nome VARCHAR(100) NOT NULL,
  cognome VARCHAR(100) NOT NULL,
  telefono VARCHAR(20),
  data_nascita DATE,
  indirizzo VARCHAR(255),
  citta VARCHAR(100),
  cap VARCHAR(10),
  codice_fiscale VARCHAR(16) UNIQUE,
  note TEXT,
  attivo BOOLEAN DEFAULT TRUE,
  email_verificata BOOLEAN DEFAULT FALSE,
  ultimo_accesso TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ruolo_id) REFERENCES ruoli(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_profili_email ON profili(email);
CREATE INDEX idx_profili_ruolo ON profili(ruolo_id);
CREATE INDEX idx_profili_attivo ON profili(attivo);

-- =====================================================
-- TABELLA: tipi_documento
-- =====================================================
CREATE TABLE tipi_documento (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  descrizione TEXT,
  obbligatorio BOOLEAN DEFAULT FALSE,
  template_url VARCHAR(500),
  ordine INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELLA: documenti_utente
-- =====================================================
CREATE TABLE documenti_utente (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  tipo_documento_id INT NOT NULL,
  file_url VARCHAR(500),
  file_name VARCHAR(255),
  stato ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  note_revisione TEXT,
  data_caricamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  data_revisione TIMESTAMP NULL,
  revisionato_da CHAR(36) NULL,
  scadenza DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  FOREIGN KEY (tipo_documento_id) REFERENCES tipi_documento(id) ON DELETE RESTRICT,
  FOREIGN KEY (revisionato_da) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_documenti_user ON documenti_utente(user_id);
CREATE INDEX idx_documenti_tipo ON documenti_utente(tipo_documento_id);
CREATE INDEX idx_documenti_stato ON documenti_utente(stato);

-- =====================================================
-- TABELLA: pacchetti
-- =====================================================
CREATE TABLE pacchetti (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  descrizione TEXT,
  num_ingressi INT NOT NULL,
  prezzo DECIMAL(10,2) NOT NULL,
  validita_giorni INT NOT NULL COMMENT 'Giorni di validità dal momento della conferma',
  attivo BOOLEAN DEFAULT TRUE,
  ordine INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELLA: acquisti
-- =====================================================
CREATE TABLE acquisti (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  pacchetto_id INT NOT NULL,
  data_acquisto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  metodo_pagamento ENUM('bonifico', 'contanti', 'carta', 'paypal', 'stripe') DEFAULT 'bonifico',
  stato_pagamento ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
  riferimento_pagamento VARCHAR(255),
  note_pagamento TEXT,
  qr_code VARCHAR(255) UNIQUE COMMENT 'Formato: PSC-{id}-{timestamp}',
  ingressi_rimanenti INT NOT NULL,
  data_scadenza DATE NULL,
  confermato_da CHAR(36) NULL,
  data_conferma TIMESTAMP NULL,
  importo_pagato DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  FOREIGN KEY (pacchetto_id) REFERENCES pacchetti(id) ON DELETE RESTRICT,
  FOREIGN KEY (confermato_da) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_acquisti_user ON acquisti(user_id);
CREATE INDEX idx_acquisti_qr ON acquisti(qr_code);
CREATE INDEX idx_acquisti_stato ON acquisti(stato_pagamento);
CREATE INDEX idx_acquisti_scadenza ON acquisti(data_scadenza);

-- =====================================================
-- TABELLA: prenotazioni
-- =====================================================
CREATE TABLE prenotazioni (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  acquisto_id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  data_turno DATE NOT NULL,
  giorno_settimana ENUM('lunedi', 'mercoledi', 'venerdi') NOT NULL,
  fascia_oraria ENUM('mattina', 'pomeriggio') NOT NULL,
  orario_inizio TIME NOT NULL,
  orario_fine TIME NOT NULL,
  stato ENUM('confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
  note TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (acquisto_id) REFERENCES acquisti(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_prenotazioni_user ON prenotazioni(user_id);
CREATE INDEX idx_prenotazioni_data ON prenotazioni(data_turno);
CREATE INDEX idx_prenotazioni_stato ON prenotazioni(stato);
CREATE UNIQUE INDEX idx_prenotazioni_unique ON prenotazioni(user_id, data_turno, fascia_oraria);

-- =====================================================
-- TABELLA: check_ins
-- =====================================================
CREATE TABLE check_ins (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  prenotazione_id CHAR(36) NULL,
  acquisto_id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  bagnino_id CHAR(36) NOT NULL,
  fascia_oraria ENUM('mattina', 'pomeriggio') NOT NULL,
  note TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (prenotazione_id) REFERENCES prenotazioni(id) ON DELETE SET NULL,
  FOREIGN KEY (acquisto_id) REFERENCES acquisti(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  FOREIGN KEY (bagnino_id) REFERENCES profili(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_checkins_user ON check_ins(user_id);
CREATE INDEX idx_checkins_acquisto ON check_ins(acquisto_id);
CREATE INDEX idx_checkins_timestamp ON check_ins(timestamp);
CREATE INDEX idx_checkins_bagnino ON check_ins(bagnino_id);

-- =====================================================
-- TABELLA: comunicazioni
-- =====================================================
CREATE TABLE comunicazioni (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  titolo VARCHAR(255) NOT NULL,
  contenuto TEXT NOT NULL,
  tipo ENUM('notifica', 'email', 'sms', 'sistema') DEFAULT 'notifica',
  priorita ENUM('bassa', 'normale', 'alta', 'urgente') DEFAULT 'normale',
  destinatari JSON COMMENT 'Array di user_id o ruoli',
  mittente_id CHAR(36) NOT NULL,
  data_invio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  allegati JSON COMMENT 'Array di URL file',
  stato ENUM('bozza', 'inviata', 'programmata') DEFAULT 'bozza',
  data_programmata TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (mittente_id) REFERENCES profili(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_comunicazioni_mittente ON comunicazioni(mittente_id);
CREATE INDEX idx_comunicazioni_stato ON comunicazioni(stato);

-- =====================================================
-- TABELLA: contenuti_sito
-- =====================================================
CREATE TABLE contenuti_sito (
  id INT PRIMARY KEY AUTO_INCREMENT,
  sezione VARCHAR(100) NOT NULL,
  chiave VARCHAR(100) NOT NULL,
  valore_testo TEXT,
  valore_html LONGTEXT,
  tipo_campo ENUM('text', 'textarea', 'html', 'image', 'url') DEFAULT 'text',
  modificabile BOOLEAN DEFAULT TRUE,
  ordine INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_sezione_chiave (sezione, chiave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_contenuti_sezione ON contenuti_sito(sezione);

-- =====================================================
-- TABELLA: gallery
-- =====================================================
CREATE TABLE gallery (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  titolo VARCHAR(255),
  descrizione TEXT,
  file_url VARCHAR(500) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  tipo_file VARCHAR(50),
  dimensione_kb INT,
  ordine INT DEFAULT 0,
  visibile BOOLEAN DEFAULT TRUE,
  caricato_da CHAR(36) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (caricato_da) REFERENCES profili(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_gallery_visibile ON gallery(visibile);
CREATE INDEX idx_gallery_ordine ON gallery(ordine);

-- =====================================================
-- TABELLA: activity_log
-- =====================================================
CREATE TABLE activity_log (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id CHAR(36) NULL,
  azione VARCHAR(100) NOT NULL,
  descrizione TEXT,
  tabella_riferimento VARCHAR(100),
  record_id VARCHAR(100),
  ip_address VARCHAR(45),
  user_agent TEXT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_timestamp ON activity_log(timestamp);
CREATE INDEX idx_activity_azione ON activity_log(azione);

-- =====================================================
-- INSERIMENTO DATI INIZIALI
-- =====================================================

-- Ruoli
INSERT INTO ruoli (nome, descrizione, livello) VALUES
('utente', 'Utente base che può acquistare pacchetti e prenotare ingressi', 1),
('bagnino', 'Bagnino che gestisce check-in tramite QR code', 2),
('ufficio', 'Personale ufficio per gestione documenti e pagamenti', 3),
('admin', 'Amministratore con accesso completo', 4);

-- Tipi documento (5 obbligatori)
INSERT INTO tipi_documento (nome, descrizione, obbligatorio, template_url, ordine) VALUES
('Modulo Iscrizione', 'Modulo di iscrizione compilato e firmato', TRUE, '/assets/templates/modulo-iscrizione.pdf', 1),
('Certificato Medico', 'Certificato medico sportivo non agonistico in corso di validità', TRUE, '/assets/templates/certificato-medico-info.pdf', 2),
('Regolamento Interno', 'Regolamento piscina letto e accettato', TRUE, '/assets/templates/regolamento.pdf', 3),
('Privacy GDPR', 'Informativa privacy e consenso trattamento dati', TRUE, '/assets/templates/privacy-gdpr.pdf', 4),
('Documento Identità', 'Carta identità o patente in corso di validità', TRUE, NULL, 5);

-- Pacchetti
INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine) VALUES
('Ingresso Singolo', 'Un singolo ingresso valido per 30 giorni', 1, 12.00, 30, TRUE, 1),
('Pacchetto 10 Ingressi', '10 ingressi validi per 180 giorni (6 mesi)', 10, 100.00, 180, TRUE, 2),
('Promo Gennaio 2026', 'Pacchetto promozionale con 3 lezioni gratuite - valido fino al 31/08/2026', 3, 30.00, 240, TRUE, 3);

-- Profili test (password: "password123" per tutti)
-- Hash bcrypt generato con cost 10
SET @password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Admin
INSERT INTO profili (id, ruolo_id, email, password_hash, nome, cognome, telefono, codice_fiscale, attivo, email_verificata) 
VALUES (UUID(), 4, 'admin@piscina.it', @password_hash, 'Andrea', 'Amministratore', '3331234567', 'MMNNDR85M01H501Z', TRUE, TRUE);

-- Ufficio
INSERT INTO profili (id, ruolo_id, email, password_hash, nome, cognome, telefono, codice_fiscale, attivo, email_verificata) 
VALUES (UUID(), 3, 'ufficio@piscina.it', @password_hash, 'Sofia', 'Rossi', '3339876543', 'RSSSFO90A41H501Y', TRUE, TRUE);

-- Bagnino
INSERT INTO profili (id, ruolo_id, email, password_hash, nome, cognome, telefono, codice_fiscale, attivo, email_verificata) 
VALUES (UUID(), 2, 'bagnino@piscina.it', @password_hash, 'Marco', 'Bianchi', '3335551234', 'BNCMRC88H15H501X', TRUE, TRUE);

-- Utenti
INSERT INTO profili (id, ruolo_id, email, password_hash, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo, email_verificata) 
VALUES 
(UUID(), 1, 'mario.rossi@email.it', @password_hash, 'Mario', 'Rossi', '3331111111', '1985-03-15', 'Via Roma 10', 'Spoltore', '65010', 'RSSMRA85C15H501A', TRUE, TRUE),
(UUID(), 1, 'laura.bianchi@email.it', @password_hash, 'Laura', 'Bianchi', '3332222222', '1990-07-22', 'Via Verdi 25', 'Pescara', '65100', 'BNCLRA90L62G482B', TRUE, TRUE),
(UUID(), 1, 'giuseppe.verdi@email.it', @password_hash, 'Giuseppe', 'Verdi', '3333333333', '1978-11-08', 'Corso Umberto 45', 'Spoltore', '65010', 'VRDGPP78S08H501C', TRUE, TRUE),
(UUID(), 1, 'anna.ferrari@email.it', @password_hash, 'Anna', 'Ferrari', '3334444444', '1995-05-30', 'Via Nazionale 100', 'Pescara', '65100', 'FRRNNA95E70G482D', TRUE, TRUE);

-- Contenuti CMS esempio
INSERT INTO contenuti_sito (sezione, chiave, valore_testo, valore_html, tipo_campo, modificabile, ordine) VALUES
('homepage', 'titolo_hero', 'Benvenuto alla Piscina Naiadi', NULL, 'text', TRUE, 1),
('homepage', 'sottotitolo_hero', 'Nuoto libero per tutti - Lun, Mer, Ven', NULL, 'text', TRUE, 2),
('homepage', 'descrizione', NULL, '<p>La piscina Naiadi offre sessioni di nuoto libero per adulti e bambini.</p>', 'html', TRUE, 3),
('chi-siamo', 'titolo', 'Chi Siamo', NULL, 'text', TRUE, 1),
('chi-siamo', 'contenuto', NULL, '<p>Siamo un centro sportivo dedicato al benessere attraverso il nuoto.</p>', 'html', TRUE, 2),
('contatti', 'indirizzo', 'Via Federico Fellini 2, Spoltore (PE)', NULL, 'text', TRUE, 1),
('contatti', 'telefono', '123 456 789', NULL, 'text', TRUE, 2),
('contatti', 'email', 'info@glisqualetti.it', NULL, 'text', TRUE, 3);

-- =====================================================
-- FINE SCRIPT
-- =====================================================

-- Verifica creazione
SELECT 'DATABASE CREATO CON SUCCESSO!' AS messaggio;
SELECT TABLE_NAME AS tabelle_create FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'piscina_gestione' ORDER BY TABLE_NAME;

-- Mostra conteggi
SELECT 'Ruoli inseriti:' AS tipo, COUNT(*) AS conteggio FROM ruoli
UNION ALL
SELECT 'Profili creati:', COUNT(*) FROM profili
UNION ALL
SELECT 'Tipi documento:', COUNT(*) FROM tipi_documento
UNION ALL
SELECT 'Pacchetti disponibili:', COUNT(*) FROM pacchetti
UNION ALL
SELECT 'Contenuti CMS:', COUNT(*) FROM contenuti_sito;