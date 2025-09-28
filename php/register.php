<?php
require 'functions.php';
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    if (empty($username) || strlen($username) > 50) {
        $error = 'Username verplicht, max 50 tekens.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldige email.';
    } elseif (strlen($password) < 6) {
        $error = 'Wachtwoord minstens 6 tekens.';
    } else {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            $error = 'Email bestaat al.';
        } else {
            $hash = hashPassword($password);
            $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (:username, :email, :hash)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':hash', $hash);
            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $error = 'Registratie mislukt.';
            }
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Registreren</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return validateForm(this);">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required maxlength="50">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Wachtwoord</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Registreren</button>
        </form>
        <p class="mt-3">Al een account? <a href="login.php">Inloggen</a></p>
    </div>
    <script src="script.js"></script>
</body>
</html>