/**
 * GamePlan Scheduler - Advanced Professional Database Schema
 * Professional Gaming Schedule Management Platform
 * 
 * This comprehensive database schema provides enterprise-level data architecture
 * for advanced gaming schedule management with security, scalability, and 
 * professional performance optimization.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 * @database gameplan_db
 */

-- Drop database if exists for clean installation
DROP DATABASE IF EXISTS gameplan_db;

-- Create database with advanced character set and collation
CREATE DATABASE gameplan_db 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE gameplan_db;

-- Advanced Users table with comprehensive security and gaming features
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique gaming username',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'User email address',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password',
    first_name VARCHAR(50) COMMENT 'User first name',
    last_name VARCHAR(50) COMMENT 'User last name',
    
    -- Gaming Profile Information
    profile_picture VARCHAR(255) DEFAULT 'default-avatar.png' COMMENT 'Profile picture filename',
    bio TEXT COMMENT 'User biography for gaming profile',
    gaming_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Professional', 'Esports') DEFAULT 'Beginner',
    preferred_platforms SET('PC', 'PlayStation', 'Xbox', 'Nintendo', 'Mobile', 'VR') DEFAULT 'PC',
    favorite_genres SET('Action', 'Adventure', 'RPG', 'Strategy', 'Sports', 'Racing', 'Puzzle', 'Horror', 'Simulation') DEFAULT 'Action',
    
    -- Location and Time Management
    timezone VARCHAR(50) DEFAULT 'Europe/Amsterdam' COMMENT 'User timezone',
    country VARCHAR(2) COMMENT 'ISO country code',
    preferred_language VARCHAR(5) DEFAULT 'nl_NL' COMMENT 'Language preference',
    
    -- Advanced Notification System
    notification_preferences JSON COMMENT 'User notification settings',
    push_token VARCHAR(255) COMMENT 'Push notification token',
    email_notifications BOOLEAN DEFAULT TRUE,
    push_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,
    
    -- Security and Account Management
    account_status ENUM('active', 'suspended', 'pending', 'banned', 'inactive') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) COMMENT 'Email verification token',
    phone_number VARCHAR(20) COMMENT 'Phone number for SMS notifications',
    phone_verified BOOLEAN DEFAULT FALSE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255) COMMENT '2FA secret key',
    recovery_codes JSON COMMENT '2FA recovery codes',
    
    -- Professional Gaming Statistics
    total_games_played INT DEFAULT 0,
    total_events_attended INT DEFAULT 0,
    total_tournaments_won INT DEFAULT 0,
    gaming_hours_logged INT DEFAULT 0,
    achievement_points INT DEFAULT 0,
    reputation_score DECIMAL(3,1) DEFAULT 5.0,
    
    -- Privacy and GDPR Compliance
    privacy_settings JSON COMMENT 'User privacy preferences',
    data_processing_consent BOOLEAN DEFAULT FALSE,
    marketing_consent BOOLEAN DEFAULT FALSE,
    third_party_sharing BOOLEAN DEFAULT FALSE,
    
    -- Advanced Timestamps and Tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    last_password_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    password_reset_token VARCHAR(255) COMMENT 'Password reset token',
    password_reset_expires TIMESTAMP NULL,
    
    -- Account Deactivation Support
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    deletion_reason TEXT COMMENT 'Reason for account deletion',
    
    -- Advanced Indexing for Performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_last_activity (last_activity),
    INDEX idx_account_status (account_status),
    INDEX idx_gaming_level (gaming_level),
    INDEX idx_country (country),
    INDEX idx_created_at (created_at),
    INDEX idx_email_verified (email_verified),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_reputation (reputation_score),
    
    -- Composite indexes for complex queries
    INDEX idx_active_users (account_status, email_verified, deleted_at),
    INDEX idx_gaming_profile (gaming_level, preferred_platforms(100)),
    INDEX idx_notification_prefs (email_notifications, push_notifications),
    
    -- Full-text search index
    FULLTEXT KEY idx_search (username, first_name, last_name, bio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Advanced user accounts with gaming profiles';

-- Advanced Games table with comprehensive gaming information
CREATE TABLE Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL COMMENT 'Game title in Dutch',
    title_english VARCHAR(100) COMMENT 'English game title for international support',
    description TEXT COMMENT 'Detailed game description',
    short_description VARCHAR(255) COMMENT 'Brief game summary for cards',
    genre ENUM('Action', 'Adventure', 'RPG', 'Strategy', 'Sports', 'Racing', 'Puzzle', 'Horror', 'Simulation', 'Battle Royale', 'FPS', 'MOBA', 'Party', 'Sandbox') NOT NULL,
    platform SET('PC', 'PlayStation', 'PlayStation 5', 'Xbox', 'Xbox Series X', 'Nintendo Switch', 'Mobile', 'VR', 'Steam', 'Epic Games') DEFAULT 'PC',
    rating DECIMAL(3,1) DEFAULT 0.0 COMMENT 'User rating out of 5.0',
    metacritic_score INT COMMENT 'Metacritic score 0-100',
    age_rating ENUM('E', 'E10+', '13+', '17+', 'M', 'AO', 'RP') DEFAULT 'E',
    release_date DATE COMMENT 'Game release date',
    developer VARCHAR(100) COMMENT 'Game developer company',
    publisher VARCHAR(100) COMMENT 'Game publisher company',
    
    -- Gaming Specific Details
    max_players INT DEFAULT 1 COMMENT 'Maximum number of players',
    min_players INT DEFAULT 1 COMMENT 'Minimum number of players',
    average_playtime INT COMMENT 'Average playtime in minutes',
    game_modes SET('Single Player', 'Multiplayer', 'Co-op', 'Online', 'Local', 'Cross-platform') DEFAULT 'Single Player',
    difficulty_level ENUM('Easy', 'Normal', 'Hard', 'Expert', 'Variable') DEFAULT 'Normal',
    
    -- Media and Marketing
    image_url VARCHAR(255) DEFAULT 'default-game.jpg' COMMENT 'Game cover image',
    banner_url VARCHAR(255) COMMENT 'Wide banner image for headers',
    trailer_url VARCHAR(500) COMMENT 'Game trailer video URL',
    website_url VARCHAR(500) COMMENT 'Official game website',
    steam_id VARCHAR(50) COMMENT 'Steam store ID',
    
    -- Popularity and Trending
    popularity_score DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Calculated popularity based on user activity',
    trending_score DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Recent popularity trend',
    total_players INT DEFAULT 0 COMMENT 'Total registered players in our system',
    active_players INT DEFAULT 0 COMMENT 'Currently active players',
    
    -- Content Management
    featured BOOLEAN DEFAULT FALSE COMMENT 'Featured game on homepage',
    status ENUM('active', 'inactive', 'deprecated', 'upcoming', 'beta') DEFAULT 'active',
    tags JSON COMMENT 'Custom tags for filtering and search',
    system_requirements JSON COMMENT 'PC system requirements',
    
    -- Advanced Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_played TIMESTAMP NULL COMMENT 'When this game was last played by any user',
    
    -- Performance Indexes
    INDEX idx_genre (genre),
    INDEX idx_platform (platform(100)),
    INDEX idx_rating (rating),
    INDEX idx_release_date (release_date),
    INDEX idx_popularity (popularity_score),
    INDEX idx_trending (trending_score),
    INDEX idx_max_players (max_players),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_age_rating (age_rating),
    
    -- Composite indexes for complex queries
    INDEX idx_active_popular (status, popularity_score),
    INDEX idx_genre_platform (genre, platform(50)),
    INDEX idx_multiplayer_rating (max_players, rating),
    
    -- Full-text search for game discovery
    FULLTEXT KEY idx_game_search (titel, title_english, description, developer, publisher)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprehensive gaming library with advanced features';

-- Advanced UserGames relationship table
CREATE TABLE UserGames (
    user_game_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    
    -- Gaming Statistics
    hours_played DECIMAL(8,2) DEFAULT 0.00 COMMENT 'Total hours played',
    last_played TIMESTAMP NULL COMMENT 'When user last played this game',
    first_played TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When user first added this game',
    play_count INT DEFAULT 0 COMMENT 'Number of gaming sessions',
    
    -- User Ratings and Reviews
    user_rating DECIMAL(3,1) DEFAULT NULL COMMENT 'User personal rating 1-5',
    review_text TEXT COMMENT 'User review of the game',
    review_date TIMESTAMP NULL COMMENT 'When review was written',
    review_helpful_votes INT DEFAULT 0 COMMENT 'How many found review helpful',
    
    -- Gaming Preferences
    skill_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert', 'Professional') DEFAULT 'Beginner',
    favorite BOOLEAN DEFAULT FALSE COMMENT 'Is this a favorite game',
    wishlist BOOLEAN DEFAULT FALSE COMMENT 'Game on wishlist',
    completed BOOLEAN DEFAULT FALSE COMMENT 'Game completed',
    achievement_progress JSON COMMENT 'Achievement tracking data',
    
    -- Social Features
    willing_to_teach BOOLEAN DEFAULT FALSE COMMENT 'Willing to help beginners',
    looking_for_team BOOLEAN DEFAULT FALSE COMMENT 'Looking for team members',
    preferred_role VARCHAR(50) COMMENT 'Preferred role in team games',
    play_style ENUM('Casual', 'Competitive', 'Hardcore', 'Social', 'Solo') DEFAULT 'Casual',
    
    -- Privacy Settings
    show_stats BOOLEAN DEFAULT TRUE COMMENT 'Show stats to other users',
    show_playtime BOOLEAN DEFAULT TRUE COMMENT 'Show playtime to friends',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints and Indexes
    UNIQUE KEY unique_user_game (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    
    INDEX idx_user_favorite (user_id, favorite),
    INDEX idx_user_playtime (user_id, hours_played),
    INDEX idx_game_rating (game_id, user_rating),
    INDEX idx_last_played (last_played),
    INDEX idx_skill_level (skill_level),
    INDEX idx_looking_team (looking_for_team),
    INDEX idx_play_style (play_style)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Advanced user-game relationships with statistics';

-- Advanced Friends table with professional relationship management
CREATE TABLE Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    
    -- Friendship Status and Management
    status ENUM('pending', 'accepted', 'blocked', 'declined', 'removed') DEFAULT 'pending' COMMENT 'Friendship status',
    initiated_by INT NOT NULL COMMENT 'User who initiated the friend request',
    
    -- Social Gaming Features
    favorite_friend BOOLEAN DEFAULT FALSE COMMENT 'Mark as favorite friend for quick access',
    gaming_compatibility_score DECIMAL(3,1) DEFAULT NULL COMMENT 'Compatibility based on shared games',
    mutual_friends_count INT DEFAULT 0 COMMENT 'Number of mutual friends',
    
    -- Communication Preferences
    allow_invites BOOLEAN DEFAULT TRUE COMMENT 'Allow game invites from this friend',
    allow_notifications BOOLEAN DEFAULT TRUE COMMENT 'Allow notifications from this friend',
    nickname VARCHAR(50) COMMENT 'Custom nickname for this friend',
    
    -- Gaming Activity Tracking
    last_played_together TIMESTAMP NULL COMMENT 'Last time played a game together',
    total_sessions_together INT DEFAULT 0 COMMENT 'Total gaming sessions together',
    shared_games_count INT DEFAULT 0 COMMENT 'Number of games both users play',
    
    -- Privacy and Settings
    visibility ENUM('public', 'friends', 'private') DEFAULT 'friends' COMMENT 'Friend visibility setting',
    notes TEXT COMMENT 'Personal notes about this friend',
    
    -- Advanced Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When friendship was initiated',
    accepted_at TIMESTAMP NULL COMMENT 'When friendship was accepted',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_interaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints and Indexes
    UNIQUE KEY unique_friendship (user_id, friend_user_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (initiated_by) REFERENCES Users(user_id) ON DELETE CASCADE,
    
    -- Check constraint to prevent self-friendship
    CHECK (user_id != friend_user_id),
    
    -- Performance Indexes
    INDEX idx_user_status (user_id, status),
    INDEX idx_friend_status (friend_user_id, status),
    INDEX idx_status (status),
    INDEX idx_favorite (favorite_friend),
    INDEX idx_compatibility (gaming_compatibility_score),
    INDEX idx_last_played (last_played_together),
    INDEX idx_created_at (created_at),
    INDEX idx_initiated_by (initiated_by),
    
    -- Composite indexes for complex queries
    INDEX idx_user_active_friends (user_id, status, last_interaction),
    INDEX idx_gaming_friends (user_id, status, allow_invites)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Advanced friendship management with gaming features';

-- Advanced Schedules table with comprehensive gaming session planning
CREATE TABLE Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    
    -- Schedule Details
    title VARCHAR(150) COMMENT 'Custom title for this gaming session',
    description TEXT COMMENT 'Detailed description of the gaming session',
    date DATE NOT NULL COMMENT 'Date of the gaming session',
    time TIME NOT NULL COMMENT 'Start time of the gaming session',
    end_time TIME COMMENT 'Expected end time of the gaming session',
    duration_minutes INT COMMENT 'Expected duration in minutes',
    
    -- Gaming Session Configuration
    max_participants INT DEFAULT 1 COMMENT 'Maximum number of participants',
    current_participants INT DEFAULT 1 COMMENT 'Current number of confirmed participants',
    session_type ENUM('casual', 'competitive', 'tutorial', 'tournament', 'practice', 'clan_war') DEFAULT 'casual',
    skill_level_required ENUM('any', 'beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'any',
    voice_chat_required BOOLEAN DEFAULT FALSE COMMENT 'Voice chat required for session',
    
    -- Location and Platform
    platform_specific VARCHAR(100) COMMENT 'Specific platform for this session',
    server_region VARCHAR(50) COMMENT 'Preferred server region',
    custom_server VARCHAR(255) COMMENT 'Custom server address if applicable',
    
    -- Social Features
    friends TEXT COMMENT 'Comma-separated list of invited friend IDs (legacy support)',
    invited_friends JSON COMMENT 'JSON array of invited friend details',
    public_session BOOLEAN DEFAULT FALSE COMMENT 'Allow public discovery of this session',
    auto_accept_friends BOOLEAN DEFAULT TRUE COMMENT 'Auto-accept friend requests to join',
    
    -- Session Management
    status ENUM('planned', 'active', 'completed', 'cancelled', 'postponed') DEFAULT 'planned',
    recurring BOOLEAN DEFAULT FALSE COMMENT 'Is this a recurring session',
    recurring_pattern ENUM('daily', 'weekly', 'monthly', 'custom') COMMENT 'Recurrence pattern',
    recurring_data JSON COMMENT 'Detailed recurrence configuration',
    
    -- Notifications and Reminders
    reminder_settings JSON COMMENT 'Reminder configuration for participants',
    send_notifications BOOLEAN DEFAULT TRUE COMMENT 'Send notifications to participants',
    notification_sent BOOLEAN DEFAULT FALSE COMMENT 'Track if notifications were sent',
    
    -- Performance and Analytics
    popularity_score DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Session popularity based on joins',
    success_rating DECIMAL(3,1) COMMENT 'Rating given by participants after session',
    participant_feedback JSON COMMENT 'Feedback from session participants',
    
    -- Advanced Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_reminder_sent TIMESTAMP NULL COMMENT 'When last reminder was sent',
    session_started_at TIMESTAMP NULL COMMENT 'When session actually started',
    session_ended_at TIMESTAMP NULL COMMENT 'When session ended',
    
    -- Constraints and Indexes
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,
    
    -- Check constraints for data integrity
    CHECK (end_time IS NULL OR end_time > time),
    CHECK (max_participants > 0),
    CHECK (current_participants >= 0),
    CHECK (current_participants <= max_participants),
    
    -- Performance Indexes
    INDEX idx_user_date (user_id, date),
    INDEX idx_game_date (game_id, date),
    INDEX idx_date_time (date, time),
    INDEX idx_status (status),
    INDEX idx_session_type (session_type),
    INDEX idx_public_sessions (public_session, status, date),
    INDEX idx_skill_level (skill_level_required),
    INDEX idx_platform (platform_specific),
    INDEX idx_recurring (recurring),
    INDEX idx_popularity (popularity_score),
    
    -- Composite indexes for complex queries
    INDEX idx_upcoming_sessions (status, date, time),
    INDEX idx_user_active_sessions (user_id, status, date),
    INDEX idx_game_popular_sessions (game_id, popularity_score, status),
    INDEX idx_public_skill_sessions (public_session, skill_level_required, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Advanced gaming session scheduling with social features';

-- Advanced Events table with comprehensive tournament and meetup management
CREATE TABLE Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    schedule_id INT NULL COMMENT 'Optional link to gaming schedule',
    
    -- Event Details
    title VARCHAR(100) NOT NULL COMMENT 'Event title',
    description TEXT COMMENT 'Detailed event description',
    short_description VARCHAR(255) COMMENT 'Brief event summary for cards',
    date DATE NOT NULL COMMENT 'Event date',
    time TIME NOT NULL COMMENT 'Event start time',
    end_time TIME COMMENT 'Event end time',
    duration_minutes INT COMMENT 'Expected duration in minutes',
    
    -- Event Type and Category
    event_type ENUM('tournament', 'meetup', 'practice', 'clan_event', 'casual_session', 'competitive_match', 'community_event') DEFAULT 'casual_session',
    category ENUM('esports', 'casual', 'educational', 'social', 'competitive', 'charity', 'promotional') DEFAULT 'casual',
    difficulty_level ENUM('beginner', 'intermediate', 'advanced', 'expert', 'mixed') DEFAULT 'mixed',
    
    -- Location and Venue
    location_type ENUM('online', 'physical', 'hybrid') DEFAULT 'online',
    venue_name VARCHAR(150) COMMENT 'Physical venue name',
    venue_address TEXT COMMENT 'Physical venue address',
    venue_coordinates POINT COMMENT 'GPS coordinates for venue',
    online_platform VARCHAR(100) COMMENT 'Online platform (Discord, Teams, etc.)',
    server_details JSON COMMENT 'Game server connection details',
    
    -- Registration and Participation
    max_participants INT DEFAULT NULL COMMENT 'Maximum participants (NULL = unlimited)',
    current_participants INT DEFAULT 0 COMMENT 'Current registered participants',
    registration_required BOOLEAN DEFAULT TRUE COMMENT 'Registration required to participate',
    registration_deadline DATETIME COMMENT 'Registration deadline',
    participant_requirements TEXT COMMENT 'Requirements for participants',
    entry_fee DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Entry fee for the event',
    
    -- Event Status and Management
    status ENUM('draft', 'published', 'registration_open', 'registration_closed', 'ongoing', 'completed', 'cancelled', 'postponed') DEFAULT 'draft',
    visibility ENUM('public', 'friends', 'private', 'invite_only') DEFAULT 'public',
    approval_required BOOLEAN DEFAULT FALSE COMMENT 'Organizer approval required for registration',
    
    -- Notifications and Reminders
    reminder VARCHAR(50) COMMENT 'Reminder setting (1hour, 1day, etc.)',
    reminder_settings JSON COMMENT 'Advanced reminder configuration',
    notification_sent BOOLEAN DEFAULT FALSE COMMENT 'Track if notifications were sent',
    last_reminder_sent TIMESTAMP NULL COMMENT 'When last reminder was sent',
    
    -- Social and Community Features
    featured BOOLEAN DEFAULT FALSE COMMENT 'Featured event on homepage',
    allow_spectators BOOLEAN DEFAULT TRUE COMMENT 'Allow spectators to watch',
    allow_streaming BOOLEAN DEFAULT TRUE COMMENT 'Allow streaming/recording',
    social_media_links JSON COMMENT 'Social media links for the event',
    hashtags JSON COMMENT 'Event hashtags for social media',
    
    -- Competition and Rewards
    prizes JSON COMMENT 'Prize structure and rewards',
    tournament_format ENUM('single_elimination', 'double_elimination', 'round_robin', 'swiss', 'league') COMMENT 'Tournament format',
    team_size INT DEFAULT 1 COMMENT 'Team size for team events',
    scoring_system JSON COMMENT 'Scoring and ranking system',
    
    -- Event Analytics and Feedback
    popularity_score DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Event popularity based on registrations',
    success_rating DECIMAL(3,1) COMMENT 'Average rating from participants',
    total_views INT DEFAULT 0 COMMENT 'Total views of event page',
    participant_feedback JSON COMMENT 'Feedback from participants',
    
    -- Advanced Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL COMMENT 'When event was published',
    registration_opened_at TIMESTAMP NULL COMMENT 'When registration opened',
    registration_closed_at TIMESTAMP NULL COMMENT 'When registration closed',
    started_at TIMESTAMP NULL COMMENT 'When event actually started',
    ended_at TIMESTAMP NULL COMMENT 'When event ended',
    
    -- Constraints and Indexes
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL,
    
    -- Check constraints
    CHECK (end_time IS NULL OR end_time > time),
    CHECK (max_participants IS NULL OR max_participants > 0),
    CHECK (current_participants >= 0),
    CHECK (entry_fee >= 0),
    
    -- Performance Indexes
    INDEX idx_user_date (user_id, date),
    INDEX idx_date_time (date, time),
    INDEX idx_event_type (event_type),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_visibility (visibility),
    INDEX idx_location_type (location_type),
    INDEX idx_featured (featured),
    INDEX idx_registration_deadline (registration_deadline),
    INDEX idx_popularity (popularity_score),
    INDEX idx_created_at (created_at),
    
    -- Composite indexes for complex queries
    INDEX idx_public_upcoming (visibility, status, date),
    INDEX idx_featured_active (featured, status, date),
    INDEX idx_type_category (event_type, category),
    INDEX idx_user_status_date (user_id, status, date),
    
    -- Spatial index for location-based queries
    SPATIAL INDEX idx_venue_coordinates (venue_coordinates)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Advanced event management with tournament features';

-- Advanced EventUserMap for event participation tracking
CREATE TABLE EventUserMap (
    event_user_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    friend_id INT NOT NULL COMMENT 'Participant user ID',
    
    -- Registration Details
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registration_status ENUM('pending', 'confirmed', 'waitlist', 'cancelled', 'declined', 'checked_in') DEFAULT 'pending',
    check_in_time TIMESTAMP NULL COMMENT 'When participant checked in',
    
    -- Participation Details
    participation_type ENUM('participant', 'spectator', 'organizer', 'volunteer', 'judge') DEFAULT 'participant',
    team_name VARCHAR(100) COMMENT 'Team name for team events',
    team_role VARCHAR(50) COMMENT 'Role within the team',
    player_number INT COMMENT 'Player number for tournaments',
    
    -- Performance Tracking
    placement INT COMMENT 'Final placement in the event',
    score DECIMAL(10,2) COMMENT 'Score achieved in the event',
    performance_data JSON COMMENT 'Detailed performance statistics',
    awards JSON COMMENT 'Awards or achievements earned',
    
    -- Communication and Preferences
    contact_method ENUM('email', 'push', 'sms', 'discord') DEFAULT 'email',
    dietary_restrictions TEXT COMMENT 'Dietary restrictions for physical events',
    accessibility_needs TEXT COMMENT 'Accessibility requirements',
    emergency_contact JSON COMMENT 'Emergency contact information',
    
    -- Feedback and Rating
    event_rating DECIMAL(3,1) COMMENT 'Rating given to the event',
    feedback_text TEXT COMMENT 'Written feedback about the event',
    feedback_date TIMESTAMP NULL COMMENT 'When feedback was submitted',
    would_recommend BOOLEAN COMMENT 'Would recommend this event to others',
    
    -- Administrative
    notes TEXT COMMENT 'Admin notes about this participant',
    payment_status ENUM('unpaid', 'paid', 'refunded', 'waived') DEFAULT 'unpaid',
    payment_date TIMESTAMP NULL COMMENT 'When payment was processed',
    refund_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Refund amount if applicable',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints and Indexes
    UNIQUE KEY unique_event_participant (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    
    INDEX idx_event_status (event_id, registration_status),
    INDEX idx_participant_events (friend_id, registration_status),
    INDEX idx_registration_date (registration_date),
    INDEX idx_participation_type (participation_type),
    INDEX idx_placement (placement),
    INDEX idx_payment_status (payment_status),
    
    -- Composite indexes
    INDEX idx_event_confirmed_participants (event_id, registration_status, participation_type),
    INDEX idx_user_event_history (friend_id, event_id, registration_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Advanced event participation tracking';

-- Enhanced sample data
-- Enhanced sample data with advanced gaming content
INSERT INTO Users (username, email, password_hash, first_name, last_name, bio, gaming_level, preferred_platforms, favorite_genres) VALUES
('testuser', 'test@example.com', '$2y$10$K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/', 'Test', 'User', 'Passionate gamer and scheduler enthusiast', 'Intermediate', 'PC,PlayStation', 'Action,RPG'),
('gamer1', 'gamer1@example.com', '$2y$10$K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/', 'John', 'Gamer', 'Fortnite and Minecraft expert with 5+ years experience', 'Advanced', 'PC,Mobile', 'Battle Royale,Sandbox'),
('progamer2', 'pro@example.com', '$2y$10$K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/', 'Sarah', 'Pro', 'Professional esports player specializing in competitive FPS games', 'Professional', 'PC', 'FPS,Strategy'),
('casualplayer', 'casual@example.com', '$2y$10$K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/', 'Mike', 'Johnson', 'Weekend warrior who loves co-op adventures with friends', 'Beginner', 'Xbox,PlayStation', 'Adventure,Sports'),
('streamergirl', 'streamer@example.com', '$2y$10$K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/', 'Emma', 'Stream', 'Content creator and variety streamer focusing on indie games', 'Intermediate', 'PC,Nintendo Switch', 'Puzzle,Horror');

INSERT INTO Games (titel, title_english, description, short_description, genre, platform, rating, metacritic_score, age_rating, release_date, developer, publisher, max_players, game_modes, image_url) VALUES
('Fortnite', 'Fortnite', 'Popular battle royale game with building mechanics, vibrant graphics, and constant content updates. Features competitive gameplay and creative modes.', 'Battle royale with building mechanics', 'Battle Royale', 'PC,PlayStation,Xbox,Mobile,Nintendo Switch', 4.2, 78, '13+', '2017-09-26', 'Epic Games', 'Epic Games', 100, 'Multiplayer,Online,Cross-platform', 'fortnite.jpg'),
('Minecraft', 'Minecraft', 'Sandbox game for building, exploration, and creativity with endless possibilities. Supports both survival and creative gameplay modes.', 'Infinite sandbox creativity', 'Sandbox', 'PC,PlayStation,Xbox,Mobile,Nintendo Switch', 4.8, 93, 'E10+', '2011-11-18', 'Mojang Studios', 'Microsoft', 10, 'Single Player,Multiplayer,Co-op,Online,Cross-platform', 'minecraft.jpg'),
('Valorant', 'Valorant', 'Tactical first-person shooter with unique agent abilities, precise gunplay, and competitive ranked system designed for esports.', 'Tactical FPS with agent abilities', 'FPS', 'PC', 4.5, 80, '17+', '2020-06-02', 'Riot Games', 'Riot Games', 10, 'Multiplayer,Online', 'valorant.jpg'),
('Among Us', 'Among Us', 'Social deduction game perfect for group play and mystery solving. Players work together to find the impostor among the crew.', 'Social deduction party game', 'Party', 'PC,Mobile,Nintendo Switch', 4.0, 85, 'E10+', '2018-06-15', 'InnerSloth', 'InnerSloth', 15, 'Multiplayer,Online,Cross-platform', 'amongus.jpg'),
('League of Legends', 'League of Legends', 'Strategic MOBA with competitive ranked gameplay, diverse champions, and professional esports scene.', 'Premier MOBA with deep strategy', 'MOBA', 'PC', 4.3, 78, '13+', '2009-10-27', 'Riot Games', 'Riot Games', 10, 'Multiplayer,Online', 'lol.jpg'),
('Apex Legends', 'Apex Legends', 'Fast-paced battle royale with unique character abilities, team-based gameplay, and dynamic map changes.', 'Hero-based battle royale', 'Battle Royale', 'PC,PlayStation,Xbox,Mobile,Nintendo Switch', 4.4, 89, '17+', '2019-02-04', 'Respawn Entertainment', 'Electronic Arts', 60, 'Multiplayer,Online,Cross-platform', 'apex.jpg'),
('Rocket League', 'Rocket League', 'Physics-based vehicle soccer game combining racing and sports in unique competitive gameplay.', 'Car soccer with physics', 'Sports', 'PC,PlayStation,Xbox,Nintendo Switch', 4.6, 86, 'E', '2015-07-07', 'Psyonix', 'Epic Games', 8, 'Multiplayer,Online,Cross-platform', 'rocketleague.jpg'),
('Overwatch 2', 'Overwatch 2', 'Team-based first-person shooter with diverse heroes, objective-based gameplay, and regular content updates.', 'Hero shooter with team objectives', 'FPS', 'PC,PlayStation,Xbox,Nintendo Switch', 4.1, 79, '13+', '2022-10-04', 'Blizzard Entertainment', 'Blizzard Entertainment', 12, 'Multiplayer,Online,Cross-platform', 'overwatch2.jpg');

INSERT INTO UserGames (user_id, game_id, hours_played, skill_level, favorite, user_rating, play_style) VALUES 
(1, 1, 125.5, 'Intermediate', TRUE, 4.5, 'Casual'), 
(1, 2, 89.2, 'Advanced', TRUE, 5.0, 'Social'), 
(2, 1, 234.7, 'Expert', TRUE, 4.0, 'Competitive'), 
(2, 3, 156.3, 'Professional', TRUE, 4.8, 'Competitive'), 
(3, 3, 445.1, 'Professional', TRUE, 5.0, 'Hardcore'), 
(3, 8, 178.9, 'Expert', FALSE, 4.2, 'Competitive'),
(4, 2, 45.6, 'Beginner', TRUE, 4.0, 'Casual'),
(4, 7, 67.3, 'Intermediate', FALSE, 3.8, 'Social'),
(5, 4, 89.1, 'Intermediate', TRUE, 4.5, 'Social'),
(5, 6, 123.4, 'Advanced', FALSE, 4.3, 'Casual');

INSERT INTO Friends (user_id, friend_user_id, status, initiated_by, favorite_friend, gaming_compatibility_score, allow_invites) VALUES
(1, 2, 'accepted', 1, TRUE, 4.2, TRUE),
(1, 3, 'accepted', 3, FALSE, 3.8, TRUE),
(2, 3, 'accepted', 2, TRUE, 4.5, TRUE),
(1, 4, 'pending', 1, FALSE, NULL, TRUE),
(2, 5, 'accepted', 5, FALSE, 3.2, TRUE),
(3, 4, 'accepted', 4, FALSE, 2.8, FALSE),
(4, 5, 'accepted', 4, TRUE, 4.1, TRUE);

-- Professional security and audit tables
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    email_attempted VARCHAR(100) COMMENT 'Email that was attempted',
    username_attempted VARCHAR(50) COMMENT 'Username that was attempted',
    success BOOLEAN DEFAULT FALSE COMMENT 'Whether login was successful',
    user_agent TEXT COMMENT 'Browser user agent string',
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Security tracking
    country_code VARCHAR(2) COMMENT 'Country from IP geolocation',
    is_tor_exit BOOLEAN DEFAULT FALSE COMMENT 'Whether IP is Tor exit node',
    is_vpn BOOLEAN DEFAULT FALSE COMMENT 'Whether IP appears to be VPN',
    risk_score DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Calculated risk score 0.00-1.00',
    
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_email_time (email_attempted, attempt_time),
    INDEX idx_success (success),
    INDEX idx_risk_score (risk_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Login attempt tracking for security';

CREATE TABLE audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT COMMENT 'User who performed the action',
    action VARCHAR(100) NOT NULL COMMENT 'Action performed (CREATE, UPDATE, DELETE, etc.)',
    table_name VARCHAR(50) NOT NULL COMMENT 'Database table affected',
    record_id VARCHAR(50) COMMENT 'ID of affected record',
    old_values JSON COMMENT 'Previous values before change',
    new_values JSON COMMENT 'New values after change',
    ip_address VARCHAR(45) COMMENT 'IP address of user',
    user_agent TEXT COMMENT 'Browser user agent',
    session_id VARCHAR(128) COMMENT 'Session identifier',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_table_time (table_name, timestamp),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprehensive audit trail';

CREATE TABLE password_reset_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE COMMENT 'Secure reset token',
    expires_at TIMESTAMP NOT NULL COMMENT 'Token expiration time',
    used_at TIMESTAMP NULL COMMENT 'When token was used',
    ip_address VARCHAR(45) COMMENT 'IP that requested reset',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Password reset token management';

CREATE TABLE email_verification_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email verification token',
    email VARCHAR(100) NOT NULL COMMENT 'Email being verified',
    expires_at TIMESTAMP NOT NULL COMMENT 'Token expiration time',
    verified_at TIMESTAMP NULL COMMENT 'When email was verified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_user_email (user_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Email verification system';

CREATE TABLE user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT COMMENT 'Browser information',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Security tracking
    login_method ENUM('password', '2fa', 'oauth', 'remember_token') DEFAULT 'password',
    device_fingerprint VARCHAR(255) COMMENT 'Device identification hash',
    location_info JSON COMMENT 'Approximate location data',
    
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_expires (expires_at),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Active user session management';

-- Advanced notification system
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('friend_request', 'event_invite', 'schedule_reminder', 'system_alert', 'achievement', 'tournament', 'message') NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    data JSON COMMENT 'Additional notification data',
    
    -- Delivery tracking
    read_at TIMESTAMP NULL COMMENT 'When notification was read',
    delivered_at TIMESTAMP NULL COMMENT 'When notification was delivered',
    email_sent BOOLEAN DEFAULT FALSE,
    push_sent BOOLEAN DEFAULT FALSE,
    sms_sent BOOLEAN DEFAULT FALSE,
    
    -- Scheduling
    scheduled_for TIMESTAMP NULL COMMENT 'When to send notification',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL COMMENT 'When notification expires',
    
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, read_at),
    INDEX idx_type_priority (type, priority),
    INDEX idx_scheduled (scheduled_for),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprehensive notification system';

-- Gaming achievement system
CREATE TABLE achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(255) DEFAULT 'default-achievement.png',
    points INT DEFAULT 0 COMMENT 'Points awarded for achievement',
    category ENUM('social', 'gaming', 'tournaments', 'community', 'special') DEFAULT 'gaming',
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    
    -- Achievement criteria
    criteria JSON COMMENT 'Conditions required to unlock achievement',
    is_active BOOLEAN DEFAULT TRUE,
    is_secret BOOLEAN DEFAULT FALSE COMMENT 'Hidden until unlocked',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category_rarity (category, rarity),
    INDEX idx_points (points),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gaming achievement definitions';

CREATE TABLE user_achievements (
    user_achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress JSON COMMENT 'Progress tracking data',
    
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(achievement_id) ON DELETE CASCADE,
    
    INDEX idx_user_unlocked (user_id, unlocked_at),
    INDEX idx_achievement (achievement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User achievement tracking';

-- Insert sample achievements
INSERT INTO achievements (name, description, points, category, rarity) VALUES
('Eerste Stappen', 'Maak je eerste profiel aan en voeg een favoriete game toe', 10, 'social', 'common'),
('Sociale Vlinder', 'Voeg 5 vrienden toe aan je netwerk', 25, 'social', 'uncommon'),
('Plannings Meester', 'Maak 10 gaming sessies aan', 50, 'gaming', 'rare'),
('Evenementen Organisator', 'Organiseer je eerste toernooi of meetup', 75, 'community', 'epic'),
('Vroege Vogel', 'Log 30 dagen achter elkaar in', 100, 'special', 'legendary');