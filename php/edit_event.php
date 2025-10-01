<?php
/**
 * GamePlan Scheduler - Enhanced Professional Event Editor
 * Advanced Gaming Event Management with Complete Validation and UX
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Event Editor
 */

// Start session and include core functionality
session_start();
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Enhanced security check with user session validation
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?error=session_required");
    exit;
}

// Comprehensive parameter validation and sanitization
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($event_id <= 0) {
    $_SESSION['error_message'] = 'Ongeldig evenement ID opgegeven.';
    header("Location: events.php");
    exit;
}

// Advanced database operations with comprehensive error handling
global $pdo;

try {
    // Fetch event with complete ownership validation
    $stmt = $pdo->prepare("SELECT e.*, s.title as schedule_title 
                          FROM Events e 
                          LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id 
                          WHERE e.event_id = :event_id AND e.user_id = :user_id");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $_SESSION['error_message'] = 'Evenement niet gevonden of u heeft geen toegang tot dit evenement.';
        header("Location: events.php");
        exit;
    }

    // Fetch available schedules for linking
    $schedules = getSchedules($user_id);
    
    // Fetch user's friends for sharing options
    $friends = getFriends($user_id);
    
    // Get currently shared friends for this event
    $shared_friends_ids = [];
    $stmt = $pdo->prepare("SELECT friend_id FROM EventUserMap WHERE event_id = :event_id");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $shared_friends_ids[] = $row['friend_id'];
    }

} catch (PDOException $e) {
    error_log('Database error in edit_event.php: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database fout opgetreden. Probeer het later opnieuw.';
    header("Location: events.php");
    exit;
}

// Initialize variables for form handling
$message = '';
$success = false;

// Advanced form processing with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Extensive input validation and sanitization
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $event_type = $_POST['event_type'] ?? 'casual';
        $location = trim($_POST['location'] ?? 'Online');
        $max_participants = isset($_POST['max_participants']) && $_POST['max_participants'] !== '' ? 
                          (int)$_POST['max_participants'] : null;
        $reminder = $_POST['reminder'] ?? '';
        $reminder_type = $_POST['reminder_type'] ?? 'in-app';
        $schedule_id = $_POST['schedule_id'] ? (int)$_POST['schedule_id'] : null;
        $shared_friends = $_POST['shared_friends'] ?? [];

        // Professional validation implementation with specific error messages
        $validation_errors = [];

        // Title validation with advanced checks
        if (empty($title)) {
            $validation_errors[] = 'Titel is verplicht en mag niet leeg zijn.';
        } elseif (preg_match('/^\s+$/', $title)) {
            $validation_errors[] = 'Titel mag niet alleen uit spaties bestaan.';
        } elseif (strlen($title) > 100) {
            $validation_errors[] = 'Titel mag maximaal 100 karakters bevatten.';
        } elseif (strlen($title) < 3) {
            $validation_errors[] = 'Titel moet minimaal 3 karakters bevatten.';
        }

        // Date validation with comprehensive checks
        if (empty($date)) {
            $validation_errors[] = 'Datum is verplicht.';
        } else {
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
                $validation_errors[] = 'Ongeldige datum formaat.';
            } else {
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                if ($date_obj < $today) {
                    $validation_errors[] = 'Datum moet in de toekomst liggen.';
                }
            }
        }

        // Time validation
        if (empty($time)) {
            $validation_errors[] = 'Tijd is verplicht.';
        } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            $validation_errors[] = 'Ongeldige tijd formaat (gebruik HH:MM).';
        }

        // End time validation (optional but must be after start time if provided)
        if (!empty($end_time)) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
                $validation_errors[] = 'Ongeldige eindtijd formaat (gebruik HH:MM).';
            } elseif (strtotime($end_time) <= strtotime($time)) {
                $validation_errors[] = 'Eindtijd moet na de starttijd zijn.';
            }
        }

        // Description validation
        if (strlen($description) > 500) {
            $validation_errors[] = 'Beschrijving mag maximaal 500 karakters bevatten.';
        }

        // Location validation
        if (strlen($location) > 100) {
            $validation_errors[] = 'Locatie mag maximaal 100 karakters bevatten.';
        }

        // Max participants validation
        if ($max_participants !== null && $max_participants <= 0) {
            $validation_errors[] = 'Maximum aantal deelnemers moet een positief getal zijn.';
        }

        // Event type validation
        $valid_event_types = ['tournament', 'practice', 'competition', 'stream', 'meetup', 'casual'];
        if (!in_array($event_type, $valid_event_types)) {
            $validation_errors[] = 'Ongeldig evenementtype geselecteerd.';
        }

        // Reminder type validation
        $valid_reminder_types = ['in-app', 'email', 'push'];
        if (!in_array($reminder_type, $valid_reminder_types)) {
            $validation_errors[] = 'Ongeldig herinneringstype geselecteerd.';
        }

        // Schedule validation (if linking to existing schedule)
        if ($schedule_id) {
            $stmt = $pdo->prepare("SELECT schedule_id FROM Schedules WHERE schedule_id = :schedule_id AND user_id = :user_id");
            $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                $validation_errors[] = 'Ongeldig schema geselecteerd.';
            }
        }

        // Shared friends validation
        if (!empty($shared_friends)) {
            foreach ($shared_friends as $friend_id) {
                if (!is_numeric($friend_id) || $friend_id == $user_id) {
                    $validation_errors[] = 'Ongeldige vriend geselecteerd voor delen.';
                    break;
                }
            }
        }

        // Security validation against SQL injection patterns
        $security_patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER)\b)/i',
            '/(UNION|OR|AND)\s+\d+\s*=\s*\d+/i',
            '/[\'\"]\s*(OR|AND)\s+[\'\"]\w+[\'\"]\s*=\s*[\'\"]/i'
        ];

        $security_fields = [$title, $description, $location];
        foreach ($security_fields as $field) {
            foreach ($security_patterns as $pattern) {
                if (preg_match($pattern, $field)) {
                    $validation_errors[] = 'Verdachte karakters gedetecteerd in invoer.';
                    break 2;
                }
            }
        }

        // Process form if no validation errors
        if (empty($validation_errors)) {
            $pdo->beginTransaction();

            try {
                // Update event with comprehensive field mapping
                $stmt = $pdo->prepare("UPDATE Events 
                                     SET title = :title, 
                                         date = :date, 
                                         time = :time, 
                                         end_time = :end_time,
                                         description = :description, 
                                         event_type = :event_type,
                                         location = :location,
                                         max_participants = :max_participants,
                                         reminder = :reminder,
                                         reminder_type = :reminder_type,
                                         schedule_id = :schedule_id,
                                         updated_at = CURRENT_TIMESTAMP
                                     WHERE event_id = :event_id AND user_id = :user_id");

                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':date', $date, PDO::PARAM_STR);
                $stmt->bindParam(':time', $time, PDO::PARAM_STR);
                $stmt->bindParam(':end_time', $end_time, PDO::PARAM_STR);
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                $stmt->bindParam(':event_type', $event_type, PDO::PARAM_STR);
                $stmt->bindParam(':location', $location, PDO::PARAM_STR);
                $stmt->bindParam(':max_participants', $max_participants, PDO::PARAM_INT);
                $stmt->bindParam(':reminder', $reminder, PDO::PARAM_STR);
                $stmt->bindParam(':reminder_type', $reminder_type, PDO::PARAM_STR);
                $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
                $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update event');
                }

                // Handle event sharing - remove old shares and add new ones
                $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
                $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                $stmt->execute();

                // Add new shared friends
                if (!empty($shared_friends)) {
                    $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id, invited_at, participation_type) 
                                         VALUES (:event_id, :friend_id, CURRENT_TIMESTAMP, 'participant')");
                    foreach ($shared_friends as $friend_id) {
                        $friend_id = (int)$friend_id;
                        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                        $stmt->bindParam(':friend_id', $friend_id, PDO::PARAM_INT);
                        $stmt->execute();
                    }
                }

                $pdo->commit();
                
                $_SESSION['success_message'] = 'Evenement succesvol bijgewerkt!';
                header("Location: events.php");
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Error updating event: ' . $e->getMessage());
                $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Fout bij het bijwerken van het evenement. Probeer het opnieuw.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><strong>Validatiefouten:</strong><ul class="mb-0 mt-2">';
            foreach ($validation_errors as $error) {
                $message .= '<li>' . htmlspecialchars($error) . '</li>';
            }
            $message .= '</ul></div>';
        }

    } catch (Exception $e) {
        error_log('Exception in edit_event.php: ' . $e->getMessage());
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Een onverwachte fout is opgetreden. Probeer het later opnieuw.</div>';
    }
}

// Update form variables with current data for display
$current_title = $_POST['title'] ?? $event['title'];
$current_date = $_POST['date'] ?? $event['date'];
$current_time = $_POST['time'] ?? $event['time'];
$current_end_time = $_POST['end_time'] ?? $event['end_time'];
$current_description = $_POST['description'] ?? $event['description'];
$current_event_type = $_POST['event_type'] ?? $event['event_type'];
$current_location = $_POST['location'] ?? $event['location'];
$current_max_participants = $_POST['max_participants'] ?? $event['max_participants'];
$current_reminder = $_POST['reminder'] ?? $event['reminder'];
$current_reminder_type = $_POST['reminder_type'] ?? ($event['reminder_type'] ?: 'in-app');
$current_schedule_id = $_POST['schedule_id'] ?? $event['schedule_id'];
$current_shared_friends = $_POST['shared_friends'] ?? $shared_friends_ids;
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bewerk uw gaming evenement in GamePlan Scheduler">
    <meta name="keywords" content="gaming, evenement, bewerken, planning, scheduler">
    <title>Evenement Bewerken - GamePlan Scheduler</title>
    
    <!-- Professional Styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Enhanced Custom Styling for Edit Event -->
    <style>
        /* Advanced Gaming Theme for Event Editor */
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .edit-event-container {
            background: linear-gradient(135deg, #1e1e1e 0%, #2a2a2a 100%);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 212, 255, 0.2);
            border: 2px solid rgba(0, 212, 255, 0.3);
            overflow: hidden;
            margin: 2rem auto;
            max-width: 900px;
        }

        .edit-event-header {
            background: linear-gradient(90deg, #000000 0%, #1a1a1a 100%);
            padding: 2rem;
            border-bottom: 3px solid #00d4ff;
            position: relative;
            overflow: hidden;
        }

        .edit-event-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.1), transparent);
            animation: headerShine 3s ease-in-out infinite;
        }

        @keyframes headerShine {
            0%, 100% { left: -100%; }
            50% { left: 100%; }
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .page-title {
            font-family: 'Orbitron', 'Courier New', monospace;
            font-size: 2.2rem;
            font-weight: 700;
            color: #00d4ff;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.6);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .title-icon {
            background: linear-gradient(135deg, #00d4ff 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            margin-top: 0.5rem;
            font-weight: 400;
        }

        .form-container {
            padding: 2.5rem;
            background: rgba(0, 0, 0, 0.3);
        }

        .form-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 212, 255, 0.2);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            border-color: rgba(0, 212, 255, 0.4);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.1);
        }

        .section-title {
            color: #00d4ff;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label {
            color: #ffffff;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .required-field {
            color: #ff6b6b;
        }

        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(0, 212, 255, 0.3);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #00d4ff;
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: #ffffff;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group-text {
            background: rgba(0, 212, 255, 0.2);
            border: 2px solid rgba(0, 212, 255, 0.3);
            color: #00d4ff;
            font-weight: 500;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00d4ff 0%, #0a58ca 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0a58ca 0%, #084298 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.4);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-secondary {
            background: rgba(108, 117, 125, 0.3);
            border: 2px solid rgba(108, 117, 125, 0.5);
            color: #ffffff;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(108, 117, 125, 0.5);
            border-color: rgba(108, 117, 125, 0.7);
            color: #ffffff;
        }

        .form-check-input:checked {
            background-color: #00d4ff;
            border-color: #00d4ff;
        }

        .form-check-label {
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
        }

        .friends-selection {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 10px;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.2);
        }

        .friends-selection::-webkit-scrollbar {
            width: 6px;
        }

        .friends-selection::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .friends-selection::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #00d4ff 0%, #8b5cf6 100%);
            border-radius: 3px;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2) 0%, rgba(220, 53, 69, 0.1) 100%);
            color: #ff6b6b;
            border-left: 4px solid #dc3545;
        }

        .breadcrumb {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
        }

        .breadcrumb-item a {
            color: #00d4ff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb-item a:hover {
            color: #ffffff;
        }

        .breadcrumb-item.active {
            color: rgba(255, 255, 255, 0.8);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 212, 255, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .edit-event-container {
                margin: 1rem;
                border-radius: 15px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-section {
                padding: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }

        /* Loading and interaction states */
        .loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #00d4ff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Character counter styling */
        .char-counter {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            text-align: right;
            margin-top: 0.25rem;
        }

        .char-counter.warning {
            color: #ffc107;
        }

        .char-counter.danger {
            color: #dc3545;
        }

        /* Enhanced form validation styling */
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .is-valid {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .valid-feedback {
            color: #28a745;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Breadcrumb -->
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../index.php">
                        <i class="fas fa-home me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="events.php">
                        <i class="fas fa-calendar-alt me-1"></i>Evenementen
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-edit me-1"></i>Bewerken
                </li>
            </ol>
        </nav>
    </div>

    <!-- Main Content Container -->
    <div class="container">
        <div class="edit-event-container">
            <!-- Professional Header -->
            <div class="edit-event-header">
                <div class="header-content">
                    <h1 class="page-title">
                        <i class="fas fa-edit title-icon"></i>
                        Evenement Bewerken
                    </h1>
                    <p class="page-subtitle">
                        <i class="fas fa-info-circle me-1"></i>
                        Pas uw gaming evenement aan en deel het met vrienden
                    </p>
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <!-- Display messages -->
                <?php if (!empty($message)): ?>
                    <?php echo $message; ?>
                <?php endif; ?>

                <!-- Main Edit Form -->
                <form method="POST" id="editEventForm" novalidate>
                    <!-- Basic Event Information -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Basis Informatie
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading"></i>
                                    Evenement Titel <span class="required-field">*</span>
                                </label>
                                <input type="text" 
                                       id="title" 
                                       name="title" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($current_title); ?>" 
                                       required 
                                       maxlength="100"
                                       placeholder="Geef uw evenement een spannende titel...">
                                <div class="char-counter" id="titleCounter">
                                    <span id="titleCount"><?php echo strlen($current_title); ?></span>/100 karakters
                                </div>
                                <div class="invalid-feedback">
                                    Titel is verplicht en mag niet alleen uit spaties bestaan.
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="event_type" class="form-label">
                                    <i class="fas fa-tags"></i>
                                    Type Evenement <span class="required-field">*</span>
                                </label>
                                <select id="event_type" name="event_type" class="form-select" required>
                                    <option value="tournament" <?php echo $current_event_type === 'tournament' ? 'selected' : ''; ?>>
                                        🏆 Toernooi
                                    </option>
                                    <option value="practice" <?php echo $current_event_type === 'practice' ? 'selected' : ''; ?>>
                                        🎯 Team Training
                                    </option>
                                    <option value="competition" <?php echo $current_event_type === 'competition' ? 'selected' : ''; ?>>
                                        🥇 Competitie
                                    </option>
                                    <option value="stream" <?php echo $current_event_type === 'stream' ? 'selected' : ''; ?>>
                                        📺 Livestream
                                    </option>
                                    <option value="meetup" <?php echo $current_event_type === 'meetup' ? 'selected' : ''; ?>>
                                        👥 Meet-up
                                    </option>
                                    <option value="casual" <?php echo $current_event_type === 'casual' ? 'selected' : ''; ?>>
                                        🎮 Casual Gaming
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left"></i>
                                Beschrijving
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-control" 
                                      rows="4"
                                      maxlength="500"
                                      placeholder="Beschrijf uw evenement in detail..."><?php echo htmlspecialchars($current_description); ?></textarea>
                            <div class="char-counter" id="descriptionCounter">
                                <span id="descriptionCount"><?php echo strlen($current_description); ?></span>/500 karakters
                            </div>
                        </div>
                    </div>

                    <!-- Date and Time Information -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-clock"></i>
                            Datum & Tijd Planning
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="date" class="form-label">
                                    <i class="fas fa-calendar"></i>
                                    Datum <span class="required-field">*</span>
                                </label>
                                <input type="date" 
                                       id="date" 
                                       name="date" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($current_date); ?>" 
                                       required 
                                       min="<?php echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback">
                                    Selecteer een geldige datum in de toekomst.
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="time" class="form-label">
                                    <i class="fas fa-clock"></i>
                                    Starttijd <span class="required-field">*</span>
                                </label>
                                <input type="time" 
                                       id="time" 
                                       name="time" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($current_time); ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Selecteer een geldige starttijd.
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label">
                                    <i class="fas fa-clock"></i>
                                    Eindtijd (optioneel)
                                </label>
                                <input type="time" 
                                       id="end_time" 
                                       name="end_time" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($current_end_time); ?>">
                                <div class="invalid-feedback">
                                    Eindtijd moet na de starttijd zijn.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location and Participants -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Locatie & Deelnemers
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="location" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Locatie
                                </label>
                                <input type="text" 
                                       id="location" 
                                       name="location" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($current_location); ?>" 
                                       maxlength="100"
                                       placeholder="Online, Discord Server, Gaming Cafe, etc...">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="max_participants" class="form-label">
                                    <i class="fas fa-users"></i>
                                    Max Deelnemers
                                </label>
                                <input type="number" 
                                       id="max_participants" 
                                       name="max_participants" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($current_max_participants); ?>" 
                                       min="1" 
                                       max="1000"
                                       placeholder="Geen limiet">
                                <div class="invalid-feedback">
                                    Voer een geldig aantal deelnemers in (1-1000).
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reminders Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-bell"></i>
                            Herinneringen & Notificaties
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reminder" class="form-label">
                                    <i class="fas fa-bell"></i>
                                    Herinnering
                                </label>
                                <select id="reminder" name="reminder" class="form-select">
                                    <option value="" <?php echo empty($current_reminder) ? 'selected' : ''; ?>>
                                        Geen herinnering
                                    </option>
                                    <option value="15 minutes before" <?php echo $current_reminder === '15 minutes before' ? 'selected' : ''; ?>>
                                        15 minuten van tevoren
                                    </option>
                                    <option value="30 minutes before" <?php echo $current_reminder === '30 minutes before' ? 'selected' : ''; ?>>
                                        30 minuten van tevoren
                                    </option>
                                    <option value="1 hour before" <?php echo $current_reminder === '1 hour before' ? 'selected' : ''; ?>>
                                        1 uur van tevoren
                                    </option>
                                    <option value="2 hours before" <?php echo $current_reminder === '2 hours before' ? 'selected' : ''; ?>>
                                        2 uur van tevoren
                                    </option>
                                    <option value="1 day before" <?php echo $current_reminder === '1 day before' ? 'selected' : ''; ?>>
                                        1 dag van tevoren
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="reminder_type" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Type Notificatie
                                </label>
                                <select id="reminder_type" name="reminder_type" class="form-select">
                                    <option value="in-app" <?php echo $current_reminder_type === 'in-app' ? 'selected' : ''; ?>>
                                        📱 In-App Notificatie
                                    </option>
                                    <option value="email" <?php echo $current_reminder_type === 'email' ? 'selected' : ''; ?>>
                                        📧 E-mail Herinnering
                                    </option>
                                    <option value="push" <?php echo $current_reminder_type === 'push' ? 'selected' : ''; ?>>
                                        🔔 Push Notificatie
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Linking -->
                    <?php if (!empty($schedules)): ?>
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-link"></i>
                            Schema Koppeling
                        </h3>
                        
                        <div class="mb-3">
                            <label for="schedule_id" class="form-label">
                                <i class="fas fa-calendar-check"></i>
                                Link aan bestaand schema (optioneel)
                            </label>
                            <select id="schedule_id" name="schedule_id" class="form-select">
                                <option value="">Geen schema koppeling</option>
                                <?php foreach ($schedules as $schedule): ?>
                                    <option value="<?php echo $schedule['schedule_id']; ?>" 
                                            <?php echo $current_schedule_id == $schedule['schedule_id'] ? 'selected' : ''; ?>>
                                        🎮 <?php echo htmlspecialchars($schedule['game_titel'] ?? $schedule['title']); ?> - 
                                        <?php echo date('d/m/Y', strtotime($schedule['date'])); ?> om 
                                        <?php echo date('H:i', strtotime($schedule['time'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                Koppel dit evenement aan een bestaand gaming schema voor betere organisatie.
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Friend Sharing -->
                    <?php if (!empty($friends)): ?>
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-share-alt"></i>
                            Delen met Vrienden
                        </h3>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-users"></i>
                                Selecteer vrienden om dit evenement mee te delen (optioneel)
                            </label>
                            <div class="friends-selection">
                                <?php foreach ($friends as $friend): ?>
                                    <div class="form-check mb-2">
                                        <input type="checkbox" 
                                               name="shared_friends[]" 
                                               value="<?php echo $friend['user_id']; ?>" 
                                               class="form-check-input" 
                                               id="friend_<?php echo $friend['user_id']; ?>"
                                               <?php echo in_array($friend['user_id'], $current_shared_friends) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="friend_<?php echo $friend['user_id']; ?>">
                                            <i class="fas fa-user-friends me-2"></i>
                                            <?php echo htmlspecialchars($friend['username']); ?>
                                            <small class="text-muted ms-2">
                                                (<?php echo $friend['is_online'] ? '🟢 Online' : '⚫ Offline'; ?>)
                                            </small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">
                                Geselecteerde vrienden ontvangen een uitnodiging voor dit evenement.
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Evenement Bijwerken
                        </button>
                        
                        <a href="events.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times me-2"></i>
                            Annuleren
                        </a>
                        
                        <a href="../index.php" class="btn btn-outline-info btn-lg">
                            <i class="fas fa-home me-2"></i>
                            Terug naar Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Professional JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    
    <!-- Advanced Form Enhancement Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editEventForm');
            const titleInput = document.getElementById('title');
            const titleCounter = document.getElementById('titleCount');
            const descriptionInput = document.getElementById('description');
            const descriptionCounter = document.getElementById('descriptionCount');
            const timeInput = document.getElementById('time');
            const endTimeInput = document.getElementById('end_time');
            
            // Character counters with color coding
            function updateCharacterCounter(input, counter, maxLength) {
                const currentLength = input.value.length;
                counter.textContent = currentLength;
                
                const counterContainer = counter.parentElement;
                counterContainer.classList.remove('warning', 'danger');
                
                if (currentLength > maxLength * 0.8) {
                    counterContainer.classList.add('warning');
                }
                if (currentLength > maxLength * 0.95) {
                    counterContainer.classList.add('danger');
                }
            }
            
            // Title character counter
            if (titleInput && titleCounter) {
                titleInput.addEventListener('input', function() {
                    updateCharacterCounter(this, titleCounter, 100);
                });
                updateCharacterCounter(titleInput, titleCounter, 100);
            }
            
            // Description character counter
            if (descriptionInput && descriptionCounter) {
                descriptionInput.addEventListener('input', function() {
                    updateCharacterCounter(this, descriptionCounter, 500);
                });
                updateCharacterCounter(descriptionInput, descriptionCounter, 500);
            }
            
            // Enhanced form validation
            function validateForm() {
                let isValid = true;
                const errors = [];
                
                // Title validation
                const title = titleInput.value.trim();
                if (!title) {
                    errors.push('Titel is verplicht.');
                    markFieldInvalid(titleInput);
                    isValid = false;
                } else if (/^\s+$/.test(title)) {
                    errors.push('Titel mag niet alleen uit spaties bestaan.');
                    markFieldInvalid(titleInput);
                    isValid = false;
                } else if (title.length > 100) {
                    errors.push('Titel mag maximaal 100 karakters bevatten.');
                    markFieldInvalid(titleInput);
                    isValid = false;
                } else {
                    markFieldValid(titleInput);
                }
                
                // Date validation
                const dateInput = document.getElementById('date');
                const dateValue = new Date(dateInput.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (!dateInput.value) {
                    errors.push('Datum is verplicht.');
                    markFieldInvalid(dateInput);
                    isValid = false;
                } else if (dateValue < today) {
                    errors.push('Datum moet in de toekomst liggen.');
                    markFieldInvalid(dateInput);
                    isValid = false;
                } else {
                    markFieldValid(dateInput);
                }
                
                // Time validation
                if (!timeInput.value) {
                    errors.push('Starttijd is verplicht.');
                    markFieldInvalid(timeInput);
                    isValid = false;
                } else {
                    markFieldValid(timeInput);
                }
                
                // End time validation (if provided)
                if (endTimeInput.value && timeInput.value) {
                    const startTime = new Date(`2000-01-01T${timeInput.value}`);
                    const endTime = new Date(`2000-01-01T${endTimeInput.value}`);
                    
                    if (endTime <= startTime) {
                        errors.push('Eindtijd moet na de starttijd zijn.');
                        markFieldInvalid(endTimeInput);
                        isValid = false;
                    } else {
                        markFieldValid(endTimeInput);
                    }
                }
                
                // Description validation
                if (descriptionInput.value.length > 500) {
                    errors.push('Beschrijving mag maximaal 500 karakters bevatten.');
                    markFieldInvalid(descriptionInput);
                    isValid = false;
                } else {
                    markFieldValid(descriptionInput);
                }
                
                // Max participants validation
                const maxParticipantsInput = document.getElementById('max_participants');
                if (maxParticipantsInput.value && maxParticipantsInput.value <= 0) {
                    errors.push('Maximum aantal deelnemers moet een positief getal zijn.');
                    markFieldInvalid(maxParticipantsInput);
                    isValid = false;
                } else {
                    markFieldValid(maxParticipantsInput);
                }
                
                return { isValid, errors };
            }
            
            function markFieldValid(field) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
            
            function markFieldInvalid(field) {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
            }
            
            // Form submission with validation
            form.addEventListener('submit', function(e) {
                const validation = validateForm();
                
                if (!validation.isValid) {
                    e.preventDefault();
                    
                    // Show error message
                    let errorMessage = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><strong>Validatiefouten:</strong><ul class="mb-0 mt-2">';
                    validation.errors.forEach(error => {
                        errorMessage += `<li>${error}</li>`;
                    });
                    errorMessage += '</ul></div>';
                    
                    // Insert error message at top of form
                    const existingAlert = form.querySelector('.alert');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                    form.insertAdjacentHTML('afterbegin', errorMessage);
                    
                    // Scroll to top of form
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    
                    return false;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Bezig met bijwerken...';
                submitBtn.disabled = true;
                
                // Add loading class to form
                form.classList.add('loading');
            });
            
            // Real-time validation on input
            const formInputs = form.querySelectorAll('input, select, textarea');
            formInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateForm();
                });
                
                input.addEventListener('input', function() {
                    // Clear validation state on input
                    this.classList.remove('is-invalid', 'is-valid');
                });
            });
            
            // Prevent form submission if user presses Enter in text fields
            titleInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.blur();
                }
            });
            
            // Auto-suggest for location field
            const locationInput = document.getElementById('location');
            const locationSuggestions = [
                'Online - Discord Server',
                'Online - Steam',
                'Online - TeamSpeak',
                'Gaming Cafe Amsterdam',
                'Gaming Cafe Rotterdam',
                'Gaming Cafe Den Haag',
                'Thuis - LAN Party',
                'Convention Center',
                'Esports Arena'
            ];
            
            // Add datalist for location suggestions
            const locationDatalist = document.createElement('datalist');
            locationDatalist.id = 'locationSuggestions';
            locationSuggestions.forEach(suggestion => {
                const option = document.createElement('option');
                option.value = suggestion;
                locationDatalist.appendChild(option);
            });
            document.body.appendChild(locationDatalist);
            locationInput.setAttribute('list', 'locationSuggestions');
            
            // Warn about unsaved changes
            let formChanged = false;
            
            formInputs.forEach(input => {
                input.addEventListener('input', function() {
                    formChanged = true;
                });
            });
            
            window.addEventListener('beforeunload', function(e) {
                if (formChanged && !form.classList.contains('loading')) {
                    e.preventDefault();
                    e.returnValue = 'U heeft niet-opgeslagen wijzigingen. Weet u zeker dat u de pagina wilt verlaten?';
                    return e.returnValue;
                }
            });
            
            // Clear unsaved changes warning on successful form submission
            form.addEventListener('submit', function() {
                formChanged = false;
            });
            
            console.log('GamePlan Event Editor initialized successfully');
        });
    </script>
</body>
</html>