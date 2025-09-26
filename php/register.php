<?php
session_start();
require_once 'functions.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (validateInput($username, 'text') && validateInput($email, 'email') && !empty($password)) {
        try {
            registerUser($username, $email, $password);
            $message = 'Registratie succesvol. Gelieve in te loggen.';
        } catch (Exception $e) {
            $message = 'Registratie mislukt.';
        }
    } else {
        $message = 'Ongeldige invoer.';
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
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body class="bg-dark text-white">
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card bg-secondary p-4">
            <h2 class="text-center">Registreren</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post" onsubmit="return validateRegisterForm()">
                <div class="mb-3">
                    <label for="username" class="form-label">Gebruikersnaam</label>
                    <input type="text" class="form-control" id="username" name="username" required maxlength="50">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Wachtwoord</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registreren</button>
            </form>
            <p class="mt-3 text-center">Al een account? <a href="login.php">Inloggen</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>