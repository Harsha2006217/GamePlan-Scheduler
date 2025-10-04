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

    <?php if (isset($msg) && $msg): ?>
        <div class="alert alert-<?php echo $msg['type']; ?> mb-4">
            <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($msg['msg']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateLoginForm();">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : generateCSRF()); ?>">
        
        <div class="mb-4">
            <label for="username" class="form-label">
                <i class="bi bi-person me-2"></i>Username
            </label>
            <input type="text" class="form-control" id="username" name="username" required 
                   placeholder="Enter your username" aria-label="Username"
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">
                <i class="bi bi-lock me-2"></i>Password
            </label>
            <input type="password" class="form-control" id="password" name="password" required 
                   placeholder="Enter your password" aria-label="Password">
            <div class="form-text">
                <a href="#" class="text-muted small"><i class="bi bi-question-circle me-1"></i>Forgot password?</a>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
        
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
            <label class="form-check-label small text-muted" for="rememberMe">
                Keep me signed in for 30 days
            </label>
        </div>
    </form>
    
    <div class="register-link">
        <p class="text-center mb-0">
            New to GamePlan? <a href="register.php" class="fw-bold">Create an account</a>
        </p>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>