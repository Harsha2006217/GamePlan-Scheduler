<?php<?php

/**/**

 * GamePlan Scheduler - Professional User Login System * GamePlan Scheduler - Login Page

 * Advanced Authentication with Security & User Experience * 

 *  * Secure login form with proper validation, CSRF protection, and user-friendly interface.

 * @author Harsha Kanaparthi * 

 * @version 2.1 Professional Edition * @author Harsha Kanaparthi

 * @date September 30, 2025 * @version 1.0

 * @description Secure login system with rate limiting, CSRF protection, and modern UI * @date 2025-09-30

 */ */



// Enable comprehensive error reporting for developmentrequire_once 'db.php';

error_reporting(E_ALL);require_once 'functions.php';

ini_set('display_errors', 1);

// Redirect if already logged in

// Include required filesif (isLoggedIn()) {

require_once 'db.php';    header('Location: index.php');

require_once 'functions.php';    exit();

}

// Initialize session securely

if (session_status() === PHP_SESSION_NONE) {$error = '';

    session_start();$success = '';

}

// Handle timeout message

// Redirect if already logged inif (isset($_GET['timeout']) && $_GET['timeout'] == '1') {

if (isLoggedIn()) {    $error = 'Your session has expired. Please log in again.';

    $redirect_url = $_SESSION['redirect_after_login'] ?? 'index.php';}

    unset($_SESSION['redirect_after_login']);

    header("Location: $redirect_url");// Handle logout message

    exit;if (isset($_GET['logout']) && $_GET['logout'] == '1') {

}    $success = 'You have been successfully logged out.';

}

// Initialize variables

$error_message = '';// Handle registration redirect message

$success_message = '';if (isset($_GET['registered']) && $_GET['registered'] == '1') {

$login_username = '';    $success = 'Registration successful! Please log in with your credentials.';

$remember_me = false;}



// Process login form submission// Process login form

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {if ($_POST) {

    try {    // Verify CSRF token

        // CSRF Protection    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {

        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {        $error = 'Security token validation failed. Please try again.';

            throw new Exception('Security token validation failed. Please try again.');    } else {

        }        $username = sanitizeInput($_POST['username'] ?? '');

                $password = $_POST['password'] ?? '';

        // Rate limiting check        

        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';        if (loginUser($username, $password)) {

        if (!checkRateLimit('login_' . $client_ip, 5, 900)) { // 5 attempts per 15 minutes            header("Location: index.php");

            throw new Exception('Too many login attempts from your IP address. Please try again in 15 minutes.');            exit;

        }        } else {

                    $error = 'Invalid login credentials.';

        // Validate input        }

        $login_username = sanitizeInput($_POST['username'] ?? '');    }

        $password = $_POST['password'] ?? '';}

        $remember_me = isset($_POST['remember_me']);

        // Generate CSRF token

        if (empty($login_username)) {$csrfToken = generateCSRFToken();

            throw new Exception('Please enter your username or email address.');?>

        }

        <!DOCTYPE html>

        if (empty($password)) {<html lang="en">

            throw new Exception('Please enter your password.');<head>

        }    <meta charset="UTF-8">

            <meta name="viewport" content="width=device-width, initial-scale=1.0">

        // Attempt login    <title>Login - GamePlan Scheduler</title>

        $login_result = loginUser($login_username, $password);    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

        if ($login_result['success']) {    <link href="../css/style.css" rel="stylesheet">

            // Set remember me cookie if requested    <style>

            if ($remember_me) {        body {

                $remember_token = bin2hex(random_bytes(32));            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);

                setcookie('gameplan_remember', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 days            min-height: 100vh;

                            display: flex;

                // Store remember token in database (you'd need to add this field to Users table)            align-items: center;

                $db = getDBConnection();            justify-content: center;

                $stmt = $db->prepare("UPDATE Users SET remember_token = ? WHERE user_id = ?");        }

                $stmt->execute([$remember_token, $login_result['user']['user_id']]);        

            }        .login-container {

                        background: rgba(255, 255, 255, 0.1);

            // Log successful login            backdrop-filter: blur(10px);

            error_log("Successful login: " . $login_username . " from IP: " . $client_ip);            border-radius: 20px;

                        padding: 2rem;

            // Set success message and redirect            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);

            $_SESSION['flash_message'] = $login_result['message'];            border: 1px solid rgba(255, 255, 255, 0.1);

            $_SESSION['flash_type'] = 'success';            max-width: 400px;

                        width: 100%;

            // Redirect to intended page or dashboard        }

            $redirect_url = $_SESSION['redirect_after_login'] ?? 'index.php';        

            unset($_SESSION['redirect_after_login']);        .brand-logo {

                        color: #00d4ff;

            header("Location: $redirect_url");            font-size: 2.5rem;

            exit;            margin-bottom: 1rem;

        } else {            text-align: center;

            throw new Exception($login_result['message']);        }

        }        

                .form-control {

    } catch (Exception $e) {            background: rgba(255, 255, 255, 0.1);

        $error_message = $e->getMessage();            border: 1px solid rgba(255, 255, 255, 0.2);

        error_log("Login error for user '$login_username' from IP $client_ip: " . $error_message);            color: white;

    }            border-radius: 10px;

}            padding: 0.75rem 1rem;

        }

// Check for logout message        

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {        .form-control:focus {

    $success_message = 'You have been logged out successfully.';            background: rgba(255, 255, 255, 0.15);

}            border-color: #00d4ff;

            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);

// Check for session timeout            color: white;

if (isset($_GET['timeout']) && $_GET['timeout'] === '1') {        }

    $error_message = 'Your session has expired. Please log in again.';        

}        .form-control::placeholder {

            color: rgba(255, 255, 255, 0.6);

// Check for registration success        }

if (isset($_GET['registered']) && $_GET['registered'] === 'success') {        

    $success_message = 'Registration successful! You can now log in with your credentials.';        .btn-primary {

}            background: linear-gradient(45deg, #00d4ff, #0099cc);

            border: none;

// Generate CSRF token for form            border-radius: 10px;

$csrf_token = generateCSRFToken();            padding: 0.75rem 1.5rem;

            font-weight: 600;

?>            text-transform: uppercase;

<!DOCTYPE html>            letter-spacing: 0.5px;

<html lang="en" class="h-100">            transition: all 0.3s ease;

<head>        }

    <meta charset="UTF-8">        

    <meta name="viewport" content="width=device-width, initial-scale=1.0">        .btn-primary:hover {

    <meta name="description" content="GamePlan Scheduler - Professional Gaming Schedule Management Platform">            background: linear-gradient(45deg, #0099cc, #00d4ff);

    <meta name="keywords" content="gaming, scheduler, esports, tournaments, friends, calendar">            transform: translateY(-2px);

    <meta name="author" content="Harsha Kanaparthi">            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.4);

    <meta name="robots" content="index, follow">        }

            

    <!-- Security Headers -->        .alert {

    <meta http-equiv="X-Content-Type-Options" content="nosniff">            border-radius: 10px;

    <meta http-equiv="X-Frame-Options" content="DENY">            border: none;

    <meta http-equiv="X-XSS-Protection" content="1; mode=block">            backdrop-filter: blur(10px);

    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">        }

            

    <title>Login - GamePlan Scheduler | Professional Gaming Platform</title>        .alert-danger {

                background: rgba(220, 53, 69, 0.2);

    <!-- Bootstrap 5.3 CSS -->            color: #ff6b6b;

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">            border: 1px solid rgba(220, 53, 69, 0.3);

            }

    <!-- Bootstrap Icons -->        

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">        .alert-success {

                background: rgba(25, 135, 84, 0.2);

    <!-- Custom CSS -->            color: #51cf66;

    <link href="../css/style.css" rel="stylesheet">            border: 1px solid rgba(25, 135, 84, 0.3);

            }

    <!-- Favicon -->        

    <link rel="icon" type="image/x-icon" href="../favicon.ico">        .register-link {

                color: #00d4ff;

    <style>            text-decoration: none;

        :root {            transition: color 0.3s ease;

            --gameplan-primary: #6f42c1;        }

            --gameplan-secondary: #e83e8c;        

            --gameplan-dark: #0d1117;        .register-link:hover {

            --gameplan-light: #f8f9fa;            color: #0099cc;

            --gameplan-success: #198754;            text-decoration: underline;

            --gameplan-danger: #dc3545;        }

            --gameplan-warning: #ffc107;        

            --gameplan-info: #0dcaf0;        .password-toggle {

        }            position: absolute;

                    right: 10px;

        body {            top: 50%;

            background: linear-gradient(135deg, var(--gameplan-dark) 0%, #1a1a2e 50%, #16213e 100%);            transform: translateY(-50%);

            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;            background: none;

            min-height: 100vh;            border: none;

        }            color: rgba(255, 255, 255, 0.6);

                    cursor: pointer;

        .login-container {            z-index: 10;

            background: rgba(255, 255, 255, 0.95);        }

            backdrop-filter: blur(10px);        

            border-radius: 20px;        .password-toggle:hover {

            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);            color: #00d4ff;

            border: 1px solid rgba(255, 255, 255, 0.2);        }

        }    </style>

        </head>

        .login-header {<body>

            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));    <div class="login-container">

            color: white;        <div class="brand-logo">

            border-radius: 20px 20px 0 0;            <i class="fas fa-gamepad"></i>

            padding: 2rem;            <div class="h3 mt-2">GamePlan</div>

            text-align: center;            <small class="text-muted">Gaming Schedule Manager</small>

        }        </div>

                

        .login-form {        <?php if ($error): ?>

            padding: 2rem;            <div class="alert alert-danger alert-dismissible fade show" role="alert">

        }                <i class="fas fa-exclamation-triangle me-2"></i>

                        <?php echo htmlspecialchars($error); ?>

        .form-control {                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            border-radius: 12px;            </div>

            border: 2px solid #e9ecef;        <?php endif; ?>

            padding: 0.75rem 1rem;        

            font-size: 1rem;        <?php if ($success): ?>

            transition: all 0.3s ease;            <div class="alert alert-success alert-dismissible fade show" role="alert">

        }                <i class="fas fa-check-circle me-2"></i>

                        <?php echo htmlspecialchars($success); ?>

        .form-control:focus {                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            border-color: var(--gameplan-primary);            </div>

            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);        <?php endif; ?>

        }        

                <form method="POST" class="needs-validation" novalidate>

        .btn-login {            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));            

            border: none;            <div class="mb-3">

            border-radius: 12px;                <label for="username" class="form-label text-white">

            padding: 0.75rem 2rem;                    <i class="fas fa-user me-2"></i>Username

            font-weight: 600;                </label>

            font-size: 1.1rem;                <input 

            transition: all 0.3s ease;                    type="text" 

        }                    class="form-control" 

                            id="username" 

        .btn-login:hover {                    name="username" 

            transform: translateY(-2px);                    placeholder="Enter your username"

            box-shadow: 0 10px 20px rgba(111, 66, 193, 0.3);                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"

        }                    required

                            autocomplete="username"

        .btn-secondary-outline {                >

            border: 2px solid var(--gameplan-primary);                <div class="invalid-feedback">

            color: var(--gameplan-primary);                    Please enter a valid username.

            border-radius: 12px;                </div>

            padding: 0.75rem 2rem;            </div>

            font-weight: 600;            

            transition: all 0.3s ease;            <div class="mb-3 position-relative">

        }                <label for="password" class="form-label text-white">

                            <i class="fas fa-lock me-2"></i>Password

        .btn-secondary-outline:hover {                </label>

            background: var(--gameplan-primary);                <input 

            color: white;                    type="password" 

            transform: translateY(-2px);                    class="form-control" 

        }                    id="password" 

                            name="password" 

        .alert {                    placeholder="Enter your password"

            border-radius: 12px;                    required

            border: none;                    autocomplete="current-password"

            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);                >

        }                <button type="button" class="password-toggle" onclick="togglePassword()">

                            <i class="fas fa-eye" id="password-eye"></i>

        .form-check-input:checked {                </button>

            background-color: var(--gameplan-primary);                <div class="invalid-feedback">

            border-color: var(--gameplan-primary);                    Please enter your password.

        }                </div>

                    </div>

        .login-footer {            

            background: #f8f9fa;            <div class="d-grid gap-2">

            border-radius: 0 0 20px 20px;                <button type="submit" class="btn btn-primary btn-lg">

            padding: 1.5rem;                    <i class="fas fa-sign-in-alt me-2"></i>Login

            text-align: center;                </button>

        }            </div>

                </form>

        .gaming-icon {        

            font-size: 3rem;        <div class="text-center mt-4">

            margin-bottom: 1rem;            <p class="text-white-50 mb-2">Don't have an account?</p>

            animation: pulse 2s infinite;            <a href="register.php" class="register-link">

        }                <i class="fas fa-user-plus me-2"></i>Create Account

                    </a>

        @keyframes pulse {        </div>

            0% { transform: scale(1); }        

            50% { transform: scale(1.05); }        <div class="text-center mt-4">

            100% { transform: scale(1); }            <small class="text-white-50">

        }                <i class="fas fa-shield-alt me-1"></i>

                        Secure login with advanced encryption

        .floating-shapes {            </small>

            position: fixed;        </div>

            top: 0;    </div>

            left: 0;

            width: 100%;    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

            height: 100%;    <script>

            pointer-events: none;        // Form validation

            z-index: -1;        (function() {

        }            'use strict';

                    window.addEventListener('load', function() {

        .shape {                var forms = document.getElementsByClassName('needs-validation');

            position: absolute;                var validation = Array.prototype.filter.call(forms, function(form) {

            background: rgba(111, 66, 193, 0.1);                    form.addEventListener('submit', function(event) {

            border-radius: 50%;                        if (form.checkValidity() === false) {

            animation: float 6s ease-in-out infinite;                            event.preventDefault();

        }                            event.stopPropagation();

                                }

        .shape:nth-child(1) {                        form.classList.add('was-validated');

            width: 80px;                    }, false);

            height: 80px;                });

            top: 20%;            }, false);

            left: 10%;        })();

            animation-delay: 0s;        

        }        // Password toggle functionality

                function togglePassword() {

        .shape:nth-child(2) {            const passwordField = document.getElementById('password');

            width: 120px;            const passwordEye = document.getElementById('password-eye');

            height: 120px;            

            top: 60%;            if (passwordField.type === 'password') {

            right: 10%;                passwordField.type = 'text';

            animation-delay: 2s;                passwordEye.classList.remove('fa-eye');

        }                passwordEye.classList.add('fa-eye-slash');

                    } else {

        .shape:nth-child(3) {                passwordField.type = 'password';

            width: 60px;                passwordEye.classList.remove('fa-eye-slash');

            height: 60px;                passwordEye.classList.add('fa-eye');

            bottom: 20%;            }

            left: 20%;        }

            animation-delay: 4s;        

        }        // Auto-dismiss alerts after 5 seconds

                setTimeout(function() {

        @keyframes float {            const alerts = document.querySelectorAll('.alert');

            0%, 100% { transform: translateY(0px) rotate(0deg); }            alerts.forEach(function(alert) {

            50% { transform: translateY(-20px) rotate(180deg); }                const bsAlert = new bootstrap.Alert(alert);

        }                bsAlert.close();

                    });

        .input-group {        }, 5000);

            position: relative;        

        }        // Focus on username field on load

                document.addEventListener('DOMContentLoaded', function() {

        .input-group-text {            document.getElementById('username').focus();

            background: transparent;        });

            border: 2px solid #e9ecef;    </script>

            border-right: none;</body>

            border-radius: 12px 0 0 12px;</html>
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        .password-toggle {
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--gameplan-primary);
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .login-header {
                padding: 1.5rem;
                border-radius: 15px 15px 0 0;
            }
            
            .login-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <!-- Login Header -->
                    <div class="login-header">
                        <div class="gaming-icon">
                            <i class="bi bi-controller"></i>
                        </div>
                        <h1 class="h3 mb-2">Welcome Back, Gamer!</h1>
                        <p class="mb-0">Sign in to access your gaming dashboard</p>
                    </div>

                    <!-- Login Form -->
                    <div class="login-form">
                        <!-- Success Message -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Error Message -->
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php" id="loginForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- Username/Email Field -->
                            <div class="mb-4">
                                <label for="username" class="form-label fw-semibold">
                                    <i class="bi bi-person-fill me-2"></i>Username or Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-at"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo htmlspecialchars($login_username); ?>"
                                           placeholder="Enter your username or email"
                                           required
                                           autocomplete="username"
                                           autofocus>
                                    <div class="invalid-feedback">
                                        Please enter your username or email address.
                                    </div>
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="bi bi-lock-fill me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-key"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter your password"
                                           required
                                           autocomplete="current-password">
                                    <button class="btn btn-outline-secondary password-toggle" 
                                            type="button" 
                                            id="passwordToggle"
                                            tabindex="-1">
                                        <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Please enter your password.
                                    </div>
                                </div>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="row mb-4">
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               value="1" 
                                               id="remember_me" 
                                               name="remember_me"
                                               <?php echo $remember_me ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="remember_me">
                                            Remember me for 30 days
                                        </label>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <a href="forgot-password.php" class="text-decoration-none">
                                        <small>Forgot Password?</small>
                                    </a>
                                </div>
                            </div>

                            <!-- Login Button -->
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" 
                                        name="login_submit" 
                                        class="btn btn-primary btn-login"
                                        id="loginButton">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Sign In to GamePlan
                                </button>
                            </div>

                            <!-- Social Login Options (Future Enhancement) -->
                            <div class="text-center mb-3">
                                <small class="text-muted">Or sign in with</small>
                            </div>
                            
                            <div class="row g-2 mb-4">
                                <div class="col">
                                    <button type="button" class="btn btn-outline-dark w-100" disabled>
                                        <i class="bi bi-google me-2"></i>Google
                                    </button>
                                </div>
                                <div class="col">
                                    <button type="button" class="btn btn-outline-primary w-100" disabled>
                                        <i class="bi bi-discord me-2"></i>Discord
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Login Footer -->
                    <div class="login-footer">
                        <p class="mb-2">
                            <small class="text-muted">Don't have an account?</small>
                        </p>
                        <a href="register.php" class="btn btn-secondary-outline">
                            <i class="bi bi-person-plus me-2"></i>Create New Account
                        </a>
                        
                        <hr class="my-3">
                        
                        <div class="row text-center">
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Secure Login
                                </small>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    24/7 Available
                                </small>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-headset me-1"></i>
                                    Support Ready
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Links -->
                <div class="text-center mt-4">
                    <div class="row">
                        <div class="col">
                            <a href="../index.php" class="text-white text-decoration-none">
                                <small><i class="bi bi-house me-1"></i>Home</small>
                            </a>
                        </div>
                        <div class="col">
                            <a href="privacy.php" class="text-white text-decoration-none">
                                <small><i class="bi bi-shield me-1"></i>Privacy</small>
                            </a>
                        </div>
                        <div class="col">
                            <a href="help.php" class="text-white text-decoration-none">
                                <small><i class="bi bi-question-circle me-1"></i>Help</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordField = document.getElementById('password');
            const passwordToggleIcon = document.getElementById('passwordToggleIcon');
            
            passwordToggle.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                passwordToggleIcon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
            
            // Form validation
            const loginForm = document.getElementById('loginForm');
            
            loginForm.addEventListener('submit', function(event) {
                event.preventDefault();
                event.stopPropagation();
                
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;
                
                let isValid = true;
                
                // Username validation
                if (!username) {
                    document.getElementById('username').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('username').classList.remove('is-invalid');
                    document.getElementById('username').classList.add('is-valid');
                }
                
                // Password validation
                if (!password) {
                    document.getElementById('password').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('password').classList.remove('is-invalid');
                    document.getElementById('password').classList.add('is-valid');
                }
                
                if (isValid) {
                    // Show loading state
                    const loginButton = document.getElementById('loginButton');
                    const originalText = loginButton.innerHTML;
                    loginButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Signing In...';
                    loginButton.disabled = true;
                    
                    // Submit form
                    this.submit();
                }
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Enhanced security: Detect multiple failed attempts
            let failedAttempts = parseInt(localStorage.getItem('loginFailedAttempts') || '0');
            
            if (failedAttempts >= 3) {
                // Add extra security measures for suspicious activity
                console.warn('Multiple failed login attempts detected');
            }
            
            // Focus management
            const usernameField = document.getElementById('username');
            if (usernameField.value === '') {
                usernameField.focus();
            } else {
                document.getElementById('password').focus();
            }
        });
        
        // CSRF protection verification
        function verifyCsrfToken() {
            const token = document.querySelector('input[name="csrf_token"]').value;
            if (!token || token.length < 32) {
                console.error('CSRF token missing or invalid');
                return false;
            }
            return true;
        }
        
        // Rate limiting warning
        let requestCount = 0;
        const maxRequests = 5;
        const timeWindow = 15 * 60 * 1000; // 15 minutes
        
        function checkRateLimit() {
            requestCount++;
            if (requestCount > maxRequests) {
                alert('Too many requests. Please wait before trying again.');
                return false;
            }
            
            setTimeout(() => requestCount--, timeWindow);
            return true;
        }
    </script>
</body>
</html>