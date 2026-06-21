-- Migration: Change matches to use registration_id instead of user_id
-- This allows both walk-in players and account holders to be matched together

USE pandapickle;

-- Drop existing matches table
DROP TABLE IF EXISTS open_play_matches;

-- Create new matches table using registration_id
CREATE TABLE open_play_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    match_round INT NOT NULL DEFAULT 1,
    player1_reg_id INT NOT NULL,
    player2_reg_id INT NOT NULL,
    player3_reg_id INT NOT NULL,
    player4_reg_id INT NOT NULL,
    match_status ENUM('pending', 'playing', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES open_play_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (player1_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player3_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player4_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    INDEX idx_session_round (session_id, match_round)
);
