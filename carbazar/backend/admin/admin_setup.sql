-- ============================================================
-- Admin Panel Setup - CarBazar
-- Run this SQL in phpMyAdmin to add admin support
-- ============================================================

USE carbazar;

-- Step 1: Add 'admin' to users user_type enum
ALTER TABLE users MODIFY COLUMN user_type ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer';

-- Step 2: Add is_blocked column to users
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) DEFAULT 0;

-- Step 3: Add is_approved column to cars (admin approval)
ALTER TABLE cars ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) DEFAULT 1;

-- Step 4: Create admin account
-- Password: admin123 (bcrypt hash)
INSERT INTO users (name, email, phone, password, user_type) VALUES
('Admin', 'admin@carbazar.com', '0300-0000000',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin')
ON DUPLICATE KEY UPDATE user_type = 'admin';

-- Note: Default password is 'password' (Laravel default hash above)
-- To set custom password, run this PHP snippet:
-- echo password_hash('admin123', PASSWORD_DEFAULT);
-- Then update: UPDATE users SET password='[hash]' WHERE email='admin@carbazar.com';
