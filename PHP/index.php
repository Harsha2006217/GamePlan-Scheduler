<?php
// GamePlan Scheduler - Main Dashboard
// Professional dashboard with calendar view, statistics, and quick actions

require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$user = getCurrentUser();

// Get dashboard data
$friends = getFriends($userId);
$onlineFriends = getOnlineFriends($userId);
$favoriteGames = getFavoriteGames($userId);
$schedules = getSchedules($userId);
$events = getEvents($userId);

// Combine schedules and events for calendar
$calendarItems = [];

// Add schedules to calendar
foreach ($schedules as $schedule) {
    $calendarItems[] = [
        'id' => 'schedule_' . $schedule['schedule_id'],
        'type' => 'schedule',
        'title' => $schedule['game_title'],
        'date' => $schedule['date'],
        'time' => $schedule['time'],
        'friends' => $schedule['friends'],
        'description' => 'Gaming session with friends',
        'color' => '#ff6b35'
    ];
}

// Add events to calendar
foreach ($events as $event) {
    $calendarItems[] = [
        'id' => 'event_' . $event['event_id'],
        'type' => 'event',
        'title' => $event['title'],
        'date' => $event['date'],
        'time' => $event['time'],
        'shared_with' => $event['shared_with'],
        'description' => $event['description'],
        'reminder' => $event['reminder'],
        'color' => '#28a745'
    ];
}

// Sort calendar items by date and time
usort($calendarItems, function($a, $b) {
    $dateA = strtotime($a['date'] . ' ' . $a['time']);
    $dateB = strtotime($b['date'] . ' ' . $b['time']);
    return $dateA <=> $dateB;
});

// Get upcoming items (next 7 days)
$upcomingItems = array_filter($calendarItems, function($item) {
    $itemDate = strtotime($item['date']);
    $weekFromNow = strtotime('+7 days');
    return $itemDate <= $weekFromNow && $itemDate >= time();
});

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: login.php?message=logged_out');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Dashboard</title>
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
                        <li><a href="index.php" class="active">Dashboard</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="friends.php">Friends</a></li>
                        <li><a href="schedules.php">Schedules</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="?logout=1">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-1">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                            <p class="text-muted mb-0">Ready to plan your next gaming session?</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="add_schedule.php" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> New Schedule
                            </a>
                            <a href="add_event.php" class="btn btn-success">
                                <i class="fas fa-trophy"></i> New Event
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <!-- Statistics Dashboard -->
            <div class="dashboard mb-4">
                <div class="card">
                    <h3><i class="fas fa-users"></i> Friends</h3>
                    <p class="h2"><?php echo count($friends); ?></p>
                    <p class="text-muted"><?php echo count($onlineFriends); ?> online now</p>
                </div>
                <div class="card">
                    <h3><i class="fas fa-calendar-check"></i> Schedules</h3>
                    <p class="h2"><?php echo count($schedules); ?></p>
                    <p class="text-muted">Total gaming sessions</p>
                </div>
                <div class="card">
                    <h3><i class="fas fa-trophy"></i> Events</h3>
                    <p class="h2"><?php echo count($events); ?></p>
                    <p class="text-muted">Tournaments & meetups</p>
                </div>
                <div class="card">
                    <h3><i class="fas fa-gamepad"></i> Favorite Games</h3>
                    <p class="h2"><?php echo count($favoriteGames); ?></p>
                    <p class="text-muted">In your library</p>
                </div>
            </div>

            <div class="row">
                <!-- Upcoming Events & Schedules -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock"></i> Upcoming (Next 7 Days)</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcomingItems)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No upcoming events</h5>
                                    <p class="text-muted">Your schedule is clear! Plan your next gaming session.</p>
                                    <a href="add_schedule.php" class="btn btn-primary">Schedule a Game</a>
                                </div>
                            <?php else: ?>
                                <div class="calendar-items">
                                    <?php foreach ($upcomingItems as $item): ?>
                                        <div class="calendar-item mb-3 p-3 border rounded" style="border-left: 4px solid <?php echo $item['color']; ?>;">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-<?php echo $item['type'] == 'schedule' ? 'gamepad' : 'trophy'; ?>"></i>
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-calendar"></i>
                                                        <?php echo date('l, F j, Y', strtotime($item['date'])); ?> at
                                                        <?php echo date('g:i A', strtotime($item['time'])); ?>
                                                    </p>
                                                    <?php if (!empty($item['friends']) || !empty($item['shared_with'])): ?>
                                                        <p class="mb-1 small">
                                                            <i class="fas fa-users"></i>
                                                            <?php
                                                            $participants = !empty($item['friends']) ? $item['friends'] : $item['shared_with'];
                                                            echo htmlspecialchars($participants);
                                                            ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['description'])): ?>
                                                        <p class="mb-0 small text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <?php if ($item['type'] == 'schedule'): ?>
                                                        <a href="edit_schedule.php?id=<?php echo str_replace('schedule_', '', $item['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="edit_event.php?id=<?php echo str_replace('event_', '', $item['id']); ?>" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($item['reminder'] && $item['reminder'] !== 'none'): ?>
                                                <div class="mt-2">
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-bell"></i>
                                                        Reminder: <?php echo $item['reminder'] == '1hour' ? '1 hour' : '1 day'; ?> before
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Online Friends -->
                    <?php if (!empty($onlineFriends)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-circle text-success"></i> Online Friends</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($onlineFriends as $friend): ?>
                                        <div class="badge bg-success p-2">
                                            <i class="fas fa-circle"></i> <?php echo htmlspecialchars($friend['username']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Favorite Games -->
                    <?php if (!empty($favoriteGames)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-heart"></i> Your Favorite Games</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach (array_slice($favoriteGames, 0, 6) as $game): ?>
                                        <div class="col-6 mb-2">
                                            <div class="text-center">
                                                <i class="fas fa-gamepad fa-2x text-primary mb-1"></i>
                                                <p class="small mb-0"><?php echo htmlspecialchars($game['titel']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($favoriteGames) > 6): ?>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">And <?php echo count($favoriteGames) - 6; ?> more...</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="add_schedule.php" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-plus"></i> Schedule Game
                                </a>
                                <a href="add_event.php" class="btn btn-outline-success">
                                    <i class="fas fa-trophy"></i> Create Event
                                </a>
                                <a href="friends.php" class="btn btn-outline-info">
                                    <i class="fas fa-user-plus"></i> Add Friends
                                </a>
                                <a href="profile.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-user-edit"></i> Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <?php if (!empty($calendarItems)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-history"></i> All Scheduled Items</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Title</th>
                                                <th>Date & Time</th>
                                                <th>Participants</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($calendarItems as $item): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge" style="background-color: <?php echo $item['color']; ?>;">
                                                            <i class="fas fa-<?php echo $item['type'] == 'schedule' ? 'gamepad' : 'trophy'; ?>"></i>
                                                            <?php echo ucfirst($item['type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                                    <td>
                                                        <?php echo date('M j, Y g:i A', strtotime($item['date'] . ' ' . $item['time'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $participants = !empty($item['friends']) ? $item['friends'] : (!empty($item['shared_with']) ? $item['shared_with'] : 'Solo/Private');
                                                        echo htmlspecialchars($participants);
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($item['type'] == 'schedule'): ?>
                                                            <a href="edit_schedule.php?id=<?php echo str_replace('schedule_', '', $item['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="edit_event.php?id=<?php echo str_replace('event_', '', $item['id']); ?>" class="btn btn-sm btn-outline-success">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>

    <!-- Reminder Notifications -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for reminders
            const reminders = document.querySelectorAll('[data-reminder]');
            const now = new Date();

            reminders.forEach(reminder => {
                const reminderTime = new Date(reminder.dataset.reminder);
                const timeDiff = (reminderTime - now) / 1000 / 60; // minutes

                if (timeDiff <= 60 && timeDiff > 0) { // Within next hour
                    setTimeout(() => {
                        showNotification('Reminder', reminder.textContent, 'reminder');
                    }, (timeDiff * 60 * 1000));
                }
            });
        });

        // Auto-refresh online friends every 5 minutes
        setInterval(function() {
            // In a real implementation, this would use AJAX to update the online friends list
            console.log('Checking for online friends updates...');
        }, 300000);
    </script>
</body>
</html>