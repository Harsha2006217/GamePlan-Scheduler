<?php
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($username) || empty($password)) {
            throw new Exception("Please enter both username and password");
        }

        $user = $gameplan->loginUser($username, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                // In a real app, you'd store this token in the database
            }
            
            // Create welcome notification
            $gameplan->createNotification(
                $user['user_id'], 
                'Welcome to GamePlan Scheduler!', 
                'Get started by adding your favorite games and creating your first schedule.',
                'System'
            );
            
            header("Location: index.php");
            exit();
        } else {
            throw new Exception("Invalid username or password");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h2><i class="fas fa-gamepad me-2"></i>GamePlan Scheduler</h2>
                        <p class="mb-0">Welcome Back!</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                           required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <div class="feature-highlights">
                        <div class="row">
                            <div class="col-4">
                                <div class="feature-item">
                                    <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                                    <p class="small mb-0">Secure</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="feature-item">
                                    <i class="fas fa-mobile-alt fa-2x text-success mb-2"></i>
                                    <p class="small mb-0">Responsive</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="feature-item">
                                    <i class="fas fa-rocket fa-2x text-success mb-2"></i>
                                    <p class="small mb-0">Fast</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>