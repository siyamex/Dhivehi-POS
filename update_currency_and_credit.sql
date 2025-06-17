-- Update database to support MVR currency and Credit payment method
-- Run this SQL in phpMyAdmin or MySQL command line

-- 1. Add 'credit' to payment_method ENUM in sales table
ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'card', 'digital', 'credit') NOT NULL;

-- 2. Add new payment status for credit sales
ALTER TABLE sales MODIFY COLUMN payment_status ENUM('paid', 'pending', 'refunded', 'credit') DEFAULT 'paid';

-- 3. Create credit_sales table to track credit transactions
CREATE TABLE IF NOT EXISTS credit_sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    customer_id INT NOT NULL,
    original_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    remaining_amount DECIMAL(10,2) NOT NULL,
    due_date DATE NULL,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX idx_customer_status (customer_id, status),
    INDEX idx_due_date (due_date)
);

-- 4. Create credit_payments table to track payments against credit sales
CREATE TABLE IF NOT EXISTS credit_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    credit_sale_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'digital') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    user_id INT NOT NULL,
    FOREIGN KEY (credit_sale_id) REFERENCES credit_sales(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
