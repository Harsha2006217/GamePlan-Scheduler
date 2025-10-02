<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$user_id = getUserId();
$favorites = getFavoriteGames($user_id);
$friends = getFriends($user_id);
$schedules = getSchedules($user_id);
$events = getEvents($user_id);
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
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; }
        header { background: #1e1e1e; padding: 15px; text-align: center; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: sticky; top: 0; z-index: 1; }
        nav a { color: #fff; margin: 0 15px; text-decoration: none; }
        nav a:hover { color: #007bff; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .section { background: #2c2c2c; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border: 1px solid #444; }
        thead { background: #007bff; }
        tr:hover { background: #3a3a3a; }
        .card { background: #2c2c2c; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaa; }
    </style>
</head>
<body>
    <header>
        <h1>GamePlan Scheduler</h1>
        <nav>
            <a href="profile.php">Profile</a>
            <a href="friends.php">Friends</a>
            <a href="add_schedule.php">Add Schedule</a>
            <a href="add_event.php">Add Event</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <div class="container">
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Favorite Games</h3>
            <ul>
                <?php foreach ($favorites as $fav): ?>
                    <li><?php echo htmlspecialchars($fav['titel']); ?> - <?php echo htmlspecialchars($fav['description']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="section">
            <h3>Friends List</h3>
            <ul>
                <?php foreach ($friends as $friend): ?>
                    <li><?php echo htmlspecialchars($friend['username']); ?> - <?php echo $friend['status']; ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="add_friend.php" class="btn btn-primary">Add Friend</a>
        </div>

        <div class="section">
            <h3>Schedules</h3>
            <table>
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
                                <a href="edit_schedule.php?id=<?php echo $sched['schedule_id']; ?>">Edit</a>
                                <a href="delete.php?type=schedule&id=<?php echo $sched['schedule_id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Events</h3>
            <table>
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
                                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>">Edit</a>
                                <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Calendar Overview</h3>
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
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy | Contact
    </footer>
    <script>
        // Display reminders as pop-ups
        const reminders = <?php echo json_encode($reminders); ?>;
        reminders.forEach(msg => alert(msg));
    </script>
</body>
</html>