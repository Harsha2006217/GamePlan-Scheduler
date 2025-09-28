<?php
session_start();
require 'db.php';

// Wachtwoord hashen met bcrypt
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Login gebruiker, check password en update last_activity
function loginUser($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        session_regenerate_id(true); // Prevent session fixation
        // Update last_activity
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = NOW() WHERE user_id = :id");
        $stmt->bindParam(':id', $user['user_id']);
        $stmt->execute();
        return true;
    }
    return false;
}

// Check of ingelogd
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Profiel ophalen
function getProfile($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    return $stmt->fetch();
}

// Favoriete games ophalen
function getFavoriteGames($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT g.titel, g.description FROM UserGames ug JOIN Games g ON ug.game_id = g.game_id WHERE ug.user_id = :user");
    $stmt->bindParam(':user', $user_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Favoriete game toevoegen
function addFavoriteGame($user_id, $game_id) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT IGNORE INTO UserGames (user_id, game_id) VALUES (:user, :game)");
    $stmt->bindParam(':user', $user_id);
    $stmt->bindParam(':game', $game_id);
    return $stmt->execute();
}

// Alle games ophalen
function getGames() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Games");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Vriend toevoegen, check zelf en duplicaat
function addFriend($user_id, $friend_username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username AND user_id != :user");
    $stmt->bindParam(':username', $friend_username);
    $stmt->bindParam(':user', $user_id);
    $stmt->execute();
    $friend = $stmt->fetch();
    if ($friend) {
        $friend_id = $friend['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM Friends WHERE user_id = :user AND friend_user_id = :friend");
        $stmt->bindParam(':user', $user_id);
        $stmt->bindParam(':friend', $friend_id);
        $stmt->execute();
        if ($stmt->fetch()) {
            return false; // Al vriend
        }
        $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (:user, :friend)");
        $stmt->bindParam(':user', $user_id);
        $stmt->bindParam(':friend', $friend_id);
        return $stmt->execute();
    }
    return false;
}

// Vrienden ophalen met online status
function getFriends($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, u.last_activity FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = :user");
    $stmt->bindParam(':user', $user_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Schema toevoegen
function addSchedule($user_id, $game_id, $date, $time, $friends) {
    global $pdo;
    if (empty($game_id) || strtotime($date) < time() || preg_match('/^-/', $time)) {
        return false;
    }
    $friends_str = implode(',', $friends);
    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (:user, :game, :date, :time, :friends)");
    $stmt->bindParam(':user', $user_id);
    $stmt->bindParam(':game', $game_id);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':friends', $friends_str);
    return $stmt->execute();
}

// Schema's ophalen
function getSchedules($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.*, g.titel AS game_titel FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = :user ORDER BY date, time LIMIT 50");
    $stmt->bindParam(':user', $user_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Schema bewerken
function editSchedule($schedule_id, $game_id, $date, $time, $friends) {
    global $pdo;
    $friends_str = implode(',', $friends);
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = :game, date = :date, time = :time, friends = :friends WHERE schedule_id = :id");
    $stmt->bindParam(':game', $game_id);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':friends', $friends_str);
    $stmt->bindParam(':id', $schedule_id);
    return $stmt->execute();
}

// Schema verwijderen
function deleteSchedule($schedule_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = :id");
    $stmt->bindParam(':id', $schedule_id);
    return $stmt->execute();
}

// Evenement toevoegen
function addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    global $pdo;
    if (empty($title) || strlen($title) > 100 || strtotime($date) < time() || preg_match('/^-/', $time)) {
        return false;
    }
    $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (:user, :title, :date, :time, :desc, :rem, :sched)");
    $stmt->bindParam(':user', $user_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':desc', $description);
    $stmt->bindParam(':rem', $reminder);
    $stmt->bindParam(':sched', $schedule_id);
    if ($stmt->execute()) {
        $event_id = $pdo->lastInsertId();
        foreach ($shared_friends as $friend_id) {
            $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (:event, :friend)");
            $stmt->bindParam(':event', $event_id);
            $stmt->bindParam(':friend', $friend_id);
            $stmt->execute();
        }
        return true;
    }
    return false;
}

// Evenementen ophalen
function getEvents($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.*, s.game_id FROM Events e LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id WHERE e.user_id = :user ORDER BY date, time LIMIT 50");
    $stmt->bindParam(':user', $user_id);
    $stmt->execute();
    $events = $stmt->fetchAll();
    foreach ($events as &$event) {
        $stmt = $pdo->prepare("SELECT u.username FROM EventUserMap em JOIN Users u ON em.friend_id = u.user_id WHERE em.event_id = :event");
        $stmt->bindParam(':event', $event['event_id']);
        $stmt->execute();
        $event['shared_with'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $events;
}

// Evenement bewerken
function editEvent($event_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE Events SET title = :title, date = :date, time = :time, description = :desc, reminder = :rem, schedule_id = :sched WHERE event_id = :id");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':desc', $description);
    $stmt->bindParam(':rem', $reminder);
    $stmt->bindParam(':sched', $schedule_id);
    $stmt->bindParam(':id', $event_id);
    if ($stmt->execute()) {
        $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :id");
        $stmt->bindParam(':id', $event_id);
        $stmt->execute();
        foreach ($shared_friends as $friend_id) {
            $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (:event, :friend)");
            $stmt->bindParam(':event', $event_id);
            $stmt->bindParam(':friend', $friend_id);
            $stmt->execute();
        }
        return true;
    }
    return false;
}

// Evenement verwijderen
function deleteEvent($event_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :id");
    $stmt->bindParam(':id', $event_id);
    $stmt->execute();
    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = :id");
    $stmt->bindParam(':id', $event_id);
    return $stmt->execute();
}

// Uitloggen
function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}