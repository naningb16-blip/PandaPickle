-- Migration: Add transfer reference number to payments table
-- Date: 2026-06-22
-- Description: Add reference_number field for tracking cashless payment references

ALTER TABLE payments 
ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100) DEFAULT NULL;

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_payments_reference_number ON payments(reference_number);

COMMENT ON COLUMN payments.reference_number IS 'Transfer reference number for cashless payments (GCash ref, bank transfer ref, etc.)';
