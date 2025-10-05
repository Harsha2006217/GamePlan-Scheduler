<?php
// add_event.php - Add Event Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to add new events with external link input and sharing.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$friends = getFriends($userId);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $reminder = $_POST['reminder'] ?? 'none';
    $externalLink = $_POST['external_link'] ?? '';
    $sharedFriends = $_POST['shared_friends'] ?? [];
    $error = addEvent($userId, $title, $date, $time, $description, $reminder, $externalLink, $sharedFriends);
    if (!$error) {
        setMessage('success', 'Event added successfully!');
        header("Location: index.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Add Event</h2>
        <form method="POST" onsubmit="return validateEventForm();">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" id="title" name="title" class="form-control" required maxlength="100" aria-label="Title">
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" id="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" aria-label="Date">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" id="time" name="time" class="form-control" required aria-label="Time">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3" maxlength="500" aria-label="Description"></textarea>
            </div>
            <div class="mb-3">
                <label for="reminder" class="form-label">Reminder</label>
                <select id="reminder" name="reminder" class="form-select" aria-label="Reminder">
                    <option value="none">None</option>
                    <option value="1_hour">1 Hour Before</option>
                    <option value="1_day">1 Day Before</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="external_link" class="form-label">External Link (Optional)</label>
                <input type="url" id="external_link" name="external_link" class="form-control" aria-label="External Link">
            </div>
            <div class="mb-3">
                <label class="form-label">Share with Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>">
                        <label class="form-check-label"><?php echo safeEcho($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Add Event</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>