<?php
/**
 * Advanced Event Editing System
 * GamePlan Scheduler - Professional Gaming Event Management
 * 
 * This module handles secure editing of gaming events with comprehensive
 * validation, friend management, schedule linking, and user authorization.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

require 'functions.php';

// Advanced security check with session validation
if (!isLoggedIn() || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables for advanced processing
$user_id = $_SESSION['user_id'];
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Advanced data loading with error handling
try {
    $schedules = getSchedules($user_id);
    $friends_list = getFriends($user_id);
    $user_profile = getProfile($user_id);
} catch (Exception $e) {
    error_log("Data loading error in edit_event.php: " . $e->getMessage());
    $schedules = [];
    $friends_list = [];
    $user_profile = null;
}

// Validate event ID
if (!$event_id || $event_id <= 0) {
    $_SESSION['error_message'] = 'Ongeldig evenement ID.';
    header("Location: index.php");
    exit();
}

// Get event data with comprehensive information
global $pdo;
try {
    $stmt = $pdo->prepare("
        SELECT e.*, s.game_id, g.titel as linked_game_title,
               COUNT(DISTINCT eum.friend_id) as shared_count
        FROM Events e
        LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
        LEFT JOIN Games g ON s.game_id = g.game_id
        LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
        WHERE e.event_id = :id AND e.user_id = :user_id
        GROUP BY e.event_id
    ");
    
    $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        $_SESSION['error_message'] = 'Evenement niet gevonden of je hebt geen toestemming om dit evenement te bewerken.';
        header("Location: index.php");
        exit();
    }
    
    // Get shared friends for this event
    $shared_friends_stmt = $pdo->prepare("
        SELECT u.user_id, u.username
        FROM EventUserMap eum
        JOIN Users u ON eum.friend_id = u.user_id
        WHERE eum.event_id = :event_id
        ORDER BY u.username
    ");
    $shared_friends_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $shared_friends_stmt->execute();
    $shared_friends_data = $shared_friends_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $shared_friends = array_column($shared_friends_data, 'user_id');
    
} catch (PDOException $e) {
    error_log("Database error in edit_event.php: " . $e->getMessage());
    $_SESSION['error_message'] = 'Database fout bij ophalen evenement.';
    header("Location: index.php");
    exit();
}

$message = '';
$validation_errors = [];

// Advanced form processing with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $time = filter_input(INPUT_POST, 'time', FILTER_SANITIZE_STRING);
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '');
    $reminder = filter_input(INPUT_POST, 'reminder', FILTER_SANITIZE_STRING) ?? '';
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT) ?: null;
    $shared_friends_post = $_POST['shared_friends'] ?? [];
    $event_type = filter_input(INPUT_POST, 'event_type', FILTER_SANITIZE_STRING) ?? $event['event_type'] ?? 'tournament';
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?? null;
    
    // Advanced validation
    if (empty($title)) {
        $validation_errors[] = 'Titel is verplicht.';
    } elseif (strlen($title) > 100) {
        $validation_errors[] = 'Titel mag maximaal 100 karakters bevatten.';
    } elseif (preg_match('/^\s*$/', $title)) {
        $validation_errors[] = 'Titel mag niet alleen uit spaties bestaan.';
    }
    
    if (empty($date)) {
        $validation_errors[] = 'Datum is verplicht.';
    } elseif (strtotime($date) === false) {
        $validation_errors[] = 'Voer een geldige datum in.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $validation_errors[] = 'Datum moet in de toekomst liggen.';
    } elseif (strtotime($date) > strtotime('+2 years')) {
        $validation_errors[] = 'Datum mag niet meer dan 2 jaar vooruit zijn.';
    }
    
    if (empty($time)) {
        $validation_errors[] = 'Tijd is verplicht.';
    } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        $validation_errors[] = 'Voer een geldige tijd in (HH:MM format).';
    }
    
    if (!empty($description) && strlen($description) > 1000) {
        $validation_errors[] = 'Beschrijving mag maximaal 1000 karakters bevatten.';
    }
    
    if ($max_participants !== null && ($max_participants < 2 || $max_participants > 100)) {
        $validation_errors[] = 'Maximum deelnemers moet tussen 2 en 100 zijn.';
    }
    
    // Advanced friend validation
    $validated_friends = [];
    if (!empty($shared_friends_post)) {
        foreach ($shared_friends_post as $friend_id) {
            $friend_id = filter_var($friend_id, FILTER_VALIDATE_INT);
            if ($friend_id && $friend_id > 0) {
                $validated_friends[] = $friend_id;
            }
        }
        
        // Check if friends exist and are actual friends
        if (!empty($validated_friends)) {
            $valid_friends = validateFriendsList($user_id, $validated_friends);
            if (count($valid_friends) !== count($validated_friends)) {
                $validation_errors[] = 'Enkele geselecteerde vrienden zijn niet geldig.';
            }
            $validated_friends = $valid_friends;
        }
    }
    
    // Process if no validation errors
    if (empty($validation_errors)) {
        $update_data = [
            'title' => $title,
            'date' => $date,
            'time' => $time,
            'description' => $description,
            'reminder' => $reminder,
            'schedule_id' => $schedule_id,
            'event_type' => $event_type,
            'max_participants' => $max_participants,
            'shared_friends' => $validated_friends,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = editEvent($event_id, $update_data);
        if ($result['success']) {
            $_SESSION['success_message'] = 'Evenement "' . htmlspecialchars($title) . '" is succesvol bijgewerkt.';
            
            // Log successful action
            logUserActivity($user_id, 'event_updated', $event_id);
            
            header("Location: index.php");
            exit();
        } else {
            $message = '<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Fout bij bijwerken:</strong> ' . htmlspecialchars($result['message']) . '
            </div>';
        }
    } else {
        $message = '<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Validatiefouten:</strong>
            <ul class="mb-0 mt-2">';
        foreach ($validation_errors as $error) {
            $message .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $message .= '</ul></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evenement Bewerken - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card bg-secondary border-primary">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Evenement Bewerken
                        </h2>
                        <small class="text-light">Pas je gaming evenement aan naar wens</small>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST" novalidate onsubmit="return validateForm(this);">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">
                                            <i class="fas fa-trophy me-2"></i>Evenement titel
                                        </label>
                                        <input type="text" 
                                               id="title" 
                                               name="title" 
                                               class="form-control" 
                                               placeholder="Bijv. Fortnite Tournament, Minecraft Bouwwedstrijd..."
                                               value="<?php echo htmlspecialchars($event['title']); ?>"
                                               required 
                                               maxlength="100">
                                        <div class="form-text">
                                            <span id="title-counter"><?php echo strlen($event['title']); ?></span>/100 karakters
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="event_type" class="form-label">
                                            <i class="fas fa-tags me-2"></i>Type evenement
                                        </label>
                                        <select id="event_type" name="event_type" class="form-select">
                                            <option value="tournament" <?php echo ($event['event_type'] === 'tournament' || !isset($event['event_type'])) ? 'selected' : ''; ?>>Tournament</option>
                                            <option value="meetup" <?php echo ($event['event_type'] === 'meetup') ? 'selected' : ''; ?>>Meetup</option>
                                            <option value="streaming" <?php echo ($event['event_type'] === 'streaming') ? 'selected' : ''; ?>>Streaming Sessie</option>
                                            <option value="practice" <?php echo ($event['event_type'] === 'practice') ? 'selected' : ''; ?>>Practice Sessie</option>
                                            <option value="other" <?php echo ($event['event_type'] === 'other') ? 'selected' : ''; ?>>Andere</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="date" class="form-label">
                                            <i class="fas fa-calendar me-2"></i>Datum
                                        </label>
                                        <input type="date" 
                                               id="date" 
                                               name="date" 
                                               class="form-control" 
                                               min="<?php echo date('Y-m-d'); ?>"
                                               value="<?php echo $event['date']; ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="time" class="form-label">
                                            <i class="fas fa-clock me-2"></i>Tijd
                                        </label>
                                        <input type="time" 
                                               id="time" 
                                               name="time" 
                                               class="form-control" 
                                               value="<?php echo $event['time']; ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="max_participants" class="form-label">
                                            <i class="fas fa-users me-2"></i>Max deelnemers (optioneel)
                                        </label>
                                        <input type="number" 
                                               id="max_participants" 
                                               name="max_participants" 
                                               class="form-control" 
                                               min="2" 
                                               max="100"
                                               placeholder="Geen limiet"
                                               value="<?php echo isset($event['max_participants']) ? $event['max_participants'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reminder" class="form-label">
                                            <i class="fas fa-bell me-2"></i>Herinnering instellen
                                        </label>
                                        <select id="reminder" name="reminder" class="form-select">
                                            <option value="" <?php echo ($event['reminder'] === '') ? 'selected' : ''; ?>>Geen herinnering</option>
                                            <option value="15 minuten ervoor" <?php echo ($event['reminder'] === '15 minuten ervoor') ? 'selected' : ''; ?>>15 minuten ervoor</option>
                                            <option value="30 minuten ervoor" <?php echo ($event['reminder'] === '30 minuten ervoor') ? 'selected' : ''; ?>>30 minuten ervoor</option>
                                            <option value="1 uur ervoor" <?php echo ($event['reminder'] === '1 uur ervoor') ? 'selected' : ''; ?>>1 uur ervoor</option>
                                            <option value="2 uur ervoor" <?php echo ($event['reminder'] === '2 uur ervoor') ? 'selected' : ''; ?>>2 uur ervoor</option>
                                            <option value="1 dag ervoor" <?php echo ($event['reminder'] === '1 dag ervoor') ? 'selected' : ''; ?>>1 dag ervoor</option>
                                            <option value="1 week ervoor" <?php echo ($event['reminder'] === '1 week ervoor') ? 'selected' : ''; ?>>1 week ervoor</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="schedule_id" class="form-label">
                                            <i class="fas fa-link me-2"></i>Link aan bestaand schema (optioneel)
                                        </label>
                                        <select id="schedule_id" name="schedule_id" class="form-select">
                                            <option value="" <?php echo (!$event['schedule_id']) ? 'selected' : ''; ?>>Geen schema gekoppeld</option>
                                            <?php foreach ($schedules as $sched): ?>
                                                <option value="<?php echo $sched['schedule_id']; ?>" 
                                                        <?php echo ($sched['schedule_id'] == $event['schedule_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($sched['game_titel']) . ' - ' . date('j M Y', strtotime($sched['date'])) . ' om ' . date('H:i', strtotime($sched['time'])); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Koppel dit evenement aan een bestaand gaming schema</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-2"></i>Beschrijving (optioneel)
                                </label>
                                <textarea id="description" 
                                          name="description" 
                                          class="form-control" 
                                          rows="4"
                                          maxlength="1000"
                                          placeholder="Beschrijf het evenement, regels, prijzen, vereisten, etc..."><?php echo htmlspecialchars($event['description']); ?></textarea>
                                <div class="form-text">
                                    <span id="description-counter"><?php echo strlen($event['description']); ?></span>/1000 karakters
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-share me-2"></i>Deel met vrienden (optioneel)
                                        </label>
                                        <div class="border rounded p-3 bg-light text-dark" style="max-height: 200px; overflow-y: auto;">
                                            <?php if (!empty($friends_list)): ?>
                                                <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllFriends">
                                                            <i class="fas fa-check-double me-1"></i>Selecteer alle
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="clearAllFriends">
                                                            <i class="fas fa-times me-1"></i>Deselecteer alle
                                                        </button>
                                                        <small class="text-muted ms-3">
                                                            <span id="friend-count"><?php echo count($shared_friends); ?></span> vriend(en) geselecteerd
                                                        </small>
                                                    </div>
                                                </div>
                                                <?php foreach ($friends_list as $friend): ?>
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" 
                                                               name="shared_friends[]" 
                                                               value="<?php echo $friend['user_id']; ?>" 
                                                               class="form-check-input friend-checkbox"
                                                               id="friend_<?php echo $friend['user_id']; ?>"
                                                               <?php echo in_array($friend['user_id'], $shared_friends) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label d-flex align-items-center" 
                                                               for="friend_<?php echo $friend['user_id']; ?>">
                                                            <div class="avatar-placeholder bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                                 style="width: 32px; height: 32px;">
                                                                <i class="fas fa-user text-white" style="font-size: 12px;"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="fw-semibold"><?php echo htmlspecialchars($friend['username']); ?></div>
                                                                <small class="text-muted">
                                                                    <?php if ($friend['status'] === 'online'): ?>
                                                                        <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i>
                                                                        Online
                                                                    <?php else: ?>
                                                                        <i class="fas fa-circle text-secondary me-1" style="font-size: 8px;"></i>
                                                                        Offline
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="text-center py-4">
                                                    <i class="fas fa-user-friends text-muted" style="font-size: 48px;"></i>
                                                    <p class="text-muted mt-3 mb-2">
                                                        <strong>Nog geen vrienden toegevoegd</strong>
                                                    </p>
                                                    <p class="text-muted mb-3">
                                                        Voeg vrienden toe om ze uit te nodigen voor evenementen
                                                    </p>
                                                    <a href="add_friend.php" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-user-plus me-1"></i>Voeg vrienden toe
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-info-circle me-2"></i>Wat verandert er bij het bijwerken?</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="mb-0">
                                            <li>Alle genodigde vrienden worden geïnformeerd</li>
                                            <li>Bestaande herinneringen worden bijgewerkt</li>
                                            <li>Gekoppelde schema's blijven verbonden</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="mb-0">
                                            <li>Wijzigingen worden direct zichtbaar in kalender</li>
                                            <li>Historie van wijzigingen wordt bijgehouden</li>
                                            <li>Nieuwe vrienden ontvangen een uitnodiging</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i>Annuleren
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Evenement Bijwerken
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-white text-center p-3 mt-5">
        <div class="container">
            <p class="mb-0">
                © 2025 GamePlan Scheduler door Harsha Kanaparthi | 
                <a href="privacy.php" class="text-white text-decoration-none">Privacybeleid</a> | 
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    
    <script>
        // Character counter for title
        const titleField = document.getElementById('title');
        const titleCounter = document.getElementById('title-counter');
        
        if (titleField && titleCounter) {
            titleField.addEventListener('input', function() {
                const currentLength = this.value.length;
                titleCounter.textContent = currentLength;
                
                if (currentLength > 80) {
                    titleCounter.className = 'text-warning';
                } else if (currentLength > 100) {
                    titleCounter.className = 'text-danger';
                } else {
                    titleCounter.className = '';
                }
            });
        }
        
        // Character counter for description
        const descriptionField = document.getElementById('description');
        const descriptionCounter = document.getElementById('description-counter');
        
        if (descriptionField && descriptionCounter) {
            descriptionField.addEventListener('input', function() {
                const currentLength = this.value.length;
                descriptionCounter.textContent = currentLength;
                
                if (currentLength > 800) {
                    descriptionCounter.className = 'text-warning';
                } else if (currentLength > 1000) {
                    descriptionCounter.className = 'text-danger';
                } else {
                    descriptionCounter.className = '';
                }
            });
        }
        
        // Event type change handler
        document.getElementById('event_type').addEventListener('change', function() {
            const eventType = this.value;
            const titleField = document.getElementById('title');
            const currentTitle = titleField.value.trim();
            
            // Only suggest if field is mostly empty
            if (currentTitle.length < 5) {
                switch(eventType) {
                    case 'tournament':
                        titleField.placeholder = 'Bijv. Fortnite Battle Royale Tournament, COD Championship...';
                        break;
                    case 'meetup':
                        titleField.placeholder = 'Bijv. Gaming Meetup Amsterdam, Local Gaming Night...';
                        break;
                    case 'streaming':
                        titleField.placeholder = 'Bijv. Live Stream Session, Gameplay Showcase...';
                        break;
                    case 'practice':
                        titleField.placeholder = 'Bijv. Team Practice, Skill Training Session...';
                        break;
                    default:
                        titleField.placeholder = 'Bijv. Gaming Event, Special Activity...';
                }
            }
        });
        
        // Friend selection controls
        const selectAllBtn = document.getElementById('selectAllFriends');
        const clearAllBtn = document.getElementById('clearAllFriends');
        const friendCheckboxes = document.querySelectorAll('.friend-checkbox');
        
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                friendCheckboxes.forEach(checkbox => checkbox.checked = true);
                updateFriendCount();
            });
        }
        
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function() {
                friendCheckboxes.forEach(checkbox => checkbox.checked = false);
                updateFriendCount();
            });
        }
        
        // Update friend selection count
        function updateFriendCount() {
            const checkedCount = document.querySelectorAll('.friend-checkbox:checked').length;
            const countDisplay = document.getElementById('friend-count');
            if (countDisplay) {
                countDisplay.textContent = checkedCount;
            }
        }
        
        friendCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateFriendCount);
        });
        
        // Enhanced form validation
        function validateForm(form) {
            const title = form.title.value.trim();
            const date = form.date.value;
            const time = form.time.value;
            const description = form.description.value.trim();
            const maxParticipants = form.max_participants.value;
            
            // Title validation
            if (!title) {
                showAlert('Voer een titel in voor het evenement', 'warning');
                form.title.focus();
                return false;
            }
            
            if (title.length > 100) {
                showAlert('Titel mag maximaal 100 karakters bevatten', 'warning');
                form.title.focus();
                return false;
            }
            
            if (/^\s*$/.test(title)) {
                showAlert('Titel mag niet alleen uit spaties bestaan', 'warning');
                form.title.focus();
                return false;
            }
            
            // Date validation
            if (!date) {
                showAlert('Selecteer een datum voor het evenement', 'warning');
                form.date.focus();
                return false;
            }
            
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                showAlert('Kies een datum in de toekomst', 'warning');
                form.date.focus();
                return false;
            }
            
            // Time validation
            if (!time) {
                showAlert('Voer een tijd in voor het evenement', 'warning');
                form.time.focus();
                return false;
            }
            
            // Description validation
            if (description && description.length > 1000) {
                showAlert('Beschrijving mag maximaal 1000 karakters bevatten', 'warning');
                form.description.focus();
                return false;
            }
            
            if (description && /^\s*$/.test(description)) {
                showAlert('Beschrijving mag niet alleen uit spaties bestaan', 'warning');
                form.description.focus();
                return false;
            }
            
            // Max participants validation
            if (maxParticipants && (maxParticipants < 2 || maxParticipants > 100)) {
                showAlert('Maximum deelnemers moet tussen 2 en 100 zijn', 'warning');
                form.max_participants.focus();
                return false;
            }
            
            return true;
        }
        
        // Show alert function
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.card-body');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateFriendCount();
        });
    </script>
</body>
</html>