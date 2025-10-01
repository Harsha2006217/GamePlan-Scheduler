<?php
/**
 * GamePlan Scheduler - Enhanced Professional Schedule Edit Page
 * Advanced Gaming Schedule Management with Complete Validation
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Schedule Editor
 */

// Start session and include required files
session_start();

// Include necessary files
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Enhanced security check - ensure user is logged in
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get and validate schedule ID
$schedule_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$schedule_id) {
    header("Location: schedules.php?error=invalid_schedule");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch schedule with ownership verification
try {
    global $pdo;
    
    // Get schedule with game information and ownership check
    $stmt = $pdo->prepare("
        SELECT s.*, g.titel as game_title, g.description as game_description
        FROM Schedules s 
        JOIN Games g ON s.game_id = g.game_id 
        WHERE s.schedule_id = :id AND s.user_id = :user_id
    ");
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        header("Location: schedules.php?error=schedule_not_found");
        exit;
    }
    
} catch (PDOException $e) {
    error_log('Database error in edit_schedule.php: ' . $e->getMessage());
    header("Location: schedules.php?error=database_error");
    exit;
}

// Get all available games for dropdown
$games = getGames();

// Get user's friends for sharing options
$friends = getFriends($user_id);

// Parse currently selected friends
$selected_friends = [];
if (!empty($schedule['friends'])) {
    $friend_usernames = explode(',', $schedule['friends']);
    foreach ($friends as $friend) {
        if (in_array($friend['username'], $friend_usernames)) {
            $selected_friends[] = $friend['user_id'];
        }
    }
}

// Initialize message variables
$message = '';
$message_type = '';
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // CSRF Protection
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = "Ongeldige formulier submission. Vernieuw de pagina en probeer opnieuw.";
        } else {
            // Enhanced input validation with security checks
            $game_id = filter_input(INPUT_POST, 'game_id', FILTER_VALIDATE_INT);
            $title = trim($_POST['title'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $time = trim($_POST['time'] ?? '');
            $end_time = trim($_POST['end_time'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $friends_selected = array_filter($_POST['friends'] ?? [], function($val) {
                return filter_var($val, FILTER_VALIDATE_INT) !== false;
            });
            $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT);
            $location = trim($_POST['location'] ?? 'Online');
            $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
            $recurring_pattern = trim($_POST['recurring_pattern'] ?? '');

            // Comprehensive validation
            
            // Validate game selection
            if (!$game_id) {
                $errors[] = "Selecteer een geldige game.";
            } else {
                // Verify game exists and is active
                $game_exists = false;
                foreach ($games as $game) {
                    if ($game['game_id'] == $game_id) {
                        $game_exists = true;
                        break;
                    }
                }
                if (!$game_exists) {
                    $errors[] = "De geselecteerde game is niet geldig.";
                }
            }

            // Validate title (optional custom title)
            if (!empty($title)) {
                if (strlen($title) < 3) {
                    $errors[] = "Titel moet minimaal 3 karakters bevatten.";
                } elseif (strlen($title) > 100) {
                    $errors[] = "Titel mag maximaal 100 karakters bevatten.";
                } elseif (preg_match('/^\s*$/', $title)) {
                    $errors[] = "Titel mag niet alleen uit spaties bestaan.";
                }
            }

            // Validate date
            if (empty($date)) {
                $errors[] = "Datum is verplicht.";
            } else {
                $date_timestamp = strtotime($date);
                if ($date_timestamp === false) {
                    $errors[] = "Voer een geldige datum in (YYYY-MM-DD formaat).";
                } elseif ($date_timestamp < strtotime('today')) {
                    $errors[] = "Datum moet vandaag of in de toekomst liggen.";
                } elseif ($date_timestamp > strtotime('+2 years')) {
                    $errors[] = "Datum mag niet meer dan 2 jaar in de toekomst liggen.";
                }
            }

            // Validate time
            if (empty($time)) {
                $errors[] = "Tijd is verplicht.";
            } else {
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                    $errors[] = "Voer een geldige tijd in (HH:MM formaat).";
                } elseif (preg_match('/^-/', $time)) {
                    $errors[] = "Tijd mag niet negatief zijn.";
                }
            }

            // Validate end time if provided
            if (!empty($end_time)) {
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
                    $errors[] = "Voer een geldige eindtijd in (HH:MM formaat).";
                } elseif (!empty($time) && strtotime($end_time) <= strtotime($time)) {
                    $errors[] = "Eindtijd moet na de starttijd liggen.";
                }
            }

            // Validate description
            if (!empty($description) && strlen($description) > 500) {
                $errors[] = "Beschrijving mag maximaal 500 karakters bevatten.";
            }

            // Validate location
            if (strlen($location) > 100) {
                $errors[] = "Locatie mag maximaal 100 karakters bevatten.";
            }

            // Validate max participants
            if ($max_participants !== null && ($max_participants < 1 || $max_participants > 100)) {
                $errors[] = "Maximum aantal deelnemers moet tussen 1 en 100 liggen.";
            }

            // Validate selected friends exist and are actual friends
            $valid_friends = [];
            if (!empty($friends_selected)) {
                foreach ($friends_selected as $friend_id) {
                    $friend_exists = false;
                    foreach ($friends as $friend) {
                        if ($friend['user_id'] == $friend_id) {
                            $friend_exists = true;
                            $valid_friends[] = $friend['username'];
                            break;
                        }
                    }
                    if (!$friend_exists) {
                        $errors[] = "Een of meer geselecteerde vrienden zijn niet geldig.";
                        break;
                    }
                }
            }

            // Check for schedule conflicts (excluding current schedule)
            if (empty($errors)) {
                if (hasScheduleConflict($user_id, $date, $time, $schedule_id)) {
                    $errors[] = "Je hebt al een ander schema op dit tijdstip. Kies een ander moment.";
                }
            }

            // Validate recurring pattern if recurring is enabled
            if ($is_recurring && empty($recurring_pattern)) {
                $errors[] = "Selecteer een herhalingspatroon als je een terugkerend schema wilt maken.";
            }

            // Process form if no errors
            if (empty($errors)) {
                try {
                    // Prepare data for update
                    $friends_string = !empty($valid_friends) ? implode(',', $valid_friends) : '';
                    
                    // Update schedule in database
                    $stmt = $pdo->prepare("
                        UPDATE Schedules 
                        SET game_id = :game_id, 
                            title = :title,
                            date = :date, 
                            time = :time, 
                            end_time = :end_time,
                            friends = :friends, 
                            description = :description,
                            max_participants = :max_participants,
                            location = :location,
                            is_recurring = :is_recurring,
                            recurring_pattern = :recurring_pattern,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE schedule_id = :schedule_id AND user_id = :user_id
                    ");
                    
                    $stmt->bindParam(':game_id', $game_id, PDO::PARAM_INT);
                    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                    $stmt->bindParam(':date', $date, PDO::PARAM_STR);
                    $stmt->bindParam(':time', $time, PDO::PARAM_STR);
                    $stmt->bindParam(':end_time', $end_time, PDO::PARAM_STR);
                    $stmt->bindParam(':friends', $friends_string, PDO::PARAM_STR);
                    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                    $stmt->bindParam(':max_participants', $max_participants, PDO::PARAM_INT);
                    $stmt->bindParam(':location', $location, PDO::PARAM_STR);
                    $stmt->bindParam(':is_recurring', $is_recurring, PDO::PARAM_INT);
                    $stmt->bindParam(':recurring_pattern', $recurring_pattern, PDO::PARAM_STR);
                    $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        // Send notifications to newly added friends
                        $original_friends = explode(',', $schedule['friends']);
                        $new_friends = array_diff($valid_friends, $original_friends);
                        
                        if (!empty($new_friends)) {
                            $game_title = '';
                            foreach ($games as $game) {
                                if ($game['game_id'] == $game_id) {
                                    $game_title = $game['titel'];
                                    break;
                                }
                            }
                            
                            foreach ($new_friends as $friend_username) {
                                // Find friend's user_id for notification
                                foreach ($friends as $friend) {
                                    if ($friend['username'] == $friend_username) {
                                        createNotification(
                                            $friend['user_id'],
                                            'Schema Bijgewerkt',
                                            "Je bent toegevoegd aan het bijgewerkte schema: {$game_title} op " . formatDateTime($date, $time),
                                            'schedule_update'
                                        );
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // Set success message and redirect
                        $_SESSION['message'] = "Schema succesvol bijgewerkt!";
                        $_SESSION['message_type'] = "success";
                        header("Location: schedules.php?updated=1");
                        exit;
                        
                    } else {
                        $errors[] = "Er is een fout opgetreden bij het bijwerken van het schema. Probeer opnieuw.";
                    }
                    
                } catch (PDOException $e) {
                    error_log('Database error updating schedule: ' . $e->getMessage());
                    $errors[] = "Database fout. Probeer het later opnieuw.";
                }
            }
        }
        
    } catch (Exception $e) {
        error_log('Unexpected error in edit_schedule.php: ' . $e->getMessage());
        $errors[] = "Een onverwachte fout is opgetreden. Probeer opnieuw.";
    }

    // Prepare message for display
    if (!empty($errors)) {
        $message = '<div class="alert alert-danger"><ul class="mb-0">';
        foreach ($errors as $error) {
            $message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $message .= '</ul></div>';
        $message_type = 'error';
    }
}

// Get current date for minimum date restriction
$min_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema Bewerken - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .edit-schedule-container {
            background: var(--color-surface);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--color-border);
            overflow: hidden;
        }
        
        .edit-schedule-header {
            background: linear-gradient(135deg, var(--color-gaming-blue) 0%, var(--color-primary) 100%);
            color: white;
            padding: var(--spacing-xl);
            position: relative;
            overflow: hidden;
        }
        
        .edit-schedule-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .game-info-card {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }
        
        .friend-selection-container {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-lg);
            max-height: 300px;
            overflow-y: auto;
        }
        
        .friend-item {
            padding: var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            transition: var(--transition-normal);
            margin-bottom: var(--spacing-xs);
        }
        
        .friend-item:hover {
            background: rgba(0, 212, 255, 0.1);
        }
        
        .online-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--color-success);
            display: inline-block;
            margin-left: var(--spacing-sm);
            animation: pulse 2s infinite;
        }
        
        .offline-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--color-secondary);
            display: inline-block;
            margin-left: var(--spacing-sm);
        }
        
        .form-floating .form-control:focus,
        .form-floating .form-select:focus {
            border-color: var(--color-gaming-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 212, 255, 0.25);
        }
        
        .schedule-preview {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }
        
        .recurring-options {
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-lg);
            margin-top: var(--spacing-md);
        }
        
        .advanced-options {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Schedule Edit Form -->
                <div class="edit-schedule-container">
                    <!-- Header -->
                    <div class="edit-schedule-header">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-event-fill me-3" style="font-size: 2rem;"></i>
                            <div>
                                <h2 class="mb-1">Schema Bewerken</h2>
                                <p class="mb-0 opacity-75">
                                    Wijzig je gaming schema en deel het met vrienden
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Content -->
                    <div class="p-4">
                        <!-- Display Messages -->
                        <?php if (!empty($message)): ?>
                            <?php echo $message; ?>
                        <?php endif; ?>
                        
                        <!-- Current Schema Info -->
                        <div class="game-info-card">
                            <h6 class="text-primary mb-2">
                                <i class="bi bi-info-circle me-2"></i>
                                Huidig Schema
                            </h6>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-controller me-2"></i>
                                <strong><?php echo htmlspecialchars($schedule['game_title']); ?></strong>
                                <span class="ms-3 text-muted">
                                    <?php echo htmlspecialchars(formatDateTime($schedule['date'], $schedule['time'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Edit Form -->
                        <form method="POST" id="editScheduleForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <!-- Game Selection -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select id="game_id" name="game_id" class="form-select" required>
                                            <option value="">Kies een game</option>
                                            <?php foreach ($games as $game): ?>
                                                <option value="<?php echo $game['game_id']; ?>" 
                                                        data-max-players="<?php echo htmlspecialchars($game['max_players'] ?? 'Onbeperkt'); ?>"
                                                        data-session-time="<?php echo htmlspecialchars($game['average_session_time'] ?? 'Onbekend'); ?>"
                                                        data-description="<?php echo htmlspecialchars($game['description'] ?? ''); ?>"
                                                        <?php echo ($game['game_id'] == $schedule['game_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($game['titel']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="game_id">
                                            <i class="bi bi-controller me-2"></i>Game *
                                        </label>
                                    </div>
                                    <div class="form-text">Selecteer de game die je wilt spelen</div>
                                </div>
                                
                                <!-- Custom Title -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" id="title" name="title" class="form-control" 
                                               maxlength="100" placeholder="Aangepaste titel"
                                               value="<?php echo htmlspecialchars($schedule['title'] ?? ''); ?>">
                                        <label for="title">
                                            <i class="bi bi-type me-2"></i>Aangepaste Titel
                                        </label>
                                    </div>
                                    <div class="form-text">Optionele aangepaste titel voor dit schema</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <!-- Date -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="date" id="date" name="date" class="form-control" 
                                               required min="<?php echo $min_date; ?>"
                                               value="<?php echo htmlspecialchars($schedule['date']); ?>">
                                        <label for="date">
                                            <i class="bi bi-calendar-date me-2"></i>Datum *
                                        </label>
                                    </div>
                                    <div class="form-text">Datum van de gaming sessie</div>
                                </div>
                                
                                <!-- Start Time -->
                                <div class="col-md-3 mb-3">
                                    <div class="form-floating">
                                        <input type="time" id="time" name="time" class="form-control" 
                                               required value="<?php echo htmlspecialchars($schedule['time']); ?>">
                                        <label for="time">
                                            <i class="bi bi-clock me-2"></i>Starttijd *
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- End Time -->
                                <div class="col-md-3 mb-3">
                                    <div class="form-floating">
                                        <input type="time" id="end_time" name="end_time" class="form-control"
                                               value="<?php echo htmlspecialchars($schedule['end_time'] ?? ''); ?>">
                                        <label for="end_time">
                                            <i class="bi bi-clock-fill me-2"></i>Eindtijd
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-3">
                                <div class="form-floating">
                                    <textarea id="description" name="description" class="form-control" 
                                              style="height: 100px" maxlength="500" 
                                              placeholder="Beschrijf je gaming sessie..."><?php echo htmlspecialchars($schedule['description'] ?? ''); ?></textarea>
                                    <label for="description">
                                        <i class="bi bi-textarea-t me-2"></i>Beschrijving
                                    </label>
                                </div>
                                <div class="form-text">
                                    Beschrijf wat je van plan bent te doen (max. 500 karakters)
                                    <span id="descriptionCount" class="float-end">0/500</span>
                                </div>
                            </div>
                            
                            <div class="row">
                                <!-- Location -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" id="location" name="location" class="form-control" 
                                               maxlength="100" placeholder="Online"
                                               value="<?php echo htmlspecialchars($schedule['location'] ?? 'Online'); ?>">
                                        <label for="location">
                                            <i class="bi bi-geo-alt me-2"></i>Locatie
                                        </label>
                                    </div>
                                    <div class="form-text">Waar de gaming sessie plaatsvindt</div>
                                </div>
                                
                                <!-- Max Participants -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="number" id="max_participants" name="max_participants" 
                                               class="form-control" min="1" max="100"
                                               value="<?php echo htmlspecialchars($schedule['max_participants'] ?? ''); ?>">
                                        <label for="max_participants">
                                            <i class="bi bi-people me-2"></i>Max. Deelnemers
                                        </label>
                                    </div>
                                    <div class="form-text">Maximaal aantal spelers (inclusief jezelf)</div>
                                </div>
                            </div>
                            
                            <!-- Friends Selection -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-people-fill me-2"></i>Vrienden Uitnodigen
                                </label>
                                <div class="friend-selection-container">
                                    <div class="mb-2">
                                        <input type="text" class="form-control form-control-sm" 
                                               id="friendSearch" placeholder="Zoek vrienden..."
                                               onkeyup="filterFriends(this.value)">
                                    </div>
                                    <div id="friendsList">
                                        <?php if (empty($friends)): ?>
                                            <div class="text-muted text-center py-3">
                                                <i class="bi bi-person-plus"></i>
                                                <p class="mb-0">Geen vrienden gevonden.</p>
                                                <a href="add_friend.php" class="btn btn-outline-primary btn-sm mt-2">
                                                    Vrienden Toevoegen
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($friends as $friend): ?>
                                                <?php
                                                $is_online = (strtotime($friend['last_activity']) > (time() - 300));
                                                $is_selected = in_array($friend['user_id'], $selected_friends);
                                                ?>
                                                <div class="form-check friend-item">
                                                    <input type="checkbox" name="friends[]" 
                                                           value="<?php echo $friend['user_id']; ?>" 
                                                           id="friend_<?php echo $friend['user_id']; ?>"
                                                           class="form-check-input"
                                                           <?php echo $is_selected ? 'checked' : ''; ?>>
                                                    <label class="form-check-label d-flex align-items-center w-100" 
                                                           for="friend_<?php echo $friend['user_id']; ?>">
                                                        <span class="flex-grow-1">
                                                            <?php echo htmlspecialchars($friend['username']); ?>
                                                        </span>
                                                        <span class="<?php echo $is_online ? 'online-indicator' : 'offline-indicator'; ?>"
                                                              title="<?php echo $is_online ? 'Online' : 'Offline'; ?>"></span>
                                                        <small class="text-muted ms-2">
                                                            <?php echo $is_online ? 'Online' : 'Offline'; ?>
                                                        </small>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-text">
                                    Selecteer vrienden om uit te nodigen voor deze gaming sessie
                                </div>
                            </div>
                            
                            <!-- Advanced Options -->
                            <div class="advanced-options">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-gear me-2"></i>Geavanceerde Opties
                                </h6>
                                
                                <!-- Recurring Schedule -->
                                <div class="form-check mb-3">
                                    <input type="checkbox" id="is_recurring" name="is_recurring" 
                                           class="form-check-input" value="1"
                                           <?php echo $schedule['is_recurring'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_recurring">
                                        <i class="bi bi-arrow-repeat me-2"></i>
                                        Terugkerend Schema
                                    </label>
                                </div>
                                
                                <!-- Recurring Pattern -->
                                <div id="recurringOptions" class="recurring-options" 
                                     style="display: <?php echo $schedule['is_recurring'] ? 'block' : 'none'; ?>;">
                                    <div class="form-floating">
                                        <select id="recurring_pattern" name="recurring_pattern" class="form-select">
                                            <option value="">Selecteer herhalingspatroon</option>
                                            <option value="daily" <?php echo ($schedule['recurring_pattern'] === 'daily') ? 'selected' : ''; ?>>Dagelijks</option>
                                            <option value="weekly" <?php echo ($schedule['recurring_pattern'] === 'weekly') ? 'selected' : ''; ?>>Wekelijks</option>
                                            <option value="monthly" <?php echo ($schedule['recurring_pattern'] === 'monthly') ? 'selected' : ''; ?>>Maandelijks</option>
                                        </select>
                                        <label for="recurring_pattern">
                                            <i class="bi bi-calendar-range me-2"></i>Herhalingspatroon
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Schema Preview -->
                            <div class="schedule-preview">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-eye me-2"></i>Schema Voorbeeld
                                </h6>
                                <div id="schedulePreview" class="text-muted">
                                    Vul het formulier in om een voorbeeld te zien...
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="schedules.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="bi bi-x-circle me-2"></i>Annuleren
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="bi bi-save me-2"></i>Wijzigingen Opslaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editScheduleForm');
        const gameSelect = document.getElementById('game_id');
        const dateInput = document.getElementById('date');
        const timeInput = document.getElementById('time');
        const endTimeInput = document.getElementById('end_time');
        const descriptionTextarea = document.getElementById('description');
        const descriptionCount = document.getElementById('descriptionCount');
        const recurringCheckbox = document.getElementById('is_recurring');
        const recurringOptions = document.getElementById('recurringOptions');
        const schedulePreview = document.getElementById('schedulePreview');
        const submitBtn = document.getElementById('submitBtn');
        
        // Character counter for description
        function updateDescriptionCount() {
            const length = descriptionTextarea.value.length;
            descriptionCount.textContent = `${length}/500`;
            
            if (length > 450) {
                descriptionCount.className = 'float-end text-warning';
            } else if (length === 500) {
                descriptionCount.className = 'float-end text-danger';
            } else {
                descriptionCount.className = 'float-end';
            }
        }
        
        descriptionTextarea.addEventListener('input', updateDescriptionCount);
        updateDescriptionCount(); // Initial count
        
        // Show/hide recurring options
        recurringCheckbox.addEventListener('change', function() {
            recurringOptions.style.display = this.checked ? 'block' : 'none';
        });
        
        // Update schedule preview
        function updateSchedulePreview() {
            const selectedGame = gameSelect.options[gameSelect.selectedIndex];
            const title = document.getElementById('title').value;
            const date = dateInput.value;
            const time = timeInput.value;
            const endTime = endTimeInput.value;
            const location = document.getElementById('location').value;
            const isRecurring = recurringCheckbox.checked;
            
            if (selectedGame.value && date && time) {
                const gameTitle = selectedGame.text;
                const displayTitle = title || gameTitle;
                const formattedDate = new Date(date).toLocaleDateString('nl-NL', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                let timeRange = time;
                if (endTime) {
                    timeRange += ' - ' + endTime;
                }
                
                let preview = `<strong>${displayTitle}</strong><br>`;
                preview += `📅 ${formattedDate}<br>`;
                preview += `🕐 ${timeRange}<br>`;
                preview += `📍 ${location}<br>`;
                
                if (isRecurring) {
                    const pattern = document.getElementById('recurring_pattern').value;
                    if (pattern) {
                        const patternText = {
                            'daily': 'Dagelijks',
                            'weekly': 'Wekelijks',
                            'monthly': 'Maandelijks'
                        };
                        preview += `🔄 ${patternText[pattern]}<br>`;
                    }
                }
                
                const selectedFriends = Array.from(document.querySelectorAll('input[name="friends[]"]:checked'))
                    .map(cb => cb.nextElementSibling.querySelector('span').textContent.trim());
                
                if (selectedFriends.length > 0) {
                    preview += `👥 Met: ${selectedFriends.join(', ')}`;
                }
                
                schedulePreview.innerHTML = preview;
            } else {
                schedulePreview.innerHTML = 'Vul het formulier in om een voorbeeld te zien...';
            }
        }
        
        // Add event listeners for preview updates
        [gameSelect, document.getElementById('title'), dateInput, timeInput, endTimeInput, 
         document.getElementById('location'), recurringCheckbox, document.getElementById('recurring_pattern')]
        .forEach(element => {
            if (element) {
                element.addEventListener('change', updateSchedulePreview);
                element.addEventListener('input', updateSchedulePreview);
            }
        });
        
        // Update preview when friends are selected
        document.querySelectorAll('input[name="friends[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', updateSchedulePreview);
        });
        
        // Initial preview update
        updateSchedulePreview();
        
        // Form validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];
            
            // Validate game selection
            if (!gameSelect.value) {
                errors.push('Selecteer een game.');
                gameSelect.classList.add('is-invalid');
                isValid = false;
            } else {
                gameSelect.classList.remove('is-invalid');
                gameSelect.classList.add('is-valid');
            }
            
            // Validate date
            const selectedDate = new Date(dateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (!dateInput.value) {
                errors.push('Selecteer een datum.');
                dateInput.classList.add('is-invalid');
                isValid = false;
            } else if (selectedDate < today) {
                errors.push('Datum moet vandaag of in de toekomst liggen.');
                dateInput.classList.add('is-invalid');
                isValid = false;
            } else {
                dateInput.classList.remove('is-invalid');
                dateInput.classList.add('is-valid');
            }
            
            // Validate time
            if (!timeInput.value) {
                errors.push('Selecteer een tijd.');
                timeInput.classList.add('is-invalid');
                isValid = false;
            } else if (/^-/.test(timeInput.value)) {
                errors.push('Tijd mag niet negatief zijn.');
                timeInput.classList.add('is-invalid');
                isValid = false;
            } else {
                timeInput.classList.remove('is-invalid');
                timeInput.classList.add('is-valid');
            }
            
            // Validate end time
            if (endTimeInput.value && timeInput.value) {
                const startTime = new Date(`2000-01-01 ${timeInput.value}`);
                const endTime = new Date(`2000-01-01 ${endTimeInput.value}`);
                
                if (endTime <= startTime) {
                    errors.push('Eindtijd moet na de starttijd liggen.');
                    endTimeInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    endTimeInput.classList.remove('is-invalid');
                    endTimeInput.classList.add('is-valid');
                }
            }
            
            // Validate title if provided
            const title = document.getElementById('title').value.trim();
            if (title && /^\s*$/.test(title)) {
                errors.push('Titel mag niet alleen uit spaties bestaan.');
                document.getElementById('title').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate recurring pattern
            if (recurringCheckbox.checked && !document.getElementById('recurring_pattern').value) {
                errors.push('Selecteer een herhalingspatroon voor terugkerende schema\'s.');
                document.getElementById('recurring_pattern').classList.add('is-invalid');
                isValid = false;
            }
            
            // Show errors if any
            if (!isValid) {
                e.preventDefault();
                
                // Create or update error alert
                let errorAlert = document.querySelector('.validation-errors');
                if (!errorAlert) {
                    errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger validation-errors';
                    form.insertBefore(errorAlert, form.firstChild);
                }
                
                errorAlert.innerHTML = `
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Corrigeer de volgende fouten:</h6>
                    <ul class="mb-0">
                        ${errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                `;
                
                // Scroll to top of form
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Focus first invalid field
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            } else {
                // Remove any existing error alerts
                const errorAlert = document.querySelector('.validation-errors');
                if (errorAlert) {
                    errorAlert.remove();
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Opslaan...';
            }
        });
        
        // Real-time validation feedback
        [gameSelect, dateInput, timeInput, endTimeInput].forEach(field => {
            field.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
            
            field.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                }
            });
        });
        
        // Show game info when selected
        gameSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const maxPlayers = selectedOption.dataset.maxPlayers;
            const sessionTime = selectedOption.dataset.sessionTime;
            const description = selectedOption.dataset.description;
            
            // Update max participants placeholder
            const maxParticipantsField = document.getElementById('max_participants');
            if (maxPlayers && maxPlayers !== 'Onbeperkt') {
                maxParticipantsField.max = maxPlayers;
                maxParticipantsField.placeholder = `Max. ${maxPlayers} spelers`;
            }
            
            // Update description placeholder if empty
            if (sessionTime && sessionTime !== 'Onbekend' && !descriptionTextarea.value) {
                descriptionTextarea.placeholder = `Gemiddelde sessieduur: ${sessionTime} minuten. Beschrijf je gaming sessie...`;
            }
        });
    });
    
    // Friend search functionality
    function filterFriends(searchText) {
        const friendItems = document.querySelectorAll('.friend-item');
        searchText = searchText.toLowerCase();
        
        friendItems.forEach(item => {
            const friendName = item.querySelector('label span').textContent.toLowerCase();
            const isVisible = friendName.includes(searchText);
            item.style.display = isVisible ? 'block' : 'none';
        });
    }
    </script>
</body>
</html>