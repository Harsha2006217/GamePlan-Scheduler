<?php
// GamePlan Scheduler - User Login
// Professional login page with security features

session_start();
require 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$message = '';

if (isset($_GET['message'])) {
    if ($_GET['message'] === 'logged_out') {
        $message = 'Je bent succesvol uitgelogd.';
    } elseif ($_GET['message'] === 'registered') {
        $message = 'Account succesvol aangemaakt! Log nu in.';
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        if (empty($email) || empty($password)) {
            throw new Exception('Vul alstublieft alle velden in');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Voer alstublieft een geldig e-mailadres in');
        }

        if (loginUser($email, $password)) {
            // Redirect to dashboard
            header('Location: index.php');
            exit;
        } else {
            $message = 'Ongeldige inloggegevens.';
        }

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-dark text-white auth-page">
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card bg-secondary p-4 shadow">
            <div class="text-center mb-4">
                <a href="index.php" class="logo mb-3">
                    <i class="fas fa-gamepad fa-3x"></i>
                </a>
                <h2 class="card-title">Welkom Terug</h2>
                <p class="text-muted">Log in op je GamePlan account</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> E-mailadres
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required autofocus>
                    <div class="invalid-feedback">
                        Voer een geldig e-mailadres in.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Wachtwoord
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">
                        Wachtwoord is verplicht.
                    </div>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Inloggen
                    </button>
                </div>
            </form>

            <div class="text-center">
                <p class="mb-0">Geen account?
                    <a href="register.php" class="text-decoration-none">Meld je hier aan</a>
                </p>
            </div>

            <hr class="my-4">

            <div class="text-center text-muted">
                <small>
                    <i class="fas fa-shield-alt"></i>
                    Je gegevens zijn veilig en versleuteld
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');

            let isValid = true;

            // Email validation
            if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.classList.add('is-invalid');
                isValid = false;
            } else {
                email.classList.remove('is-invalid');
            }

            // Password validation
            if (!password.value) {
                password.classList.add('is-invalid');
                isValid = false;
            } else {
                password.classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Auto-focus and enter key handling
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (emailField && !emailField.value) {
                emailField.focus();
            }
        });
    </script>
</body>
</html>