<?php
require_once 'functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = registerUser($username, $email, $password);
    if ($result === true) {
        setMessage('success', 'Account created. Log in to start planning.');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
            --input-bg: #2c2c2c;
            --text-color: #ffffff;
        }
        
        body { 
            background: linear-gradient(135deg, #121212 0%, #1a1a2e 50%, #16213e 100%);
            color: var(--text-color); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            font-size: 1.1rem;
            margin: 0; 
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-container {
            max-width: 450px;
            width: 100%;
            padding: 40px 30px;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .form-control { 
            background: var(--input-bg); 
            color: var(--text-color); 
            border: 1px solid #444; 
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 1rem;
            line-height: 1.5;
        }
        
        .form-control:focus { 
            border-color: var(--primary-color); 
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
            background: var(--input-bg);
            color: var(--text-color);
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            border: none; 
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
        }
        
        .alert { 
            border-radius: 8px; 
            padding: 15px;
            border: none;
            font-size: 1rem;
            line-height: 1.5;
        }
        
        .alert-success { background: rgba(40,167,69,0.2); color: #28a745; border-left: 4px solid #28a745; }
        .alert-danger { background: rgba(220,53,69,0.2); color: #dc3545; border-left: 4px solid #dc3545; }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #ddd;
            font-size: 1rem;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #aaa;
            font-size: 0.95rem;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) { 
            .register-container { 
                margin: 10px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="bi bi-controller"></i>
            <h2 class="mb-0">GamePlan Scheduler</h2>
            <p class="text-muted">Create Your Gaming Account</p>
        </div>
        
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> mb-4">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" onsubmit="return validateRegisterForm();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="mb-4">
                <label for="username" class="form-label">
                    <i class="bi bi-person me-2"></i>Username
                </label>
                <input type="text" class="form-control" id="username" name="username" required maxlength="50" 
                       placeholder="Enter your username" aria-label="Username">
                <div class="form-text text-muted">1-50 characters, letters, numbers, hyphens, underscores</div>
            </div>
            
            <div class="mb-4">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope me-2"></i>Email
                </label>
                <input type="email" class="form-control" id="email" name="email" required maxlength="100" 
                       placeholder="Enter your email" aria-label="Email">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required minlength="8" 
                       placeholder="Enter your password" aria-label="Password">
                <div class="form-text text-muted">Minimum 8 characters</div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-3">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Log in here</a>
        </div>
    </div>

    <script>
        function validateRegisterForm() {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || username.length > 50 || !/^[\w-]+$/.test(username)) {
                alert('Username: 1-50 alphanumeric characters, hyphens, or underscores only.');
                return false;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Please enter a valid email address.');
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>