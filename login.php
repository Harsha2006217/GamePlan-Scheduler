<?php
require_once 'functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = loginUser($username, $password);
    if ($result === true) {
        header('Location: index.php');
        exit;
    } else {
        setMessage('error', $result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 60px auto; padding: 20px; background: #1e1e1e; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.5); }
        .form-control { background: #2c2c2c; color: #fff; border: 1px solid #ddd; transition: border 0.3s; }
        .form-control:focus { border-color: #007bff; }
        .btn-primary { background: #007bff; border: none; transition: background 0.3s; }
        .btn-primary:hover { background: #0056b3; }
        .alert { border-radius: 5px; padding: 12px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        @media (max-width: 768px) { .container { padding: 15px; } }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4"><i class="bi bi-box-arrow-in-right"></i> Login</h2>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return validateLoginForm();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username <i class="bi bi-person"></i></label>
                <input type="text" class="form-control" id="username" name="username" required aria-label="Username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password <i class="bi bi-lock"></i></label>
                <input type="password" class="form-control" id="password" name="password" required aria-label="Password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>
        <p class="text-center mt-3">New user? <a href="register.php" style="color: #007bff;">Create account</a></p>
    </div>
    <script>
        function validateLoginForm() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            if (!username) {
                alert('Enter username.');
                return false;
            }
            if (!password) {
                alert('Enter password.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>