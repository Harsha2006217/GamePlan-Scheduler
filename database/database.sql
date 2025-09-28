CREATE DATABASE IF NOT EXISTS gameplan_db;
USE gameplan_db;

CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS UserGames (
    user_id INT,
    game_id INT,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    friend_user_id INT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    game_id INT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    friends TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Events (
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

CREATE TABLE IF NOT EXISTS EventUserMap (
    event_id INT,
    friend_id INT,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Voorbeeldgegevens
INSERT INTO Users (username, email, password_hash) VALUES ('testuser', 'test@example.com', '$2y$10$examplehashedpassword');

INSERT INTO Games (titel, description) VALUES ('Fortnite', 'Battle royale game'), ('Minecraft', 'Sandbox game');

INSERT INTO UserGames (user_id, game_id) VALUES (1, 1);
