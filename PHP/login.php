<?php
/**
 * Advanced Login System
 * GamePlan Scheduler - Professional Gaming Authentication Portal
 * 
 * This module provides comprehensive login functionality with
 * advanced security features, brute force protection, and professional UI.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

require 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success_msg = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_STRING) ?? '';

// Process login attempt
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token if present
    if (isset($_POST['csrf_token']) && !validateCSRF($_POST['csrf_token'])) {
        $error = 'Ongeldige beveiligingstoken. Probeer opnieuw.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        // Enhanced validation
        if (empty($email) || empty($password)) {
            $error = 'Email en wachtwoord zijn verplicht.';
        } elseif (!validateInput($email, 'email')) {
            $error = 'Ongeldig email formaat.';
        } elseif (strlen($password) < 8) {
            $error = 'Wachtwoord moet minimaal 8 karakters bevatten.';
        } else {
            $result = loginUser($email, $password);
            if ($result['success']) {
                // Set remember me cookie if requested
                if ($remember_me) {
                    setcookie('remember_email', $email, time() + (30 * 24 * 3600), '/', '', true, true);
                } else {
                    setcookie('remember_email', '', time() - 3600, '/');
                }
                
                $_SESSION['success_message'] = 'Welkom terug! Je bent succesvol ingelogd.';
                header("Location: index.php");
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get remembered email if available
$remembered_email = $_COOKIE['remember_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
    
    <!-- Advanced Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data:;">
    
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d3748 100%);
            min-height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-radius: 15px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.4);
        }
        
        .gaming-icon {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .security-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .demo-hint {
            background: linear-gradient(45deg, #17a2b8, #138496);
            border: none;
            transition: all 0.3s ease;
        }
        
        .demo-hint:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.4);
        }
        
        .input-group-text {
            border: none;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        
        .remember-section {
            background: rgba(248, 249, 250, 0.8);
            border-radius: 8px;
            padding: 15px;
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
    </style>
</head>
<body class="bg-dark text-light">
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7 col-sm-9">
                    <!-- Security Badge -->
                    <div class="security-badge">
                        <i class="fas fa-shield-alt me-1"></i>Beveiligd
                    </div>
                    
                    <div class="card login-card">
                        <div class="login-header text-center">
                            <div class="gaming-icon mb-3">
                                <i class="fas fa-gamepad fa-4x"></i>
                            </div>
                            <h2 class="mb-2">GamePlan Scheduler</h2>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-trophy me-2"></i>
                                Je Gaming Command Center
                            </p>
                        </div>
                        
                        <div class="card-body p-4">
                            <!-- Success/Error Messages -->
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Login Fout:</strong> <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success_msg)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($success_msg); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" id="loginForm" novalidate>
                                <!-- CSRF Token -->
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">
                                        <i class="fas fa-envelope me-2 text-primary"></i>Email Adres
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user text-white"></i>
                                        </span>
                                        <input type="email" 
                                               class="form-control form-control-lg" 
                                               id="email" 
                                               name="email" 
                                               placeholder="bijvoorbeeld: gamer@email.com"
                                               value="<?php echo htmlspecialchars($remembered_email ?: ($_POST['email'] ?? '')); ?>"
                                               required
                                               autocomplete="email">
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-times-circle me-1"></i>Voer een geldig email adres in
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2 text-primary"></i>Wachtwoord
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-key text-white"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control form-control-lg" 
                                               id="password" 
                                               name="password" 
                                               placeholder="Voer je wachtwoord in"
                                               required
                                               autocomplete="current-password"
                                               minlength="8">
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                id="togglePassword"
                                                tabindex="-1"
                                                title="Toon/verberg wachtwoord">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="strengthBar"></div>
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-times-circle me-1"></i>Wachtwoord is verplicht (minimaal 8 karakters)
                                    </div>
                                </div>
                                
                                <div class="mb-4 remember-section">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="rememberMe" 
                                                       name="remember_me"
                                                       value="1"
                                                       <?php echo !empty($remembered_email) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="rememberMe">
                                                    <i class="fas fa-heart me-1 text-danger"></i>Onthoud mij
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <a href="forgot_password.php" class="text-decoration-none small">
                                                <i class="fas fa-question-circle me-1"></i>Wachtwoord vergeten?
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg py-3" id="loginButton">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        <span class="button-text">Inloggen & Gamen</span>
                                    </button>
                                </div>
                                
                                <!-- Quick Demo Access -->
                                <div class="text-center mb-3">
                                    <button type="button" class="btn demo-hint btn-sm" onclick="quickLogin()">
                                        <i class="fas fa-bolt me-2"></i>Demo Account Proberen
                                    </button>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Test de app met een vooraf ingesteld account
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Security Info -->
                                <div class="text-center">
                                    <small class="text-muted d-flex align-items-center justify-content-center">
                                        <i class="fas fa-shield-alt me-2 text-success"></i>
                                        <span>256-bit SSL Encryptie | Brute Force Bescherming</span>
                                    </small>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card-footer text-center bg-light py-3">
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <p class="mb-0 fw-semibold">Nog geen gaming account? 
                                        <a href="register.php" class="text-decoration-none text-primary">
                                            <i class="fas fa-user-plus me-1"></i>Word Gamer
                                        </a>
                                    </p>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted">
                                        Door in te loggen ga je akkoord met onze 
                                        <a href="privacy.php" class="text-decoration-none">Privacyvoorwaarden</a>
                                        en <a href="terms.php" class="text-decoration-none">Servicevoorwaarden</a>
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Gaming Stats -->
                            <div class="row mt-3 pt-3 border-top">
                                <div class="col-4 text-center">
                                    <div class="fw-bold text-primary">1000+</div>
                                    <small class="text-muted">Gamers</small>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="fw-bold text-success">500+</div>
                                    <small class="text-muted">Events</small>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="fw-bold text-warning">24/7</div>
                                    <small class="text-muted">Support</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Info -->
                    <div class="text-center mt-4">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <small class="text-light">
                                    <i class="fas fa-users me-1"></i>
                                    <a href="about.php" class="text-decoration-none text-light opacity-75">Over Ons</a>
                                </small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="text-light">
                                    <i class="fas fa-headset me-1"></i>
                                    <a href="contact.php" class="text-decoration-none text-light opacity-75">Support</a>
                                </small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="text-light">
                                    <i class="fas fa-mobile-alt me-1"></i>
                                    <span class="opacity-75">Mobile App Binnenkort</span>
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
                this.title = 'Verberg wachtwoord';
            } else {
                password.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
                this.title = 'Toon wachtwoord';
            }
        });
        
        // Demo account quick login
        function quickLogin() {
            document.getElementById('email').value = 'demo@gameplan.com';
            document.getElementById('password').value = 'Demo123!@#';
            showToast('Demo Account', 'Demo inloggegevens ingevuld! Klik op "Inloggen & Gamen" om door te gaan.', 'info');
            
            // Add visual feedback
            const button = document.querySelector('.demo-hint');
            button.innerHTML = '<i class="fas fa-check me-2"></i>Demo Gegevens Ingevuld';
            button.classList.remove('demo-hint');
            button.classList.add('btn-success');
            
            // Highlight login button
            const loginButton = document.getElementById('loginButton');
            loginButton.style.animation = 'pulse 2s infinite';
        }
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            let strength = 0;
            
            // Check password criteria
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength bar
            const colors = ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#007bff'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];
            
            if (password.length > 0) {
                strengthBar.style.width = widths[strength - 1] || '20%';
                strengthBar.style.backgroundColor = colors[strength - 1] || '#dc3545';
            } else {
                strengthBar.style.width = '0%';
            }
        });
        
        // Enhanced form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            let isValid = true;
            
            // Clear previous validation states
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            
            // Email validation
            if (!email) {
                document.getElementById('email').classList.add('is-invalid');
                isValid = false;
            } else if (!isValidEmail(email)) {
                document.getElementById('email').classList.add('is-invalid');
                isValid = false;
            }
            
            // Password validation
            if (!password) {
                document.getElementById('password').classList.add('is-invalid');
                isValid = false;
            } else if (password.length < 8) {
                document.getElementById('password').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast('Validatiefout', 'Controleer je invoer en probeer opnieuw', 'error');
                return false;
            }
            
            // Show loading state
            const button = document.getElementById('loginButton');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Inloggen...';
            button.disabled = true;
            
            // Re-enable button if form submission fails
            setTimeout(() => {
                if (button.disabled) {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            }, 5000);
        });
        
        // Email validation function
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Remember me functionality
        if (localStorage.getItem('rememberEmail')) {
            const storedEmail = localStorage.getItem('rememberEmail');
            if (storedEmail && !document.getElementById('email').value) {
                document.getElementById('email').value = storedEmail;
                document.getElementById('rememberMe').checked = true;
            }
        }
        
        document.getElementById('rememberMe').addEventListener('change', function() {
            const email = document.getElementById('email').value;
            if (this.checked && email) {
                localStorage.setItem('rememberEmail', email);
            } else {
                localStorage.removeItem('rememberEmail');
            }
        });
        
        // Email input listener for remember me
        document.getElementById('email').addEventListener('blur', function() {
            const rememberCheckbox = document.getElementById('rememberMe');
            if (rememberCheckbox.checked && this.value) {
                localStorage.setItem('rememberEmail', this.value);
            }
        });
        
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
        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            .form-control.is-invalid {
                animation: shake 0.5s ease-in-out;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Focus first empty field
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            if (!email.value) {
                email.focus();
            } else if (!password.value) {
                password.focus();
            }
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>