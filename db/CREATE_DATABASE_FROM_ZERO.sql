-- =====================================================
-- Nuoto Libero - CREATE DATABASE FROM ZERO
-- Compatibile con XAMPP (MySQL/MariaDB)
-- =====================================================

DROP DATABASE IF EXISTS nuoto_libero;
CREATE DATABASE nuoto_libero
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nuoto_libero;

-- =====================================================
-- 1) RUOLI
-- =====================================================
CREATE TABLE ruoli (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL UNIQUE,
  descrizione TEXT NULL,
  livello INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ruoli (id, nome, descrizione, livello) VALUES
  (1, 'utente', 'Utente cliente', 1),
  (2, 'bagnino', 'Scanner QR e check-in', 2),
  (3, 'ufficio', 'Segreteria pagamenti/documenti', 3),
  (4, 'segreteria', 'Alias segreteria', 3),
  (5, 'admin', 'Accesso completo', 5);

ALTER TABLE ruoli AUTO_INCREMENT = 6;

-- =====================================================
-- 2) PROFILI
-- =====================================================
CREATE TABLE profili (
  id CHAR(36) PRIMARY KEY,
  ruolo_id INT NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nome VARCHAR(100) NOT NULL,
  cognome VARCHAR(100) NOT NULL,
  telefono VARCHAR(30) NULL,
  data_nascita DATE NULL,
  indirizzo VARCHAR(255) NULL,
  citta VARCHAR(100) NULL,
  cap VARCHAR(10) NULL,
  codice_fiscale VARCHAR(16) NULL UNIQUE,
  note TEXT NULL,
  attivo TINYINT(1) NOT NULL DEFAULT 1,
  email_verificata TINYINT(1) NOT NULL DEFAULT 0,
  stato_iscrizione ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  ultimo_accesso DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profili_ruolo FOREIGN KEY (ruolo_id) REFERENCES ruoli(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_profili_ruolo ON profili(ruolo_id);
CREATE INDEX idx_profili_attivo ON profili(attivo);

-- =====================================================
-- 3) TIPI DOCUMENTO
-- =====================================================
CREATE TABLE tipi_documento (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL UNIQUE,
  descrizione TEXT NULL,
  obbligatorio TINYINT(1) NOT NULL DEFAULT 0,
  template_url VARCHAR(500) NULL,
  ordine INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tipi_documento (nome, descrizione, obbligatorio, template_url, ordine) VALUES
  ('Modulo Iscrizione', 'Modulo compilato e firmato', 1, '/assets/modulo-iscrizione.html', 1),
  ('Certificato Medico', 'Certificato medico valido', 1, '/assets/regolamento-piscina.html', 2),
  ('Regolamento Interno', 'Regolamento accettato', 1, '/assets/regolamento-piscina.html', 3),
  ('Privacy GDPR', 'Consenso privacy', 1, '/privacy.html', 4),
  ('Documento Identita', 'Documento identita valido', 1, NULL, 5);

-- =====================================================
-- 4) DOCUMENTI UTENTE
-- =====================================================
CREATE TABLE documenti_utente (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  tipo_documento_id INT NOT NULL,
  file_url VARCHAR(500) NULL,
  file_name VARCHAR(255) NULL,
  file_mime VARCHAR(120) NULL,
  file_size INT UNSIGNED NULL,
  file_blob LONGBLOB NULL,
  stato ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  note_revisione TEXT NULL,
  data_caricamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  data_revisione DATETIME NULL,
  revisionato_da CHAR(36) NULL,
  scadenza DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_documenti_user FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  CONSTRAINT fk_documenti_tipo FOREIGN KEY (tipo_documento_id) REFERENCES tipi_documento(id) ON DELETE RESTRICT,
  CONSTRAINT fk_documenti_revisore FOREIGN KEY (revisionato_da) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_documenti_user ON documenti_utente(user_id);
CREATE INDEX idx_documenti_stato ON documenti_utente(stato);

-- =====================================================
-- 5) PACCHETTI
-- =====================================================
CREATE TABLE pacchetti (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  descrizione TEXT NULL,
  num_ingressi INT NOT NULL,
  prezzo DECIMAL(10,2) NOT NULL,
  validita_giorni INT NOT NULL,
  attivo TINYINT(1) NOT NULL DEFAULT 1,
  ordine INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pacchetti (id, nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine) VALUES
  (1, 'Ingresso Singolo', '1 ingresso con validita 30 giorni', 1, 12.00, 30, 1, 1),
  (2, 'Pacchetto 10 Ingressi', '10 ingressi validi 3 mesi', 10, 100.00, 90, 1, 2),
  (3, 'Pacchetto 20 Ingressi', '20 ingressi validi 3 mesi', 20, 180.00, 90, 1, 3),
  (4, 'Promo 3 Ingressi', 'Pacchetto promozionale valido 3 mesi', 3, 30.00, 90, 1, 4);

ALTER TABLE pacchetti AUTO_INCREMENT = 5;

-- =====================================================
-- 6) ACQUISTI / PAGAMENTI
-- =====================================================
CREATE TABLE acquisti (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  pacchetto_id INT NOT NULL,
  data_acquisto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  metodo_pagamento ENUM('bonifico', 'contanti', 'carta', 'paypal', 'stripe') NOT NULL DEFAULT 'bonifico',
  stato_pagamento ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
  riferimento_pagamento VARCHAR(255) NULL,
  note_pagamento TEXT NULL,
  qr_code VARCHAR(120) NULL UNIQUE,
  ingressi_rimanenti INT NOT NULL,
  data_scadenza DATE NULL,
  confermato_da CHAR(36) NULL,
  data_conferma DATETIME NULL,
  importo_pagato DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_acquisti_user FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  CONSTRAINT fk_acquisti_pacchetto FOREIGN KEY (pacchetto_id) REFERENCES pacchetti(id) ON DELETE RESTRICT,
  CONSTRAINT fk_acquisti_confermato_da FOREIGN KEY (confermato_da) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_acquisti_user ON acquisti(user_id);
CREATE INDEX idx_acquisti_stato ON acquisti(stato_pagamento);
CREATE INDEX idx_acquisti_scadenza ON acquisti(data_scadenza);
CREATE INDEX idx_acquisti_conferma ON acquisti(data_conferma);

-- =====================================================
-- 7) CHECK-INS
-- =====================================================
CREATE TABLE check_ins (
  id CHAR(36) PRIMARY KEY,
  acquisto_id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  bagnino_id CHAR(36) NOT NULL,
  fascia_oraria ENUM('mattina', 'pomeriggio') NOT NULL,
  note TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_checkins_acquisto FOREIGN KEY (acquisto_id) REFERENCES acquisti(id) ON DELETE CASCADE,
  CONSTRAINT fk_checkins_user FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  CONSTRAINT fk_checkins_bagnino FOREIGN KEY (bagnino_id) REFERENCES profili(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_checkins_user ON check_ins(user_id);
CREATE INDEX idx_checkins_timestamp ON check_ins(timestamp);
CREATE INDEX idx_checkins_acquisto_data ON check_ins(acquisto_id, timestamp);

-- =====================================================
-- 8) LOG ATTIVITA
-- =====================================================
CREATE TABLE activity_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id CHAR(36) NULL,
  azione VARCHAR(100) NOT NULL,
  descrizione TEXT NULL,
  tabella_riferimento VARCHAR(100) NULL,
  record_id VARCHAR(100) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_timestamp ON activity_log(timestamp);

-- =====================================================
-- 9) TOKEN RESET PASSWORD
-- =====================================================
CREATE TABLE password_reset_tokens (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  requested_ip VARCHAR(45) NULL,
  requested_user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_reset_user ON password_reset_tokens(user_id);
CREATE INDEX idx_reset_expires ON password_reset_tokens(expires_at);

-- =====================================================
-- 10) LOG NOTIFICHE EMAIL
-- =====================================================
CREATE TABLE notifiche_email (
  id CHAR(36) PRIMARY KEY,
  acquisto_id CHAR(36) NOT NULL,
  tipo ENUM('one_entry', 'expiry_7days') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifiche_acquisto FOREIGN KEY (acquisto_id) REFERENCES acquisti(id) ON DELETE CASCADE,
  UNIQUE KEY uk_notifica_tipo (acquisto_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11) CONTENUTI CMS (supporto)
-- =====================================================
CREATE TABLE contenuti_sito (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sezione VARCHAR(100) NOT NULL,
  chiave VARCHAR(100) NOT NULL,
  valore_testo TEXT NULL,
  valore_html LONGTEXT NULL,
  tipo_campo ENUM('text', 'textarea', 'html', 'image', 'url') DEFAULT 'text',
  modificabile TINYINT(1) NOT NULL DEFAULT 1,
  ordine INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_contenuti (sezione, chiave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12) GALLERY (supporto)
-- =====================================================
CREATE TABLE gallery (
  id CHAR(36) PRIMARY KEY,
  titolo VARCHAR(255) NULL,
  descrizione TEXT NULL,
  file_url VARCHAR(500) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  tipo_file VARCHAR(50) NULL,
  dimensione_kb INT NULL,
  ordine INT DEFAULT 0,
  visibile TINYINT(1) NOT NULL DEFAULT 1,
  caricato_da CHAR(36) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_gallery_user FOREIGN KEY (caricato_da) REFERENCES profili(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13) MODULI SCARICABILI CMS
-- =====================================================
CREATE TABLE moduli (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL,
  nome VARCHAR(150) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime VARCHAR(120) NOT NULL,
  size BIGINT UNSIGNED NOT NULL,
  file_data LONGBLOB NULL,
  version_num INT NOT NULL DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by CHAR(36) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_moduli_updated_by FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_moduli_slug_active ON moduli(slug, is_active);
CREATE INDEX idx_moduli_updated_at ON moduli(updated_at);

-- =====================================================
-- SEED UTENTI TEST (password per tutti: password123)
-- =====================================================
SET @pwd = '$2y$10$qxUYrqnR/jLXoRjbhDGHpOg.v76nOqTvlr18fTrfC5cE1ZvMAmymC';

INSERT INTO profili (id, ruolo_id, email, password_hash, nome, cognome, telefono, codice_fiscale, attivo, email_verificata)
VALUES
  (UUID(), 5, 'admin@piscina.it', @pwd, 'Andrea', 'Admin', '3331234567', 'ADMNDR85M01H501Z', 1, 1),
  (UUID(), 3, 'ufficio@piscina.it', @pwd, 'Sofia', 'Rossi', '3339876543', 'RSSSFO90A41H501Y', 1, 1),
  (UUID(), 2, 'bagnino@piscina.it', @pwd, 'Marco', 'Bianchi', '3335551234', 'BNCMRC88H15H501X', 1, 1),
  (UUID(), 1, 'mario.rossi@email.it', @pwd, 'Mario', 'Rossi', '3331111111', 'RSSMRA85C15H501A', 1, 1),
  (UUID(), 1, 'laura.bianchi@email.it', @pwd, 'Laura', 'Bianchi', '3332222222', 'BNCLRA90L62G482B', 1, 1),
  (UUID(), 1, 'giuseppe.verdi@email.it', @pwd, 'Giuseppe', 'Verdi', '3333333333', 'VRDGPP78S08H501C', 1, 1),
  (UUID(), 1, 'anna.ferrari@email.it', @pwd, 'Anna', 'Ferrari', '3334444444', 'FRRNNA95E70G482D', 1, 1);

SET @admin_id = (SELECT id FROM profili WHERE email = 'admin@piscina.it' LIMIT 1);
SET @ufficio_id = (SELECT id FROM profili WHERE email = 'ufficio@piscina.it' LIMIT 1);
SET @bagnino_id = (SELECT id FROM profili WHERE email = 'bagnino@piscina.it' LIMIT 1);
SET @mario_id = (SELECT id FROM profili WHERE email = 'mario.rossi@email.it' LIMIT 1);
SET @laura_id = (SELECT id FROM profili WHERE email = 'laura.bianchi@email.it' LIMIT 1);
SET @giuseppe_id = (SELECT id FROM profili WHERE email = 'giuseppe.verdi@email.it' LIMIT 1);
SET @anna_id = (SELECT id FROM profili WHERE email = 'anna.ferrari@email.it' LIMIT 1);

-- Acquisti seed
INSERT INTO acquisti (
  id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento,
  qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato
) VALUES
  (UUID(), @mario_id, 2, 'bonifico', 'confirmed', 'BON-MARIO-001', 'Pagamento test', 'NL-MARIO-001', 8, DATE_ADD(CURDATE(), INTERVAL 70 DAY), @ufficio_id, NOW(), 100.00),
  (UUID(), @laura_id, 2, 'contanti', 'confirmed', 'CASH-LAURA-002', 'Pagamento test', 'NL-LAURA-002', 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), @ufficio_id, NOW(), 100.00),
  (UUID(), @giuseppe_id, 2, 'bonifico', 'confirmed', 'BON-GIUSEPPE-003', 'Pacchetto esaurito', 'NL-GIUSEPPE-003', 0, DATE_SUB(CURDATE(), INTERVAL 5 DAY), @ufficio_id, DATE_SUB(NOW(), INTERVAL 120 DAY), 100.00),
  (UUID(), @anna_id, 4, 'contanti', 'confirmed', 'CASH-ANNA-004', 'Promo attiva', 'NL-ANNA-004', 3, DATE_ADD(CURDATE(), INTERVAL 75 DAY), @ufficio_id, NOW(), 30.00),
  (UUID(), @anna_id, 2, 'bonifico', 'pending', 'BONIFICO-TEST-20260216', 'In attesa verifica contabile', 'NL-ANNA-PENDING', 10, DATE_ADD(CURDATE(), INTERVAL 90 DAY), NULL, NULL, 100.00);

SET @acq_mario = (SELECT id FROM acquisti WHERE user_id = @mario_id AND qr_code = 'NL-MARIO-001' LIMIT 1);
SET @acq_laura = (SELECT id FROM acquisti WHERE user_id = @laura_id AND qr_code = 'NL-LAURA-002' LIMIT 1);

-- Check-in seed
INSERT INTO check_ins (id, acquisto_id, user_id, bagnino_id, fascia_oraria, note, timestamp)
VALUES
  (UUID(), @acq_mario, @mario_id, @bagnino_id, 'mattina', 'Ingresso regolare', DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (UUID(), @acq_mario, @mario_id, @bagnino_id, 'pomeriggio', 'Ingresso regolare', DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (UUID(), @acq_laura, @laura_id, @bagnino_id, 'mattina', 'Ingresso regolare', NOW());

-- Documenti seed
INSERT INTO documenti_utente (id, user_id, tipo_documento_id, file_url, file_name, stato, data_caricamento)
VALUES
  (UUID(), @mario_id, 1, '/uploads/documenti/mario/modulo.pdf', 'modulo-mario.pdf', 'approved', DATE_SUB(NOW(), INTERVAL 10 DAY)),
  (UUID(), @mario_id, 2, '/uploads/documenti/mario/certificato.pdf', 'certificato-mario.pdf', 'approved', DATE_SUB(NOW(), INTERVAL 9 DAY)),
  (UUID(), @anna_id, 1, '/uploads/documenti/anna/modulo.pdf', 'modulo-anna.pdf', 'pending', NOW());

-- Contenuti seed minimi
INSERT INTO contenuti_sito (sezione, chiave, valore_testo, tipo_campo, ordine) VALUES
  ('homepage', 'titolo_hero', 'Nuoto Libero alla Piscina Naiadi', 'text', 1),
  ('contatti', 'email', 'info@glisqualetti.it', 'text', 2),
  ('contatti', 'telefono', '+39 320 300 9040', 'text', 3);

-- Verifica finale
SELECT 'Database nuoto_libero creato con successo' AS esito;
SELECT 'ruoli' AS tabella, COUNT(*) AS totale FROM ruoli
UNION ALL SELECT 'profili', COUNT(*) FROM profili
UNION ALL SELECT 'pacchetti', COUNT(*) FROM pacchetti
UNION ALL SELECT 'acquisti', COUNT(*) FROM acquisti
UNION ALL SELECT 'check_ins', COUNT(*) FROM check_ins
UNION ALL SELECT 'documenti_utente', COUNT(*) FROM documenti_utente
UNION ALL SELECT 'moduli', COUNT(*) FROM moduli;
