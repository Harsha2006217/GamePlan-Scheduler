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
            margin: 0; 
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 20px auto;
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
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
        }
        
        .alert { 
            border-radius: 8px; 
            padding: 15px;
            border: none;
        }
        
        .alert-success { background: rgba(40,167,69,0.2); color: #28a745; border-left: 4px solid #28a745; }
        .alert-danger { background: rgba(220,53,69,0.2); color: #dc3545; border-left: 4px solid #dc3545; }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #ddd;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #aaa;
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) { 
            .login-container { 
                margin: 10px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="bi bi-controller"></i>
            <h2 class="mb-0">GamePlan Scheduler</h2>
            <p class="text-muted">Welcome Back, Gamer!</p>
        </div>
        
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> mb-4">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" onsubmit="return validateLoginForm();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="mb-4">
                <label for="username" class="form-label">
                    <i class="bi bi-person me-2"></i>Username
                </label>
                <input type="text" class="form-control" id="username" name="username" required 
                       placeholder="Enter your username" aria-label="Username">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required 
                       placeholder="Enter your password" aria-label="Password">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>
        
        <div class="register-link">
            New to GamePlan? <a href="register.php">Create an account</a>
        </div>
    </div>

    <script>
        function validateLoginForm() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username) {
                alert('Please enter your username.');
                return false;
            }
            
            if (!password) {
                alert('Please enter your password.');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>