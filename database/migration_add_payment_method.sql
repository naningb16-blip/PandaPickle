-- Migration: Add payment_method column to exclusive_reservations table
-- Run this if you have existing data

USE pandapickle;

-- Add payment_method column if it doesn't exist
ALTER TABLE exclusive_reservations 
ADD COLUMN IF NOT EXISTS payment_method ENUM('cash', 'cashless') NOT NULL DEFAULT 'cash' 
AFTER total_amount;

-- Optional: Update existing reservations to have a default payment method
UPDATE exclusive_reservations 
SET payment_method = 'cash' 
WHERE payment_method IS NULL;
