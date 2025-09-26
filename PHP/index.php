<?php
/**
 * Advanced Dashboard System
 * GamePlan Scheduler - Professional Gaming Dashboard
 * 
 * This module provides comprehensive dashboard functionality with
 * real-time activity tracking, statistics, and quick actions.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

require 'functions.php';

// Advanced security check with session validation
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get comprehensive user data with error handling
    $profile = getProfile($user_id);
    $favorite_games = getFavoriteGames($user_id);
    $upcoming_activities = getUpcomingActivities($user_id, 7);
    $friends = getFriends($user_id);
    $user_stats = getUserStats($user_id);
    
} catch (Exception $e) {
    error_log("Error loading dashboard data: " . $e->getMessage());
    $profile = ['username' => $_SESSION['username'] ?? 'Gebruiker'];
    $favorite_games = [];
    $upcoming_activities = [];
    $friends = [];
    $user_stats = ['friends' => 0, 'active_friends' => 0, 'upcoming_events' => 0, 'upcoming_schedules' => 0];
}

// Process success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>
    
    <main class="container my-4">
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Welcome Section with Advanced Stats -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-gradient" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                    <div class="card-body text-white">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-3">
                                    <i class="fas fa-user-circle me-2"></i>
                                    Welkom terug, <?php echo htmlspecialchars($profile['username']); ?>!
                                </h2>
                                <p class="lead mb-0">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    Klaar voor je volgende gaming sessie?
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="d-flex justify-content-center">
                                    <div class="avatar-placeholder bg-light rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 80px; height: 80px;">
                                        <i class="fas fa-gamepad text-primary fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-3 col-6 text-center border-end border-light">
                                <h4 class="mb-1"><?php echo $profile['friend_count'] ?? 0; ?></h4>
                                <small class="opacity-75">
                                    <i class="fas fa-users me-1"></i>Gaming Vrienden
                                </small>
                            </div>
                            <div class="col-md-3 col-6 text-center border-end border-light">
                                <h4 class="mb-1"><?php echo $user_stats['upcoming_events'] ?? 0; ?></h4>
                                <small class="opacity-75">
                                    <i class="fas fa-trophy me-1"></i>Komende Events
                                </small>
                            </div>
                            <div class="col-md-3 col-6 text-center border-end border-light">
                                <h4 class="mb-1"><?php echo $user_stats['active_friends'] ?? 0; ?></h4>
                                <small class="opacity-75">
                                    <i class="fas fa-circle text-success me-1"></i>Nu Online
                                </small>
                            </div>
                            <div class="col-md-3 col-6 text-center">
                                <h4 class="mb-1"><?php echo count($favorite_games); ?></h4>
                                <small class="opacity-75">
                                    <i class="fas fa-heart me-1"></i>Favoriete Games
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions with Enhanced Design -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <a href="add_schedule.php" class="card text-decoration-none h-100 quick-action-card">
                    <div class="card-body text-center">
                        <div class="quick-action-icon bg-primary mb-3">
                            <i class="fas fa-calendar-plus fa-2x text-white"></i>
                        </div>
                        <h5 class="text-primary">Schema Toevoegen</h5>
                        <p class="text-muted mb-0">Plan een gaming sessie met vrienden</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <a href="add_event.php" class="card text-decoration-none h-100 quick-action-card">
                    <div class="card-body text-center">
                        <div class="quick-action-icon bg-success mb-3">
                            <i class="fas fa-trophy fa-2x text-white"></i>
                        </div>
                        <h5 class="text-success">Event Aanmaken</h5>
                        <p class="text-muted mb-0">Organiseer toernooien en meetups</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <a href="add_friend.php" class="card text-decoration-none h-100 quick-action-card">
                    <div class="card-body text-center">
                        <div class="quick-action-icon bg-info mb-3">
                            <i class="fas fa-user-plus fa-2x text-white"></i>
                        </div>
                        <h5 class="text-info">Vriend Toevoegen</h5>
                        <p class="text-muted mb-0">Breid je gaming netwerk uit</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <a href="profile.php" class="card text-decoration-none h-100 quick-action-card">
                    <div class="card-body text-center">
                        <div class="quick-action-icon bg-warning mb-3">
                            <i class="fas fa-user-cog fa-2x text-white"></i>
                        </div>
                        <h5 class="text-warning">Profiel Beheren</h5>
                        <p class="text-muted mb-0">Bewerk je favoriete games</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Advanced Dashboard Content -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card bg-secondary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Aankomende Activiteiten
                        </h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-light active" onclick="filterActivities('all')">
                                <i class="fas fa-list me-1"></i>Alle
                            </button>
                            <button type="button" class="btn btn-outline-light" onclick="filterActivities('events')">
                                <i class="fas fa-trophy me-1"></i>Events
                            </button>
                            <button type="button" class="btn btn-outline-light" onclick="filterActivities('schedules')">
                                <i class="fas fa-calendar me-1"></i>Schema's
                            </button>
                        </div>
                    </div>
                    <div class="card-body" id="calendar">
                        <?php if (!empty($upcoming_activities)): ?>
                            <div class="timeline">
                                <?php foreach ($upcoming_activities as $activity): ?>
                                    <?php
                                    $activity_datetime = strtotime($activity['date'] . ' ' . $activity['time']);
                                    $is_today = date('Y-m-d') === $activity['date'];
                                    $is_soon = $activity_datetime <= (time() + 3600); // Within 1 hour
                                    $activity_class = $is_today ? 'border-warning' : ($activity['type'] === 'event' ? 'border-success' : 'border-primary');
                                    ?>
                                    <div class="activity-item mb-3 p-3 border-start border-4 <?php echo $activity_class; ?> bg-light text-dark rounded-end" 
                                         data-type="<?php echo $activity['type']; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-<?php echo $activity['type'] === 'event' ? 'trophy' : 'gamepad'; ?> me-2 text-<?php echo $activity['type'] === 'event' ? 'success' : 'primary'; ?>"></i>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($activity['title']); ?>
                                                        <?php if ($is_today): ?>
                                                            <span class="badge bg-warning text-dark ms-2">
                                                                <i class="fas fa-clock me-1"></i>Vandaag
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($is_soon): ?>
                                                            <span class="badge bg-danger ms-2">
                                                                <i class="fas fa-exclamation me-1"></i>Binnenkort
                                                            </span>
                                                        <?php endif; ?>
                                                    </h6>
                                                </div>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('j F Y', strtotime($activity['date'])); ?> om 
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('H:i', strtotime($activity['time'])); ?>
                                                </p>
                                                <?php if (!empty($activity['description'])): ?>
                                                    <p class="mb-2 small text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        <?php echo htmlspecialchars($activity['description']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (!empty($activity['friends'])): ?>
                                                    <div class="mb-2">
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-users me-1"></i>Met vrienden
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (isset($activity['reminder']) && !empty($activity['reminder'])): ?>
                                                    <div class="mb-2">
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-bell me-1"></i><?php echo htmlspecialchars($activity['reminder']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <span class="badge bg-<?php echo $activity['type'] === 'event' ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($activity['type']); ?>
                                                </span>
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <a href="<?php echo $activity['type'] === 'event' ? 'edit_event' : 'edit_schedule'; ?>.php?id=<?php echo $activity['id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm" title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo $activity['type'] === 'event' ? 'delete_event' : 'delete_schedule'; ?>.php?id=<?php echo $activity['id']; ?>" 
                                                       class="btn btn-outline-danger btn-sm" title="Verwijderen"
                                                       onclick="return confirm('Weet je zeker dat je dit <?php echo $activity['type'] === 'event' ? 'evenement' : 'schema'; ?> wilt verwijderen?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                <h4>Geen aankomende activiteiten</h4>
                                <p class="text-muted mb-4">
                                    Plan je eerste gaming sessie of evenement om aan de slag te gaan!
                                </p>
                                <div class="d-flex gap-3 justify-content-center">
                                    <a href="add_schedule.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus me-2"></i>Schema Toevoegen
                                    </a>
                                    <a href="add_event.php" class="btn btn-success">
                                        <i class="fas fa-trophy me-2"></i>Event Aanmaken
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Sidebar -->
            <div class="col-lg-4">
                <!-- Friends List with Advanced Features -->
                <div class="card bg-secondary mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-users me-2"></i>Gaming Vrienden
                        </h6>
                        <a href="friends.php" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-list me-1"></i>Alle
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($friends)): ?>
                            <?php foreach (array_slice($friends, 0, 5) as $friend): ?>
                                <div class="d-flex align-items-center mb-3 p-2 rounded bg-dark">
                                    <div class="position-relative">
                                        <div class="avatar-placeholder bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <span class="position-absolute bottom-0 end-0 translate-middle-x badge rounded-pill bg-<?php echo $friend['status'] === 'online' ? 'success' : 'secondary'; ?>" 
                                              style="font-size: 8px; padding: 2px 4px;">
                                            <i class="fas fa-circle"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($friend['username']); ?></div>
                                        <small class="text-<?php echo $friend['status'] === 'online' ? 'success' : 'muted'; ?>">
                                            <?php if ($friend['status'] === 'online'): ?>
                                                <i class="fas fa-wifi me-1"></i>Nu online
                                            <?php else: ?>
                                                <i class="fas fa-clock me-1"></i>Offline
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php if ($friend['status'] === 'online'): ?>
                                        <button class="btn btn-sm btn-success" onclick="inviteToPlay('<?php echo $friend['user_id']; ?>')" title="Uitnodigen om te spelen">
                                            <i class="fas fa-gamepad"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($friends) > 5): ?>
                                <div class="text-center">
                                    <small class="text-muted">en <?php echo (count($friends) - 5); ?> meer...</small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-user-friends text-muted fa-2x mb-2"></i>
                                <p class="text-muted mb-2">Nog geen vrienden toegevoegd</p>
                                <a href="add_friend.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-user-plus me-1"></i>Vriend Toevoegen
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Favorite Games -->
                <div class="card bg-secondary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-gamepad me-2"></i>Favoriete Games
                        </h6>
                        <a href="profile.php" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-cog me-1"></i>Beheren
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($favorite_games)): ?>
                            <?php foreach (array_slice($favorite_games, 0, 4) as $game): ?>
                                <div class="d-flex align-items-center mb-3 p-2 border rounded bg-dark">
                                    <div class="me-3">
                                        <div class="game-icon bg-gradient rounded d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px; background: linear-gradient(45deg, #007bff, #0056b3);">
                                            <i class="fas fa-gamepad text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($game['titel']); ?></div>
                                        <small class="text-muted">
                                            <?php if (!empty($game['genre'])): ?>
                                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($game['genre']); ?>
                                            <?php else: ?>
                                                <i class="fas fa-gamepad me-1"></i>Game
                                            <?php endif; ?>
                                        </small>
                                        <?php if (!empty($game['rating'])): ?>
                                            <div class="mt-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $game['rating'] ? 'text-warning' : 'text-muted'; ?>" style="font-size: 10px;"></i>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <a href="add_schedule.php?game=<?php echo $game['game_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Schema maken met dit game">
                                            <i class="fas fa-calendar-plus"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($favorite_games) > 4): ?>
                                <div class="text-center">
                                    <small class="text-muted">en <?php echo (count($favorite_games) - 4); ?> meer games...</small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-gamepad text-muted fa-2x mb-2"></i>
                                <p class="text-muted mb-2">Geen favoriete games gekozen</p>
                                <a href="profile.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i>Games Toevoegen
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gaming Statistics Dashboard -->
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-chart-bar me-2"></i>Deze Week</h5>
                        <div class="row">
                            <div class="col-6">
                                <small>Geplande Sessies:</small>
                                <div class="h4"><?php echo $user_stats['upcoming_schedules'] ?? 0; ?></div>
                            </div>
                            <div class="col-6">
                                <small>Komende Events:</small>
                                <div class="h4"><?php echo $user_stats['upcoming_events'] ?? 0; ?></div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: <?php echo min(100, ($user_stats['upcoming_schedules'] ?? 0) * 20); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-trophy me-2"></i>Gaming Prestaties</h5>
                        <div class="row">
                            <div class="col-6">
                                <small>Actieve Vrienden:</small>
                                <div class="h4"><?php echo $user_stats['active_friends'] ?? 0; ?></div>
                            </div>
                            <div class="col-6">
                                <small>Game Collectie:</small>
                                <div class="h4"><?php echo count($favorite_games); ?></div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: <?php echo min(100, count($favorite_games) * 10); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="bg-dark text-white text-center p-3 mt-5">
        <div class="container">
            <p class="mb-0">
                © 2025 GamePlan Scheduler door Harsha Kanaparthi | 
                <a href="privacy.php" class="text-white text-decoration-none">Privacybeleid</a> | 
                <a href="contact.php" class="text-white text-decoration-none">Contact</a>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    
    <!-- Reminder System -->
    <script>
        // Enhanced reminder system
        document.addEventListener('DOMContentLoaded', function() {
            // Check for upcoming reminders
            <?php foreach ($upcoming_activities as $activity): ?>
                <?php if (isset($activity['reminder']) && !empty($activity['reminder'])): ?>
                    const activityDate<?php echo $activity['id']; ?> = new Date('<?php echo $activity['date']; ?> <?php echo $activity['time']; ?>');
                    const now = new Date();
                    const timeUntil = activityDate<?php echo $activity['id']; ?>.getTime() - now.getTime();
                    
                    // Check if reminder should be shown (within 1 hour for "1 uur ervoor", 1 day for "1 dag ervoor")
                    <?php if ($activity['reminder'] === '1 uur ervoor'): ?>
                        if (timeUntil > 0 && timeUntil <= 3600000) { // 1 hour in milliseconds
                            setTimeout(() => {
                                showNotification('Herinnering', '<?php echo addslashes($activity['title']); ?> begint over 1 uur!');
                            }, 1000);
                        }
                    <?php elseif ($activity['reminder'] === '1 dag ervoor'): ?>
                        if (timeUntil > 0 && timeUntil <= 86400000) { // 1 day in milliseconds
                            setTimeout(() => {
                                showNotification('Herinnering', '<?php echo addslashes($activity['title']); ?> is morgen!');
                            }, 1000);
                        }
                    <?php elseif ($activity['reminder'] === '15 minuten ervoor'): ?>
                        if (timeUntil > 0 && timeUntil <= 900000) { // 15 minutes in milliseconds
                            setTimeout(() => {
                                showNotification('Herinnering', '<?php echo addslashes($activity['title']); ?> begint over 15 minuten!');
                            }, 1000);
                        }
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            
            // Request notification permission
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        });

        function showNotification(title, message) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('GamePlan Scheduler', {
                    body: `${title} - ${message}`,
                    icon: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjMDA3YmZmIiBkPSJNMTIgMkM2LjQ4IDIgMiA2LjQ4IDIgMTJzNC40OCAxMCAxMCAxMCAxMC00LjQ4IDEwLTEwUzE3LjUyIDIgMTIgMnptLTIgMTVsLTUtNSAxLjQxLTEuNDFMMTAgMTQuMTdsNy41OS03LjU5TDE5IDhsLTkgOXoiLz48L3N2Zz4=',
                    tag: 'gameplan-reminder'
                });
            } else if ('Notification' in window && Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        showNotification(title, message);
                    }
                });
            } else {
                // Fallback to toast notification
                showToast(title, message, 'warning');
            }
        }
        
        // Activity filtering
        function filterActivities(type) {
            const activities = document.querySelectorAll('.activity-item');
            const buttons = document.querySelectorAll('.btn-group .btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            activities.forEach(activity => {
                const activityType = activity.dataset.type || 'schedule';
                if (type === 'all' || type === activityType + 's') {
                    activity.style.display = 'block';
                    setTimeout(() => {
                        activity.style.opacity = '1';
                        activity.style.transform = 'translateX(0)';
                    }, 50);
                } else {
                    activity.style.opacity = '0';
                    activity.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        activity.style.display = 'none';
                    }, 300);
                }
            });
        }
        
        // Invite friend to play functionality
        function inviteToPlay(friendId) {
            if (confirm('Wil je deze vriend uitnodigen om te spelen?')) {
                fetch('invite_friend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        friend_id: friendId,
                        action: 'invite_to_play'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Uitnodiging verzonden!', 'Je vriend heeft een uitnodiging ontvangen.', 'success');
                    } else {
                        showToast('Fout', 'Er ging iets fout bij het verzenden van de uitnodiging.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Fout', 'Er ging iets fout bij het verzenden van de uitnodiging.', 'error');
                });
            }
        }
        
        // Show toast notification
        function showToast(title, message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container') || createToastContainer();
            const toastId = 'toast-' + Date.now();
            
            const toastHTML = `
                <div class="toast align-items-center text-bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'}" 
                     role="alert" id="${toastId}">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}</strong><br>
                            ${message}
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remove toast after it's hidden
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }
        
        // Create toast container if it doesn't exist
        function createToastContainer() {
            const container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }
        
        // Set activity type data attributes for filtering
        document.addEventListener('DOMContentLoaded', function() {
            const activities = document.querySelectorAll('.activity-item');
            activities.forEach(activity => {
                const badge = activity.querySelector('.badge');
                if (badge) {
                    const type = badge.textContent.toLowerCase();
                    activity.dataset.type = type;
                }
            });
        });
        
        // Add hover effects for quick action cards
        document.addEventListener('DOMContentLoaded', function() {
            const quickActionCards = document.querySelectorAll('.quick-action-card');
            
            quickActionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.transition = 'all 0.3s ease';
                    this.style.boxShadow = '0 8px 25px rgba(0,123,255,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>