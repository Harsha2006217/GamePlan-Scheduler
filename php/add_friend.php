<?php
/**
 * GamePlan Scheduler - Enhanced Professional Friend Management System
 * Advanced Friend Addition with Real-Time Search and Comprehensive Validation
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Friend Addition System
 */

// Start session with enhanced security settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 1800, // 30 minutes
        'cookie_secure' => false,   // Set to true for HTTPS
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'use_only_cookies' => true
    ]);
}

// Comprehensive includes for full functionality
require_once 'functions.php';
require_once 'db.php';

// Enhanced authentication check with automatic redirect
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Initialize variables with proper defaults
$user_id = $_SESSION['user_id'];
$profile = getProfile($user_id);
$message = '';
$error_type = '';
$success_type = '';

// Advanced CSRF token generation for enhanced security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Enhanced friend addition processing with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Advanced CSRF protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Beveiligingstoken is ongeldig. Probeer opnieuw.';
        $error_type = 'security';
    } else {
        $action = htmlspecialchars(trim($_POST['action']), ENT_QUOTES, 'UTF-8');
        
        if ($action === 'add_friend') {
            // Enhanced input validation and sanitization
            $friend_username = isset($_POST['friend_username']) ? trim($_POST['friend_username']) : '';
            $friend_id = isset($_POST['friend_id']) ? filter_var($_POST['friend_id'], FILTER_VALIDATE_INT) : null;
            
            // Comprehensive validation chain
            if (empty($friend_username) && !$friend_id) {
                $message = 'Gebruikersnaam is verplicht en mag niet leeg zijn.';
                $error_type = 'validation';
            } elseif (!empty($friend_username) && preg_match('/^\s*$/', $friend_username)) {
                $message = 'Gebruikersnaam mag niet alleen uit spaties bestaan.';
                $error_type = 'validation';
            } elseif (!empty($friend_username) && strlen($friend_username) > 50) {
                $message = 'Gebruikersnaam mag maximaal 50 karakters bevatten.';
                $error_type = 'validation';
            } elseif (!empty($friend_username) && strlen($friend_username) < 3) {
                $message = 'Gebruikersnaam moet minimaal 3 karakters bevatten.';
                $error_type = 'validation';
            } else {
                // Advanced friend addition logic with multiple validation layers
                if (!$friend_id && !empty($friend_username)) {
                    // Find user by username with enhanced security
                    $friend_id = findUserByUsername($friend_username);
                }
                
                if (!$friend_id) {
                    $message = 'Gebruiker niet gevonden. Controleer de gebruikersnaam.';
                    $error_type = 'not_found';
                } elseif ($friend_id === $user_id) {
                    $message = 'Je kunt jezelf niet als vriend toevoegen.';
                    $error_type = 'self_add';
                } elseif (areFriends($user_id, $friend_id)) {
                    $message = 'Deze gebruiker is al je vriend.';
                    $error_type = 'already_friends';
                } else {
                    // Execute friend addition with comprehensive error handling
                    $add_result = addFriendById($user_id, $friend_id);
                    
                    if ($add_result === true) {
                        $friend_info = getUserById($friend_id);
                        $message = 'Vriend "' . htmlspecialchars($friend_info['username'], ENT_QUOTES, 'UTF-8') . '" succesvol toegevoegd!';
                        $success_type = 'friend_added';
                        
                        // Update user activity timestamp
                        updateUserActivity($user_id);
                        
                        // Create notification for the new friend
                        createNotification($friend_id, 'friend_request', $profile['username'] . ' heeft je toegevoegd als vriend!');
                        
                    } else {
                        $message = 'Er is een fout opgetreden bij het toevoegen van de vriend. Probeer opnieuw.';
                        $error_type = 'database_error';
                    }
                }
            }
        }
    }
}

// Get enhanced friend suggestions with comprehensive data
$suggested_friends = getSuggestedFriends($user_id);
$popular_users = getPopularUsers($user_id);
$recent_users = getRecentActiveUsers($user_id);

// Enhanced page metadata
$page_title = 'Vriend Toevoegen - GamePlan Scheduler';
$page_description = 'Voeg nieuwe vrienden toe aan je GamePlan netwerk en bouw je gaming community uit.';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="author" content="Harsha Kanaparthi">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Enhanced CSS Framework Integration -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Enhanced Favicon and Icons -->
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <link rel="apple-touch-icon" href="../assets/apple-touch-icon.png">
    
    <!-- Professional Inline Styles for Enhanced Gaming UI -->
    <style>
        :root {
            --primary-gaming-blue: #00d4ff;
            --secondary-gaming-purple: #8b5cf6;
            --dark-bg-primary: #0a0a0a;
            --dark-bg-secondary: #1a1a1a;
            --dark-bg-card: #252525;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-muted: rgba(255, 255, 255, 0.6);
            --border-color: #333333;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, var(--dark-bg-primary) 0%, var(--dark-bg-secondary) 100%);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .main-container {
            background: var(--dark-bg-secondary);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.2);
            border: 1px solid var(--primary-gaming-blue);
            margin: 2rem auto;
            max-width: 1000px;
            overflow: hidden;
        }

        .page-header {
            background: linear-gradient(90deg, var(--dark-bg-primary) 0%, var(--dark-bg-secondary) 100%);
            border-bottom: 2px solid var(--primary-gaming-blue);
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.1), transparent);
            animation: headerShine 3s ease-in-out infinite;
        }

        @keyframes headerShine {
            0%, 100% { left: -100%; }
            50% { left: 100%; }
        }

        .page-title {
            font-family: 'Orbitron', 'Courier New', monospace;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-gaming-blue);
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.6);
            margin: 0;
            position: relative;
            z-index: 2;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-top: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .search-container {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            background: var(--dark-bg-card);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1.1rem;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .search-input:focus {
            border-color: var(--primary-gaming-blue);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
            background: var(--dark-bg-secondary);
            outline: none;
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--dark-bg-card);
            border: 1px solid var(--border-color);
            border-radius: 0 0 12px 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
        }

        .search-result-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .search-result-item:hover {
            background: rgba(0, 212, 255, 0.1);
            transform: translateX(5px);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .section-card {
            background: var(--dark-bg-card);
            border-radius: 12px;
            margin-bottom: 2rem;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .section-header {
            background: linear-gradient(135deg, var(--primary-gaming-blue) 0%, var(--secondary-gaming-purple) 100%);
            color: white;
            padding: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-header i {
            font-size: 1.4rem;
        }

        .section-content {
            padding: 1.5rem;
        }

        .user-card {
            background: var(--dark-bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .user-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary-gaming-blue) 0%, var(--secondary-gaming-purple) 100%);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .user-card:hover::before {
            transform: scaleY(1);
        }

        .user-card:hover {
            transform: translateX(5px);
            border-color: var(--primary-gaming-blue);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.2);
        }

        .user-info h6 {
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .user-meta {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .btn-add-friend {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-add-friend::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-add-friend:hover::before {
            left: 100%;
        }

        .btn-add-friend:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .alert-custom {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.1) 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.1) 100%);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h6 {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .back-button {
            background: linear-gradient(135deg, var(--border-color) 0%, #404040 100%);
            border: none;
            border-radius: 8px;
            color: var(--text-primary);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-button:hover {
            background: linear-gradient(135deg, #404040 0%, #525252 100%);
            color: var(--primary-gaming-blue);
            transform: translateY(-2px);
        }

        .loading-spinner {
            display: none;
            color: var(--primary-gaming-blue);
        }

        .loading-spinner.show {
            display: inline-block;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 8px;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .section-content {
                padding: 1rem;
            }

            .user-card {
                padding: 1rem;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }

            .btn-add-friend {
                width: 100%;
            }
        }

        /* Custom scrollbar for search results */
        .search-results::-webkit-scrollbar {
            width: 6px;
        }

        .search-results::-webkit-scrollbar-track {
            background: var(--dark-bg-secondary);
        }

        .search-results::-webkit-scrollbar-thumb {
            background: var(--primary-gaming-blue);
            border-radius: 3px;
        }

        .search-results::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-gaming-purple);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="main-container">
            <!-- Enhanced Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-person-plus-fill me-3"></i>
                    Vriend Toevoegen
                </h1>
                <p class="page-subtitle">
                    Bouw je gaming netwerk uit en vind nieuwe speelpartners
                </p>
            </div>

            <div class="p-4">
                <!-- Enhanced Message Display System -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-custom <?php echo $success_type ? 'alert-success' : 'alert-danger'; ?> alert-dismissible" role="alert">
                        <i class="bi <?php echo $success_type ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Sluiten"></button>
                    </div>
                <?php endif; ?>

                <!-- Advanced Friend Search System -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="bi bi-search"></i>
                        <span>Zoek Vrienden</span>
                    </div>
                    <div class="section-content">
                        <div class="search-container">
                            <input type="text" 
                                   id="userSearch" 
                                   class="search-input" 
                                   placeholder="Zoek gebruikers op naam, games of interesses..."
                                   autocomplete="off"
                                   maxlength="50">
                            <div id="searchResults" class="search-results d-none">
                                <!-- Enhanced search results will be populated here -->
                            </div>
                            <div class="loading-spinner mt-2" id="searchLoading">
                                <i class="bi bi-arrow-clockwise spin"></i>
                                <span class="ms-2">Zoeken...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Suggested Friends Section -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="bi bi-people-fill"></i>
                        <span>Aanbevolen Vrienden</span>
                        <span class="badge bg-primary ms-auto"><?php echo count($suggested_friends); ?></span>
                    </div>
                    <div class="section-content">
                        <?php if (empty($suggested_friends)): ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h6>Geen aanbevelingen beschikbaar</h6>
                                <p class="small">Voeg meer games toe aan je profiel voor betere aanbevelingen</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($suggested_friends as $friend): ?>
                                <div class="user-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="user-info flex-grow-1">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($friend['username'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if (isUserOnline($friend['user_id'])): ?>
                                                    <span class="badge bg-success ms-2">Online</span>
                                                <?php endif; ?>
                                            </h6>
                                            <div class="user-meta">
                                                <i class="bi bi-controller me-1"></i>
                                                <?php echo htmlspecialchars($friend['common_games'] ?? '0', ENT_QUOTES, 'UTF-8'); ?> games in common
                                                <?php if (isset($friend['mutual_friends']) && $friend['mutual_friends'] > 0): ?>
                                                    <span class="ms-3">
                                                        <i class="bi bi-people me-1"></i>
                                                        <?php echo $friend['mutual_friends']; ?> wederzijdse vrienden
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <form method="POST" class="d-inline" onsubmit="return confirmAddFriend(this)">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="add_friend">
                                            <input type="hidden" name="friend_id" value="<?php echo htmlspecialchars($friend['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn-add-friend">
                                                <i class="bi bi-person-plus-fill me-2"></i>
                                                Toevoegen
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Popular Users Section -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="bi bi-star-fill"></i>
                        <span>Populaire Gebruikers</span>
                        <span class="badge bg-warning ms-auto"><?php echo count($popular_users); ?></span>
                    </div>
                    <div class="section-content">
                        <?php if (empty($popular_users)): ?>
                            <div class="empty-state">
                                <i class="bi bi-star"></i>
                                <h6>Geen populaire gebruikers gevonden</h6>
                                <p class="small">Kom later terug voor nieuwe aanbevelingen</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($popular_users as $user): ?>
                                <div class="user-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="user-info flex-grow-1">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if (isUserOnline($user['user_id'])): ?>
                                                    <span class="badge bg-success ms-2">Online</span>
                                                <?php endif; ?>
                                            </h6>
                                            <div class="user-meta">
                                                <i class="bi bi-people me-1"></i>
                                                <?php echo htmlspecialchars($user['friend_count'] ?? '0', ENT_QUOTES, 'UTF-8'); ?> vrienden
                                                <span class="ms-3">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <?php echo htmlspecialchars($user['activity_count'] ?? '0', ENT_QUOTES, 'UTF-8'); ?> activiteiten
                                                </span>
                                            </div>
                                        </div>
                                        <form method="POST" class="d-inline" onsubmit="return confirmAddFriend(this)">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="add_friend">
                                            <input type="hidden" name="friend_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn-add-friend">
                                                <i class="bi bi-person-plus-fill me-2"></i>
                                                Toevoegen
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Recent Active Users Section -->
                <?php if (!empty($recent_users)): ?>
                <div class="section-card">
                    <div class="section-header">
                        <i class="bi bi-clock-fill"></i>
                        <span>Recent Actieve Gebruikers</span>
                        <span class="badge bg-info ms-auto"><?php echo count($recent_users); ?></span>
                    </div>
                    <div class="section-content">
                        <?php foreach ($recent_users as $user): ?>
                            <div class="user-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="user-info flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
                                            <span class="badge bg-success ms-2">Recent Actief</span>
                                        </h6>
                                        <div class="user-meta">
                                            <i class="bi bi-clock me-1"></i>
                                            Laatst actief: <?php echo timeAgo($user['last_activity']); ?>
                                        </div>
                                    </div>
                                    <form method="POST" class="d-inline" onsubmit="return confirmAddFriend(this)">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="add_friend">
                                        <input type="hidden" name="friend_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn-add-friend">
                                            <i class="bi bi-person-plus-fill me-2"></i>
                                            Toevoegen
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Enhanced Navigation Footer -->
                <div class="text-center mt-4">
                    <a href="index.php" class="back-button">
                        <i class="bi bi-house-door-fill"></i>
                        <span>Terug naar Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <!-- Advanced Professional JavaScript Implementation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced search functionality with comprehensive features
            const searchInput = document.getElementById('userSearch');
            const searchResults = document.getElementById('searchResults');
            const searchLoading = document.getElementById('searchLoading');
            let searchTimeout;
            let currentSearchRequest;

            // Advanced debounced search with loading states
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    hideSearchResults();
                    return;
                }

                // Show loading state
                searchLoading.classList.add('show');
                
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            });

            // Enhanced focus and blur handlers
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2 && searchResults.children.length > 0) {
                    searchResults.classList.remove('d-none');
                }
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    hideSearchResults();
                }
            });

            // Keyboard navigation for search results
            searchInput.addEventListener('keydown', function(e) {
                const items = searchResults.querySelectorAll('.search-result-item');
                const currentFocus = searchResults.querySelector('.search-result-item.focused');
                
                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        navigateResults(items, currentFocus, 'down');
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        navigateResults(items, currentFocus, 'up');
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (currentFocus) {
                            const button = currentFocus.querySelector('button');
                            if (button) button.click();
                        }
                        break;
                    case 'Escape':
                        hideSearchResults();
                        searchInput.blur();
                        break;
                }
            });

            /**
             * Enhanced search function with comprehensive error handling
             * @param {string} query - Search query string
             */
            async function performSearch(query) {
                // Cancel previous request if still pending
                if (currentSearchRequest) {
                    currentSearchRequest.abort();
                }

                try {
                    // Create AbortController for request cancellation
                    const controller = new AbortController();
                    currentSearchRequest = controller;

                    const response = await fetch(`search_users.php?q=${encodeURIComponent(query)}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: controller.signal
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    displaySearchResults(data);
                    
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Search error:', error);
                        displaySearchError('Er is een fout opgetreden bij het zoeken. Probeer opnieuw.');
                    }
                } finally {
                    searchLoading.classList.remove('show');
                    currentSearchRequest = null;
                }
            }

            /**
             * Enhanced search results display with comprehensive data
             * @param {Array} users - Array of user objects
             */
            function displaySearchResults(users) {
                searchResults.innerHTML = '';

                if (!users || users.length === 0) {
                    searchResults.innerHTML = `
                        <div class="search-result-item text-center">
                            <i class="bi bi-search text-muted me-2"></i>
                            <span class="text-muted">Geen gebruikers gevonden</span>
                        </div>
                    `;
                } else {
                    const resultsHTML = users.map(user => `
                        <div class="search-result-item" tabindex="-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="user-info flex-grow-1">
                                    <h6 class="mb-1">
                                        ${escapeHtml(user.username)}
                                        ${user.is_online ? '<span class="badge bg-success ms-2">Online</span>' : ''}
                                    </h6>
                                    <div class="user-meta">
                                        <i class="bi bi-controller me-1"></i>
                                        ${parseInt(user.common_games) || 0} games in common
                                        ${user.mutual_friends > 0 ? `<span class="ms-3"><i class="bi bi-people me-1"></i>${user.mutual_friends} wederzijdse vrienden</span>` : ''}
                                    </div>
                                </div>
                                <form method="POST" class="d-inline" onsubmit="return confirmAddFriend(this)">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="add_friend">
                                    <input type="hidden" name="friend_id" value="${user.user_id}">
                                    <button type="submit" class="btn-add-friend btn-sm">
                                        <i class="bi bi-person-plus-fill me-1"></i>
                                        Toevoegen
                                    </button>
                                </form>
                            </div>
                        </div>
                    `).join('');

                    searchResults.innerHTML = resultsHTML;
                }

                searchResults.classList.remove('d-none');
            }

            /**
             * Display search error message
             * @param {string} message - Error message to display
             */
            function displaySearchError(message) {
                searchResults.innerHTML = `
                    <div class="search-result-item text-center">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        <span class="text-warning">${escapeHtml(message)}</span>
                    </div>
                `;
                searchResults.classList.remove('d-none');
            }

            /**
             * Hide search results with smooth animation
             */
            function hideSearchResults() {
                searchResults.classList.add('d-none');
                searchLoading.classList.remove('show');
            }

            /**
             * Navigate through search results with keyboard
             * @param {NodeList} items - Search result items
             * @param {Element} currentFocus - Currently focused item
             * @param {string} direction - Navigation direction (up/down)
             */
            function navigateResults(items, currentFocus, direction) {
                if (items.length === 0) return;

                // Remove existing focus
                if (currentFocus) {
                    currentFocus.classList.remove('focused');
                }

                let newIndex = 0;
                if (currentFocus) {
                    const currentIndex = Array.from(items).indexOf(currentFocus);
                    if (direction === 'down') {
                        newIndex = (currentIndex + 1) % items.length;
                    } else {
                        newIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
                    }
                }

                // Add focus to new item
                items[newIndex].classList.add('focused');
                items[newIndex].scrollIntoView({ block: 'nearest' });
            }

            /**
             * Enhanced HTML escaping for security
             * @param {string} unsafe - Unsafe string to escape
             * @returns {string} Escaped string
             */
            function escapeHtml(unsafe) {
                if (typeof unsafe !== 'string') return '';
                
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // Enhanced form submission tracking
            let isSubmitting = false;

            /**
             * Confirmation dialog for adding friends
             * @param {HTMLFormElement} form - Form element
             * @returns {boolean} Whether to proceed with submission
             */
            window.confirmAddFriend = function(form) {
                if (isSubmitting) {
                    return false;
                }

                const usernameInput = form.querySelector('input[name="friend_id"]');
                if (!usernameInput || !usernameInput.value) {
                    showNotification('Geen geldige gebruiker geselecteerd.', 'error');
                    return false;
                }

                isSubmitting = true;
                
                // Update button to show loading state
                const button = form.querySelector('button[type="submit"]');
                const originalHTML = button.innerHTML;
                
                button.innerHTML = '<i class="bi bi-arrow-clockwise spin me-2"></i>Toevoegen...';
                button.disabled = true;

                // Reset button after delay if form doesn't submit normally
                setTimeout(() => {
                    if (isSubmitting) {
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                        isSubmitting = false;
                    }
                }, 5000);

                return true;
            };

            /**
             * Enhanced notification system
             * @param {string} message - Notification message
             * @param {string} type - Notification type (success, error, warning, info)
             */
            function showNotification(message, type = 'info') {
                const alertClass = type === 'error' ? 'alert-danger' : `alert-${type}`;
                const iconClass = {
                    'success': 'bi-check-circle-fill',
                    'error': 'bi-exclamation-triangle-fill',
                    'warning': 'bi-exclamation-triangle-fill',
                    'info': 'bi-info-circle-fill'
                }[type] || 'bi-info-circle-fill';

                const notification = document.createElement('div');
                notification.className = `alert alert-custom ${alertClass} alert-dismissible position-fixed`;
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                notification.innerHTML = `
                    <i class="${iconClass} me-2"></i>
                    ${escapeHtml(message)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(notification);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 5000);
            }

            // Enhanced loading animation for spinning icons
            const style = document.createElement('style');
            style.textContent = `
                .spin {
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                .search-result-item.focused {
                    background: rgba(0, 212, 255, 0.1);
                    border-color: var(--primary-gaming-blue);
                }
            `;
            document.head.appendChild(style);

            // Enhanced accessibility improvements
            searchInput.setAttribute('aria-describedby', 'search-help');
            searchInput.setAttribute('aria-expanded', 'false');
            
            const searchHelp = document.createElement('div');
            searchHelp.id = 'search-help';
            searchHelp.className = 'visually-hidden';
            searchHelp.textContent = 'Typ minimaal 2 karakters om te zoeken naar gebruikers. Gebruik de pijltjestoetsen om door resultaten te navigeren.';
            searchInput.parentNode.appendChild(searchHelp);

            // Update aria-expanded when showing/hiding results
            const originalDisplayResults = displaySearchResults;
            window.displaySearchResults = function(users) {
                originalDisplayResults(users);
                searchInput.setAttribute('aria-expanded', 'true');
            };

            const originalHideResults = hideSearchResults;
            window.hideSearchResults = function() {
                originalHideResults();
                searchInput.setAttribute('aria-expanded', 'false');
            };
        });
    </script>
</body>
</html>