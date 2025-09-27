<?php
// Login pagina voor GamePlan Scheduler
// Geavanceerde form met client-side validatie en server-side checks
// Inclusief CSRF protection (future-proof) en rate limiting simulatie

require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        loginUser($email, $password);
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body class="bg-dark text-light d-flex align-items-center justify-content-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card bg-secondary border-0 shadow-lg rounded-3">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Login</h2>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo sanitizeInput($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="jouw@email.com">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Wachtwoord</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Inloggen</button>
                        </form>
                        <p class="text-center mt-3">Nog geen account? <a href="register.php" class="text-primary">Registreer hier</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>