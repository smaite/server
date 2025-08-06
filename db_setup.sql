-- Create database (if not already exists)
CREATE DATABASE IF NOT EXISTS animeelite;

-- Use the database
USE animeelite;

-- Create users table (extends Firebase Auth users)
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(128) PRIMARY KEY, -- Firebase UID
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    photo_url TEXT,
    phone_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    INDEX (email)
);

-- Create subscription plans table
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    billing_cycle ENUM('monthly', 'quarterly', 'yearly', 'lifetime') DEFAULT 'monthly',
    features JSON,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(128) NOT NULL,
    plan VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    price DECIMAL(10, 2) NOT NULL,
    status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active',
    payment_method VARCHAR(50),
    payment_id VARCHAR(255),
    recurring BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (plan),
    INDEX (status)
);

-- Create subscription history table
CREATE TABLE IF NOT EXISTS subscription_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(128) NOT NULL,
    subscription_id INT,
    plan VARCHAR(20) NOT NULL,
    action VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
    INDEX (user_id)
);

-- Create coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percent DECIMAL(5, 2) NOT NULL,
    description TEXT,
    valid_from DATETIME,
    valid_to DATETIME,
    max_uses INT,
    current_uses INT DEFAULT 0,
    plan_restriction VARCHAR(20), -- NULL for all plans
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (code),
    INDEX (active)
);

-- Create coupon usage table
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id VARCHAR(128) NOT NULL,
    subscription_id INT,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    discount_amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
    INDEX (coupon_id),
    INDEX (user_id)
);

-- Create watch history table
CREATE TABLE IF NOT EXISTS watch_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(128) NOT NULL,
    anime_slug VARCHAR(100) NOT NULL,
    season_num INT NOT NULL,
    episode_num INT NOT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    watch_duration INT, -- Duration in seconds
    completed BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_watch (user_id, anime_slug, season_num, episode_num),
    INDEX (user_id),
    INDEX (anime_slug)
);

-- Create favorites table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(128) NOT NULL,
    anime_slug VARCHAR(100) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, anime_slug),
    INDEX (user_id)
);

-- Insert default subscription plans
INSERT INTO subscription_plans (name, code, description, price, billing_cycle, features) VALUES 
('Free', 'free', 'Basic access to our library', 0.00, 'monthly', '{"resolution": "720p", "ads": true, "offline_viewing": false, "library": "standard"}'),
('Premium', 'premium', 'Enhanced streaming experience', 9.99, 'monthly', '{"resolution": "1080p", "ads": false, "offline_viewing": true, "library": "full", "early_access": true}'),
('Ultimate', 'ultimate', 'The complete package', 14.99, 'monthly', '{"resolution": "4k", "ads": false, "offline_viewing": true, "library": "full", "early_access": true, "simultaneous_streams": 4, "exclusive_content": true}');

-- Insert sample coupon codes
INSERT INTO coupons (code, discount_percent, description, valid_from, valid_to, max_uses, active) VALUES
('WELCOME2023', 20.00, 'Welcome coupon with 20% discount', '2023-01-01', '2023-12-31', 1000, 1),
('SUMMER50', 50.00, 'Summer special with 50% discount', '2023-06-01', '2023-08-31', 500, 1),
('ANIME10', 10.00, 'Standard 10% discount code', NULL, NULL, NULL, 1),
('PREMIUM25', 25.00, 'Premium subscription discount', NULL, NULL, 200, 1);

-- Create procedure to check and update subscription status
DELIMITER //
CREATE PROCEDURE update_expired_subscriptions()
BEGIN
    -- Update subscriptions that have expired
    UPDATE subscriptions
    SET status = 'expired'
    WHERE end_date < CURDATE()
    AND status = 'active';
    
    -- Log subscription expirations
    INSERT INTO subscription_history (user_id, subscription_id, plan, action, description)
    SELECT user_id, id, plan, 'expired', CONCAT('Subscription to ', plan, ' plan expired')
    FROM subscriptions
    WHERE end_date = CURDATE() - INTERVAL 1 DAY
    AND status = 'expired';
END //
DELIMITER ;

-- Create event to run the procedure daily
CREATE EVENT IF NOT EXISTS daily_subscription_check
ON SCHEDULE EVERY 1 DAY
DO CALL update_expired_subscriptions(); 