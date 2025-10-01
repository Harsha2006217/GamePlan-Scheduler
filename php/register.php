<?php<?php

/**/**

 * GamePlan Scheduler - Professional User Registration System * GamePlan Scheduler - Registration Page

 * Advanced Registration with Security & User Experience * 

 *  * Secure registration form with comprehensive validation, password strength checking,

 * @author Harsha Kanaparthi * and user-friendly interface.

 * @version 2.1 Professional Edition * 

 * @date September 30, 2025 * @author Harsha Kanaparthi

 * @description Secure registration system with comprehensive validation and modern UI * @version 1.0

 */ * @date 2025-09-30

 */

// Enable comprehensive error reporting for development

error_reporting(E_ALL);require_once 'db.php';

ini_set('display_errors', 1);require_once 'functions.php';



// Include required files// Redirect if already logged in

require_once 'db.php';if (isLoggedIn()) {

require_once 'functions.php';    header('Location: index.php');

    exit();

// Initialize session securely}

if (session_status() === PHP_SESSION_NONE) {

    session_start();$message = '';

}$formData = ['username' => '', 'email' => ''];



// Redirect if already logged in// Process registration form

if (isLoggedIn()) {if ($_POST) {

    header("Location: ../index.php");    // Verify CSRF token

    exit;    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {

}        $message = '<div class="alert alert-danger">Security token validation failed. Please try again.</div>';

    } else {

// Initialize variables        $username = sanitizeInput($_POST['username'] ?? '');

$error_message = '';        $email = sanitizeInput($_POST['email'] ?? '');

$success_message = '';        $password = $_POST['password'] ?? '';

$form_data = [        $confirmPassword = $_POST['confirm_password'] ?? '';

    'username' => '',        

    'email' => '',        // Store form data for re-population

    'first_name' => '',        $formData = ['username' => $username, 'email' => $email];

    'last_name' => '',        

    'date_of_birth' => '',        // Comprehensive validation

    'timezone' => 'America/New_York'        $validationErrors = [];

];        

        // Username validation

// Process registration form submission        if (empty($username)) {

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {            $validationErrors[] = 'Username is required.';

    try {        } elseif (strlen($username) < 3) {

        // CSRF Protection            $validationErrors[] = 'Username must be at least 3 characters.';

        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {        } elseif (strlen($username) > 50) {

            throw new Exception('Security token validation failed. Please try again.');            $validationErrors[] = 'Username must be less than 50 characters.';

        }        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {

                    $validationErrors[] = 'Username can only contain letters, numbers, and underscores.';

        // Rate limiting check        }

        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';        

        if (!checkRateLimit('register_' . $client_ip, 3, 3600)) { // 3 attempts per hour        // Email validation

            throw new Exception('Too many registration attempts from your IP address. Please try again in 1 hour.');        if (empty($email)) {

        }            $validationErrors[] = 'Email is required.';

                } elseif (!validateEmail($email)) {

        // Collect and sanitize form data            $validationErrors[] = 'Please enter a valid email address.';

        $form_data = [        }

            'username' => sanitizeInput($_POST['username'] ?? ''),        

            'email' => sanitizeInput($_POST['email'] ?? ''),        // Password validation

            'password' => $_POST['password'] ?? '',        if (empty($password)) {

            'confirm_password' => $_POST['confirm_password'] ?? '',            $validationErrors[] = 'Password is required.';

            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),        } elseif (strlen($password) < 6) {

            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),            $validationErrors[] = 'Password must be at least 6 characters.';

            'date_of_birth' => sanitizeInput($_POST['date_of_birth'] ?? ''),        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {

            'timezone' => sanitizeInput($_POST['timezone'] ?? 'America/New_York'),            $validationErrors[] = 'Password must contain at least one lowercase letter, one uppercase letter, and one number.';

            'terms_agreed' => isset($_POST['terms_agreed'])        }

        ];        

                // Confirm password validation

        // Comprehensive validation        if (empty($confirmPassword)) {

        $validation_errors = [];            $validationErrors[] = 'Please confirm your password.';

                } elseif ($password !== $confirmPassword) {

        // Username validation            $validationErrors[] = 'Passwords do not match.';

        if (empty($form_data['username'])) {        }

            $validation_errors[] = 'Username is required.';        

        } elseif (strlen($form_data['username']) < 3) {        if (!empty($validationErrors)) {

            $validation_errors[] = 'Username must be at least 3 characters long.';            $message = '<div class="alert alert-danger">' . implode('<br>', $validationErrors) . '</div>';

        } elseif (strlen($form_data['username']) > 30) {        } else {

            $validation_errors[] = 'Username must be no more than 30 characters long.';            // Attempt registration

        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $form_data['username'])) {            $result = registerUser($username, $email, $password);

            $validation_errors[] = 'Username can only contain letters, numbers, underscores, and hyphens.';            

        }            if ($result['success']) {

                        $message = '<div class="alert alert-success">Registratie succesvol. <a href="login.php">Inloggen</a></div>';

        // Email validation                // Redirect to login page after successful registration

        if (empty($form_data['email'])) {                header('Location: login.php?registered=1');

            $validation_errors[] = 'Email address is required.';                exit();

        } elseif (!validateEmail($form_data['email'])) {            } else {

            $validation_errors[] = 'Please enter a valid email address.';                $message = '<div class="alert alert-danger">Registratie mislukt. Controleer invoer.</div>';

        }            }

                }

        // Password validation    }

        if (empty($form_data['password'])) {}

            $validation_errors[] = 'Password is required.';

        } else {// Generate CSRF token

            $passwordValidation = validatePassword($form_data['password']);$csrfToken = generateCSRFToken();

            if (!$passwordValidation['success']) {?>

                $validation_errors[] = $passwordValidation['message'];

            }<!DOCTYPE html>

        }<html lang="en">

        <head>

        // Confirm password validation    <meta charset="UTF-8">

        if (empty($form_data['confirm_password'])) {    <meta name="viewport" content="width=device-width, initial-scale=1.0">

            $validation_errors[] = 'Please confirm your password.';    <title>Register - GamePlan Scheduler</title>

        } elseif ($form_data['password'] !== $form_data['confirm_password']) {    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

            $validation_errors[] = 'Passwords do not match.';    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

        }    <link href="../css/style.css" rel="stylesheet">

            <style>

        // Name validation        body {

        if (empty($form_data['first_name'])) {            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);

            $validation_errors[] = 'First name is required.';            min-height: 100vh;

        } elseif (strlen($form_data['first_name']) > 50) {            display: flex;

            $validation_errors[] = 'First name must be no more than 50 characters long.';            align-items: center;

        }            justify-content: center;

                    padding: 2rem 0;

        if (empty($form_data['last_name'])) {        }

            $validation_errors[] = 'Last name is required.';        

        } elseif (strlen($form_data['last_name']) > 50) {        .register-container {

            $validation_errors[] = 'Last name must be no more than 50 characters long.';            background: rgba(255, 255, 255, 0.1);

        }            backdrop-filter: blur(10px);

                    border-radius: 20px;

        // Date of birth validation (optional but validate if provided)            padding: 2rem;

        if (!empty($form_data['date_of_birth'])) {            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);

            $dob = DateTime::createFromFormat('Y-m-d', $form_data['date_of_birth']);            border: 1px solid rgba(255, 255, 255, 0.1);

            if (!$dob) {            max-width: 450px;

                $validation_errors[] = 'Please enter a valid date of birth.';            width: 100%;

            } else {        }

                $age = $dob->diff(new DateTime())->y;        

                if ($age < 13) {        .brand-logo {

                    $validation_errors[] = 'You must be at least 13 years old to register.';            color: #00d4ff;

                } elseif ($age > 120) {            font-size: 2.5rem;

                    $validation_errors[] = 'Please enter a valid date of birth.';            margin-bottom: 1rem;

                }            text-align: center;

            }        }

        }        

                .form-control {

        // Terms agreement validation            background: rgba(255, 255, 255, 0.1);

        if (!$form_data['terms_agreed']) {            border: 1px solid rgba(255, 255, 255, 0.2);

            $validation_errors[] = 'You must agree to the Terms of Service and Privacy Policy.';            color: white;

        }            border-radius: 10px;

                    padding: 0.75rem 1rem;

        // If there are validation errors, throw an exception        }

        if (!empty($validation_errors)) {        

            throw new Exception(implode('<br>', $validation_errors));        .form-control:focus {

        }            background: rgba(255, 255, 255, 0.15);

                    border-color: #00d4ff;

        // Attempt registration            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);

        $registration_result = registerNewUser($form_data);            color: white;

                }

        if ($registration_result['success']) {        

            // Log successful registration        .form-control::placeholder {

            error_log("Successful registration: " . $form_data['username'] . " from IP: " . $client_ip);            color: rgba(255, 255, 255, 0.6);

                    }

            // Redirect to login page with success message        

            header("Location: login.php?registered=success");        .btn-primary {

            exit;            background: linear-gradient(45deg, #00d4ff, #0099cc);

        } else {            border: none;

            throw new Exception($registration_result['message']);            border-radius: 10px;

        }            padding: 0.75rem 1.5rem;

                    font-weight: 600;

    } catch (Exception $e) {            text-transform: uppercase;

        $error_message = $e->getMessage();            letter-spacing: 0.5px;

        error_log("Registration error for user '{$form_data['username']}' from IP $client_ip: " . $error_message);            transition: all 0.3s ease;

    }        }

}        

        .btn-primary:hover {

// Generate CSRF token for form            background: linear-gradient(45deg, #0099cc, #00d4ff);

$csrf_token = generateCSRFToken();            transform: translateY(-2px);

            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.4);

// Get list of timezones for the select dropdown        }

$timezones = [        

    'America/New_York' => 'Eastern Time (ET)',        .alert {

    'America/Chicago' => 'Central Time (CT)',            border-radius: 10px;

    'America/Denver' => 'Mountain Time (MT)',            border: none;

    'America/Los_Angeles' => 'Pacific Time (PT)',            backdrop-filter: blur(10px);

    'America/Anchorage' => 'Alaska Time (AKT)',        }

    'Pacific/Honolulu' => 'Hawaii Time (HT)',        

    'Europe/London' => 'Greenwich Mean Time (GMT)',        .alert-danger {

    'Europe/Paris' => 'Central European Time (CET)',            background: rgba(220, 53, 69, 0.2);

    'Europe/Berlin' => 'Central European Time (CET)',            color: #ff6b6b;

    'Asia/Tokyo' => 'Japan Standard Time (JST)',            border: 1px solid rgba(220, 53, 69, 0.3);

    'Asia/Shanghai' => 'China Standard Time (CST)',        }

    'Australia/Sydney' => 'Australian Eastern Time (AET)',        

    'UTC' => 'Coordinated Universal Time (UTC)'        .alert-success {

];            background: rgba(25, 135, 84, 0.2);

            color: #51cf66;

?>            border: 1px solid rgba(25, 135, 84, 0.3);

<!DOCTYPE html>        }

<html lang="en" class="h-100">        

<head>        .login-link {

    <meta charset="UTF-8">            color: #00d4ff;

    <meta name="viewport" content="width=device-width, initial-scale=1.0">            text-decoration: none;

    <meta name="description" content="Create your GamePlan Scheduler account - Join the ultimate gaming schedule management platform">            transition: color 0.3s ease;

    <meta name="keywords" content="gaming, scheduler, esports, tournaments, friends, calendar, registration">        }

    <meta name="author" content="Harsha Kanaparthi">        

    <meta name="robots" content="index, follow">        .login-link:hover {

                color: #0099cc;

    <!-- Security Headers -->            text-decoration: underline;

    <meta http-equiv="X-Content-Type-Options" content="nosniff">        }

    <meta http-equiv="X-Frame-Options" content="DENY">        

    <meta http-equiv="X-XSS-Protection" content="1; mode=block">        .password-toggle {

    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">            position: absolute;

                right: 10px;

    <title>Create Account - GamePlan Scheduler | Join the Gaming Community</title>            top: 50%;

                transform: translateY(-50%);

    <!-- Bootstrap 5.3 CSS -->            background: none;

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">            border: none;

                color: rgba(255, 255, 255, 0.6);

    <!-- Bootstrap Icons -->            cursor: pointer;

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">            z-index: 10;

            }

    <!-- Custom CSS -->        

    <link href="../css/style.css" rel="stylesheet">        .password-toggle:hover {

                color: #00d4ff;

    <!-- Favicon -->        }

    <link rel="icon" type="image/x-icon" href="../favicon.ico">        

            .password-strength {

    <style>            margin-top: 0.5rem;

        :root {        }

            --gameplan-primary: #6f42c1;        

            --gameplan-secondary: #e83e8c;        .strength-bar {

            --gameplan-dark: #0d1117;            height: 4px;

            --gameplan-light: #f8f9fa;            border-radius: 2px;

            --gameplan-success: #198754;            background: rgba(255, 255, 255, 0.2);

            --gameplan-danger: #dc3545;            overflow: hidden;

            --gameplan-warning: #ffc107;        }

            --gameplan-info: #0dcaf0;        

        }        .strength-fill {

                    height: 100%;

        body {            transition: all 0.3s ease;

            background: linear-gradient(135deg, var(--gameplan-dark) 0%, #1a1a2e 50%, #16213e 100%);            width: 0%;

            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;        }

            min-height: 100vh;        

        }        .strength-weak { background: #ff4757; width: 25%; }

                .strength-fair { background: #ffa502; width: 50%; }

        .register-container {        .strength-good { background: #2ed573; width: 75%; }

            background: rgba(255, 255, 255, 0.95);        .strength-strong { background: #1e90ff; width: 100%; }

            backdrop-filter: blur(10px);        

            border-radius: 20px;        .requirements {

            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);            font-size: 0.8rem;

            border: 1px solid rgba(255, 255, 255, 0.2);            margin-top: 0.5rem;

            margin: 2rem 0;        }

        }        

                .requirement {

        .register-header {            color: rgba(255, 255, 255, 0.6);

            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));            transition: color 0.3s ease;

            color: white;        }

            border-radius: 20px 20px 0 0;        

            padding: 2rem;        .requirement.met {

            text-align: center;            color: #2ed573;

        }        }

                

        .register-form {        .requirement i {

            padding: 2rem;            margin-right: 0.5rem;

        }        }

            </style>

        .form-control {</head>

            border-radius: 12px;<body>

            border: 2px solid #e9ecef;    <div class="register-container">

            padding: 0.75rem 1rem;        <div class="brand-logo">

            font-size: 1rem;            <i class="fas fa-gamepad"></i>

            transition: all 0.3s ease;            <div class="h3 mt-2">GamePlan</div>

        }            <small class="text-muted">Join the Gaming Community</small>

                </div>

        .form-control:focus {        

            border-color: var(--gameplan-primary);        <?php echo $message; ?>

            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);        

        }        <form method="POST" class="needs-validation" novalidate>

                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        .form-select {            

            border-radius: 12px;            <div class="mb-3">

            border: 2px solid #e9ecef;                <label for="username" class="form-label text-white">

            padding: 0.75rem 1rem;                    <i class="fas fa-user me-2"></i>Username

            transition: all 0.3s ease;                </label>

        }                <input 

                            type="text" 

        .form-select:focus {                    class="form-control" 

            border-color: var(--gameplan-primary);                    id="username" 

            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);                    name="username" 

        }                    placeholder="Choose a unique username"

                            value="<?php echo htmlspecialchars($formData['username']); ?>"

        .btn-register {                    required

            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));                    autocomplete="username"

            border: none;                    pattern="[a-zA-Z0-9_]{3,50}"

            border-radius: 12px;                >

            padding: 0.75rem 2rem;                <div class="invalid-feedback">

            font-weight: 600;                    Username must be 3-50 characters and contain only letters, numbers, and underscores.

            font-size: 1.1rem;                </div>

            transition: all 0.3s ease;                <small class="text-white-50">3-50 characters, letters, numbers, and underscores only</small>

        }            </div>

                    

        .btn-register:hover {            <div class="mb-3">

            transform: translateY(-2px);                <label for="email" class="form-label text-white">

            box-shadow: 0 10px 20px rgba(111, 66, 193, 0.3);                    <i class="fas fa-envelope me-2"></i>Email Address

        }                </label>

                        <input 

        .btn-secondary-outline {                    type="email" 

            border: 2px solid var(--gameplan-primary);                    class="form-control" 

            color: var(--gameplan-primary);                    id="email" 

            border-radius: 12px;                    name="email" 

            padding: 0.75rem 2rem;                    placeholder="Enter your email address"

            font-weight: 600;                    value="<?php echo htmlspecialchars($formData['email']); ?>"

            transition: all 0.3s ease;                    required

        }                    autocomplete="email"

                        >

        .btn-secondary-outline:hover {                <div class="invalid-feedback">

            background: var(--gameplan-primary);                    Please enter a valid email address.

            color: white;                </div>

            transform: translateY(-2px);            </div>

        }            

                    <div class="mb-3 position-relative">

        .alert {                <label for="password" class="form-label text-white">

            border-radius: 12px;                    <i class="fas fa-lock me-2"></i>Password

            border: none;                </label>

            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);                <input 

        }                    type="password" 

                            class="form-control" 

        .form-check-input:checked {                    id="password" 

            background-color: var(--gameplan-primary);                    name="password" 

            border-color: var(--gameplan-primary);                    placeholder="Create a strong password"

        }                    required

                            autocomplete="new-password"

        .register-footer {                    oninput="checkPasswordStrength()"

            background: #f8f9fa;                >

            border-radius: 0 0 20px 20px;                <button type="button" class="password-toggle" onclick="togglePassword('password', 'password-eye')">

            padding: 1.5rem;                    <i class="fas fa-eye" id="password-eye"></i>

            text-align: center;                </button>

        }                <div class="invalid-feedback">

                            Password must meet the requirements below.

        .gaming-icon {                </div>

            font-size: 3rem;                

            margin-bottom: 1rem;                <div class="password-strength">

            animation: bounce 2s infinite;                    <div class="strength-bar">

        }                        <div class="strength-fill" id="strength-fill"></div>

                            </div>

        @keyframes bounce {                    <small id="strength-text" class="text-white-50"></small>

            0%, 20%, 53%, 80%, 100% { transform: translate3d(0,0,0); }                </div>

            40%, 43% { transform: translate3d(0,-30px,0); }                

            70% { transform: translate3d(0,-15px,0); }                <div class="requirements">

            90% { transform: translate3d(0,-4px,0); }                    <div class="requirement" id="req-length">

        }                        <i class="fas fa-times"></i>At least 6 characters

                            </div>

        .floating-shapes {                    <div class="requirement" id="req-lower">

            position: fixed;                        <i class="fas fa-times"></i>One lowercase letter

            top: 0;                    </div>

            left: 0;                    <div class="requirement" id="req-upper">

            width: 100%;                        <i class="fas fa-times"></i>One uppercase letter

            height: 100%;                    </div>

            pointer-events: none;                    <div class="requirement" id="req-number">

            z-index: -1;                        <i class="fas fa-times"></i>One number

        }                    </div>

                        </div>

        .shape {            </div>

            position: absolute;            

            background: rgba(111, 66, 193, 0.1);            <div class="mb-3 position-relative">

            border-radius: 50%;                <label for="confirm_password" class="form-label text-white">

            animation: float 8s ease-in-out infinite;                    <i class="fas fa-lock me-2"></i>Confirm Password

        }                </label>

                        <input 

        .shape:nth-child(1) {                    type="password" 

            width: 100px;                    class="form-control" 

            height: 100px;                    id="confirm_password" 

            top: 10%;                    name="confirm_password" 

            left: 10%;                    placeholder="Confirm your password"

            animation-delay: 0s;                    required

        }                    autocomplete="new-password"

                            oninput="checkPasswordMatch()"

        .shape:nth-child(2) {                >

            width: 80px;                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'confirm-eye')">

            height: 80px;                    <i class="fas fa-eye" id="confirm-eye"></i>

            top: 70%;                </button>

            right: 10%;                <div class="invalid-feedback" id="password-match-feedback">

            animation-delay: 3s;                    Passwords do not match.

        }                </div>

                    </div>

        .shape:nth-child(3) {            

            width: 120px;            <div class="d-grid gap-2">

            height: 120px;                <button type="submit" class="btn btn-primary btn-lg">

            bottom: 20%;                    <i class="fas fa-user-plus me-2"></i>Create Account

            left: 20%;                </button>

            animation-delay: 6s;            </div>

        }        </form>

                

        .shape:nth-child(4) {        <div class="text-center mt-4">

            width: 60px;            <p class="text-white-50 mb-2">Already have an account?</p>

            height: 60px;            <a href="login.php" class="login-link">

            top: 40%;                <i class="fas fa-sign-in-alt me-2"></i>Sign In

            right: 30%;            </a>

            animation-delay: 2s;        </div>

        }        

                <div class="text-center mt-4">

        @keyframes float {            <small class="text-white-50">

            0%, 100% { transform: translateY(0px) rotate(0deg); }                <i class="fas fa-shield-alt me-1"></i>

            33% { transform: translateY(-20px) rotate(120deg); }                Your data is protected with advanced encryption

            66% { transform: translateY(10px) rotate(240deg); }            </small>

        }        </div>

            </div>

        .input-group {

            position: relative;    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        }    <script>

                // Form validation

        .input-group-text {        (function() {

            background: transparent;            'use strict';

            border: 2px solid #e9ecef;            window.addEventListener('load', function() {

            border-right: none;                var forms = document.getElementsByClassName('needs-validation');

            border-radius: 12px 0 0 12px;                var validation = Array.prototype.filter.call(forms, function(form) {

        }                    form.addEventListener('submit', function(event) {

                                if (form.checkValidity() === false) {

        .input-group .form-control {                            event.preventDefault();

            border-left: none;                            event.stopPropagation();

            border-radius: 0 12px 12px 0;                        }

        }                        form.classList.add('was-validated');

                            }, false);

        .password-strength {                });

            margin-top: 0.5rem;            }, false);

        }        })();

                

        .strength-indicator {        // Password toggle functionality

            height: 4px;        function togglePassword(fieldId, eyeId) {

            border-radius: 2px;            const passwordField = document.getElementById(fieldId);

            transition: all 0.3s ease;            const passwordEye = document.getElementById(eyeId);

        }            

                    if (passwordField.type === 'password') {

        .strength-weak { background: #dc3545; }                passwordField.type = 'text';

        .strength-medium { background: #ffc107; }                passwordEye.classList.remove('fa-eye');

        .strength-strong { background: #198754; }                passwordEye.classList.add('fa-eye-slash');

                    } else {

        .password-requirements {                passwordField.type = 'password';

            font-size: 0.875rem;                passwordEye.classList.remove('fa-eye-slash');

            margin-top: 0.5rem;                passwordEye.classList.add('fa-eye');

        }            }

                }

        .requirement {        

            color: #6c757d;        // Password strength checker

            transition: color 0.3s ease;        function checkPasswordStrength() {

        }            const password = document.getElementById('password').value;

                    const strengthFill = document.getElementById('strength-fill');

        .requirement.met {            const strengthText = document.getElementById('strength-text');

            color: #198754;            

        }            // Requirements check

                    const requirements = {

        @media (max-width: 768px) {                length: password.length >= 6,

            .register-container {                lower: /[a-z]/.test(password),

                margin: 1rem;                upper: /[A-Z]/.test(password),

                border-radius: 15px;                number: /\d/.test(password)

            }            };

                        

            .register-header {            // Update requirement indicators

                padding: 1.5rem;            Object.keys(requirements).forEach(req => {

                border-radius: 15px 15px 0 0;                const element = document.getElementById('req-' + req);

            }                const icon = element.querySelector('i');

                            

            .register-form {                if (requirements[req]) {

                padding: 1.5rem;                    element.classList.add('met');

            }                    icon.classList.remove('fa-times');

        }                    icon.classList.add('fa-check');

    </style>                } else {

</head>                    element.classList.remove('met');

<body class="d-flex align-items-center py-4">                    icon.classList.remove('fa-check');

    <!-- Floating Background Shapes -->                    icon.classList.add('fa-times');

    <div class="floating-shapes">                }

        <div class="shape"></div>            });

        <div class="shape"></div>            

        <div class="shape"></div>            // Calculate strength

        <div class="shape"></div>            const metRequirements = Object.values(requirements).filter(Boolean).length;

    </div>            let strength = 0;

            let strengthClass = '';

    <div class="container">            let strengthLabel = '';

        <div class="row justify-content-center">            

            <div class="col-md-8 col-lg-6">            if (password.length === 0) {

                <div class="register-container">                strength = 0;

                    <!-- Registration Header -->                strengthClass = '';

                    <div class="register-header">                strengthLabel = '';

                        <div class="gaming-icon">            } else if (metRequirements < 2) {

                            <i class="bi bi-person-plus-fill"></i>                strength = 1;

                        </div>                strengthClass = 'strength-weak';

                        <h1 class="h3 mb-2">Join the Gaming Revolution!</h1>                strengthLabel = 'Weak';

                        <p class="mb-0">Create your GamePlan account and connect with gamers worldwide</p>            } else if (metRequirements < 3) {

                    </div>                strength = 2;

                strengthClass = 'strength-fair';

                    <!-- Registration Form -->                strengthLabel = 'Fair';

                    <div class="register-form">            } else if (metRequirements < 4) {

                        <!-- Error Message -->                strength = 3;

                        <?php if (!empty($error_message)): ?>                strengthClass = 'strength-good';

                            <div class="alert alert-danger alert-dismissible fade show" role="alert">                strengthLabel = 'Good';

                                <i class="bi bi-exclamation-triangle-fill me-2"></i>            } else {

                                <?php echo $error_message; ?>                strength = 4;

                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>                strengthClass = 'strength-strong';

                            </div>                strengthLabel = 'Strong';

                        <?php endif; ?>            }

            

                        <form method="POST" action="register.php" id="registerForm" novalidate>            // Update strength bar

                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">            strengthFill.className = 'strength-fill ' + strengthClass;

                                        strengthText.textContent = strengthLabel;

                            <!-- Username Field -->        }

                            <div class="mb-4">        

                                <label for="username" class="form-label fw-semibold">        // Password match checker

                                    <i class="bi bi-person-badge me-2"></i>Username <span class="text-danger">*</span>        function checkPasswordMatch() {

                                </label>            const password = document.getElementById('password').value;

                                <div class="input-group">            const confirmPassword = document.getElementById('confirm_password').value;

                                    <span class="input-group-text">            const feedback = document.getElementById('password-match-feedback');

                                        <i class="bi bi-at"></i>            const confirmField = document.getElementById('confirm_password');

                                    </span>            

                                    <input type="text"             if (confirmPassword.length > 0) {

                                           class="form-control"                 if (password === confirmPassword) {

                                           id="username"                     confirmField.setCustomValidity('');

                                           name="username"                     feedback.textContent = 'Passwords match!';

                                           value="<?php echo htmlspecialchars($form_data['username']); ?>"                    feedback.style.color = '#2ed573';

                                           placeholder="Choose a unique username"                } else {

                                           required                    confirmField.setCustomValidity('Passwords do not match');

                                           minlength="3"                    feedback.textContent = 'Passwords do not match.';

                                           maxlength="30"                    feedback.style.color = '#ff6b6b';

                                           pattern="[a-zA-Z0-9_-]+"                }

                                           autocomplete="username">            } else {

                                    <div class="invalid-feedback">                confirmField.setCustomValidity('');

                                        Username must be 3-30 characters and contain only letters, numbers, underscores, and hyphens.                feedback.textContent = 'Passwords do not match.';

                                    </div>                feedback.style.color = '#ff6b6b';

                                </div>            }

                                <div class="form-text">        }

                                    <small>This will be your unique identifier on GamePlan</small>        

                                </div>        // Auto-dismiss alerts after 5 seconds

                            </div>        setTimeout(function() {

            const alerts = document.querySelectorAll('.alert');

                            <!-- Email Field -->            alerts.forEach(function(alert) {

                            <div class="mb-4">                const bsAlert = new bootstrap.Alert(alert);

                                <label for="email" class="form-label fw-semibold">                bsAlert.close();

                                    <i class="bi bi-envelope me-2"></i>Email Address <span class="text-danger">*</span>            });

                                </label>        }, 5000);

                                <div class="input-group">        

                                    <span class="input-group-text">        // Focus on username field on load

                                        <i class="bi bi-envelope-at"></i>        document.addEventListener('DOMContentLoaded', function() {

                                    </span>            document.getElementById('username').focus();

                                    <input type="email"         });

                                           class="form-control"     </script>

                                           id="email" </body>

                                           name="email" </html>
                                           value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                           placeholder="Enter your email address"
                                           required
                                           autocomplete="email">
                                    <div class="invalid-feedback">
                                        Please enter a valid email address.
                                    </div>
                                </div>
                            </div>

                            <!-- Name Fields -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label fw-semibold">
                                        <i class="bi bi-person me-2"></i>First Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="first_name" 
                                           name="first_name" 
                                           value="<?php echo htmlspecialchars($form_data['first_name']); ?>"
                                           placeholder="Your first name"
                                           required
                                           maxlength="50"
                                           autocomplete="given-name">
                                    <div class="invalid-feedback">
                                        First name is required and must be no more than 50 characters.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label fw-semibold">
                                        <i class="bi bi-person me-2"></i>Last Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="last_name" 
                                           name="last_name" 
                                           value="<?php echo htmlspecialchars($form_data['last_name']); ?>"
                                           placeholder="Your last name"
                                           required
                                           maxlength="50"
                                           autocomplete="family-name">
                                    <div class="invalid-feedback">
                                        Last name is required and must be no more than 50 characters.
                                    </div>
                                </div>
                            </div>

                            <!-- Password Fields -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="bi bi-shield-lock me-2"></i>Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-key"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Create a strong password"
                                           required
                                           minlength="8"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary password-toggle" 
                                            type="button" 
                                            id="passwordToggle"
                                            tabindex="-1">
                                        <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Password must meet all security requirements.
                                    </div>
                                </div>
                                
                                <!-- Password Strength Indicator -->
                                <div class="password-strength">
                                    <div class="strength-indicator" id="strengthIndicator"></div>
                                    <div class="password-requirements mt-2">
                                        <small>
                                            <div class="requirement" id="req-length">
                                                <i class="bi bi-circle me-1"></i>At least 8 characters
                                            </div>
                                            <div class="requirement" id="req-uppercase">
                                                <i class="bi bi-circle me-1"></i>One uppercase letter
                                            </div>
                                            <div class="requirement" id="req-lowercase">
                                                <i class="bi bi-circle me-1"></i>One lowercase letter
                                            </div>
                                            <div class="requirement" id="req-number">
                                                <i class="bi bi-circle me-1"></i>One number
                                            </div>
                                            <div class="requirement" id="req-special">
                                                <i class="bi bi-circle me-1"></i>One special character
                                            </div>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fw-semibold">
                                    <i class="bi bi-shield-check me-2"></i>Confirm Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-shield-check"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Re-enter your password"
                                           required
                                           autocomplete="new-password">
                                    <div class="invalid-feedback">
                                        Passwords must match.
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Fields -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="date_of_birth" class="form-label fw-semibold">
                                        <i class="bi bi-calendar-event me-2"></i>Date of Birth
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="date_of_birth" 
                                           name="date_of_birth" 
                                           value="<?php echo htmlspecialchars($form_data['date_of_birth']); ?>"
                                           max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>"
                                           autocomplete="bday">
                                    <div class="form-text">
                                        <small>Optional - helps us provide age-appropriate content</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="timezone" class="form-label fw-semibold">
                                        <i class="bi bi-globe me-2"></i>Timezone
                                    </label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <?php foreach ($timezones as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" 
                                                    <?php echo $form_data['timezone'] === $value ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           value="1" 
                                           id="terms_agreed" 
                                           name="terms_agreed" 
                                           required>
                                    <label class="form-check-label" for="terms_agreed">
                                        I agree to the 
                                        <a href="terms.php" target="_blank" class="text-decoration-none">Terms of Service</a> 
                                        and 
                                        <a href="privacy.php" target="_blank" class="text-decoration-none">Privacy Policy</a>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="invalid-feedback">
                                        You must agree to the Terms of Service and Privacy Policy to create an account.
                                    </div>
                                </div>
                            </div>

                            <!-- Registration Button -->
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" 
                                        name="register_submit" 
                                        class="btn btn-primary btn-register"
                                        id="registerButton">
                                    <i class="bi bi-person-plus me-2"></i>
                                    Create My GamePlan Account
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Registration Footer -->
                    <div class="register-footer">
                        <p class="mb-2">
                            <small class="text-muted">Already have an account?</small>
                        </p>
                        <a href="login.php" class="btn btn-secondary-outline">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In Instead
                        </a>
                        
                        <hr class="my-3">
                        
                        <div class="row text-center">
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check me-1"></i>
                                    100% Secure
                                </small>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-lightning me-1"></i>
                                    Instant Setup
                                </small>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-people me-1"></i>
                                    Join 10K+ Gamers
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
                            <a href="help.php" class="text-white text-decoration-none">
                                <small><i class="bi bi-question-circle me-1"></i>Help Center</small>
                            </a>
                        </div>
                        <div class="col">
                            <a href="contact.php" class="text-white text-decoration-none">
                                <small><i class="bi bi-envelope me-1"></i>Contact</small>
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
            
            // Password strength checker
            passwordField.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
            
            function checkPasswordStrength(password) {
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^a-zA-Z0-9]/.test(password)
                };
                
                // Update requirement indicators
                Object.keys(requirements).forEach(req => {
                    const element = document.getElementById('req-' + req);
                    if (requirements[req]) {
                        element.classList.add('met');
                        element.querySelector('i').className = 'bi bi-check-circle-fill me-1';
                    } else {
                        element.classList.remove('met');
                        element.querySelector('i').className = 'bi bi-circle me-1';
                    }
                });
                
                // Calculate strength
                const metRequirements = Object.values(requirements).filter(Boolean).length;
                const strengthIndicator = document.getElementById('strengthIndicator');
                
                if (metRequirements < 3) {
                    strengthIndicator.className = 'strength-indicator strength-weak';
                    strengthIndicator.style.width = '33%';
                } else if (metRequirements < 5) {
                    strengthIndicator.className = 'strength-indicator strength-medium';
                    strengthIndicator.style.width = '66%';
                } else {
                    strengthIndicator.className = 'strength-indicator strength-strong';
                    strengthIndicator.style.width = '100%';
                }
            }
            
            // Form validation
            const registerForm = document.getElementById('registerForm');
            
            // Real-time password confirmation
            const confirmPasswordField = document.getElementById('confirm_password');
            confirmPasswordField.addEventListener('input', function() {
                if (this.value && this.value !== passwordField.value) {
                    this.setCustomValidity('Passwords do not match');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    if (this.value) this.classList.add('is-valid');
                }
            });
            
            // Username validation
            const usernameField = document.getElementById('username');
            usernameField.addEventListener('input', function() {
                const username = this.value;
                if (username.length < 3 || username.length > 30 || !/^[a-zA-Z0-9_-]+$/.test(username)) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
            
            // Form submission
            registerForm.addEventListener('submit', function(event) {
                event.preventDefault();
                event.stopPropagation();
                
                let isValid = true;
                
                // Validate all required fields
                const requiredFields = ['username', 'email', 'password', 'confirm_password', 'first_name', 'last_name'];
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                });
                
                // Check terms agreement
                const termsCheckbox = document.getElementById('terms_agreed');
                if (!termsCheckbox.checked) {
                    termsCheckbox.classList.add('is-invalid');
                    isValid = false;
                } else {
                    termsCheckbox.classList.remove('is-invalid');
                }
                
                if (isValid) {
                    // Show loading state
                    const registerButton = document.getElementById('registerButton');
                    const originalText = registerButton.innerHTML;
                    registerButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating Account...';
                    registerButton.disabled = true;
                    
                    // Submit form
                    this.submit();
                }
            });
            
            // Auto-dismiss alerts after 7 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 7000);
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
    </script>
</body>
</html>