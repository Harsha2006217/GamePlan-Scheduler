<?php
// Centrale functies voor GamePlan Scheduler
// Georganiseerd per functionaliteit, met uitgebreide validatie, logging en error handling
// Alle functies gebruiken prepared statements voor security
// Inclusief caching simulatie voor veelgebruikte queries zoals getGames

require 'db.php';

session_start();  // Start sessie voor alle functies

// Logging functie voor auditing en debugging (logt acties naar file)
function logAction($action, $details = '') {
    $log = date('Y-m-d H:i:s') . " - User ID: " . ($_SESSION['user_id'] ?? 'Guest') . " - Action: $action - Details: $details\n";
    file_put_contents('app_log.log', $log, FILE_APPEND);
}

// Validatie helper: Controleert op leeg/trim, lengte, regex patroon
function validateInput($input, $type = 'string', $maxLength = 0, $minLength = 0, $pattern = null, $errorMsg = 'Ongeldige invoer') {
    $input = trim($input);
    if (empty($input) && $minLength > 0) {
        throw new Exception('Veld is verplicht en mag niet leeg zijn.');
    }
    if ($maxLength > 0 && strlen($input) > $maxLength) {
        throw new Exception("Veld mag maximaal $maxLength tekens zijn.");
    }
    if ($minLength > 0 && strlen($input) < $minLength) {
        throw new Exception("Veld moet minimaal $minLength tekens zijn.");
    }
    if ($pattern && !preg_match($pattern, $input)) {
        throw new Exception($errorMsg);
    }
    return sanitizeInput($input);  // Sanitize tegen XSS
}

// Sanitize functie voor output (htmlspecialchars met quotes)
function sanitizeInput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Registratie functie: Valideert en hash wachtwoord, logt actie
function registerUser($username, $email, $password) {
    global $pdo;
    try {
        validateInput($username, 'string', 50, 3, '/^[a-zA-Z0-9]+$/', 'Username mag alleen letters en cijfers bevatten.');
        validateInput($email, 'string', 100, 5, '/^[^\s@]+@[^\s@]+\.[^\s@]+$/', 'Ongeldig e-mailadres.');
        validateInput($password, 'string', 255, 8, null, 'Wachtwoord moet minimaal 8 tekens zijn.');

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (:username, :email, :hash)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();

        logAction('Registratie', "Nieuwe gebruiker: $username ($email)");
        return true;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {  // Duplicate entry error
            throw new Exception("Gebruikersnaam of e-mail bestaat al.");
        }
        logAction('Registratie fout', $e->getMessage());
        throw new Exception("Registratie mislukt. Probeer opnieuw.");
    } catch (Exception $e) {
        throw $e;
    }
}

// Login functie: Verifieert wachtwoord, update last_activity, sessie regen
function loginUser($email, $password) {
    global $pdo;
    try {
        validateInput($email, 'string', 100, 5, '/^[^\s@]+@[^\s@]+\.[^\s@]+$/', 'Ongeldig e-mailadres.');

        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            session_regenerate_id(true);  // Regenerate sessie ID tegen fixation attacks

            // Update last_activity
            $updateStmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :id");
            $updateStmt->bindParam(':id', $user['user_id']);
            $updateStmt->execute();

            logAction('Login', "Gebruiker ingelogd: " . $user['username']);
            return true;
        }
        throw new Exception("Ongeldig e-mail of wachtwoord.");
    } catch (Exception $e) {
        logAction('Login fout', $e->getMessage());
        throw $e;
    }
}

// Logout functie: Vernietig sessie en log actie
function logoutUser() {
    logAction('Logout', "Gebruiker uitgelogd: " . ($_SESSION['user_id'] ?? 'Unknown'));
    session_destroy();
    header("Location: login.php");
    exit;
}

// Check sessie timeout (30 min = 1800 sec)
function checkSessionTimeout() {
    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        logoutUser();
    }
    $_SESSION['last_activity'] = time();  // Update timestamp
}

// Functie om favoriete game toe te voegen (voor profiel)
function addFavoriteGame($user_id, $game_id) {
    global $pdo;
    try {
        validateInput($game_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig game ID.');

        $stmt = $pdo->prepare("INSERT IGNORE INTO UserGames (user_id, game_id) VALUES (:user_id, :game_id)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':game_id', $game_id, PDO::PARAM_INT);
        $stmt->execute();

        logAction('Favoriete game toegevoegd', "User $user_id voegde game $game_id toe.");
        return true;
    } catch (Exception $e) {
        logAction('Favoriete game fout', $e->getMessage());
        throw $e;
    }
}

// Functie om favoriete games op te halen (met caching simulatie via static var)
function getFavoriteGames($user_id) {
    static $cache = [];  // Simple in-memory cache per request
    $cacheKey = 'favorites_' . $user_id;

    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT g.game_id, g.titel, g.description FROM UserGames ug JOIN Games g ON ug.game_id = g.game_id WHERE ug.user_id = :user_id LIMIT 50");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $games = $stmt->fetchAll();

    $cache[$cacheKey] = $games;
    return $games;
}

// Functie om vriend toe te voegen (check op zelf en duplicaten)
function addFriend($user_id, $friend_username) {
    global $pdo;
    try {
        validateInput($friend_username, 'string', 50, 3, '/^[a-zA-Z0-9]+$/', 'Ongeldige vriend gebruikersnaam.');

        $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username");
        $stmt->bindParam(':username', $friend_username);
        $stmt->execute();
        $friend = $stmt->fetch();

        if (!$friend) {
            throw new Exception("Gebruiker niet gevonden.");
        }
        $friend_id = $friend['user_id'];

        if ($friend_id == $user_id) {
            throw new Exception("Je kunt jezelf niet toevoegen als vriend.");
        }

        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Friends WHERE user_id = :user_id AND friend_user_id = :friend_id");
        $checkStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $checkStmt->bindParam(':friend_id', $friend_id, PDO::PARAM_INT);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("Deze vriend is al toegevoegd.");
        }

        $insertStmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (:user_id, :friend_id)");
        $insertStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $insertStmt->bindParam(':friend_id', $friend_id, PDO::PARAM_INT);
        $insertStmt->execute();

        logAction('Vriend toegevoegd', "User $user_id voegde vriend $friend_id toe.");
        return true;
    } catch (Exception $e) {
        logAction('Vriend toevoegen fout', $e->getMessage());
        throw $e;
    }
}

// Functie om vriendenlijst op te halen met online status
function getFriends($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, u.last_activity > (NOW() - INTERVAL 5 MINUTE) AS online FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = :user_id LIMIT 50");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Functie om schema toe te voegen met vrienden delen
function addSchedule($user_id, $game_id, $date, $time, $friends = []) {
    global $pdo;
    try {
        validateInput($game_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig game ID.');
        validateInput($date, 'string', 0, 0, '/^\d{4}-\d{2}-\d{2}$/', 'Ongeldig datum formaat (YYYY-MM-DD).');
        validateInput($time, 'string', 0, 0, '/^\d{2}:\d{2}(:\d{2})?$/', 'Ongeldig tijd formaat (HH:MM or HH:MM:SS).');

        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            throw new Exception("Datum moet in de toekomst liggen.");
        }

        $friendsStr = implode(',', $friends);  // Converteer array naar comma-separated string

        $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (:user_id, :game_id, :date, :time, :friends)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':game_id', $game_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':friends', $friendsStr);
        $stmt->execute();

        logAction('Schema toegevoegd', "User $user_id voegde schema toe voor game $game_id op $date $time met vrienden: $friendsStr");
        return true;
    } catch (Exception $e) {
        logAction('Schema toevoegen fout', $e->getMessage());
        throw $e;
    }
}

// Functie om schedules op te halen met game titel
function getSchedules($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.*, g.titel AS game_titel FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = :user_id ORDER BY s.date, s.time LIMIT 50");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Functie om schema te bewerken
function editSchedule($schedule_id, $user_id, $game_id, $date, $time, $friends = []) {
    global $pdo;
    try {
        validateInput($schedule_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig schema ID.');
        // Hergebruik validatie van addSchedule

        $friendsStr = implode(',', $friends);

        $stmt = $pdo->prepare("UPDATE Schedules SET game_id = :game_id, date = :date, time = :time, friends = :friends WHERE schedule_id = :schedule_id AND user_id = :user_id");
        $stmt->bindParam(':game_id', $game_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':friends', $friendsStr);
        $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            throw new Exception("Geen rechten om dit te bewerken of niet gevonden.");
        }

        logAction('Schema bewerkt', "User $user_id bewerkte schema $schedule_id");
        return true;
    } catch (Exception $e) {
        logAction('Schema bewerken fout', $e->getMessage());
        throw $e;
    }
}

// Functie om schema te verwijderen
function deleteSchedule($schedule_id, $user_id) {
    global $pdo;
    try {
        validateInput($schedule_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig schema ID.');

        $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = :schedule_id AND user_id = :user_id");
        $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            throw new Exception("Geen rechten om dit te verwijderen of niet gevonden.");
        }

        logAction('Schema verwijderd', "User $user_id verwijderde schema $schedule_id");
        return true;
    } catch (Exception $e) {
        logAction('Schema verwijderen fout', $e->getMessage());
        throw $e;
    }
}

// Functie om evenement toe te voegen met delen en reminder
function addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id = null, $shared_friends = []) {
    global $pdo;
    try {
        validateInput($title, 'string', 100, 1, null, 'Titel is verplicht en max 100 tekens.');
        validateInput($date, 'string', 0, 0, '/^\d{4}-\d{2}-\d{2}$/', 'Ongeldig datum formaat.');
        validateInput($time, 'string', 0, 0, '/^\d{2}:\d{2}(:\d{2})?$/', 'Ongeldig tijd formaat.');
        validateInput($description, 'string', 500, 0, null, 'Beschrijving max 500 tekens.');
        validateInput($reminder, 'string', 50, 0, null, 'Ongeldige reminder optie.');
        if ($schedule_id) validateInput($schedule_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig schema ID.');

        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            throw new Exception("Datum moet in de toekomst liggen.");
        }

        $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (:user_id, :title, :date, :time, :description, :reminder, :schedule_id)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':reminder', $reminder);
        $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $stmt->execute();

        $event_id = $pdo->lastInsertId();

        // Deel met vrienden via mapping
        foreach ($shared_friends as $friend_id) {
            validateInput($friend_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig vriend ID.');
            $mapStmt = $pdo->prepare("INSERT IGNORE INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
            $mapStmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $mapStmt->bindParam(':friend_id', $friend_id, PDO::PARAM_INT);
            $mapStmt->execute();
        }

        logAction('Evenement toegevoegd', "User $user_id voegde event $event_id toe met gedeelde vrienden: " . implode(',', $shared_friends));
        return true;
    } catch (Exception $e) {
        logAction('Evenement toevoegen fout', $e->getMessage());
        throw $e;
    }
}

// Functie om events op te halen met shared vrienden en game info
function getEvents($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.*, s.game_id AS linked_game FROM Events e LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id WHERE e.user_id = :user_id ORDER BY e.date, e.time LIMIT 50");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $events = $stmt->fetchAll();

    foreach ($events as &$event) {
        $sharedStmt = $pdo->prepare("SELECT u.username FROM EventUserMap em JOIN Users u ON em.friend_id = u.user_id WHERE em.event_id = :event_id");
        $sharedStmt->bindParam(':event_id', $event['event_id'], PDO::PARAM_INT);
        $sharedStmt->execute();
        $event['shared_with'] = $sharedStmt->fetchAll(PDO::FETCH_COLUMN, 0);  // Array van usernames
    }
    return $events;
}

// Functie om event te bewerken
function editEvent($event_id, $user_id, $title, $date, $time, $description, $reminder, $schedule_id = null, $shared_friends = []) {
    global $pdo;
    try {
        validateInput($event_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig event ID.');
        // Hergebruik validatie van addEvent

        $stmt = $pdo->prepare("UPDATE Events SET title = :title, date = :date, time = :time, description = :description, reminder = :reminder, schedule_id = :schedule_id WHERE event_id = :event_id AND user_id = :user_id");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':reminder', $reminder);
        $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            throw new Exception("Geen rechten om dit te bewerken of niet gevonden.");
        }

        // Update shared vrienden: Eerst verwijder oude, dan voeg nieuwe toe
        $deleteMap = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
        $deleteMap->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $deleteMap->execute();

        foreach ($shared_friends as $friend_id) {
            $mapStmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
            $mapStmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $mapStmt->bindParam(':friend_id', $friend_id, PDO::PARAM_INT);
            $mapStmt->execute();
        }

        logAction('Evenement bewerkt', "User $user_id bewerkte event $event_id");
        return true;
    } catch (Exception $e) {
        logAction('Evenement bewerken fout', $e->getMessage());
        throw $e;
    }
}

// Functie om event te verwijderen
function deleteEvent($event_id, $user_id) {
    global $pdo;
    try {
        validateInput($event_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig event ID.');

        // Eerst mapping verwijderen (cascade handelt dit, maar expliciet voor clarity)
        $deleteMap = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
        $deleteMap->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $deleteMap->execute();

        $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = :event_id AND user_id = :user_id");
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            throw new Exception("Geen rechten om dit te verwijderen of niet gevonden.");
        }

        logAction('Evenement verwijderd', "User $user_id verwijderde event $event_id");
        return true;
    } catch (Exception $e) {
        logAction('Evenement verwijderen fout', $e->getMessage());
        throw $e;
    }
}

// Functie om games op te halen (met caching simulatie)
function getGames() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Games ORDER BY titel ASC LIMIT 100");
    $stmt->execute();
    $cache = $stmt->fetchAll();
    return $cache;
}

// Helper voor kalender: Merge en sort schedules/events
function getCalendarItems($user_id) {
    $schedules = getSchedules($user_id);
    $events = getEvents($user_id);

    // Voeg type toe voor onderscheid in view
    foreach ($schedules as &$sch) {
        $sch['type'] = 'schedule';
        $sch['title'] = $sch['game_titel'];
    }
    foreach ($events as &$ev) {
        $ev['type'] = 'event';
    }

    $items = array_merge($schedules, $events);

    // Sort op date/time met usort voor efficiency
    usort($items, function($a, $b) {
        $timeA = strtotime($a['date'] . ' ' . $a['time']);
        $timeB = strtotime($b['date'] . ' ' . $b['time']);
        return $timeA <=> $timeB;
    });

    return $items;
}

// Update last_activity bij elke request (roep aan in header of index)
function updateLastActivity($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Check if user is logged in, redirect if not
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    checkSessionTimeout();
    updateLastActivity($_SESSION['user_id']);
}

// Functie voor zoeken gebruikers (voor AJAX in add_friend)
function searchUsers($query, $user_id) {
    global $pdo;
    $query = '%' . $query . '%';
    $stmt = $pdo->prepare("SELECT username, user_id FROM Users WHERE username LIKE :query AND user_id != :user_id LIMIT 10");
    $stmt->bindParam(':query', $query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Functie om vriend te verwijderen
function deleteFriend($user_id, $friend_id) {
    global $pdo;
    try {
        validateInput($friend_id, 'string', 0, 0, '/^\d+$/', 'Ongeldig vriend ID.');

        $stmt = $pdo->prepare("DELETE FROM Friends WHERE user_id = :user_id AND friend_user_id = :friend_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':friend_id', $friend_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            throw new Exception("Geen rechten om dit te verwijderen of niet gevonden.");
        }

        logAction('Vriend verwijderd', "User $user_id verwijderde vriend $friend_id");
        return true;
    } catch (Exception $e) {
        logAction('Vriend verwijderen fout', $e->getMessage());
        throw $e;
    }
}
?>