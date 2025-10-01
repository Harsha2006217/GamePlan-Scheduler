-- Database: gameplan_scheduler
CREATE DATABASE IF NOT EXISTS gameplan_scheduler CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gameplan_scheduler;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    profile_picture VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Games table
CREATE TABLE games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    game_title VARCHAR(100) NOT NULL,
    game_description TEXT,
    genre VARCHAR(50),
    platform VARCHAR(50),
    release_year INT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- UserGames junction table (many-to-many)
CREATE TABLE user_games (
    user_game_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    hours_played DECIMAL(10,2) DEFAULT 0,
    skill_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Beginner',
    favorite BOOLEAN DEFAULT FALSE,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_game (user_id, game_id)
);

-- Schedules table
CREATE TABLE schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    schedule_title VARCHAR(100) NOT NULL,
    schedule_description TEXT,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    recurring ENUM('None', 'Daily', 'Weekly', 'Monthly') DEFAULT 'None',
    max_participants INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
);

-- Events table
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    event_title VARCHAR(100) NOT NULL,
    event_description TEXT,
    event_type ENUM('Tournament', 'Practice', 'Casual', 'Ranked') DEFAULT 'Casual',
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE
);

-- Event participants table
CREATE TABLE event_participants (
    event_participant_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('Host', 'Participant', 'Spectator') DEFAULT 'Participant',
    join_status ENUM('Pending', 'Accepted', 'Declined') DEFAULT 'Pending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_user (event_id, user_id)
);

-- Friends table
CREATE TABLE friends (
    friendship_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('Pending', 'Accepted', 'Blocked') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id)
);

-- Notifications table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Friend Request', 'Event Invite', 'Reminder', 'System') DEFAULT 'System',
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_user_games_user ON user_games(user_id);
CREATE INDEX idx_user_games_game ON user_games(game_id);
CREATE INDEX idx_schedules_user ON schedules(user_id);
CREATE INDEX idx_schedules_date ON schedules(schedule_date);
CREATE INDEX idx_events_schedule ON events(schedule_id);
CREATE INDEX idx_friends_user ON friends(user_id);
CREATE INDEX idx_friends_friend ON friends(friend_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);

-- Sample data
INSERT INTO users (username, email, password_hash, first_name, last_name, bio) VALUES
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'Passionate gamer and esports enthusiast'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'Casual gamer who loves RPGs'),
('mike_wilson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Wilson', 'Competitive FPS player');

INSERT INTO games (game_title, game_description, genre, platform, release_year) VALUES
('Cyberpunk 2077', 'Open-world action-adventure RPG set in Night City', 'RPG', 'PC, PlayStation, Xbox', 2020),
('Valorant', 'Team-based tactical shooter and character-based game', 'FPS', 'PC', 2020),
('League of Legends', 'Team-based strategy game where two teams compete to destroy bases', 'MOBA', 'PC', 2009),
('Elden Ring', 'Action RPG set in a world created by Hidetaka Miyazaki and George R. R. Martin', 'RPG', 'Multi-platform', 2022);

INSERT INTO user_games (user_id, game_id, hours_played, skill_level, favorite) VALUES
(1, 1, 85.5, 'Advanced', TRUE),
(1, 2, 120.0, 'Expert', TRUE),
(2, 1, 45.0, 'Intermediate', TRUE),
(2, 3, 200.0, 'Expert', FALSE),
(3, 2, 300.0, 'Expert', TRUE);