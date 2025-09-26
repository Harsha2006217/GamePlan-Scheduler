-- GamePlan Scheduler Database Schema
-- Professional database design with proper relationships, constraints, and indexes.
-- Version 2.0

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `gameplan_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gameplan_db`;

-- Table for users
CREATE TABLE IF NOT EXISTS `Users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table for games
CREATE TABLE IF NOT EXISTS `Games` (
    `game_id` INT AUTO_INCREMENT PRIMARY KEY,
    `titel` VARCHAR(100) NOT NULL,
    `description` TEXT NULL
) ENGINE=InnoDB;

-- Junction table for users' favorite games (Many-to-Many)
CREATE TABLE IF NOT EXISTS `UserGames` (
    `user_id` INT NOT NULL,
    `game_id` INT NOT NULL,
    PRIMARY KEY (`user_id`, `game_id`),
    FOREIGN KEY (`user_id`) REFERENCES `Users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`game_id`) REFERENCES `Games`(`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table for friendships
CREATE TABLE IF NOT EXISTS `Friends` (
    `friendship_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `friend_user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `Users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`friend_user_id`) REFERENCES `Users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table for schedules
CREATE TABLE IF NOT EXISTS `Schedules` (
    `schedule_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `game_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `friends` TEXT NULL COMMENT 'Comma-separated list of friend user_ids',
    FOREIGN KEY (`user_id`) REFERENCES `Users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`game_id`) REFERENCES `Games`(`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table for events
CREATE TABLE IF NOT EXISTS `Events` (
    `event_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `reminder` VARCHAR(50) NULL,
    `schedule_id` INT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `Users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`schedule_id`) REFERENCES `Schedules`(`schedule_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Junction table for sharing events with friends (Many-to-Many)
CREATE TABLE IF NOT EXISTS `EventUserMap` (
    `event_id` INT NOT NULL,
    `friend_id` INT NOT NULL,
    PRIMARY KEY (`event_id`, `friend_id`),
    FOREIGN KEY (`event_id`) REFERENCES `Events`(`event_id`) ON DELETE CASCADE,
    FOREIGN KEY (`friend_id`) REFERENCES `Users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Add indexes for performance
CREATE INDEX idx_users_email ON Users(email);
CREATE INDEX idx_users_username ON Users(username);
CREATE INDEX idx_friends_user_id ON Friends(user_id);
CREATE INDEX idx_friends_friend_user_id ON Friends(friend_user_id);
CREATE INDEX idx_schedules_user_id ON Schedules(user_id);
CREATE INDEX idx_schedules_date ON Schedules(date);
CREATE INDEX idx_events_user_id ON Events(user_id);
CREATE INDEX idx_events_date ON Events(date);
CREATE INDEX idx_eventusermap_event_id ON EventUserMap(event_id);
CREATE INDEX idx_eventusermap_friend_id ON EventUserMap(friend_id);

-- Insert sample games
INSERT INTO Games (titel, description) VALUES
('Fortnite', 'A battle royale game with building mechanics.'),
('Among Us', 'An online multiplayer social deduction game.'),
('Minecraft', 'A sandbox game for creativity and survival.'),
('League of Legends', 'A multiplayer online battle arena game.'),
('Call of Duty', 'A first-person shooter game series.'),
('FIFA', 'A football simulation game.'),
('The Sims', 'A life simulation game.'),
('GTA V', 'An open-world action-adventure game.'),
('Overwatch', 'A team-based multiplayer first-person shooter.'),
('World of Warcraft', 'A massively multiplayer online role-playing game.');
CREATE INDEX idx_users_username ON Users(username);
CREATE INDEX idx_schedules_date_time ON Schedules(date, time);
CREATE INDEX idx_events_date_time ON Events(date, time);

-- Insert some sample games
INSERT INTO `Games` (`titel`, `description`) VALUES
('Fortnite', 'A popular battle royale game.'),
('Minecraft', 'A sandbox game about placing blocks and going on adventures.'),
('League of Legends', 'A multiplayer online battle arena (MOBA) game.'),
('Valorant', 'A tactical first-person shooter.'),
('Among Us', 'An online multiplayer social deduction game.');