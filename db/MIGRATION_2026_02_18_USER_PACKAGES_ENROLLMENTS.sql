-- =====================================================
-- Nuoto Libero - MIGRATION_2026_02_18_USER_PACKAGES_ENROLLMENTS
-- Obiettivo:
-- 1) introdurre tabella packages (gestione dinamica admin/ufficio)
-- 2) introdurre flusso iscrizioni pending/approved/rejected
-- 3) forzare pacchetto commerciale unico: 10 ingressi (EUR 110)
-- 4) mantenere compatibilita con tabella legacy pacchetti/acquisti
-- =====================================================

USE nuoto_libero;

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

ALTER TABLE profili
  ADD COLUMN IF NOT EXISTS stato_iscrizione ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER note,
  ADD COLUMN IF NOT EXISTS force_password_change TINYINT(1) NOT NULL DEFAULT 0 AFTER email_verificata;

ALTER TABLE acquisti
  ADD COLUMN IF NOT EXISTS ingressi_totali INT NOT NULL DEFAULT 0 AFTER ingressi_rimanenti;

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
