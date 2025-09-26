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
    $schedules = getSchedules($user_id);
    $friends_list = getFriends($user_id);
    $user_profile = getProfile($user_id);
} catch (Exception $e) {
    error_log("Data loading error: " . $e->getMessage());
    $schedules = [];
    $friends_list = [];
    $user_profile = null;
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
    $shared_friends = $_POST['shared_friends'] ?? [];
    $event_type = filter_input(INPUT_POST, 'event_type', FILTER_SANITIZE_STRING) ?? 'tournament';
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
    if (!empty($shared_friends)) {
        foreach ($shared_friends as $friend_id) {
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
        $event_data = [
            'user_id' => $user_id,
            'title' => $title,
            'date' => $date,
            'time' => $time,
            'description' => $description,
            'reminder' => $reminder,
            'schedule_id' => $schedule_id,
            'shared_friends' => $validated_friends,
            'event_type' => $event_type,
            'max_participants' => $max_participants,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = addEvent($event_data);
        if ($result['success']) {
            $message = '<div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Succesvol!</strong> ' . htmlspecialchars($result['message']) . '
                <br><small>Evenement ID: ' . $result['event_id'] . '</small>
            </div>';
            
            // Log successful action
            logUserActivity($user_id, 'event_created', $result['event_id']);
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
    <title>Evenement toevoegen - GamePlan Scheduler</title>
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
                <a href="events.php" class="btn btn-outline-light">
                    <i class="fas fa-calendar-check me-1"></i>Alle Events
                </a>
            </nav>
        </div>
    </header>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Evenement Toevoegen</h2>
                        <p class="mb-0 text-muted">Organiseer toernooien, meetups en andere gaming events</p>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST" onsubmit="return validateForm(this);">
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
                                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                               required 
                                               maxlength="100">
                                        <div class="form-text">
                                            <span id="title-counter">0</span>/100 karakters
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="event_type" class="form-label">
                                            <i class="fas fa-tags me-2"></i>Type evenement
                                        </label>
                                        <select id="event_type" name="event_type" class="form-select">
                                            <option value="tournament" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'tournament') ? 'selected' : ''; ?>>Tournament</option>
                                            <option value="meetup" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'meetup') ? 'selected' : ''; ?>>Meetup</option>
                                            <option value="streaming" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'streaming') ? 'selected' : ''; ?>>Streaming Sessie</option>
                                            <option value="practice" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'practice') ? 'selected' : ''; ?>>Practice Sessie</option>
                                            <option value="other" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'other') ? 'selected' : ''; ?>>Andere</option>
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
                                               value="<?php echo isset($_POST['date']) ? $_POST['date'] : ''; ?>"
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
                                               value="<?php echo isset($_POST['time']) ? $_POST['time'] : ''; ?>"
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
                                               value="<?php echo isset($_POST['max_participants']) ? $_POST['max_participants'] : ''; ?>">
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
                                            <option value="">Geen herinnering</option>
                                            <option value="15 minuten ervoor" <?php echo (isset($_POST['reminder']) && $_POST['reminder'] === '15 minuten ervoor') ? 'selected' : ''; ?>>15 minuten ervoor</option>
                                            <option value="30 minuten ervoor" <?php echo (isset($_POST['reminder']) && $_POST['reminder'] === '30 minuten ervoor') ? 'selected' : ''; ?>>30 minuten ervoor</option>
                                            <option value="1 uur ervoor" <?php echo (isset($_POST['reminder']) && $_POST['reminder'] === '1 uur ervoor') ? 'selected' : ''; ?>>1 uur ervoor</option>
                                            <option value="2 uur ervoor" <?php echo (isset($_POST['reminder']) && $_POST['reminder'] === '2 uur ervoor') ? 'selected' : ''; ?>>2 uur ervoor</option>
                                            <option value="1 dag ervoor" <?php echo (isset($_POST['reminder']) && $_POST['reminder'] === '1 dag ervoor') ? 'selected' : ''; ?>>1 dag ervoor</option>
                                            <option value="1 week ervoor" <?php echo (isset($_POST['reminder']) && $_POST['reminder'] === '1 week ervoor') ? 'selected' : ''; ?>>1 week ervoor</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="schedule_id" class="form-label">
                                            <i class="fas fa-link me-2"></i>Link aan bestaand schema (optioneel)
                                        </label>
                                        <select id="schedule_id" name="schedule_id" class="form-select">
                                            <option value="">Geen schema gekoppeld</option>
                                            <?php foreach ($schedules as $sched): ?>
                                                <option value="<?php echo $sched['schedule_id']; ?>" 
                                                        <?php echo (isset($_POST['schedule_id']) && $_POST['schedule_id'] == $sched['schedule_id']) ? 'selected' : ''; ?>>
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
                                          placeholder="Beschrijf het evenement, regels, prijzen, vereisten, etc..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">
                                    <span id="description-counter">0</span>/1000 karakters
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-share me-2"></i>Delen met vrienden (optioneel)
                                </label>
                                <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                    <?php if (!empty($friends_list)): ?>
                                        <?php foreach ($friends_list as $friend): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" 
                                                       name="shared_friends[]" 
                                                       value="<?php echo $friend['user_id']; ?>" 
                                                       class="form-check-input"
                                                       id="friend_<?php echo $friend['user_id']; ?>"
                                                       <?php echo (isset($_POST['shared_friends']) && in_array($friend['user_id'], $_POST['shared_friends'])) ? 'checked' : ''; ?>>
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
                                            <a href="add_friend.php" class="text-decoration-none">Voeg vrienden toe</a> om ze uit te nodigen voor events.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-lightbulb me-2"></i>Tips voor een succesvol evenement:</h6>
                                <ul class="mb-0">
                                    <li>Kies een duidelijke en pakkende titel</li>
                                    <li>Plan events minstens een dag van tevoren</li>
                                    <li>Voeg een gedetailleerde beschrijving toe met regels</li>
                                    <li>Stel herinneringen in zodat niemand het vergeet</li>
                                    <li>Nodig actieve vrienden uit die vaak online zijn</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-calendar-check me-2"></i>Evenement Aanmaken
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
            
            // Initialize counter
            titleCounter.textContent = titleField.value.length;
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
            
            // Initialize counter
            descriptionCounter.textContent = descriptionField.value.length;
        }
        
        // Event type change handler
        document.getElementById('event_type').addEventListener('change', function() {
            const eventType = this.value;
            const titleField = document.getElementById('title');
            
            if (titleField.value === '') {
                switch(eventType) {
                    case 'tournament':
                        titleField.placeholder = 'Bijv. Fortnite Battle Royale Tournament, COD Warzone Championship...';
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
            
            const twoYearsFromNow = new Date();
            twoYearsFromNow.setFullYear(twoYearsFromNow.getFullYear() + 2);
            
            if (selectedDate > twoYearsFromNow) {
                showAlert('Datum mag niet meer dan 2 jaar vooruit zijn', 'warning');
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
            // Initialize counters
            if (titleField && titleCounter) {
                titleCounter.textContent = titleField.value.length;
            }
            
            if (descriptionField && descriptionCounter) {
                descriptionCounter.textContent = descriptionField.value.length;
            }
            
            // Set minimum date to today
            const dateField = document.getElementById('date');
            if (dateField && !dateField.value) {
                dateField.min = new Date().toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>