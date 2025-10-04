<?php
// login.php: Secure login page with validation and CSRF protection
// Redirects to index if successful, shows errors otherwise
// Dark theme, responsive, beautiful UI

require_once 'functions.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $result = loginUser($username, $password);
    if ($result === true) {
        header('Location: index.php');
        exit;
    } else {
        setMessage('danger', $result);
    }
}
$msg = getMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="bi bi-controller"></i>
            <h2 class="mb-0">GamePlan Scheduler</h2>
            <p class="text-muted">Welcome Back, Gamer!</p>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> mb-4">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
            <div class="mb-4">
                <label for="username" class="form-label"><i class="bi bi-person me-2"></i>Username</label>
                <input type="text" class="form-control" id="username" name="username" required placeholder="Enter your username">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label"><i class="bi bi-lock me-2"></i>Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</button>
        </form>

        <div class="register-link">
            New to GamePlan? <a href="register.php">Create an account</a>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>