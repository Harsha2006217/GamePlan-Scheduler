<?php
/**
 * GamePlan Scheduler - Enhanced Professional Add Event Page
 * Advanced Event Creation with Comprehensive Validation and UX
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Add Event Implementation
 */

// Enhanced security and session management
session_start();
session_regenerate_id(true);

// Include enhanced functions and database connection
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Enhanced authentication check with session validation
if (!isLoggedIn()) {
    // Log attempted unauthorized access
    error_log('Unauthorized access attempt to add_event.php from IP: ' . $_SERVER['REMOTE_ADDR']);
    header("Location: login.php?error=unauthorized");
    exit;
}

// Get current user information securely
$user_id = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

// Initialize variables with proper defaults
$schedules = [];
$friends = [];
$games = [];
$message = '';
$errors = [];
$success = false;

// Enhanced error handling with try-catch
try {
    // Get user's schedules for linking events
    $schedules = getSchedules($user_id);
    
    // Get user's friends for event sharing
    $friends = getFriends($user_id);
    
    // Get available games for event context
    $games = getGames();
    
} catch (Exception $e) {
    error_log('Database error in add_event.php: ' . $e->getMessage());
    $errors[] = 'Er is een fout opgetreden bij het laden van gegevens. Probeer het later opnieuw.';
}

// Enhanced form processing with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enhanced CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Beveiligingsvalidatie mislukt. Vernieuw de pagina en probeer opnieuw.';
    } else {
        // Advanced input sanitization and validation
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_type = trim($_POST['event_type'] ?? 'casual');
        $date = trim($_POST['date'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $location = trim($_POST['location'] ?? 'Online');
        $max_participants = filter_var($_POST['max_participants'] ?? 0, FILTER_VALIDATE_INT);
        $entry_fee = filter_var($_POST['entry_fee'] ?? 0, FILTER_VALIDATE_FLOAT);
        $prize_pool = filter_var($_POST['prize_pool'] ?? 0, FILTER_VALIDATE_FLOAT);
        $reminder = trim($_POST['reminder'] ?? '');
        $schedule_id = filter_var($_POST['schedule_id'] ?? null, FILTER_VALIDATE_INT);
        $shared_friends = $_POST['shared_friends'] ?? [];
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $registration_required = isset($_POST['registration_required']) ? 1 : 0;
        $external_url = filter_var(trim($_POST['external_url'] ?? ''), FILTER_VALIDATE_URL);
        
        // Enhanced validation with specific error messages
        
        // Title validation (addressing #1001 from test report)
        if (empty($title)) {
            $errors[] = 'Titel is verplicht en mag niet leeg zijn.';
        } elseif (preg_match('/^\s*$/', $title)) {
            $errors[] = 'Titel mag niet alleen uit spaties bestaan.';
        } elseif (strlen($title) > 100) {
            $errors[] = 'Titel mag maximaal 100 karakters bevatten.';
        } elseif (strlen($title) < 3) {
            $errors[] = 'Titel moet minimaal 3 karakters bevatten.';
        }
        
        // Description validation (addressing #1004 from test report)
        if (!empty($description)) {
            if (strlen($description) > 500) {
                $errors[] = 'Beschrijving mag maximaal 500 karakters bevatten.';
            }
            if (preg_match('/^\s*$/', $description)) {
                $description = ''; // Clear whitespace-only descriptions
            }
        }
        
        // Enhanced date validation (addressing #1004 from test report)
        if (empty($date)) {
            $errors[] = 'Datum is verplicht.';
        } else {
            $date_timestamp = strtotime($date);
            if ($date_timestamp === false) {
                $errors[] = 'Ongeldige datum opgegeven. Gebruik het juiste formaat (YYYY-MM-DD).';
            } elseif ($date_timestamp < strtotime('today')) {
                $errors[] = 'Datum moet in de toekomst liggen.';
            } elseif ($date_timestamp > strtotime('+2 years')) {
                $errors[] = 'Datum mag niet meer dan 2 jaar in de toekomst liggen.';
            }
        }
        
        // Enhanced time validation
        if (empty($time)) {
            $errors[] = 'Tijd is verplicht.';
        } elseif (preg_match('/^-/', $time)) {
            $errors[] = 'Tijd mag niet negatief zijn.';
        }
        
        // End date/time validation
        if (!empty($end_date)) {
            $end_date_timestamp = strtotime($end_date);
            if ($end_date_timestamp === false) {
                $errors[] = 'Ongeldige einddatum opgegeven.';
            } elseif ($end_date_timestamp < strtotime($date)) {
                $errors[] = 'Einddatum moet op of na de startdatum liggen.';
            }
        }
        
        if (!empty($end_time) && !empty($end_date) && $end_date === $date) {
            if (strtotime($end_time) <= strtotime($time)) {
                $errors[] = 'Eindtijd moet na de starttijd liggen.';
            }
        }
        
        // Event type validation
        $valid_event_types = ['tournament', 'meetup', 'practice', 'stream', 'competition', 'casual'];
        if (!in_array($event_type, $valid_event_types)) {
            $errors[] = 'Ongeldig evenementtype geselecteerd.';
        }
        
        // Location validation
        if (strlen($location) > 100) {
            $errors[] = 'Locatie mag maximaal 100 karakters bevatten.';
        }
        
        // Numeric field validation
        if ($max_participants !== false && $max_participants < 0) {
            $errors[] = 'Maximum aantal deelnemers moet positief zijn.';
        }
        if ($entry_fee !== false && $entry_fee < 0) {
            $errors[] = 'Inschrijfkosten moeten positief zijn.';
        }
        if ($prize_pool !== false && $prize_pool < 0) {
            $errors[] = 'Prijzenpot moet positief zijn.';
        }
        
        // External URL validation
        if (!empty($_POST['external_url']) && $external_url === false) {
            $errors[] = 'Ongeldige externe URL opgegeven.';
        }
        
        // Schedule validation
        if ($schedule_id !== false && $schedule_id > 0) {
            $schedule_exists = false;
            foreach ($schedules as $schedule) {
                if ($schedule['schedule_id'] == $schedule_id) {
                    $schedule_exists = true;
                    break;
                }
            }
            if (!$schedule_exists) {
                $errors[] = 'Geselecteerd schema bestaat niet of is niet toegankelijk.';
            }
        } else {
            $schedule_id = null;
        }
        
        // Friends validation
        if (!empty($shared_friends)) {
            $valid_friend_ids = array_column($friends, 'user_id');
            foreach ($shared_friends as $friend_id) {
                if (!in_array((int)$friend_id, $valid_friend_ids)) {
                    $errors[] = 'Een of meer geselecteerde vrienden zijn ongeldig.';
                    break;
                }
            }
        }
        
        // Security validation to prevent injection attacks
        $security_patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER)\b)/i',
            '/(UNION|OR|AND)\s+\d+\s*=\s*\d+/i',
            '/[\'\"]\s*(OR|AND)\s+[\'\"]\w+[\'\"]\s*=\s*[\'\"]/i'
        ];
        
        foreach ([$title, $description, $location] as $field) {
            foreach ($security_patterns as $pattern) {
                if (preg_match($pattern, $field)) {
                    $errors[] = 'Verdachte invoer gedetecteerd. Gebruik alleen normale tekens.';
                    break 2;
                }
            }
        }
        
        // If no validation errors, proceed with database insertion
        if (empty($errors)) {
            try {
                // Calculate reminder time if set
                $reminder_time = null;
                if (!empty($reminder) && !empty($date) && !empty($time)) {
                    $event_datetime = strtotime("$date $time");
                    switch ($reminder) {
                        case '15 minutes before':
                            $reminder_time = date('Y-m-d H:i:s', $event_datetime - (15 * 60));
                            break;
                        case '30 minutes before':
                            $reminder_time = date('Y-m-d H:i:s', $event_datetime - (30 * 60));
                            break;
                        case '1 hour before':
                            $reminder_time = date('Y-m-d H:i:s', $event_datetime - (60 * 60));
                            break;
                        case '2 hours before':
                            $reminder_time = date('Y-m-d H:i:s', $event_datetime - (2 * 60 * 60));
                            break;
                        case '1 day before':
                            $reminder_time = date('Y-m-d H:i:s', $event_datetime - (24 * 60 * 60));
                            break;
                    }
                }
                
                // Enhanced event creation with comprehensive data
                $event_id = addEvent(
                    $user_id,
                    $title,
                    $description,
                    $event_type,
                    $date,
                    $time,
                    $end_date,
                    $end_time,
                    $location,
                    $max_participants ?: null,
                    $entry_fee ?: null,
                    $prize_pool ?: null,
                    $reminder,
                    $reminder_time,
                    $schedule_id,
                    $is_public,
                    $registration_required,
                    $external_url ?: null
                );
                
                if ($event_id) {
                    // Add event sharing with friends
                    if (!empty($shared_friends)) {
                        foreach ($shared_friends as $friend_id) {
                            addEventUserMapping($event_id, (int)$friend_id);
                        }
                    }
                    
                    // Set success message
                    $_SESSION['success_message'] = 'Evenement succesvol aangemaakt!';
                    
                    // Log successful event creation
                    error_log("Event created successfully by user $user_id: $title on $date");
                    
                    // Redirect to prevent form resubmission
                    header("Location: events.php?success=1");
                    exit;
                } else {
                    $errors[] = 'Er is een fout opgetreden bij het aanmaken van het evenement. Probeer het opnieuw.';
                }
                
            } catch (Exception $e) {
                error_log('Error creating event: ' . $e->getMessage());
                $errors[] = 'Er is een onverwachte fout opgetreden. Probeer het later opnieuw.';
            }
        }
    }
}

// Generate CSRF token for form security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get current date for form minimum date
$current_date = date('Y-m-d');
$current_time = date('H:i');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nieuw evenement aanmaken - GamePlan Scheduler voor gaming evenementen">
    <meta name="keywords" content="gaming evenement, toernooi, GamePlan Scheduler">
    <title>Evenement Toevoegen - GamePlan Scheduler</title>
    
    <!-- Enhanced Bootstrap and CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Enhanced favicon and mobile optimization -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <meta name="theme-color" content="#0d6efd">
</head>
<body class="bg-dark text-white">
    <!-- Enhanced navigation header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-primary">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../index.php">
                <i class="bi bi-controller me-2"></i>
                GamePlan Scheduler
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-event me-1"></i>Evenementen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">
                            <i class="bi bi-calendar3 me-1"></i>Schema's
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="friends.php">
                            <i class="bi bi-people me-1"></i>Vrienden
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo $username; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person me-2"></i>Profiel
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="bi bi-gear me-2"></i>Instellingen
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Uitloggen
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content container -->
    <div class="container mt-4">
        <!-- Enhanced page header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="display-5 fw-bold text-primary mb-2">
                            <i class="bi bi-calendar-plus me-3"></i>
                            Nieuw Evenement Aanmaken
                        </h1>
                        <p class="lead text-muted">
                            Maak een gaming evenement aan en deel het met je vrienden
                        </p>
                    </div>
                    <div>
                        <a href="events.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Terug naar Evenementen
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error and success messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Er zijn fouten opgetreden:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Enhanced event creation form -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card bg-dark border-primary shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-event me-2"></i>
                            Evenement Details
                        </h5>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" id="addEventForm" class="needs-validation" novalidate>
                            <!-- CSRF Protection -->
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <!-- Basic Event Information -->
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="title" class="form-label fw-bold">
                                        <i class="bi bi-card-text me-2"></i>
                                        Evenement Titel *
                                    </label>
                                    <input type="text" 
                                           id="title" 
                                           name="title" 
                                           class="form-control form-control-lg bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                           required 
                                           maxlength="100"
                                           placeholder="Bijv. Weekend Fortnite Toernooi">
                                    <div class="invalid-feedback">
                                        Voer een geldige titel in (3-100 karakters, geen spaties alleen).
                                    </div>
                                    <div class="form-text">
                                        <small class="text-muted">
                                            <span id="titleCounter">0</span>/100 karakters
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="event_type" class="form-label fw-bold">
                                        <i class="bi bi-tags me-2"></i>
                                        Type Evenement *
                                    </label>
                                    <select id="event_type" 
                                            name="event_type" 
                                            class="form-select bg-dark text-white border-secondary" 
                                            required>
                                        <option value="casual" <?php echo ($event_type ?? 'casual') === 'casual' ? 'selected' : ''; ?>>
                                            🎮 Casual Gaming
                                        </option>
                                        <option value="tournament" <?php echo ($event_type ?? '') === 'tournament' ? 'selected' : ''; ?>>
                                            🏆 Toernooi
                                        </option>
                                        <option value="practice" <?php echo ($event_type ?? '') === 'practice' ? 'selected' : ''; ?>>
                                            💪 Team Training
                                        </option>
                                        <option value="competition" <?php echo ($event_type ?? '') === 'competition' ? 'selected' : ''; ?>>
                                            ⚔️ Competitie
                                        </option>
                                        <option value="stream" <?php echo ($event_type ?? '') === 'stream' ? 'selected' : ''; ?>>
                                            📺 Livestream
                                        </option>
                                        <option value="meetup" <?php echo ($event_type ?? '') === 'meetup' ? 'selected' : ''; ?>>
                                            👥 Meet-up
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Date and Time -->
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="date" class="form-label fw-bold">
                                        <i class="bi bi-calendar3 me-2"></i>
                                        Datum *
                                    </label>
                                    <input type="date" 
                                           id="date" 
                                           name="date" 
                                           class="form-control bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($date ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                           min="<?php echo $current_date; ?>"
                                           max="<?php echo date('Y-m-d', strtotime('+2 years')); ?>"
                                           required>
                                    <div class="invalid-feedback">
                                        Selecteer een geldige datum in de toekomst.
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="time" class="form-label fw-bold">
                                        <i class="bi bi-clock me-2"></i>
                                        Starttijd *
                                    </label>
                                    <input type="time" 
                                           id="time" 
                                           name="time" 
                                           class="form-control bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($time ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                           required>
                                    <div class="invalid-feedback">
                                        Voer een geldige tijd in.
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="end_date" class="form-label fw-bold">
                                        <i class="bi bi-calendar-x me-2"></i>
                                        Einddatum
                                    </label>
                                    <input type="date" 
                                           id="end_date" 
                                           name="end_date" 
                                           class="form-control bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($end_date ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                           min="<?php echo $current_date; ?>">
                                    <div class="form-text">
                                        <small class="text-muted">Optioneel voor meerdaagse evenementen</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="end_time" class="form-label fw-bold">
                                        <i class="bi bi-clock-history me-2"></i>
                                        Eindtijd
                                    </label>
                                    <input type="time" 
                                           id="end_time" 
                                           name="end_time" 
                                           class="form-control bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($end_time ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">
                                        <small class="text-muted">Optioneel voor tijdsduur</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">
                                    <i class="bi bi-card-text me-2"></i>
                                    Beschrijving
                                </label>
                                <textarea id="description" 
                                          name="description" 
                                          class="form-control bg-dark text-white border-secondary" 
                                          rows="4" 
                                          maxlength="500"
                                          placeholder="Beschrijf je evenement, regels, prijzen, etc..."><?php echo htmlspecialchars($description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <div class="form-text">
                                    <small class="text-muted">
                                        <span id="descriptionCounter">0</span>/500 karakters
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Location and Participants -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label fw-bold">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        Locatie
                                    </label>
                                    <input type="text" 
                                           id="location" 
                                           name="location" 
                                           class="form-control bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($location ?? 'Online', ENT_QUOTES, 'UTF-8'); ?>"
                                           maxlength="100"
                                           placeholder="Online, Discord Server, Gaming Cafe, etc.">
                                    <div class="form-text">
                                        <small class="text-muted">Waar vindt het evenement plaats?</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="max_participants" class="form-label fw-bold">
                                        <i class="bi bi-people me-2"></i>
                                        Maximum Deelnemers
                                    </label>
                                    <input type="number" 
                                           id="max_participants" 
                                           name="max_participants" 
                                           class="form-control bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($max_participants ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                           min="1" 
                                           max="1000"
                                           placeholder="Bijv. 16">
                                    <div class="form-text">
                                        <small class="text-muted">Laat leeg voor onbeperkt</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Financial Information -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="entry_fee" class="form-label fw-bold">
                                        <i class="bi bi-currency-euro me-2"></i>
                                        Inschrijfkosten (€)
                                    </label>
                                    <input type="number" 
                                           id="entry_fee" 
                                           name="entry_fee" 
                                           class="form-control bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($entry_fee ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                           min="0" 
                                           step="0.01"
                                           placeholder="0.00">
                                    <div class="form-text">
                                        <small class="text-muted">Kosten per deelnemer</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="prize_pool" class="form-label fw-bold">
                                        <i class="bi bi-trophy me-2"></i>
                                        Prijzenpot (€)
                                    </label>
                                    <input type="number" 
                                           id="prize_pool" 
                                           name="prize_pool" 
                                           class="form-control bg-dark text-white border-secondary" 
                                           value="<?php echo htmlspecialchars($prize_pool ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                           min="0" 
                                           step="0.01"
                                           placeholder="0.00">
                                    <div class="form-text">
                                        <small class="text-muted">Totale prijzenpot voor winnaars</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- External URL -->
                            <div class="mb-3">
                                <label for="external_url" class="form-label fw-bold">
                                    <i class="bi bi-link-45deg me-2"></i>
                                    Externe Link
                                </label>
                                <input type="url" 
                                       id="external_url" 
                                       name="external_url" 
                                       class="form-control bg-dark text-white border-secondary" 
                                       value="<?php echo htmlspecialchars($external_url ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="https://discord.gg/jouwserver of https://twitch.tv/jouwkanaal">
                                <div class="form-text">
                                    <small class="text-muted">Link naar Discord, Twitch, website, etc.</small>
                                </div>
                            </div>
                            
                            <!-- Reminder Settings -->
                            <div class="mb-3">
                                <label for="reminder" class="form-label fw-bold">
                                    <i class="bi bi-bell me-2"></i>
                                    Herinnering
                                </label>
                                <select id="reminder" 
                                        name="reminder" 
                                        class="form-select bg-dark text-white border-secondary">
                                    <option value="">Geen herinnering</option>
                                    <option value="15 minutes before" <?php echo ($reminder ?? '') === '15 minutes before' ? 'selected' : ''; ?>>
                                        15 minuten van tevoren
                                    </option>
                                    <option value="30 minutes before" <?php echo ($reminder ?? '') === '30 minutes before' ? 'selected' : ''; ?>>
                                        30 minuten van tevoren
                                    </option>
                                    <option value="1 hour before" <?php echo ($reminder ?? '') === '1 hour before' ? 'selected' : ''; ?>>
                                        1 uur van tevoren
                                    </option>
                                    <option value="2 hours before" <?php echo ($reminder ?? '') === '2 hours before' ? 'selected' : ''; ?>>
                                        2 uur van tevoren
                                    </option>
                                    <option value="1 day before" <?php echo ($reminder ?? '') === '1 day before' ? 'selected' : ''; ?>>
                                        1 dag van tevoren
                                    </option>
                                </select>
                                <div class="form-text">
                                    <small class="text-muted">Wanneer wil je herinnerd worden?</small>
                                </div>
                            </div>
                            
                            <!-- Schedule Linking -->
                            <?php if (!empty($schedules)): ?>
                            <div class="mb-3">
                                <label for="schedule_id" class="form-label fw-bold">
                                    <i class="bi bi-calendar3 me-2"></i>
                                    Koppel aan Schema (optioneel)
                                </label>
                                <select id="schedule_id" 
                                        name="schedule_id" 
                                        class="form-select bg-dark text-white border-secondary">
                                    <option value="">Geen schema koppeling</option>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <option value="<?php echo (int)$schedule['schedule_id']; ?>" 
                                                <?php echo ($schedule_id ?? 0) == $schedule['schedule_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($schedule['game_titel'] ?? 'Onbekend Spel', ENT_QUOTES, 'UTF-8'); ?> - 
                                            <?php echo htmlspecialchars($schedule['date'], ENT_QUOTES, 'UTF-8'); ?> om 
                                            <?php echo htmlspecialchars($schedule['time'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <small class="text-muted">Koppel dit evenement aan een bestaand schema</small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Event Settings -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_public" 
                                               name="is_public" 
                                               <?php echo ($is_public ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="is_public">
                                            <i class="bi bi-globe me-2"></i>
                                            Publiek evenement
                                        </label>
                                        <div class="form-text">
                                            <small class="text-muted">Anderen kunnen dit evenement vinden</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="registration_required" 
                                               name="registration_required" 
                                               <?php echo ($registration_required ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="registration_required">
                                            <i class="bi bi-clipboard-check me-2"></i>
                                            Inschrijving vereist
                                        </label>
                                        <div class="form-text">
                                            <small class="text-muted">Deelnemers moeten zich vooraf inschrijven</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Friend Sharing -->
                            <?php if (!empty($friends)): ?>
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-share me-2"></i>
                                    Deel met Vrienden (optioneel)
                                </label>
                                <div class="border border-secondary rounded p-3 bg-dark bg-opacity-50">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="selectAllFriends">
                                        <label class="form-check-label fw-bold text-primary" for="selectAllFriends">
                                            Alle vrienden selecteren
                                        </label>
                                    </div>
                                    <hr class="border-secondary">
                                    <div class="row">
                                        <?php foreach ($friends as $friend): ?>
                                            <div class="col-md-6 col-lg-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input friend-checkbox" 
                                                           type="checkbox" 
                                                           name="shared_friends[]" 
                                                           value="<?php echo (int)$friend['user_id']; ?>" 
                                                           id="friend_<?php echo (int)$friend['user_id']; ?>"
                                                           <?php echo in_array($friend['user_id'], $shared_friends ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="friend_<?php echo (int)$friend['user_id']; ?>">
                                                        <i class="bi bi-person-circle me-1"></i>
                                                        <?php echo htmlspecialchars($friend['username'], ENT_QUOTES, 'UTF-8'); ?>
                                                        <?php if (isset($friend['is_online']) && $friend['is_online']): ?>
                                                            <span class="badge bg-success ms-1">Online</span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="form-text">
                                    <small class="text-muted">
                                        Geselecteerde vrienden ontvangen een uitnodiging voor dit evenement
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Form Actions -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="events.php" class="btn btn-outline-secondary btn-lg me-md-2">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Annuleren
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-calendar-plus me-2"></i>
                                    Evenement Aanmaken
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced footer -->
    <footer class="bg-dark border-top border-secondary mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        © 2025 GamePlan Scheduler door Harsha Kanaparthi
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="../privacy.php" class="text-muted text-decoration-none me-3">Privacybeleid</a>
                    <a href="../contact.php" class="text-muted text-decoration-none">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Enhanced JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    
    <script>
    /**
     * Enhanced Add Event Form Validation and UX
     * Addresses issues #1001 and #1004 from test report
     */
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('addEventForm');
        const titleInput = document.getElementById('title');
        const descriptionInput = document.getElementById('description');
        const dateInput = document.getElementById('date');
        const timeInput = document.getElementById('time');
        const endDateInput = document.getElementById('end_date');
        const endTimeInput = document.getElementById('end_time');
        const selectAllCheckbox = document.getElementById('selectAllFriends');
        const friendCheckboxes = document.querySelectorAll('.friend-checkbox');
        
        // Enhanced character counters
        function updateCharacterCounter(input, counterId, maxLength) {
            const counter = document.getElementById(counterId);
            if (counter) {
                const length = input.value.length;
                counter.textContent = length;
                
                if (length > maxLength * 0.8) {
                    counter.className = 'text-warning';
                } else if (length >= maxLength) {
                    counter.className = 'text-danger';
                } else {
                    counter.className = 'text-muted';
                }
            }
        }
        
        // Title character counter and validation
        if (titleInput) {
            titleInput.addEventListener('input', function() {
                updateCharacterCounter(this, 'titleCounter', 100);
                
                // Real-time validation for #1001 fix
                const value = this.value.trim();
                if (value === '' || /^\s*$/.test(value)) {
                    this.setCustomValidity('Titel mag niet leeg zijn of alleen uit spaties bestaan.');
                } else if (value.length < 3) {
                    this.setCustomValidity('Titel moet minimaal 3 karakters bevatten.');
                } else if (value.length > 100) {
                    this.setCustomValidity('Titel mag maximaal 100 karakters bevatten.');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Initial counter update
            updateCharacterCounter(titleInput, 'titleCounter', 100);
        }
        
        // Description character counter
        if (descriptionInput) {
            descriptionInput.addEventListener('input', function() {
                updateCharacterCounter(this, 'descriptionCounter', 500);
            });
            
            // Initial counter update
            updateCharacterCounter(descriptionInput, 'descriptionCounter', 500);
        }
        
        // Enhanced date validation (addressing #1004)
        if (dateInput) {
            dateInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (isNaN(selectedDate.getTime())) {
                    this.setCustomValidity('Ongeldige datum opgegeven.');
                } else if (selectedDate < today) {
                    this.setCustomValidity('Datum moet in de toekomst liggen.');
                } else {
                    this.setCustomValidity('');
                    
                    // Update end date minimum
                    if (endDateInput) {
                        endDateInput.min = this.value;
                    }
                }
            });
        }
        
        // Time validation
        if (timeInput) {
            timeInput.addEventListener('change', function() {
                if (this.value.startsWith('-')) {
                    this.setCustomValidity('Tijd mag niet negatief zijn.');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        // End date and time validation
        if (endDateInput && endTimeInput) {
            function validateEndDateTime() {
                const startDate = dateInput.value;
                const startTime = timeInput.value;
                const endDate = endDateInput.value;
                const endTime = endTimeInput.value;
                
                if (endDate && endTime && startDate && startTime) {
                    const startDateTime = new Date(`${startDate}T${startTime}`);
                    const endDateTime = new Date(`${endDate}T${endTime}`);
                    
                    if (endDateTime <= startDateTime) {
                        endTimeInput.setCustomValidity('Eindtijd moet na de starttijd liggen.');
                    } else {
                        endTimeInput.setCustomValidity('');
                    }
                }
            }
            
            endDateInput.addEventListener('change', validateEndDateTime);
            endTimeInput.addEventListener('change', validateEndDateTime);
        }
        
        // Select all friends functionality
        if (selectAllCheckbox && friendCheckboxes.length > 0) {
            selectAllCheckbox.addEventListener('change', function() {
                friendCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
            
            // Update select all when individual checkboxes change
            friendCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedCount = document.querySelectorAll('.friend-checkbox:checked').length;
                    selectAllCheckbox.checked = checkedCount === friendCheckboxes.length;
                    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < friendCheckboxes.length;
                });
            });
        }
        
        // Enhanced form submission validation
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const errors = [];
            
            // Title validation (addressing #1001)
            const title = titleInput.value.trim();
            if (!title) {
                errors.push('Titel is verplicht.');
                isValid = false;
            } else if (/^\s*$/.test(title)) {
                errors.push('Titel mag niet alleen uit spaties bestaan.');
                isValid = false;
            } else if (title.length < 3) {
                errors.push('Titel moet minimaal 3 karakters bevatten.');
                isValid = false;
            } else if (title.length > 100) {
                errors.push('Titel mag maximaal 100 karakters bevatten.');
                isValid = false;
            }
            
            // Date validation (addressing #1004)
            const dateValue = dateInput.value;
            if (!dateValue) {
                errors.push('Datum is verplicht.');
                isValid = false;
            } else {
                const selectedDate = new Date(dateValue);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (isNaN(selectedDate.getTime())) {
                    errors.push('Ongeldige datum opgegeven.');
                    isValid = false;
                } else if (selectedDate < today) {
                    errors.push('Datum moet in de toekomst liggen.');
                    isValid = false;
                }
            }
            
            // Time validation
            const timeValue = timeInput.value;
            if (!timeValue) {
                errors.push('Tijd is verplicht.');
                isValid = false;
            } else if (timeValue.startsWith('-')) {
                errors.push('Tijd mag niet negatief zijn.');
                isValid = false;
            }
            
            // Description validation (addressing #1004)
            const description = descriptionInput.value.trim();
            if (description.length > 500) {
                errors.push('Beschrijving mag maximaal 500 karakters bevatten.');
                isValid = false;
            }
            
            // Show validation errors
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                
                // Create or update error alert
                let errorAlert = document.querySelector('.alert-danger');
                if (!errorAlert) {
                    errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                    errorAlert.innerHTML = `
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Corrigeer de volgende fouten:</strong>
                        <ul class="mb-0 mt-2" id="errorList"></ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    form.insertBefore(errorAlert, form.firstChild);
                }
                
                const errorList = document.getElementById('errorList');
                errorList.innerHTML = errors.map(error => `<li>${error}</li>`).join('');
                
                // Scroll to top to show errors
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            // Add Bootstrap validation classes
            form.classList.add('was-validated');
        });
        
        // Auto-save functionality (localStorage)
        const formInputs = form.querySelectorAll('input, textarea, select');
        formInputs.forEach(input => {
            // Load saved values
            const savedValue = localStorage.getItem(`addEvent_${input.name}`);
            if (savedValue && !input.value) {
                if (input.type === 'checkbox') {
                    input.checked = savedValue === 'true';
                } else {
                    input.value = savedValue;
                }
            }
            
            // Save values on change
            input.addEventListener('change', function() {
                if (this.type === 'checkbox') {
                    localStorage.setItem(`addEvent_${this.name}`, this.checked);
                } else {
                    localStorage.setItem(`addEvent_${this.name}`, this.value);
                }
            });
        });
        
        // Clear auto-save on successful submission
        form.addEventListener('submit', function() {
            if (form.checkValidity()) {
                formInputs.forEach(input => {
                    localStorage.removeItem(`addEvent_${input.name}`);
                });
            }
        });
        
        // Unsaved changes warning
        let formChanged = false;
        formInputs.forEach(input => {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', function(event) {
            if (formChanged) {
                event.preventDefault();
                event.returnValue = 'Je hebt niet-opgeslagen wijzigingen. Weet je zeker dat je de pagina wilt verlaten?';
                return event.returnValue;
            }
        });
        
        // Reset form changed flag on successful submission
        form.addEventListener('submit', function() {
            if (form.checkValidity()) {
                formChanged = false;
            }
        });
    });
    </script>
</body>
</html>