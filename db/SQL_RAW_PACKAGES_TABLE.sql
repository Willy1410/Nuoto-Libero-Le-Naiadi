-- SQL RAW per phpMyAdmin (richiesta utente)
CREATE TABLE packages (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100),
 description TEXT,
 entries_count INT,
 price DECIMAL(10,2),
 visible BOOLEAN DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO packages (name, description, entries_count, price, visible)
VALUES (
 '10 Ingressi',
 'Pacchetto con iscrizione obbligatoria + tesseramento + 2 ingressi omaggio (validita 60 giorni)',
 10,
 110,
 1
);
