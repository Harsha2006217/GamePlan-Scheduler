<?php
// GamePlan Scheduler - Professional Event Editing
// Advanced form for editing existing events with sharing capabilities

require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$eventId = (int)($_GET['id'] ?? 0);

if (!$eventId) {
    header('Location: index.php');
    exit;
}

// Get event data
$event = getEventById($eventId, $userId);
if (!$event) {
    header('Location: index.php');
    exit;
}

$friends = getFriends($userId);
$schedules = getSchedules($userId);

// Parse shared friends string into array for form
$currentSharedFriends = [];
if (!empty($event['shared_with'])) {
    $sharedNames = explode(',', $event['shared_with']);
    foreach ($sharedNames as $name) {
        foreach ($friends as $friend) {
            if ($friend['username'] === trim($name)) {
                $currentSharedFriends[] = $friend['user_id'];
                break;
            }
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $reminder = trim($_POST['reminder'] ?? 'none');
    $scheduleId = !empty($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : null;
    $sharedFriends = $_POST['shared_friends'] ?? [];

    try {
        // Validate input
        if (empty($title)) {
            throw new Exception("Titel is verplicht");
        }

        if (strlen($title) > 100) {
            throw new Exception("Titel mag maximaal 100 karakters bevatten");
        }

        if (preg_match('/^\s*$/', $title)) {
            throw new Exception("Titel mag niet alleen uit spaties bestaan");
        }

        $eventDateTime = strtotime("$date $time");
        if ($eventDateTime === false || $eventDateTime <= time()) {
            throw new Exception("Het evenement moet in de toekomst plaatsvinden");
        }

        if (strlen($description) > 500) {
            throw new Exception("Beschrijving mag maximaal 500 karakters bevatten");
        }

        $validReminders = ['none', '1hour', '1day'];
        if (!in_array($reminder, $validReminders)) {
            $reminder = 'none';
        }

        if ($scheduleId) {
            $schedule = getScheduleById($scheduleId, $userId);
            if (!$schedule) {
                throw new Exception("Ongeldig schema geselecteerd");
            }
        }

        // Update event
        updateEvent($eventId, $userId, $title, $date, $time, $description, $reminder, $sharedFriends, $scheduleId);

        $_SESSION['message'] = 'Evenement succesvol bijgewerkt!';
        $_SESSION['message_type'] = 'success';

        header('Location: events.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Evenement Bewerken</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="logo">
                    <i class="fas fa-gamepad"></i> GamePlan Scheduler
                </a>
                <nav>
                    <ul class="d-flex">
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="profile.php">Profiel</a></li>
                        <li><a href="friends.php">Vrienden</a></li>
                        <li><a href="schedules.php">Schema's</a></li>
                        <li><a href="events.php" class="active">Evenementen</a></li>
                        <li><a href="?logout=1">Uitloggen</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-edit"></i> Evenement Bewerken</h2>
                            <p class="text-muted mb-0">Werk je evenementdetails en deelinstellingen bij</p>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" novalidate>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="title" class="form-label">
                                            <i class="fas fa-heading"></i> Evenement Titel *
                                        </label>
                                        <input type="text" class="form-control" id="title" name="title"
                                               value="<?php echo htmlspecialchars($event['title']); ?>"
                                               maxlength="100" required>
                                        <div class="form-text">Geef je evenement een pakkende naam (max 100 karakters)</div>
                                        <div class="invalid-feedback">
                                            Titel is verplicht en mag niet alleen uit spaties bestaan.
                                        </div>
                                    </div>

                                    <div class="col-md-2 mb-3">
                                        <label for="date" class="form-label">
                                            <i class="fas fa-calendar"></i> Datum *
                                        </label>
                                        <input type="date" class="form-control" id="date" name="date"
                                               value="<?php echo htmlspecialchars($event['date']); ?>"
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">
                                            Selecteer een toekomstige datum.
                                        </div>
                                    </div>

                                    <div class="col-md-2 mb-3">
                                        <label for="time" class="form-label">
                                            <i class="fas fa-clock"></i> Tijd *
                                        </label>
                                        <input type="time" class="form-control" id="time" name="time"
                                               value="<?php echo htmlspecialchars($event['time']); ?>" required>
                                        <div class="invalid-feedback">
                                            Selecteer een tijd.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left"></i> Beschrijving
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                              maxlength="500"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
                                    <div class="form-text">Beschrijf je evenement (max 500 karakters)</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="reminder" class="form-label">
                                            <i class="fas fa-bell"></i> Herinnering
                                        </label>
                                        <select class="form-select" id="reminder" name="reminder">
                                            <option value="none" <?php echo ($event['reminder'] == 'none') ? 'selected' : ''; ?>>Geen herinnering</option>
                                            <option value="1hour" <?php echo ($event['reminder'] == '1hour') ? 'selected' : ''; ?>>1 uur ervoor</option>
                                            <option value="1day" <?php echo ($event['reminder'] == '1day') ? 'selected' : ''; ?>>1 dag ervoor</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="schedule_id" class="form-label">
                                            <i class="fas fa-link"></i> Koppel aan Schema (Optioneel)
                                        </label>
                                        <select class="form-select" id="schedule_id" name="schedule_id">
                                            <option value="">Selecteer een game sessie...</option>
                                            <?php foreach ($schedules as $schedule): ?>
                                                <option value="<?php echo $schedule['schedule_id']; ?>"
                                                        <?php echo ($schedule['schedule_id'] == $event['schedule_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($schedule['game_title']); ?> - <?php echo date('M j, g:i A', strtotime($schedule['date'] . ' ' . $schedule['time'])); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Koppel dit evenement aan een bestaande game sessie</div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-share"></i> Deel met Vrienden
                                    </label>
                                    <div class="row">
                                        <?php if (empty($friends)): ?>
                                            <div class="col-12">
                                                <p class="text-muted">Nog geen vrienden om mee te delen. <a href="friends.php">Voeg eerst vrienden toe</a>!</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="col-12 mb-2">
                                                <div class="d-flex gap-2 mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllFriends()">Selecteer Alle</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllFriends()">Deselecteer Alle</button>
                                                </div>
                                            </div>
                                            <?php foreach ($friends as $friend): ?>
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="friend_<?php echo $friend['user_id']; ?>"
                                                               name="shared_friends[]" value="<?php echo $friend['user_id']; ?>"
                                                               <?php echo in_array($friend['user_id'], $currentSharedFriends) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="friend_<?php echo $friend['user_id']; ?>">
                                                            <?php echo htmlspecialchars($friend['username']); ?>
                                                            <span class="badge <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'bg-success' : 'bg-secondary'; ?> ms-1">
                                                                <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Evenement Bijwerken
                                    </button>
                                    <a href="events.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Terug naar Evenementen
                                    </a>
                                    <a href="delete_event.php?id=<?php echo $eventId; ?>" class="btn btn-outline-danger ms-auto"
                                       onclick="return confirm('Weet je zeker dat je dit evenement wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.')">
                                        <i class="fas fa-trash"></i> Verwijder Evenement
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Event Info -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Evenement Informatie</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Gecreëerd:</strong><br>
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($event['created_at'] ?? 'now')); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Gelinkt Schema:</strong><br>
                                    <?php echo $event['game_title'] ? htmlspecialchars($event['game_title']) : 'Geen'; ?>
                                </div>
                            </div>
                            <?php if (!empty($event['shared_with'])): ?>
                                <div class="mt-3">
                                    <strong>Momenteel Gedeeld Met:</strong><br>
                                    <?php
                                    $sharedList = explode(',', $event['shared_with']);
                                    foreach ($sharedList as $shared):
                                    ?>
                                        <span class="badge bg-info me-1"><?php echo htmlspecialchars(trim($shared)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 GamePlan Scheduler door Harsha Kanaparthi. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        // Select/clear all friends
        function selectAllFriends() {
            document.querySelectorAll('input[name="shared_friends[]"]').forEach(cb => cb.checked = true);
        }

        function clearAllFriends() {
            document.querySelectorAll('input[name="shared_friends[]"]').forEach(cb => cb.checked = false);
        }

        // Character counter for description
        document.getElementById('description').addEventListener('input', function() {
            const maxLength = 500;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;

            let counter = this.parentNode.querySelector('.char-counter');
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'char-counter form-text';
                this.parentNode.appendChild(counter);
            }

            counter.textContent = `${currentLength}/${maxLength} karakters`;
            counter.style.color = remaining < 50 ? '#dc3545' : '#6c757d';
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title');
            const date = document.getElementById('date');
            const time = document.getElementById('time');
            const description = document.getElementById('description');

            let isValid = true;

            // Title validation
            if (!title.value.trim()) {
                title.classList.add('is-invalid');
                isValid = false;
            } else if (/^\s*$/.test(title.value)) {
                title.classList.add('is-invalid');
                isValid = false;
            } else {
                title.classList.remove('is-invalid');
            }

            // Date validation
            if (!date.value) {
                date.classList.add('is-invalid');
                isValid = false;
            } else {
                const selectedDate = new Date(date.value + 'T' + (time.value || '00:00'));
                const now = new Date();
                if (selectedDate <= now) {
                    date.classList.add('is-invalid');
                    isValid = false;
                } else {
                    date.classList.remove('is-invalid');
                }
            }

            // Time validation
            if (!time.value) {
                time.classList.add('is-invalid');
                isValid = false;
            } else {
                time.classList.remove('is-invalid');
            }

            // Description length
            if (description.value.length > 500) {
                description.classList.add('is-invalid');
                isValid = false;
            } else {
                description.classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Set minimum date to today
        document.getElementById('date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>