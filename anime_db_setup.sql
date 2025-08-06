-- Create anime table
CREATE TABLE IF NOT EXISTS `anime` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `cover_image` VARCHAR(255),
  `release_year` VARCHAR(4),
  `genres` VARCHAR(255),
  `status` ENUM('ongoing', 'completed', 'upcoming') DEFAULT 'ongoing',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create seasons table
CREATE TABLE IF NOT EXISTS `seasons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `anime_id` INT NOT NULL,
  `season_number` INT NOT NULL,
  `title` VARCHAR(255),
  `description` TEXT,
  `cover_image` VARCHAR(255),
  `release_year` VARCHAR(4),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`anime_id`) REFERENCES `anime`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_season` (`anime_id`, `season_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create episodes table
CREATE TABLE IF NOT EXISTS `episodes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `season_id` INT NOT NULL,
  `episode_number` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `thumbnail` VARCHAR(255),
  `video_url` TEXT NOT NULL,
  `duration` VARCHAR(10),
  `is_premium` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`season_id`) REFERENCES `seasons`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_episode` (`season_id`, `episode_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_watch_history table
CREATE TABLE IF NOT EXISTS `user_watch_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(255) NOT NULL,
  `episode_id` INT NOT NULL,
  `watched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `watch_duration` INT DEFAULT 0,
  `completed` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_watch` (`user_id`, `episode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_favorites table
CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(255) NOT NULL,
  `anime_id` INT NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`anime_id`) REFERENCES `anime`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_favorite` (`user_id`, `anime_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
INSERT INTO `anime` (`title`, `description`, `cover_image`, `release_year`, `genres`, `status`) VALUES
('Attack on Titan', 'Humans are nearly exterminated by giant creatures called Titans. Titans are typically several stories tall, seem to have no intelligence and eat humans.', 'https://cdn.myanimelist.net/images/anime/1300/110853.jpg', '2013', 'Action, Dark Fantasy, Post-Apocalyptic', 'ongoing'),
('Demon Slayer', 'A boy raised in a family of demon slayers fights to cure his sister, who has been turned into a demon herself.', 'https://cdn.myanimelist.net/images/anime/1286/99889.jpg', '2019', 'Action, Fantasy, Historical', 'ongoing'),
('Jujutsu Kaisen', 'A boy swallows a cursed talisman - the finger of a demon - and becomes cursed himself. He enters a shaman's school to be able to locate the demon's other body parts and thus exorcise himself.', 'https://cdn.myanimelist.net/images/anime/1171/109222.jpg', '2020', 'Action, Supernatural', 'ongoing'),
('My Hero Academia', 'A superhero-loving boy without any powers is determined to enroll in a prestigious hero academy and learn what it really means to be a hero.', 'https://cdn.myanimelist.net/images/anime/1170/111519.jpg', '2016', 'Action, Superhero', 'ongoing');

-- Sample seasons
INSERT INTO `seasons` (`anime_id`, `season_number`, `title`, `release_year`) VALUES
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
INSERT INTO `episodes` (`season_id`, `episode_number`, `title`, `video_url`, `is_premium`) VALUES
(1, 1, 'To You, 2,000 Years From Now', 'https://iframe.example.com/embed/aot-s01e01', 0),
(1, 2, 'That Day', 'https://iframe.example.com/embed/aot-s01e02', 0),
(1, 3, 'A Dim Light Amid Despair', 'https://iframe.example.com/embed/aot-s01e03', 1);

-- Sample episodes for Demon Slayer Season 1
INSERT INTO `episodes` (`season_id`, `episode_number`, `title`, `video_url`, `is_premium`) VALUES
(5, 1, 'Cruelty', 'https://iframe.example.com/embed/ds-s01e01', 0),
(5, 2, 'Trainer Sakonji Urokodaki', 'https://iframe.example.com/embed/ds-s01e02', 0),
(5, 3, 'Sabito and Makomo', 'https://iframe.example.com/embed/ds-s01e03', 1); 