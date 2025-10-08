<?php
// login.php - User Login Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Handles user login with validation and session setup.

require_once 'functions.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $error = loginUser($email, $password);
    if (!$error) {
        header("Location: index.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h1 class="text-center">Login</h1>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>
        <form method="POST" class="mt-4" onsubmit="return validateLoginForm();">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required aria-label="Email address">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required aria-label="Password">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Login</button>
        </form>
        <p class="text-center mt-3">Don't have an account? <a href="register.php" class="text-light">Register</a></p>
    </div>
    <script src="script.js"></script>
</body>
</html>