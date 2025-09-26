<?php
/**
 * Registration Page
 * Handles new user creation
 */

require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error_message = '';
$success = false;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Ongeldige beveiligingstoken. Probeer het opnieuw.";
    } else {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Additional validation
        if (empty(trim($username)) || empty(trim($email)) || empty($password) || empty($confirm_password)) {
            $error_message = "Alle velden zijn verplicht";
        } elseif (strlen($username) > 50) {
            $error_message = "Gebruikersnaam mag maximaal 50 tekens lang zijn";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Ongeldig e-mailadres";
        } elseif (strlen($password) < 8) {
            $error_message = "Wachtwoord moet minimaal 8 tekens lang zijn";
        } elseif ($password !== $confirm_password) {
            $error_message = "Wachtwoorden komen niet overeen";
        } else {
            // Register the user
            $result = registerUser($username, $email, $password);
            
            if ($result['success']) {
                $success = true;
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../CSS/style.css" rel="stylesheet">
</head>
<body class="dark-theme">
    <?php include 'header.php'; ?>

    <div class="container main-content">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if ($success): ?>
                    <div class="card bg-dark text-light mt-5">
                        <div class="card-header">
                            <h3 class="text-center">Registratie Succesvol</h3>
                        </div>
                        <div class="card-body text-center">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <p>Je account is succesvol aangemaakt! Je kunt nu inloggen.</p>
                            </div>
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Ga naar Inloggen
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card bg-dark text-light mt-5">
                        <div class="card-header">
                            <h3 class="text-center">Account Aanmaken</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                            <?php endif; ?>
                            
                            <form method="post" action="register.php" id="registerForm">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Gebruikersnaam</label>
                                    <input type="text" class="form-control bg-dark-secondary text-light" id="username" name="username" maxlength="50" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mailadres</label>
                                    <input type="email" class="form-control bg-dark-secondary text-light" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Wachtwoord</label>
                                    <input type="password" class="form-control bg-dark-secondary text-light" id="password" name="password" required>
                                    <small class="form-text text-muted">Minimaal 8 tekens</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Bevestig Wachtwoord</label>
                                    <input type="password" class="form-control bg-dark-secondary text-light" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Registreren
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center">
                            <p>Heb je al een account? <a href="login.php" class="text-primary">Inloggen</a></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('registerForm').addEventListener('submit', function(event) {
                let valid = true;
                const username = document.getElementById('username').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (username === '') {
                    valid = false;
                    alert('Gebruikersnaam is verplicht');
                } else if (username.length > 50) {
                    valid = false;
                    alert('Gebruikersnaam mag maximaal 50 tekens lang zijn');
                }
                
                if (email === '') {
                    valid = false;
                    alert('E-mailadres is verplicht');
                } else if (!email.includes('@')) {
                    valid = false;
                    alert('Ongeldig e-mailadres');
                }
                
                if (password === '') {
                    valid = false;
                    alert('Wachtwoord is verplicht');
                } else if (password.length < 8) {
                    valid = false;
                    alert('Wachtwoord moet minimaal 8 tekens lang zijn');
                }
                
                if (confirmPassword === '') {
                    valid = false;
                    alert('Bevestig wachtwoord is verplicht');
                } else if (password !== confirmPassword) {
                    valid = false;
                    alert('Wachtwoorden komen niet overeen');
                }
                
                if (!valid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>