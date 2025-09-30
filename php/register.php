<?php
/**
 * GamePlan Scheduler - Registration Page
 * 
 * Secure registration form with comprehensive validation, password strength checking,
 * and user-friendly interface.
 * 
 * @author Harsha Kanaparthi
 * @version 1.0
 * @date 2025-09-30
 */

require_once 'db.php';
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$message = '';
$formData = ['username' => '', 'email' => ''];

// Process registration form
if ($_POST) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger">Security token validation failed. Please try again.</div>';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Store form data for re-population
        $formData = ['username' => $username, 'email' => $email];
        
        // Comprehensive validation
        $validationErrors = [];
        
        // Username validation
        if (empty($username)) {
            $validationErrors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $validationErrors[] = 'Username must be at least 3 characters.';
        } elseif (strlen($username) > 50) {
            $validationErrors[] = 'Username must be less than 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $validationErrors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        
        // Email validation
        if (empty($email)) {
            $validationErrors[] = 'Email is required.';
        } elseif (!validateEmail($email)) {
            $validationErrors[] = 'Please enter a valid email address.';
        }
        
        // Password validation
        if (empty($password)) {
            $validationErrors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $validationErrors[] = 'Password must be at least 6 characters.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $validationErrors[] = 'Password must contain at least one lowercase letter, one uppercase letter, and one number.';
        }
        
        // Confirm password validation
        if (empty($confirmPassword)) {
            $validationErrors[] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $validationErrors[] = 'Passwords do not match.';
        }
        
        if (!empty($validationErrors)) {
            $message = '<div class="alert alert-danger">' . implode('<br>', $validationErrors) . '</div>';
        } else {
            // Attempt registration
            $result = registerUser($username, $email, $password);
            
            if ($result['success']) {
                $message = '<div class="alert alert-success">Registratie succesvol. <a href="login.php">Inloggen</a></div>';
                // Redirect to login page after successful registration
                header('Location: login.php?registered=1');
                exit();
            } else {
                $message = '<div class="alert alert-danger">Registratie mislukt. Controleer invoer.</div>';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 450px;
            width: 100%;
        }
        
        .brand-logo {
            color: #00d4ff;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #00d4ff;
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #00d4ff, #0099cc);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #0099cc, #00d4ff);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            backdrop-filter: blur(10px);
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.2);
            color: #51cf66;
            border: 1px solid rgba(25, 135, 84, 0.3);
        }
        
        .login-link {
            color: #00d4ff;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .login-link:hover {
            color: #0099cc;
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #00d4ff;
        }
        
        .password-strength {
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .strength-weak { background: #ff4757; width: 25%; }
        .strength-fair { background: #ffa502; width: 50%; }
        .strength-good { background: #2ed573; width: 75%; }
        .strength-strong { background: #1e90ff; width: 100%; }
        
        .requirements {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .requirement {
            color: rgba(255, 255, 255, 0.6);
            transition: color 0.3s ease;
        }
        
        .requirement.met {
            color: #2ed573;
        }
        
        .requirement i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="brand-logo">
            <i class="fas fa-gamepad"></i>
            <div class="h3 mt-2">GamePlan</div>
            <small class="text-muted">Join the Gaming Community</small>
        </div>
        
        <?php echo $message; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="mb-3">
                <label for="username" class="form-label text-white">
                    <i class="fas fa-user me-2"></i>Username
                </label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="Choose a unique username"
                    value="<?php echo htmlspecialchars($formData['username']); ?>"
                    required
                    autocomplete="username"
                    pattern="[a-zA-Z0-9_]{3,50}"
                >
                <div class="invalid-feedback">
                    Username must be 3-50 characters and contain only letters, numbers, and underscores.
                </div>
                <small class="text-white-50">3-50 characters, letters, numbers, and underscores only</small>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label text-white">
                    <i class="fas fa-envelope me-2"></i>Email Address
                </label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email address"
                    value="<?php echo htmlspecialchars($formData['email']); ?>"
                    required
                    autocomplete="email"
                >
                <div class="invalid-feedback">
                    Please enter a valid email address.
                </div>
            </div>
            
            <div class="mb-3 position-relative">
                <label for="password" class="form-label text-white">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    placeholder="Create a strong password"
                    required
                    autocomplete="new-password"
                    oninput="checkPasswordStrength()"
                >
                <button type="button" class="password-toggle" onclick="togglePassword('password', 'password-eye')">
                    <i class="fas fa-eye" id="password-eye"></i>
                </button>
                <div class="invalid-feedback">
                    Password must meet the requirements below.
                </div>
                
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strength-fill"></div>
                    </div>
                    <small id="strength-text" class="text-white-50"></small>
                </div>
                
                <div class="requirements">
                    <div class="requirement" id="req-length">
                        <i class="fas fa-times"></i>At least 6 characters
                    </div>
                    <div class="requirement" id="req-lower">
                        <i class="fas fa-times"></i>One lowercase letter
                    </div>
                    <div class="requirement" id="req-upper">
                        <i class="fas fa-times"></i>One uppercase letter
                    </div>
                    <div class="requirement" id="req-number">
                        <i class="fas fa-times"></i>One number
                    </div>
                </div>
            </div>
            
            <div class="mb-3 position-relative">
                <label for="confirm_password" class="form-label text-white">
                    <i class="fas fa-lock me-2"></i>Confirm Password
                </label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Confirm your password"
                    required
                    autocomplete="new-password"
                    oninput="checkPasswordMatch()"
                >
                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'confirm-eye')">
                    <i class="fas fa-eye" id="confirm-eye"></i>
                </button>
                <div class="invalid-feedback" id="password-match-feedback">
                    Passwords do not match.
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-white-50 mb-2">Already have an account?</p>
            <a href="login.php" class="login-link">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </a>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white-50">
                <i class="fas fa-shield-alt me-1"></i>
                Your data is protected with advanced encryption
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Password toggle functionality
        function togglePassword(fieldId, eyeId) {
            const passwordField = document.getElementById(fieldId);
            const passwordEye = document.getElementById(eyeId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordEye.classList.remove('fa-eye');
                passwordEye.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordEye.classList.remove('fa-eye-slash');
                passwordEye.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            
            // Requirements check
            const requirements = {
                length: password.length >= 6,
                lower: /[a-z]/.test(password),
                upper: /[A-Z]/.test(password),
                number: /\d/.test(password)
            };
            
            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById('req-' + req);
                const icon = element.querySelector('i');
                
                if (requirements[req]) {
                    element.classList.add('met');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-check');
                } else {
                    element.classList.remove('met');
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-times');
                }
            });
            
            // Calculate strength
            const metRequirements = Object.values(requirements).filter(Boolean).length;
            let strength = 0;
            let strengthClass = '';
            let strengthLabel = '';
            
            if (password.length === 0) {
                strength = 0;
                strengthClass = '';
                strengthLabel = '';
            } else if (metRequirements < 2) {
                strength = 1;
                strengthClass = 'strength-weak';
                strengthLabel = 'Weak';
            } else if (metRequirements < 3) {
                strength = 2;
                strengthClass = 'strength-fair';
                strengthLabel = 'Fair';
            } else if (metRequirements < 4) {
                strength = 3;
                strengthClass = 'strength-good';
                strengthLabel = 'Good';
            } else {
                strength = 4;
                strengthClass = 'strength-strong';
                strengthLabel = 'Strong';
            }
            
            // Update strength bar
            strengthFill.className = 'strength-fill ' + strengthClass;
            strengthText.textContent = strengthLabel;
        }
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const feedback = document.getElementById('password-match-feedback');
            const confirmField = document.getElementById('confirm_password');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    confirmField.setCustomValidity('');
                    feedback.textContent = 'Passwords match!';
                    feedback.style.color = '#2ed573';
                } else {
                    confirmField.setCustomValidity('Passwords do not match');
                    feedback.textContent = 'Passwords do not match.';
                    feedback.style.color = '#ff6b6b';
                }
            } else {
                confirmField.setCustomValidity('');
                feedback.textContent = 'Passwords do not match.';
                feedback.style.color = '#ff6b6b';
            }
        }
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Focus on username field on load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>