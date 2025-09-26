<?php
/**
 * Advanced Registration System
 * GamePlan Scheduler - Professional Gaming Account Creation Portal
 * 
 * This module provides comprehensive registration functionality with
 * advanced validation, security features, and professional UI.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

require 'functions.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = false;
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Enhanced input sanitization and validation
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING)) ?? '';
    $last_name = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING)) ?? '';
    $terms_accepted = isset($_POST['terms_accepted']);
    $newsletter = isset($_POST['newsletter']);
    
    // Comprehensive validation
    if (empty($username)) {
        $error = 'Username is verplicht.';
    } elseif (!validateInput($username, 'username')) {
        $error = 'Username moet 3-50 tekens zijn en mag alleen letters, cijfers, underscore en streepjes bevatten.';
    } elseif (empty($email) || !validateInput($email, 'email')) {
        $error = 'Voer een geldig emailadres in.';
    } elseif (empty($password) || !validateInput($password, 'password')) {
        $error = 'Wachtwoord moet minstens 8 tekens, een hoofdletter, kleine letter, cijfer en speciaal teken bevatten.';
    } elseif ($password !== $confirm_password) {
        $error = 'Wachtwoorden komen niet overeen.';
    } elseif (!$terms_accepted) {
        $error = 'Je moet akkoord gaan met de algemene voorwaarden.';
    } else {
        global $pdo;
        try {
            $pdo->beginTransaction();
            
            // Enhanced duplicate check
            $stmt = $pdo->prepare("SELECT COUNT(*) as count, 'email' as type FROM Users WHERE email = ? 
                                  UNION ALL 
                                  SELECT COUNT(*) as count, 'username' as type FROM Users WHERE username = ?");
            $stmt->execute([$email, $username]);
            $results = $stmt->fetchAll();
            
            $email_exists = $results[0]['count'] > 0;
            $username_exists = $results[1]['count'] > 0;
            
            if ($email_exists && $username_exists) {
                $error = 'Deze email en username zijn al in gebruik.';
            } elseif ($email_exists) {
                $error = 'Dit emailadres is al geregistreerd. <a href="login.php" class="text-decoration-none">Inloggen?</a>';
            } elseif ($username_exists) {
                $error = 'Deze username is al in gebruik. Probeer een andere.';
            } else {
                // Create secure hash
                $hash = hashPassword($password);
                
                // Generate verification token
                $verification_token = bin2hex(random_bytes(32));
                
                // Insert user with enhanced data
                $stmt = $pdo->prepare("INSERT INTO Users (
                    username, email, password_hash, first_name, last_name, 
                    account_status, email_verified, verification_token, newsletter_subscribed,
                    registration_ip, created_at
                ) VALUES (?, ?, ?, ?, ?, 'active', FALSE, ?, ?, ?, NOW())");
                
                $registration_ip = get_client_ip();
                
                $stmt->execute([
                    $username, $email, $hash, $first_name, $last_name,
                    $verification_token, $newsletter, $registration_ip
                ]);
                
                $new_user_id = $pdo->lastInsertId();
                
                // Log registration activity
                logUserActivity($new_user_id, 'registration', 'account_created', [
                    'registration_method' => 'web',
                    'newsletter_subscribed' => $newsletter,
                    'ip_address' => $registration_ip,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
                
                $pdo->commit();
                
                // Set success message for redirect
                $success_message = "Welkom bij GamePlan Scheduler! Je account is succesvol aangemaakt. Log nu in om je gaming avontuur te beginnen!";
                
                // Simulate email verification (in real app, send actual email)
                if (function_exists('mail')) {
                    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/gameplan/PHP/verify.php?token=" . $verification_token;
                    // In production: send verification email
                }
                
                $success = true;
                header("Location: login.php?msg=" . urlencode($success_message));
                exit;
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $error = 'Er is een fout opgetreden tijdens registratie. Probeer het later opnieuw.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d3748 100%);
            min-height: 100vh;
        }
        
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .register-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-radius: 15px;
        }
        
        .register-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
        }
        
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
        }
        
        .gaming-icon {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-fair { background: #fd7e14; }
        .strength-good { background: #ffc107; }
        .strength-strong { background: #28a745; }
        .strength-very-strong { background: #007bff; }
        
        .requirements-list {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .requirements-list .requirement {
            transition: all 0.3s ease;
        }
        
        .requirements-list .requirement.met {
            color: #28a745;
        }
        
        .requirements-list .requirement:not(.met) {
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="card register-card">
                        <div class="register-header text-center">
                            <div class="gaming-icon mb-3">
                                <i class="fas fa-user-plus fa-4x"></i>
                            </div>
                            <h2 class="mb-2">Word GamePlan Gamer!</h2>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-rocket me-2"></i>
                                Start je gaming avontuur vandaag
                            </p>
                        </div>
                        
                        <div class="card-body p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Registratiefout:</strong> <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" id="registerForm" novalidate>
                                <!-- Personal Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label fw-semibold">
                                                <i class="fas fa-user me-2 text-success"></i>Voornaam
                                                <small class="text-muted">(optioneel)</small>
                                            </label>
                                            <input type="text" 
                                                   id="first_name" 
                                                   name="first_name" 
                                                   class="form-control form-control-lg" 
                                                   placeholder="Je voornaam"
                                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                                   maxlength="50">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label fw-semibold">
                                                <i class="fas fa-user me-2 text-success"></i>Achternaam
                                                <small class="text-muted">(optioneel)</small>
                                            </label>
                                            <input type="text" 
                                                   id="last_name" 
                                                   name="last_name" 
                                                   class="form-control form-control-lg" 
                                                   placeholder="Je achternaam"
                                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                                   maxlength="50">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Gaming Identity -->
                                <div class="mb-3">
                                    <label for="username" class="form-label fw-semibold">
                                        <i class="fas fa-gamepad me-2 text-success"></i>Gaming Username
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fas fa-at"></i>
                                        </span>
                                        <input type="text" 
                                               id="username" 
                                               name="username" 
                                               class="form-control form-control-lg" 
                                               placeholder="Kies je unieke gaming naam"
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                               required 
                                               minlength="3" 
                                               maxlength="50" 
                                               pattern="[a-zA-Z0-9_-]+">
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        3-50 tekens, alleen letters, cijfers, underscore en streepjes
                                    </div>
                                    <div class="invalid-feedback">
                                        Username moet 3-50 tekens zijn en mag alleen letters, cijfers, underscore en streepjes bevatten
                                    </div>
                                    <div id="usernameCheck" class="mt-1"></div>
                                </div>
                                
                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">
                                        <i class="fas fa-envelope me-2 text-success"></i>Email Adres
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" 
                                               id="email" 
                                               name="email" 
                                               class="form-control form-control-lg" 
                                               placeholder="je@email.com"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                               required
                                               autocomplete="email">
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldig emailadres in
                                    </div>
                                    <div id="emailCheck" class="mt-1"></div>
                                </div>
                                
                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2 text-success"></i>Wachtwoord
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fas fa-key"></i>
                                        </span>
                                        <input type="password" 
                                               id="password" 
                                               name="password" 
                                               class="form-control form-control-lg" 
                                               placeholder="Maak een sterk wachtwoord"
                                               required 
                                               minlength="8"
                                               autocomplete="new-password">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="strengthBar"></div>
                                    </div>
                                    <div class="requirements-list">
                                        <div class="requirement" id="req-length">
                                            <i class="fas fa-times me-1"></i>Minimaal 8 karakters
                                        </div>
                                        <div class="requirement" id="req-lower">
                                            <i class="fas fa-times me-1"></i>Minimaal één kleine letter
                                        </div>
                                        <div class="requirement" id="req-upper">
                                            <i class="fas fa-times me-1"></i>Minimaal één hoofdletter
                                        </div>
                                        <div class="requirement" id="req-number">
                                            <i class="fas fa-times me-1"></i>Minimaal één cijfer
                                        </div>
                                        <div class="requirement" id="req-special">
                                            <i class="fas fa-times me-1"></i>Minimaal één speciaal teken
                                        </div>
                                    </div>
                                    <div class="invalid-feedback">
                                        Wachtwoord moet minimaal 8 tekens bevatten
                                    </div>
                                </div>
                                
                                <!-- Confirm Password -->
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label fw-semibold">
                                        <i class="fas fa-check me-2 text-success"></i>Bevestig Wachtwoord
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fas fa-check-double"></i>
                                        </span>
                                        <input type="password" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               class="form-control form-control-lg" 
                                               placeholder="Herhaal je wachtwoord"
                                               required
                                               autocomplete="new-password">
                                    </div>
                                    <div class="invalid-feedback" id="passwordMatchFeedback">
                                        Wachtwoorden komen niet overeen
                                    </div>
                                    <div class="valid-feedback" id="passwordMatchValid">
                                        <i class="fas fa-check me-1"></i>Wachtwoorden komen overeen
                                    </div>
                                </div>
                                
                                <!-- Terms and Newsletter -->
                                <div class="mb-4">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="terms_accepted" 
                                               name="terms_accepted"
                                               required
                                               value="1">
                                        <label class="form-check-label" for="terms_accepted">
                                            Ik ga akkoord met de 
                                            <a href="privacy.php" target="_blank" class="text-decoration-none">
                                                Privacyvoorwaarden
                                            </a> 
                                            en 
                                            <a href="terms.php" target="_blank" class="text-decoration-none">
                                                Algemene Voorwaarden
                                            </a>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="invalid-feedback">
                                            Je moet akkoord gaan met de voorwaarden
                                        </div>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="newsletter" 
                                               name="newsletter"
                                               value="1">
                                        <label class="form-check-label" for="newsletter">
                                            <i class="fas fa-envelope me-2"></i>
                                            Ik wil gaming updates en tips ontvangen
                                            <small class="text-muted d-block">
                                                (Je kunt dit altijd uitschakelen in je profiel)
                                            </small>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid gap-2 mb-3">
                                    <button type="submit" class="btn btn-success btn-lg py-3" id="registerButton">
                                        <i class="fas fa-rocket me-2"></i>
                                        <span class="button-text">Start Gaming Avontuur</span>
                                    </button>
                                </div>
                                
                                <!-- Security Info -->
                                <div class="text-center">
                                    <small class="text-muted d-flex align-items-center justify-content-center">
                                        <i class="fas fa-shield-alt me-2 text-success"></i>
                                        <span>Je gegevens zijn veilig beschermd met 256-bit encryptie</span>
                                    </small>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card-footer text-center bg-light py-3">
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <p class="mb-0 fw-semibold">Al een gaming account? 
                                        <a href="login.php" class="text-decoration-none text-success">
                                            <i class="fas fa-sign-in-alt me-1"></i>Inloggen
                                        </a>
                                    </p>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted">
                                        Door te registreren maak je deel uit van een community van 1000+ gamers
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Features Preview -->
                            <div class="row mt-3 pt-3 border-top">
                                <div class="col-4 text-center">
                                    <i class="fas fa-users fa-2x text-success mb-2"></i>
                                    <div class="fw-bold">Vrienden</div>
                                    <small class="text-muted">Vind gamers</small>
                                </div>
                                <div class="col-4 text-center">
                                    <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                                    <div class="fw-bold">Planning</div>
                                    <small class="text-muted">Organiseer sessies</small>
                                </div>
                                <div class="col-4 text-center">
                                    <i class="fas fa-trophy fa-2x text-warning mb-2"></i>
                                    <div class="fw-bold">Evenementen</div>
                                    <small class="text-muted">Join toernooien</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Info -->
                    <div class="text-center mt-4">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <small class="text-light">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <span class="opacity-75">100% Privacy Beschermd</span>
                                </small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="text-light">
                                    <i class="fas fa-clock me-1"></i>
                                    <span class="opacity-75">Registratie in 2 minuten</span>
                                </small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="text-light">
                                    <i class="fas fa-star me-1"></i>
                                    <span class="opacity-75">Gratis voor altijd</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        // Enhanced password toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const requirements = {
                length: { element: document.getElementById('req-length'), test: password.length >= 8 },
                lower: { element: document.getElementById('req-lower'), test: /[a-z]/.test(password) },
                upper: { element: document.getElementById('req-upper'), test: /[A-Z]/.test(password) },
                number: { element: document.getElementById('req-number'), test: /[0-9]/.test(password) },
                special: { element: document.getElementById('req-special'), test: /[^A-Za-z0-9]/.test(password) }
            };
            
            let metRequirements = 0;
            
            // Update requirement indicators
            Object.values(requirements).forEach(req => {
                if (req.test) {
                    req.element.classList.add('met');
                    req.element.querySelector('i').className = 'fas fa-check me-1';
                    metRequirements++;
                } else {
                    req.element.classList.remove('met');
                    req.element.querySelector('i').className = 'fas fa-times me-1';
                }
            });
            
            // Update strength bar
            const strengthClasses = ['strength-weak', 'strength-fair', 'strength-good', 'strength-strong', 'strength-very-strong'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];
            
            strengthBar.className = 'password-strength-bar';
            
            if (password.length > 0) {
                const strengthIndex = Math.min(metRequirements - 1, 4);
                strengthBar.classList.add(strengthClasses[strengthIndex]);
                strengthBar.style.width = widths[strengthIndex];
            } else {
                strengthBar.style.width = '0%';
            }
        });
        
        // Password match validation
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const confirmField = document.getElementById('confirm_password');
            const matchFeedback = document.getElementById('passwordMatchFeedback');
            const matchValid = document.getElementById('passwordMatchValid');
            
            if (confirmPassword.length === 0) {
                confirmField.classList.remove('is-valid', 'is-invalid');
                return;
            }
            
            if (password === confirmPassword) {
                confirmField.classList.remove('is-invalid');
                confirmField.classList.add('is-valid');
            } else {
                confirmField.classList.remove('is-valid');
                confirmField.classList.add('is-invalid');
                confirmField.setCustomValidity('Wachtwoorden komen niet overeen');
            }
        }
        
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        document.getElementById('password').addEventListener('input', checkPasswordMatch);
        
        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const checkDiv = document.getElementById('usernameCheck');
            
            if (username.length < 3) {
                checkDiv.innerHTML = '<small class="text-muted"><i class="fas fa-info-circle me-1"></i>Minimaal 3 tekens</small>';
                return;
            }
            
            if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                checkDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Alleen letters, cijfers, _ en -</small>';
                return;
            }
            
            checkDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Geldige username</small>';
        });
        
        // Email validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const checkDiv = document.getElementById('emailCheck');
            
            if (!email) return;
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(email)) {
                checkDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Geldig email adres</small>';
            } else {
                checkDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Ongeldig email adres</small>';
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Clear previous validation states
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            
            // Username validation
            const username = document.getElementById('username').value.trim();
            if (!username || username.length < 3 || !/^[a-zA-Z0-9_-]+$/.test(username)) {
                document.getElementById('username').classList.add('is-invalid');
                isValid = false;
            }
            
            // Email validation
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                document.getElementById('email').classList.add('is-invalid');
                isValid = false;
            }
            
            // Password validation
            const password = document.getElementById('password').value;
            if (!password || password.length < 8) {
                document.getElementById('password').classList.add('is-invalid');
                isValid = false;
            }
            
            // Password match validation
            const confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                document.getElementById('confirm_password').classList.add('is-invalid');
                isValid = false;
            }
            
            // Terms validation
            const termsAccepted = document.getElementById('terms_accepted').checked;
            if (!termsAccepted) {
                document.getElementById('terms_accepted').classList.add('is-invalid');
                showToast('Voorwaarden', 'Je moet akkoord gaan met de voorwaarden', 'warning');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast('Validatiefout', 'Controleer je invoer en probeer opnieuw', 'error');
                return false;
            }
            
            // Show loading state
            const button = document.getElementById('registerButton');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Account aanmaken...';
            button.disabled = true;
            
            // Re-enable button if form submission fails
            setTimeout(() => {
                if (button.disabled) {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            }, 10000);
        });
        
        // Show toast notification
        function showToast(title, message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container') || createToastContainer();
            const toastId = 'toast-' + Date.now();
            
            const toastHTML = `
                <div class="toast align-items-center text-bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'info'}" 
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
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Focus first field
            document.getElementById('first_name').focus();
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>