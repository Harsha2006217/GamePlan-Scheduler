-- Database schema for GamePlan Scheduler
-- Created by Harsha Kanaparthi on 02-10-2025
-- This script creates the database and tables with foreign keys, constraints, and indexes for performance.
-- Run this in phpMyAdmin or MySQL CLI to set up the DB.

CREATE DATABASE IF NOT EXISTS gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE gameplan_db;

-- Users table for user accounts
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Games table for game information
CREATE TABLE IF NOT EXISTS Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- UserGames table for user favorite games (many-to-many with denormalized data)
CREATE TABLE IF NOT EXISTS UserGames (
    user_id INT NOT NULL,
    game_id INT NOT NULL PRIMARY KEY,
    gametitel VARCHAR(100) NOT NULL,
    game_description TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Friends table for friendships
CREATE TABLE IF NOT EXISTS Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friend (user_id, friend_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Schedules table for game schedules
CREATE TABLE IF NOT EXISTS Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    INDEX idx_date_time (date, time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events table for events linked to schedules
CREATE TABLE IF NOT EXISTS Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    reminder VARCHAR(50),
    schedule_id INT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL,
    INDEX idx_date_time (date, time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- EventUserMap table for event sharing with friends
CREATE TABLE IF NOT EXISTS EventUserMap (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for testing
INSERT INTO Users (username, email, password_hash) VALUES 
('testuser', 'test@example.com', '$2y$10$examplehashforpassword');

INSERT INTO Games (titel, description) VALUES 
('Fortnite', 'Battle Royale game');

INSERT INTO UserGames (user_id, game_id, gametitel, game_description) VALUES 
(1, 1, 'Fortnite', 'Battle Royale game');

INSERT INTO Friends (user_id, friend_user_id) VALUES 
(1, 1);  -- Self for testing, but logic prevents in code

INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES 
(1, 1, '2025-10-10', '15:00:00', '1');

INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES 
(1, 'Tournament', '2025-10-15', '18:00:00', 'Join us', '1 hour before', 1);

INSERT INTO EventUserMap (event_id, friend_id) VALUES 
(1, 1);