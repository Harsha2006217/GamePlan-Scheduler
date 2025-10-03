<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$user_id = getUserId();
$games = getGames();
$favorites = getFavoriteGames($user_id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $game_ids = $_POST['game_ids'] ?? [];
    $success = true;
    foreach ($game_ids as $game_id) {
        $result = addFavoriteGame($game_id);
        if ($result !== true) {
            setMessage('error', $result);
            $success = false;
            break;
        }
    }
    if ($success) {
        setMessage('success', 'Favorites added successfully!');
    }
    header('Location: profile.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GamePlan Scheduler</title>
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
            max-width: 1200px; 
            margin: 30px auto; 
            padding: 20px;
        }
        
        .section { 
            background: var(--card-bg); 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            transition: transform 0.3s ease;
        }
        
        .section:hover {
            transform: translateY(-2px);
        }
        
        .form-select { 
            background: var(--input-bg); 
            color: var(--text-color); 
            border: 1px solid #444; 
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-select:focus { 
            border-color: var(--primary-color); 
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
            background: var(--input-bg);
            color: var(--text-color);
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            border: none; 
            border-radius: 8px;
            padding: 12px 24px;
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
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .game-card {
            background: var(--input-bg);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .game-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .game-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .game-description {
            color: #aaa;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #444;
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
                        <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a></li>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">Manage Profile</h1>
            <div class="text-muted">
                <i class="bi bi-person-circle me-1"></i>Welcome back!
            </div>
        </div>
        
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>

        <!-- Add Favorite Games -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-star me-2"></i>Add Favorite Games</h3>
            <form method="POST" onsubmit="return validateFavoriteForm();">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="game_ids" class="form-label">Select Your Favorite Games</label>
                            <select class="form-select" id="game_ids" name="game_ids[]" multiple required 
                                    size="5" aria-label="Select favorite games">
                                <?php foreach ($games as $game): ?>
                                    <option value="<?php echo $game['game_id']; ?>">
                                        <?php echo htmlspecialchars($game['titel']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted">
                                Hold <kbd>Ctrl</kbd> (Windows) or <kbd>Cmd</kbd> (Mac) to select multiple games
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-end h-100">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-2"></i>Add to Favorites
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Your Favorites -->
        <div class="section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="section-title mb-0"><i class="bi bi-heart-fill me-2"></i>Your Favorite Games</h3>
                <span class="badge bg-primary"><?php echo count($favorites); ?> games</span>
            </div>
            
            <?php if (empty($favorites)): ?>
                <div class="empty-state">
                    <i class="bi bi-controller"></i>
                    <h4>No Favorite Games Yet</h4>
                    <p class="text-muted">Start by adding some games to your favorites list above!</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($favorites as $fav): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="game-card">
                                <div class="game-title"><?php echo htmlspecialchars($fav['titel']); ?></div>
                                <div class="game-description"><?php echo htmlspecialchars($fav['description']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Profile Stats -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-graph-up me-2"></i>Profile Statistics</h3>
            <div class="row text-center">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-primary"><?php echo count($favorites); ?></div>
                        <div class="stat-label">Favorite Games</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-success"><?php echo count(getFriends($user_id)); ?></div>
                        <div class="stat-label">Friends</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-warning"><?php echo count(getSchedules($user_id)); ?></div>
                        <div class="stat-label">Schedules</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-info"><?php echo count(getEvents($user_id)); ?></div>
                        <div class="stat-label">Events</div>
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
        function validateFavoriteForm() {
            const selected = document.getElementById('game_ids').selectedOptions.length;
            if (selected === 0) {
                alert('Please select at least one game to add to your favorites.');
                return false;
            }
            return true;
        }

        // Add search functionality to game select
        document.addEventListener('DOMContentLoaded', function() {
            const gameSelect = document.getElementById('game_ids');
            const games = Array.from(gameSelect.options);
            
            // Create search input
            const searchContainer = document.createElement('div');
            searchContainer.className = 'mb-3';
            searchContainer.innerHTML = `
                <input type="text" id="gameSearch" class="form-control" placeholder="Search games...">
            `;
            gameSelect.parentNode.insertBefore(searchContainer, gameSelect);
            
            const searchInput = document.getElementById('gameSearch');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                games.forEach(option => {
                    const gameName = option.text.toLowerCase();
                    if (gameName.includes(searchTerm)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>