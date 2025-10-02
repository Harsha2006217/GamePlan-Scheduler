<?php
require_once 'functions.php';

requireLogin();
checkTimeout();

$user_id = getUserId();
$favorites = getFavoriteGames($user_id);
$friends = getFriends($user_id);
$schedules = getSchedules($user_id);
$events = getEvents($user_id);
$calendar = getCalendarOverview();
$reminders = getDueReminders();

if (!empty($reminders)) {
    echo '<script>';
    foreach ($reminders as $reminder) {
        echo 'alert("' . $reminder . '");';
    }
    echo '</script>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Dashboard</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <h3>Favorite Games</h3>
        <ul>
            <?php foreach ($favorites as $favorite): ?>
                <li><?php echo $favorite['gametitel']; ?> - <?php echo $favorite['game_description']; ?></li>
            <?php endforeach; ?>
        </ul>

        <h3>Friends</h3>
        <ul>
            <?php foreach ($friends as $friend): ?>
                <li><?php echo $friend['username']; ?> - <?php echo $friend['status']; ?></li>
            <?php endforeach; ?>
        </ul>

        <h3>Schedules</h3>
        <table class="table">
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
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?php echo $schedule['game_titel']; ?></td>
                        <td><?php echo $schedule['date']; ?></td>
                        <td><?php echo $schedule['time']; ?></td>
                        <td><?php echo $schedule['friends']; ?></td>
                        <td>
                            <a href="edit_schedule.php?schedule_id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="delete.php?type=schedule&id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Events</h3>
        <table class="table">
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
                        <td><?php echo $event['title']; ?></td>
                        <td><?php echo $event['date']; ?></td>
                        <td><?php echo $event['time']; ?></td>
                        <td><?php echo $event['description']; ?></td>
                        <td><?php echo $event['reminder']; ?></td>
                        <td><?php echo implode(', ', $event['shared_with'] ?? []); ?></td>
                        <td>
                            <a href="edit_event.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Calendar Overview</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Description</th>
                    <th>Reminder</th>
                    <th>Shared With</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($calendar as $item): ?>
                    <tr>
                        <td><?php echo $item['type']; ?></td>
                        <td><?php echo $item['title']; ?></td>
                        <td><?php echo $item['date']; ?></td>
                        <td><?php echo $item['time']; ?></td>
                        <td><?php echo $item['description']; ?></td>
                        <td><?php echo $item['reminder']; ?></td>
                        <td><?php echo $item['shared_with']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>