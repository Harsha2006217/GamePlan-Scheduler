-- Geavanceerd SQL-script voor GamePlan Scheduler database
-- Ondersteunt relaties, indexes voor performance, constraints voor data-integriteit
-- Inclusief voorbeeldgegevens voor testen en demo
-- Ontworpen voor MySQL 8.0+, met foreign keys en cascade voor automatische cleanup

CREATE DATABASE IF NOT EXISTS gameplan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gameplan_db;

-- Tabel Users: Basis voor gebruikersaccounts met unieke velden en activiteitstracking
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unieke gebruikersnaam, max 50 tekens',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Uniek e-mailadres voor login en contact',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Gehasht wachtwoord met bcrypt voor security',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Laatste activiteit voor online/offline status'
) ENGINE=InnoDB COMMENT='Tabel voor gebruiker accounts met security features';

-- Tabel Games: Predefined lijst van games met beschrijving
CREATE TABLE IF NOT EXISTS Games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(100) NOT NULL UNIQUE COMMENT 'Naam van de game, uniek',
    description TEXT COMMENT 'Beschrijving van de game voor profielweergave'
) ENGINE=InnoDB COMMENT='Tabel voor beschikbare games';

-- Tabel UserGames: Koppelt users aan favoriete games (many-to-many relatie)
CREATE TABLE IF NOT EXISTS UserGames (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Koppeltabel voor favoriete games per gebruiker';

-- Tabel Friends: Vriendenrelaties tussen users (self-referencing)
CREATE TABLE IF NOT EXISTS Friends (
    friend_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'User die vriend toevoegt',
    friend_user_id INT NOT NULL COMMENT 'User die toegevoegd wordt als vriend',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (friend_user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_friends (user_id, friend_user_id) COMMENT 'Voorkomt dubbele vriendschappen'
) ENGINE=InnoDB COMMENT='Tabel voor vriendschapsrelaties met unieke checks';

-- Tabel Schedules: Gaming schema's met link naar game en vrienden (tekstveld voor eenvoud)
CREATE TABLE IF NOT EXISTS Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Eigenaar van het schema',
    game_id INT NOT NULL COMMENT 'Link naar game',
    date DATE NOT NULL COMMENT 'Datum van het schema',
    time TIME NOT NULL COMMENT 'Tijd van het schema',
    friends TEXT COMMENT 'Comma-separated lijst van friend_user_ids voor delen',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Tabel voor gaming schema's met delen via vrienden';

-- Tabel Events: Evenementen met optionele link naar schema en reminder
CREATE TABLE IF NOT EXISTS Events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Eigenaar van het evenement',
    title VARCHAR(100) NOT NULL COMMENT 'Titel van het evenement, max 100 tekens',
    date DATE NOT NULL COMMENT 'Datum van het evenement',
    time TIME NOT NULL COMMENT 'Tijd van het evenement',
    description TEXT COMMENT 'Beschrijving van het evenement',
    reminder VARCHAR(50) COMMENT 'Reminder optie zoals \'1 uur ervoor\'',
    schedule_id INT NULL COMMENT 'Optionele link naar een schema',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Tabel voor evenementen met reminder en schema link';

-- Tabel EventUserMap: Koppelt events aan vrienden voor delen (many-to-many)
CREATE TABLE IF NOT EXISTS EventUserMap (
    event_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (event_id, friend_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Koppeltabel voor het delen van events met vrienden';

-- Indexes voor performance optimalisatie op veelgebruikte queries
ALTER TABLE Users ADD INDEX idx_username (username);
ALTER TABLE Users ADD INDEX idx_last_activity (last_activity);
ALTER TABLE Schedules ADD INDEX idx_user_date (user_id, date);
ALTER TABLE Events ADD INDEX idx_user_date (user_id, date);
ALTER TABLE Friends ADD INDEX idx_user_friends (user_id);

-- Voorbeeldgegevens voor testen en demo (realistische data voor jonge gamers)
INSERT INTO Games (titel, description) VALUES
('Fortnite', 'Battle royale game met bouwen en schieten. Ideaal voor teams.'),
('Minecraft', 'Bouw je wereld en verken met vrienden. Creatief en avontuurlijk.'),
('League of Legends', 'MOBA game met strategie en teamplay. Voor competitieve gamers.'),
('Among Us', 'Social deduction game. Vind de impostor met vrienden.'),
('Roblox', 'Platform voor user-generated games. Speel en maak met anderen.');

INSERT INTO Users (username, email, password_hash) VALUES
('testuser1', 'test1@example.com', '$2y$10$K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/'),
('testuser2', 'test2@example.com', '$2y$10$K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/0q0K.IwX0z0/'),
('gamerpro', 'gamerpro@example.com', '$2y$10$dummyhashfordemo1');

INSERT INTO UserGames (user_id, game_id) VALUES
(1, 1), (1, 2), (2, 3), (3, 4);

INSERT INTO Friends (user_id, friend_user_id) VALUES
(1, 2), (1, 3), (2, 1), (3, 1);

INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES
(1, 1, '2025-10-05', '15:00:00', '2,3'),
(2, 3, '2025-10-06', '18:00:00', '1');

INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES
(1, 'Fortnite Toernooi', '2025-10-10', '20:00:00', 'Team battle met prijzen', '1 uur ervoor', 1),
(2, 'Minecraft Bouwen', '2025-10-11', '16:00:00', 'Bouw een kasteel samen', '1 dag ervoor', NULL);

INSERT INTO EventUserMap (event_id, friend_id) VALUES
(1, 2), (1, 3), (2, 1);

-- Database optimalisatie: Analyze tables voor betere query planning
ANALYZE TABLE Users, Games, UserGames, Friends, Schedules, Events, EventUserMap;