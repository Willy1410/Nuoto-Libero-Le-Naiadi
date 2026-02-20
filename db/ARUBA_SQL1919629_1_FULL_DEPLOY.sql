-- =====================================================
-- Nuoto Libero Le Naiadi
-- ARUBA SINGLE DEPLOY SQL
-- Target DB: Sql1919629_1
-- Generated: 2026-02-20
-- NOTE: import this file in phpMyAdmin selecting database Sql1919629_1
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

USE `Sql1919629_1`;

-- ATTENZIONE: reset completo schema applicativo nel DB selezionato.
-- Eseguire solo se vuoi ripartire da zero.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS
  cms_revisions,
  cms_settings,
  cms_media,
  cms_pages,
  profile_update_requests,
  iscrizioni,
  packages,
  check_ins,
  acquisti,
  documenti_utente,
  moduli,
  contenuti_sito,
  gallery,
  notifiche_email,
  password_reset_tokens,
  impostazioni_operative,
  pacchetti,
  tipi_documento,
  activity_log,
  profili,
  ruoli;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- SOURCE: db/CREATE_DATABASE_FROM_ZERO.sql
-- -----------------------------------------------------
-- =====================================================
-- Nuoto Libero - CREATE DATABASE FROM ZERO
-- Compatibile con XAMPP (MySQL/MariaDB)
-- =====================================================

-- DROP DATABASE IF EXISTS nuoto_libero;
-- CREATE DATABASE nuoto_libero
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
--
-- USE nuoto_libero;

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
  qr_token VARCHAR(96) NULL UNIQUE,
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
  ('Privacy GDPR', 'Consenso privacy', 1, '/privacy.php', 4),
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
  qr_code VARCHAR(120) NULL,
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
CREATE INDEX idx_acquisti_qr_code ON acquisti(qr_code);
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
-- 9) RICHIESTE MODIFICA PROFILO
-- =====================================================
CREATE TABLE profile_update_requests (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  requested_changes_json LONGTEXT NOT NULL,
  current_snapshot_json LONGTEXT NULL,
  review_note TEXT NULL,
  reviewed_by CHAR(36) NULL,
  reviewed_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_update_requests_user FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  CONSTRAINT fk_profile_update_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_profile_update_requests_user ON profile_update_requests(user_id);
CREATE INDEX idx_profile_update_requests_status ON profile_update_requests(status);
CREATE INDEX idx_profile_update_requests_created_at ON profile_update_requests(created_at);

-- =====================================================
-- 10) IMPOSTAZIONI OPERATIVE SCANNER
-- =====================================================
CREATE TABLE impostazioni_operative (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  scanner_enabled TINYINT(1) NOT NULL DEFAULT 1,
  h24_mode TINYINT(1) NOT NULL DEFAULT 0,
  schedule_json LONGTEXT NULL,
  updated_by CHAR(36) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_impostazioni_operative_updated_by FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO impostazioni_operative (id, scanner_enabled, h24_mode, schedule_json, updated_by)
VALUES (
  1,
  1,
  0,
  '[{\"day\":1,\"start\":\"06:30\",\"end\":\"09:00\"},{\"day\":1,\"start\":\"13:00\",\"end\":\"14:00\"},{\"day\":3,\"start\":\"06:30\",\"end\":\"09:00\"},{\"day\":3,\"start\":\"13:00\",\"end\":\"14:00\"},{\"day\":5,\"start\":\"06:30\",\"end\":\"09:00\"},{\"day\":5,\"start\":\"13:00\",\"end\":\"14:00\"}]',
  NULL
);

-- =====================================================
-- 11) TOKEN RESET PASSWORD
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
-- 12) LOG NOTIFICHE EMAIL
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
-- 13) CONTENUTI CMS (supporto)
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
-- 14) GALLERY (supporto)
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
-- 15) MODULI SCARICABILI CMS
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

INSERT INTO profili (id, ruolo_id, email, qr_token, password_hash, nome, cognome, telefono, codice_fiscale, attivo, email_verificata)
VALUES
  (UUID(), 5, 'admin@piscina.it', 'qr_admin_piscina_0001', @pwd, 'Andrea', 'Admin', '3331234567', 'ADMNDR85M01H501Z', 1, 1),
  (UUID(), 3, 'ufficio@piscina.it', 'qr_ufficio_piscina_0002', @pwd, 'Sofia', 'Rossi', '3339876543', 'RSSSFO90A41H501Y', 1, 1),
  (UUID(), 2, 'bagnino@piscina.it', 'qr_bagnino_piscina_0003', @pwd, 'Marco', 'Bianchi', '3335551234', 'BNCMRC88H15H501X', 1, 1),
  (UUID(), 1, 'mario.rossi@email.it', 'qr_mario_rossi_0004', @pwd, 'Mario', 'Rossi', '3331111111', 'RSSMRA85C15H501A', 1, 1),
  (UUID(), 1, 'laura.bianchi@email.it', 'qr_laura_bianchi_0005', @pwd, 'Laura', 'Bianchi', '3332222222', 'BNCLRA90L62G482B', 1, 1),
  (UUID(), 1, 'giuseppe.verdi@email.it', 'qr_giuseppe_verdi_0006', @pwd, 'Giuseppe', 'Verdi', '3333333333', 'VRDGPP78S08H501C', 1, 1),
  (UUID(), 1, 'anna.ferrari@email.it', 'qr_anna_ferrari_0007', @pwd, 'Anna', 'Ferrari', '3334444444', 'FRRNNA95E70G482D', 1, 1);

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
  (UUID(), @mario_id, 2, 'bonifico', 'confirmed', 'BON-MARIO-001', 'Pagamento test', 'qr_mario_rossi_0004', 8, DATE_ADD(CURDATE(), INTERVAL 70 DAY), @ufficio_id, NOW(), 100.00),
  (UUID(), @laura_id, 2, 'contanti', 'confirmed', 'CASH-LAURA-002', 'Pagamento test', 'qr_laura_bianchi_0005', 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), @ufficio_id, NOW(), 100.00),
  (UUID(), @giuseppe_id, 2, 'bonifico', 'confirmed', 'BON-GIUSEPPE-003', 'Pacchetto esaurito', 'qr_giuseppe_verdi_0006', 0, DATE_SUB(CURDATE(), INTERVAL 5 DAY), @ufficio_id, DATE_SUB(NOW(), INTERVAL 120 DAY), 100.00),
  (UUID(), @anna_id, 4, 'contanti', 'confirmed', 'CASH-ANNA-004', 'Promo attiva', 'qr_anna_ferrari_0007', 3, DATE_ADD(CURDATE(), INTERVAL 75 DAY), @ufficio_id, NOW(), 30.00),
  (UUID(), @anna_id, 2, 'bonifico', 'pending', 'BONIFICO-TEST-20260216', 'In attesa verifica contabile', 'qr_anna_ferrari_0007', 10, DATE_ADD(CURDATE(), INTERVAL 90 DAY), NULL, NULL, 100.00);

SET @acq_mario = (SELECT id FROM acquisti WHERE user_id = @mario_id AND qr_code = 'qr_mario_rossi_0004' LIMIT 1);
SET @acq_laura = (SELECT id FROM acquisti WHERE user_id = @laura_id AND qr_code = 'qr_laura_bianchi_0005' LIMIT 1);

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
  ('contatti', 'telefono', '+39 331 1931 737', 'text', 3);

-- Verifica finale
SELECT 'Database nuoto_libero creato con successo' AS esito;
SELECT 'ruoli' AS tabella, COUNT(*) AS totale FROM ruoli
UNION ALL SELECT 'profili', COUNT(*) FROM profili
UNION ALL SELECT 'pacchetti', COUNT(*) FROM pacchetti
UNION ALL SELECT 'acquisti', COUNT(*) FROM acquisti
UNION ALL SELECT 'check_ins', COUNT(*) FROM check_ins
UNION ALL SELECT 'documenti_utente', COUNT(*) FROM documenti_utente
UNION ALL SELECT 'moduli', COUNT(*) FROM moduli;


-- -----------------------------------------------------
-- SOURCE: db/MIGRATION_2026_02_17_STORAGE_DB.sql
-- -----------------------------------------------------
-- =====================================================
-- Nuoto Libero - MIGRATION_2026_02_17_STORAGE_DB
-- Obiettivo:
-- 1) salvare upload documenti/moduli direttamente su DB
-- 2) rimuovere dipendenza da path hardcoded /nuoto-libero
-- =====================================================
USE `Sql1919629_1`;
-- Nota compatibilita Aruba:
-- le colonne file_mime/file_size/file_blob/file_data sono gia presenti
-- nello schema base (CREATE_DATABASE_FROM_ZERO.sql), quindi qui non servono ALTER.

-- Normalizzazione opzionale path legacy
UPDATE documenti_utente
SET file_url = REPLACE(file_url, '/nuoto-libero/uploads/', 'uploads/')
WHERE file_url LIKE '%/nuoto-libero/uploads/%';


-- -----------------------------------------------------
-- SOURCE: db/MIGRATION_2026_02_18_CMS_BUILDER_READY.sql
-- -----------------------------------------------------
-- =====================================================
-- Nuoto Libero - MIGRATION_2026_02_18_CMS_BUILDER_READY
-- Predisposizione CMS headless compatibile Builder.io
-- =====================================================
USE `Sql1919629_1`;
CREATE TABLE IF NOT EXISTS cms_pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(160) NOT NULL,
  title VARCHAR(200) NOT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  content_json LONGTEXT NOT NULL,
  version_num INT UNSIGNED NOT NULL DEFAULT 1,
  updated_by CHAR(36) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  published_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cms_pages_slug (slug),
  KEY idx_cms_pages_status (status),
  KEY idx_cms_pages_updated_at (updated_at),
  CONSTRAINT fk_cms_pages_updated_by FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('image', 'file') NOT NULL,
  storage_driver VARCHAR(30) NOT NULL DEFAULT 'local',
  file_path VARCHAR(500) NOT NULL,
  public_url VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime VARCHAR(120) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  sha256 CHAR(64) NULL,
  uploaded_by CHAR(36) NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cms_media_type_uploaded (type, uploaded_at),
  CONSTRAINT fk_cms_media_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id BIGINT UNSIGNED NOT NULL,
  version_num INT UNSIGNED NOT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  content_json LONGTEXT NOT NULL,
  created_by CHAR(36) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cms_revisions_page_version (page_id, version_num),
  CONSTRAINT fk_cms_revisions_page FOREIGN KEY (page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
  CONSTRAINT fk_cms_revisions_created_by FOREIGN KEY (created_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  value_json LONGTEXT NOT NULL,
  updated_by CHAR(36) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cms_settings_key (setting_key),
  CONSTRAINT fk_cms_settings_updated_by FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- SOURCE: db/MIGRATION_2026_02_18_USER_PACKAGES_ENROLLMENTS.sql
-- -----------------------------------------------------
-- =====================================================
-- Nuoto Libero - MIGRATION_2026_02_18_USER_PACKAGES_ENROLLMENTS
-- Obiettivo:
-- 1) introdurre tabella packages (gestione dinamica admin/ufficio)
-- 2) introdurre flusso iscrizioni pending/approved/rejected
-- 3) forzare pacchetto commerciale unico: 10 ingressi (EUR 110)
-- 4) mantenere compatibilita con tabella legacy pacchetti/acquisti
-- =====================================================
USE `Sql1919629_1`;
CREATE TABLE IF NOT EXISTS packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  entries_count INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  legacy_pacchetto_id INT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_packages_legacy_pacchetto FOREIGN KEY (legacy_pacchetto_id) REFERENCES pacchetti(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nota compatibilita Aruba:
-- stato_iscrizione e force_password_change sono gia presenti nello schema base.

ALTER TABLE acquisti
  ADD COLUMN ingressi_totali INT NOT NULL DEFAULT 0 AFTER ingressi_rimanenti;

UPDATE acquisti a
JOIN pacchetti p ON p.id = a.pacchetto_id
SET a.ingressi_totali = CASE
  WHEN a.ingressi_totali IS NULL OR a.ingressi_totali <= 0 THEN p.num_ingressi
  ELSE a.ingressi_totali
END;

UPDATE profili
SET stato_iscrizione = 'approved'
WHERE stato_iscrizione IS NULL OR stato_iscrizione = '';

CREATE TABLE IF NOT EXISTS iscrizioni (
  id CHAR(36) PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  cognome VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  telefono VARCHAR(30) NULL,
  data_nascita DATE NULL,
  indirizzo VARCHAR(255) NULL,
  citta VARCHAR(100) NULL,
  cap VARCHAR(10) NULL,
  codice_fiscale VARCHAR(16) NULL,
  note TEXT NULL,
  requested_package_id INT NULL,
  stato ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  note_revisione TEXT NULL,
  approvato_user_id CHAR(36) NULL,
  revisionato_da CHAR(36) NULL,
  reviewed_at DATETIME NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_iscrizioni_requested_package FOREIGN KEY (requested_package_id) REFERENCES packages(id) ON DELETE SET NULL,
  CONSTRAINT fk_iscrizioni_approvato_user FOREIGN KEY (approvato_user_id) REFERENCES profili(id) ON DELETE SET NULL,
  CONSTRAINT fk_iscrizioni_revisionato_da FOREIGN KEY (revisionato_da) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @legacy_package_id := (
  SELECT id
  FROM pacchetti
  WHERE nome = '10 Ingressi'
  ORDER BY id ASC
  LIMIT 1
);

INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine)
SELECT
  '10 Ingressi',
  'Pacchetto con iscrizione obbligatoria + tesseramento + 2 ingressi omaggio (validita 60 giorni)',
  10,
  110.00,
  365,
  1,
  1
WHERE @legacy_package_id IS NULL;

SET @legacy_package_id := (
  SELECT id
  FROM pacchetti
  WHERE nome = '10 Ingressi'
  ORDER BY id ASC
  LIMIT 1
);

UPDATE pacchetti
SET
  nome = '10 Ingressi',
  descrizione = 'Pacchetto con iscrizione obbligatoria + tesseramento + 2 ingressi omaggio (validita 60 giorni)',
  num_ingressi = 10,
  prezzo = 110.00,
  validita_giorni = 365,
  attivo = 1,
  ordine = 1
WHERE id = @legacy_package_id;

UPDATE acquisti
SET pacchetto_id = @legacy_package_id
WHERE pacchetto_id <> @legacy_package_id;

DELETE FROM pacchetti
WHERE id <> @legacy_package_id;

DELETE FROM packages;

INSERT INTO packages (name, description, entries_count, price, visible, legacy_pacchetto_id)
VALUES (
  '10 Ingressi',
  'Pacchetto con iscrizione obbligatoria + tesseramento + 2 ingressi omaggio (validita 60 giorni)',
  10,
  110.00,
  1,
  @legacy_package_id
);


-- -----------------------------------------------------
-- SOURCE: db/MIGRATION_2026_02_19_PROFILE_UPDATE_REQUESTS.sql
-- -----------------------------------------------------
-- =====================================================
-- MIGRATION 2026-02-19
-- Richieste modifica dati profilo (utente -> ufficio/admin)
-- =====================================================
USE `Sql1919629_1`;
CREATE TABLE IF NOT EXISTS profile_update_requests (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  requested_changes_json LONGTEXT NOT NULL,
  current_snapshot_json LONGTEXT NULL,
  review_note TEXT NULL,
  reviewed_by CHAR(36) NULL,
  reviewed_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_update_requests_user FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  CONSTRAINT fk_profile_update_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @idx_user_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'profile_update_requests'
    AND INDEX_NAME = 'idx_profile_update_requests_user'
);
SET @idx_user_sql := IF(@idx_user_exists = 0, 'CREATE INDEX idx_profile_update_requests_user ON profile_update_requests(user_id)', 'SELECT 1');
PREPARE stmt_idx_user FROM @idx_user_sql;
EXECUTE stmt_idx_user;
DEALLOCATE PREPARE stmt_idx_user;

SET @idx_status_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'profile_update_requests'
    AND INDEX_NAME = 'idx_profile_update_requests_status'
);
SET @idx_status_sql := IF(@idx_status_exists = 0, 'CREATE INDEX idx_profile_update_requests_status ON profile_update_requests(status)', 'SELECT 1');
PREPARE stmt_idx_status FROM @idx_status_sql;
EXECUTE stmt_idx_status;
DEALLOCATE PREPARE stmt_idx_status;

SET @idx_created_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'profile_update_requests'
    AND INDEX_NAME = 'idx_profile_update_requests_created_at'
);
SET @idx_created_sql := IF(@idx_created_exists = 0, 'CREATE INDEX idx_profile_update_requests_created_at ON profile_update_requests(created_at)', 'SELECT 1');
PREPARE stmt_idx_created FROM @idx_created_sql;
EXECUTE stmt_idx_created;
DEALLOCATE PREPARE stmt_idx_created;


-- -----------------------------------------------------
-- SOURCE: db/MIGRATION_2026_02_19_OPERATIONAL_SETTINGS.sql
-- -----------------------------------------------------
-- MIGRATION: impostazioni operative scanner QR
-- Data: 2026-02-19
USE `Sql1919629_1`;
CREATE TABLE IF NOT EXISTS impostazioni_operative (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  scanner_enabled TINYINT(1) NOT NULL DEFAULT 1,
  h24_mode TINYINT(1) NOT NULL DEFAULT 0,
  schedule_json LONGTEXT NULL,
  updated_by CHAR(36) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_impostazioni_operative_updated_by
    FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO impostazioni_operative (id, scanner_enabled, h24_mode, schedule_json, updated_by)
VALUES (
  1,
  1,
  0,
  '[{"day":1,"start":"06:30","end":"09:00"},{"day":1,"start":"13:00","end":"14:00"},{"day":3,"start":"06:30","end":"09:00"},{"day":3,"start":"13:00","end":"14:00"},{"day":5,"start":"06:30","end":"09:00"},{"day":5,"start":"13:00","end":"14:00"}]',
  NULL
)
ON DUPLICATE KEY UPDATE
  scanner_enabled = VALUES(scanner_enabled),
  h24_mode = VALUES(h24_mode),
  schedule_json = VALUES(schedule_json);


-- -----------------------------------------------------
-- SOURCE: db/MIGRATION_2026_02_19_STATIC_USER_QR_TOKEN.sql
-- -----------------------------------------------------
-- =====================================================
-- MIGRATION 2026-02-19
-- QR statico per utente (token univoco su profili)
-- =====================================================
USE `Sql1919629_1`;
SET @db_name := DATABASE();

-- 1) profili.qr_token (colonna dedicata, unica e persistente)
SET @has_profili_qr_token := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'profili'
    AND COLUMN_NAME = 'qr_token'
);
SET @sql_add_profili_qr_token := IF(
  @has_profili_qr_token = 0,
  'ALTER TABLE profili ADD COLUMN qr_token VARCHAR(96) NULL AFTER email',
  'SELECT 1'
);
PREPARE stmt_add_profili_qr_token FROM @sql_add_profili_qr_token;
EXECUTE stmt_add_profili_qr_token;
DEALLOCATE PREPARE stmt_add_profili_qr_token;

-- 2) backfill token per utenti esistenti senza token
UPDATE profili
SET qr_token = LOWER(CONCAT(REPLACE(UUID(), '-', ''), REPLACE(UUID(), '-', '')))
WHERE qr_token IS NULL OR TRIM(qr_token) = '';

-- 3) vincolo univocita su qr_token
SET @has_unique_profili_qr_token := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'profili'
    AND COLUMN_NAME = 'qr_token'
    AND NON_UNIQUE = 0
);
SET @sql_add_unique_profili_qr_token := IF(
  @has_unique_profili_qr_token = 0,
  'ALTER TABLE profili ADD UNIQUE INDEX uq_profili_qr_token (qr_token)',
  'SELECT 1'
);
PREPARE stmt_add_unique_profili_qr_token FROM @sql_add_unique_profili_qr_token;
EXECUTE stmt_add_unique_profili_qr_token;
DEALLOCATE PREPARE stmt_add_unique_profili_qr_token;

-- 4) rimuove eventuale unique su acquisti.qr_code (deve poter ripetersi per utente)
SET @acquisti_qr_unique_idx := (
  SELECT INDEX_NAME
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'acquisti'
    AND COLUMN_NAME = 'qr_code'
    AND NON_UNIQUE = 0
    AND INDEX_NAME <> 'PRIMARY'
  ORDER BY INDEX_NAME
  LIMIT 1
);
SET @sql_drop_acquisti_qr_unique := IF(
  @acquisti_qr_unique_idx IS NULL,
  'SELECT 1',
  CONCAT('ALTER TABLE acquisti DROP INDEX `', REPLACE(@acquisti_qr_unique_idx, '`', '``'), '`')
);
PREPARE stmt_drop_acquisti_qr_unique FROM @sql_drop_acquisti_qr_unique;
EXECUTE stmt_drop_acquisti_qr_unique;
DEALLOCATE PREPARE stmt_drop_acquisti_qr_unique;

-- 5) indice non-unico su acquisti.qr_code (performance lookup/report)
SET @has_non_unique_acquisti_qr_idx := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'acquisti'
    AND COLUMN_NAME = 'qr_code'
    AND NON_UNIQUE = 1
);
SET @sql_add_non_unique_acquisti_qr_idx := IF(
  @has_non_unique_acquisti_qr_idx = 0,
  'CREATE INDEX idx_acquisti_qr_code ON acquisti(qr_code)',
  'SELECT 1'
);
PREPARE stmt_add_non_unique_acquisti_qr_idx FROM @sql_add_non_unique_acquisti_qr_idx;
EXECUTE stmt_add_non_unique_acquisti_qr_idx;
DEALLOCATE PREPARE stmt_add_non_unique_acquisti_qr_idx;

-- 6) allinea tutti gli acquisti al token statico utente
UPDATE acquisti a
JOIN profili p ON p.id = a.user_id
SET a.qr_code = p.qr_token
WHERE p.qr_token IS NOT NULL
  AND p.qr_token <> ''
  AND (a.qr_code IS NULL OR a.qr_code <> p.qr_token);


