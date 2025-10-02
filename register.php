<?php
require_once 'functions.php';
requireLogin();  // No, allow public access for register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = registerUser($username, $email, $password);
    if ($result === true) {
        setMessage('success', 'Registration successful. Please log in.');
        header('Location: login.php');
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
    <title>Register - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom dark theme styling - professional gaming look */
        body { background-color: #121212; color: #ffffff; font-family: 'Sans-serif', Arial; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 60px auto; padding: 20px; background: #1e1e1e; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.5); }
        .form-control { background: #2c2c2c; color: #fff; border: 1px solid #dddddd; }
        .btn-primary { background: #007bff; border: none; transition: background 0.3s; }
        .btn-primary:hover { background: #0056b3; }
        .alert { margin-bottom: 20px; border-radius: 5px; padding: 12px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        @media (max-width: 768px) { .container { padding: 15px; } }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Register for GamePlan Scheduler</h2>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return validateRegisterForm();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username (1-50 chars, alphanumeric)</label>
                <input type="text" class="form-control" id="username" name="username" required maxlength="50">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required maxlength="100">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password (min 8 chars)</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary w-100" aria-label="Register button">Register</button>
        </form>
        <p class="text-center mt-3">Already have an account? <a href="login.php" style="color: #007bff;">Log in</a></p>
    </div>
    <script>
        // Client-side validation for register form
        function validateRegisterForm() {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            if (username === '' || username.length > 50 || !/^\w+$/.test(username)) {
                alert('Invalid username: 1-50 alphanumeric characters only.');
                return false;
            }
            if (!email.match(/^[\w-]+@([\w-]+\.)+[\w-]{2,4}$/)) {
                alert('Invalid email address.');
                return false;
            }
            if (password.length < 8) {
                alert('Password must be at least 8 characters.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>