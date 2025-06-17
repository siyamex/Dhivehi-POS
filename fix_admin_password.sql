-- Update admin user password
-- This will set the password to 'admin123'
UPDATE users SET password = '$2y$10$TKh/HTiLdIDp1Pp7.7s8u.xKt9UX1VC7L3o2L4O5t4F1O1xC6xXk2' WHERE username = 'admin';

-- Alternative: Delete and recreate the admin user
DELETE FROM users WHERE username = 'admin';
INSERT INTO users (username, email, password, first_name, last_name, role, status) 
VALUES ('admin', 'admin@pos.com', '$2y$10$TKh/HTiLdIDp1Pp7.7s8u.xKt9UX1VC7L3o2L4O5t4F1O1xC6xXk2', 'Admin', 'User', 'admin', 'active');
