<?php
// GamePlan Scheduler - Professional Schedule Creation
// Advanced form for creating new gaming session schedules

require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$friends = getFriends($userId);
$games = getGames();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gameId = (int)($_POST['game_id'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $friendsSelected = $_POST['friends'] ?? [];

    try {
        // Validate input
        if (empty($gameId) || empty($date) || empty($time)) {
            throw new Exception("Game, date, and time are required");
        }

        $scheduleDateTime = strtotime("$date $time");
        if ($scheduleDateTime === false || $scheduleDateTime <= time()) {
            throw new Exception("Schedule must be in the future");
        }

        // Convert friends array to string
        $friendsString = '';
        if (!empty($friendsSelected) && is_array($friendsSelected)) {
            $validFriends = [];
            foreach ($friendsSelected as $friendId) {
                foreach ($friends as $friend) {
                    if ($friend['user_id'] == $friendId) {
                        $validFriends[] = $friend['username'];
                        break;
                    }
                }
            }
            $friendsString = implode(', ', $validFriends);
        }

        // Create schedule
        $scheduleId = addSchedule($userId, $gameId, $date, $time, $friendsString);

        $_SESSION['message'] = 'Schedule created successfully!';
        $_SESSION['message_type'] = 'success';

        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Add Schedule</title>
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
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="friends.php">Friends</a></li>
                        <li><a href="schedules.php" class="active">Schedules</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="?logout=1">Logout</a></li>
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
                            <h2><i class="fas fa-calendar-plus"></i> Create Gaming Schedule</h2>
                            <p class="text-muted mb-0">Plan your next gaming session with friends</p>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="game_id" class="form-label">
                                            <i class="fas fa-gamepad"></i> Game *
                                        </label>
                                        <select class="form-select" id="game_id" name="game_id" required>
                                            <option value="">Select a game...</option>
                                            <?php foreach ($games as $game): ?>
                                                <option value="<?php echo $game['game_id']; ?>">
                                                    <?php echo htmlspecialchars($game['titel']); ?> (<?php echo htmlspecialchars($game['genre']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a game.
                                        </div>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="date" class="form-label">
                                            <i class="fas fa-calendar"></i> Date *
                                        </label>
                                        <input type="date" class="form-control" id="date" name="date"
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">
                                            Please select a future date.
                                        </div>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="time" class="form-label">
                                            <i class="fas fa-clock"></i> Time *
                                        </label>
                                        <input type="time" class="form-control" id="time" name="time" required>
                                        <div class="invalid-feedback">
                                            Please select a time.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-users"></i> Invite Friends (Optional)
                                    </label>
                                    <div class="row">
                                        <?php if (empty($friends)): ?>
                                            <div class="col-12">
                                                <p class="text-muted">No friends to invite yet. <a href="friends.php">Add some friends</a> first!</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="col-12 mb-2">
                                                <div class="d-flex gap-2 mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllFriends()">Select All</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllFriends()">Clear All</button>
                                                </div>
                                            </div>
                                            <?php foreach ($friends as $friend): ?>
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="friend_<?php echo $friend['user_id']; ?>"
                                                               name="friends[]" value="<?php echo $friend['user_id']; ?>">
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
                                        <i class="fas fa-calendar-plus"></i> Create Schedule
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="fas fa-lightbulb"></i> Tips for Great Gaming Sessions</h5>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li><strong>Choose the right time:</strong> Pick times when most of your friends are available</li>
                                <li><strong>Plan ahead:</strong> Give your friends enough notice to prepare</li>
                                <li><strong>Be specific:</strong> Include game mode, objectives, or special rules</li>
                                <li><strong>Stay flexible:</strong> Have backup plans in case someone can't make it</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        // Select/clear all friends
        function selectAllFriends() {
            document.querySelectorAll('input[name="friends[]"]').forEach(cb => cb.checked = true);
        }

        function clearAllFriends() {
            document.querySelectorAll('input[name="friends[]"]').forEach(cb => cb.checked = false);
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const gameId = document.getElementById('game_id');
            const date = document.getElementById('date');
            const time = document.getElementById('time');

            let isValid = true;

            if (!gameId.value) {
                gameId.classList.add('is-invalid');
                isValid = false;
            } else {
                gameId.classList.remove('is-invalid');
            }

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

            if (!time.value) {
                time.classList.add('is-invalid');
                isValid = false;
            } else {
                time.classList.remove('is-invalid');
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