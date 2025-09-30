-- GamePlan Scheduler Database Schema - Professional Enhanced Version
-- Created by: Harsha Kanaparthi
-- Date: 30-09-2025  
-- Version: 2.0 - Complete professional implementation with all features
-- Security Level: Enterprise-grade with comprehensive validation

-- Drop and recreate database for clean installation
DROP DATABASE IF EXISTS gameplan_db;
CREATE DATABASE gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gameplan_db;

-- Users table for account management with enterprise-level security
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    profile_picture VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    failed_login_attempts INT DEFAULT 0,
    lockout_until TIMESTAMP NULL DEFAULT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(100) DEFAULT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expires TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT username_length CHECK (CHAR_LENGTH(TRIM(username)) >= 3 AND CHAR_LENGTH(TRIM(username)) <= 50),
    CONSTRAINT email_format CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),
    CONSTRAINT username_alphanumeric CHECK (username REGEXP '^[A-Za-z0-9_-]+$'),
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_last_activity (last_activity),
    INDEX idx_is_active (is_active)
);

-- Games table for comprehensive game information
CREATE TABLE Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'Other',
    release_year YEAR DEFAULT NULL,
    platform VARCHAR(100) DEFAULT NULL,
    genre VARCHAR(100) DEFAULT NULL,
    rating VARCHAR(10) DEFAULT NULL,
    publisher VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    popularity_score INT DEFAULT 0,
    CONSTRAINT titel_not_empty CHECK (CHAR_LENGTH(TRIM(titel)) >= 2),
    CONSTRAINT valid_year CHECK (release_year IS NULL OR release_year BETWEEN 1970 AND YEAR(CURDATE()) + 5),
    INDEX idx_titel (titel),
    INDEX idx_category (category),
    INDEX idx_genre (genre),
    INDEX idx_popularity (popularity_score)
);

-- UserGames junction table for user favorite games (Many-to-Many)
CREATE TABLE UserGames (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    play_time_hours INT DEFAULT 0,
    skill_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Beginner',
    favorite TINYINT(1) DEFAULT 0,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    CONSTRAINT valid_playtime CHECK (play_time_hours >= 0),
    INDEX idx_user_games (user_id, game_id),
    INDEX idx_favorite (favorite)
);

-- Friends table for comprehensive friend relationship management
CREATE TABLE Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL DEFAULT NULL,
    blocked_at TIMESTAMP NULL DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_user_id),
    CONSTRAINT no_self_friend CHECK (user_id != friend_user_id),
    INDEX idx_user_friends (user_id),
    INDEX idx_friend_user (friend_user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Schedules table for comprehensive gaming schedule management
CREATE TABLE Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_recurring TINYINT(1) DEFAULT 0,
    recurrence_pattern VARCHAR(50) DEFAULT NULL,
    max_participants INT DEFAULT NULL,
    visibility ENUM('public', 'friends', 'private') DEFAULT 'friends',
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    CONSTRAINT valid_schedule_date CHECK (date >= CURDATE() OR date = CURDATE()),
    CONSTRAINT valid_time CHECK (time >= '00:00:00' AND time <= '23:59:59'),
    CONSTRAINT valid_participants CHECK (max_participants IS NULL OR max_participants > 0),
    INDEX idx_user_schedules (user_id),
    INDEX idx_schedule_date (date),
    INDEX idx_schedule_game (game_id),
    INDEX idx_schedule_datetime (date, time),
    INDEX idx_schedule_status (status)
);

-- Events table for comprehensive gaming event management
CREATE TABLE Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT DEFAULT NULL,
    reminder VARCHAR(50) DEFAULT NULL,
    schedule_id INT NULL,
    location VARCHAR(100) DEFAULT NULL,
    max_participants INT DEFAULT NULL,
    is_public TINYINT(1) DEFAULT 0,
    registration_required TINYINT(1) DEFAULT 0,
    entry_fee DECIMAL(10,2) DEFAULT 0.00,
    prize_pool DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL,
    CONSTRAINT title_not_empty CHECK (CHAR_LENGTH(TRIM(title)) >= 3),
    CONSTRAINT title_max_length CHECK (CHAR_LENGTH(title) <= 100),
    CONSTRAINT valid_event_date CHECK (date >= CURDATE() OR date = CURDATE()),
    CONSTRAINT valid_event_time CHECK (time >= '00:00:00' AND time <= '23:59:59'),
    CONSTRAINT valid_reminder CHECK (reminder IS NULL OR reminder IN ('15_minutes', '30_minutes', '1_hour', '2_hours', '1_day', '1_week')),
    CONSTRAINT valid_max_participants CHECK (max_participants IS NULL OR max_participants > 0),
    CONSTRAINT valid_entry_fee CHECK (entry_fee >= 0),
    CONSTRAINT valid_prize_pool CHECK (prize_pool >= 0),
    INDEX idx_user_events (user_id),
    INDEX idx_event_date (date),
    INDEX idx_event_schedule (schedule_id),
    INDEX idx_event_datetime (date, time),
    INDEX idx_event_status (status),
    INDEX idx_event_public (is_public)
);

-- EventUserMap table for comprehensive event sharing and participation
CREATE TABLE EventUserMap (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('invited', 'accepted', 'declined', 'maybe') DEFAULT 'invited',
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    response_notes TEXT DEFAULT NULL,
    reminder_sent TINYINT(1) DEFAULT 0,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_event_friends (event_id),
    INDEX idx_friend_events (friend_id),
    INDEX idx_participation_status (status),
    INDEX idx_invited_at (invited_at)
);

-- Notifications table for system notifications
CREATE TABLE Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('friend_request', 'event_invite', 'schedule_reminder', 'event_reminder', 'system') DEFAULT 'system',
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    related_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id),
    INDEX idx_notification_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Insert comprehensive and realistic game data with full details
INSERT INTO Games (titel, description, category, release_year, platform, genre, rating, publisher, popularity_score) VALUES
('Fortnite', 'Epic battle royale game featuring building mechanics, creative modes, and constant content updates with seasonal themes', 'Battle Royale', 2017, 'PC, PlayStation, Xbox, Nintendo Switch, Mobile', 'Action, Shooter', 'T', 'Epic Games', 95),
('Call of Duty: Modern Warfare III', 'Latest installment in the legendary FPS franchise with cutting-edge graphics and intense multiplayer combat', 'FPS', 2023, 'PC, PlayStation, Xbox', 'First-Person Shooter', 'M', 'Activision', 92),
('League of Legends', 'The world\'s most popular MOBA featuring strategic 5v5 gameplay with over 160 unique champions', 'MOBA', 2009, 'PC, Mac', 'Strategy, MOBA', 'T', 'Riot Games', 98),
('Minecraft', 'The ultimate sandbox experience where creativity knows no bounds - build, explore, and survive in infinite worlds', 'Sandbox', 2011, 'PC, Console, Mobile', 'Sandbox, Survival', 'E10+', 'Mojang Studios', 97),
('Valorant', 'Tactical 5v5 character-based shooter combining precise gunplay with unique agent abilities', 'FPS', 2020, 'PC', 'Tactical Shooter', 'T', 'Riot Games', 89),
('Among Us', 'Social deduction party game where teamwork and betrayal collide in space-themed mystery gameplay', 'Social', 2018, 'PC, Mobile, Console', 'Party, Social Deduction', 'E10+', 'InnerSloth', 85),
('Rocket League', 'High-octane hybrid of arcade-style soccer and vehicular mayhem with competitive esports scene', 'Sports', 2015, 'PC, PlayStation, Xbox, Nintendo Switch', 'Sports, Racing', 'E', 'Psyonix', 88),
('Apex Legends', 'Fast-paced battle royale with unique character abilities, team-based strategy, and evolving map dynamics', 'Battle Royale', 2019, 'PC, PlayStation, Xbox, Nintendo Switch', 'Battle Royale, Shooter', 'T', 'EA', 91),
('FIFA 24', 'The world\'s most authentic football experience with HyperMotionV technology and ultimate team modes', 'Sports', 2023, 'PC, PlayStation, Xbox, Nintendo Switch', 'Sports Simulation', 'E', 'EA Sports', 86),
('Overwatch 2', 'Team-based multiplayer shooter featuring diverse heroes with unique abilities in fast-paced 5v5 combat', 'FPS', 2022, 'PC, PlayStation, Xbox, Nintendo Switch', 'Hero Shooter', 'T', 'Blizzard Entertainment', 83),
('Counter-Strike 2', 'The legendary tactical shooter rebuilt on Source 2 engine with enhanced graphics and refined gameplay', 'FPS', 2023, 'PC', 'Tactical Shooter', 'M', 'Valve', 94),
('Genshin Impact', 'Open-world action RPG with anime-style graphics, elemental magic system, and gacha collection mechanics', 'RPG', 2020, 'PC, PlayStation, Mobile', 'Action RPG, Open World', 'T', 'miHoYo', 90),
('Destiny 2', 'Sci-fi looter shooter with cooperative PvE raids, competitive PvP, and an ever-evolving storyline', 'FPS', 2017, 'PC, PlayStation, Xbox', 'Looter Shooter, MMO', 'T', 'Bungie', 87),
('World of Warcraft', 'The definitive MMORPG experience with epic quests, dungeon raids, and a vast fantasy universe', 'MMORPG', 2004, 'PC, Mac', 'MMORPG, Fantasy', 'T', 'Blizzard Entertainment', 93),
('Grand Theft Auto V', 'Open-world crime saga with three playable protagonists and massive online multiplayer world', 'Action', 2013, 'PC, PlayStation, Xbox', 'Action, Open World', 'M', 'Rockstar Games', 96),
('Dota 2', 'Complex and competitive MOBA with deep strategy, professional esports scene, and regular updates', 'MOBA', 2013, 'PC, Mac, Linux', 'Strategy, MOBA', 'T', 'Valve', 91),
('Fall Guys', 'Colorful battle royale party game featuring whimsical obstacle courses and bean-shaped characters', 'Party', 2020, 'PC, PlayStation, Xbox, Nintendo Switch, Mobile', 'Party, Battle Royale', 'E', 'Mediatonic', 78),
('Warframe', 'Free-to-play online action game featuring ninja-inspired Warframes with unique abilities and weapons', 'Action', 2013, 'PC, PlayStation, Xbox, Nintendo Switch', 'Third-Person Shooter, MMO', 'M', 'Digital Extremes', 84),
('Halo Infinite', 'Master Chief returns in this epic sci-fi shooter with multiplayer combat and campaign adventure', 'FPS', 2021, 'PC, Xbox', 'Sci-Fi Shooter', 'T', 'Microsoft', 82),
('Roblox', 'Gaming platform where millions of users create and play games together in user-generated worlds', 'Platform', 2006, 'PC, Mobile, Xbox, PlayStation', 'Sandbox, Social', 'E10+', 'Roblox Corporation', 89);

-- Insert professional test users with secure password hashing (password: GamePlan2024!)
INSERT INTO Users (username, email, password_hash, email_verified) VALUES
('admin_user', 'admin@gameplan.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/vDVGGXqH5GWD7JzOy', 1),
('test_gamer', 'test@gameplan.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/vDVGGXqH5GWD7JzOy', 1),
('demo_player', 'demo@gameplan.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/vDVGGXqH5GWD7JzOy', 1),
('pro_gamer', 'pro@gameplan.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/vDVGGXqH5GWD7JzOy', 1),
('casual_player', 'casual@gameplan.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/vDVGGXqH5GWD7JzOy', 1);

-- Insert comprehensive user-game relationships with detailed preferences
INSERT INTO UserGames (user_id, game_id, play_time_hours, skill_level, favorite) VALUES
(1, 1, 250, 'Advanced', 1), (1, 3, 180, 'Expert', 1), (1, 5, 120, 'Advanced', 0), (1, 11, 95, 'Intermediate', 0),
(2, 2, 320, 'Expert', 1), (2, 11, 150, 'Advanced', 1), (2, 13, 200, 'Advanced', 0), (2, 15, 85, 'Intermediate', 0),
(3, 4, 500, 'Expert', 1), (3, 7, 180, 'Advanced', 1), (3, 12, 95, 'Intermediate', 0), (3, 17, 120, 'Advanced', 0),
(4, 1, 420, 'Expert', 1), (4, 8, 280, 'Advanced', 1), (4, 14, 350, 'Expert', 1), (4, 16, 45, 'Beginner', 0),
(5, 6, 75, 'Intermediate', 1), (5, 9, 120, 'Intermediate', 0), (5, 18, 200, 'Advanced', 1), (5, 20, 380, 'Expert', 1);

-- Insert comprehensive friendship network
INSERT INTO Friends (user_id, friend_user_id, status, accepted_at) VALUES
(1, 2, 'accepted', NOW() - INTERVAL 30 DAY), (1, 3, 'accepted', NOW() - INTERVAL 25 DAY), (1, 4, 'accepted', NOW() - INTERVAL 20 DAY),
(2, 1, 'accepted', NOW() - INTERVAL 30 DAY), (2, 3, 'accepted', NOW() - INTERVAL 15 DAY), (2, 5, 'accepted', NOW() - INTERVAL 10 DAY),
(3, 1, 'accepted', NOW() - INTERVAL 25 DAY), (3, 2, 'accepted', NOW() - INTERVAL 15 DAY), (3, 4, 'accepted', NOW() - INTERVAL 12 DAY),
(4, 1, 'accepted', NOW() - INTERVAL 20 DAY), (4, 3, 'accepted', NOW() - INTERVAL 12 DAY), (4, 5, 'accepted', NOW() - INTERVAL 8 DAY),
(5, 2, 'accepted', NOW() - INTERVAL 10 DAY), (5, 4, 'accepted', NOW() - INTERVAL 8 DAY);

-- Insert realistic upcoming schedules with detailed information
INSERT INTO Schedules (user_id, game_id, date, time, friends, description, max_participants, visibility) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00:00', '2,3,4', 'Weekly Fortnite Squad Practice - Working on late game strategies and building techniques', 4, 'friends'),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '20:30:00', '1,4', 'Call of Duty Ranked Grind - Push for Diamond rank before season end', 3, 'friends'),
(1, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '18:00:00', '2', 'League of Legends Duo Queue - Climbing ranked ladder with coordinated bot lane', 2, 'friends'),
(3, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '16:30:00', '1,5', 'Minecraft Creative Building - Working on massive castle project together', 6, 'public'),
(4, 8, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '21:00:00', '1,2,3', 'Apex Legends Tournament Prep - Practice for upcoming weekend tournament', 3, 'friends'),
(5, 20, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '15:00:00', '2,4', 'Roblox Game Development - Collaborating on new multiplayer experience', 4, 'friends'),
(2, 11, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '19:30:00', '1,3,5', 'Counter-Strike 2 Competitive - Team practice for local tournament', 5, 'friends');

-- Insert exciting upcoming events with comprehensive details
INSERT INTO Events (user_id, title, date, time, description, reminder, location, max_participants, is_public, entry_fee, prize_pool) VALUES
(1, 'GamePlan Community Tournament', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '14:00:00', 'Massive multi-game tournament featuring Fortnite, Valorant, and Rocket League with live streaming and commentary', '1_day', 'Online - Discord Server', 64, 1, 0.00, 500.00),
(2, 'Local Gaming Meetup & LAN Party', DATE_ADD(CURDATE(), INTERVAL 10 DAY), '18:00:00', 'In-person gaming event at community center with tournaments, free pizza, and networking opportunities', '2_hours', 'Downtown Community Center', 30, 1, 10.00, 200.00),
(3, 'Minecraft Building Contest', DATE_ADD(CURDATE(), INTERVAL 12 DAY), '13:00:00', 'Creative building competition with themes announced live - showcase your architectural skills', '1_day', 'Minecraft Server', 20, 1, 0.00, 100.00),
(1, 'Pro League Viewing Party', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '19:00:00', 'Watch the championship finals together with live commentary, predictions, and giveaways', '1_hour', 'GamePlan Lounge', 50, 1, 0.00, 0.00),
(4, 'Speedrun Challenge Night', DATE_ADD(CURDATE(), INTERVAL 16 DAY), '20:00:00', 'Various game speedrunning challenges with live timing and community voting for categories', '30_minutes', 'Twitch Stream', 15, 1, 5.00, 75.00),
(5, 'Casual Game Night', DATE_ADD(CURDATE(), INTERVAL 18 DAY), '17:30:00', 'Relaxed gaming session featuring party games, co-op adventures, and friendly competitions', '1_hour', 'Discord Voice Chat', 12, 0, 0.00, 0.00);

-- Insert comprehensive event participation data
INSERT INTO EventUserMap (event_id, friend_id, status, responded_at) VALUES
(1, 2, 'accepted', NOW() - INTERVAL 1 DAY), (1, 3, 'accepted', NOW() - INTERVAL 2 DAY), (1, 4, 'maybe', NULL), (1, 5, 'invited', NULL),
(2, 1, 'accepted', NOW() - INTERVAL 3 HOUR), (2, 3, 'accepted', NOW() - INTERVAL 1 DAY), (2, 4, 'declined', NOW() - INTERVAL 2 HOUR), (2, 5, 'accepted', NOW() - INTERVAL 6 HOUR),
(3, 1, 'accepted', NOW() - INTERVAL 4 HOUR), (3, 2, 'maybe', NULL), (3, 4, 'accepted', NOW() - INTERVAL 8 HOUR), (3, 5, 'accepted', NOW() - INTERVAL 12 HOUR),
(4, 2, 'accepted', NOW() - INTERVAL 2 DAY), (4, 3, 'accepted', NOW() - INTERVAL 1 DAY), (4, 4, 'accepted', NOW() - INTERVAL 18 HOUR), (4, 5, 'invited', NULL),
(5, 1, 'maybe', NULL), (5, 2, 'accepted', NOW() - INTERVAL 5 HOUR), (5, 3, 'accepted', NOW() - INTERVAL 7 HOUR), (5, 5, 'invited', NULL);

-- Insert sample notifications for realistic user experience
INSERT INTO Notifications (user_id, type, title, message, related_id) VALUES
(1, 'event_invite', 'Event Invitation', 'You have been invited to Local Gaming Meetup & LAN Party', 2),
(2, 'friend_request', 'New Friend Request', 'casual_player sent you a friend request', 5),
(3, 'schedule_reminder', 'Schedule Reminder', 'Your Minecraft Creative Building session starts in 1 hour', 4),
(4, 'event_reminder', 'Event Starting Soon', 'GamePlan Community Tournament begins in 30 minutes', 1),
(5, 'system', 'Welcome to GamePlan!', 'Thanks for joining our gaming community. Start by adding your favorite games!', NULL);

-- Create comprehensive indexes for optimal performance
CREATE INDEX idx_users_activity_status ON Users(last_activity DESC, is_active);
CREATE INDEX idx_games_popularity ON Games(popularity_score DESC, is_active);
CREATE INDEX idx_schedules_upcoming ON Schedules(date ASC, time ASC, status);
CREATE INDEX idx_events_upcoming ON Events(date ASC, time ASC, status);
CREATE INDEX idx_notifications_unread ON Notifications(user_id, is_read, created_at DESC);
CREATE INDEX idx_usergames_favorites ON UserGames(user_id, favorite DESC);
CREATE INDEX idx_friends_active ON Friends(user_id, status, created_at DESC);

-- Create professional database views for common operations
CREATE VIEW v_user_dashboard AS
SELECT 
    u.user_id,
    u.username,
    u.email,
    u.last_activity,
    COUNT(DISTINCT f.friend_id) as friend_count,
    COUNT(DISTINCT ug.game_id) as favorite_games_count,
    COUNT(DISTINCT s.schedule_id) as upcoming_schedules,
    COUNT(DISTINCT e.event_id) as upcoming_events,
    COUNT(DISTINCT n.notification_id) as unread_notifications
FROM Users u
LEFT JOIN Friends f ON u.user_id = f.user_id AND f.status = 'accepted'
LEFT JOIN UserGames ug ON u.user_id = ug.user_id
LEFT JOIN Schedules s ON u.user_id = s.user_id AND s.date >= CURDATE() AND s.status = 'active'
LEFT JOIN Events e ON u.user_id = e.user_id AND e.date >= CURDATE() AND e.status = 'planned'
LEFT JOIN Notifications n ON u.user_id = n.user_id AND n.is_read = 0
WHERE u.is_active = 1
GROUP BY u.user_id, u.username, u.email, u.last_activity;

CREATE VIEW v_upcoming_activities AS
SELECT 
    'schedule' as activity_type,
    s.schedule_id as activity_id,
    s.user_id,
    u.username,
    CONCAT('Gaming Session: ', g.titel) as title,
    s.date,
    s.time,
    s.description,
    g.titel as game_title,
    s.max_participants,
    NULL as prize_pool
FROM Schedules s
JOIN Users u ON s.user_id = u.user_id
JOIN Games g ON s.game_id = g.game_id
WHERE s.date >= CURDATE() AND s.status = 'active'

UNION ALL

SELECT 
    'event' as activity_type,
    e.event_id as activity_id,
    e.user_id,
    u.username,
    e.title,
    e.date,
    e.time,
    e.description,
    NULL as game_title,
    e.max_participants,
    e.prize_pool
FROM Events e
JOIN Users u ON e.user_id = u.user_id
WHERE e.date >= CURDATE() AND e.status = 'planned'
ORDER BY date ASC, time ASC;

CREATE VIEW v_user_social_activity AS
SELECT 
    u.user_id,
    u.username,
    COUNT(DISTINCT f.friend_id) as total_friends,
    COUNT(DISTINCT CASE WHEN f.status = 'accepted' THEN f.friend_id END) as active_friends,
    COUNT(DISTINCT CASE WHEN f.status = 'pending' THEN f.friend_id END) as pending_requests,
    COUNT(DISTINCT eum.event_id) as events_invited_to,
    COUNT(DISTINCT CASE WHEN eum.status = 'accepted' THEN eum.event_id END) as events_accepted,
    AVG(g.popularity_score) as avg_game_popularity
FROM Users u
LEFT JOIN Friends f ON u.user_id = f.user_id
LEFT JOIN EventUserMap eum ON u.user_id = eum.friend_id
LEFT JOIN UserGames ug ON u.user_id = ug.user_id
LEFT JOIN Games g ON ug.game_id = g.game_id
WHERE u.is_active = 1
GROUP BY u.user_id, u.username;

-- Add database triggers for automatic maintenance
DELIMITER //

CREATE TRIGGER update_user_activity 
AFTER INSERT ON Schedules
FOR EACH ROW
BEGIN
    UPDATE Users SET last_activity = NOW() WHERE user_id = NEW.user_id;
END//

CREATE TRIGGER update_user_activity_events
AFTER INSERT ON Events
FOR EACH ROW
BEGIN
    UPDATE Users SET last_activity = NOW() WHERE user_id = NEW.user_id;
END//

CREATE TRIGGER auto_accept_mutual_friends
AFTER INSERT ON Friends
FOR EACH ROW
BEGIN
    IF NEW.status = 'pending' THEN
        UPDATE Friends 
        SET status = 'accepted', accepted_at = NOW()
        WHERE user_id = NEW.friend_user_id 
        AND friend_user_id = NEW.user_id 
        AND status = 'pending';
        
        IF ROW_COUNT() > 0 THEN
            UPDATE Friends 
            SET status = 'accepted', accepted_at = NOW()
            WHERE friend_id = NEW.friend_id;
        END IF;
    END IF;
END//

DELIMITER ;

-- Final validation and optimization
ANALYZE TABLE Users, Games, UserGames, Friends, Schedules, Events, EventUserMap, Notifications;

-- Display successful completion message
SELECT 
    'GamePlan Scheduler Database v2.0 installed successfully!' as status,
    COUNT(*) as total_users FROM Users
UNION ALL
SELECT 
    'Total games available:', 
    COUNT(*) FROM Games
UNION ALL
SELECT 
    'Total schedules created:', 
    COUNT(*) FROM Schedules
UNION ALL
SELECT 
    'Total events planned:', 
    COUNT(*) FROM Events;
    e.user_id,
    u.username,
    e.title,
    e.date,
    e.time,
    e.description,
    e.reminder,
    e.schedule_id,
    s.game_id,
    g.titel as game_title,
    e.created_at
FROM Events e
JOIN Users u ON e.user_id = u.user_id
LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
LEFT JOIN Games g ON s.game_id = g.game_id
WHERE e.date >= CURDATE()
ORDER BY e.date ASC, e.time ASC;

-- Ensure proper character set for all tables
ALTER DATABASE gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Add success message
SELECT 'Database gameplan_db created successfully with enhanced schema and sample data!' as Status;
