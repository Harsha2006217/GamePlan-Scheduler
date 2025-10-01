<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$games = getGames();
$friends = getFriends($user_id);
$message = '';
$errors = [];

// Form Processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Ongeldige form submission. Probeer opnieuw.";
    } else {
        // Input Validation
        $game_id = filter_input(INPUT_POST, 'game_id', FILTER_VALIDATE_INT);
        $date = trim($_POST['date'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $friends_selected = array_filter($_POST['friends'] ?? [], 'is_numeric');
        
        // Validate Game
        if (!$game_id || !getGameById($game_id)) {
            $errors[] = "Selecteer een geldige game.";
        }
        
        // Validate Date
        if (!$date || strtotime($date) < strtotime('today')) {
            $errors[] = "Selecteer een geldige datum (niet in het verleden).";
        }
        
        // Validate Time
        if (!$time || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            $errors[] = "Voer een geldige tijd in.";
        }
        
        // Check for Schedule Conflicts
        if (empty($errors)) {
            $conflicts = checkScheduleConflicts($user_id, $date, $time, $friends_selected, $game_id);
            
            if (!empty($conflicts)) {
                if (isset($conflicts['user'])) {
                    $errors[] = "Je hebt al een schema dat overlapt met dit tijdstip. Kies een ander moment.";
                }
                
                if (isset($conflicts['friends'])) {
                    foreach ($conflicts['friends'] as $friend_conflict) {
                        $errors[] = "De geselecteerde vriend {$friend_conflict['username']} heeft al een schema dat overlapt met dit tijdstip.";
                    }
                }
            }
        
        // Process if no errors
        if (empty($errors)) {
            if (addSchedule($user_id, $game_id, $date, $time, $friends_selected, $description)) {
                // Notify selected friends
                foreach ($friends_selected as $friend_id) {
                    $game = getGameById($game_id);
                    createNotification(
                        $friend_id,
                        'Nieuwe Schema Uitnodiging',
                        "Je bent uitgenodigd voor {$game['titel']} op " . formatDateTime($date, $time),
                        'schedule_invite'
                    );
                }
                
                header("Location: schedules.php?success=added");
                exit;
            } else {
                $errors[] = "Er is een fout opgetreden bij het toevoegen van het schema.";
            }
        }
    }
    
    if (!empty($errors)) {
        $message = '<div class="alert alert-danger"><ul class="mb-0">';
        foreach ($errors as $error) {
            $message .= "<li>$error</li>";
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Schema toevoegen</h2>
        <?php echo $message; ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="POST" onsubmit="return validateForm(this);" class="shadow p-4 rounded">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="game_id" class="form-label required">Game</label>
                        <select id="game_id" name="game_id" class="form-select" required>
                            <option value="">Kies game</option>
                            <?php foreach ($games as $game): ?>
                                <option value="<?php echo $game['game_id']; ?>" 
                                        data-max-players="<?php echo $game['max_players']; ?>"
                                        data-session-time="<?php echo $game['average_session_time']; ?>"
                                        <?php if ($game['game_id'] == ($_POST['game_id'] ?? '')): ?>selected<?php endif; ?>>
                                    <?php echo htmlspecialchars($game['titel']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Selecteer de game die je wilt spelen</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date" class="form-label required">Datum</label>
                            <input type="date" id="date" name="date" class="form-control" 
                                   required min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo $_POST['date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="time" class="form-label required">Tijd</label>
                            <input type="time" id="time" name="time" class="form-control" 
                                   required value="<?php echo $_POST['time'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Beschrijving</label>
                        <textarea id="description" name="description" class="form-control" rows="3"
                                placeholder="Optionele details over de gaming sessie..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vrienden Uitnodigen</label>
                        <div class="friend-list-container border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            <div class="mb-2">
                                <input type="text" class="form-control" id="friendSearch" 
                                       placeholder="Zoek vrienden..." onkeyup="filterFriends(this.value)">
                            </div>
                            <div id="friendsList">
                                <?php foreach ($friends as $friend): ?>
                                    <div class="form-check friend-item">
                                        <input type="checkbox" name="friends[]" 
                                               value="<?php echo $friend['user_id']; ?>" 
                                               class="form-check-input"
                                               <?php if (in_array($friend['user_id'], $_POST['friends'] ?? [])): ?>checked<?php endif; ?>>
                                        <label class="form-check-label">
                                            <?php echo htmlspecialchars($friend['username']); ?>
                                            <span class="badge <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'bg-success' : 'bg-secondary'; ?> ms-2">
                                                <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-text">Selecteer vrienden om uit te nodigen voor deze gaming sessie</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-calendar-plus"></i> Schema Toevoegen
                        </button>
                        <a href="schedules.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Annuleren
                        </a>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="bi bi-house"></i> Terug naar Dashboard
                        </a>
                    </div>
                </form>

                <script>
                async function checkConflicts(gameId, date, time, friendIds) {
                    try {
                        const response = await fetch('check_conflicts.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': document.querySelector('[name="csrf_token"]').value
                            },
                            body: JSON.stringify({ gameId, date, time, friendIds })
                        });
                        
                        if (!response.ok) throw new Error('Network response was not ok');
                        return await response.json();
                    } catch (error) {
                        console.error('Error checking conflicts:', error);
                        return { error: 'Could not check for conflicts' };
                    }
                }

                async function validateForm(form) {
                    const date = new Date(form.date.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (date < today) {
                        alert('Je kunt geen schema in het verleden maken.');
                        return false;
                    }

                    const gameSelect = form.game_id;
                    const selectedOption = gameSelect.options[gameSelect.selectedIndex];
                    const maxPlayers = parseInt(selectedOption.dataset.maxPlayers);
                    const selectedFriends = Array.from(form.querySelectorAll('input[name="friends[]"]:checked'));
                    
                    if (selectedFriends.length > maxPlayers - 1) {
                        alert(`Deze game ondersteunt maximaal ${maxPlayers} spelers (inclusief jijzelf).`);
                        return false;
                    }

                    // Check for schedule conflicts
                    const conflicts = await checkConflicts(
                        form.game_id.value,
                        form.date.value,
                        form.time.value,
                        selectedFriends.map(cb => cb.value)
                    );

                    if (conflicts.error) {
                        alert('Er is een fout opgetreden bij het controleren van conflicten. Probeer het opnieuw.');
                        return false;
                    }

                    if (conflicts.user) {
                        alert('Je hebt al een schema dat overlapt met dit tijdstip. Kies een ander moment.');
                        return false;
                    }

                    if (conflicts.friends && conflicts.friends.length > 0) {
                        const friendConflicts = conflicts.friends
                            .map(f => f.username)
                            .join(', ');
                        alert(`De volgende vrienden hebben al een schema dat overlapt: ${friendConflicts}`);
                        return false;
                    }

                    return true;
                }

                function filterFriends(searchText) {
                    const friendItems = document.getElementsByClassName('friend-item');
                    searchText = searchText.toLowerCase();
                    
                    for (let item of friendItems) {
                        const username = item.querySelector('label').textContent.toLowerCase();
                        item.style.display = username.includes(searchText) ? '' : 'none';
                    }
                }

                // Show session time when game is selected
                document.getElementById('game_id').addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const sessionTime = selectedOption.dataset.sessionTime;
                    if (sessionTime) {
                        document.getElementById('description').placeholder = 
                            `Gemiddelde sessieduur voor deze game is ${sessionTime} minuten. Voeg hier extra details toe...`;
                    }
                });
                </script>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>