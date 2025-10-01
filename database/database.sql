-- GamePlan Scheduler - Enhanced Professional Database Schema
-- Advanced gaming schedule management system
-- Author: Harsha Kanaparthi
-- Version: 2.1 Professional Edition
-- Date: September 30, 2025

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+01:00";

-- Create database with proper charset and collation for international support
CREATE DATABASE IF NOT EXISTS `gameplan_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `gameplan_db`;

-- ===================== ENHANCED USERS TABLE =====================
-- Advanced user management with security features and activity tracking
CREATE TABLE `Users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  `failed_login_attempts` int(3) DEFAULT 0,
  `lockout_until` timestamp NULL DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'Europe/Amsterdam',
  `profile_image` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `preferences` json DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== ENHANCED GAMES TABLE =====================
-- Comprehensive game catalog with metadata and categorization
CREATE TABLE `Games` (
  `game_id` int(11) NOT NULL AUTO_INCREMENT,
  `titel` varchar(100) NOT NULL,
  `description` text,
  `genre` varchar(50) DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `release_year` int(4) DEFAULT NULL,
  `max_players` int(3) DEFAULT NULL,
  `min_players` int(3) DEFAULT 1,
  `average_session_time` int(5) DEFAULT NULL,
  `rating` enum('E','T','M','AO') DEFAULT 'E',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `image_url` varchar(255) DEFAULT NULL,
  `developer` varchar(100) DEFAULT NULL,
  `popularity_score` int(5) DEFAULT 0,
  PRIMARY KEY (`game_id`),
  KEY `idx_titel` (`titel`),
  KEY `idx_genre` (`genre`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_popularity` (`popularity_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== USER-GAMES RELATIONSHIP TABLE =====================
-- Links users to their favorite games with additional metadata
CREATE TABLE `UserGames` (
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `added_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `skill_level` enum('Beginner','Intermediate','Advanced','Expert') DEFAULT 'Beginner',
  `hours_played` int(6) DEFAULT 0,
  `favorite_mode` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_currently_playing` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`user_id`, `game_id`),
  KEY `idx_added_at` (`added_at`),
  KEY `idx_skill_level` (`skill_level`),
  CONSTRAINT `fk_usergames_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usergames_game` FOREIGN KEY (`game_id`) REFERENCES `Games` (`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== ENHANCED FRIENDS TABLE =====================
-- Advanced friendship system with status tracking
CREATE TABLE `Friends` (
  `friend_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_user_id` int(11) NOT NULL,
  `status` enum('pending','accepted','blocked') DEFAULT 'accepted',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_favorite` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`friend_id`),
  UNIQUE KEY `unique_friendship` (`user_id`, `friend_user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_friend_user_id` (`friend_user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_friends_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_friends_friend` FOREIGN KEY (`friend_user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== ENHANCED SCHEDULES TABLE =====================
-- Comprehensive gaming schedule management
CREATE TABLE `Schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `friends` text DEFAULT NULL,
  `max_participants` int(3) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) DEFAULT 'Online',
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_pattern` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reminder_sent` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`schedule_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_game_id` (`game_id`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_schedules_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedules_game` FOREIGN KEY (`game_id`) REFERENCES `Games` (`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== ENHANCED EVENTS TABLE =====================
-- Advanced event management with comprehensive features
CREATE TABLE `Events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('tournament','meetup','practice','stream','competition','casual') DEFAULT 'casual',
  `date` date NOT NULL,
  `time` time NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(100) DEFAULT 'Online',
  `max_participants` int(5) DEFAULT NULL,
  `entry_fee` decimal(10,2) DEFAULT NULL,
  `prize_pool` decimal(10,2) DEFAULT NULL,
  `reminder` varchar(50) DEFAULT NULL,
  `reminder_time` timestamp NULL DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `is_public` tinyint(1) DEFAULT 1,
  `registration_required` tinyint(1) DEFAULT 0,
  `external_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_schedule_id` (`schedule_id`),
  KEY `idx_date` (`date`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_events_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `Schedules` (`schedule_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== EVENT USER MAPPING TABLE =====================
-- Advanced event sharing and participation tracking
CREATE TABLE `EventUserMap` (
  `event_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `invited_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `response_status` enum('pending','accepted','declined','maybe') DEFAULT 'pending',
  `responded_at` timestamp NULL DEFAULT NULL,
  `participation_type` enum('participant','organizer','spectator') DEFAULT 'participant',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`event_id`, `friend_id`),
  KEY `idx_response_status` (`response_status`),
  KEY `idx_participation_type` (`participation_type`),
  CONSTRAINT `fk_eventmap_event` FOREIGN KEY (`event_id`) REFERENCES `Events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eventmap_user` FOREIGN KEY (`friend_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== SAMPLE DATA INSERTION =====================
-- Professional sample data for testing and demonstration

-- Insert sample games with comprehensive metadata
INSERT INTO `Games` (`titel`, `description`, `genre`, `platform`, `release_year`, `max_players`, `min_players`, `average_session_time`, `rating`, `developer`, `popularity_score`) VALUES
('Fortnite', 'Battle royale game with building mechanics and competitive gameplay', 'Battle Royale', 'PC, Console, Mobile', 2017, 100, 1, 25, 'T', 'Epic Games', 95),
('Counter-Strike 2', 'Tactical first-person shooter with competitive ranking system', 'FPS', 'PC', 2023, 10, 2, 45, 'M', 'Valve Corporation', 90),
('League of Legends', 'Multiplayer online battle arena with strategic team-based gameplay', 'MOBA', 'PC', 2009, 10, 2, 35, 'T', 'Riot Games', 88),
('Valorant', 'Tactical shooter combining precise gunplay with unique agent abilities', 'FPS', 'PC', 2020, 10, 2, 40, 'T', 'Riot Games', 85),
('Minecraft', 'Sandbox game allowing creativity and exploration in blocky worlds', 'Sandbox', 'PC, Console, Mobile', 2011, 10, 1, 60, 'E', 'Mojang Studios', 92),
('Among Us', 'Social deduction game with teamwork and deception elements', 'Party', 'PC, Mobile, Console', 2018, 15, 4, 15, 'E', 'InnerSloth', 75),
('Rocket League', 'Vehicle-based soccer game with physics-driven gameplay', 'Sports', 'PC, Console', 2015, 8, 1, 20, 'E', 'Psyonix', 80),
('World of Warcraft', 'Massively multiplayer online role-playing game in fantasy setting', 'MMORPG', 'PC', 2004, 40, 1, 120, 'T', 'Blizzard Entertainment', 85),
('Fall Guys', 'Battle royale party game with colorful obstacle courses', 'Party', 'PC, Console, Mobile', 2020, 60, 1, 10, 'E', 'Mediatonic', 70),
('Overwatch 2', 'Team-based first-person shooter with hero-based gameplay', 'FPS', 'PC, Console', 2022, 10, 2, 30, 'T', 'Blizzard Entertainment', 82),
('Apex Legends', 'Battle royale with unique character abilities and squad-based combat', 'Battle Royale', 'PC, Console, Mobile', 2019, 60, 1, 25, 'T', 'Respawn Entertainment', 83),
('Call of Duty: Warzone', 'Large-scale battle royale with realistic military combat', 'Battle Royale', 'PC, Console', 2020, 150, 1, 30, 'M', 'Infinity Ward', 78),
('Genshin Impact', 'Open-world action RPG with gacha mechanics and exploration', 'Action RPG', 'PC, Console, Mobile', 2020, 4, 1, 45, 'T', 'miHoYo', 87),
('Destiny 2', 'Online multiplayer first-person shooter with RPG elements', 'FPS RPG', 'PC, Console', 2017, 6, 1, 60, 'T', 'Bungie', 79),
('FIFA 23', 'Football simulation game with realistic gameplay and teams', 'Sports', 'PC, Console', 2022, 2, 1, 25, 'E', 'EA Sports', 76);

-- Insert sample users with realistic data
INSERT INTO `Users` (`username`, `email`, `password_hash`, `is_active`, `timezone`, `email_verified`) VALUES
('TestGamer', 'test@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1),
('ProPlayer99', 'proplayer@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1),
('CasualGamer', 'casual@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1),
('StreamerLife', 'streamer@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1),
('CompetitiveAce', 'comp@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1);

-- Insert sample user-game relationships
INSERT INTO `UserGames` (`user_id`, `game_id`, `skill_level`, `hours_played`, `favorite_mode`, `is_currently_playing`) VALUES
(1, 1, 'Intermediate', 250, 'Battle Royale', 1),
(1, 2, 'Advanced', 500, 'Competitive', 1),
(1, 5, 'Expert', 1200, 'Creative', 1),
(2, 2, 'Expert', 2000, 'Competitive', 1),
(2, 4, 'Advanced', 800, 'Ranked', 1),
(2, 3, 'Expert', 1500, 'Ranked Solo', 1),
(3, 5, 'Intermediate', 400, 'Survival', 1),
(3, 6, 'Beginner', 50, 'Classic', 1),
(3, 9, 'Intermediate', 100, 'Main Show', 1),
(4, 1, 'Advanced', 800, 'Creative', 1),
(4, 7, 'Expert', 600, 'Competitive', 1),
(4, 10, 'Advanced', 450, 'Quick Play', 1),
(5, 2, 'Expert', 1800, 'Competitive', 1),
(5, 4, 'Expert', 1200, 'Ranked', 1),
(5, 11, 'Advanced', 300, 'Battle Royale', 1);

-- Insert sample friendships
INSERT INTO `Friends` (`user_id`, `friend_user_id`, `status`, `is_favorite`) VALUES
(1, 2, 'accepted', 1),
(1, 3, 'accepted', 0),
(1, 4, 'accepted', 1),
(2, 1, 'accepted', 1),
(2, 3, 'accepted', 0),
(2, 5, 'accepted', 1),
(3, 1, 'accepted', 0),
(3, 2, 'accepted', 0),
(3, 4, 'accepted', 1),
(4, 1, 'accepted', 1),
(4, 3, 'accepted', 1),
(4, 5, 'accepted', 0),
(5, 2, 'accepted', 1),
(5, 4, 'accepted', 0);

-- Insert sample schedules
INSERT INTO `Schedules` (`user_id`, `game_id`, `title`, `date`, `time`, `end_time`, `friends`, `description`, `status`, `max_participants`) VALUES
(1, 1, 'Evening Fortnite Squad', '2025-10-01', '20:00:00', '22:00:00', '2,3,4', 'Casual squad games with friends', 'scheduled', 4),
(1, 2, 'CS2 Competitive Match', '2025-10-02', '19:30:00', '21:30:00', '2,5', 'Ranked matchmaking session', 'scheduled', 5),
(2, 3, 'LoL Ranked Climb', '2025-10-01', '18:00:00', '23:00:00', '1,5', 'Serious ranked climbing session', 'scheduled', 2),
(3, 5, 'Minecraft Building Project', '2025-10-03', '15:00:00', '18:00:00', '1,4', 'Working on castle project together', 'scheduled', 4),
(4, 7, 'Rocket League Tournament Prep', '2025-10-02', '17:00:00', '19:00:00', '1,3', 'Practice for upcoming tournament', 'scheduled', 3),
(5, 4, 'Valorant Ranked Session', '2025-10-04', '20:30:00', '23:00:00', '2,4', 'Pushing for higher rank', 'scheduled', 5);

-- Insert sample events
INSERT INTO `Events` (`user_id`, `title`, `description`, `event_type`, `date`, `time`, `end_time`, `location`, `max_participants`, `reminder`, `status`, `is_public`) VALUES
(1, 'Weekly Gaming Tournament', 'Community tournament with prizes for winners', 'tournament', '2025-10-05', '14:00:00', '18:00:00', 'GamePlan Community Server', 32, '1 hour before', 'upcoming', 1),
(2, 'CS2 Scrim Practice', 'Team practice session before major tournament', 'practice', '2025-10-03', '19:00:00', '21:00:00', 'Private Server', 10, '30 minutes before', 'upcoming', 0),
(3, 'Casual Gaming Meetup', 'Relaxed gaming session with snacks and fun', 'meetup', '2025-10-06', '13:00:00', '17:00:00', 'Local Gaming Cafe', 8, '1 day before', 'upcoming', 1),
(4, 'Streaming Session', 'Live streaming various games with viewer interaction', 'stream', '2025-10-02', '20:00:00', '23:00:00', 'Twitch Channel', 100, '15 minutes before', 'upcoming', 1),
(5, 'Championship Qualifier', 'Qualifying match for regional championship', 'competition', '2025-10-07', '16:00:00', '20:00:00', 'Tournament Platform', 16, '2 hours before', 'upcoming', 1);

-- Insert sample event user mappings
INSERT INTO `EventUserMap` (`event_id`, `friend_id`, `response_status`, `participation_type`) VALUES
(1, 2, 'accepted', 'participant'),
(1, 3, 'accepted', 'participant'),
(1, 4, 'pending', 'participant'),
(2, 1, 'accepted', 'participant'),
(2, 5, 'accepted', 'participant'),
(3, 1, 'accepted', 'participant'),
(3, 4, 'maybe', 'participant'),
(4, 1, 'accepted', 'spectator'),
(4, 3, 'accepted', 'spectator'),
(5, 2, 'accepted', 'participant'),
(5, 4, 'declined', 'participant');

-- ===================== PERFORMANCE OPTIMIZATION INDEXES =====================
-- Additional indexes for optimal query performance

-- Composite indexes for common query patterns
CREATE INDEX `idx_user_date_schedules` ON `Schedules` (`user_id`, `date`, `status`);
CREATE INDEX `idx_user_date_events` ON `Events` (`user_id`, `date`, `status`);
CREATE INDEX `idx_friend_lookup` ON `Friends` (`user_id`, `friend_user_id`, `status`);
CREATE INDEX `idx_event_reminders` ON `Events` (`reminder_time`, `status`);
CREATE INDEX `idx_schedule_reminders` ON `Schedules` (`date`, `time`, `reminder_sent`);

-- Full-text search indexes for better search functionality
ALTER TABLE `Games` ADD FULLTEXT(`titel`, `description`);
ALTER TABLE `Events` ADD FULLTEXT(`title`, `description`);

-- ===================== TRIGGERS FOR DATA INTEGRITY =====================
-- Automatic data maintenance and integrity enforcement

DELIMITER //

-- Trigger to prevent self-friendship
CREATE TRIGGER `prevent_self_friendship` 
BEFORE INSERT ON `Friends` 
FOR EACH ROW 
BEGIN 
    IF NEW.user_id = NEW.friend_user_id THEN 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Users cannot add themselves as friends';
    END IF;
END//

-- Trigger to update user activity on login
CREATE TRIGGER `update_user_activity` 
BEFORE UPDATE ON `Users` 
FOR EACH ROW 
BEGIN 
    IF NEW.last_activity != OLD.last_activity THEN
        SET NEW.failed_login_attempts = 0;
        SET NEW.lockout_until = NULL;
    END IF;
END//

-- Trigger to automatically set reminder times for events
CREATE TRIGGER `set_event_reminder_time` 
BEFORE INSERT ON `Events` 
FOR EACH ROW 
BEGIN 
    IF NEW.reminder IS NOT NULL THEN
        CASE NEW.reminder
            WHEN '15 minutes before' THEN 
                SET NEW.reminder_time = TIMESTAMP(NEW.date, NEW.time) - INTERVAL 15 MINUTE;
            WHEN '30 minutes before' THEN 
                SET NEW.reminder_time = TIMESTAMP(NEW.date, NEW.time) - INTERVAL 30 MINUTE;
            WHEN '1 hour before' THEN 
                SET NEW.reminder_time = TIMESTAMP(NEW.date, NEW.time) - INTERVAL 1 HOUR;
            WHEN '2 hours before' THEN 
                SET NEW.reminder_time = TIMESTAMP(NEW.date, NEW.time) - INTERVAL 2 HOUR;
            WHEN '1 day before' THEN 
                SET NEW.reminder_time = TIMESTAMP(NEW.date, NEW.time) - INTERVAL 1 DAY;
        END CASE;
    END IF;
END//

DELIMITER ;

-- ===================== VIEWS FOR COMPLEX QUERIES =====================
-- Predefined views for commonly used data combinations

-- View for user dashboard statistics
CREATE VIEW `user_dashboard_stats` AS
SELECT 
    u.user_id,
    u.username,
    COUNT(DISTINCT f.friend_user_id) as friend_count,
    COUNT(DISTINCT ug.game_id) as favorite_games_count,
    COUNT(DISTINCT s.schedule_id) as upcoming_schedules,
    COUNT(DISTINCT e.event_id) as upcoming_events
FROM `Users` u
LEFT JOIN `Friends` f ON u.user_id = f.user_id AND f.status = 'accepted'
LEFT JOIN `UserGames` ug ON u.user_id = ug.user_id AND ug.is_currently_playing = 1
LEFT JOIN `Schedules` s ON u.user_id = s.user_id AND s.date >= CURDATE() AND s.status = 'scheduled'
LEFT JOIN `Events` e ON u.user_id = e.user_id AND e.date >= CURDATE() AND e.status = 'upcoming'
GROUP BY u.user_id, u.username;

-- View for upcoming activities with game information
CREATE VIEW `upcoming_activities` AS
SELECT 
    'schedule' as activity_type,
    s.schedule_id as activity_id,
    s.user_id,
    COALESCE(s.title, CONCAT(g.titel, ' Session')) as title,
    s.description,
    s.date,
    s.time,
    s.end_time,
    g.titel as game_title,
    g.genre,
    s.friends,
    s.max_participants,
    s.status,
    s.created_at
FROM `Schedules` s
JOIN `Games` g ON s.game_id = g.game_id
WHERE s.date >= CURDATE() AND s.status = 'scheduled'

UNION ALL

SELECT 
    'event' as activity_type,
    e.event_id as activity_id,
    e.user_id,
    e.title,
    e.description,
    e.date,
    e.time,
    e.end_time,
    NULL as game_title,
    e.event_type as genre,
    NULL as friends,
    e.max_participants,
    e.status,
    e.created_at
FROM `Events` e
WHERE e.date >= CURDATE() AND e.status = 'upcoming'

ORDER BY date ASC, time ASC;

-- ===================== STORED PROCEDURES =====================
-- Useful procedures for common operations

DELIMITER //

-- Procedure to get user statistics
CREATE PROCEDURE `GetUserStatistics`(IN userId INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM Friends WHERE user_id = userId AND status = 'accepted') as total_friends,
        (SELECT COUNT(*) FROM UserGames WHERE user_id = userId AND is_currently_playing = 1) as favorite_games,
        (SELECT COUNT(*) FROM Schedules WHERE user_id = userId AND date >= CURDATE()) as upcoming_schedules,
        (SELECT COUNT(*) FROM Events WHERE user_id = userId AND date >= CURDATE()) as upcoming_events,
        (SELECT SUM(hours_played) FROM UserGames WHERE user_id = userId) as total_hours_played;
END//

-- Procedure to clean up old data
CREATE PROCEDURE `CleanupOldData`()
BEGIN
    -- Archive completed events older than 6 months
    UPDATE Events SET status = 'completed' 
    WHERE date < DATE_SUB(NOW(), INTERVAL 6 MONTH) AND status != 'completed';
    
    -- Archive completed schedules older than 3 months
    UPDATE Schedules SET status = 'completed' 
    WHERE date < DATE_SUB(NOW(), INTERVAL 3 MONTH) AND status != 'completed';
    
    -- Reset failed login attempts older than 24 hours
    UPDATE Users SET failed_login_attempts = 0, lockout_until = NULL 
    WHERE lockout_until < NOW();
END//

DELIMITER ;

-- ===================== SECURITY MEASURES =====================
-- Additional security configurations

-- Create read-only user for reporting
CREATE USER IF NOT EXISTS 'gameplan_readonly'@'localhost' IDENTIFIED BY 'readonly_secure_2025!';
GRANT SELECT ON gameplan_db.* TO 'gameplan_readonly'@'localhost';

-- Create backup user
CREATE USER IF NOT EXISTS 'gameplan_backup'@'localhost' IDENTIFIED BY 'backup_secure_2025!';
GRANT SELECT, LOCK TABLES ON gameplan_db.* TO 'gameplan_backup'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

COMMIT;
-- ===================== NOTIFICATIONS TABLE =====================
-- Advanced notification system for event management
CREATE TABLE `Notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('event_invite','event_update','event_reminder','friend_request','system') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expire_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_read` (`is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Add UserStatus table for real-time status tracking
CREATE TABLE IF NOT EXISTS UserStatus (
    user_id INT PRIMARY KEY,
    status_type ENUM('offline', 'online', 'playing', 'break', 'looking') NOT NULL DEFAULT 'offline',
    game_id INT NULL,
    status_message VARCHAR(255) NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE SET NULL
) ENGINE=InnoDB;
-- Add enhanced notifications table
CREATE TABLE IF NOT EXISTS `Notifications` (
    `notification_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` enum('schedule_invite', 'friend_request', 'game_invite', 'schedule_update', 'friend_playing', 'game_join', 'schedule_reminder', 'achievement', 'system') NOT NULL,
    `title` varchar(100) NOT NULL,
    `message` text NOT NULL,
    `link_url` varchar(255) DEFAULT NULL,
    `reference_id` int(11) DEFAULT NULL,
    `reference_type` varchar(50) DEFAULT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `is_email_sent` tinyint(1) DEFAULT 0,
    `email_attempts` int(2) DEFAULT 0,
    `priority` enum('low', 'normal', 'high') DEFAULT 'normal',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `read_at` timestamp NULL DEFAULT NULL,
    `expire_at` timestamp NULL DEFAULT NULL,
    `email_scheduled_for` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`notification_id`),
    KEY `idx_user_notifications` (`user_id`, `is_read`, `created_at`),
    KEY `idx_type_expire` (`type`, `expire_at`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user notification preferences
CREATE TABLE IF NOT EXISTS `NotificationPreferences` (
    `user_id` int(11) NOT NULL,
    `notification_type` enum('schedule_invite', 'friend_request', 'game_invite', 'schedule_update', 'friend_playing', 'game_join', 'schedule_reminder', 'achievement', 'system') NOT NULL,
    `email_enabled` tinyint(1) DEFAULT 1,
    `browser_enabled` tinyint(1) DEFAULT 1,
    `quiet_hours_start` time DEFAULT NULL,
    `quiet_hours_end` time DEFAULT NULL,
    PRIMARY KEY (`user_id`, `notification_type`),
    CONSTRAINT `fk_notification_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add email queue for notification emails
CREATE TABLE IF NOT EXISTS `EmailQueue` (
    `email_id` int(11) NOT NULL AUTO_INCREMENT,
    `notification_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `email_type` varchar(50) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `body` text NOT NULL,
    `status` enum('pending', 'sending', 'sent', 'failed') DEFAULT 'pending',
    `attempts` int(2) DEFAULT 0,
    `last_attempt` timestamp NULL DEFAULT NULL,
    `scheduled_for` timestamp NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`email_id`),
    KEY `idx_status_schedule` (`status`, `scheduled_for`),
    CONSTRAINT `fk_email_queue_notification` FOREIGN KEY (`notification_id`) REFERENCES `Notifications` (`notification_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_email_queue_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Add schedule templates tables
CREATE TABLE IF NOT EXISTS `ScheduleTemplates` (
    `template_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `game_id` int(11) NOT NULL,
    `description` text DEFAULT NULL,
    `time` time NOT NULL,
    `duration` int(5) DEFAULT 60,
    `max_participants` int(3) DEFAULT NULL,
    `recurring_pattern` enum('daily', 'weekly', 'biweekly', 'monthly') DEFAULT NULL,
    `weekdays` set('monday','tuesday','wednesday','thursday','friday','saturday','sunday') DEFAULT NULL,
    `monthly_day` int(2) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`template_id`),
    KEY `idx_user_game` (`user_id`, `game_id`),
    CONSTRAINT `fk_template_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_template_game` FOREIGN KEY (`game_id`) REFERENCES `Games` (`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template friend invites
CREATE TABLE IF NOT EXISTS `TemplateInvites` (
    `template_id` int(11) NOT NULL,
    `friend_id` int(11) NOT NULL,
    `auto_invite` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`template_id`, `friend_id`),
    CONSTRAINT `fk_templateinvites_template` FOREIGN KEY (`template_id`) 
        REFERENCES `ScheduleTemplates` (`template_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_templateinvites_friend` FOREIGN KEY (`friend_id`) 
        REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template generated schedules tracking
CREATE TABLE IF NOT EXISTS `TemplateSchedules` (
    `template_id` int(11) NOT NULL,
    `schedule_id` int(11) NOT NULL,
    `generated_for_date` date NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`template_id`, `schedule_id`),
    KEY `idx_date` (`generated_for_date`),
    CONSTRAINT `fk_templateschedules_template` FOREIGN KEY (`template_id`) 
        REFERENCES `ScheduleTemplates` (`template_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_templateschedules_schedule` FOREIGN KEY (`schedule_id`) 
        REFERENCES `Schedules` (`schedule_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ===================== DATABASE DOCUMENTATION =====================
/*
ADVANCED GAMEPLAN SCHEDULER DATABASE SCHEMA DOCUMENTATION

TABLES OVERVIEW:
1. Users: Enhanced user management with security features
2. Games: Comprehensive game catalog with metadata  
3. UserGames: User-game relationships with skill tracking
4. Friends: Advanced friendship system with status
5. Schedules: Gaming schedule management with recurrence
6. Events: Event system with participation tracking
7. EventUserMap: Event sharing and invitation system

SECURITY FEATURES:
- Foreign key constraints for data integrity
- Triggers for business logic enforcement
- Indexed columns for performance optimization
- User privilege separation
- Password hashing requirement
- Failed login attempt tracking

PERFORMANCE OPTIMIZATIONS:
- Strategic indexing on frequently queried columns
- Composite indexes for complex queries
- Full-text search capabilities
- Optimized views for dashboard data
- Stored procedures for common operations

SCALABILITY FEATURES:
- JSON fields for flexible user preferences
- Enum types for controlled vocabularies
- Proper charset and collation for international support
- Partitioning-ready design for future growth
- Audit trail capabilities with timestamps

This schema supports all user stories from the planning documentation
and provides a solid foundation for the GamePlan Scheduler application.
*/
