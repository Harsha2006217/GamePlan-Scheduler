<?php
/**
 * GamePlan Scheduler - Professional Gaming Dashboard
 * Advanced main dashboard with real-time statistics and gaming features
 * 
 * @author Harsha Kanaparthi
 * @version 2.1 Professional Edition
 * @date September 30, 2025
 * @description Main dashboard with activity feeds, statistics, and gaming community features
 */

// Enable comprehensive error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'db.php';
require_once 'functions.php';

// Initialize session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, redirect if not
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// Get current user information
$currentUser = getCurrentUser();
$user_id = $currentUser['user_id'];
$username = $currentUser['username'];
$first_name = $currentUser['first_name'] ?? '';
$timezone = $currentUser['timezone'] ?? 'America/New_York';

// Update user activity
updateUserActivity();

// Initialize dashboard data
$dashboard_data = [
    'stats' => [],
    'upcoming_events' => [],
    'recent_activity' => [],
    'friends' => [],
    'favorite_games' => [],
    'quick_stats' => []
];

try {
    $db = getDBConnection();
    
    // Get dashboard statistics
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM Schedules WHERE user_id = ? AND date >= CURDATE()) as upcoming_schedules,
            (SELECT COUNT(*) FROM Events e JOIN EventUserMap eum ON e.event_id = eum.event_id 
             WHERE eum.user_id = ? AND e.date >= CURDATE()) as upcoming_events,
            (SELECT COUNT(*) FROM Friends WHERE (user_id = ? OR friend_user_id = ?) AND status = 'accepted') as total_friends,
            (SELECT COUNT(*) FROM UserGames WHERE user_id = ?) as favorite_games,
            (SELECT COUNT(DISTINCT g.game_id) FROM Games g 
             JOIN UserGames ug ON g.game_id = ug.game_id 
             JOIN Friends f ON (f.user_id = ? AND f.friend_user_id = ug.user_id) OR 
                              (f.friend_user_id = ? AND f.user_id = ug.user_id)
             WHERE f.status = 'accepted') as shared_games
    ";
    
    $stmt = $db->prepare($stats_query);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $dashboard_data['quick_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get upcoming events and schedules
    $upcoming_query = "
        (SELECT 'event' as type, e.title, e.description, e.date, e.time, e.location, 
                'event' as category, e.event_id as id
         FROM Events e 
         JOIN EventUserMap eum ON e.event_id = eum.event_id 
         WHERE eum.user_id = ? AND e.date >= CURDATE() 
         ORDER BY e.date, e.time LIMIT 5)
        UNION
        (SELECT 'schedule' as type, s.title, s.description, s.date, s.time, s.location,
                'schedule' as category, s.schedule_id as id
         FROM Schedules s 
         WHERE s.user_id = ? AND s.date >= CURDATE() 
         ORDER BY s.date, s.time LIMIT 5)
        ORDER BY date, time LIMIT 8
    ";
    
    $stmt = $db->prepare($upcoming_query);
    $stmt->execute([$user_id, $user_id]);
    $dashboard_data['upcoming_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent friend activity (last 10 activities)
    $activity_query = "
        SELECT 'friend_joined' as activity_type, u.username, u.first_name, u.last_name,
               f.created_at as activity_date, 'joined GamePlan' as activity_description
        FROM Friends f
        JOIN Users u ON u.user_id = f.friend_user_id
        WHERE f.user_id = ? AND f.status = 'accepted'
        ORDER BY f.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($activity_query);
    $stmt->execute([$user_id]);
    $dashboard_data['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get friends list with status
    $friends_query = "
        SELECT u.user_id, u.username, u.first_name, u.last_name, u.profile_picture,
               u.last_activity, f.status, f.created_at as friendship_date
        FROM Friends f
        JOIN Users u ON (f.friend_user_id = u.user_id AND f.user_id = ?) OR 
                        (f.user_id = u.user_id AND f.friend_user_id = ?)
        WHERE f.status = 'accepted' AND u.user_id != ?
        ORDER BY u.last_activity DESC
        LIMIT 12
    ";
    
    $stmt = $db->prepare($friends_query);
    $stmt->execute([$user_id, $user_id, $user_id]);
    $dashboard_data['friends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get favorite games
    $games_query = "
        SELECT g.game_id, g.titel as title, g.description, g.category, ug.added_at
        FROM Games g
        JOIN UserGames ug ON g.game_id = ug.game_id
        WHERE ug.user_id = ? AND g.is_active = 1
        ORDER BY g.category, g.titel
        LIMIT 12
    ";
    
    $stmt = $db->prepare($games_query);
    $stmt->execute([$user_id]);
    $dashboard_data['favorite_games'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    // Continue with empty data arrays
}

// Handle flash messages
$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Generate CSRF token for forms
$csrf_token = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GamePlan Scheduler Dashboard - Manage your gaming schedule and connect with friends">
    <meta name="keywords" content="gaming, scheduler, dashboard, esports, tournaments, friends">
    <meta name="author" content="Harsha Kanaparthi">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <title>Dashboard - GamePlan Scheduler | Your Gaming Command Center</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../css/style.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    
    <style>
        :root {
            --gameplan-primary: #6f42c1;
            --gameplan-secondary: #e83e8c;
            --gameplan-dark: #0d1117;
            --gameplan-light: #f8f9fa;
            --gameplan-success: #198754;
            --gameplan-danger: #dc3545;
            --gameplan-warning: #ffc107;
            --gameplan-info: #0dcaf0;
            --gameplan-sidebar: #1a1a2e;
            --gameplan-card: rgba(255, 255, 255, 0.95);
        }
        
        body {
            background: linear-gradient(135deg, var(--gameplan-dark) 0%, #1a1a2e 50%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .sidebar {
            background: var(--gameplan-sidebar);
            min-height: 100vh;
            border-right: 3px solid rgba(111, 66, 193, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .sidebar .nav-link {
            color: #ffffff;
            padding: 0.8rem 1.5rem;
            margin: 0.2rem 0.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(111, 66, 193, 0.2);
            color: #ffffff;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: #ffffff;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .main-content {
            background: transparent;
            min-height: 100vh;
            padding: 2rem;
        }
        
        .dashboard-card {
            background: var(--gameplan-card);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 1;
        }
        
        .stat-card .stat-content {
            position: relative;
            z-index: 2;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease;
        }
        
        .activity-item:hover {
            background: rgba(111, 66, 193, 0.05);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .navbar-custom {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .navbar-brand {
            color: #ffffff !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .quick-action-btn {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            border: none;
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            margin: 0.25rem;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(111, 66, 193, 0.4);
            color: white;
        }
        
        .friend-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .friend-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .online-indicator {
            width: 10px;
            height: 10px;
            background: #198754;
            border-radius: 50%;
            display: inline-block;
            margin-left: 0.5rem;
        }
        
        .offline-indicator {
            width: 10px;
            height: 10px;
            background: #6c757d;
            border-radius: 50%;
            display: inline-block;
            margin-left: 0.5rem;
        }
        
        .game-badge {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin: 0.25rem;
            display: inline-block;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 40%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(15deg);
            z-index: 1;
        }
        
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(111, 66, 193, 0.05);
            border-radius: 50%;
            animation: float 10s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 70%;
            right: 10%;
            animation-delay: 3s;
        }
        
        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 30%;
            animation-delay: 6s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
    </style>
</head>
<body>
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-controller me-2"></i>GamePlan Scheduler
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($first_name ?: $username); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="logout.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid" style="margin-top: 76px;">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="../index.php">
                                <i class="bi bi-speedometer2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="schedules.php">
                                <i class="bi bi-calendar-week"></i>My Schedules
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="events.php">
                                <i class="bi bi-calendar-event"></i>Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="friends.php">
                                <i class="bi bi-people"></i>Friends
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="games.php">
                                <i class="bi bi-controller"></i>Games
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="privacy.php">
                                <i class="bi bi-shield-check"></i>Privacy
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white">
                    
                    <!-- Quick Actions -->
                    <div class="px-3 pb-3">
                        <h6 class="text-white mb-3">Quick Actions</h6>
                        <a href="add_schedule.php" class="btn quick-action-btn w-100 mb-2">
                            <i class="bi bi-plus-circle me-2"></i>New Schedule
                        </a>
                        <a href="add_event.php" class="btn quick-action-btn w-100 mb-2">
                            <i class="bi bi-calendar-plus me-2"></i>Create Event
                        </a>
                        <a href="add_friend.php" class="btn quick-action-btn w-100">
                            <i class="bi bi-person-plus me-2"></i>Add Friend
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <!-- Flash Messages -->
                <?php if (!empty($flash_message)): ?>
                    <div class="alert alert-<?php echo $flash_type === 'error' ? 'danger' : $flash_type; ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?php echo $flash_type === 'success' ? 'check-circle' : 'info-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($flash_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-6 fw-bold mb-2">
                                    Welcome back, <?php echo htmlspecialchars($first_name ?: $username); ?>! 🎮
                                </h1>
                                <p class="lead mb-0">
                                    Ready to dominate today's gaming schedule? Let's see what epic adventures await!
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="bi bi-trophy display-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $dashboard_data['quick_stats']['upcoming_schedules'] ?? 0; ?></div>
                                <div class="stat-label">
                                    <i class="bi bi-calendar-week me-1"></i>Upcoming Schedules
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $dashboard_data['quick_stats']['upcoming_events'] ?? 0; ?></div>
                                <div class="stat-label">
                                    <i class="bi bi-calendar-event me-1"></i>Gaming Events
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $dashboard_data['quick_stats']['total_friends'] ?? 0; ?></div>
                                <div class="stat-label">
                                    <i class="bi bi-people me-1"></i>Gaming Buddies
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $dashboard_data['quick_stats']['favorite_games'] ?? 0; ?></div>
                                <div class="stat-label">
                                    <i class="bi bi-controller me-1"></i>Favorite Games
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Upcoming Activities -->
                    <div class="col-lg-8">
                        <div class="dashboard-card">
                            <div class="card-header bg-transparent border-0 p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-clock me-2 text-primary"></i>Upcoming Gaming Sessions
                                    </h5>
                                    <a href="schedules.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (!empty($dashboard_data['upcoming_events'])): ?>
                                    <?php foreach ($dashboard_data['upcoming_events'] as $activity): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-<?php echo $activity['type'] === 'event' ? 'calendar-event' : 'calendar-week'; ?> 
                                                           text-primary me-3 fs-5"></i>
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                                            <p class="text-muted mb-0 small">
                                                                <?php echo date('M j, Y', strtotime($activity['date'])); ?> at 
                                                                <?php echo date('g:i A', strtotime($activity['time'])); ?>
                                                                <?php if (!empty($activity['location'])): ?>
                                                                    • <?php echo htmlspecialchars($activity['location']); ?>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="badge bg-<?php echo $activity['type'] === 'event' ? 'primary' : 'success'; ?>">
                                                    <?php echo ucfirst($activity['type']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                                        <h5 class="text-muted">No upcoming activities</h5>
                                        <p class="text-muted mb-3">Start planning your gaming sessions!</p>
                                        <a href="add_schedule.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>Create Schedule
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Friends & Activity -->
                    <div class="col-lg-4">
                        <!-- Online Friends -->
                        <div class="dashboard-card mb-4">
                            <div class="card-header bg-transparent border-0 p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-people me-2 text-success"></i>Gaming Buddies
                                    </h6>
                                    <a href="friends.php" class="btn btn-sm btn-outline-success">View All</a>
                                </div>
                            </div>
                            <div class="card-body p-3" style="max-height: 300px; overflow-y: auto;">
                                <?php if (!empty($dashboard_data['friends'])): ?>
                                    <?php foreach (array_slice($dashboard_data['friends'], 0, 6) as $friend): ?>
                                        <div class="friend-card">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $friend['profile_picture'] ?: '../images/default-avatar.png'; ?>" 
                                                     alt="Avatar" class="user-avatar me-3">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?></h6>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($friend['username']); ?></small>
                                                </div>
                                                <?php
                                                $last_activity = strtotime($friend['last_activity']);
                                                $is_online = $last_activity && (time() - $last_activity) < 300; // 5 minutes
                                                ?>
                                                <span class="<?php echo $is_online ? 'online' : 'offline'; ?>-indicator"></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="bi bi-person-plus display-6 text-muted mb-2"></i>
                                        <p class="text-muted mb-2">No friends yet</p>
                                        <a href="add_friend.php" class="btn btn-sm btn-primary">Add Friends</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Favorite Games -->
                        <div class="dashboard-card">
                            <div class="card-header bg-transparent border-0 p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-controller me-2 text-warning"></i>Favorite Games
                                    </h6>
                                    <a href="games.php" class="btn btn-sm btn-outline-warning">Manage</a>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <?php if (!empty($dashboard_data['favorite_games'])): ?>
                                    <div class="d-flex flex-wrap">
                                        <?php foreach (array_slice($dashboard_data['favorite_games'], 0, 8) as $game): ?>
                                            <span class="game-badge" title="<?php echo htmlspecialchars($game['description']); ?>">
                                                <?php echo htmlspecialchars($game['title']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($dashboard_data['favorite_games']) > 8): ?>
                                        <div class="text-center mt-3">
                                            <a href="games.php" class="btn btn-sm btn-outline-primary">
                                                View All <?php echo count($dashboard_data['favorite_games']); ?> Games
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="bi bi-controller display-6 text-muted mb-2"></i>
                                        <p class="text-muted mb-2">No favorite games</p>
                                        <a href="games.php" class="btn btn-sm btn-primary">Browse Games</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <?php if (!empty($dashboard_data['recent_activity'])): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="dashboard-card">
                            <div class="card-header bg-transparent border-0 p-4">
                                <h5 class="mb-0">
                                    <i class="bi bi-activity me-2 text-info"></i>Recent Activity
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($dashboard_data['recent_activity'] as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-plus-fill text-success me-3 fs-5"></i>
                                            <div>
                                                <span class="fw-semibold"><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></span>
                                                <span class="text-muted"><?php echo htmlspecialchars($activity['activity_description']); ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo date('M j, Y \a\t g:i A', strtotime($activity['activity_date'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Update activity indicators every 30 seconds
            setInterval(function() {
                // This would typically make an AJAX call to update online status
                console.log('Updating activity indicators...');
            }, 30000);
            
            // Add smooth scrolling to sidebar links
            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Remove active class from all links
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    // Add active class to clicked link
                    this.classList.add('active');
                });
            });
            
            // Responsive sidebar collapse on mobile
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            function handleResize() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('d-none');
                    mainContent.classList.remove('col-md-9', 'ms-sm-auto', 'col-lg-10');
                    mainContent.classList.add('col-12');
                } else {
                    sidebar.classList.remove('d-none');
                    mainContent.classList.add('col-md-9', 'ms-sm-auto', 'col-lg-10');
                    mainContent.classList.remove('col-12');
                }
            }
            
            window.addEventListener('resize', handleResize);
            handleResize(); // Call on initial load
        });
        
        // Function to update dashboard data (for future AJAX implementation)
        function refreshDashboard() {
            // This would make an AJAX call to refresh dashboard data
            console.log('Refreshing dashboard data...');
        }
        
        // Refresh dashboard data every 5 minutes
        setInterval(refreshDashboard, 300000);
    </script>
</body>
</html>