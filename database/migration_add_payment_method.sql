-- Add payment method tracking to payments table
-- This allows tracking whether customer paid via GCash or Bank Transfer

-- Add payment_method column if it doesn't exist
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'payments' AND column_name = 'payment_method'
    ) THEN
        ALTER TABLE payments 
        ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL;
        
        COMMENT ON COLUMN payments.payment_method IS 'Payment method used: gcash, bank_transfer, etc.';
    END IF;
END $$;

-- Add notes column for admin remarks if it doesn't exist
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'payments' AND column_name = 'admin_notes'
    ) THEN
        ALTER TABLE payments 
        ADD COLUMN admin_notes TEXT DEFAULT NULL;
        
        COMMENT ON COLUMN payments.admin_notes IS 'Admin notes/remarks about the payment';
    END IF;
END $$;
