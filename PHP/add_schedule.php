<?php
require 'functions.php';

// Advanced security check with session validation
if (!isLoggedIn() || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Advanced data loading with error handling
try {
    $games = getGames();
    $friends_list = getFriends($user_id);
    $user_profile = getProfile($user_id);
} catch (Exception $e) {
    error_log("Data loading error: " . $e->getMessage());
    $games = [];
    $friends_list = [];
    $user_profile = null;
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
    $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_STRING) ?? 'medium';
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?? '');
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT) ?? 60;
    
    // Advanced validation
    if (!$game_id || $game_id <= 0) {
        $validation_errors[] = 'Selecteer een geldige game.';
    }
    
    if (empty($date)) {
        $validation_errors[] = 'Datum is verplicht.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $validation_errors[] = 'Datum moet in de toekomst liggen.';
    } elseif (strtotime($date) > strtotime('+1 year')) {
        $validation_errors[] = 'Datum mag niet meer dan een jaar vooruit zijn.';
    }
    
    if (empty($time)) {
        $validation_errors[] = 'Tijd is verplicht.';
    } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        $validation_errors[] = 'Voer een geldige tijd in (HH:MM format).';
    }
    
    if (!empty($notes) && strlen($notes) > 500) {
        $validation_errors[] = 'Notities mogen maximaal 500 karakters bevatten.';
    }
    
    if ($duration < 15 || $duration > 480) {
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
        $schedule_data = [
            'user_id' => $user_id,
            'game_id' => $game_id,
            'date' => $date,
            'time' => $time,
            'friends' => $validated_friends,
            'priority' => $priority,
            'notes' => $notes,
            'duration' => $duration,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = addSchedule($schedule_data);
        if ($result['success']) {
            $message = '<div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Succesvol!</strong> ' . htmlspecialchars($result['message']) . '
                <br><small>Schema ID: ' . $result['schedule_id'] . '</small>
            </div>';
            
            // Log successful action
            logUserActivity($user_id, 'schedule_created', $result['schedule_id']);
        } else {
            $message = '<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Fout:</strong> ' . htmlspecialchars($result['message']) . '
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
    <title>Schema toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
    <header class="bg-dark text-white p-3">
        <div class="container d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-gamepad me-2"></i>GamePlan Scheduler</h1>
            <nav>
                <a href="index.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a href="schedules.php" class="btn btn-outline-light">
                    <i class="fas fa-calendar me-1"></i>Alle Schema's
                </a>
            </nav>
        </div>
    </header>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Schema Toevoegen</h2>
                        <p class="mb-0 text-muted">Plan een gaming sessie met vrienden</p>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST" onsubmit="return validateForm(this);">
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
                                                        data-genre="<?php echo htmlspecialchars($game['genre'] ?? ''); ?>" 
                                                        data-rating="<?php echo $game['rating'] ?? ''; ?>"
                                                        <?php echo (isset($_POST['game_id']) && $_POST['game_id'] == $game['game_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($game['titel']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text" id="game-info"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date" class="form-label">
                                            <i class="fas fa-calendar me-2"></i>Datum
                                        </label>
                                        <input type="date" 
                                               id="date" 
                                               name="date" 
                                               class="form-control" 
                                               min="<?php echo date('Y-m-d'); ?>"
                                               value="<?php echo isset($_POST['date']) ? $_POST['date'] : date('Y-m-d', strtotime('+1 day')); ?>"
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="time" class="form-label">
                                            <i class="fas fa-clock me-2"></i>Tijd
                                        </label>
                                        <input type="time" 
                                               id="time" 
                                               name="time" 
                                               class="form-control" 
                                               value="<?php echo isset($_POST['time']) ? $_POST['time'] : '20:00'; ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-users me-2"></i>Vrienden uitnodigen
                                        </label>
                                        <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                            <?php if (!empty($friends_list)): ?>
                                                <?php foreach ($friends_list as $friend): ?>
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" 
                                                               name="friends[]" 
                                                               value="<?php echo $friend['user_id']; ?>" 
                                                               class="form-check-input"
                                                               id="friend_<?php echo $friend['user_id']; ?>"
                                                               <?php echo (isset($_POST['friends']) && in_array($friend['user_id'], $_POST['friends'])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label d-flex align-items-center" 
                                                               for="friend_<?php echo $friend['user_id']; ?>">
                                                            <div class="avatar-placeholder bg-primary rounded-circle me-2" style="width: 24px; height: 24px;">
                                                                <i class="fas fa-user text-white" style="font-size: 10px;"></i>
                                                            </div>
                                                            <div>
                                                                <?php echo htmlspecialchars($friend['username']); ?>
                                                                <br>
                                                                <small class="text-muted">
                                                                    <?php if ($friend['status'] === 'online'): ?>
                                                                        <i class="fas fa-circle text-success"></i> Online
                                                                    <?php else: ?>
                                                                        <i class="fas fa-circle text-secondary"></i> Offline
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Nog geen vrienden toegevoegd. 
                                                    <a href="add_friend.php">Voeg vrienden toe</a> om ze uit te nodigen.
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-lightbulb me-2"></i>Tips voor een succesvol schema:</h6>
                                <ul class="mb-0">
                                    <li>Kies een tijd die voor iedereen werkt</li>
                                    <li>Controleer of je vrienden online zijn</li>
                                    <li>Denk aan tijdzones bij internationale vrienden</li>
                                    <li>Plan wat extra tijd in voor verbindingsproblemen</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-calendar-plus me-2"></i>Schema Toevoegen
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Terug naar dashboard
                                </a>
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
                <a href="privacy.php" class="text-white text-decoration-none">Privacy</a>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    
    <script>
        // Enhanced game selection with info display
        document.getElementById('game_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('game-info');
            
            if (selectedOption.value) {
                const genre = selectedOption.dataset.genre;
                const rating = selectedOption.dataset.rating;
                
                let infoText = '';
                if (genre) infoText += `Genre: ${genre}`;
                if (rating) infoText += ` | Rating: ${rating}/5`;
                
                infoDiv.innerHTML = `<i class="fas fa-info-circle me-1"></i>${infoText}`;
                infoDiv.className = 'form-text text-info';
            } else {
                infoDiv.innerHTML = '';
            }
        });
        
        // Character counter for notes
        const notesField = document.getElementById('notes');
        const notesCounter = document.getElementById('notes-counter');
        
        if (notesField && notesCounter) {
            notesField.addEventListener('input', function() {
                const currentLength = this.value.length;
                notesCounter.textContent = currentLength;
                
                if (currentLength > 450) {
                    notesCounter.className = 'text-warning';
                } else if (currentLength > 500) {
                    notesCounter.className = 'text-danger';
                } else {
                    notesCounter.className = '';
                }
            });
            
            // Initialize counter
            notesCounter.textContent = notesField.value.length;
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
            const notes = form.notes ? form.notes.value.trim() : '';
            
            // Game validation
            if (!gameId) {
                showAlert('Selecteer een game om verder te gaan', 'warning');
                form.game_id.focus();
                return false;
            }
            
            // Date validation
            if (!date) {
                showAlert('Selecteer een datum voor je gaming sessie', 'warning');
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
                showAlert('Voer een tijd in voor je gaming sessie', 'warning');
                form.time.focus();
                return false;
            }
            
            // Notes validation (prevent only spaces)
            if (notes && /^\s*$/.test(notes)) {
                showAlert('Notities mogen niet alleen uit spaties bestaan', 'warning');
                form.notes.focus();
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
        
        // Auto-suggest optimal time based on friends' activity
        document.getElementById('date').addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
                // This could be enhanced with AJAX to check friends' availability
                console.log('Date selected:', selectedDate);
                
                // Show tip about peak gaming hours
                const timeField = document.getElementById('time');
                const currentHour = new Date().getHours();
                
                if (currentHour >= 19 && currentHour <= 23) {
                    timeField.value = (currentHour + 1).toString().padStart(2, '0') + ':00';
                } else if (!timeField.value) {
                    timeField.value = '20:00'; // Default peak gaming time
                }
            }
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateFriendCount();
            
            // Set minimum date to today
            const dateField = document.getElementById('date');
            if (dateField && !dateField.value) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                dateField.min = tomorrow.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>