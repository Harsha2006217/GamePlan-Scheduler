<?php
require_once 'db.php';

class GamePlanFunctions {
    private $conn;
    private $upload_dir = 'uploads/';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Security functions
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function validateTime($time) {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
    }

    // User functions
    public function registerUser($username, $email, $password, $first_name = '', $last_name = '') {
        $username = $this->sanitizeInput($username);
        $email = $this->sanitizeInput($email);
        $first_name = $this->sanitizeInput($first_name);
        $last_name = $this->sanitizeInput($last_name);

        if (!$this->validateEmail($email)) {
            throw new Exception("Invalid email format");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, password_hash, first_name, last_name) 
                  VALUES (:username, :email, :password_hash, :first_name, :last_name)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        } else {
            throw new Exception("Registration failed. Username or email may already exist.");
        }
    }

    public function loginUser($username, $password) {
        $username = $this->sanitizeInput($username);
        
        $query = "SELECT user_id, username, email, password_hash, first_name, last_name 
                  FROM users WHERE username = :username AND is_active = TRUE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            if (password_verify($password, $user['password_hash'])) {
                // Update last login
                $update_query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(':user_id', $user['user_id']);
                $update_stmt->execute();

                return $user;
            }
        }
        return false;
    }

    // Game functions
    public function addGameToUser($user_id, $game_id, $hours_played = 0, $skill_level = 'Beginner', $favorite = false) {
        $query = "INSERT INTO user_games (user_id, game_id, hours_played, skill_level, favorite) 
                  VALUES (:user_id, :game_id, :hours_played, :skill_level, :favorite)
                  ON DUPLICATE KEY UPDATE hours_played = VALUES(hours_played), 
                  skill_level = VALUES(skill_level), favorite = VALUES(favorite)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':game_id', $game_id);
        $stmt->bindParam(':hours_played', $hours_played);
        $stmt->bindParam(':skill_level', $skill_level);
        $stmt->bindParam(':favorite', $favorite, PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    public function getUserGames($user_id) {
        $query = "SELECT g.*, ug.hours_played, ug.skill_level, ug.favorite, ug.added_date 
                  FROM user_games ug 
                  JOIN games g ON ug.game_id = g.game_id 
                  WHERE ug.user_id = :user_id 
                  ORDER BY ug.favorite DESC, ug.hours_played DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function searchGames($search_term) {
        $search_term = "%" . $this->sanitizeInput($search_term) . "%";
        
        $query = "SELECT * FROM games 
                  WHERE game_title LIKE :search_term 
                  OR game_description LIKE :search_term 
                  OR genre LIKE :search_term 
                  ORDER BY game_title";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':search_term', $search_term);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Schedule functions
    public function createSchedule($user_id, $game_id, $schedule_title, $schedule_description, 
                                 $schedule_date, $start_time, $end_time, $recurring = 'None', $max_participants = 1) {
        
        if (!$this->validateDate($schedule_date) || !$this->validateTime($start_time) || !$this->validateTime($end_time)) {
            throw new Exception("Invalid date or time format");
        }

        $query = "INSERT INTO schedules (user_id, game_id, schedule_title, schedule_description, 
                  schedule_date, start_time, end_time, recurring, max_participants) 
                  VALUES (:user_id, :game_id, :schedule_title, :schedule_description, 
                  :schedule_date, :start_time, :end_time, :recurring, :max_participants)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':game_id', $game_id);
        $stmt->bindParam(':schedule_title', $schedule_title);
        $stmt->bindParam(':schedule_description', $schedule_description);
        $stmt->bindParam(':schedule_date', $schedule_date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        $stmt->bindParam(':recurring', $recurring);
        $stmt->bindParam(':max_participants', $max_participants);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        } else {
            throw new Exception("Failed to create schedule");
        }
    }

    public function getUserSchedules($user_id, $start_date = null, $end_date = null) {
        $query = "SELECT s.*, g.game_title, g.genre, u.username as host_username 
                  FROM schedules s 
                  JOIN games g ON s.game_id = g.game_id 
                  JOIN users u ON s.user_id = u.user_id 
                  WHERE s.user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if ($start_date && $end_date) {
            $query .= " AND s.schedule_date BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }

        $query .= " ORDER BY s.schedule_date, s.start_time";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Event functions
    public function createEvent($schedule_id, $event_title, $event_description, $event_type = 'Casual') {
        $query = "INSERT INTO events (schedule_id, event_title, event_description, event_type) 
                  VALUES (:schedule_id, :event_title, :event_description, :event_type)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':schedule_id', $schedule_id);
        $stmt->bindParam(':event_title', $event_title);
        $stmt->bindParam(':event_description', $event_description);
        $stmt->bindParam(':event_type', $event_type);

        if ($stmt->execute()) {
            $event_id = $this->conn->lastInsertId();
            
            // Add schedule creator as host participant
            $this->addEventParticipant($event_id, $this->getScheduleOwner($schedule_id), 'Host', 'Accepted');
            
            return $event_id;
        } else {
            throw new Exception("Failed to create event");
        }
    }

    private function getScheduleOwner($schedule_id) {
        $query = "SELECT user_id FROM schedules WHERE schedule_id = :schedule_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':schedule_id', $schedule_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['user_id'] : null;
    }

    public function addEventParticipant($event_id, $user_id, $role = 'Participant', $join_status = 'Pending') {
        $query = "INSERT INTO event_participants (event_id, user_id, role, join_status) 
                  VALUES (:event_id, :user_id, :role, :join_status)
                  ON DUPLICATE KEY UPDATE role = VALUES(role), join_status = VALUES(join_status)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':join_status', $join_status);

        return $stmt->execute();
    }

    // Friend functions
    public function sendFriendRequest($user_id, $friend_id) {
        if ($user_id == $friend_id) {
            throw new Exception("Cannot send friend request to yourself");
        }

        $query = "INSERT INTO friends (user_id, friend_id, status) 
                  VALUES (:user_id, :friend_id, 'Pending')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Friend request already sent or users are already friends");
        }
    }

    public function getFriendRequests($user_id) {
        $query = "SELECT f.*, u.username, u.first_name, u.last_name 
                  FROM friends f 
                  JOIN users u ON f.user_id = u.user_id 
                  WHERE f.friend_id = :user_id AND f.status = 'Pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getFriends($user_id) {
        $query = "SELECT u.user_id, u.username, u.first_name, u.last_name, u.profile_picture 
                  FROM friends f 
                  JOIN users u ON (f.friend_id = u.user_id AND f.user_id = :user_id) 
                  OR (f.user_id = u.user_id AND f.friend_id = :user_id) 
                  WHERE f.status = 'Accepted'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Notification functions
    public function createNotification($user_id, $title, $message, $type = 'System', $related_id = null) {
        $query = "INSERT INTO notifications (user_id, title, message, type, related_id) 
                  VALUES (:user_id, :title, :message, :type, :related_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':related_id', $related_id);

        return $stmt->execute();
    }

    public function getUserNotifications($user_id, $unread_only = false) {
        $query = "SELECT * FROM notifications WHERE user_id = :user_id";
        
        if ($unread_only) {
            $query .= " AND is_read = FALSE";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Utility functions
    public function formatDateTime($date, $time) {
        return date('Y-m-d H:i:s', strtotime("$date $time"));
    }

    public function getTimeRemaining($datetime) {
        $now = new DateTime();
        $future = new DateTime($datetime);
        $interval = $now->diff($future);

        if ($interval->invert) {
            return 'Past';
        }

        if ($interval->days > 0) {
            return $interval->days . ' day' . ($interval->days > 1 ? 's' : '');
        } elseif ($interval->h > 0) {
            return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
        } else {
            return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
        }
    }
}

// Initialize functions class
$gameplan = new GamePlanFunctions($db);

// Session management
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>