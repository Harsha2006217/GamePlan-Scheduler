<?php
// Registratie pagina voor GamePlan Scheduler
// Met geavanceerde validatie op server en client, inclusief password strength check

require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        registerUser($username, $email, $password);
        $_SESSION['msg'] = "Registratie succesvol! Log in om te beginnen.";
        header("Location: login.php");
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
    <title>Registreren - GamePlan Scheduler</title>
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
                        <h2 class="card-title text-center mb-4">Registreren</h2>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo sanitizeInput($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" id="registerForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Gebruikersnaam</label>
                                <input type="text" class="form-control" id="username" name="username" required minlength="3" maxlength="50" placeholder="gamerpro" pattern="^[a-zA-Z0-9]+$">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="jouw@email.com">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Wachtwoord</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Registreren</button>
                        </form>
                        <p class="text-center mt-3">Al een account? <a href="login.php" class="text-primary">Log in hier</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>