-- Migration: Add match preference to open play registrations
-- Allows players to choose random matching or play with friends

USE pandapickle;

-- Add match_preference column
ALTER TABLE open_play_registrations 
ADD COLUMN IF NOT EXISTS match_preference ENUM('random', 'friends') NOT NULL DEFAULT 'random'
AFTER partner_name;

-- Add friend_group column for grouping teams that want to play together
ALTER TABLE open_play_registrations 
ADD COLUMN IF NOT EXISTS friend_group VARCHAR(100) DEFAULT NULL
AFTER match_preference;

-- Add game_number column to matches for tracking play order
ALTER TABLE open_play_matches 
ADD COLUMN IF NOT EXISTS game_number INT DEFAULT NULL
AFTER match_round;
