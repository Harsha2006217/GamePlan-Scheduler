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
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; }
        header { background: #1e1e1e; padding: 15px; text-align: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .nav-link { color: #ffffff; margin: 0 15px; text-decoration: none; font-size: 1.1em; transition: color 0.3s; }
        .nav-link:hover { color: #007bff; }
        .navbar-toggler { border-color: #007bff; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .section { background: #2c2c2c; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 1.1em; }
        table th, table td { padding: 12px; border: 1px solid #ddd; text-align: left; transition: background 0.3s; }
        table thead { background: #007bff; color: #fff; }
        table tr:hover { background: #3a3a3a; }
        .card { background: #2c2c2c; border-radius: 10px; padding: 15px; margin-bottom: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.3); transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .alert { border-radius: 5px; padding: 12px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaa; font-size: 0.9em; }
        @media (max-width: 768px) { table { font-size: 0.9em; } .container { padding: 15px; } }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">GamePlan Scheduler</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php">Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php">Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php">Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="container">
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Favorite Games <i class="bi bi-star-fill"></i></h3>
            <ul>
                <?php foreach ($favorites as $fav): ?>
                    <li><?php echo htmlspecialchars($fav['titel']); ?> - <?php echo htmlspecialchars($fav['description']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="section">
            <h3>Friends List <i class="bi bi-people-fill"></i></h3>
            <ul>
                <?php foreach ($friends as $friend): ?>
                    <li><?php echo htmlspecialchars($friend['username']); ?> - <?php echo $friend['status']; ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="add_friend.php" class="btn btn-primary">Add Friend <i class="bi bi-person-plus"></i></a>
        </div>

        <div class="section">
            <h3>Schedules <i class="bi bi-calendar-event"></i></h3>
            <a href="?sort=date_asc" class="btn btn-sm btn-secondary">Sort Date ASC</a>
            <a href="?sort=date_desc" class="btn btn-sm btn-secondary">Sort Date DESC</a>
            <table class="table table-dark">
                <thead>
                    <tr><th>Game</th><th>Date</th><th>Time</th><th>Friends</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $sched): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sched['game_titel']); ?></td>
                            <td><?php echo htmlspecialchars($sched['date']); ?></td>
                            <td><?php echo htmlspecialchars($sched['time']); ?></td>
                            <td><?php echo htmlspecialchars($sched['friends']); ?></td>
                            <td>
                                <a href="edit_schedule.php?id=<?php echo $sched['schedule_id']; ?>" class="btn btn-sm btn-primary" aria-label="Edit schedule">Edit <i class="bi bi-pencil"></i></a>
                                <a href="delete.php?type=schedule&id=<?php echo $sched['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirm delete schedule?');" aria-label="Delete schedule">Delete <i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Events <i class="bi bi-calendar-check"></i></h3>
            <a href="?sort=date_asc" class="btn btn-sm btn-secondary">Sort Date ASC</a>
            <a href="?sort=date_desc" class="btn btn-sm btn-secondary">Sort Date DESC</a>
            <table class="table table-dark">
                <thead>
                    <tr><th>Title</th><th>Date</th><th>Time</th><th>Description</th><th>Reminder</th><th>Shared With</th><th>Actions</th></tr>
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
                                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary" aria-label="Edit event">Edit <i class="bi bi-pencil"></i></a>
                                <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirm delete event?');" aria-label="Delete event">Delete <i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Calendar Overview <i class="bi bi-calendar"></i></h3>
            <?php foreach ($calendar as $item): ?>
                <div class="card">
                    <h5><?php echo htmlspecialchars($item['title'] ?? $item['game_titel']); ?> - <?php echo htmlspecialchars($item['date']); ?> at <?php echo htmlspecialchars($item['time']); ?></h5>
                    <?php if (isset($item['description'])): ?><p><?php echo htmlspecialchars($item['description']); ?></p><?php endif; ?>
                    <?php if (isset($item['reminder'])): ?><p>Reminder: <?php echo htmlspecialchars($item['reminder']); ?></p><?php endif; ?>
                    <?php if (isset($item['shared_with'])): ?><p>Shared with: <?php echo implode(', ', $item['shared_with']); ?></p><?php endif; ?>
                    <?php if (isset($item['friends'])): ?><p>Friends: <?php echo htmlspecialchars($item['friends']); ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | <a href="#" style="color: #aaa;">Privacy</a> | <a href="#" style="color: #aaa;">Contact</a>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show reminders
        const reminders = <?php echo json_encode($reminders); ?>;
        reminders.forEach(msg => alert(msg));
    </script>
</body>
</html>