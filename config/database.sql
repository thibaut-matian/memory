

-- Table des joueurs
CREATE TABLE players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des scores
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    pairs INT NOT NULL,
    moves INT NOT NULL,
    time INT NOT NULL,
    score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    INDEX idx_player_id (player_id),
    INDEX idx_score (score DESC),
    INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques données de test (optionnel)
INSERT INTO players (username, email) VALUES 
('TestPlayer1', 'test1@example.com'),
('TestPlayer2', 'test2@example.com'),
('Champion', 'champion@example.com');

INSERT INTO scores (player_id, pairs, moves, time, score) VALUES
(1, 6, 12, 120, 5500),
(1, 8, 16, 180, 6200),
(2, 6, 14, 140, 5100),
(2, 10, 20, 240, 7800),
(3, 12, 24, 300, 9500),
(3, 6, 12, 90, 6800);