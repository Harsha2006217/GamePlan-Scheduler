<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$user_id = getUserId();
$sort = $_GET['sort'] ?? 'date ASC, time ASC';
$favorites = getFavoriteGames($user_id);
$friends = getFriends($user_id);
$schedules = getSchedules($user_id, $sort);
$events = getEvents($user_id, $sort);
$calendar = getCalendarData($user_id);
$reminders = getDueReminders($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GamePlan Scheduler</title>
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
        
        .navbar-toggler { border-color: var(--primary-color); }
        
        .container { 
            max-width: 1400px; 
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
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 0.95rem;
            border-radius: 8px;
            overflow: hidden;
        }
        
        table th, table td { 
            padding: 12px 15px; 
            border: 1px solid #444; 
            text-align: left; 
            transition: background 0.3s ease;
        }
        
        table thead { 
            background: linear-gradient(135deg, var(--primary-color), #0056b3); 
            color: #fff; 
        }
        
        table tr:hover { 
            background: rgba(255,255,255,0.05); 
        }
        
        .card { 
            background: var(--card-bg); 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 15px; 
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .card:hover { 
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
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
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            border: none; 
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-primary:hover { 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 1rem;
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #aaa;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) { 
            table { font-size: 0.85em; } 
            .container { padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
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
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($favorites); ?></div>
                <div class="stat-label">Favorite Games</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($friends); ?></div>
                <div class="stat-label">Friends</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($schedules); ?></div>
                <div class="stat-label">Schedules</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($events); ?></div>
                <div class="stat-label">Events</div>
            </div>
        </div>

        <!-- Favorite Games -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-star-fill me-2"></i>Favorite Games</h3>
            <?php if (empty($favorites)): ?>
                <p class="text-muted">No favorite games yet. <a href="profile.php">Add some favorites</a>!</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($favorites as $fav): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card">
                                <h6 class="card-title text-primary"><?php echo htmlspecialchars($fav['titel']); ?></h6>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($fav['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Friends List -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-people-fill me-2"></i>Friends List</h3>
            <?php if (empty($friends)): ?>
                <p class="text-muted">No friends yet. <a href="add_friend.php">Add some friends</a>!</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($friends as $friend): ?>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                    <span class="badge bg-<?php echo $friend['status'] === 'Online' ? 'success' : 'secondary'; ?>">
                                        <i class="bi bi-circle-fill me-1"></i><?php echo $friend['status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a href="add_friend.php" class="btn btn-primary mt-3">
                <i class="bi bi-person-plus me-2"></i>Add Friend
            </a>
        </div>

        <!-- Schedules -->
        <div class="section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="section-title mb-0"><i class="bi bi-calendar-event me-2"></i>Schedules</h3>
                <div>
                    <a href="?sort=date ASC, time ASC" class="btn btn-sm btn-secondary me-2">Date ASC</a>
                    <a href="?sort=date DESC, time DESC" class="btn btn-sm btn-secondary">Date DESC</a>
                </div>
            </div>
            
            <?php if (empty($schedules)): ?>
                <p class="text-muted">No schedules yet. <a href="add_schedule.php">Create your first schedule</a>!</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Game</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Friends</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $sched): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sched['game_titel']); ?></td>
                                    <td><?php echo htmlspecialchars($sched['date']); ?></td>
                                    <td><?php echo htmlspecialchars($sched['time']); ?></td>
                                    <td><?php echo htmlspecialchars($sched['friends']); ?></td>
                                    <td>
                                        <a href="edit_schedule.php?id=<?php echo $sched['schedule_id']; ?>" class="btn btn-sm btn-primary me-1" aria-label="Edit schedule">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="delete.php?type=schedule&id=<?php echo $sched['schedule_id']; ?>" class="btn btn-sm btn-danger" aria-label="Delete schedule">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Events -->
        <div class="section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="section-title mb-0"><i class="bi bi-calendar-check me-2"></i>Events</h3>
                <div>
                    <a href="?sort=date ASC, time ASC" class="btn btn-sm btn-secondary me-2">Date ASC</a>
                    <a href="?sort=date DESC, time DESC" class="btn btn-sm btn-secondary">Date DESC</a>
                </div>
            </div>
            
            <?php if (empty($events)): ?>
                <p class="text-muted">No events yet. <a href="add_event.php">Create your first event</a>!</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Description</th>
                                <th>Reminder</th>
                                <th>Shared With</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['date']); ?></td>
                                    <td><?php echo htmlspecialchars($event['time']); ?></td>
                                    <td><?php echo htmlspecialchars($event['description']); ?></td>
                                    <td><?php echo htmlspecialchars($event['reminder']); ?></td>
                                    <td><?php echo implode(', ', $event['shared_with'] ?? []); ?></td>
                                    <td>
                                        <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary me-1" aria-label="Edit event">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger" aria-label="Delete event">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Calendar Overview -->
        <div class="section">
            <h3 class="section-title"><i class="bi bi-calendar me-2"></i>Calendar Overview</h3>
            <?php if (empty($calendar)): ?>
                <p class="text-muted">No items in calendar yet. Start by adding schedules or events!</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($calendar as $item): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">
                                        <?php echo htmlspecialchars($item['title'] ?? $item['game_titel']); ?>
                                    </h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <i class="bi bi-calendar me-1"></i><?php echo htmlspecialchars($item['date']); ?> 
                                        <i class="bi bi-clock ms-2 me-1"></i><?php echo htmlspecialchars($item['time']); ?>
                                    </h6>
                                    <?php if (isset($item['description'])): ?>
                                        <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <?php endif; ?>
                                    <?php if (isset($item['reminder'])): ?>
                                        <p class="card-text"><small class="text-warning"><i class="bi bi-bell me-1"></i>Reminder: <?php echo htmlspecialchars($item['reminder']); ?></small></p>
                                    <?php endif; ?>
                                    <?php if (isset($item['shared_with'])): ?>
                                        <p class="card-text"><small><i class="bi bi-share me-1"></i>Shared with: <?php echo implode(', ', $item['shared_with']); ?></small></p>
                                    <?php endif; ?>
                                    <?php if (isset($item['friends'])): ?>
                                        <p class="card-text"><small><i class="bi bi-people me-1"></i>Friends: <?php echo htmlspecialchars($item['friends']); ?></small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
        // Show reminders
        const reminders = <?php echo json_encode($reminders); ?>;
        if (reminders.length > 0) {
            reminders.forEach(msg => {
                const notification = document.createElement('div');
                notification.className = 'alert alert-warning position-fixed top-0 end-0 m-3';
                notification.style.zIndex = '1060';
                notification.innerHTML = `
                    <i class="bi bi-bell-fill me-2"></i>${msg}
                    <button type="button" class="btn-close btn-close-white float-end" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 10000);
            });
        }
        
        // Smooth scrolling
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('a[href^="#"]').forEach(link => {
                link.addEventListener('click', e => {
                    e.preventDefault();
                    const target = document.querySelector(link.getAttribute('href'));
                    target?.scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
</body>
</html>