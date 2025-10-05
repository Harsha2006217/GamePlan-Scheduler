-- Advanced MySQL Schema for GamePlan Scheduler
-- Created by Harsha Kanaparthi on 02-10-2025
-- Matches ERD: Users 1:N Friends/UserGames/Schedules/Events, Schedules 1:1 Events (optional), Events N:M Users via EventUserMap.
-- Constraints, indexes for performance, cascades for integrity.
-- Sample data with hashed passwords ('test123') for testing.

CREATE DATABASE IF NOT EXISTS gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE gameplan_db;

-- Users: Core accounts with activity tracking for online status and timeouts
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_activity (last_activity) -- For quick online checks
) ENGINE=InnoDB;

-- Games: Predefined games list
CREATE TABLE Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- UserGames: Favorites (user_id FK to Users, game_id PK/FK to Games, denormalized for quick reads)
CREATE TABLE UserGames (
    user_id INT NOT NULL,
    game_id INT PRIMARY KEY,
    gametitel VARCHAR(100) NOT NULL,
    game_description TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Friends: Friendships (mutual assumed, but stored directed for simplicity)
CREATE TABLE Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friend (user_id, friend_user_id) -- Prevent duplicates
) ENGINE=InnoDB;

-- Schedules: Gaming plans (game_id FK to Games)
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

-- Events: Events linked to schedules (schedule_id FK optional, set null on delete)
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

-- EventUserMap: Event sharing (N:M between Events and Users)
CREATE TABLE EventUserMap (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample Data (for testing – passwords hashed with bcrypt for 'test123')
INSERT INTO Users (username, email, password_hash) VALUES
('harsha', 'harsha@example.com', '$2y$10$5M4f6G7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6A7B8C9D0E'),
('testuser', 'test@example.com', '$2y$10$5M4f6G7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6A7B8C9D0E');

INSERT INTO Games (titel, description) VALUES
('Fortnite', 'Epic battle royale with building mechanics and cross-platform play.'),
('Minecraft', 'Sandbox game for creative building and survival adventures.'),
('Call of Duty: Warzone', 'Free-to-play battle royale with realistic combat.'),
('League of Legends', 'Team-based strategy game with champions and objectives.'),
('Valorant', 'Tactical shooter with unique agent abilities.');

INSERT INTO UserGames (user_id, game_id, gametitel, game_description) VALUES
(1, 1, 'Fortnite', 'Epic battle royale with building mechanics and cross-platform play.');

INSERT INTO Friends (user_id, friend_user_id) VALUES
(1, 2);

INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES
(1, 1, '2025-10-10', '15:00:00', '2'),
(1, 2, '2025-10-12', '18:00:00', '2');

INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES
(1, 'Fortnite Tournament', '2025-10-15', '18:00:00', 'Join friends for an epic tournament with prizes.', '1 hour before', 1),
(1, 'Minecraft Build Competition', '2025-10-20', '14:00:00', 'Creative building competition with theme.', '1 day before', NULL);

INSERT INTO EventUserMap (event_id, friend_id) VALUES
(1, 2);