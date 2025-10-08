<?php
// add_event.php - Add Event Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to add new events with external link input and shared with str.
require_once 'functions.php';
checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$userId = getUserId();
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $reminder = $_POST['reminder'] ?? 'none';
    $externalLink = $_POST['external_link'] ?? '';
    $sharedWithStr = $_POST['shared_with_str'] ?? '';
    $error = addEvent($userId, $title, $date, $time, $description, $reminder, $externalLink, $sharedWithStr);
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
                <label for="shared_with_str" class="form-label">Shared With (comma-separated usernames)</label>
                <input type="text" id="shared_with_str" name="shared_with_str" class="form-control" aria-label="Shared With">
            </div>
            <button type="submit" class="btn btn-primary">Add Event</button>
        </form>
    </main>
    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>