-- Create database
CREATE DATABASE IF NOT EXISTS gameplan_db;
USE gameplan_db;

-- Users table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Games table
CREATE TABLE Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    gametitel VARCHAR(100) NOT NULL,
    game_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- UserGames table (favorite games)
CREATE TABLE UserGames (
    user_id INT,
    game_id INT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);

-- Friends table
CREATE TABLE Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    status ENUM('pending', 'accepted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_user_id)
);

-- Schedules table
CREATE TABLE Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    schedule_time TIME NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);

-- Events table
CREATE TABLE Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    schedule_id INT,
    event_title VARCHAR(100) NOT NULL,
    event_description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    reminder_type ENUM('none', '15min', '1hour', '1day') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL
);

-- Event User Map table
CREATE TABLE EventUserMap (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Insert sample games
INSERT INTO Games (gametitel, game_description) VALUES
('Fortnite', 'Battle Royale game with building mechanics'),
('League of Legends', 'MOBA game with champions and strategy'),
('Valorant', 'Tactical shooter with unique agent abilities'),
('Minecraft', 'Sandbox building and exploration game'),
('Call of Duty: Warzone', 'Battle Royale first-person shooter'),
('Apex Legends', 'Hero-based battle royale game'),
('Overwatch 2', 'Team-based hero shooter'),
('Rocket League', 'Soccer with rocket-powered cars'),
('Genshin Impact', 'Action RPG with gacha elements'),
('Counter-Strike 2', 'Tactical team-based shooter');

-- Create indexes for better performance
CREATE INDEX idx_users_username ON Users(username);
CREATE INDEX idx_users_email ON Users(email);
CREATE INDEX idx_friends_user ON Friends(user_id);
CREATE INDEX idx_friends_friend ON Friends(friend_user_id);
CREATE INDEX idx_schedules_user_date ON Schedules(user_id, schedule_date);
CREATE INDEX idx_events_user_date ON Events(user_id, event_date);
CREATE INDEX idx_event_user_map_event ON EventUserMap(event_id);
CREATE INDEX idx_event_user_map_friend ON EventUserMap(friend_id);