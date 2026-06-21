-- Migration: Update open play system - Doubles only format with partner names
-- Run this if you have existing open play data

USE pandapickle;

-- Update open_play_registrations table
ALTER TABLE open_play_registrations 
DROP COLUMN IF EXISTS number_of_players;

ALTER TABLE open_play_registrations 
DROP COLUMN IF EXISTS play_type;

ALTER TABLE open_play_registrations 
ADD COLUMN IF NOT EXISTS user_name VARCHAR(100) DEFAULT NULL
AFTER user_id;

ALTER TABLE open_play_registrations 
ADD COLUMN IF NOT EXISTS partner_name VARCHAR(100) NOT NULL DEFAULT 'Partner' 
AFTER user_name;

ALTER TABLE open_play_registrations 
ADD COLUMN IF NOT EXISTS payment_method ENUM('cash', 'cashless') NOT NULL DEFAULT 'cash' 
AFTER partner_name;

-- Drop and recreate matches table (doubles only)
DROP TABLE IF EXISTS open_play_matches;

CREATE TABLE open_play_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    match_round INT NOT NULL DEFAULT 1,
    player1_id INT NOT NULL,
    player2_id INT NOT NULL,
    player3_id INT NOT NULL,
    player4_id INT NOT NULL,
    match_status ENUM('pending', 'playing', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES open_play_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (player1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (player3_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (player4_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_round (session_id, match_round)
);
