-- GamePlan Scheduler Database Schema
-- Professional MySQL database structure with proper relationships and constraints
-- Created for MBO-4 Software Development project

-- Create database with UTF-8 support
CREATE DATABASE IF NOT EXISTS gameplan_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE gameplan_db;

-- Users table: Store user account information
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    bio TEXT,
    avatar_url VARCHAR(255),
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Games table: Store available games information
CREATE TABLE IF NOT EXISTS Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT,
    genre VARCHAR(50),
    platform VARCHAR(50),
    rating VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_titel (titel),
    INDEX idx_genre (genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- UserGames table: Store user's favorite games (many-to-many relationship)
CREATE TABLE IF NOT EXISTS UserGames (
    user_game_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_game (user_id, game_id),
    INDEX idx_user_id (user_id),
    INDEX idx_game_id (game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Friends table: Store friend relationships (bidirectional)
CREATE TABLE IF NOT EXISTS Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_friend_user_id (friend_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schedules table: Store gaming session schedules
CREATE TABLE IF NOT EXISTS Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT, -- Comma-separated list of friend usernames
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_game_id (game_id),
    INDEX idx_date (date),
    INDEX idx_datetime (date, time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table: Store tournaments and special events
CREATE TABLE IF NOT EXISTS Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    reminder ENUM('none', '1hour', '1day') DEFAULT 'none',
    schedule_id INT, -- Optional link to a schedule
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_schedule_id (schedule_id),
    INDEX idx_date (date),
    INDEX idx_datetime (date, time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EventUserMap table: Store event sharing with friends (many-to-many relationship)
CREATE TABLE IF NOT EXISTS EventUserMap (
    event_user_map_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_friend (event_id, friend_id),
    INDEX idx_event_id (event_id),
    INDEX idx_friend_id (friend_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log table: Store user activity for auditing and analytics
CREATE TABLE IF NOT EXISTS activity_log (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts table: Store failed login attempts for security
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(100) NOT NULL, -- email or IP
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample games data
INSERT INTO Games (titel, description, genre, platform, rating) VALUES
('Fortnite', 'Battle royale game with building mechanics and competitive gameplay', 'Battle Royale', 'Multi-platform', 'T'),
('Counter-Strike 2', 'Tactical first-person shooter with team-based gameplay', 'FPS', 'PC', 'M'),
('League of Legends', 'Multiplayer online battle arena with strategic team combat', 'MOBA', 'PC', 'T'),
('Minecraft', 'Sandbox game with building, exploration, and survival elements', 'Sandbox', 'Multi-platform', 'E10+'),
('Among Us', 'Social deduction game with crewmate and imposter roles', 'Social Deduction', 'Multi-platform', 'E10+'),
('Valorant', 'Character-based tactical shooter with unique agent abilities', 'FPS', 'PC', 'T'),
('Apex Legends', 'Battle royale with hero characters and squad-based gameplay', 'Battle Royale', 'Multi-platform', 'T'),
('Rocket League', 'Soccer with rocket-powered cars and physics-based gameplay', 'Sports', 'Multi-platform', 'E'),
('Overwatch 2', 'Team-based multiplayer shooter with diverse hero roster', 'FPS', 'Multi-platform', 'T'),
('World of Warcraft', 'Massively multiplayer online role-playing game', 'MMORPG', 'PC', 'T')
ON DUPLICATE KEY UPDATE titel = VALUES(titel);