-- Migration: Add walk-in customer support to reservations
-- Run this to allow admin to create reservations for customers without accounts

USE pandapickle;

-- Make user_id nullable (for walk-in customers)
ALTER TABLE exclusive_reservations 
MODIFY COLUMN user_id INT DEFAULT NULL;

-- Add customer_name field for walk-in customers
ALTER TABLE exclusive_reservations 
ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100) DEFAULT NULL
AFTER user_id;

-- Add customer_phone field for walk-in customers
ALTER TABLE exclusive_reservations 
ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(20) DEFAULT NULL
AFTER customer_name;

-- Update the foreign key constraint to allow NULL and SET NULL on delete
ALTER TABLE exclusive_reservations 
DROP FOREIGN KEY IF EXISTS exclusive_reservations_ibfk_1;

ALTER TABLE exclusive_reservations 
ADD CONSTRAINT exclusive_reservations_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
