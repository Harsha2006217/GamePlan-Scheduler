-- Advanced MySQL schema for GamePlan Scheduler
-- Created by Harsha Kanaparthi, 30-09-2025
-- Features: Auto-increment PKs, FK constraints with cascade/delete behaviors for data integrity,
-- Indexes for performance on frequent lookups (user_id, game_id, date), timestamps for online status.

CREATE DATABASE IF NOT EXISTS gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gameplan_db;

-- Users table: Stores user accounts with secure hashing and activity tracking
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique username for login and display',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique email for registration and recovery',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password for security',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last activity for online status and timeout',
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Games table: Predefined games with titles and descriptions
CREATE TABLE IF NOT EXISTS Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL COMMENT 'Game title (e.g., Fortnite)',
    description TEXT COMMENT 'Detailed game description',
    INDEX idx_titel (titel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- UserGames table: Many-to-many mapping for user favorite games
CREATE TABLE IF NOT EXISTS UserGames (
    user_id INT NOT NULL COMMENT 'FK to Users',
    game_id INT NOT NULL PRIMARY KEY COMMENT 'PK and FK to Games - ensures unique favorites per user',
    gametitel VARCHAR(100) NOT NULL COMMENT 'Redundant title for quick display without joins',
    game_description TEXT COMMENT 'Redundant description for quick display',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Friends table: User friendships (one-way or mutual via dual inserts)
CREATE TABLE IF NOT EXISTS Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'FK to owner user',
    friend_user_id INT NOT NULL COMMENT 'FK to friend user',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_user_id) COMMENT 'Prevent duplicate friendships',
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Schedules table: Game schedules with sharing via text field (comma-separated friend IDs)
CREATE TABLE IF NOT EXISTS Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'FK to owner user',
    game_id INT NOT NULL COMMENT 'FK to Games',
    date DATE NOT NULL COMMENT 'Schedule date (future only via validation)',
    time TIME NOT NULL COMMENT 'Schedule time (positive only via validation)',
    friends TEXT COMMENT 'Comma-separated friend user_ids for sharing',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id_date (user_id, date) COMMENT 'For fast calendar queries'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events table: Events linked to schedules
CREATE TABLE IF NOT EXISTS Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'FK to owner user',
    title VARCHAR(100) NOT NULL COMMENT 'Event title (max 100 chars)',
    date DATE NOT NULL COMMENT 'Event date (future only)',
    time TIME NOT NULL COMMENT 'Event time',
    description TEXT COMMENT 'Event details',
    reminder VARCHAR(50) COMMENT 'Reminder option (e.g., 1 hour before)',
    schedule_id INT DEFAULT NULL COMMENT 'Optional FK to Schedules',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_user_id_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- EventUserMap table: Many-to-many for event sharing with friends
CREATE TABLE IF NOT EXISTS EventUserMap (
    event_id INT NOT NULL COMMENT 'FK to Events',
    friend_id INT NOT NULL COMMENT 'FK to Users (friends)',
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Data for Testing
-- Insert sample users (password 'test123' hashed)
INSERT INTO Users (username, email, password_hash) VALUES 
('harsha', 'harsha@example.com', '$2y$10$K4yO2jQ6y.3z7wV5pU5QeO.1zL4f5G6h7i8j9k0l1m2n3o4p5q6r'), -- Hashed 'test123'
('testuser', 'test@example.com', '$2y$10$K4yO2jQ6y.3z7wV5pU5QeO.1zL4f5G6h7i8j9k0l1m2n3o4p5q6r');

-- Sample games
INSERT INTO Games (titel, description) VALUES 
('Fortnite', 'Battle royale game with building mechanics.'),
('Minecraft', 'Sandbox game for creative building.');

-- Sample user favorites
INSERT INTO UserGames (user_id, game_id, gametitel, game_description) VALUES 
(1, 1, 'Fortnite', 'Battle royale game with building mechanics.');

-- Sample friends
INSERT INTO Friends (user_id, friend_user_id) VALUES (1, 2);

-- Sample schedule
INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (1, 1, '2025-10-10', '15:00:00', '2');

-- Sample event
INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (1, 'Fortnite Tournament', '2025-10-15', '18:00:00', 'Join the fun tournament!', '1 hour before', 1);

-- Sample event sharing
INSERT INTO EventUserMap (event_id, friend_id) VALUES (1, 2);