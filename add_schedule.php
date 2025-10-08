<?php
// add_schedule.php - Add Schedule Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to add new schedules with game title input, date, time, shared with text.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gameTitle = $_POST['game_title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $sharedWithStr = $_POST['shared_with'] ?? '';
    $error = addSchedule($userId, $gameTitle, $date, $time, $sharedWithStr);
    if (!$error) {
        setMessage('success', 'Schedule added successfully!');
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
    <title>Add Schedule - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Add Schedule</h2>
        <form method="POST" onsubmit="return validateScheduleForm();">
            <div class="mb-3">
                <label for="game_title" class="form-label">Game Title</label>
                <input type="text" id="game_title" name="game_title" class="form-control" required maxlength="100" aria-label="Game Title">
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
                <label for="shared_with" class="form-label">Shared With (comma-separated usernames, optional)</label>
                <input type="text" id="shared_with" name="shared_with" class="form-control" aria-label="Shared With">
            </div>
            <button type="submit" class="btn btn-primary">Add Schedule</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>