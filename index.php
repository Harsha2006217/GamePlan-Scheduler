<?php
// index.php: Dashboard with schedules, events, reminders
require_once 'functions.php';
requireLogin();
$user_id = getUserId();
$schedules = getSchedules($user_id);
$events = getEvents($user_id);
$reminders = getReminders($user_id);
$msg = getMessage();
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
        th, td { padding: 12px; border: 1px solid #444; text-align: left; }
        thead { background: #007bff; }
        tr:hover { background: #3a3a3a; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaa; }
        @media (max-width: 768px) { .container { padding: 15px; } table { font-size: 0.9em; } }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="add_friend.php">Add Friend</a>
            <a href="add_schedule.php">Add Schedule</a>
            <a href="add_event.php">Add Event</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Schedules</h3>
            <table>
                <thead>
                    <tr><th>Game</th><th>Date</th><th>Time</th><th>Friends</th><th>Reminder</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $sched): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sched['game']); ?></td>
                            <td><?php echo htmlspecialchars($sched['date']); ?></td>
                            <td><?php echo htmlspecialchars($sched['time']); ?></td>
                            <td><?php echo htmlspecialchars($sched['friends']); ?></td>
                            <td><?php echo htmlspecialchars($sched['reminder']); ?></td>
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
                            <td><?php echo implode(', ', $event['shared_with']); ?></td>
                            <td>
                                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>">Edit</a>
                                <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy | Contact
    </footer>
    <script>
        const reminders = <?php echo json_encode($reminders); ?>;
        reminders.forEach(msg => alert(msg));
    </script>
</body>
</html>