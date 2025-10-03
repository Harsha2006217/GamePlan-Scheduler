-- database.sql: Advanced MySQL schema for GamePlan Scheduler
-- Normalized tables with FK constraints, indexes, UTF-8 support
-- Based on project design: Users, Friends, Schedules, Events, plus UserGames, EventUserMap

CREATE DATABASE IF NOT EXISTS gameplan_scheduler CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gameplan_scheduler;

-- Users table: Core user profiles with secure hashing
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Games table: Standalone games for reference
CREATE TABLE IF NOT EXISTS games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT,
    INDEX idx_titel (titel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- UserGames table: Maps users to favorite games (M:N relationship)
CREATE TABLE IF NOT EXISTS user_games (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Friends table: User friendships (self-referencing)
CREATE TABLE IF NOT EXISTS friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    status ENUM('online', 'offline') DEFAULT 'offline',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_friendship (user_id, friend_user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_friend_user_id (friend_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schedules table: Game schedules linked to games and users
CREATE TABLE IF NOT EXISTS schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    game VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT,  -- Comma-separated friend IDs for sharing
    reminder ENUM('none', '1hour', '1day') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table: Events linked to schedules
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    reminder ENUM('none', '1hour', '1day') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_schedule_id (schedule_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EventUserMap table: Maps events to shared friends (M:N)
CREATE TABLE IF NOT EXISTS event_user_map (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES friends(friend_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing (seed)
INSERT INTO users (username, email, password_hash) VALUES
('testuser', 'test@example.com', '$2y$10$examplehash'),  -- Use bcrypt in production
('friend1', 'friend1@example.com', '$2y$10$examplehash');

INSERT INTO games (titel, description) VALUES
('Fortnite', 'Battle Royale game'),
('Minecraft', 'Sandbox building game');

INSERT INTO user_games (user_id, game_id) VALUES (1, 1), (1, 2);

INSERT INTO friends (user_id, friend_user_id, status) VALUES (1, 2, 'online');

INSERT INTO schedules (user_id, game_id, game, date, time, friends, reminder) VALUES
(1, 1, 'Fortnite', '2025-10-10', '15:00:00', '2', '1hour');

INSERT INTO events (schedule_id, user_id, title, date, time, description, reminder) VALUES
(1, 1, 'Tournament', '2025-10-10', '15:00:00', 'Online tourney', '1day');

INSERT INTO event_user_map (event_id, friend_id) VALUES (1, 1);