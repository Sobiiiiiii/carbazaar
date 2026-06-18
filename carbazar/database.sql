-- ============================================================
-- CarBazar Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS carbazar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carbazar;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    phone       VARCHAR(20)   DEFAULT NULL,
    password    VARCHAR(255)  NOT NULL,
    user_type   ENUM('buyer','seller') NOT NULL DEFAULT 'buyer',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT         DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default categories
INSERT INTO categories (name, description) VALUES
('Engine Parts',    'Oil, filters, spark plugs, pistons'),
('Suspension',      'Shocks, springs, bushings, struts'),
('Brakes',          'Pads, discs, calipers, brake fluid'),
('Electrical',      'Battery, alternator, starter, wiring'),
('Cooling System',  'Radiator, thermostat, water pump'),
('Body Parts',      'Mirrors, lights, bumpers, trim'),
('Transmission',    'Gearbox, clutch, driveshaft'),
('Exhaust',         'Muffler, catalytic converter, pipes');

-- ============================================================
-- PRODUCTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    seller_id       INT           NOT NULL,
    category_id     INT           NOT NULL,
    name            VARCHAR(200)  NOT NULL,
    description     TEXT          DEFAULT NULL,
    price           DECIMAL(10,2) NOT NULL,
    discount_price  DECIMAL(10,2) DEFAULT NULL,
    stock           INT           NOT NULL DEFAULT 0,
    brand           VARCHAR(100)  DEFAULT NULL,
    image           VARCHAR(255)  DEFAULT 'default.jpg',
    rating          DECIMAL(3,2)  DEFAULT 0.00,
    reviews_count   INT           DEFAULT 0,
    is_active       TINYINT(1)    DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id)   REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- CART TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS cart (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ORDERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT           NOT NULL,
    total_amount     DECIMAL(10,2) NOT NULL,
    shipping_address TEXT          NOT NULL,
    payment_method   ENUM('cod','online') NOT NULL DEFAULT 'cod',
    status           ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    order_date       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT           NOT NULL,
    product_id  INT           NOT NULL,
    seller_id   INT           NOT NULL,
    quantity    INT           NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (seller_id)  REFERENCES users(id)    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- WISHLIST TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS wishlist (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    product_id  INT DEFAULT NULL,
    car_id      INT DEFAULT NULL,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist_product (user_id, product_id),
    UNIQUE KEY unique_wishlist_car     (user_id, car_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (car_id)     REFERENCES cars(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- REVIEWS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT  NOT NULL,
    user_id     INT  NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CARS TABLE (Used Cars Buy & Sell)
-- ============================================================
CREATE TABLE IF NOT EXISTS cars (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    seller_id       INT           NOT NULL,
    title           VARCHAR(200)  NOT NULL,
    brand           VARCHAR(100)  NOT NULL,
    model           VARCHAR(100)  NOT NULL,
    year            YEAR          NOT NULL,
    price           DECIMAL(12,2) NOT NULL,
    mileage         INT           DEFAULT 0,
    fuel_type       ENUM('petrol','diesel','hybrid','electric','cng') DEFAULT 'petrol',
    transmission    ENUM('manual','automatic') DEFAULT 'manual',
    condition_type  ENUM('excellent','good','fair','needs_repair') DEFAULT 'good',
    color           VARCHAR(50)   DEFAULT NULL,
    city            VARCHAR(100)  DEFAULT NULL,
    description     TEXT          DEFAULT NULL,
    image           VARCHAR(255)  DEFAULT 'default.jpg',
    is_active       TINYINT(1)    DEFAULT 1,
    is_sold         TINYINT(1)    DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample Cars Data (seller_id 1 ke liye — pehle ek seller register karo)
-- Yeh data tab insert hoga jab users table mein koi record ho
-- INSERT INTO cars ... (manually add karo ya register ke baad)

-- ============================================================
-- ADMIN PANEL SETUP
-- Run these queries to enable admin panel
-- ============================================================

-- Step 1: Add 'admin' to user_type enum
ALTER TABLE users MODIFY COLUMN user_type ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer';

-- Step 2: Add is_blocked column
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) DEFAULT 0;

-- Step 3: Create admin account
-- Email: admin@carbazar.com | Password: admin123
INSERT INTO users (name, email, phone, password, user_type) VALUES
('Admin', 'admin@carbazar.com', '0300-0000000',
 '$2y$10$TKh8H1.PfuA2Pi3iSbonBuDkHVl79OGjh5JkEMIYhJwE4AxnmK6Wy',
 'admin')
ON DUPLICATE KEY UPDATE user_type = 'admin';
-- Note: Password hash above = 'admin123'
-- To change password: UPDATE users SET password=password_hash('newpass', PASSWORD_DEFAULT) WHERE email='admin@carbazar.com';

-- ============================================================
-- CAR OFFERS TABLE (For Buy Now / Make Offer Feature)
-- ============================================================
CREATE TABLE IF NOT EXISTS car_offers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    car_id          INT           NOT NULL,
    buyer_id        INT           NOT NULL,
    seller_id       INT           NOT NULL,
    offer_amount    DECIMAL(12,2) NOT NULL,
    buyer_message   TEXT          DEFAULT NULL,
    buyer_phone     VARCHAR(20)   DEFAULT NULL,
    status          ENUM('pending','accepted','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id)    REFERENCES cars(id)  ON DELETE CASCADE,
    FOREIGN KEY (buyer_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TRANSACTIONS TABLE (Payment Tracking with Commission)
-- ============================================================
CREATE TABLE IF NOT EXISTS transactions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type    ENUM('car_sale','product_sale') NOT NULL,
    reference_id        INT           NOT NULL COMMENT 'car_id or order_id',
    buyer_id            INT           NOT NULL,
    seller_id           INT           NOT NULL,
    total_amount        DECIMAL(12,2) NOT NULL COMMENT 'Total paid by buyer',
    commission_percent  DECIMAL(5,2)  NOT NULL DEFAULT 5.00 COMMENT 'Platform commission %',
    commission_amount   DECIMAL(12,2) NOT NULL COMMENT 'Platform earnings',
    seller_amount       DECIMAL(12,2) NOT NULL COMMENT 'Amount to be paid to seller',
    payment_method      ENUM('jazzcash','easypaisa','bank_transfer','cod','stripe') NOT NULL,
    payment_status      ENUM('pending','paid','processing','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    payment_proof       VARCHAR(255)  DEFAULT NULL COMMENT 'Receipt/screenshot upload',
    seller_paid         TINYINT(1)    DEFAULT 0 COMMENT 'Has seller received payment?',
    seller_paid_at      TIMESTAMP     NULL DEFAULT NULL,
    notes               TEXT          DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id)  REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- PLATFORM SETTINGS TABLE (Commission & Config)
-- ============================================================
CREATE TABLE IF NOT EXISTS platform_settings (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    setting_key             VARCHAR(100) NOT NULL UNIQUE,
    setting_value           TEXT         NOT NULL,
    setting_description     TEXT         DEFAULT NULL,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default Settings
INSERT INTO platform_settings (setting_key, setting_value, setting_description) VALUES
('car_commission_percent',     '5.00',  'Commission percentage on car sales'),
('product_commission_percent', '10.00', 'Commission percentage on spare parts sales'),
('min_payout_amount',          '5000',  'Minimum amount for seller payout (PKR)'),
('platform_bank_account',      'HBL - 12345678901234', 'Platform bank account for deposits'),
('jazzcash_number',            '03001234567', 'JazzCash merchant number'),
('easypaisa_number',           '03001234567', 'EasyPaisa merchant number')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);


