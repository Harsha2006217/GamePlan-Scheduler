<?php<?php

/**require 'functions.php';

 * GamePlan Scheduler - Professional User Profile Managementif (!isLoggedIn()) {

 * Advanced profile system with comprehensive user settings and preferences    header("Location: login.php");

 *     exit;

 * @author Harsha Kanaparthi}

 * @version 2.1 Professional Edition$user_id = $_SESSION['user_id'];

 * @date September 30, 2025$profile = getProfile($user_id);

 * @description Complete user profile management with security, preferences, and gaming settings$favorites = getFavoriteGames($user_id);

 */$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

// Enable comprehensive error reporting for development    $selected_games = $_POST['games'] ?? [];

error_reporting(E_ALL);    foreach ($selected_games as $game_id) {

ini_set('display_errors', 1);        addFavoriteGame($user_id, $game_id);

    }

// Include required files    $message = '<div class="alert alert-success">Favorieten bijgewerkt.</div>';

require_once 'db.php';}

require_once 'functions.php';?>

<!DOCTYPE html>

// Initialize session securely<html lang="nl">

if (session_status() === PHP_SESSION_NONE) {<head>

    session_start();    <meta charset="UTF-8">

}    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profiel - GamePlan Scheduler</title>

// Check if user is logged in, redirect if not    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

if (!isLoggedIn()) {    <link rel="stylesheet" href="style.css">

    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];</head>

    header("Location: login.php");<body>

    exit;    <div class="container mt-5">

}        <h2 class="text-center mb-4">Profiel</h2>

        <?php echo $message; ?>

// Get current user information        <div class="row justify-content-center">

$currentUser = getCurrentUser();            <div class="col-md-8">

$user_id = $currentUser['user_id'];                <div class="card shadow mb-4">

                    <div class="card-header bg-primary text-white">Gebruikersinformatie</div>

// Update user activity                    <div class="card-body">

updateUserActivity();                        <p><strong>Username:</strong> <?php echo htmlspecialchars($profile['username']); ?></p>

                        <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>

// Initialize variables                        <p><strong>Lid sinds:</strong> <?php echo htmlspecialchars($profile['created_at']); ?></p>

$error_message = '';                    </div>

$success_message = '';                </div>

$profile_data = [];

$favorite_games = [];                <div class="card shadow">

$security_settings = [];                    <div class="card-header bg-success text-white">Favoriete Games</div>

                    <div class="card-body">

try {                        <form method="POST">

    $db = getDBConnection();                            <div class="mb-3">

                                    <label class="form-label">Selecteer favoriete games:</label>

    // Get complete user profile data                                <?php $games = getGames(); ?>

    $stmt = $db->prepare("                                <?php foreach ($games as $game): ?>

        SELECT user_id, username, email, first_name, last_name, date_of_birth,                                     <div class="form-check">

               timezone, profile_picture, bio, privacy_level, email_notifications,                                        <input type="checkbox" name="games[]" value="<?php echo $game['game_id']; ?>" class="form-check-input" <?php if (in_array($game['titel'], array_column($favorites, 'titel'))) echo 'checked'; ?>>

               push_notifications, last_login, created_at, is_active                                        <label class="form-check-label"><?php echo htmlspecialchars($game['titel']); ?> - <?php echo htmlspecialchars($game['description']); ?></label>

        FROM Users                                     </div>

        WHERE user_id = ?                                <?php endforeach; ?>

    ");                            </div>

    $stmt->execute([$user_id]);                            <button type="submit" class="btn btn-success">Opslaan</button>

    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);                        </form>

                        </div>

    if (!$profile_data) {                </div>

        throw new Exception("User profile not found");

    }                <div class="mt-4 text-center">

                        <a href="index.php" class="btn btn-outline-primary btn-lg">Terug naar dashboard</a>

    // Get user's favorite games                </div>

    $stmt = $db->prepare("            </div>

        SELECT g.game_id, g.titel as title, g.description, g.category, ug.added_at        </div>

        FROM Games g    </div>

        JOIN UserGames ug ON g.game_id = ug.game_id</body>

        WHERE ug.user_id = ? AND g.is_active = 1</html>
        ORDER BY g.category, g.titel
    ");
    $stmt->execute([$user_id]);
    $favorite_games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get security settings
    $stmt = $db->prepare("
        SELECT failed_login_attempts, locked_until, last_password_change
        FROM Users 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $security_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Profile data error: " . $e->getMessage());
    $error_message = "Unable to load profile data. Please try again.";
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF Protection
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Security token validation failed. Please try again.');
        }
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_profile':
                $result = updateUserProfile($user_id, $_POST);
                break;
                
            case 'change_password':
                $result = changeUserPassword($user_id, $_POST);
                break;
                
            case 'update_preferences':
                $result = updateUserPreferences($user_id, $_POST);
                break;
                
            case 'upload_avatar':
                $result = uploadProfilePicture($user_id, $_FILES);
                break;
                
            default:
                throw new Exception('Invalid action specified.');
        }
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Refresh profile data
            header("Location: profile.php?updated=success");
            exit;
        } else {
            $error_message = $result['message'];
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Profile update error for user $user_id: " . $error_message);
    }
}

// Handle success message from redirect
if (isset($_GET['updated']) && $_GET['updated'] === 'success') {
    $success_message = 'Profile updated successfully!';
}

// Generate CSRF token for forms
$csrf_token = generateCSRFToken();

// Get available timezones
$timezones = [
    'America/New_York' => 'Eastern Time (ET)',
    'America/Chicago' => 'Central Time (CT)', 
    'America/Denver' => 'Mountain Time (MT)',
    'America/Los_Angeles' => 'Pacific Time (PT)',
    'America/Anchorage' => 'Alaska Time (AKT)',
    'Pacific/Honolulu' => 'Hawaii Time (HT)',
    'Europe/London' => 'Greenwich Mean Time (GMT)',
    'Europe/Paris' => 'Central European Time (CET)',
    'Europe/Berlin' => 'Central European Time (CET)',
    'Asia/Tokyo' => 'Japan Standard Time (JST)',
    'Asia/Shanghai' => 'China Standard Time (CST)',
    'Australia/Sydney' => 'Australian Eastern Time (AET)',
    'UTC' => 'Coordinated Universal Time (UTC)'
];

/**
 * Update user profile information
 */
function updateUserProfile($user_id, $post_data) {
    try {
        $db = getDBConnection();
        
        // Validate and sanitize input
        $first_name = sanitizeInput($post_data['first_name'] ?? '');
        $last_name = sanitizeInput($post_data['last_name'] ?? '');
        $email = sanitizeInput($post_data['email'] ?? '');
        $bio = sanitizeInput($post_data['bio'] ?? '');
        $date_of_birth = sanitizeInput($post_data['date_of_birth'] ?? '');
        $timezone = sanitizeInput($post_data['timezone'] ?? 'America/New_York');
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            return ['success' => false, 'message' => 'Please fill in all required fields.'];
        }
        
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        
        // Check if email is already used by another user
        $stmt = $db->prepare("SELECT user_id FROM Users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email address is already in use by another account.'];
        }
        
        // Update profile
        $stmt = $db->prepare("
            UPDATE Users 
            SET first_name = ?, last_name = ?, email = ?, bio = ?, 
                date_of_birth = ?, timezone = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $result = $stmt->execute([
            $first_name, $last_name, $email, $bio, 
            $date_of_birth ?: null, $timezone, $user_id
        ]);
        
        if ($result) {
            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            $_SESSION['timezone'] = $timezone;
            
            return ['success' => true, 'message' => 'Profile updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile. Please try again.'];
        }
        
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating your profile.'];
    }
}

/**
 * Change user password
 */
function changeUserPassword($user_id, $post_data) {
    try {
        $db = getDBConnection();
        
        $current_password = $post_data['current_password'] ?? '';
        $new_password = $post_data['new_password'] ?? '';
        $confirm_password = $post_data['confirm_password'] ?? '';
        
        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            return ['success' => false, 'message' => 'Please fill in all password fields.'];
        }
        
        if ($new_password !== $confirm_password) {
            return ['success' => false, 'message' => 'New passwords do not match.'];
        }
        
        // Validate new password strength
        $passwordValidation = validatePassword($new_password);
        if (!$passwordValidation['success']) {
            return ['success' => false, 'message' => $passwordValidation['message']];
        }
        
        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM Users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        // Update password
        $new_password_hash = password_hash($new_password, PASSWORD_ARGON2ID);
        $stmt = $db->prepare("
            UPDATE Users 
            SET password_hash = ?, last_password_change = NOW(), updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $result = $stmt->execute([$new_password_hash, $user_id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Password changed successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password. Please try again.'];
        }
        
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while changing your password.'];
    }
}

/**
 * Update user preferences
 */
function updateUserPreferences($user_id, $post_data) {
    try {
        $db = getDBConnection();
        
        $privacy_level = sanitizeInput($post_data['privacy_level'] ?? 'public');
        $email_notifications = isset($post_data['email_notifications']) ? 1 : 0;
        $push_notifications = isset($post_data['push_notifications']) ? 1 : 0;
        
        $stmt = $db->prepare("
            UPDATE Users 
            SET privacy_level = ?, email_notifications = ?, push_notifications = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $result = $stmt->execute([$privacy_level, $email_notifications, $push_notifications, $user_id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Preferences updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to update preferences. Please try again.'];
        }
        
    } catch (Exception $e) {
        error_log("Preferences update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating your preferences.'];
    }
}

/**
 * Upload profile picture
 */
function uploadProfilePicture($user_id, $files) {
    try {
        if (!isset($files['profile_picture']) || $files['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Please select a valid image file.'];
        }
        
        $file = $files['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            return ['success' => false, 'message' => 'Please upload a JPEG, PNG, or GIF image.'];
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'Image file size must be less than 5MB.'];
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = '../uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update database
            $db = getDBConnection();
            $stmt = $db->prepare("UPDATE Users SET profile_picture = ?, updated_at = NOW() WHERE user_id = ?");
            $result = $stmt->execute(['uploads/avatars/' . $filename, $user_id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Profile picture updated successfully!'];
            } else {
                unlink($filepath); // Remove uploaded file if database update fails
                return ['success' => false, 'message' => 'Failed to update profile picture in database.'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to upload image file.'];
        }
        
    } catch (Exception $e) {
        error_log("Profile picture upload error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while uploading your profile picture.'];
    }
}

?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GamePlan Scheduler Profile Management - Customize your gaming profile and preferences">
    <meta name="keywords" content="gaming, profile, settings, preferences, avatar">
    <meta name="author" content="Harsha Kanaparthi">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <title>Profile Settings - GamePlan Scheduler | Customize Your Gaming Experience</title>
    
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
        
        .profile-container {
            background: var(--gameplan-card);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin: 2rem 0;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 60%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(15deg);
            z-index: 1;
        }
        
        .profile-content {
            position: relative;
            z-index: 2;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.8);
            object-fit: cover;
        }
        
        .avatar-upload-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--gameplan-success);
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .avatar-upload-btn:hover {
            background: #157347;
            transform: scale(1.1);
        }
        
        .nav-pills .nav-link {
            border-radius: 12px;
            margin: 0.25rem;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--gameplan-primary);
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }
        
        .btn-custom {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            border: none;
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(111, 66, 193, 0.4);
            color: white;
        }
        
        .security-info {
            background: rgba(111, 66, 193, 0.1);
            border-left: 4px solid var(--gameplan-primary);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .game-chip {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            margin: 0.25rem;
            display: inline-block;
            font-size: 0.9rem;
            position: relative;
        }
        
        .game-chip .remove-btn {
            margin-left: 0.5rem;
            cursor: pointer;
            opacity: 0.8;
        }
        
        .game-chip .remove-btn:hover {
            opacity: 1;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gameplan-primary);
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .profile-header {
                padding: 1.5rem;
                border-radius: 15px 15px 0 0;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body class="py-4">
    <div class="container">
        <!-- Navigation Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../index.php" class="text-white text-decoration-none">
                        <i class="bi bi-house me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active text-white" aria-current="page">Profile Settings</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="profile-container">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-content">
                            <div class="avatar-container">
                                <img src="<?php echo $profile_data['profile_picture'] ? '../' . htmlspecialchars($profile_data['profile_picture']) : '../images/default-avatar.png'; ?>" 
                                     alt="Profile Picture" class="profile-avatar" id="profileAvatarImg">
                                <label for="avatarUpload" class="avatar-upload-btn">
                                    <i class="bi bi-camera"></i>
                                </label>
                                <input type="file" id="avatarUpload" name="profile_picture" accept="image/*" style="display: none;">
                            </div>
                            <h2 class="mb-2"><?php echo htmlspecialchars($profile_data['first_name'] . ' ' . $profile_data['last_name']); ?></h2>
                            <p class="mb-2">@<?php echo htmlspecialchars($profile_data['username']); ?></p>
                            <p class="mb-0 opacity-75">
                                <i class="bi bi-calendar me-1"></i>
                                Member since <?php echo date('F Y', strtotime($profile_data['created_at'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Profile Content -->
                    <div class="p-4">
                        <!-- Flash Messages -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Profile Stats -->
                        <div class="row mb-4">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="stats-card">
                                    <div class="stats-value"><?php echo count($favorite_games); ?></div>
                                    <div class="stats-label">Favorite Games</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="stats-card">
                                    <div class="stats-value">
                                        <?php 
                                        $days_active = floor((time() - strtotime($profile_data['created_at'])) / (60 * 60 * 24));
                                        echo $days_active;
                                        ?>
                                    </div>
                                    <div class="stats-label">Days Active</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="stats-card">
                                    <div class="stats-value">
                                        <?php echo $profile_data['last_login'] ? date('M j', strtotime($profile_data['last_login'])) : 'Today'; ?>
                                    </div>
                                    <div class="stats-label">Last Login</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="stats-card">
                                    <div class="stats-value">
                                        <i class="bi bi-shield-check text-success"></i>
                                    </div>
                                    <div class="stats-label">Account Verified</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Navigation -->
                        <ul class="nav nav-pills nav-justified mb-4" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-tab" data-bs-toggle="pill" data-bs-target="#basic" type="button" role="tab">
                                    <i class="bi bi-person me-2"></i>Basic Info
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                                    <i class="bi bi-shield-lock me-2"></i>Security
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="preferences-tab" data-bs-toggle="pill" data-bs-target="#preferences" type="button" role="tab">
                                    <i class="bi bi-gear me-2"></i>Preferences
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="games-tab" data-bs-toggle="pill" data-bs-target="#games" type="button" role="tab">
                                    <i class="bi bi-controller me-2"></i>Gaming
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Basic Information Tab -->
                            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                <form method="POST" action="profile.php" id="basicInfoForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label fw-semibold">
                                                <i class="bi bi-person me-2"></i>First Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($profile_data['first_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label fw-semibold">
                                                <i class="bi bi-person me-2"></i>Last Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($profile_data['last_name']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-semibold">
                                            <i class="bi bi-envelope me-2"></i>Email Address <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($profile_data['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label fw-semibold">
                                            <i class="bi bi-chat-quote me-2"></i>Bio
                                        </label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3" 
                                                  placeholder="Tell other gamers about yourself..."><?php echo htmlspecialchars($profile_data['bio'] ?? ''); ?></textarea>
                                        <div class="form-text">Share your gaming interests and what you're passionate about!</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="date_of_birth" class="form-label fw-semibold">
                                                <i class="bi bi-calendar-event me-2"></i>Date of Birth
                                            </label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                                   value="<?php echo htmlspecialchars($profile_data['date_of_birth'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="timezone" class="form-label fw-semibold">
                                                <i class="bi bi-globe me-2"></i>Timezone
                                            </label>
                                            <select class="form-select" id="timezone" name="timezone">
                                                <?php foreach ($timezones as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" 
                                                            <?php echo $profile_data['timezone'] === $value ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-custom">
                                            <i class="bi bi-check-circle me-2"></i>Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <!-- Security Information -->
                                <div class="security-info mb-4">
                                    <h6><i class="bi bi-info-circle me-2"></i>Account Security Status</h6>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Failed Login Attempts:</strong> <?php echo $security_settings['failed_login_attempts'] ?? 0; ?></p>
                                            <p class="mb-1"><strong>Account Status:</strong> 
                                                <span class="badge bg-success">Active & Secure</span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Last Password Change:</strong></p>
                                            <p class="mb-0">
                                                <?php echo $security_settings['last_password_change'] ? 
                                                    date('M j, Y', strtotime($security_settings['last_password_change'])) : 'Never'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Change Password Form -->
                                <form method="POST" action="profile.php" id="passwordForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label fw-semibold">
                                            <i class="bi bi-key me-2"></i>Current Password <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label fw-semibold">
                                            <i class="bi bi-shield-lock me-2"></i>New Password <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Password must be at least 8 characters with uppercase, lowercase, number, and special character.</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="confirm_password" class="form-label fw-semibold">
                                            <i class="bi bi-shield-check me-2"></i>Confirm New Password <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-custom">
                                            <i class="bi bi-shield-check me-2"></i>Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Preferences Tab -->
                            <div class="tab-pane fade" id="preferences" role="tabpanel">
                                <form method="POST" action="profile.php" id="preferencesForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="update_preferences">
                                    
                                    <div class="mb-4">
                                        <label for="privacy_level" class="form-label fw-semibold">
                                            <i class="bi bi-eye me-2"></i>Profile Privacy
                                        </label>
                                        <select class="form-select" id="privacy_level" name="privacy_level">
                                            <option value="public" <?php echo ($profile_data['privacy_level'] ?? 'public') === 'public' ? 'selected' : ''; ?>>
                                                Public - Anyone can see my profile
                                            </option>
                                            <option value="friends" <?php echo ($profile_data['privacy_level'] ?? 'public') === 'friends' ? 'selected' : ''; ?>>
                                                Friends Only - Only my friends can see my profile
                                            </option>
                                            <option value="private" <?php echo ($profile_data['privacy_level'] ?? 'public') === 'private' ? 'selected' : ''; ?>>
                                                Private - Only I can see my profile
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6><i class="bi bi-bell me-2"></i>Notification Preferences</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="email_notifications" 
                                                   name="email_notifications" value="1" 
                                                   <?php echo ($profile_data['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_notifications">
                                                Email Notifications
                                            </label>
                                            <div class="form-text">Receive email updates about friend requests, events, and important announcements</div>
                                        </div>
                                        
                                        <div class="form-check mt-3">
                                            <input class="form-check-input" type="checkbox" id="push_notifications" 
                                                   name="push_notifications" value="1" 
                                                   <?php echo ($profile_data['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="push_notifications">
                                                Push Notifications
                                            </label>
                                            <div class="form-text">Receive browser notifications for real-time updates</div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-custom">
                                            <i class="bi bi-gear me-2"></i>Update Preferences
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Gaming Tab -->
                            <div class="tab-pane fade" id="games" role="tabpanel">
                                <div class="mb-4">
                                    <h6><i class="bi bi-controller me-2"></i>Favorite Games</h6>
                                    <p class="text-muted">Manage your favorite games to connect with gamers who share similar interests.</p>
                                    
                                    <div class="favorite-games-container mb-3">
                                        <?php if (!empty($favorite_games)): ?>
                                            <?php foreach ($favorite_games as $game): ?>
                                                <span class="game-chip" data-game-id="<?php echo $game['game_id']; ?>">
                                                    <?php echo htmlspecialchars($game['title']); ?>
                                                    <span class="remove-btn" onclick="removeFavoriteGame(<?php echo $game['game_id']; ?>)">
                                                        <i class="bi bi-x"></i>
                                                    </span>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No favorite games selected yet.</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="games.php" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Manage Games
                                    </a>
                                </div>
                                
                                <div class="gaming-stats">
                                    <h6><i class="bi bi-trophy me-2"></i>Gaming Stats</h6>
                                    <div class="row">
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="stats-card">
                                                <div class="stats-value"><?php echo count($favorite_games); ?></div>
                                                <div class="stats-label">Favorite Games</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="stats-card">
                                                <div class="stats-value">0</div>
                                                <div class="stats-label">Tournaments</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="stats-card">
                                                <div class="stats-value">0</div>
                                                <div class="stats-label">Achievements</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back to Dashboard Button -->
                <div class="text-center mt-4">
                    <a href="../index.php" class="btn btn-outline-light">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Avatar Upload Form (Hidden) -->
    <form method="POST" action="profile.php" enctype="multipart/form-data" id="avatarForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="action" value="upload_avatar">
    </form>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Avatar upload functionality
            const avatarUpload = document.getElementById('avatarUpload');
            const avatarImg = document.getElementById('profileAvatarImg');
            const avatarForm = document.getElementById('avatarForm');
            
            avatarUpload.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    // Preview image
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        avatarImg.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    
                    // Submit form
                    avatarForm.appendChild(this);
                    avatarForm.submit();
                }
            });
            
            // Password confirmation validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePasswords() {
                if (newPassword.value && confirmPassword.value) {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                        confirmPassword.classList.add('is-invalid');
                    } else {
                        confirmPassword.setCustomValidity('');
                        confirmPassword.classList.remove('is-invalid');
                        confirmPassword.classList.add('is-valid');
                    }
                }
            }
            
            if (newPassword) newPassword.addEventListener('input', validatePasswords);
            if (confirmPassword) confirmPassword.addEventListener('input', validatePasswords);
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Tab persistence
            const triggerTabList = [].slice.call(document.querySelectorAll('#profileTabs button'));
            triggerTabList.forEach(function (triggerEl) {
                const tabTrigger = new bootstrap.Tab(triggerEl);
                
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });
        });
        
        // Remove favorite game function
        function removeFavoriteGame(gameId) {
            if (confirm('Are you sure you want to remove this game from your favorites?')) {
                // This would typically make an AJAX call to remove the game
                const gameChip = document.querySelector(`[data-game-id="${gameId}"]`);
                if (gameChip) {
                    gameChip.remove();
                }
                
                // You can implement the actual removal via AJAX here
                console.log('Removing game ID:', gameId);
            }
        }
        
        // Form validation
        const basicInfoForm = document.getElementById('basicInfoForm');
        if (basicInfoForm) {
            basicInfoForm.addEventListener('submit', function(e) {
                const firstName = document.getElementById('first_name').value.trim();
                const lastName = document.getElementById('last_name').value.trim();
                const email = document.getElementById('email').value.trim();
                
                if (!firstName || !lastName || !email) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }
            });
        }
        
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (!currentPassword || !newPassword || !confirmPassword) {
                    e.preventDefault();
                    alert('Please fill in all password fields.');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match.');
                    return false;
                }
                
                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('New password must be at least 8 characters long.');
                    return false;
                }
            });
        }
    </script>
</body>
</html>