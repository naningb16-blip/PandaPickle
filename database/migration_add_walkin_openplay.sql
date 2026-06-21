-- Migration: Add walk-in support to open play registrations
-- Run this to allow admin to register walk-in players without accounts

USE pandapickle;

-- Make user_id nullable (for walk-in players)
ALTER TABLE open_play_registrations 
MODIFY COLUMN user_id INT DEFAULT NULL;

-- Add contact_phone field for walk-in players
ALTER TABLE open_play_registrations 
ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(20) DEFAULT NULL
AFTER partner_name;

-- Update the foreign key constraint to allow NULL and SET NULL on delete
ALTER TABLE open_play_registrations 
DROP FOREIGN KEY IF EXISTS open_play_registrations_ibfk_2;

ALTER TABLE open_play_registrations 
ADD CONSTRAINT open_play_registrations_ibfk_2 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
