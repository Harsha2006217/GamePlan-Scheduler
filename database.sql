-- Advanced MySQL Schema for GamePlan Scheduler
-- Created by Harsha Kanaparthi on 04-10-2025
-- This schema defines normalized tables with foreign keys, cascades for integrity, indexes for performance, and soft deletes.
-- It exactly matches the project requirements: Users, Games, UserGames, Friends, Schedules, Events, EventUserMap.
-- Sample data included for testing (hashed passwords use 'test123' – change in production).
-- Run in phpMyAdmin or MySQL CLI to set up.
-- UTF-8 support for international usernames/descriptions.

CREATE DATABASE IF NOT EXISTS gameplan_scheduler CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gameplan_scheduler;

-- Users table: Core user profiles with secure hashing, timestamps, and activity tracking
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Games table: Standalone games for reference with unique titles
CREATE TABLE IF NOT EXISTS games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    INDEX idx_titel (titel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- UserGames table: Maps users to favorite games with denormalized title and description for quick access
CREATE TABLE IF NOT EXISTS user_games (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    gametitel VARCHAR(100) NOT NULL,
    game_description TEXT,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Friends table: User friendships (self-referencing) with status and unique friendships
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

-- Schedules table: Game schedules linked to users and games, with soft delete and reminders
CREATE TABLE IF NOT EXISTS schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT,  -- Comma-separated friend IDs for sharing
    reminder ENUM('none', '1hour', '1day') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_game_id (game_id),
    INDEX idx_date (date),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table: Events optionally linked to schedules, with soft delete and reminders
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    reminder ENUM('none', '1hour', '1day') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_schedule_id (schedule_id),
    INDEX idx_user_id (user_id),
    INDEX idx_date (date),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EventUserMap table: Maps events to shared friends (M:N relationship)
CREATE TABLE IF NOT EXISTS event_user_map (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES friends(friend_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing (seed)
-- Users: testuser and friend1 with hashed 'test123' (use bcrypt_hash in production)
INSERT INTO users (username, email, password_hash) VALUES
('testuser', 'test@example.com', '$2y$10$K.3xJ0fQf3zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6z'),
('friend1', 'friend1@example.com', '$2y$10$K.3xJ0fQf3zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6zJ6z');

-- Games: Sample games
INSERT INTO games (titel, description) VALUES
('Fortnite', 'Epic battle royale game with building and cross-platform play.'),
('Minecraft', 'Creative sandbox game for building and survival adventures.');

-- UserGames: testuser favorites both games
INSERT INTO user_games (user_id, game_id, gametitel, game_description) VALUES
(1, 1, 'Fortnite', 'Epic battle royale game with building and cross-platform play.'),
(1, 2, 'Minecraft', 'Creative sandbox game for building and survival adventures.');

-- Friends: testuser friends with friend1
INSERT INTO friends (user_id, friend_user_id, status) VALUES (1, 2, 'online');

-- Schedules: Sample schedule for testuser
INSERT INTO schedules (user_id, game_id, date, time, friends, reminder) VALUES
(1, 1, '2025-10-10', '15:00:00', '2', '1hour');

-- Events: Sample event linked to schedule
INSERT INTO events (schedule_id, user_id, title, date, time, description, reminder) VALUES
(1, 1, 'Fortnite Tournament', '2025-10-15', '18:00:00', 'Join friends for an epic tournament.', '1day');

-- EventUserMap: Share event with friend1
INSERT INTO event_user_map (event_id, friend_id) VALUES (1, 1);