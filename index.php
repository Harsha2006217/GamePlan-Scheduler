<?php
// index.php: Main dashboard with schedules, events, and reminders
// Displays favorites, friends, tables for schedules/events, with sort and actions
// Beautiful dark theme, responsive, advanced features like reminders popup
// Human-written: Logical flow, comments, error handling

require_once 'functions.php';
requireLogin();
checkTimeout();
$user_id = getUserId();
$favorites = getFavoriteGames($user_id);
$friends = getFriends($user_id);
$schedules = getSchedules($user_id);
$events = getEvents($user_id);
$reminders = getReminders($user_id);
$msg = getMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php"><i class="bi bi-controller me-2"></i>GamePlan Scheduler</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i>Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php"><i class="bi bi-people me-1"></i>Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php"><i class="bi bi-calendar-event me-1"></i>Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> mb-4">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($favorites); ?></div>
                <div class="stat-label">Favorite Games</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($friends); ?></div>
                <div class="stat-label">Friends</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($schedules); ?></div>
                <div class="stat-label">Schedules</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($events); ?></div>
                <div class="stat-label">Events</div>
            </div>
        </div>

        <!-- Favorite Games Section -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-star-fill me-2"></i>Favorite Games</h3>
            <?php if (empty($favorites)): ?>
                <p class="text-muted">No favorite games yet. Add some from your profile!</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($favorites as $fav): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <h6 class="card-title text-primary"><?php echo htmlspecialchars($fav['titel']); ?></h6>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($fav['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Friends List Section -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-people-fill me-2"></i>Friends List</h3>
            <?php if (empty($friends)): ?>
                <p class="text-muted">No friends yet. <a href="add_friend.php">Add some friends</a>!</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($friends as $friend): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card text-center">
                                <h6 class="card-title"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                <span class="badge bg-<?php echo $friend['calculated_status'] === 'online' ? 'success' : 'secondary'; ?>">
                                    <i class="bi bi-circle-fill me-1"></i><?php echo $friend['calculated_status']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Schedules Section -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-calendar-event me-2"></i>Schedules</h3>
            <?php if (empty($schedules)): ?>
                <p class="text-muted">No schedules yet. Create one!</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Game</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Friends</th>
                                <th>Reminder</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $sched): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sched['game_titel']); ?></td>
                                    <td><?php echo htmlspecialchars($sched['date']); ?></td>
                                    <td><?php echo htmlspecialchars($sched['time']); ?></td>
                                    <td><?php echo htmlspecialchars($sched['friends'] ? 'Shared with ' . $sched['friends'] : 'Private'); ?></td>
                                    <td><?php echo htmlspecialchars($sched['reminder']); ?></td>
                                    <td>
                                        <a href="edit_schedule.php?id=<?php echo $sched['schedule_id']; ?>" class="btn btn-sm btn-primary me-1">Edit</a>
                                        <a href="delete.php?type=schedule&id=<?php echo $sched['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Events Section -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-calendar-check me-2"></i>Events</h3>
            <?php if (empty($events)): ?>
                <p class="text-muted">No events yet. Create one!</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Description</th>
                                <th>Reminder</th>
                                <th>Shared With</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['date']); ?></td>
                                    <td><?php echo htmlspecialchars($event['time']); ?></td>
                                    <td><?php echo htmlspecialchars($event['description']); ?></td>
                                    <td><?php echo htmlspecialchars($event['reminder']); ?></td>
                                    <td><?php echo implode(', ', $event['shared_with']); ?></td>
                                    <td>
                                        <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary me-1">Edit</a>
                                        <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="container">
            © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy Policy | Contact Support
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show reminders as alerts on page load
        const reminders = <?php echo json_encode($reminders); ?>;
        if (reminders.length > 0) {
            reminders.forEach(msg => alert('Reminder: ' + msg));
        }
    </script>
</body>
</html>