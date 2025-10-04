<?php
// register.php: Secure registration page with validation and CSRF
// Redirects to login on success, shows errors otherwise
// Dark theme, responsive, beautiful UI

require_once 'functions.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $result = registerUser($username, $email, $password);
    if ($result === true) {
        setMessage('success', 'Registered successfully. Please log in.');
        header('Location: login.php');
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
    <title>Register - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="bi bi-controller"></i>
            <h2 class="mb-0">GamePlan Scheduler</h2>
            <p class="text-muted">Create Your Gaming Account</p>
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
                <input type="text" class="form-control" id="username" name="username" required maxlength="50" placeholder="Enter your username">
            </div>
            <div class="mb-4">
                <label for="email" class="form-label"><i class="bi bi-envelope me-2"></i>Email</label>
                <input type="email" class="form-control" id="email" name="email" required maxlength="100" placeholder="Enter your email">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label"><i class="bi bi-lock me-2"></i>Password</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3"><i class="bi bi-person-plus me-2"></i>Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Log in here</a>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>