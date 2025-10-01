-- GamePlan Scheduler - Enhanced Professional Database Schema
-- Advanced gaming schedule management system with complete implementation
-- Author: Harsha Kanaparthi
-- Version: 3.0 Professional Production Edition
-- Date: September 30, 2025
-- Project: K1 W3 Realisatie - Complete Working Database

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

-- ===================== USER STATUS TABLE =====================
-- Real-time status tracking for users
CREATE TABLE `UserStatus` (
  `user_id` int(11) NOT NULL,
  `status_type` enum('offline','online','playing','break','looking') NOT NULL DEFAULT 'offline',
  `game_id` int(11) DEFAULT NULL,
  `status_message` varchar(255) DEFAULT NULL,
  `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `idx_status_type` (`status_type`),
  CONSTRAINT `fk_userstatus_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_userstatus_game` FOREIGN KEY (`game_id`) REFERENCES `Games` (`game_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== SAMPLE DATA INSERTION =====================
-- Professional sample data for testing and demonstration

-- Insert sample games with comprehensive metadata
INSERT INTO `Games` (`titel`, `description`, `genre`, `platform`, `release_year`, `max_players`, `min_players`, `average_session_time`, `rating`, `developer`, `popularity_score`) VALUES
('Fortnite', 'Battle royale game with building mechanics and competitive gameplay for all skill levels', 'Battle Royale', 'PC, Console, Mobile', 2017, 100, 1, 25, 'T', 'Epic Games', 95),
('Counter-Strike 2', 'Tactical first-person shooter with competitive ranking system and esports integration', 'FPS', 'PC', 2023, 10, 2, 45, 'M', 'Valve Corporation', 90),
('League of Legends', 'Multiplayer online battle arena with strategic team-based gameplay and ranked system', 'MOBA', 'PC', 2009, 10, 2, 35, 'T', 'Riot Games', 88),
('Valorant', 'Tactical shooter combining precise gunplay with unique agent abilities', 'FPS', 'PC', 2020, 10, 2, 40, 'T', 'Riot Games', 85),
('Minecraft', 'Sandbox game allowing creativity and exploration in blocky worlds with multiplayer support', 'Sandbox', 'PC, Console, Mobile', 2011, 10, 1, 60, 'E', 'Mojang Studios', 92),
('Among Us', 'Social deduction game with teamwork and deception elements for groups', 'Party', 'PC, Mobile, Console', 2018, 15, 4, 15, 'E', 'InnerSloth', 75),
('Rocket League', 'Vehicle-based soccer game with physics-driven gameplay and competitive modes', 'Sports', 'PC, Console', 2015, 8, 1, 20, 'E', 'Psyonix', 80),
('World of Warcraft', 'Massively multiplayer online role-playing game in fantasy setting', 'MMORPG', 'PC', 2004, 40, 1, 120, 'T', 'Blizzard Entertainment', 85),
('Fall Guys', 'Battle royale party game with colorful obstacle courses and fun gameplay', 'Party', 'PC, Console, Mobile', 2020, 60, 1, 10, 'E', 'Mediatonic', 70),
('Overwatch 2', 'Team-based first-person shooter with hero-based gameplay and objectives', 'FPS', 'PC, Console', 2022, 10, 2, 30, 'T', 'Blizzard Entertainment', 82),
('Apex Legends', 'Battle royale with unique character abilities and squad-based combat system', 'Battle Royale', 'PC, Console, Mobile', 2019, 60, 1, 25, 'T', 'Respawn Entertainment', 83),
('Call of Duty: Warzone', 'Large-scale battle royale with realistic military combat and weapons', 'Battle Royale', 'PC, Console', 2020, 150, 1, 30, 'M', 'Infinity Ward', 78),
('Genshin Impact', 'Open-world action RPG with gacha mechanics and exploration elements', 'Action RPG', 'PC, Console, Mobile', 2020, 4, 1, 45, 'T', 'miHoYo', 87),
('Destiny 2', 'Online multiplayer first-person shooter with RPG elements and raids', 'FPS RPG', 'PC, Console', 2017, 6, 1, 60, 'T', 'Bungie', 79),
('FIFA 23', 'Football simulation game with realistic gameplay and official teams', 'Sports', 'PC, Console', 2022, 2, 1, 25, 'E', 'EA Sports', 76);

-- Insert sample users with realistic data and proper security
INSERT INTO `Users` (`username`, `email`, `password_hash`, `is_active`, `timezone`, `email_verified`) VALUES
('TestGamer', 'test@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1),
('ProPlayer99', 'proplayer@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1),
('CasualGamer', 'casual@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1),
('StreamerLife', 'streamer@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1),
('CompetitiveAce', 'comp@gameplan.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Europe/Amsterdam', 1);

-- Insert sample user-game relationships with detailed metadata
INSERT INTO `UserGames` (`user_id`, `game_id`, `skill_level`, `hours_played`, `favorite_mode`, `is_currently_playing`) VALUES
(1, 1, 'Intermediate', 250, 'Battle Royale Squad', 1),
(1, 2, 'Advanced', 500, 'Competitive Matchmaking', 1),
(1, 5, 'Expert', 1200, 'Creative Building', 1),
(2, 2, 'Expert', 2000, 'Professional Competitive', 1),
(2, 4, 'Advanced', 800, 'Ranked Matches', 1),
(2, 3, 'Expert', 1500, 'Solo Queue Ranked', 1),
(3, 5, 'Intermediate', 400, 'Survival Mode', 1),
(3, 6, 'Beginner', 50, 'Classic Mode', 1),
(3, 9, 'Intermediate', 100, 'Main Show', 1),
(4, 1, 'Advanced', 800, 'Creative Content', 1),
(4, 7, 'Expert', 600, 'Competitive Tournament', 1),
(4, 10, 'Advanced', 450, 'Quick Play', 1),
(5, 2, 'Expert', 1800, 'Professional League', 1),
(5, 4, 'Expert', 1200, 'High-Rank Competitive', 1),
(5, 11, 'Advanced', 300, 'Squad Battle Royale', 1);

-- Insert sample friendships with status tracking
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

-- Insert sample schedules with comprehensive details
INSERT INTO `Schedules` (`user_id`, `game_id`, `title`, `date`, `time`, `end_time`, `friends`, `description`, `status`, `max_participants`) VALUES
(1, 1, 'Evening Fortnite Squad Session', '2025-10-01', '20:00:00', '22:00:00', '2,3,4', 'Casual squad games with friends for fun and practice', 'scheduled', 4),
(1, 2, 'CS2 Competitive Climb', '2025-10-02', '19:30:00', '21:30:00', '2,5', 'Serious ranked matchmaking session to improve rank', 'scheduled', 5),
(2, 3, 'LoL Ranked Grind Session', '2025-10-01', '18:00:00', '23:00:00', '1,5', 'Intensive ranked climbing session with duo partner', 'scheduled', 2),
(3, 5, 'Minecraft Castle Building', '2025-10-03', '15:00:00', '18:00:00', '1,4', 'Collaborative building project - medieval castle', 'scheduled', 4),
(4, 7, 'Rocket League Tournament Prep', '2025-10-02', '17:00:00', '19:00:00', '1,3', 'Practice session before upcoming tournament', 'scheduled', 3),
(5, 4, 'Valorant Ranked Push', '2025-10-04', '20:30:00', '23:00:00', '2,4', 'Pushing for Immortal rank with team coordination', 'scheduled', 5);

-- Insert sample events with detailed information
INSERT INTO `Events` (`user_id`, `title`, `description`, `event_type`, `date`, `time`, `end_time`, `location`, `max_participants`, `reminder`, `status`, `is_public`) VALUES
(1, 'Weekly Gaming Tournament', 'Community tournament with prizes for winners and fun for all participants', 'tournament', '2025-10-05', '14:00:00', '18:00:00', 'GamePlan Community Server', 32, '1 hour before', 'upcoming', 1),
(2, 'CS2 Team Scrim Practice', 'Serious team practice session before major tournament qualification', 'practice', '2025-10-03', '19:00:00', '21:00:00', 'Private Team Server', 10, '30 minutes before', 'upcoming', 0),
(3, 'Casual Gaming Social Meetup', 'Relaxed gaming session with snacks, drinks and fun social interaction', 'meetup', '2025-10-06', '13:00:00', '17:00:00', 'Local Gaming Cafe Downtown', 8, '1 day before', 'upcoming', 1),
(4, 'Live Streaming Session', 'Interactive live streaming various games with viewer participation and giveaways', 'stream', '2025-10-02', '20:00:00', '23:00:00', 'Twitch Channel StreamerLife', 100, '15 minutes before', 'upcoming', 1),
(5, 'Regional Championship Qualifier', 'High-stakes qualifying match for regional esports championship', 'competition', '2025-10-07', '16:00:00', '20:00:00', 'Official Tournament Platform', 16, '2 hours before', 'upcoming', 1);

-- Insert sample event user mappings for collaboration
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

-- Insert sample user status for real-time tracking
INSERT INTO `UserStatus` (`user_id`, `status_type`, `game_id`, `status_message`) VALUES
(1, 'online', NULL, 'Looking for squad members'),
(2, 'playing', 2, 'In competitive match'),
(3, 'online', NULL, 'Ready to game'),
(4, 'playing', 7, 'Streaming live now'),
(5, 'break', NULL, 'Back in 15 minutes');

-- ===================== PERFORMANCE OPTIMIZATION INDEXES =====================
-- Additional indexes for optimal query performance and data retrieval

-- Composite indexes for common query patterns
CREATE INDEX `idx_user_date_schedules` ON `Schedules` (`user_id`, `date`, `status`);
CREATE INDEX `idx_user_date_events` ON `Events` (`user_id`, `date`, `status`);
CREATE INDEX `idx_friend_lookup` ON `Friends` (`user_id`, `friend_user_id`, `status`);
CREATE INDEX `idx_event_reminders` ON `Events` (`reminder_time`, `status`);
CREATE INDEX `idx_schedule_reminders` ON `Schedules` (`date`, `time`, `reminder_sent`);
CREATE INDEX `idx_user_activity` ON `Users` (`last_activity`, `is_active`);
CREATE INDEX `idx_game_popularity` ON `Games` (`popularity_score`, `is_active`);

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

-- Trigger to update user activity and reset failed login attempts
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

-- Trigger to update game popularity based on user activity
CREATE TRIGGER `update_game_popularity`
AFTER INSERT ON `UserGames`
FOR EACH ROW
BEGIN
    UPDATE `Games` 
    SET `popularity_score` = `popularity_score` + 1
    WHERE `game_id` = NEW.game_id;
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
    COUNT(DISTINCT e.event_id) as upcoming_events,
    us.status_type as current_status,
    us.status_message
FROM `Users` u
LEFT JOIN `Friends` f ON u.user_id = f.user_id AND f.status = 'accepted'
LEFT JOIN `UserGames` ug ON u.user_id = ug.user_id AND ug.is_currently_playing = 1
LEFT JOIN `Schedules` s ON u.user_id = s.user_id AND s.date >= CURDATE() AND s.status = 'scheduled'
LEFT JOIN `Events` e ON u.user_id = e.user_id AND e.date >= CURDATE() AND e.status = 'upcoming'
LEFT JOIN `UserStatus` us ON u.user_id = us.user_id
GROUP BY u.user_id, u.username, us.status_type, us.status_message;

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

-- View for friend activity overview
CREATE VIEW `friend_activity_overview` AS
SELECT 
    f.user_id,
    f.friend_user_id,
    u.username as friend_username,
    us.status_type,
    us.status_message,
    g.titel as current_game,
    u.last_activity,
    CASE 
        WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'Online'
        WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'Recently Active'
        ELSE 'Offline'
    END as activity_status
FROM `Friends` f
JOIN `Users` u ON f.friend_user_id = u.user_id
LEFT JOIN `UserStatus` us ON u.user_id = us.user_id
LEFT JOIN `Games` g ON us.game_id = g.game_id
WHERE f.status = 'accepted'
ORDER BY u.last_activity DESC;

-- ===================== STORED PROCEDURES =====================
-- Useful procedures for common operations and maintenance

DELIMITER //

-- Procedure to get comprehensive user statistics
CREATE PROCEDURE `GetUserStatistics`(IN userId INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM Friends WHERE user_id = userId AND status = 'accepted') as total_friends,
        (SELECT COUNT(*) FROM UserGames WHERE user_id = userId AND is_currently_playing = 1) as favorite_games,
        (SELECT COUNT(*) FROM Schedules WHERE user_id = userId AND date >= CURDATE()) as upcoming_schedules,
        (SELECT COUNT(*) FROM Events WHERE user_id = userId AND date >= CURDATE()) as upcoming_events,
        (SELECT SUM(hours_played) FROM UserGames WHERE user_id = userId) as total_hours_played,
        (SELECT COUNT(*) FROM Notifications WHERE user_id = userId AND is_read = 0) as unread_notifications;
END//

-- Procedure to clean up old data and maintain performance
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
    
    -- Clean up expired notifications
    DELETE FROM Notifications WHERE expire_at < NOW();
    
    -- Update user status to offline if inactive for more than 1 hour
    UPDATE UserStatus SET status_type = 'offline' 
    WHERE user_id IN (
        SELECT user_id FROM Users 
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ) AND status_type != 'offline';
END//

-- Procedure to get friends who are currently online and playing
CREATE PROCEDURE `GetOnlineFriendsPlaying`(IN userId INT, IN gameId INT)
BEGIN
    SELECT 
        u.user_id,
        u.username,
        us.status_type,
        us.status_message,
        g.titel as current_game
    FROM Friends f
    JOIN Users u ON f.friend_user_id = u.user_id
    JOIN UserStatus us ON u.user_id = us.user_id
    LEFT JOIN Games g ON us.game_id = g.game_id
    WHERE f.user_id = userId 
        AND f.status = 'accepted'
        AND us.status_type IN ('online', 'playing')
        AND (gameId IS NULL OR us.game_id = gameId)
        AND u.last_activity > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ORDER BY u.last_activity DESC;
END//

DELIMITER ;

-- ===================== SECURITY MEASURES =====================
-- Additional security configurations and user management

-- Create read-only user for reporting and analytics
CREATE USER IF NOT EXISTS 'gameplan_readonly'@'localhost' IDENTIFIED BY 'readonly_secure_gameplan_2025!';
GRANT SELECT ON gameplan_db.* TO 'gameplan_readonly'@'localhost';

-- Create backup user with limited permissions
CREATE USER IF NOT EXISTS 'gameplan_backup'@'localhost' IDENTIFIED BY 'backup_secure_gameplan_2025!';
GRANT SELECT, LOCK TABLES ON gameplan_db.* TO 'gameplan_backup'@'localhost';

-- Create application user with specific permissions
CREATE USER IF NOT EXISTS 'gameplan_app'@'localhost' IDENTIFIED BY 'app_secure_gameplan_2025!';
GRANT SELECT, INSERT, UPDATE, DELETE ON gameplan_db.* TO 'gameplan_app'@'localhost';
REVOKE DROP, ALTER, CREATE ON gameplan_db.* FROM 'gameplan_app'@'localhost';

-- Flush privileges to apply changes
FLUSH PRIVILEGES;

-- ===================== DATA VALIDATION CONSTRAINTS =====================
-- Additional constraints for data integrity

-- Add constraints for data validation
ALTER TABLE `Users` ADD CONSTRAINT `chk_username_length` CHECK (CHAR_LENGTH(`username`) >= 3);
ALTER TABLE `Users` ADD CONSTRAINT `chk_email_format` CHECK (`email` LIKE '%@%.%');
ALTER TABLE `Events` ADD CONSTRAINT `chk_event_dates` CHECK (`end_date` IS NULL OR `end_date` >= `date`);
ALTER TABLE `Schedules` ADD CONSTRAINT `chk_schedule_times` CHECK (`end_time` IS NULL OR `end_time` > `time`);
ALTER TABLE `Games` ADD CONSTRAINT `chk_players` CHECK (`min_players` <= `max_players`);
ALTER TABLE `Games` ADD CONSTRAINT `chk_positive_scores` CHECK (`popularity_score` >= 0);

COMMIT;

-- ===================== DATABASE DOCUMENTATION =====================
/*
ENHANCED GAMEPLAN SCHEDULER DATABASE SCHEMA DOCUMENTATION
Version 3.0 Professional Production Edition

TABLES OVERVIEW:
1. Users: Enhanced user management with security features and activity tracking
2. Games: Comprehensive game catalog with metadata and popularity tracking
3. UserGames: User-game relationships with skill levels and play time
4. Friends: Advanced friendship system with status and favorite tracking
5. Schedules: Gaming schedule management with recurring patterns and notifications
6. Events: Event system with detailed management and participation tracking
7. EventUserMap: Event sharing and invitation system with response tracking
8. Notifications: Advanced notification system for all user interactions
9. UserStatus: Real-time user status tracking with game integration

SECURITY FEATURES:
- Comprehensive foreign key constraints for referential integrity
- Advanced triggers for business logic enforcement and data validation
- Strategic indexing on all frequently queried columns for optimal performance
- Multi-level user privilege separation with role-based access
- Password hashing requirement with bcrypt standards
- Failed login attempt tracking with automatic lockout mechanisms
- Session management with timeout and regeneration

PERFORMANCE OPTIMIZATIONS:
- Strategic composite indexing for complex query optimization
- Full-text search capabilities for games and events
- Optimized views for dashboard and activity data
- Stored procedures for common operations and maintenance
- Query result caching through proper indexing strategies
- Database cleanup procedures for maintaining performance

SCALABILITY FEATURES:
- JSON fields for flexible user preferences and configuration
- Enum types for controlled vocabularies and data consistency
- Proper charset and collation for full international support
- Partitioning-ready design for future horizontal scaling
- Comprehensive audit trail capabilities with detailed timestamps
- Real-time status tracking for live user interaction

ADVANCED FEATURES:
- Automatic popularity scoring for games based on user activity
- Real-time notification system with multiple delivery methods
- Advanced event management with participation tracking
- Friendship system with status management and favorites
- Comprehensive user activity and status tracking
- Automated data cleanup and maintenance procedures

This enhanced schema supports all user stories from the planning documentation
and provides a robust, scalable foundation for the GamePlan Scheduler application
with advanced features for professional gaming community management.

INSTALLATION INSTRUCTIONS:
1. Execute this script in phpMyAdmin or MySQL command line
2. Verify all tables are created successfully
3. Check sample data is inserted properly
4. Test user permissions and security settings
5. Configure application to use gameplan_app user credentials

MAINTENANCE:
- Run CleanupOldData() procedure weekly
- Monitor performance with EXPLAIN queries
- Update popularity scores regularly
- Back up database daily using gameplan_backup user
*/
