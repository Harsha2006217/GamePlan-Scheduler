<?php
// add_schedule.php: Form to add schedule
require_once 'functions.php';
requireLogin();
checkTimeout();
$games = getGames();
$friends = getFriends(getUserId());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $game_id = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] . ':00';
    $friends = $_POST['friends'] ?? [];
    $result = addSchedule($game_id, $date, $time, $friends);
    if ($result === true) {
        setMessage('success', 'Schedule added successfully!');
        header('Location: index.php');
        exit;
    } else {
        setMessage('error', $result);
    }
}
$msg = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule - GamePlan Scheduler</title>
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
            max-width: 900px; 
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
        
        .form-control, .form-select { 
            background: var(--input-bg); 
            color: var(--text-color); 
            border: 1px solid #444; 
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus { 
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
            font-size: 1rem;
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
            font-size: 1rem;
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
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .friends-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            background: var(--input-bg);
            border-radius: 8px;
            border: 1px solid #444;
            font-size: 1rem;
        }
        
        .friend-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.3s ease;
        }
        
        .friend-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .friend-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            margin-right: 10px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-size: 1.4rem;
        }
        
        .section-title i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        @media (max-width: 768px) { 
            .container { padding: 15px; }
            .friends-grid {
                grid-template-columns: 1fr;
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
                        <li class="nav-item"><a class="nav-link" href="friends.php"><i class="bi bi-people me-1"></i>Friends</a></li>
                        <li class="nav-item"><a class="nav-link active" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php"><i class="bi bi-calendar-event me-1"></i>Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <div class="text-center mb-5">
                <i class="bi bi-calendar-plus display-1 text-primary mb-3"></i>
                <h1 class="h2 mb-3">Create Gaming Schedule</h1>
                <p class="text-muted">Plan your gaming sessions with friends</p>
            </div>
            
            <form method="POST" onsubmit="return validateScheduleForm();">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <!-- Game Selection -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-joystick"></i>Game Selection
                    </h3>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="game_id" class="form-label h6">Select Game</label>
                                <select class="form-select" id="game_id" name="game_id" required aria-label="Select game">
                                    <option value="">Choose a game...</option>
                                    <?php foreach ($games as $game): ?>
                                        <option value="<?php echo $game['game_id']; ?>"><?php echo htmlspecialchars($game['titel']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-muted">
                                    Choose the game for your gaming session
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Date & Time -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-clock"></i>Schedule Timing
                    </h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date" class="form-label h6">Date</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       required min="<?php echo date('Y-m-d'); ?>" 
                                       aria-label="Schedule date">
                                <div class="form-text text-muted">
                                    Select the date for your gaming session
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="time" class="form-label h6">Time (HH:MM)</label>
                                <input type="time" class="form-control" id="time" name="time" 
                                       required aria-label="Schedule time">
                                <div class="form-text text-muted">
                                    Choose the start time for your session
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Friends Selection -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-people"></i>Invite Friends
                    </h3>
                    <?php if (empty($friends)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            You don't have any friends yet. <a href="add_friend.php" class="alert-link">Add some friends</a> to invite them to your gaming sessions!
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label h6">Select Friends to Invite</label>
                            <div class="friends-grid">
                                <?php foreach ($friends as $friend): ?>
                                    <div class="friend-item">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="friends[]" 
                                                   value="<?php echo $friend['user_id']; ?>" 
                                                   id="friend_<?php echo $friend['user_id']; ?>">
                                            <label class="form-check-label d-flex align-items-center" 
                                                   for="friend_<?php echo $friend['user_id']; ?>">
                                                <div class="friend-avatar">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div>
                                                    <div><?php echo htmlspecialchars($friend['username']); ?></div>
                                                    <small class="text-muted"><?php echo $friend['status']; ?></small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text text-muted">
                                Select friends you want to invite to this gaming session
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllFriends()">
                                <i class="bi bi-check-all me-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAllFriends()">
                                <i class="bi bi-x-circle me-1"></i>Deselect All
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="selectOnlineFriends()">
                                <i class="bi bi-wifi me-1"></i>Select Online
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-calendar-plus me-2"></i>Create Schedule
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary btn-lg px-5">
                        <i class="bi bi-arrow-left me-2"></i>Cancel
                    </a>
                </div>
            </form>
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
        function validateScheduleForm() {
            const gameId = document.getElementById('game_id').value;
            const date = document.getElementById('date').value;
            const time = document.getElementById('time').value;
            
            if (!gameId) {
                alert('Please select a game for your schedule.');
                return false;
            }
            
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                alert('Please select a future date for your gaming session.');
                return false;
            }
            
            if (!time.match(/^([01]\d|2[0-3]):[0-5]\d$/)) {
                alert('Please enter a valid time in HH:MM format.');
                return false;
            }
            
            return true;
        }
        
        function selectAllFriends() {
            document.querySelectorAll('input[name="friends[]"]').forEach(checkbox => {
                checkbox.checked = true;
            });
        }
        
        function deselectAllFriends() {
            document.querySelectorAll('input[name="friends[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
        }
        
        function selectOnlineFriends() {
            document.querySelectorAll('input[name="friends[]"]').forEach(checkbox => {
                const label = checkbox.closest('.form-check-label');
                if (label && label.textContent.includes('Online')) {
                    checkbox.checked = true;
                }
            });
        }
        
        // Set minimum time to current time if today is selected
        document.getElementById('date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            
            if (selectedDate.toDateString() === today.toDateString()) {
                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                document.getElementById('time').min = `${hours}:${minutes}`;
            } else {
                document.getElementById('time').removeAttribute('min');
            }
        });
    </script>
</body>
</html>