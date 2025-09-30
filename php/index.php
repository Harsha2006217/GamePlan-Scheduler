<?php
/**
 * GamePlan Scheduler - Dashboard/Index Page
 * 
 * Main dashboard with statistics, upcoming activities, and navigation to all features.
 * 
 * @author Harsha Kanaparthi
 * @version 1.0
 * @date 2025-09-30
 */

require_once 'db.php';
require_once 'functions.php';

// Require login
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];
$username = $currentUser['username'];

// Get dashboard statistics
$stats = getDashboardStats($userId);

// Get upcoming activities
$upcomingActivities = getUpcomingActivities($userId, 5);

// Get flash messages
$flashMessages = getFlashMessages();

// Get favorite games
$favorites = getFavoriteGames($userId);

// Get friends
$friends = getFriends($userId);

// Get schedules
$schedules = getSchedules($userId);

// Get events
$events = getEvents($userId);

// Merge schedules and events for calendar
$calendarItems = array_merge($schedules, $events);
usort($calendarItems, function($a, $b) {
    $aTime = strtotime($a['date'] . ' ' . $a['time']);
    $bTime = strtotime($b['date'] . ' ' . $b['time']);
    return $aTime - $bTime;
});

// Page title and meta
$pageTitle = "Dashboard - GamePlan Scheduler";
$pageDescription = "Manage your gaming schedule, events, and connect with friends.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../css/style.css" rel="stylesheet">
    
    <!-- Additional Dashboard Styles -->
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: white;
        }
        
        .navbar {
            background: rgba(0, 0, 0, 0.3) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .navbar-brand {
            color: #00d4ff !important;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: #00d4ff !important;
            transform: translateY(-1px);
        }
        
        .dashboard-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.2);
            border-color: rgba(0, 212, 255, 0.3);
        }
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #00d4ff;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .activity-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid #00d4ff;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .activity-time {
            color: #00d4ff;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .activity-title {
            color: white;
            margin: 0.25rem 0;
            font-weight: 500;
        }
        
        .activity-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            margin: 0;
        }
        
        .quick-action-btn {
            background: linear-gradient(45deg, #00d4ff, #0099cc);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0.25rem;
        }
        
        .quick-action-btn:hover {
            background: linear-gradient(45deg, #0099cc, #00d4ff);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.2), rgba(0, 153, 204, 0.2));
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        
        .section-title {
            color: #00d4ff;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.5rem;
        }
        
        .no-activities {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-style: italic;
            padding: 2rem;
        }
        
        .badge-notification {
            background: #ff4757;
            color: white;
            border-radius: 10px;
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #00d4ff, #0099cc);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gamepad me-2"></i>GamePlan
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">
                            <i class="fas fa-calendar-alt me-1"></i>Schedules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="fas fa-calendar-check me-1"></i>Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="friends.php">
                            <i class="fas fa-users me-1"></i>Friends
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($username); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="privacy.php">
                                <i class="fas fa-shield-alt me-2"></i>Privacy
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Flash Messages -->
        <?php foreach ($flashMessages as $message): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card welcome-card p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h2 mb-2">Welcome back, <?php echo htmlspecialchars($username); ?>! 🎮</h1>
                            <p class="lead mb-3">Ready to level up your gaming schedule? Check out your dashboard below.</p>
                            <div class="d-flex flex-wrap">
                                <a href="add_schedule.php" class="quick-action-btn">
                                    <i class="fas fa-plus me-2"></i>Quick Schedule
                                </a>
                                <a href="add_event.php" class="quick-action-btn">
                                    <i class="fas fa-calendar-plus me-2"></i>Create Event
                                </a>
                                <a href="add_friend.php" class="quick-action-btn">
                                    <i class="fas fa-user-plus me-2"></i>Add Friend
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-rocket text-primary" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="dashboard-card stat-card">
                    <div class="stat-number"><?php echo $stats['friends']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-users me-1"></i>Friends
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="dashboard-card stat-card">
                    <div class="stat-number"><?php echo $stats['games']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-gamepad me-1"></i>Games
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="dashboard-card stat-card">
                    <div class="stat-number"><?php echo $stats['schedules']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-calendar-alt me-1"></i>Schedules
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="dashboard-card stat-card">
                    <div class="stat-number"><?php echo $stats['events']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-calendar-check me-1"></i>Events
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="row">
            <!-- Upcoming Activities -->
            <div class="col-lg-8 mb-4">
                <div class="dashboard-card p-4">
                    <h3 class="section-title">
                        <i class="fas fa-clock"></i>Upcoming Activities
                    </h3>
                    
                    <?php if (empty($upcomingActivities)): ?>
                        <div class="no-activities">
                            <i class="fas fa-calendar-times fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p>No upcoming activities scheduled.</p>
                            <p>Create your first <a href="add_schedule.php" class="text-primary">schedule</a> or <a href="add_event.php" class="text-primary">event</a> to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcomingActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <div class="activity-time">
                                            <i class="fas fa-<?php echo $activity['type'] === 'schedule' ? 'calendar-alt' : 'calendar-check'; ?> me-2"></i>
                                            <?php echo formatDate($activity['date_time'], 'M j, H:i'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                        <?php if (!empty($activity['description'])): ?>
                                            <p class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <span class="badge bg-<?php echo $activity['type'] === 'schedule' ? 'primary' : 'success'; ?>">
                                            <?php echo ucfirst($activity['type']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-3">
                            <a href="<?php echo count($upcomingActivities) > 3 ? 'schedules.php' : 'events.php'; ?>" class="btn btn-outline-primary">
                                View All Activities
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions & Info -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="dashboard-card p-4 mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-lightning-bolt"></i>Quick Actions
                    </h3>
                    
                    <div class="d-grid gap-2">
                        <a href="add_schedule.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>New Schedule
                        </a>
                        <a href="add_event.php" class="btn btn-outline-success">
                            <i class="fas fa-calendar-plus me-2"></i>Create Event
                        </a>
                        <a href="friends.php" class="btn btn-outline-info">
                            <i class="fas fa-users me-2"></i>Manage Friends
                        </a>
                        <a href="profile.php" class="btn btn-outline-warning">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>

                <!-- Gaming Tips -->
                <div class="dashboard-card p-4">
                    <h3 class="section-title">
                        <i class="fas fa-lightbulb"></i>Gaming Tips
                    </h3>
                    
                    <div class="mb-3">
                        <h6 class="text-primary">📅 Schedule Smart</h6>
                        <small class="text-muted">Plan gaming sessions during your peak energy hours for better performance.</small>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-primary">👥 Team Up</h6>
                        <small class="text-muted">Invite friends to events for more engaging multiplayer experiences.</small>
                    </div>
                    
                    <div>
                        <h6 class="text-primary">⏰ Take Breaks</h6>
                        <small class="text-muted">Remember to schedule breaks between long gaming sessions.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Favorite Games & Friends -->
        <div class="row mb-4">
            <!-- Favorite Games -->
            <div class="col-md-4">
                <div class="dashboard-card p-4">
                    <h3 class="section-title">
                        <i class="fas fa-star"></i>Favorite Games
                    </h3>
                    
                    <?php if (empty($favorites)): ?>
                        <div class="no-activities">
                            <i class="fas fa-heart-broken fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p>No favorite games added yet.</p>
                            <p>Discover new games and add your favorites to this list!</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($favorites as $fav): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($fav['title']); ?></strong>
                                        <p class="mb-0 text-muted" style="font-size: 0.9rem;"><?php echo htmlspecialchars($fav['description']); ?></p>
                                    </div>
                                    <a href="remove_favorite.php?id=<?php echo $fav['id']; ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Friends List -->
            <div class="col-md-4">
                <div class="dashboard-card p-4">
                    <h3 class="section-title">
                        <i class="fas fa-users"></i>Friends
                    </h3>
                    
                    <?php if (empty($friends)): ?>
                        <div class="no-activities">
                            <i class="fas fa-user-friends fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p>No friends added yet.</p>
                            <p>Connect with other players and add them as friends!</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($friends as $friend): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($friend['username']); ?></strong>
                                        <span class="badge <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?>
                                        </span>
                                    </div>
                                    <a href="remove_friend.php?id=<?php echo $friend['id']; ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar Overview -->
            <div class="col-md-4">
                <div class="dashboard-card p-4">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-alt"></i>Calendar Overview
                    </h3>
                    
                    <?php if (empty($calendarItems)): ?>
                        <div class="no-activities">
                            <i class="fas fa-calendar-times fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p>No schedules or events planned.</p>
                            <p>Create new schedules or events to see them here!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($calendarItems as $item): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['title'] ?? $item['game_titel']); ?></strong>
                                            <p class="mb-0 text-muted" style="font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($item['date']); ?> at <?php echo htmlspecialchars($item['time']); ?>
                                            </p>
                                        </div>
                                        <a href="edit_activity.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="../js/script.js"></script>
    
    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000);
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Welcome animation
        document.addEventListener('DOMContentLoaded', function() {
            const welcomeCard = document.querySelector('.welcome-card');
            welcomeCard.style.opacity = '0';
            welcomeCard.style.transform = 'translateY(20px)';
            
            setTimeout(function() {
                welcomeCard.style.transition = 'all 0.6s ease';
                welcomeCard.style.opacity = '1';
                welcomeCard.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>