<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $friend_username = $_POST['friend_username'] ?? '';
    $result = addFriend($friend_username);
    if ($result === true) {
        setMessage('success', 'Friend added successfully!');
        header('Location: friends.php');
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
    <title>Add Friend - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
            --input-bg: #2c2c2c;
            --text-color: #ffffff;
            --header-bg: #1a1a2e;
        }
        
        body { 
            background: linear-gradient(135deg, #121212 0%, #1a1a2e 50%, #16213e 100%);
            color: var(--text-color); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; 
            padding: 0;
            min-height: 100vh;
        }
        
        header { 
            background: var(--header-bg); 
            padding: 15px 0; 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }
        
        .nav-link { 
            color: #ddd !important; 
            margin: 0 10px; 
            text-decoration: none; 
            font-size: 1rem; 
            transition: all 0.3s ease;
            border-radius: 6px;
            padding: 8px 16px !important;
        }
        
        .nav-link:hover { 
            color: var(--primary-color) !important; 
            background: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        .container { 
            max-width: 800px; 
            margin: 30px auto; 
            padding: 20px;
        }
        
        .section { 
            background: var(--card-bg); 
            border-radius: 12px; 
            padding: 30px; 
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
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
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
        }
        
        .alert { 
            border-radius: 8px; 
            padding: 15px 20px;
            border: none;
            margin-bottom: 20px;
        }
        
        .alert-success { 
            background: rgba(40,167,69,0.2); 
            color: #28a745; 
            border-left: 4px solid #28a745; 
        }
        
        .alert-danger { 
            background: rgba(220,53,69,0.2); 
            color: #dc3545; 
            border-left: 4px solid #dc3545; 
        }
        
        footer { 
            background: var(--header-bg); 
            padding: 20px; 
            text-align: center; 
            color: #aaa; 
            font-size: 0.9em;
            margin-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .feature-card {
            background: var(--input-bg);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) { 
            .container { padding: 15px; }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="bi bi-controller me-2"></i>GamePlan Scheduler
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i>Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php"><i class="bi bi-people me-1"></i>Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php"><i class="bi bi-calendar-event me-1"></i>Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <div class="section">
            <div class="text-center mb-5">
                <i class="bi bi-person-plus-fill display-1 text-primary mb-3"></i>
                <h1 class="h2 mb-3">Add a Friend</h1>
                <p class="text-muted">Connect with other gamers and build your gaming community</p>
            </div>
            
            <?php $msg = getMessage(); if ($msg): ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($msg['msg']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return validateAddFriendForm();" class="mb-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="mb-4">
                            <label for="friend_username" class="form-label h5">
                                <i class="bi bi-search me-2"></i>Friend's Username
                            </label>
                            <input type="text" class="form-control form-control-lg" id="friend_username" name="friend_username" 
                                   required placeholder="Enter your friend's username" aria-label="Friend username">
                            <div class="form-text text-muted">
                                Enter the exact username of the person you want to add as a friend
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-plus-circle me-2"></i>Send Friend Request
                            </button>
                            <a href="friends.php" class="btn btn-outline-secondary btn-lg px-5">
                                <i class="bi bi-arrow-left me-2"></i>Back to Friends
                            </a>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Features Section -->
            <div class="row mt-5">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5>Build Your Squad</h5>
                        <p class="text-muted small">Create your ultimate gaming team with trusted friends</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h5>Coordinate Games</h5>
                        <p class="text-muted small">Schedule gaming sessions and events with your friends</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <h5>Compete Together</h5>
                        <p class="text-muted small">Join tournaments and climb leaderboards as a team</p>
                    </div>
                </div>
            </div>
            
            <!-- Tips Section -->
            <div class="mt-5 p-4 rounded" style="background: rgba(255,255,255,0.05);">
                <h5 class="mb-3"><i class="bi bi-lightbulb me-2"></i>Tips for Finding Friends</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="text-muted">
                            <li>Ask for usernames in gaming communities</li>
                            <li>Connect with players from recent matches</li>
                            <li>Share your username on social media</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="text-muted">
                            <li>Join gaming Discord servers</li>
                            <li>Participate in community events</li>
                            <li>Be active in game forums</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            © 2025 GamePlan Scheduler by Harsha Kanaparthi | 
            <a href="#" style="color: #aaa;">Privacy Policy</a> | 
            <a href="#" style="color: #aaa;">Contact Support</a>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateAddFriendForm() {
            const username = document.getElementById('friend_username').value.trim();
            
            if (!username) {
                alert('Please enter a username to add as a friend.');
                return false;
            }
            
            if (!/^[\w-]+$/.test(username)) {
                alert('Username can only contain letters, numbers, hyphens, and underscores.');
                return false;
            }
            
            if (username.length > 50) {
                alert('Username must be 50 characters or less.');
                return false;
            }
            
            return true;
        }
        
        // Add real-time validation
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('friend_username');
            
            usernameInput.addEventListener('input', function() {
                const username = this.value.trim();
                const isValid = /^[\w-]*$/.test(username) && username.length <= 50;
                
                if (username && !isValid) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        });
    </script>
</body>
</html>