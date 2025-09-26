-- GamePlan Scheduler Database Schema
-- MySQL 8.0+ with UTF-8 support

CREATE DATABASE IF NOT EXISTS gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gameplan_db;

-- Users table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Games table
CREATE TABLE Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    INDEX idx_title (title)
) ENGINE=InnoDB;

-- UserGames table (junction for favorite games)
CREATE TABLE UserGames (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Friends table
CREATE TABLE Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Schedules table
CREATE TABLE Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_date (date)
) ENGINE=InnoDB;

-- Events table
CREATE TABLE Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    reminder VARCHAR(50) DEFAULT 'geen',
    schedule_id INT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_date (date)
) ENGINE=InnoDB;

-- EventUserMap table (for sharing events)
CREATE TABLE EventUserMap (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Activity log table
CREATE TABLE activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB;

-- Insert sample data
INSERT INTO Games (title, description) VALUES
('Fortnite', 'Battle Royale game'),
('Minecraft', 'Sandbox building game'),
('Call of Duty', 'First-person shooter');

INSERT INTO Users (username, email, password_hash) VALUES
('demo', 'demo@gameplan.com', '$argon2id$v=19$m=65536,t=4,p=1$examplehash'); -- Replace with actual hash