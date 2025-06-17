-- Add system settings table for POS configuration
-- Run this SQL in phpMyAdmin or MySQL command line

-- Create system_settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'image', 'number', 'boolean') DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('system_name', 'POS System', 'text', 'Name of the POS system displayed on receipts and interface'),
('system_logo', '', 'image', 'Logo image filename for the POS system'),
('receipt_header', 'Thank you for shopping with us!', 'text', 'Header text displayed on receipts'),
('receipt_footer', 'Please come again!', 'text', 'Footer text displayed on receipts'),
('store_address', '', 'text', 'Store address displayed on receipts'),
('store_phone', '', 'text', 'Store phone number displayed on receipts'),
('store_email', '', 'text', 'Store email address displayed on receipts'),
('default_language', 'en', 'text', 'Default system language (en/dv)'),
('tax_rate', '6', 'number', 'Default tax rate percentage'),
('currency_symbol', 'MVR', 'text', 'Currency symbol displayed throughout the system')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
description = VALUES(description);

-- Create uploads directory for system logos if not exists
-- Note: This will be handled by PHP code
