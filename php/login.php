<?php
/**
 * Login Page
 * Handles user authentication
 */

require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Ongeldige beveiligingstoken. Probeer het opnieuw.";
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Set session variables
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $result['username'];
            
            // Regenerate session ID to prevent session fixation
            regenerateSession();
            
            // Redirect to dashboard
            header("Location: index.php");
            exit;
        } else {
            $error_message = $result['message'];
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
    <title>Inloggen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../CSS/style.css" rel="stylesheet">
</head>
<body class="dark-theme">
    <?php include 'header.php'; ?>

    <div class="container main-content">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card bg-dark text-light mt-5">
                    <div class="card-header">
                        <h3 class="text-center">Inloggen</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="login.php" id="loginForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mailadres</label>
                                <input type="email" class="form-control bg-dark-secondary text-light" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Wachtwoord</label>
                                <input type="password" class="form-control bg-dark-secondary text-light" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Inloggen
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>Nog geen account? <a href="register.php" class="text-primary">Registreer hier</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('loginForm').addEventListener('submit', function(event) {
                let valid = true;
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                
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
                }
                
                if (!valid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>