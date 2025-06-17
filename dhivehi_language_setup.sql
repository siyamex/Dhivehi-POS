-- Dhivehi Language Support Database Setup
-- Run this after the main system_settings.sql

-- Ensure the system_settings table exists with all language-related settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('default_language', 'en', 'text', 'Default system language (en for English, dv for Dhivehi)')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
description = VALUES(description);

-- Update existing settings to ensure they have proper descriptions
UPDATE system_settings SET 
    description = 'Name of the POS system displayed on receipts and interface'
WHERE setting_key = 'system_name';

UPDATE system_settings SET 
    description = 'Logo image filename for the POS system'
WHERE setting_key = 'system_logo';

UPDATE system_settings SET 
    description = 'Header text displayed on receipts'
WHERE setting_key = 'receipt_header';

UPDATE system_settings SET 
    description = 'Footer text displayed on receipts'
WHERE setting_key = 'receipt_footer';

UPDATE system_settings SET 
    description = 'Store address displayed on receipts'
WHERE setting_key = 'store_address';

UPDATE system_settings SET 
    description = 'Store phone number displayed on receipts'
WHERE setting_key = 'store_phone';

UPDATE system_settings SET 
    description = 'Store email address displayed on receipts'
WHERE setting_key = 'store_email';

UPDATE system_settings SET 
    description = 'Currency symbol displayed throughout the system'
WHERE setting_key = 'currency_symbol';

UPDATE system_settings SET 
    description = 'Default tax rate percentage'
WHERE setting_key = 'tax_rate';

-- Verify installation
SELECT 
    setting_key, 
    setting_value, 
    setting_type, 
    description
FROM system_settings 
WHERE setting_key IN (
    'default_language', 
    'system_name', 
    'currency_symbol'
)
ORDER BY setting_key;
