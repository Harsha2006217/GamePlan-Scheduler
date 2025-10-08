<?php
// index.php - Advanced Dashboard
// Author: Harsha Kanaparthi
// Date: 30-09-2025

require_once 'functions.php';
checkSessionTimeout();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
updateLastActivity($userId);

// Get all data for dashboard
$friends = getFriends($userId);
$favorites = getFavoriteGames($userId);
$schedules = getSchedules($userId);
$events = getEvents($userId);
$calendarItems = getCalendarItems($userId);
$reminders = getReminders($userId);

// Statistics
$stats = [
    'friends' => count($friends),
    'favorites' => count($favorites),
    'schedules' => count($schedules),
    'events' => count($events),
    'upcoming' => count(array_filter($calendarItems, function($item) {
        return strtotime($item['date'] . ' ' . $item['time']) >= time();
    }))
];

// Sort options
$sortSchedules = $_GET['sort_schedules'] ?? 'date ASC, time ASC';
$sortEvents = $_GET['sort_events'] ?? 'date ASC, time ASC';
?>
<?php include 'header.php'; ?>

<div class="container-fluid">
    <?php echo getMessage(); ?>
    
    <!-- Dashboard Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">Welcome back, <?php echo safeEcho($_SESSION['username']); ?>! 👋</h1>
                    <p class="text-muted">Manage your gaming schedule and connect with friends</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-success fs-6">
                        <i class="fas fa-circle me-1"></i>Online
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-5">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="advanced-card text-center p-4">
                <div class="stats-number"><?php echo $stats['friends']; ?></div>
                <div class="stats-label">Friends</div>
                <i class="fas fa-users fs-1 text-primary mt-3"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="advanced-card text-center p-4">
                <div class="stats-number"><?php echo $stats['favorites']; ?></div>
                <div class="stats-label">Favorite Games</div>
                <i class="fas fa-heart fs-1 text-danger mt-3"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="advanced-card text-center p-4">
                <div class="stats-number"><?php echo $stats['schedules']; ?></div>
                <div class="stats-label">Schedules</div>
                <i class="fas fa-calendar-day fs-1 text-warning mt-3"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="advanced-card text-center p-4">
                <div class="stats-number"><?php echo $stats['events']; ?></div>
                <div class="stats-label">Events</div>
                <i class="fas fa-calendar-alt fs-1 text-info mt-3"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="advanced-card text-center p-4">
                <div class="stats-number"><?php echo $stats['upcoming']; ?></div>
                <div class="stats-label">Upcoming</div>
                <i class="fas fa-clock fs-1 text-success mt-3"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="advanced-card text-center p-4">
                <div class="stats-number"><?php echo count($reminders); ?></div>
                <div class="stats-label">Reminders</div>
                <i class="fas fa-bell fs-1 text-purple mt-3"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Friends Section -->
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="advanced-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Friends List
                    </h5>
                    <a href="friends.php" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-plus me-1"></i>Manage
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($friends)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No friends added yet</p>
                            <a href="add_friend.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i>Add Your First Friend
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-advanced table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($friends as $friend): ?>
                                        <tr class="slide-in-left">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                                    <strong><?php echo safeEcho($friend['username']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $friend['status'] === 'Online' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $friend['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo safeEcho($friend['note'] ?: 'No note'); ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit_friend.php?id=<?php echo $friend['friend_id']; ?>" 
                                                       class="btn btn-warning" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Edit Note">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?type=friend&id=<?php echo $friend['friend_id']; ?>" 
                                                       class="btn btn-danger advanced-confirm"
                                                       data-confirm-message="Are you sure you want to remove this friend?"
                                                       data-confirm-action="Remove Friend">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Favorite Games Section -->
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="advanced-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-heart me-2"></i>Favorite Games
                    </h5>
                    <a href="profile.php" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-cog me-1"></i>Manage
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($favorites)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-gamepad fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No favorite games added yet</p>
                            <a href="profile.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add Favorite Game
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-advanced table-hover">
                                <thead>
                                    <tr>
                                        <th>Game Title</th>
                                        <th>Description</th>
                                        <th>Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($favorites as $game): ?>
                                        <tr class="slide-in-left">
                                            <td>
                                                <strong><?php echo safeEcho($game['titel']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo safeEcho($game['description'] ?: 'No description'); ?></span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo safeEcho($game['note'] ?: 'No note'); ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit_favorite.php?id=<?php echo $game['user_game_id']; ?>" 
                                                       class="btn btn-warning"
                                                       data-bs-toggle="tooltip"
                                                       title="Edit Game">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?type=favorite&id=<?php echo $game['user_game_id']; ?>" 
                                                       class="btn btn-danger advanced-confirm"
                                                       data-confirm-message="Are you sure you want to remove this game from favorites?"
                                                       data-confirm-action="Remove Game">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Schedules Section -->
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="advanced-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>Game Schedules
                    </h5>
                    <div>
                        <a href="?sort_schedules=date ASC, time ASC" class="btn btn-sm btn-outline-light me-1">↑ Date</a>
                        <a href="?sort_schedules=date DESC, time DESC" class="btn btn-sm btn-outline-light me-1">↓ Date</a>
                        <a href="add_schedule.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Add
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($schedules)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-plus fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No schedules created yet</p>
                            <a href="add_schedule.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Create Schedule
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-advanced table-hover">
                                <thead>
                                    <tr>
                                        <th>Game</th>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Friends</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr class="slide-in-left">
                                            <td>
                                                <strong><?php echo safeEcho($schedule['game_title']); ?></strong>
                                            </td>
                                            <td><?php echo safeEcho($schedule['schedule_title']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo date('M j, Y', strtotime($schedule['date'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo date('g:i A', strtotime($schedule['time'])); ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo safeEcho($schedule['friends_list'] ?: 'None'); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                                       class="btn btn-warning"
                                                       data-bs-toggle="tooltip"
                                                       title="Edit Schedule">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?type=schedule&id=<?php echo $schedule['schedule_id']; ?>" 
                                                       class="btn btn-danger advanced-confirm"
                                                       data-confirm-message="Are you sure you want to delete this schedule?"
                                                       data-confirm-action="Delete Schedule">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Events Section -->
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="advanced-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Events
                    </h5>
                    <div>
                        <a href="?sort_events=date ASC, time ASC" class="btn btn-sm btn-outline-light me-1">↑ Date</a>
                        <a href="?sort_events=date DESC, time DESC" class="btn btn-sm btn-outline-light me-1">↓ Date</a>
                        <a href="add_event.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Add
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($events)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No events created yet</p>
                            <a href="add_event.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Create Event
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-advanced table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Reminder</th>
                                        <th>Shared</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $event): ?>
                                        <tr class="slide-in-left">
                                            <td>
                                                <strong><?php echo safeEcho($event['event_title']); ?></strong>
                                                <?php if ($event['game_title']): ?>
                                                    <br><small class="text-muted">Game: <?php echo safeEcho($event['game_title']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo date('M j, Y', strtotime($event['date'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo date('g:i A', strtotime($event['time'])); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($event['reminder'] !== 'none'): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-bell me-1"></i><?php echo str_replace('_', ' ', $event['reminder']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo safeEcho($event['shared_with'] ?: 'None'); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" 
                                                       class="btn btn-warning"
                                                       data-bs-toggle="tooltip"
                                                       title="Edit Event">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" 
                                                       class="btn btn-danger advanced-confirm"
                                                       data-confirm-message="Are you sure you want to delete this event?"
                                                       data-confirm-action="Delete Event">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Overview -->
    <div class="row">
        <div class="col-12">
            <div class="advanced-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar me-2"></i>Calendar Overview
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($calendarItems)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No schedules or events to display</p>
                            <p class="text-muted">Add some schedules or events to see them here</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($calendarItems as $item): ?>
                                <div class="col-xl-4 col-md-6 mb-3">
                                    <div class="calendar-item <?php echo $item['type']; ?> fade-in">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">
                                                <i class="fas fa-<?php echo $item['type'] === 'event' ? 'calendar-alt' : 'calendar-day'; ?> me-2"></i>
                                                <?php echo safeEcho($item['title']); ?>
                                            </h6>
                                            <span class="badge bg-<?php echo $item['type'] === 'event' ? 'success' : 'primary'; ?>">
                                                <?php echo ucfirst($item['type']); ?>
                                            </span>
                                        </div>
                                        <p class="mb-1">
                                            <i class="fas fa-clock me-1 text-muted"></i>
                                            <?php echo date('M j, Y', strtotime($item['date'])); ?> at 
                                            <?php echo date('g:i A', strtotime($item['time'])); ?>
                                        </p>
                                        <?php if ($item['game_title']): ?>
                                            <p class="mb-1">
                                                <i class="fas fa-gamepad me-1 text-muted"></i>
                                                <?php echo safeEcho($item['game_title']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($item['description']): ?>
                                            <p class="mb-2 text-muted small"><?php echo safeEcho($item['description']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($item['reminder'] && $item['reminder'] !== 'none'): ?>
                                            <p class="mb-1">
                                                <i class="fas fa-bell me-1 text-warning"></i>
                                                Reminder: <?php echo str_replace('_', ' ', $item['reminder']); ?> before
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($item['shared_with']): ?>
                                            <p class="mb-0">
                                                <i class="fas fa-share-alt me-1 text-info"></i>
                                                Shared with: <?php echo safeEcho($item['shared_with']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reminders Data for JavaScript -->
<script id="remindersData" type="application/json">
<?php echo json_encode($reminders); ?>
</script>

<?php include 'footer.php'; ?>