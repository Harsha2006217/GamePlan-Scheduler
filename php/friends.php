<?php
/**
 * GamePlan Scheduler - Enhanced Professional Friends Management System
 * Advanced Friend Management with Real-Time Status and Interactive Features
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Friends Management
 */

// Enhanced security and session management
require_once 'functions.php';
require_once 'includes/security_functions.php';

// Comprehensive authentication check with security validation
if (!isLoggedIn()) {
    logSecurityEvent('Unauthorized access attempt to friends page');
    header("Location: login.php?error=unauthorized");
    exit;
}

// Initialize user data and security context
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// Enhanced error handling and data validation
$error_message = '';
$success_message = '';
$warning_message = '';

try {
    // Advanced friend data retrieval with comprehensive status tracking
    $friends = getEnhancedFriends($user_id);
    $pending_requests = getAdvancedPendingFriendRequests($user_id);
    $sent_requests = getAdvancedSentFriendRequests($user_id);
    $blocked_users = getBlockedUsers($user_id);
    $friend_suggestions = getFriendSuggestions($user_id);
    
    // Enhanced statistics for dashboard
    $friend_stats = getFriendStatistics($user_id);
    
    // Validate data integrity with comprehensive checks
    if (!is_array($friends)) $friends = [];
    if (!is_array($pending_requests)) $pending_requests = [];
    if (!is_array($sent_requests)) $sent_requests = [];
    if (!is_array($blocked_users)) $blocked_users = [];
    if (!is_array($friend_suggestions)) $friend_suggestions = [];
    
} catch (Exception $e) {
    error_log('Friends Management Error: ' . $e->getMessage());
    $error_message = 'Er is een fout opgetreden bij het laden van vrienden gegevens.';
    $friends = [];
    $pending_requests = [];
    $sent_requests = [];
    $blocked_users = [];
    $friend_suggestions = [];
    $friend_stats = ['total' => 0, 'online' => 0, 'playing' => 0];
}

// Enhanced POST request handling with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation for security
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Beveiligingsvalidatie mislukt. Probeer opnieuw.';
    } else {
        $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
        
        if ($request_id && $action) {
            // Process friend management actions with enhanced security
            switch ($action) {
                case 'accept':
                    if (acceptFriendRequestSecure($request_id, $user_id)) {
                        $success_message = 'Vriendschapsverzoek succesvol geaccepteerd!';
                        logUserActivity($user_id, 'Friend request accepted', $request_id);
                        
                        // Send notification to the friend
                        $requester = getUserById($request_id);
                        if ($requester) {
                            sendNotification($requester['user_id'], 'friend_accepted', 
                                $_SESSION['username'] . ' heeft je vriendschapsverzoek geaccepteerd!');
                        }
                    } else {
                        $error_message = 'Fout bij het accepteren van het vriendschapsverzoek.';
                    }
                    break;
                    
                case 'decline':
                    if (declineFriendRequestSecure($request_id, $user_id)) {
                        $warning_message = 'Vriendschapsverzoek afgewezen.';
                        logUserActivity($user_id, 'Friend request declined', $request_id);
                    } else {
                        $error_message = 'Fout bij het afwijzen van het vriendschapsverzoek.';
                    }
                    break;
                    
                case 'block':
                    if (blockUserSecure($request_id, $user_id)) {
                        $warning_message = 'Gebruiker succesvol geblokkeerd.';
                        logUserActivity($user_id, 'User blocked', $request_id);
                    } else {
                        $error_message = 'Fout bij het blokkeren van de gebruiker.';
                    }
                    break;
                    
                case 'unblock':
                    if (unblockUserSecure($request_id, $user_id)) {
                        $success_message = 'Gebruiker succesvol gedeblokkeerd.';
                        logUserActivity($user_id, 'User unblocked', $request_id);
                    } else {
                        $error_message = 'Fout bij het deblokkeren van de gebruiker.';
                    }
                    break;
                    
                case 'cancel':
                    if (cancelFriendRequestSecure($request_id, $user_id)) {
                        $warning_message = 'Vriendschapsverzoek geannuleerd.';
                        logUserActivity($user_id, 'Friend request cancelled', $request_id);
                    } else {
                        $error_message = 'Fout bij het annuleren van het vriendschapsverzoek.';
                    }
                    break;
                    
                case 'remove':
                    if (removeFriendSecure($request_id, $user_id)) {
                        $warning_message = 'Vriend succesvol verwijderd.';
                        logUserActivity($user_id, 'Friend removed', $request_id);
                    } else {
                        $error_message = 'Fout bij het verwijderen van de vriend.';
                    }
                    break;
                    
                case 'favorite':
                    if (toggleFriendFavorite($request_id, $user_id)) {
                        $success_message = 'Favoriet status bijgewerkt.';
                        logUserActivity($user_id, 'Friend favorite toggled', $request_id);
                    } else {
                        $error_message = 'Fout bij het bijwerken van favoriet status.';
                    }
                    break;
                    
                default:
                    $error_message = 'Ongeldige actie opgegeven.';
                    logSecurityEvent('Invalid friend action attempted', $user_id);
            }
            
            // Redirect to prevent form resubmission
            $redirect_params = [];
            if ($success_message) $redirect_params['success'] = 'action_completed';
            if ($error_message) $redirect_params['error'] = 'action_failed';
            if ($warning_message) $redirect_params['warning'] = 'action_warning';
            
            $redirect_url = 'friends.php';
            if (!empty($redirect_params)) {
                $redirect_url .= '?' . http_build_query($redirect_params);
            }
            
            header("Location: " . $redirect_url);
            exit;
        } else {
            $error_message = 'Ongeldige gegevens opgegeven.';
        }
    }
}

// Handle URL parameters for status messages
if (isset($_GET['success']) || isset($_GET['error']) || isset($_GET['warning'])) {
    if (isset($_GET['success'])) {
        switch ($_GET['success']) {
            case 'action_completed':
                $success_message = 'Actie succesvol uitgevoerd!';
                break;
            case 'friend_added':
                $success_message = 'Vriend succesvol toegevoegd!';
                break;
        }
    }
    
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'action_failed':
                $error_message = 'Er is een fout opgetreden bij het uitvoeren van de actie.';
                break;
        }
    }
    
    if (isset($_GET['warning'])) {
        switch ($_GET['warning']) {
            case 'action_warning':
                $warning_message = 'Actie uitgevoerd met waarschuwing.';
                break;
        }
    }
}

// Generate CSRF token for forms
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GamePlan Scheduler - Beheer je gaming vrienden en bouw je gaming community">
    <meta name="keywords" content="gaming, vrienden, social, scheduler, planning">
    <meta name="author" content="Harsha Kanaparthi">
    <title>Vrienden Beheer - GamePlan Scheduler</title>
    
    <!-- Enhanced Professional Styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Additional Gaming Theme Styles -->
    <style>
        /* Enhanced Gaming Theme for Friends Management */
        .friends-dashboard {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
            color: #ffffff;
        }
        
        .friends-header {
            background: linear-gradient(90deg, #000000 0%, #1a1a1a 100%);
            border-bottom: 3px solid #00d4ff;
            padding: 2rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .friends-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.2), transparent);
            animation: headerPulse 3s ease-in-out infinite;
        }
        
        @keyframes headerPulse {
            0%, 100% { left: -100%; }
            50% { left: 100%; }
        }
        
        .stats-card {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }
        
        .friend-card {
            background: rgba(37, 37, 37, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .friend-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00d4ff, #8b5cf6);
        }
        
        .friend-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.2);
            border-color: rgba(0, 212, 255, 0.5);
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            position: relative;
        }
        
        .status-online {
            background: #28a745;
            box-shadow: 0 0 10px #28a745;
            animation: pulse 2s infinite;
        }
        
        .status-playing {
            background: #007bff;
            box-shadow: 0 0 10px #007bff;
            animation: pulse 2s infinite;
        }
        
        .status-offline {
            background: #6c757d;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
        
        .nav-tabs .nav-link {
            background: transparent;
            border: 1px solid rgba(0, 212, 255, 0.3);
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
            border-color: rgba(0, 212, 255, 0.5);
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #00d4ff 0%, #0a58ca 100%);
            color: #ffffff;
            border-color: #00d4ff;
        }
        
        .action-btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .action-btn:hover::before {
            left: 100%;
        }
        
        .favorite-star {
            color: #ffc107;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .favorite-star:hover {
            transform: scale(1.2);
            text-shadow: 0 0 10px #ffc107;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .empty-state i {
            font-size: 4rem;
            opacity: 0.5;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .friends-header {
                padding: 1rem 0;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .friend-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body class="friends-dashboard">
    <!-- Enhanced Navigation -->
    <?php include 'includes/navigation.php'; ?>
    
    <!-- Professional Friends Header -->
    <div class="friends-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 fw-bold text-white mb-0">
                        <i class="bi bi-people-fill text-primary me-3"></i>
                        Gaming Squad Beheer
                    </h1>
                    <p class="lead text-white-50 mt-2">
                        Beheer je gaming vrienden en bouw je community
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="add_friend.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus-fill me-2"></i>
                            Vriend Toevoegen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Enhanced Status Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($warning_message): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <?php echo htmlspecialchars($warning_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Advanced Friends Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-people display-4 text-primary mb-2"></i>
                        <h3 class="fw-bold text-white"><?php echo $friend_stats['total']; ?></h3>
                        <p class="text-white-50 mb-0">Totaal Vrienden</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-circle-fill display-4 text-success mb-2"></i>
                        <h3 class="fw-bold text-white"><?php echo $friend_stats['online']; ?></h3>
                        <p class="text-white-50 mb-0">Nu Online</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-controller display-4 text-info mb-2"></i>
                        <h3 class="fw-bold text-white"><?php echo $friend_stats['playing']; ?></h3>
                        <p class="text-white-50 mb-0">Aan het Gamen</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope display-4 text-warning mb-2"></i>
                        <h3 class="fw-bold text-white"><?php echo count($pending_requests); ?></h3>
                        <p class="text-white-50 mb-0">Openstaande Verzoeken</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="friendsTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="friends-tab" data-bs-toggle="tab" data-bs-target="#friends" type="button" role="tab">
                    <i class="bi bi-people me-2"></i>
                    Mijn Vrienden
                    <?php if (count($friends) > 0): ?>
                        <span class="badge bg-secondary ms-2"><?php echo count($friends); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
                    <i class="bi bi-inbox me-2"></i>
                    Verzoeken
                    <?php if (count($pending_requests) > 0): ?>
                        <span class="badge bg-primary ms-2"><?php echo count($pending_requests); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab">
                    <i class="bi bi-send me-2"></i>
                    Verzonden
                    <?php if (count($sent_requests) > 0): ?>
                        <span class="badge bg-info ms-2"><?php echo count($sent_requests); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="suggestions-tab" data-bs-toggle="tab" data-bs-target="#suggestions" type="button" role="tab">
                    <i class="bi bi-lightbulb me-2"></i>
                    Suggesties
                    <?php if (count($friend_suggestions) > 0): ?>
                        <span class="badge bg-success ms-2"><?php echo count($friend_suggestions); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="blocked-tab" data-bs-toggle="tab" data-bs-target="#blocked" type="button" role="tab">
                    <i class="bi bi-shield-x me-2"></i>
                    Geblokkeerd
                    <?php if (count($blocked_users) > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo count($blocked_users); ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <!-- Enhanced Tab Content -->
        <div class="tab-content" id="friendsTabsContent">
            <!-- Friends List Tab -->
            <div class="tab-pane fade show active" id="friends" role="tabpanel">
                <?php if (empty($friends)): ?>
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        <h3 class="text-white-50">Nog geen vrienden toegevoegd</h3>
                        <p class="text-white-25">Begin met het toevoegen van vrienden om je gaming community op te bouwen!</p>
                        <a href="add_friend.php" class="btn btn-primary btn-lg mt-3">
                            <i class="bi bi-person-plus me-2"></i>
                            Eerste Vriend Toevoegen
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($friends as $friend): ?>
                            <?php
                            // Calculate status and activity
                            $last_activity = strtotime($friend['last_activity']);
                            $time_diff = time() - $last_activity;
                            
                            if ($time_diff <= 300) { // 5 minutes
                                $status_class = 'status-online';
                                $status_text = 'Online Nu';
                                $status_icon = 'bi-circle-fill text-success';
                            } elseif ($time_diff <= 1800 && !empty($friend['current_game'])) { // 30 minutes and playing
                                $status_class = 'status-playing';
                                $status_text = 'Speelt ' . htmlspecialchars($friend['current_game']);
                                $status_icon = 'bi-controller text-info';
                            } else {
                                $status_class = 'status-offline';
                                $status_text = 'Offline';
                                $status_icon = 'bi-circle text-muted';
                            }
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="friend-card card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="status-indicator <?php echo $status_class; ?>"></div>
                                                <h5 class="card-title text-white mb-0">
                                                    <?php echo htmlspecialchars($friend['username']); ?>
                                                </h5>
                                            </div>
                                            <button class="favorite-star btn btn-link p-0" 
                                                    data-friend-id="<?php echo $friend['user_id']; ?>"
                                                    data-is-favorite="<?php echo $friend['is_favorite'] ? 'true' : 'false'; ?>">
                                                <i class="bi <?php echo $friend['is_favorite'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-white-50">
                                                <i class="<?php echo $status_icon; ?> me-1"></i>
                                                <?php echo $status_text; ?>
                                            </small>
                                        </div>
                                        
                                        <?php if (!empty($friend['favorite_games'])): ?>
                                            <div class="mb-3">
                                                <small class="text-white-25">Favoriete Games:</small>
                                                <div class="mt-1">
                                                    <?php 
                                                    $games = explode(',', $friend['favorite_games']);
                                                    foreach (array_slice($games, 0, 3) as $game): 
                                                    ?>
                                                        <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(trim($game)); ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($games) > 3): ?>
                                                        <span class="badge bg-dark">+<?php echo count($games) - 3; ?> meer</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex gap-2">
                                            <?php if ($status_class === 'status-online'): ?>
                                                <button class="btn btn-success btn-sm action-btn flex-fill" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#inviteModal"
                                                        data-friend-id="<?php echo $friend['user_id']; ?>"
                                                        data-friend-name="<?php echo htmlspecialchars($friend['username']); ?>">
                                                    <i class="bi bi-chat-dots me-1"></i>
                                                    Uitnodigen
                                                </button>
                                            <?php endif; ?>
                                            
                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary btn-sm action-btn dropdown-toggle" 
                                                        type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-dark">
                                                    <li>
                                                        <a class="dropdown-item" href="profile.php?user=<?php echo $friend['user_id']; ?>">
                                                            <i class="bi bi-person me-2"></i>Profiel Bekijken
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="request_id" value="<?php echo $friend['user_id']; ?>">
                                                            <button type="submit" name="action" value="block" 
                                                                    class="dropdown-item text-warning"
                                                                    onclick="return confirm('Weet je zeker dat je <?php echo htmlspecialchars($friend['username']); ?> wilt blokkeren?')">
                                                                <i class="bi bi-shield-x me-2"></i>Blokkeren
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="request_id" value="<?php echo $friend['user_id']; ?>">
                                                            <button type="submit" name="action" value="remove" 
                                                                    class="dropdown-item text-danger"
                                                                    onclick="return confirm('Weet je zeker dat je <?php echo htmlspecialchars($friend['username']); ?> als vriend wilt verwijderen?')">
                                                                <i class="bi bi-person-x me-2"></i>Verwijderen
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Requests Tab -->
            <div class="tab-pane fade" id="requests" role="tabpanel">
                <?php if (empty($pending_requests)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3 class="text-white-50">Geen openstaande vriendschapsverzoeken</h3>
                        <p class="text-white-25">Wanneer iemand je een vriendschapsverzoek stuurt, zie je dat hier.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="friend-card card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="status-indicator status-offline me-2"></div>
                                            <h5 class="card-title text-white mb-0">
                                                <?php echo htmlspecialchars($request['username']); ?>
                                            </h5>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-white-50">
                                                <i class="bi bi-clock me-1"></i>
                                                Verzonden: <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <?php if (!empty($request['mutual_friends'])): ?>
                                            <div class="mb-3">
                                                <small class="text-white-25">
                                                    <?php echo $request['mutual_friends']; ?> wederzijdse vrienden
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex gap-2">
                                            <form method="POST" class="flex-fill">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="request_id" value="<?php echo $request['friend_id']; ?>">
                                                <button type="submit" name="action" value="accept" 
                                                        class="btn btn-success btn-sm action-btn w-100">
                                                    <i class="bi bi-check-lg me-1"></i>
                                                    Accepteren
                                                </button>
                                            </form>
                                            <form method="POST" class="flex-fill">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="request_id" value="<?php echo $request['friend_id']; ?>">
                                                <button type="submit" name="action" value="decline" 
                                                        class="btn btn-outline-danger btn-sm action-btn w-100">
                                                    <i class="bi bi-x-lg me-1"></i>
                                                    Afwijzen
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sent Requests Tab -->
            <div class="tab-pane fade" id="sent" role="tabpanel">
                <?php if (empty($sent_requests)): ?>
                    <div class="empty-state">
                        <i class="bi bi-send"></i>
                        <h3 class="text-white-50">Geen verzonden verzoeken</h3>
                        <p class="text-white-25">Vriendschapsverzoeken die je verstuurt verschijnen hier.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($sent_requests as $request): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="friend-card card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="status-indicator status-offline me-2"></div>
                                            <h5 class="card-title text-white mb-0">
                                                <?php echo htmlspecialchars($request['username']); ?>
                                            </h5>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-white-50">
                                                <i class="bi bi-clock me-1"></i>
                                                Verzonden: <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <span class="badge bg-warning">
                                                <i class="bi bi-hourglass-split me-1"></i>
                                                In afwachting
                                            </span>
                                        </div>
                                        
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="request_id" value="<?php echo $request['friend_id']; ?>">
                                            <button type="submit" name="action" value="cancel" 
                                                    class="btn btn-outline-secondary btn-sm action-btn w-100"
                                                    onclick="return confirm('Weet je zeker dat je dit verzoek wilt annuleren?')">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Verzoek Annuleren
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Friend Suggestions Tab -->
            <div class="tab-pane fade" id="suggestions" role="tabpanel">
                <?php if (empty($friend_suggestions)): ?>
                    <div class="empty-state">
                        <i class="bi bi-lightbulb"></i>
                        <h3 class="text-white-50">Geen vrienden suggesties</h3>
                        <p class="text-white-25">We zoeken naar mensen die je misschien kent!</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($friend_suggestions as $suggestion): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="friend-card card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="status-indicator status-offline me-2"></div>
                                            <h5 class="card-title text-white mb-0">
                                                <?php echo htmlspecialchars($suggestion['username']); ?>
                                            </h5>
                                        </div>
                                        
                                        <?php if (!empty($suggestion['reason'])): ?>
                                            <div class="mb-3">
                                                <small class="text-white-50">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    <?php echo htmlspecialchars($suggestion['reason']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($suggestion['mutual_friends'])): ?>
                                            <div class="mb-3">
                                                <small class="text-white-25">
                                                    <?php echo $suggestion['mutual_friends']; ?> wederzijdse vrienden
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex gap-2">
                                            <a href="add_friend.php?suggest=<?php echo $suggestion['user_id']; ?>" 
                                               class="btn btn-primary btn-sm action-btn flex-fill">
                                                <i class="bi bi-person-plus me-1"></i>
                                                Vriend Toevoegen
                                            </a>
                                            <a href="profile.php?user=<?php echo $suggestion['user_id']; ?>" 
                                               class="btn btn-outline-info btn-sm action-btn">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Blocked Users Tab -->
            <div class="tab-pane fade" id="blocked" role="tabpanel">
                <?php if (empty($blocked_users)): ?>
                    <div class="empty-state">
                        <i class="bi bi-shield-x"></i>
                        <h3 class="text-white-50">Geen geblokkeerde gebruikers</h3>
                        <p class="text-white-25">Gebruikers die je blokkeert verschijnen hier.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($blocked_users as $blocked): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="friend-card card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="status-indicator bg-danger me-2"></div>
                                            <h5 class="card-title text-white mb-0">
                                                <?php echo htmlspecialchars($blocked['username']); ?>
                                            </h5>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-white-50">
                                                <i class="bi bi-shield-x me-1"></i>
                                                Geblokkeerd: <?php echo date('d/m/Y', strtotime($blocked['blocked_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="request_id" value="<?php echo $blocked['user_id']; ?>">
                                            <button type="submit" name="action" value="unblock" 
                                                    class="btn btn-outline-success btn-sm action-btn w-100"
                                                    onclick="return confirm('Weet je zeker dat je <?php echo htmlspecialchars($blocked['username']); ?> wilt deblokkeren?')">
                                                <i class="bi bi-shield-check me-1"></i>
                                                Deblokkeren
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Footer -->
        <div class="text-center mt-5 mb-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="add_friend.php" class="btn btn-primary btn-lg action-btn">
                            <i class="bi bi-person-plus-fill me-2"></i>
                            Vriend Toevoegen
                        </a>
                        <a href="index.php" class="btn btn-outline-light btn-lg action-btn">
                            <i class="bi bi-house-fill me-2"></i>
                            Terug naar Dashboard
                        </a>
                        <a href="events.php" class="btn btn-outline-primary btn-lg action-btn">
                            <i class="bi bi-calendar-event me-2"></i>
                            Evenementen Bekijken
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Game Invitation Modal -->
    <div class="modal fade" id="inviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">
                        <i class="bi bi-controller me-2"></i>
                        Gaming Uitnodiging Versturen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="inviteForm" method="POST" action="send_invitation.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="friend_id" id="inviteFriendId">
                        
                        <div class="mb-3">
                            <label class="form-label">Uitnodigen voor:</label>
                            <h6 class="text-primary" id="inviteFriendName"></h6>
                        </div>
                        
                        <div class="mb-3">
                            <label for="inviteGame" class="form-label">Game Selecteren:</label>
                            <select class="form-select bg-dark text-white border-secondary" id="inviteGame" name="game_id" required>
                                <option value="">Kies een game...</option>
                                <?php
                                $user_games = getUserGames($user_id);
                                foreach ($user_games as $game):
                                ?>
                                    <option value="<?php echo $game['game_id']; ?>">
                                        <?php echo htmlspecialchars($game['titel']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="inviteMessage" class="form-label">Bericht (optioneel):</label>
                            <textarea class="form-control bg-dark text-white border-secondary" 
                                      id="inviteMessage" name="message" rows="3" 
                                      placeholder="Kom je meedoen? Wordt leuk!"></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-send me-2"></i>
                                Uitnodiging Versturen
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Annuleren
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Professional JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced invite modal functionality
        const inviteModal = document.getElementById('inviteModal');
        if (inviteModal) {
            inviteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const friendId = button.getAttribute('data-friend-id');
                const friendName = button.getAttribute('data-friend-name');
                
                document.getElementById('inviteFriendId').value = friendId;
                document.getElementById('inviteFriendName').textContent = friendName;
            });
        }
        
        // Enhanced favorite star functionality
        document.querySelectorAll('.favorite-star').forEach(button => {
            button.addEventListener('click', async function() {
                const friendId = this.getAttribute('data-friend-id');
                const isFavorite = this.getAttribute('data-is-favorite') === 'true';
                const icon = this.querySelector('i');
                
                try {
                    const response = await fetch('toggle_favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?php echo $csrf_token; ?>'
                        },
                        body: JSON.stringify({
                            friend_id: friendId,
                            is_favorite: !isFavorite
                        })
                    });
                    
                    if (response.ok) {
                        // Toggle star appearance
                        if (isFavorite) {
                            icon.className = 'bi bi-star';
                            this.setAttribute('data-is-favorite', 'false');
                        } else {
                            icon.className = 'bi bi-star-fill';
                            this.setAttribute('data-is-favorite', 'true');
                        }
                        
                        // Add animation effect
                        this.style.transform = 'scale(1.3)';
                        setTimeout(() => {
                            this.style.transform = 'scale(1)';
                        }, 200);
                    }
                } catch (error) {
                    console.error('Error toggling favorite:', error);
                }
            });
        });
        
        // Enhanced tab persistence
        const triggerTabList = document.querySelectorAll('#friendsTabs button');
        triggerTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            
            triggerEl.addEventListener('click', event => {
                event.preventDefault();
                tabTrigger.show();
                
                // Store active tab in localStorage
                localStorage.setItem('activeFriendsTab', triggerEl.getAttribute('data-bs-target'));
            });
        });
        
        // Restore active tab from localStorage
        const activeTab = localStorage.getItem('activeFriendsTab');
        if (activeTab) {
            const tabEl = document.querySelector(`[data-bs-target="${activeTab}"]`);
            if (tabEl) {
                const tab = new bootstrap.Tab(tabEl);
                tab.show();
            }
        }
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Enhanced form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Bezig...';
                    
                    // Re-enable after 3 seconds to prevent permanent disabling
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Versturen';
                    }, 3000);
                }
            });
        });
        
        // Store original button text for restoration
        document.querySelectorAll('button[type="submit"]').forEach(btn => {
            btn.setAttribute('data-original-text', btn.innerHTML);
        });
        
        // Enhanced search functionality (if search input exists)
        const searchInput = document.getElementById('friendSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const friendCards = document.querySelectorAll('.friend-card');
                
                friendCards.forEach(card => {
                    const friendName = card.querySelector('.card-title').textContent.toLowerCase();
                    const shouldShow = friendName.includes(searchTerm);
                    
                    card.closest('.col-md-6, .col-lg-4').style.display = shouldShow ? 'block' : 'none';
                });
            });
        }
        
        // Enhanced keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + F to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                const searchInput = document.getElementById('friendSearch');
                if (searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    const modal = bootstrap.Modal.getInstance(activeModal);
                    if (modal) modal.hide();
                }
            }
        });
        
        console.log('Enhanced GamePlan Friends Management initialized successfully');
    });
    </script>
</body>
</html>