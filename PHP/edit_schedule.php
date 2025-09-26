<?php
/**
 * Advanced Schedule Editing System
 * GamePlan Scheduler - Professional Gaming Schedule Management
 * 
 * This module handles secure editing of gaming schedules with comprehensive
 * validation, friend management, and user authorization checks.
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
$schedule_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Advanced data loading with error handling
try {
    $games = getGames();
    $friends_list = getFriends($user_id);
    $user_profile = getProfile($user_id);
} catch (Exception $e) {
    error_log("Data loading error in edit_schedule.php: " . $e->getMessage());
    $games = [];
    $friends_list = [];
    $user_profile = null;
}

// Validate schedule ID
if (!$schedule_id || $schedule_id <= 0) {
    $_SESSION['error_message'] = 'Ongeldig schema ID.';
    header("Location: index.php");
    exit();
}

// Get schedule data with comprehensive information
global $pdo;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, g.titel as game_titel, g.description as game_description,
               u.username as owner_username
        FROM Schedules s 
        LEFT JOIN Games g ON s.game_id = g.game_id
        LEFT JOIN Users u ON s.user_id = u.user_id
        WHERE s.schedule_id = :id AND s.user_id = :user_id
    ");
    
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        $_SESSION['error_message'] = 'Schema niet gevonden of je hebt geen toestemming om dit schema te bewerken.';
        header("Location: index.php");
        exit();
    }
    
    // Process friends list (stored as comma-separated string)
    $selected_friends = !empty($schedule['friends']) ? explode(',', $schedule['friends']) : [];
    $selected_friends = array_filter($selected_friends); // Remove empty values
    
} catch (PDOException $e) {
    error_log("Database error in edit_schedule.php: " . $e->getMessage());
    $_SESSION['error_message'] = 'Database fout bij ophalen schema.';
    header("Location: index.php");
    exit();
}

$message = '';
$validation_errors = [];

// Advanced form processing with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $game_id = filter_input(INPUT_POST, 'game_id', FILTER_VALIDATE_INT);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $time = filter_input(INPUT_POST, 'time', FILTER_SANITIZE_STRING);
    $friends = $_POST['friends'] ?? [];
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?? '');
    $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_STRING) ?? 'medium';
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT) ?? null;
    
    // Advanced validation
    if (!$game_id || $game_id <= 0) {
        $validation_errors[] = 'Selecteer een geldige game.';
    }
    
    if (empty($date)) {
        $validation_errors[] = 'Datum is verplicht.';
    } elseif (strtotime($date) === false) {
        $validation_errors[] = 'Voer een geldige datum in.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $validation_errors[] = 'Datum moet in de toekomst liggen.';
    } elseif (strtotime($date) > strtotime('+1 year')) {
        $validation_errors[] = 'Datum mag niet meer dan 1 jaar vooruit zijn.';
    }
    
    if (empty($time)) {
        $validation_errors[] = 'Tijd is verplicht.';
    } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        $validation_errors[] = 'Voer een geldige tijd in (HH:MM format).';
    }
    
    if (!empty($notes) && strlen($notes) > 500) {
        $validation_errors[] = 'Notities mogen maximaal 500 karakters bevatten.';
    }
    
    if ($duration !== null && ($duration < 15 || $duration > 480)) {
        $validation_errors[] = 'Duur moet tussen 15 minuten en 8 uur zijn.';
    }
    
    // Advanced friend validation
    $validated_friends = [];
    if (!empty($friends)) {
        foreach ($friends as $friend_id) {
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
            'game_id' => $game_id,
            'date' => $date,
            'time' => $time,
            'friends' => $validated_friends,
            'notes' => $notes,
            'priority' => $priority,
            'duration' => $duration,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = editSchedule($schedule_id, $update_data);
        if ($result['success']) {
            $_SESSION['success_message'] = 'Schema is succesvol bijgewerkt.';
            
            // Log successful action
            logUserActivity($user_id, 'schedule_updated', $schedule_id);
            
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
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema Bewerken - GamePlan Scheduler</title>
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
                            Gaming Schema Bewerken
                        </h2>
                        <small class="text-light">Pas je gaming sessie aan naar wens</small>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST" novalidate onsubmit="return validateForm(this);">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="game_id" class="form-label">
                                            <i class="fas fa-gamepad me-2"></i>Game selecteren
                                        </label>
                                        <select id="game_id" name="game_id" class="form-select" required>
                                            <option value="">Kies een game</option>
                                            <?php foreach ($games as $game): ?>
                                                <option value="<?php echo $game['game_id']; ?>" 
                                                        <?php echo ($game['game_id'] == $schedule['game_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($game['titel']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Selecteer de game die je wilt spelen</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="priority" class="form-label">
                                            <i class="fas fa-star me-2"></i>Prioriteit
                                        </label>
                                        <select id="priority" name="priority" class="form-select">
                                            <option value="low" <?php echo (isset($schedule['priority']) && $schedule['priority'] === 'low') ? 'selected' : ''; ?>>
                                                <i class="fas fa-arrow-down"></i> Laag
                                            </option>
                                            <option value="medium" <?php echo (!isset($schedule['priority']) || $schedule['priority'] === 'medium') ? 'selected' : ''; ?>>
                                                <i class="fas fa-minus"></i> Medium
                                            </option>
                                            <option value="high" <?php echo (isset($schedule['priority']) && $schedule['priority'] === 'high') ? 'selected' : ''; ?>>
                                                <i class="fas fa-arrow-up"></i> Hoog
                                            </option>
                                            <option value="critical" <?php echo (isset($schedule['priority']) && $schedule['priority'] === 'critical') ? 'selected' : ''; ?>>
                                                <i class="fas fa-exclamation"></i> Kritiek
                                            </option>
                                        </select>
                                        <div class="form-text">Hoe belangrijk is deze gaming sessie?</div>
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
                                               value="<?php echo $schedule['date']; ?>" 
                                               required 
                                               min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="time" class="form-label">
                                            <i class="fas fa-clock me-2"></i>Starttijd
                                        </label>
                                        <input type="time" 
                                               id="time" 
                                               name="time" 
                                               class="form-control" 
                                               value="<?php echo $schedule['time']; ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">
                                            <i class="fas fa-hourglass-half me-2"></i>Duur (minuten)
                                        </label>
                                        <input type="number" 
                                               id="duration" 
                                               name="duration" 
                                               class="form-control" 
                                               min="15" 
                                               max="480" 
                                               step="15"
                                               placeholder="Bijv. 60"
                                               value="<?php echo isset($schedule['duration']) ? $schedule['duration'] : ''; ?>">
                                        <div class="form-text">15 minuten tot 8 uur</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">
                                    <i class="fas fa-sticky-note me-2"></i>Notities (optioneel)
                                </label>
                                <textarea id="notes" 
                                          name="notes" 
                                          class="form-control" 
                                          rows="3"
                                          maxlength="500"
                                          placeholder="Bijv. Tournament voorbereiding, nieuwe strategie proberen..."><?php echo isset($schedule['notes']) ? htmlspecialchars($schedule['notes']) : ''; ?></textarea>
                                <div class="form-text">
                                    <span id="notes-counter"><?php echo isset($schedule['notes']) ? strlen($schedule['notes']) : 0; ?></span>/500 karakters
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-users me-2"></i>Vrienden uitnodigen (optioneel)
                                </label>
                                <div class="border rounded p-3 bg-light text-dark" style="max-height: 250px; overflow-y: auto;">
                                    <?php if (!empty($friends_list)): ?>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllFriends">
                                                    <i class="fas fa-check-double me-1"></i>Selecteer alle
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="clearAllFriends">
                                                    <i class="fas fa-times me-1"></i>Deselecteer alle
                                                </button>
                                                <small class="text-muted ms-3">
                                                    <span id="friend-count"><?php echo count($selected_friends); ?></span> vriend(en) geselecteerd
                                                </small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <?php foreach ($friends_list as $friend): ?>
                                                <div class="col-md-6 mb-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" 
                                                               name="friends[]" 
                                                               value="<?php echo $friend['user_id']; ?>" 
                                                               class="form-check-input friend-checkbox"
                                                               id="friend_<?php echo $friend['user_id']; ?>"
                                                               <?php echo in_array($friend['user_id'], $selected_friends) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label d-flex align-items-center" 
                                                               for="friend_<?php echo $friend['user_id']; ?>">
                                                            <div class="avatar-placeholder bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                                 style="width: 28px; height: 28px;">
                                                                <i class="fas fa-user text-white" style="font-size: 10px;"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="fw-semibold"><?php echo htmlspecialchars($friend['username']); ?></div>
                                                                <small class="text-muted">
                                                                    <?php if ($friend['status'] === 'online'): ?>
                                                                        <i class="fas fa-circle text-success me-1" style="font-size: 6px;"></i>
                                                                        Online
                                                                    <?php else: ?>
                                                                        <i class="fas fa-circle text-secondary me-1" style="font-size: 6px;"></i>
                                                                        Offline
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-user-friends text-muted" style="font-size: 48px;"></i>
                                            <p class="text-muted mt-3 mb-2">
                                                <strong>Nog geen vrienden toegevoegd</strong>
                                            </p>
                                            <p class="text-muted mb-3">
                                                Voeg vrienden toe om samen te gamen
                                            </p>
                                            <a href="add_friend.php" class="btn btn-primary btn-sm">
                                                <i class="fas fa-user-plus me-1"></i>Voeg vrienden toe
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-info-circle me-2"></i>Schema bijwerken</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="mb-0">
                                            <li>Alle uitgenodigde vrienden ontvangen een update</li>
                                            <li>Wijzigingen worden direct zichtbaar in de kalender</li>
                                            <li>Notities helpen je om je planning bij te houden</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="mb-0">
                                            <li>Prioriteit bepaalt de volgorde in je overzicht</li>
                                            <li>Duur helpt met tijdsplanning</li>
                                            <li>Nieuwe vrienden krijgen een uitnodiging</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i>Annuleren
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Schema Bijwerken
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
        // Character counter for notes
        const notesField = document.getElementById('notes');
        const notesCounter = document.getElementById('notes-counter');
        
        if (notesField && notesCounter) {
            notesField.addEventListener('input', function() {
                const currentLength = this.value.length;
                notesCounter.textContent = currentLength;
                
                if (currentLength > 400) {
                    notesCounter.className = 'text-warning';
                } else if (currentLength > 500) {
                    notesCounter.className = 'text-danger';
                } else {
                    notesCounter.className = '';
                }
            });
        }
        
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
            const gameId = form.game_id.value;
            const date = form.date.value;
            const time = form.time.value;
            const notes = form.notes.value.trim();
            const duration = form.duration.value;
            
            // Game validation
            if (!gameId) {
                showAlert('Selecteer een game voor het schema', 'warning');
                form.game_id.focus();
                return false;
            }
            
            // Date validation
            if (!date) {
                showAlert('Selecteer een datum voor het schema', 'warning');
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
                showAlert('Voer een starttijd in', 'warning');
                form.time.focus();
                return false;
            }
            
            // Notes validation
            if (notes && notes.length > 500) {
                showAlert('Notities mogen maximaal 500 karakters bevatten', 'warning');
                form.notes.focus();
                return false;
            }
            
            if (notes && /^\s*$/.test(notes)) {
                showAlert('Notities mogen niet alleen uit spaties bestaan', 'warning');
                form.notes.focus();
                return false;
            }
            
            // Duration validation
            if (duration && (duration < 15 || duration > 480)) {
                showAlert('Duur moet tussen 15 minuten en 8 uur zijn', 'warning');
                form.duration.focus();
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
            
            // Set minimum date to today
            const dateField = document.getElementById('date');
            if (dateField && !dateField.value) {
                dateField.min = new Date().toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>