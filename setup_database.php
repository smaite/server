<?php
// Database setup script
require_once 'config.php';

// Enable error display during setup
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>AnimeElite Database Setup</h1>";

try {
    // Connect to MySQL server without selecting a database
    $conn = new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("MySQL connection failed: " . $conn->connect_error);
    }
    
    echo "<p>Connected to MySQL server successfully.</p>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        echo "<p>Database '$dbname' created successfully or already exists.</p>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db($dbname);
    
    // Create anime table
    $sql = "CREATE TABLE IF NOT EXISTS anime (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        cover_image VARCHAR(255),
        release_year VARCHAR(4),
        genres VARCHAR(255),
        status ENUM('ongoing', 'completed', 'upcoming') DEFAULT 'ongoing',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'anime' created successfully.</p>";
    } else {
        throw new Exception("Error creating table 'anime': " . $conn->error);
    }
    
    // Create seasons table
    $sql = "CREATE TABLE IF NOT EXISTS seasons (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'seasons' created successfully.</p>";
    } else {
        throw new Exception("Error creating table 'seasons': " . $conn->error);
    }
    
    // Create episodes table
    $sql = "CREATE TABLE IF NOT EXISTS episodes (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'episodes' created successfully.</p>";
    } else {
        throw new Exception("Error creating table 'episodes': " . $conn->error);
    }
    
    // Check if we have sample data
    $result = $conn->query("SELECT COUNT(*) as count FROM anime");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "<p>Adding sample anime data...</p>";
        
        // Insert sample anime
        $sql = "INSERT INTO anime (title, description, cover_image, release_year, genres, status) VALUES
            ('Attack on Titan', 'Humans are nearly exterminated by giant creatures called Titans. Titans are typically several stories tall, seem to have no intelligence and eat humans.', 'https://cdn.myanimelist.net/images/anime/1300/110853.jpg', '2013', 'Action, Dark Fantasy, Post-Apocalyptic', 'ongoing'),
            ('Demon Slayer', 'A boy raised in a family of demon slayers fights to cure his sister, who has been turned into a demon herself.', 'https://cdn.myanimelist.net/images/anime/1286/99889.jpg', '2019', 'Action, Fantasy, Historical', 'ongoing')";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p>Sample anime data added successfully.</p>";
        } else {
            throw new Exception("Error adding sample anime data: " . $conn->error);
        }
        
        // Insert sample seasons
        $sql = "INSERT INTO seasons (anime_id, season_number, title, release_year) VALUES
            (1, 1, 'Attack on Titan Season 1', '2013'),
            (1, 2, 'Attack on Titan Season 2', '2017'),
            (2, 1, 'Demon Slayer: Kimetsu no Yaiba', '2019')";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p>Sample season data added successfully.</p>";
        } else {
            throw new Exception("Error adding sample season data: " . $conn->error);
        }
        
        // Insert sample episodes for Attack on Titan Season 1
        $sql = "INSERT INTO episodes (season_id, episode_number, title, video_url, is_premium) VALUES
            (1, 1, 'To You, 2,000 Years From Now', 'https://iframe.example.com/embed/aot-s01e01', 0),
            (1, 2, 'That Day', 'https://iframe.example.com/embed/aot-s01e02', 0),
            (1, 3, 'A Dim Light Amid Despair', 'https://iframe.example.com/embed/aot-s01e03', 1)";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p>Sample episodes for Attack on Titan added successfully.</p>";
        } else {
            throw new Exception("Error adding sample episodes for Attack on Titan: " . $conn->error);
        }
        
        // Insert sample episodes for Demon Slayer Season 1
        $sql = "INSERT INTO episodes (season_id, episode_number, title, video_url, is_premium) VALUES
            (3, 1, 'Cruelty', 'https://iframe.example.com/embed/ds-s01e01', 0),
            (3, 2, 'Trainer Sakonji Urokodaki', 'https://iframe.example.com/embed/ds-s01e02', 0),
            (3, 3, 'Sabito and Makomo', 'https://iframe.example.com/embed/ds-s01e03', 1)";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p>Sample episodes for Demon Slayer added successfully.</p>";
        } else {
            throw new Exception("Error adding sample episodes for Demon Slayer: " . $conn->error);
        }
    } else {
        echo "<p>Database already contains anime data. Skipping sample data insertion.</p>";
    }
    
    echo "<h2>Database setup completed successfully!</h2>";
    echo "<p>You can now use the AnimeElite website.</p>";
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; border:1px solid red; margin:20px 0;'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 