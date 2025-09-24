CREATE DATABASE gameplan_db;
USE gameplan_db;

CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE UserGames (
    user_id INT,
    game_id INT,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);

CREATE TABLE Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    friend_user_id INT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    game_id INT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);

CREATE TABLE Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    reminder VARCHAR(50),
    schedule_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL
);

CREATE TABLE EventUserMap (
    event_id INT,
    friend_id INT,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Voorbeeld data
INSERT INTO Users (username, email, password_hash) VALUES
('testuser', 'test@example.com', '$2y$10$K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/');

INSERT INTO Games (titel, description) VALUES
('Fortnite', 'Popular battle royale game with building mechanics.'),
('Minecraft', 'Sandbox game for building and exploration.');

INSERT INTO UserGames (user_id, game_id) VALUES (1, 1), (1, 2);