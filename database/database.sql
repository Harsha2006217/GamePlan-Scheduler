-- GamePlan Scheduler - Enhanced Database Schema
-- Professional database structure for gaming schedule management
-- Author: Harsha Kanaparthi
-- Version: 2.0 Professional Edition

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+01:00";

-- Create database with proper charset and collation
CREATE DATABASE IF NOT EXISTS `gameplan_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `gameplan_db`;

-- Enhanced Users table with comprehensive user management
CREATE TABLE `Users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `lockout_until` timestamp NULL DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_last_activity` (`last_activity`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced Games table with categories and metadata
CREATE TABLE `Games` (
  `game_id` int(11) NOT NULL AUTO_INCREMENT,
  `titel` varchar(100) NOT NULL,
  `description` text,
  `category` varchar(50) DEFAULT 'Action',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `popularity_score` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`game_id`),
  KEY `idx_category` (`category`),
  KEY `idx_popularity` (`popularity_score`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- UserGames junction table for favorite games with additional metadata
CREATE TABLE `UserGames` (
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `play_time_hours` int(11) DEFAULT 0,
  `skill_level` enum('Beginner','Intermediate','Advanced','Expert') DEFAULT 'Beginner',
  `favorite` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`, `game_id`),
  KEY `fk_usergames_game` (`game_id`),
  KEY `idx_favorite` (`favorite`),
  CONSTRAINT `fk_usergames_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usergames_game` FOREIGN KEY (`game_id`) REFERENCES `Games` (`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced Friends table with friendship status
CREATE TABLE `Friends` (
  `friend_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_user_id` int(11) NOT NULL,
  `status` enum('pending','accepted','blocked') NOT NULL DEFAULT 'accepted',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`friend_id`),
  UNIQUE KEY `unique_friendship` (`user_id`, `friend_user_id`),
  KEY `fk_friends_friend` (`friend_user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_friends_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_friends_friend` FOREIGN KEY (`friend_user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced Schedules table with comprehensive scheduling
CREATE TABLE `Schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `friends` text,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  KEY `fk_schedules_user` (`user_id`),
  KEY `fk_schedules_game` (`game_id`),
  KEY `idx_date_time` (`date`, `time`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_schedules_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedules_game` FOREIGN KEY (`game_id`) REFERENCES `Games` (`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced Events table with comprehensive event management
CREATE TABLE `Events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `description` text,
  `reminder` varchar(50) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 10,
  `status` enum('upcoming','active','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `fk_events_user` (`user_id`),
  KEY `fk_events_schedule` (`schedule_id`),
  KEY `idx_date_time` (`date`, `time`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_events_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `Schedules` (`schedule_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EventUserMap for sharing events with friends
CREATE TABLE `EventUserMap` (
  `event_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `status` enum('invited','confirmed','declined') NOT NULL DEFAULT 'invited',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`, `friend_id`),
  KEY `fk_eventmap_friend` (`friend_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_eventmap_event` FOREIGN KEY (`event_id`) REFERENCES `Events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eventmap_friend` FOREIGN KEY (`friend_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert comprehensive sample games data
INSERT INTO `Games` (`game_id`, `titel`, `description`, `category`, `popularity_score`) VALUES
(1, 'Fortnite', 'Battle Royale spel met building mechanics', 'Battle Royale', 95),
(2, 'Call of Duty: Warzone', 'Gratis battle royale shooter', 'FPS', 88),
(3, 'Minecraft', 'Sandbox building en survival game', 'Sandbox', 92),
(4, 'League of Legends', 'Multiplayer Online Battle Arena (MOBA)', 'MOBA', 90),
(5, 'Valorant', 'Tactische 5v5 character-based shooter', 'FPS', 85),
(6, 'Apex Legends', 'Hero shooter battle royale', 'Battle Royale', 82),
(7, 'Counter-Strike 2', 'Competitieve tactische shooter', 'FPS', 89),
(8, 'Rocket League', 'Voetbal met auto\'s', 'Sports', 87),
(9, 'Among Us', 'Social deduction party game', 'Party', 75),
(10, 'Fall Guys', 'Battle royale party platformer', 'Party', 70),
(11, 'FIFA 24', 'Voetbalsimulatie', 'Sports', 78),
(12, 'Grand Theft Auto V', 'Open-world action-adventure', 'Action', 91),
(13, 'Overwatch 2', 'Team-based hero shooter', 'FPS', 83),
(14, 'World of Warcraft', 'Massively multiplayer online RPG', 'MMORPG', 86),
(15, 'Genshin Impact', 'Open-world action RPG', 'RPG', 84);

-- Insert sample users (passwords are hashed versions of 'password123')
INSERT INTO `Users` (`user_id`, `username`, `email`, `password_hash`, `is_active`) VALUES
(1, 'testuser', 'test@gameplan.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewfBmdlyVBkMlx4m', 1),
(2, 'gamer_pro', 'pro@gameplan.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewfBmdlyVBkMlx4m', 1),
(3, 'casual_player', 'casual@gameplan.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewfBmdlyVBkMlx4m', 1);

-- Insert sample user games relationships
INSERT INTO `UserGames` (`user_id`, `game_id`, `skill_level`, `favorite`) VALUES
(1, 1, 'Intermediate', 1),
(1, 3, 'Advanced', 1),
(1, 5, 'Beginner', 0),
(2, 2, 'Expert', 1),
(2, 4, 'Advanced', 1),
(3, 9, 'Intermediate', 1),
(3, 10, 'Beginner', 0);

-- Insert sample friendships
INSERT INTO `Friends` (`user_id`, `friend_user_id`, `status`) VALUES
(1, 2, 'accepted'),
(1, 3, 'accepted'),
(2, 3, 'accepted');

-- Insert sample schedules
INSERT INTO `Schedules` (`user_id`, `game_id`, `date`, `time`, `friends`, `description`) VALUES
(1, 1, '2025-01-15', '20:00:00', '2,3', 'Evening Fortnite session'),
(2, 2, '2025-01-16', '19:30:00', '1', 'Warzone duos'),
(3, 9, '2025-01-17', '21:00:00', '1,2', 'Among Us party');

-- Insert sample events
INSERT INTO `Events` (`user_id`, `title`, `date`, `time`, `description`, `reminder`) VALUES
(1, 'Fortnite Tournament', '2025-01-20', '18:00:00', 'Weekly community tournament', '1 hour before'),
(2, 'Gaming Marathon', '2025-01-25', '14:00:00', '24-hour gaming session', '1 day before'),
(3, 'Casual Game Night', '2025-01-18', '20:00:00', 'Relaxed gaming with friends', '1 hour before');

-- Insert sample event sharing
INSERT INTO `EventUserMap` (`event_id`, `friend_id`, `status`) VALUES
(1, 2, 'confirmed'),
(1, 3, 'invited'),
(2, 1, 'confirmed'),
(3, 1, 'confirmed'),
(3, 2, 'confirmed');

COMMIT;
