<?php
// index.php - Dashboard and Calendar View
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Main dashboard showing friends, favorites, schedules, events, and merged calendar.
// Includes session check, message display, and responsive tables/cards.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
updateLastActivity(getDBConnection(), $userId);

$friends = getFriends($userId);
$favorites = getFavoriteGames($userId);
$schedules = getSchedules($userId);
$events = getEvents($userId);
$calendarItems = getCalendarItems($userId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>

        <section class="mb-4">
            <h2>Friends List</h2>
            <table class="table table-dark table-bordered">
                <thead class="bg-info">
                    <tr><th>Username</th><th>Status</th><th>Note</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($friends as $friend): ?>
                        <tr>
                            <td><?php echo safeEcho($friend['username']); ?></td>
                            <td><?php echo $friend['status']; ?></td>
                            <td><?php echo safeEcho($friend['note']); ?></td>
                            <td>
                                <a href="edit_friend.php?id=<?php echo $friend['user_id']; ?>" class="btn btn-sm btn-warning">Edit Note</a>
                                <a href="delete.php?type=friend&id=<?php echo $friend['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mb-4">
            <h2>Favorite Games</h2>
            <table class="table table-dark table-bordered">
                <thead class="bg-info">
                    <tr><th>Title</th><th>Description</th><th>Note</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($favorites as $game): ?>
                        <tr>
                            <td><?php echo safeEcho($game['titel']); ?></td>
                            <td><?php echo safeEcho($game['description']); ?></td>
                            <td><?php echo safeEcho($game['note']); ?></td>
                            <td>
                                <a href="edit_favorite.php?id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete.php?type=favorite&id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mb-4">
            <h2>Schedules</h2>
            <table class="table table-dark table-bordered">
                <thead class="bg-info">
                    <tr><th>Game</th><th>Date</th><th>Time</th><th>Shared With</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo safeEcho($schedule['game_titel']); ?></td>
                            <td><?php echo safeEcho($schedule['date']); ?></td>
                            <td><?php echo safeEcho($schedule['time']); ?></td>
                            <td><?php echo safeEcho(getUsernamesFromIds(getDBConnection(), explode(',', $schedule['friends']))); ?></td>
                            <td>
                                <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete.php?type=schedule&id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mb-4">
            <h2>Events</h2>
            <table class="table table-dark table-bordered">
                <thead class="bg-info">
                    <tr><th>Title</th><th>Date</th><th>Time</th><th>Description</th><th>Reminder</th><th>External Link</th><th>Shared With</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo safeEcho($event['title']); ?></td>
                            <td><?php echo safeEcho($event['date']); ?></td>
                            <td><?php echo safeEcho($event['time']); ?></td>
                            <td><?php echo safeEcho($event['description']); ?></td>
                            <td><?php echo safeEcho($event['reminder']); ?></td>
                            <td><a href="<?php echo safeEcho($event['external_link']); ?>" target="_blank"><?php echo safeEcho($event['external_link']); ?></a></td>
                            <td><?php echo safeEcho($event['shared_with']); ?></td>
                            <td>
                                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mb-4">
            <h2>Calendar Overview</h2>
            <div class="row">
                <?php foreach ($calendarItems as $item): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-secondary border-0 rounded-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo safeEcho($item['title'] ?? $item['game_titel']); ?> - <?php echo safeEcho($item['date'] . ' at ' . $item['time']); ?></h5>
                                <?php if (isset($item['description'])): ?><p><?php echo safeEcho($item['description']); ?></p><?php endif; ?>
                                <?php if (isset($item['reminder'])): ?><p>Reminder: <?php echo safeEcho($item['reminder']); ?></p><?php endif; ?>
                                <?php if (isset($item['external_link'])): ?><p>Link: <a href="<?php echo safeEcho($item['external_link']); ?>" target="_blank">View</a></p><?php endif; ?>
                                <?php if (isset($item['shared_with'])): ?><p>Shared with: <?php echo safeEcho($item['shared_with']); ?></p><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
<?php
if (isset($_GET['logout'])) {
    logout();
}
?>