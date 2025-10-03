<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$friends = getFriends(getUserId());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends - GamePlan Scheduler</title>
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
            line-height: 1.6;
            font-size: 1.1rem;
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
            max-width: 1200px; 
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
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            border: none; 
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
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
        
        .friend-card {
            background: var(--input-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .friend-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .online-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .online {
            background: rgba(40,167,69,0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }
        
        .offline {
            background: rgba(108,117,125,0.2);
            color: #6c757d;
            border: 1px solid #6c757d;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 1rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #444;
        }
        
        .friend-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .friend-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-right: 15px;
        }
        
        @media (max-width: 768px) { 
            .container { padding: 15px; }
            .friend-card {
                text-align: center;
            }
            .friend-avatar {
                margin: 0 auto 15px;
            }
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
                        <li class="nav-item"><a class="nav-link active" href="friends.php"><i class="bi bi-people me-1"></i>Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php"><i class="bi bi-calendar-event me-1"></i>Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">Your Friends</h1>
            <a href="add_friend.php" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i>Add Friend
            </a>
        </div>

        <div class="section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0"><i class="bi bi-people-fill me-2"></i>Friends List</h3>
                <span class="badge bg-primary"><?php echo count($friends); ?> friends</span>
            </div>
            
            <?php if (empty($friends)): ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <h3>No Friends Yet</h3>
                    <p class="text-muted mb-4">Start building your gaming community by adding friends!</p>
                    <a href="add_friend.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-person-plus me-2"></i>Add Your First Friend
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($friends as $friend): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="friend-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="friend-avatar">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($friend['username']); ?></h5>
                                        <span class="online-status <?php echo strtolower($friend['status']); ?>">
                                            <i class="bi bi-circle-fill me-1"></i><?php echo $friend['status']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="friend-actions">
                                    <button class="btn btn-sm btn-outline-primary flex-fill" 
                                            onclick="sendMessage('<?php echo htmlspecialchars($friend['username']); ?>')">
                                        <i class="bi bi-chat me-1"></i>Message
                                    </button>
                                    <button class="btn btn-sm btn-outline-success flex-fill"
                                            onclick="inviteToGame('<?php echo htmlspecialchars($friend['username']); ?>')">
                                        <i class="bi bi-controller me-1"></i>Invite
                                    </button>
                                </div>
                                
                                <?php if ($friend['status'] === 'Online'): ?>
                                    <div class="mt-2">
                                        <small class="text-success">
                                            <i class="bi bi-clock me-1"></i>Active now
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>Last seen recently
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Friends Statistics -->
                <div class="mt-4 p-4 rounded" style="background: rgba(255,255,255,0.05);">
                    <div class="row text-center">
                        <div class="col-6 col-md-3 mb-3">
                            <div class="text-primary h4 mb-1">
                                <?php echo count(array_filter($friends, fn($f) => $f['status'] === 'Online')); ?>
                            </div>
                            <div class="text-muted small">Online Now</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="text-success h4 mb-1"><?php echo count($friends); ?></div>
                            <div class="text-muted small">Total Friends</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="text-warning h4 mb-1">
                                <?php 
                                    $onlineFriends = array_filter($friends, fn($f) => $f['status'] === 'Online');
                                    echo count($friends) > 0 ? round((count($onlineFriends) / count($friends)) * 100) : 0;
                                ?>%
                            </div>
                            <div class="text-muted small">Online Rate</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="text-info h4 mb-1"><?php echo count($friends); ?></div>
                            <div class="text-muted small">Gaming Buddies</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="section">
            <h4 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="add_friend.php" class="btn btn-outline-primary w-100 h-100 py-3">
                        <i class="bi bi-person-plus display-6 mb-2 d-block"></i>
                        Add Friend
                    </a>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-success w-100 h-100 py-3" onclick="inviteAllOnline()">
                        <i class="bi bi-controller display-6 mb-2 d-block"></i>
                        Invite Online Friends
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-info w-100 h-100 py-3" onclick="viewFriendSuggestions()">
                        <i class="bi bi-people display-6 mb-2 d-block"></i>
                        Find Friends
                    </button>
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
        function sendMessage(username) {
            alert(`Message feature coming soon! Would send message to ${username}`);
        }
        
        function inviteToGame(username) {
            alert(`Game invitation sent to ${username}!`);
        }
        
        function inviteAllOnline() {
            const onlineFriends = <?php echo json_encode(array_filter($friends, fn($f) => $f['status'] === 'Online')); ?>;
            if (onlineFriends.length > 0) {
                alert(`Invitations sent to ${onlineFriends.length} online friends!`);
            } else {
                alert('No online friends to invite at the moment.');
            }
        }
        
        function viewFriendSuggestions() {
            alert('Friend suggestions feature coming soon!');
        }
        
        // Auto-refresh online status every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>