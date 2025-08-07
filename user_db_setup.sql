-- Create database (if not already exists)
CREATE DATABASE IF NOT EXISTS animeelite;

-- Use the database
USE animeelite;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    last_login DATETIME,
    subscription ENUM('free', 'premium', 'ultimate') DEFAULT 'free',
    subscription_updated DATETIME,
    subscription_expires DATETIME,
    coupon_used VARCHAR(50),
    profile_image VARCHAR(255),
    is_admin TINYINT(1) DEFAULT 0,
    display_name VARCHAR(100),
    photo_url TEXT,
    phone_number VARCHAR(20),
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user sessions table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subscription history table
CREATE TABLE IF NOT EXISTS subscription_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT,
    plan VARCHAR(20) NOT NULL,
    action VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount INT NOT NULL,
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    max_uses INT DEFAULT NULL,
    current_uses INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    discount_percent DECIMAL(5, 2),
    valid_from DATETIME,
    valid_to DATETIME,
    plan_restriction VARCHAR(20),
    INDEX (code),
    INDEX (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create coupon usage table
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    coupon_code VARCHAR(50) NOT NULL,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    coupon_id INT,
    subscription_id INT,
    discount_amount DECIMAL(10, 2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (coupon_id),
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create anime table
CREATE TABLE IF NOT EXISTS anime (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  cover_image VARCHAR(255),
  release_year VARCHAR(4),
  genres VARCHAR(255),
  status ENUM('ongoing', 'completed', 'upcoming') DEFAULT 'ongoing',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create seasons table
CREATE TABLE IF NOT EXISTS seasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  anime_id INT NOT NULL,
  season_number INT NOT NULL,
  title VARCHAR(255),
  description TEXT,
  cover_image VARCHAR(255),
  release_year VARCHAR(4),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
  UNIQUE KEY unique_season (anime_id, season_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create episodes table
CREATE TABLE IF NOT EXISTS episodes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_id INT NOT NULL,
  episode_number INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  thumbnail VARCHAR(255),
  video_url TEXT NOT NULL,
  duration VARCHAR(10),
  is_premium TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
  UNIQUE KEY unique_episode (season_id, episode_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create watch history table
CREATE TABLE IF NOT EXISTS watch_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  anime_id INT NOT NULL,
  season_id INT NOT NULL,
  episode_id INT NOT NULL,
  watched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  watch_duration INT DEFAULT 0,
  completed TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
  FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
  FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user favorites table
CREATE TABLE IF NOT EXISTS user_favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  anime_id INT NOT NULL,
  added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
  UNIQUE KEY unique_favorite (user_id, anime_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert special premium coupons
INSERT INTO coupons (code, discount, description, expires_at, max_uses, active)
VALUES 
('xsse3', 100, 'Special premium coupon', DATE_ADD(NOW(), INTERVAL 1 YEAR), NULL, 1),
('ELITE100', 100, 'Elite premium coupon', DATE_ADD(NOW(), INTERVAL 1 YEAR), NULL, 1),
('ANIMEPRO', 100, 'Anime Pro premium coupon', DATE_ADD(NOW(), INTERVAL 1 YEAR), NULL, 1),
('PREMIUM24', 100, 'Premium 24 coupon', DATE_ADD(NOW(), INTERVAL 1 YEAR), NULL, 1);

-- Insert additional sample coupon codes
INSERT INTO coupons (code, discount_percent, description, valid_from, valid_to, max_uses, active) VALUES
('WELCOME2023', 20.00, 'Welcome coupon with 20% discount', '2023-01-01', '2023-12-31', 1000, 1),
('SUMMER50', 50.00, 'Summer special with 50% discount', '2023-06-01', '2023-08-31', 500, 1),
('ANIME10', 10.00, 'Standard 10% discount code', NULL, NULL, NULL, 1),
('PREMIUM25', 25.00, 'Premium subscription discount', NULL, NULL, 200, 1);

-- Insert default subscription plans
INSERT INTO subscription_plans (name, code, description, price, billing_cycle, features) VALUES 
('Free', 'free', 'Basic access to our library', 0.00, 'monthly', '{"resolution": "720p", "ads": true, "offline_viewing": false, "library": "standard"}'),
('Premium', 'premium', 'Enhanced streaming experience', 9.99, 'monthly', '{"resolution": "1080p", "ads": false, "offline_viewing": true, "library": "full", "early_access": true}'),
('Ultimate', 'ultimate', 'The complete package', 14.99, 'monthly', '{"resolution": "4k", "ads": false, "offline_viewing": true, "library": "full", "early_access": true, "simultaneous_streams": 4, "exclusive_content": true}');

-- Create admin user
INSERT INTO users (username, email, password, created_at, subscription, is_admin)
VALUES ('admin', 'admin@animeelite.com', '$2y$10$8mnR1bUBRX0TcDxZrHxfxuYBrDyV9n3XB4H3QVvwJcissOK9IwZ.G', NOW(), 'premium', 1);
-- Password is 'admin123'

-- Sample data for anime testing
INSERT INTO anime (title, description, cover_image, release_year, genres, status) VALUES
('Attack on Titan', 'Humans are nearly exterminated by giant creatures called Titans. Titans are typically several stories tall, seem to have no intelligence and eat humans.', 'https://cdn.myanimelist.net/images/anime/1300/110853.jpg', '2013', 'Action, Dark Fantasy, Post-Apocalyptic', 'ongoing'),
('Demon Slayer', 'A boy raised in a family of demon slayers fights to cure his sister, who has been turned into a demon herself.', 'https://cdn.myanimelist.net/images/anime/1286/99889.jpg', '2019', 'Action, Fantasy, Historical', 'ongoing'),
('Jujutsu Kaisen', 'A boy swallows a cursed talisman - the finger of a demon - and becomes cursed himself. He enters a shaman\'s school to be able to locate the demon\'s other body parts and thus exorcise himself.', 'https://cdn.myanimelist.net/images/anime/1171/109222.jpg', '2020', 'Action, Supernatural', 'ongoing'),
('My Hero Academia', 'A superhero-loving boy without any powers is determined to enroll in a prestigious hero academy and learn what it really means to be a hero.', 'https://cdn.myanimelist.net/images/anime/1170/111519.jpg', '2016', 'Action, Superhero', 'ongoing');

-- Sample seasons
INSERT INTO seasons (anime_id, season_number, title, release_year) VALUES
(1, 1, 'Attack on Titan Season 1', '2013'),
(1, 2, 'Attack on Titan Season 2', '2017'),
(1, 3, 'Attack on Titan Season 3', '2018'),
(1, 4, 'Attack on Titan Final Season', '2020'),
(2, 1, 'Demon Slayer: Kimetsu no Yaiba', '2019'),
(2, 2, 'Demon Slayer: Entertainment District Arc', '2021'),
(3, 1, 'Jujutsu Kaisen Season 1', '2020'),
(4, 1, 'My Hero Academia Season 1', '2016'),
(4, 2, 'My Hero Academia Season 2', '2017'),
(4, 3, 'My Hero Academia Season 3', '2018'),
(4, 4, 'My Hero Academia Season 4', '2019'),
(4, 5, 'My Hero Academia Season 5', '2021');

-- Sample episodes for Attack on Titan Season 1
INSERT INTO episodes (season_id, episode_number, title, video_url, is_premium) VALUES
(1, 1, 'To You, 2,000 Years From Now', 'https://iframe.example.com/embed/aot-s01e01', 0),
(1, 2, 'That Day', 'https://iframe.example.com/embed/aot-s01e02', 0),
(1, 3, 'A Dim Light Amid Despair', 'https://iframe.example.com/embed/aot-s01e03', 1);

-- Sample episodes for Demon Slayer Season 1
INSERT INTO episodes (season_id, episode_number, title, video_url, is_premium) VALUES
(5, 1, 'Cruelty', 'https://iframe.example.com/embed/ds-s01e01', 0),
(5, 2, 'Trainer Sakonji Urokodaki', 'https://iframe.example.com/embed/ds-s01e02', 0),
(5, 3, 'Sabito and Makomo', 'https://iframe.example.com/embed/ds-s01e03', 1);

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