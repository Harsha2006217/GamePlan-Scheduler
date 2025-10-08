-- database.sql - Database Schema Script
-- Author: Harsha Kanaparthi
-- Date: 30-09-2025
-- Description: Creates the gameplan_db database with 7 tables, relationships, and indexes.
-- Added deleted_at for soft delete, note in Friends and UserGames, external_link and shared_with in Events and Schedules.

CREATE DATABASE IF NOT EXISTS gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gameplan_db;

-- Users Table
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Games Table
CREATE TABLE IF NOT EXISTS Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- UserGames Table (Favorites with note)
CREATE TABLE IF NOT EXISTS UserGames (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    note TEXT,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Friends Table with note
CREATE TABLE IF NOT EXISTS Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    note TEXT,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Schedules Table with shared_with
CREATE TABLE IF NOT EXISTS Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT,
    shared_with TEXT,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events Table with external_link and shared_with
CREATE TABLE IF NOT EXISTS Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    reminder VARCHAR(50),
    external_link VARCHAR(255),
    shared_with TEXT,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- EventUserMap Table (optional, but kept for potential expansion)
CREATE TABLE IF NOT EXISTS EventUserMap (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for performance
CREATE INDEX idx_users_email ON Users(email);
CREATE INDEX idx_schedules_user_date ON Schedules(user_id, date);
CREATE INDEX idx_events_user_date ON Events(user_id, date);

-- Sample Data
INSERT INTO Games (titel, description) VALUES 
('Fortnite', 'Battle Royale game'),
('Minecraft', 'Sandbox building game'),
('League of Legends', 'MOBA strategy game');