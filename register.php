<?php
// register.php: Secure registration with validation
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
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; }
        .container { max-width: 500px; margin: 100px auto; padding: 30px; background: #1e1e1e; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .form-control { background: #2c2c2c; border: 1px solid #444; color: #fff; }
        .btn-primary { background: #007bff; border: none; }
        .btn-primary:hover { background: #0069d9; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; color: #fff; }
        .text-center a { color: #007bff; text-decoration: none; }
        .text-center a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Register</h2>
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> text-center"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required maxlength="50">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required maxlength="100">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
        <p class="text-center mt-3">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>