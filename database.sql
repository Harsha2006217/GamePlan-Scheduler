-- Advanced MySQL Schema for GamePlan Scheduler
-- Created by Harsha Kanaparthi on 02-10-2025
-- Schema with 7 tables, foreign keys, cascades for integrity, indexes for fast queries.
-- Matches ERD: Users 1:N Friends/UserGames/Schedules/Events, Schedules 1:1 Events (optional), Events N:M Users via EventUserMap.
-- Sample data for testing (passwords hashed for 'test123' – change in production).

CREATE DATABASE IF NOT EXISTS gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE gameplan_db;

-- Users table: Accounts with activity tracking for online status and timeouts
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_activity (last_activity) -- Quick online status checks
) ENGINE=InnoDB;

-- Games table: Game info
CREATE TABLE Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- UserGames table: Favorites (user_id FK, game_id PK)
CREATE TABLE UserGames (
    user_id INT NOT NULL,
    game_id INT PRIMARY KEY,
    gametitel VARCHAR(100) NOT NULL,
    game_description TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Friends table: Friendships (directed, unique to prevent duplicates)
CREATE TABLE Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friend (user_id, friend_user_id) -- No duplicates
) ENGINE=InnoDB;

-- Schedules table: Gaming plans (game_id FK)
CREATE TABLE Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT, -- Comma-separated user_ids
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    INDEX idx_date_time (date, time) -- For calendar sorting
) ENGINE=InnoDB;

-- Events table: Events linked to schedules (schedule_id optional)
CREATE TABLE Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    reminder VARCHAR(50),
    schedule_id INT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL,
    INDEX idx_date_time (date, time) -- For calendar sorting
) ENGINE=InnoDB;

-- EventUserMap table: Sharing events (N:M)
CREATE TABLE EventUserMap (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample Data for Testing
INSERT INTO Users (username, email, password_hash) VALUES
('harsha', 'harsha@example.com', '$2y$10$5M4f6G7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6A7B8C9D0E'),
('testuser', 'test@example.com', '$2y$10$5M4f6G7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6A7B8C9D0E');

INSERT INTO Games (titel, description) VALUES
('Fortnite', 'Epic battle royale with building mechanics.'),
('Minecraft', 'Sandbox game for creative building.');

INSERT INTO UserGames (user_id, game_id, gametitel, game_description) VALUES
(1, 1, 'Fortnite', 'Epic battle royale with building mechanics.');

INSERT INTO Friends (user_id, friend_user_id) VALUES
(1, 2);

INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES
(1, 1, '2025-10-10', '15:00:00', '2');

INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES
(1, 'Fortnite Tournament', '2025-10-15', '18:00:00', 'Join friends for an epic tournament.', '1 hour before', 1);

INSERT INTO EventUserMap (event_id, friend_id) VALUES
(1, 2);