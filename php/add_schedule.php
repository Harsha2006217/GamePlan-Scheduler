<?php
/**
 * GamePlan Scheduler - Enhanced Professional Schedule Creation Page
 * Advanced Gaming Schedule Management with Complete Validation and Security
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Schedule Creation
 * 
 * This page implements User Story 3: "Als gamer wil ik speelschema's delen in een kalender,
 * zodat ik met vrienden kan afspreken om te gamen."
 */

require_once 'includes/functions.php';
require_once 'includes/security.php';

// Enhanced session and login verification
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Initialize variables for form processing
$user_id = $_SESSION['user_id'];
$message = '';
$errors = [];
$success = false;
$form_data = [];

// Get required data for form dropdowns with enhanced error handling
try {
    $games = getGames();
    $friends = getFriends($user_id);
    $user_games = getUserGames($user_id); // For personalized game suggestions
    
    if (empty($games)) {
        $errors[] = "Geen games beschikbaar. Neem contact op met de beheerder.";
    }
} catch (Exception $e) {
    error_log('Error loading schedule form data: ' . $e->getMessage());
    $errors[] = "Er is een fout opgetreden bij het laden van de formuliergegevens.";
}

// Advanced form processing with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Enhanced CSRF protection
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new SecurityException("Ongeldige form submission. Herlaad de pagina en probeer opnieuw.");
        }
        
        // Sanitize and validate input data
        $game_id = filter_input(INPUT_POST, 'game_id', FILTER_VALIDATE_INT);
        $title = trim($_POST['title'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT);
        $friends_selected = array_filter($_POST['friends'] ?? [], function($id) {
            return filter_var($id, FILTER_VALIDATE_INT) !== false;
        });
        $reminder = $_POST['reminder'] ?? '';
        $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
        $recurring_pattern = $is_recurring ? ($_POST['recurring_pattern'] ?? '') : '';
        
        // Store form data for repopulation on error
        $form_data = [
            'game_id' => $game_id,
            'title' => $title,
            'date' => $date,
            'time' => $time,
            'end_time' => $end_time,
            'description' => $description,
            'max_participants' => $max_participants,
            'friends' => $friends_selected,
            'reminder' => $reminder,
            'is_recurring' => $is_recurring,
            'recurring_pattern' => $recurring_pattern
        ];
        
        // Comprehensive validation
        
        // Game validation
        if (!$game_id) {
            $errors[] = "Selecteer een geldige game.";
        } else {
            $selected_game = getGameById($game_id);
            if (!$selected_game) {
                $errors[] = "De geselecteerde game bestaat niet.";
            }
        }
        
        // Title validation (addressing test issue #1001)
        if (empty($title)) {
            // Use game name as default title if not provided
            if ($game_id && isset($selected_game)) {
                $title = $selected_game['titel'] . " Gaming Sessie";
                $form_data['title'] = $title;
            } else {
                $errors[] = "Schema titel is verplicht.";
            }
        } elseif (preg_match('/^\s+$/', $title)) {
            $errors[] = "Schema titel mag niet alleen uit spaties bestaan.";
        } elseif (strlen($title) > 100) {
            $errors[] = "Schema titel mag maximaal 100 karakters bevatten.";
        }
        
        // Date validation (addressing test issue #1004)
        if (empty($date)) {
            $errors[] = "Datum is verplicht.";
        } else {
            $schedule_date = DateTime::createFromFormat('Y-m-d', $date);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            
            if (!$schedule_date) {
                $errors[] = "Ongeldige datum. Gebruik het formaat YYYY-MM-DD.";
            } elseif ($schedule_date < $today) {
                $errors[] = "Datum moet in de toekomst liggen.";
            } elseif ($schedule_date > $today->add(new DateInterval('P1Y'))) {
                $errors[] = "Datum mag niet meer dan een jaar in de toekomst liggen.";
            }
        }
        
        // Time validation
        if (empty($time)) {
            $errors[] = "Tijd is verplicht.";
        } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            $errors[] = "Voer een geldige tijd in (HH:MM formaat).";
        } elseif (preg_match('/^-/', $time)) {
            $errors[] = "Tijd mag niet negatief zijn.";
        }
        
        // End time validation
        if (!empty($end_time)) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
                $errors[] = "Voer een geldige eindtijd in (HH:MM formaat).";
            } elseif (!empty($time) && strtotime($end_time) <= strtotime($time)) {
                $errors[] = "Eindtijd moet na de starttijd liggen.";
            }
        }
        
        // Description validation
        if (strlen($description) > 500) {
            $errors[] = "Beschrijving mag maximaal 500 karakters bevatten.";
        }
        
        // Max participants validation
        if ($max_participants && ($max_participants < 1 || $max_participants > 50)) {
            $errors[] = "Maximum aantal deelnemers moet tussen 1 en 50 liggen.";
        }
        
        // Friends validation
        if (!empty($friends_selected)) {
            // Check if selected friends exist and user has permission
            $valid_friends = [];
            $user_friends = array_column($friends, 'user_id');
            
            foreach ($friends_selected as $friend_id) {
                if (in_array($friend_id, $user_friends)) {
                    $valid_friends[] = $friend_id;
                } else {
                    $errors[] = "Een van de geselecteerde vrienden is niet geldig.";
                    break;
                }
            }
            $friends_selected = $valid_friends;
            
            // Check game player limits
            if (isset($selected_game) && $selected_game['max_players']) {
                $total_players = count($friends_selected) + 1; // +1 for current user
                if ($total_players > $selected_game['max_players']) {
                    $errors[] = "Te veel spelers geselecteerd. {$selected_game['titel']} ondersteunt maximaal {$selected_game['max_players']} spelers.";
                }
            }
        }
        
        // Recurring pattern validation
        if ($is_recurring && empty($recurring_pattern)) {
            $errors[] = "Selecteer een herhalingspatroon voor terugkerende schema's.";
        }
        
        // Advanced schedule conflict checking
        if (empty($errors) && !empty($date) && !empty($time)) {
            $conflicts = checkAdvancedScheduleConflicts($user_id, $date, $time, $end_time, $friends_selected, $game_id);
            
            if (!empty($conflicts)) {
                if (isset($conflicts['user_conflict'])) {
                    $errors[] = "Je hebt al een schema dat overlapt met dit tijdstip: '{$conflicts['user_conflict']['title']}' op {$conflicts['user_conflict']['date']} om {$conflicts['user_conflict']['time']}. ";
                }
                
                if (isset($conflicts['friend_conflicts']) && !empty($conflicts['friend_conflicts'])) {
                    foreach ($conflicts['friend_conflicts'] as $friend_conflict) {
                        $errors[] = "Je vriend {$friend_conflict['username']} heeft al een schema dat overlapt: '{$friend_conflict['title']}' op {$friend_conflict['date']} om {$friend_conflict['time']}. ";
                    }
                }
                
                if (isset($conflicts['game_conflicts']) && !empty($conflicts['game_conflicts'])) {
                    $errors[] = "Er zijn al {$conflicts['game_conflicts']['count']} andere schema's voor {$selected_game['titel']} rond dit tijdstip. Overweeg een ander tijdstip.";
                }
            }
        }
        
        // Process form if no validation errors
        if (empty($errors)) {
            // Begin database transaction for data consistency
            beginTransaction();
            
            try {
                // Add the schedule with enhanced data
                $schedule_id = addAdvancedSchedule(
                    $user_id,
                    $game_id,
                    $title,
                    $date,
                    $time,
                    $end_time,
                    $description,
                    $friends_selected,
                    $max_participants,
                    $reminder,
                    $is_recurring,
                    $recurring_pattern
                );
                
                if (!$schedule_id) {
                    throw new DatabaseException("Fout bij het opslaan van het schema.");
                }
                
                // Send notifications to selected friends
                if (!empty($friends_selected)) {
                    foreach ($friends_selected as $friend_id) {
                        $notification_message = "Je bent uitgenodigd voor '{$title}' op " . formatDate($date) . " om " . formatTime($time);
                        if (!empty($description)) {
                            $notification_message .= "\n\nBeschrijving: " . substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
                        }
                        
                        createNotification(
                            $friend_id,
                            'Nieuwe Schema Uitnodiging',
                            $notification_message,
                            'schedule_invite',
                            $schedule_id
                        );
                    }
                }
                
                // Create reminder if specified
                if (!empty($reminder)) {
                    createScheduleReminder($schedule_id, $reminder, $date, $time);
                }
                
                // Handle recurring schedules
                if ($is_recurring && !empty($recurring_pattern)) {
                    createRecurringSchedules($schedule_id, $recurring_pattern, $date, 4); // Create next 4 occurrences
                }
                
                // Log activity for security audit
                logUserActivity($user_id, 'schedule_created', "Created schedule: {$title} for {$date} {$time}");
                
                // Commit transaction
                commitTransaction();
                
                $success = true;
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Schema succesvol aangemaakt!</strong><br>
                    Je gaming sessie "<strong>' . htmlspecialchars($title) . '</strong>" is gepland voor ' . formatDate($date) . ' om ' . formatTime($time) . '. ';
                
                if (!empty($friends_selected)) {
                    $message .= '<br><small class="text-success">✓ Uitnodigingen verzonden naar ' . count($friends_selected) . ' vriend(en).</small>';
                }
                
                if (!empty($reminder)) {
                    $message .= '<br><small class="text-info">🔔 Herinnering ingesteld: ' . $reminder . '.</small>';
                }
                
                $message .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                
                // Clear form data on success
                $form_data = [];
                
            } catch (Exception $e) {
                rollbackTransaction();
                error_log('Error creating schedule: ' . $e->getMessage());
                $errors[] = "Er is een onverwachte fout opgetreden bij het aanmaken van het schema. Probeer het opnieuw.";
            }
        }
        
    } catch (SecurityException $e) {
        $errors[] = $e->getMessage();
    } catch (Exception $e) {
        error_log('Schedule creation error: ' . $e->getMessage());
        $errors[] = "Er is een onverwachte fout opgetreden. Probeer het later opnieuw.";
    }
    
    // Format error messages
    if (!empty($errors)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Formulier bevat fouten:</strong>
            <ul class="mb-0 mt-2">';
        foreach ($errors as $error) {
            $message .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $message .= '</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// Helper function to check if option should be selected
function isSelected($value, $form_value) {
    return $value == $form_value ? 'selected' : '';
}

function isChecked($value, $form_array) {
    return in_array($value, $form_array) ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema Toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <meta name="description" content="Maak een nieuw gaming schema aan en nodig vrienden uit">
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="bg-dark text-white">
    <!-- Enhanced Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-gaming sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-controller me-2"></i>GamePlan Scheduler
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house-fill me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">
                            <i class="bi bi-calendar-week me-1"></i>Schema's
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="add_schedule.php">
                            <i class="bi bi-calendar-plus me-1"></i>Schema Toevoegen
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end bg-dark">
                            <li><a class="dropdown-item text-white" href="profile.php">Profiel</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-white" href="logout.php">Uitloggen</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="text-gaming-blue mb-1">
                            <i class="bi bi-calendar-plus me-2"></i>Nieuw Gaming Schema
                        </h1>
                        <p class="text-muted mb-0">Plan een gaming sessie en nodig vrienden uit</p>
                    </div>
                    <div class="text-end d-none d-md-block">
                        <div class="text-muted small">
                            <i class="bi bi-clock me-1"></i>Huidige tijd: <span id="currentTime"></span>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-calendar-date me-1"></i>Vandaag: <?php echo formatDate(date('Y-m-d')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Form Card -->
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="card bg-dark border-gaming shadow-lg">
                    <div class="card-header bg-gradient-gaming">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-plus-circle me-2"></i>Schema Gegevens
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="scheduleForm" class="needs-validation" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Game Selection -->
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <label for="game_id" class="form-label required">
                                        <i class="bi bi-joystick me-1"></i>Game
                                    </label>
                                    <select id="game_id" name="game_id" class="form-select form-select-lg" required>
                                        <option value="">Selecteer een game...</option>
                                        <?php if (!empty($user_games)): ?>
                                            <optgroup label="Jouw Favoriete Games">
                                                <?php foreach ($user_games as $game): ?>
                                                    <option value="<?php echo $game['game_id']; ?>" 
                                                            <?php echo isSelected($game['game_id'], $form_data['game_id'] ?? ''); ?>
                                                            data-max-players="<?php echo $game['max_players'] ?? 0; ?>"
                                                            data-genre="<?php echo htmlspecialchars($game['genre'] ?? ''); ?>"
                                                            data-session-time="<?php echo $game['average_session_time'] ?? 0; ?>">
                                                        <?php echo htmlspecialchars($game['titel']); ?>
                                                        <?php if (!empty($game['genre'])): ?>
                                                            <small>(<?php echo htmlspecialchars($game['genre']); ?>)</small>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                        <optgroup label="Alle Games">
                                            <?php foreach ($games as $game): ?>
                                                <?php if (empty($user_games) || !in_array($game['game_id'], array_column($user_games, 'game_id'))): ?>
                                                    <option value="<?php echo $game['game_id']; ?>" 
                                                            <?php echo isSelected($game['game_id'], $form_data['game_id'] ?? ''); ?>
                                                            data-max-players="<?php echo $game['max_players'] ?? 0; ?>"
                                                            data-genre="<?php echo htmlspecialchars($game['genre'] ?? ''); ?>"
                                                            data-session-time="<?php echo $game['average_session_time'] ?? 0; ?>">
                                                        <?php echo htmlspecialchars($game['titel']); ?>
                                                        <?php if (!empty($game['genre'])): ?>
                                                            <small>(<?php echo htmlspecialchars($game['genre']); ?>)</small>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Kies de game die je wilt spelen
                                    </div>
                                    <div class="invalid-feedback">
                                        Selecteer een game voor je schema.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="max_participants" class="form-label">
                                        <i class="bi bi-people me-1"></i>Max Spelers
                                    </label>
                                    <input type="number" id="max_participants" name="max_participants" 
                                           class="form-control" min="1" max="50" 
                                           value="<?php echo htmlspecialchars($form_data['max_participants'] ?? ''); ?>"
                                           placeholder="Automatisch">
                                    <div class="form-text">
                                        <span id="gameMaxPlayers">Wordt automatisch ingesteld</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Title -->
                            <div class="mb-4">
                                <label for="title" class="form-label">
                                    <i class="bi bi-card-heading me-1"></i>Schema Titel
                                </label>
                                <input type="text" id="title" name="title" class="form-control" 
                                       maxlength="100" placeholder="Bijv. Competitieve Avond, Casual Gaming..."
                                       value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>">
                                <div class="form-text">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    Laat leeg om automatisch een titel te genereren gebaseerd op de geselecteerde game
                                </div>
                                <div class="invalid-feedback">
                                    Titel mag niet alleen uit spaties bestaan en moet minder dan 100 karakters zijn.
                                </div>
                            </div>

                            <!-- Date and Time -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="date" class="form-label required">
                                        <i class="bi bi-calendar3 me-1"></i>Datum
                                    </label>
                                    <input type="date" id="date" name="date" class="form-control" 
                                           required min="<?php echo date('Y-m-d'); ?>" 
                                           max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>"
                                           value="<?php echo htmlspecialchars($form_data['date'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Selecteer een geldige datum in de toekomst.
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="time" class="form-label required">
                                        <i class="bi bi-clock me-1"></i>Starttijd
                                    </label>
                                    <input type="time" id="time" name="time" class="form-control" 
                                           required value="<?php echo htmlspecialchars($form_data['time'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Voer een geldige starttijd in.
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="end_time" class="form-label">
                                        <i class="bi bi-clock-history me-1"></i>Eindtijd
                                    </label>
                                    <input type="time" id="end_time" name="end_time" class="form-control"
                                           value="<?php echo htmlspecialchars($form_data['end_time'] ?? ''); ?>">
                                    <div class="form-text">Optioneel</div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label for="description" class="form-label">
                                    <i class="bi bi-file-text me-1"></i>Beschrijving
                                </label>
                                <textarea id="description" name="description" class="form-control" 
                                          rows="3" maxlength="500" 
                                          placeholder="Beschrijf je gaming sessie, regels, doelen, etc..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    <span id="descriptionCount">0</span>/500 karakters gebruikt
                                </div>
                            </div>

                            <!-- Friends Selection -->
                            <?php if (!empty($friends)): ?>
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-people me-1"></i>Vrienden Uitnodigen
                                    </label>
                                    <div class="card bg-secondary bg-opacity-25 border-0">
                                        <div class="card-header bg-transparent border-0 pb-2">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <small class="text-muted">Selecteer vrienden om uit te nodigen</small>
                                                </div>
                                                <div class="col-auto">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="toggleAllFriends()">
                                                        <i class="bi bi-check-all me-1"></i>Alles Selecteren
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="mb-3">
                                                <input type="text" class="form-control form-control-sm" 
                                                       id="friendSearch" placeholder="Zoek vrienden..."
                                                       onkeyup="filterFriends(this.value)">
                                            </div>
                                            <div class="row" id="friendsList">
                                                <?php foreach ($friends as $friend): ?>
                                                    <?php
                                                    $is_online = (strtotime($friend['last_activity']) > time() - 300);
                                                    $status_class = $is_online ? 'text-success' : 'text-muted';
                                                    $status_text = $is_online ? 'Online' : 'Offline';
                                                    ?>
                                                    <div class="col-md-6 col-lg-4 mb-2 friend-item">
                                                        <div class="form-check bg-dark bg-opacity-25 rounded p-2">
                                                            <input type="checkbox" name="friends[]" 
                                                                   value="<?php echo $friend['user_id']; ?>" 
                                                                   class="form-check-input friend-checkbox"
                                                                   <?php echo isChecked($friend['user_id'], $form_data['friends'] ?? []); ?>
                                                                   id="friend_<?php echo $friend['user_id']; ?>">
                                                            <label class="form-check-label w-100" 
                                                                   for="friend_<?php echo $friend['user_id']; ?>">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-bold">
                                                                            <?php echo htmlspecialchars($friend['username']); ?>
                                                                        </div>
                                                                        <small class="<?php echo $status_class; ?>">
                                                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i>
                                                                            <?php echo $status_text; ?>
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="text-center mt-3">
                                                <small class="text-muted">
                                                    <span id="selectedFriendsCount">0</span> vriend(en) geselecteerd
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-4">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Je hebt nog geen vrienden toegevoegd. 
                                        <a href="add_friend.php" class="alert-link">Voeg vrienden toe</a> 
                                        om ze uit te nodigen voor je gaming sessies.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Reminder Settings -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="reminder" class="form-label">
                                        <i class="bi bi-bell me-1"></i>Herinnering
                                    </label>
                                    <select id="reminder" name="reminder" class="form-select">
                                        <option value="">Geen herinnering</option>
                                        <option value="15 minuten ervoor" <?php echo isSelected('15 minuten ervoor', $form_data['reminder'] ?? ''); ?>>15 minuten ervoor</option>
                                        <option value="30 minuten ervoor" <?php echo isSelected('30 minuten ervoor', $form_data['reminder'] ?? ''); ?>>30 minuten ervoor</option>
                                        <option value="1 uur ervoor" <?php echo isSelected('1 uur ervoor', $form_data['reminder'] ?? ''); ?>>1 uur ervoor</option>
                                        <option value="2 uren ervoor" <?php echo isSelected('2 uren ervoor', $form_data['reminder'] ?? ''); ?>>2 uren ervoor</option>
                                        <option value="1 dag ervoor" <?php echo isSelected('1 dag ervoor', $form_data['reminder'] ?? ''); ?>>1 dag ervoor</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="bi bi-arrow-repeat me-1"></i>Herhaling
                                    </label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               id="is_recurring" name="is_recurring" 
                                               <?php echo isset($form_data['is_recurring']) && $form_data['is_recurring'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_recurring">
                                            Terugkerend schema
                                        </label>
                                    </div>
                                    <select id="recurring_pattern" name="recurring_pattern" 
                                            class="form-select mt-2" style="display: none;">
                                        <option value="">Selecteer patroon...</option>
                                        <option value="weekly" <?php echo isSelected('weekly', $form_data['recurring_pattern'] ?? ''); ?>>Wekelijks</option>
                                        <option value="biweekly" <?php echo isSelected('biweekly', $form_data['recurring_pattern'] ?? ''); ?>>Tweewekelijks</option>
                                        <option value="monthly" <?php echo isSelected('monthly', $form_data['recurring_pattern'] ?? ''); ?>>Maandelijks</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="schedules.php" class="btn btn-outline-secondary me-md-2">
                                            <i class="bi bi-x-circle me-1"></i>Annuleren
                                        </a>
                                        <button type="button" class="btn btn-info me-md-2" 
                                                onclick="previewSchedule()">
                                            <i class="bi bi-eye me-1"></i>Voorbeeld
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                            <i class="bi bi-calendar-plus me-1"></i>
                                            Schema Aanmaken
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Help Card -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-secondary bg-opacity-25 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-info">
                            <i class="bi bi-lightbulb me-1"></i>Tips voor het Maken van Schema's
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <small>
                                    <strong>Game Selectie:</strong> Kies games uit je favorieten voor snellere toegang.
                                </small>
                            </div>
                            <div class="col-md-4">
                                <small>
                                    <strong>Timing:</strong> Plan minimaal 30 minuten van tevoren om vrienden de kans te geven zich voor te bereiden.
                                </small>
                            </div>
                            <div class="col-md-4">
                                <small>
                                    <strong>Beschrijving:</strong> Vermeld eventuele vereisten zoals voice chat of specifieke uitrusting.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">
                        <i class="bi bi-eye me-2"></i>Schema Voorbeeld
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="previewContent">
                    <!-- Preview content will be inserted here -->
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
                    <button type="button" class="btn btn-primary" onclick="$('#scheduleForm').submit();">
                        <i class="bi bi-check-lg me-1"></i>Schema Aanmaken
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>

    <!-- Enhanced Page-Specific JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeScheduleForm();
    });

    function initializeScheduleForm() {
        // Initialize form validation
        const form = document.getElementById('scheduleForm');
        const gameSelect = document.getElementById('game_id');
        const maxParticipantsInput = document.getElementById('max_participants');
        const descriptionTextarea = document.getElementById('description');
        const dateInput = document.getElementById('date');
        const timeInput = document.getElementById('time');
        const endTimeInput = document.getElementById('end_time');
        const isRecurringCheckbox = document.getElementById('is_recurring');
        const recurringPatternSelect = document.getElementById('recurring_pattern');

        // Set minimum date to today
        if (dateInput) {
            dateInput.min = new Date().toISOString().split('T')[0];
        }

        // Update current time display
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);

        // Game selection handler
        if (gameSelect) {
            gameSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const maxPlayers = selectedOption.dataset.maxPlayers;
                const sessionTime = selectedOption.dataset.sessionTime;
                const genre = selectedOption.dataset.genre;

                // Update max participants
                if (maxPlayers && maxParticipantsInput) {
                    maxParticipantsInput.value = maxPlayers;
                    maxParticipantsInput.max = maxPlayers;
                }

                // Update game info display
                const gameMaxPlayersSpan = document.getElementById('gameMaxPlayers');
                if (gameMaxPlayersSpan) {
                    if (maxPlayers) {
                        gameMaxPlayersSpan.textContent = `Max ${maxPlayers} spelers`;
                    } else {
                        gameMaxPlayersSpan.textContent = 'Geen limiet';
                    }
                }

                // Auto-suggest end time based on session duration
                if (sessionTime && timeInput.value && !endTimeInput.value) {
                    const startTime = new Date(`2000-01-01T${timeInput.value}`);
                    const endTime = new Date(startTime.getTime() + parseInt(sessionTime) * 60000);
                    endTimeInput.value = endTime.toTimeString().substr(0, 5);
                }

                // Check friend limits
                updateFriendSelectionLimits();
            });
        }

        // Description character count
        if (descriptionTextarea) {
            const updateCount = () => {
                const count = descriptionTextarea.value.length;
                document.getElementById('descriptionCount').textContent = count;
                
                if (count > 450) {
                    document.getElementById('descriptionCount').className = 'text-warning';
                } else if (count > 500) {
                    document.getElementById('descriptionCount').className = 'text-danger';
                } else {
                    document.getElementById('descriptionCount').className = 'text-muted';
                }
            };
            
            descriptionTextarea.addEventListener('input', updateCount);
            updateCount(); // Initial count
        }

        // Time validation
        if (timeInput && endTimeInput) {
            const validateTimes = () => {
                if (timeInput.value && endTimeInput.value) {
                    const startTime = new Date(`2000-01-01T${timeInput.value}`);
                    const endTime = new Date(`2000-01-01T${endTimeInput.value}`);
                    
                    if (endTime <= startTime) {
                        endTimeInput.setCustomValidity('Eindtijd moet na de starttijd liggen');
                        endTimeInput.classList.add('is-invalid');
                    } else {
                        endTimeInput.setCustomValidity('');
                        endTimeInput.classList.remove('is-invalid');
                    }
                }
            };
            
            timeInput.addEventListener('change', validateTimes);
            endTimeInput.addEventListener('change', validateTimes);
        }

        // Recurring schedule toggle
        if (isRecurringCheckbox && recurringPatternSelect) {
            isRecurringCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    recurringPatternSelect.style.display = 'block';
                    recurringPatternSelect.required = true;
                } else {
                    recurringPatternSelect.style.display = 'none';
                    recurringPatternSelect.required = false;
                    recurringPatternSelect.value = '';
                }
            });

            // Initialize state
            if (isRecurringCheckbox.checked) {
                recurringPatternSelect.style.display = 'block';
                recurringPatternSelect.required = true;
            }
        }

        // Friend selection counter
        updateSelectedFriendsCount();
        const friendCheckboxes = document.querySelectorAll('.friend-checkbox');
        friendCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateSelectedFriendsCount();
                updateFriendSelectionLimits();
            });
        });

        // Form validation
        form.addEventListener('submit', function(event) {
            if (!validateScheduleForm()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        // Auto-conflict checking
        if (dateInput && timeInput) {
            const checkConflicts = debounce(async () => {
                await checkScheduleConflicts();
            }, 500);

            dateInput.addEventListener('change', checkConflicts);
            timeInput.addEventListener('change', checkConflicts);
        }
    }

    function updateCurrentTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('nl-NL', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const timeElement = document.getElementById('currentTime');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }

    function filterFriends(searchTerm) {
        const friendItems = document.querySelectorAll('.friend-item');
        const searchLower = searchTerm.toLowerCase();
        
        friendItems.forEach(item => {
            const username = item.querySelector('.fw-bold').textContent.toLowerCase();
            if (username.includes(searchLower)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function toggleAllFriends() {
        const checkboxes = document.querySelectorAll('.friend-checkbox');
        const visibleCheckboxes = Array.from(checkboxes).filter(cb => 
            cb.closest('.friend-item').style.display !== 'none'
        );
        
        const allChecked = visibleCheckboxes.every(cb => cb.checked);
        
        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
        
        updateSelectedFriendsCount();
        updateFriendSelectionLimits();
    }

    function updateSelectedFriendsCount() {
        const checkedBoxes = document.querySelectorAll('.friend-checkbox:checked');
        const countElement = document.getElementById('selectedFriendsCount');
        if (countElement) {
            countElement.textContent = checkedBoxes.length;
        }
    }

    function updateFriendSelectionLimits() {
        const gameSelect = document.getElementById('game_id');
        const selectedOption = gameSelect?.options[gameSelect.selectedIndex];
        const maxPlayers = selectedOption?.dataset.maxPlayers;
        
        if (maxPlayers) {
            const maxFriends = parseInt(maxPlayers) - 1; // -1 for current user
            const checkboxes = document.querySelectorAll('.friend-checkbox');
            const checkedCount = document.querySelectorAll('.friend-checkbox:checked').length;
            
            checkboxes.forEach(checkbox => {
                if (!checkbox.checked && checkedCount >= maxFriends) {
                    checkbox.disabled = true;
                    checkbox.closest('.form-check').classList.add('opacity-50');
                } else {
                    checkbox.disabled = false;
                    checkbox.closest('.form-check').classList.remove('opacity-50');
                }
            });
            
            // Show warning if at limit
            const warningElement = document.getElementById('friendLimitWarning');
            if (checkedCount >= maxFriends) {
                if (!warningElement) {
                    const warning = document.createElement('div');
                    warning.id = 'friendLimitWarning';
                    warning.className = 'alert alert-warning alert-sm mt-2';
                    warning.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i>Maximum aantal spelers bereikt voor deze game (${maxPlayers}).`;
                    document.getElementById('friendsList').parentElement.appendChild(warning);
                }
            } else if (warningElement) {
                warningElement.remove();
            }
        }
    }

    async function checkScheduleConflicts() {
        const gameId = document.getElementById('game_id')?.value;
        const date = document.getElementById('date')?.value;
        const time = document.getElementById('time')?.value;
        const friends = Array.from(document.querySelectorAll('.friend-checkbox:checked')).map(cb => cb.value);
        
        if (!gameId || !date || !time) return;
        
        try {
            const response = await fetch('check_conflicts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('[name="csrf_token"]').value
                },
                body: JSON.stringify({ gameId, date, time, friends })
            });
            
            if (!response.ok) throw new Error('Network error');
            
            const result = await response.json();
            
            // Remove existing conflict warnings
            const existingWarnings = document.querySelectorAll('.conflict-warning');
            existingWarnings.forEach(warning => warning.remove());
            
            if (result.conflicts && result.conflicts.length > 0) {
                const warningDiv = document.createElement('div');
                warningDiv.className = 'alert alert-warning conflict-warning mt-2';
                warningDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Mogelijke conflicten gedetecteerd:</strong>
                    <ul class="mb-0 mt-1">
                        ${result.conflicts.map(conflict => `<li>${conflict}</li>`).join('')}
                    </ul>
                `;
                
                document.getElementById('date').parentElement.appendChild(warningDiv);
            }
            
        } catch (error) {
            console.error('Error checking conflicts:', error);
        }
    }

    function validateScheduleForm() {
        let isValid = true;
        
        // Custom validation beyond HTML5
        const title = document.getElementById('title').value.trim();
        const date = document.getElementById('date').value;
        const time = document.getElementById('time').value;
        const endTime = document.getElementById('end_time').value;
        
        // Title validation (addressing issue #1001)
        if (title && /^\s+$/.test(title)) {
            showFieldError('title', 'Titel mag niet alleen uit spaties bestaan');
            isValid = false;
        }
        
        // Date validation
        if (date) {
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                showFieldError('date', 'Datum moet in de toekomst liggen');
                isValid = false;
            }
        }
        
        // Time validation
        if (time && time.startsWith('-')) {
            showFieldError('time', 'Tijd mag niet negatief zijn');
            isValid = false;
        }
        
        // End time validation
        if (time && endTime) {
            const startTime = new Date(`2000-01-01T${time}`);
            const endTimeObj = new Date(`2000-01-01T${endTime}`);
            
            if (endTimeObj <= startTime) {
                showFieldError('end_time', 'Eindtijd moet na de starttijd liggen');
                isValid = false;
            }
        }
        
        return isValid;
    }

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.setCustomValidity(message);
            field.classList.add('is-invalid');
            
            // Create or update custom feedback
            let feedback = field.parentElement.querySelector('.invalid-feedback.custom');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback custom';
                field.parentElement.appendChild(feedback);
            }
            feedback.textContent = message;
        }
    }

    function previewSchedule() {
        const gameSelect = document.getElementById('game_id');
        const selectedGame = gameSelect.options[gameSelect.selectedIndex];
        const title = document.getElementById('title').value || (selectedGame.text + ' Gaming Sessie');
        const date = document.getElementById('date').value;
        const time = document.getElementById('time').value;
        const endTime = document.getElementById('end_time').value;
        const description = document.getElementById('description').value;
        const reminder = document.getElementById('reminder').value;
        const selectedFriends = Array.from(document.querySelectorAll('.friend-checkbox:checked'))
            .map(cb => cb.nextElementSibling.querySelector('.fw-bold').textContent);
        
        const previewContent = `
            <div class="card bg-secondary bg-opacity-25 border-0">
                <div class="card-header">
                    <h5 class="mb-0">${title}</h5>
                    ${selectedGame.text ? `<small class="text-muted">${selectedGame.text}</small>` : ''}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-calendar3 me-1"></i>Datum:</strong> ${formatDate(date)}</p>
                            <p><strong><i class="bi bi-clock me-1"></i>Tijd:</strong> ${time}${endTime ? ` - ${endTime}` : ''}</p>
                            ${reminder ? `<p><strong><i class="bi bi-bell me-1"></i>Herinnering:</strong> ${reminder}</p>` : ''}
                        </div>
                        <div class="col-md-6">
                            ${selectedFriends.length > 0 ? `
                                <p><strong><i class="bi bi-people me-1"></i>Uitgenodigde vrienden:</strong></p>
                                <ul class="list-unstyled ms-3">
                                    ${selectedFriends.map(friend => `<li>• ${friend}</li>`).join('')}
                                </ul>
                            ` : '<p class="text-muted">Geen vrienden uitgenodigd</p>'}
                        </div>
                    </div>
                    ${description ? `
                        <hr>
                        <p><strong><i class="bi bi-file-text me-1"></i>Beschrijving:</strong></p>
                        <p class="text-muted">${description}</p>
                    ` : ''}
                </div>
            </div>
        `;
        
        document.getElementById('previewContent').innerHTML = previewContent;
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('nl-NL', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    </script>
</body>
</html>