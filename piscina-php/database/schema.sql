-- =====================================================
-- SCHEMA DATABASE MYSQL - Sistema Gestione Piscina
-- =====================================================
-- Per phpMyAdmin / XAMPP / WAMP
-- MySQL 5.7+ / MariaDB 10.3+
-- =====================================================

-- Crea database
CREATE DATABASE IF NOT EXISTS piscina_gestione CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE piscina_gestione;

-- =====================================================
-- 1. TABELLA RUOLI
-- =====================================================
CREATE TABLE ruoli (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) UNIQUE NOT NULL,
  descrizione TEXT,
  livello INT NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO ruoli (nome, descrizione, livello) VALUES
('utente', 'Utente base: acquista pacchetti, prenota turni, carica documenti', 1),
('bagnino', 'Bagnino: scansiona QR, registra ingressi, visualizza presenze', 2),
('ufficio', 'Ufficio: approva documenti/pagamenti, statistiche, report', 3),
('admin', 'Amministratore: accesso completo, gestione sistema', 4);

-- =====================================================
-- 2. TABELLA UTENTI
-- =====================================================
CREATE TABLE utenti (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  ruolo_id INT NOT NULL DEFAULT 1,
  nome VARCHAR(255),
  cognome VARCHAR(255),
  telefono VARCHAR(20),
  data_nascita DATE,
  indirizzo TEXT,
  citta VARCHAR(100),
  cap VARCHAR(10),
  codice_fiscale VARCHAR(16) UNIQUE,
  note TEXT,
  attivo BOOLEAN DEFAULT TRUE,
  email_verified BOOLEAN DEFAULT FALSE,
  reset_token VARCHAR(100),
  reset_token_expires DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ruolo_id) REFERENCES ruoli(id)
) ENGINE=InnoDB;

CREATE INDEX idx_utenti_email ON utenti(email);
CREATE INDEX idx_utenti_ruolo ON utenti(ruolo_id);

-- =====================================================
-- 3. TABELLA SESSIONI (per gestione login)
-- =====================================================
CREATE TABLE sessioni (
  id VARCHAR(128) PRIMARY KEY,
  user_id INT NOT NULL,
  ip_address VARCHAR(50),
  user_agent TEXT,
  last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 4. TABELLA TIPI DOCUMENTO
-- =====================================================
CREATE TABLE tipi_documento (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) UNIQUE NOT NULL,
  descrizione TEXT,
  obbligatorio BOOLEAN DEFAULT FALSE,
  template_url TEXT,
  ordine INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tipi_documento (nome, descrizione, obbligatorio, ordine) VALUES
('Modulo Iscrizione', 'Modulo di iscrizione compilato e firmato', TRUE, 1),
('Certificato Medico', 'Certificato medico per attività sportiva non agonistica (validità 1 anno)', TRUE, 2),
('Regolamento Interno', 'Regolamento piscina firmato per accettazione', TRUE, 3),
('Privacy GDPR', 'Consenso al trattamento dati personali', TRUE, 4),
('Documento Identità', 'Copia documento identità (carta identità o patente)', TRUE, 5);

-- =====================================================
-- 5. TABELLA DOCUMENTI UTENTE
-- =====================================================
CREATE TABLE documenti_utente (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  tipo_documento_id INT NOT NULL,
  file_path TEXT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  stato ENUM('da_inviare', 'in_attesa', 'approvato', 'rifiutato') DEFAULT 'in_attesa',
  note_revisione TEXT,
  data_caricamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  data_revisione TIMESTAMP NULL,
  revisionato_da INT,
  scadenza DATE,
  FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
  FOREIGN KEY (tipo_documento_id) REFERENCES tipi_documento(id),
  FOREIGN KEY (revisionato_da) REFERENCES utenti(id)
) ENGINE=InnoDB;

CREATE INDEX idx_documenti_user ON documenti_utente(user_id);
CREATE INDEX idx_documenti_stato ON documenti_utente(stato);

-- =====================================================
-- 6. TABELLA PACCHETTI
-- =====================================================
CREATE TABLE pacchetti (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descrizione TEXT,
  num_ingressi INT NOT NULL,
  prezzo DECIMAL(10,2) NOT NULL,
  validita_giorni INT NOT NULL,
  attivo BOOLEAN DEFAULT TRUE,
  ordine INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, ordine) VALUES
('Ingresso Singolo', 'Singolo ingresso valido per una sessione', 1, 12.00, 30, 1),
('10 Ingressi', 'Pacchetto 10 ingressi (risparmio €20)', 10, 100.00, 180, 2),
('Promo Iscrizione', 'Iscrizione società + 3 lezioni omaggio', 3, 30.00, 180, 3);

-- =====================================================
-- 7. TABELLA ACQUISTI
-- =====================================================
CREATE TABLE acquisti (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  pacchetto_id INT NOT NULL,
  data_acquisto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  metodo_pagamento ENUM('bonifico', 'contanti', 'carta'),
  stato_pagamento ENUM('in_attesa', 'confermato', 'rifiutato') DEFAULT 'in_attesa',
  riferimento_pagamento TEXT,
  note_pagamento TEXT,
  qr_code VARCHAR(100) UNIQUE,
  ingressi_rimanenti INT,
  data_scadenza DATE,
  confermato_da INT,
  data_conferma TIMESTAMP NULL,
  importo_pagato DECIMAL(10,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
  FOREIGN KEY (pacchetto_id) REFERENCES pacchetti(id),
  FOREIGN KEY (confermato_da) REFERENCES utenti(id)
) ENGINE=InnoDB;

CREATE INDEX idx_acquisti_user ON acquisti(user_id);
CREATE INDEX idx_acquisti_qr ON acquisti(qr_code);
CREATE INDEX idx_acquisti_stato ON acquisti(stato_pagamento);

-- =====================================================
-- 8. TABELLA PRENOTAZIONI
-- =====================================================
CREATE TABLE prenotazioni (
  id INT AUTO_INCREMENT PRIMARY KEY,
  acquisto_id INT NOT NULL,
  user_id INT NOT NULL,
  data_turno DATE NOT NULL,
  giorno_settimana ENUM('lunedi', 'mercoledi', 'venerdi'),
  fascia_oraria ENUM('mattina', 'pomeriggio', 'sera'),
  orario_inizio TIME NOT NULL,
  orario_fine TIME NOT NULL,
  stato ENUM('confermata', 'completata', 'cancellata', 'non_presentato') DEFAULT 'confermata',
  note TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (acquisto_id) REFERENCES acquisti(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_prenotazioni_user ON prenotazioni(user_id);
CREATE INDEX idx_prenotazioni_data ON prenotazioni(data_turno);
CREATE INDEX idx_prenotazioni_stato ON prenotazioni(stato);

-- =====================================================
-- 9. TABELLA CHECK-INS
-- =====================================================
CREATE TABLE check_ins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prenotazione_id INT,
  acquisto_id INT NOT NULL,
  user_id INT NOT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  bagnino_id INT NOT NULL,
  fascia_oraria ENUM('mattina', 'pomeriggio'),
  note TEXT,
  FOREIGN KEY (prenotazione_id) REFERENCES prenotazioni(id),
  FOREIGN KEY (acquisto_id) REFERENCES acquisti(id),
  FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
  FOREIGN KEY (bagnino_id) REFERENCES utenti(id)
) ENGINE=InnoDB;

CREATE INDEX idx_checkins_user ON check_ins(user_id);
CREATE INDEX idx_checkins_timestamp ON check_ins(timestamp);
CREATE INDEX idx_checkins_bagnino ON check_ins(bagnino_id);

-- =====================================================
-- 10. TABELLA COMUNICAZIONI
-- =====================================================
CREATE TABLE comunicazioni (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titolo VARCHAR(255) NOT NULL,
  messaggio TEXT NOT NULL,
  tipo ENUM('info', 'avviso', 'urgente', 'manutenzione'),
  destinatari ENUM('tutti', 'utenti', 'staff'),
  priorita INT DEFAULT 1 CHECK (priorita BETWEEN 1 AND 3),
  pubblicata BOOLEAN DEFAULT FALSE,
  data_inizio TIMESTAMP,
  data_fine TIMESTAMP,
  creata_da INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (creata_da) REFERENCES utenti(id)
) ENGINE=InnoDB;

CREATE INDEX idx_comunicazioni_pubblicata ON comunicazioni(pubblicata);
CREATE INDEX idx_comunicazioni_destinatari ON comunicazioni(destinatari);

-- =====================================================
-- 11. TABELLA CONTENUTI SITO (CMS)
-- =====================================================
CREATE TABLE contenuti_sito (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sezione VARCHAR(50) NOT NULL,
  chiave VARCHAR(100) UNIQUE NOT NULL,
  valore TEXT,
  tipo ENUM('text', 'html', 'image', 'url'),
  ordine INT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_contenuti_sezione ON contenuti_sito(sezione);

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

-- =====================================================
-- 12. TABELLA GALLERY
-- =====================================================
CREATE TABLE gallery (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titolo VARCHAR(255),
  descrizione TEXT,
  image_path TEXT NOT NULL,
  ordine INT,
  visibile BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_gallery_visibile ON gallery(visibile);

-- =====================================================
-- 13. TABELLA ACTIVITY LOG
-- =====================================================
CREATE TABLE activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  azione VARCHAR(100) NOT NULL,
  entita VARCHAR(50),
  entita_id INT,
  dettagli JSON,
  ip_address VARCHAR(50),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_timestamp ON activity_log(timestamp);
CREATE INDEX idx_activity_azione ON activity_log(azione);

-- =====================================================
-- DATI SEED - Utenti Test
-- =====================================================

-- Password hash per tutti: password123 (bcrypt cost 10)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Admin
INSERT INTO utenti (email, password_hash, ruolo_id, nome, cognome, telefono, codice_fiscale, attivo, email_verified) VALUES
('admin@piscina.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 'Amministratore', 'Sistema', '+39 123 456 789', 'ADMSST80A01H501Z', TRUE, TRUE);

-- Ufficio
INSERT INTO utenti (email, password_hash, ruolo_id, nome, cognome, telefono, codice_fiscale, attivo, email_verified) VALUES
('ufficio@piscina.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'Maria', 'Bianchi', '+39 345 678 901', 'BNCMRA85E55H501X', TRUE, TRUE);

-- Bagnino
INSERT INTO utenti (email, password_hash, ruolo_id, nome, cognome, telefono, codice_fiscale, attivo, email_verified) VALUES
('bagnino@piscina.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'Luca', 'Verdi', '+39 333 222 111', 'VRDLCU90M20H501K', TRUE, TRUE);

-- Utenti test
INSERT INTO utenti (email, password_hash, ruolo_id, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo, email_verified) VALUES
('mario.rossi@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Mario', 'Rossi', '+39 333 123 4567', '1985-08-01', 'Via Roma, 123', 'Pescara', '65100', 'RSSMRA85M01H501Z', TRUE, TRUE),
('laura.bianchi@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Laura', 'Bianchi', '+39 345 987 6543', '1990-04-05', 'Via Garibaldi, 45', 'Spoltore', '65010', 'BNCLRA90D45H501K', TRUE, TRUE),
('giuseppe.verdi@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Giuseppe', 'Verdi', '+39 338 555 1234', '1980-01-01', 'Corso Umberto, 78', 'Pescara', '65100', 'VRDGPP80A01H501M', TRUE, TRUE),
('anna.ferrari@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Anna', 'Ferrari', '+39 340 777 8899', '1988-12-08', 'Via Venezia, 56', 'Montesilvano', '65016', 'FRRNN88T48H501R', TRUE, TRUE);

-- =====================================================
-- ACQUISTI TEST
-- =====================================================

-- Mario Rossi: 10 ingressi (8 rimanenti)
INSERT INTO acquisti (user_id, pacchetto_id, metodo_pagamento, stato_pagamento, qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato) VALUES
(4, 2, 'bonifico', 'confermato', 'PSC-MARIO-001', 8, DATE_ADD(CURDATE(), INTERVAL 150 DAY), 2, NOW(), 100.00);

-- Laura Bianchi: 10 ingressi (2 rimanenti, in scadenza)
INSERT INTO acquisti (user_id, pacchetto_id, metodo_pagamento, stato_pagamento, qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato) VALUES
(5, 2, 'contanti', 'confermato', 'PSC-LAURA-002', 2, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 2, NOW(), 100.00);

-- Giuseppe Verdi: 10 ingressi (0 rimanenti, ESAURITO)
INSERT INTO acquisti (user_id, pacchetto_id, metodo_pagamento, stato_pagamento, qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato) VALUES
(6, 2, 'bonifico', 'confermato', 'PSC-GIUSEPPE-003', 0, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 2, DATE_SUB(NOW(), INTERVAL 180 DAY), 100.00);

-- Anna Ferrari: Promo (3 ingressi)
INSERT INTO acquisti (user_id, pacchetto_id, metodo_pagamento, stato_pagamento, qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato) VALUES
(7, 3, 'contanti', 'confermato', 'PSC-ANNA-004', 3, DATE_ADD(CURDATE(), INTERVAL 170 DAY), 2, NOW(), 30.00);

-- Anna Ferrari: Acquisto IN ATTESA
INSERT INTO acquisti (user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento, importo_pagato) VALUES
(7, 2, 'bonifico', 'in_attesa', 'BONIFICO-20260215-001', 'Pagamento effettuato il 13/02/2026', 100.00);

-- =====================================================
-- COMUNICAZIONI TEST
-- =====================================================

INSERT INTO comunicazioni (titolo, messaggio, tipo, destinatari, priorita, pubblicata, data_inizio, creata_da) VALUES
('⚠️ Chiusura straordinaria 20 febbraio', 'La piscina resterà chiusa per manutenzione straordinaria il giorno 20 febbraio 2026.', 'urgente', 'tutti', 3, TRUE, NOW(), 1),
('ℹ️ Nuovi orari da marzo', 'Da marzo 2026 verranno attivati nuovi orari pomeridiani anche il martedì e giovedì (15:00-17:00).', 'info', 'utenti', 1, TRUE, NOW(), 1),
('📋 Riunione staff 18 febbraio', 'Tutti i bagnini e il personale di ufficio sono convocati per una riunione operativa il 18 febbraio alle ore 14:00.', 'avviso', 'staff', 2, TRUE, NOW(), 1);

-- =====================================================
-- FINE SCHEMA
-- =====================================================
