-- Add payment settings table for admin payment information
-- This stores the admin's payment details that users see when clicking "Pay Now"

CREATE TABLE IF NOT EXISTS payment_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_label VARCHAR(200),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default payment methods
INSERT INTO payment_settings (setting_key, setting_value, setting_label, display_order) VALUES
('gcash_number', '09123456789', 'GCash Number', 1),
('gcash_name', 'PandaPickle Court', 'GCash Account Name', 2),
('bank_name', 'BDO', 'Bank Name', 3),
('bank_account_number', '1234567890', 'Bank Account Number', 4),
('bank_account_name', 'PandaPickle Pickleball Court', 'Bank Account Name', 5),
('payment_instructions', 'Please send payment and screenshot the receipt. Send proof to our GCash or bank account.', 'Payment Instructions', 6),
('contact_number', '09123456789', 'Contact Number for Inquiries', 7)
ON CONFLICT (setting_key) DO NOTHING;

-- Add index for faster queries
CREATE INDEX idx_payment_settings_active ON payment_settings(is_active);

COMMENT ON TABLE payment_settings IS 'Stores admin payment information displayed to users';
COMMENT ON COLUMN payment_settings.setting_key IS 'Unique identifier for the setting';
COMMENT ON COLUMN payment_settings.setting_value IS 'The actual value/content of the setting';
COMMENT ON COLUMN payment_settings.setting_label IS 'Display label shown to users';
