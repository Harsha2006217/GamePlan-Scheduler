<?php
// edit_schedule.php - Edit Schedule Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to edit existing schedules.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    header("Location: index.php");
    exit;
}

$schedules = getSchedules($userId);
$schedule = array_filter($schedules, function($s) use ($id) { return $s['schedule_id'] == $id; });
$schedule = reset($schedule);
if (!$schedule) {
    setMessage('danger', 'Schedule not found or no permission.');
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $game = $_POST['game'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $sharedWith = $_POST['shared_with'] ?? '';
    $error = editSchedule($userId, $id, $game, $date, $time, $sharedWith);
    if (!$error) {
        setMessage('success', 'Schedule updated successfully!');
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
    <title>Edit Schedule - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Edit Schedule</h2>
        <form method="POST" onsubmit="return validateScheduleForm();">
            <div class="mb-3">
                <label for="game" class="form-label">Game</label>
                <input type="text" id="game" name="game" class="form-control" required maxlength="100" value="<?php echo safeEcho($schedule['game']); ?>">
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" id="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo safeEcho($schedule['date']); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" id="time" name="time" class="form-control" required value="<?php echo safeEcho($schedule['time']); ?>">
            </div>
            <div class="mb-3">
                <label for="shared_with" class="form-label">Shared With (comma-separated usernames)</label>
                <input type="text" id="shared_with" name="shared_with" class="form-control" value="<?php echo safeEcho($schedule['shared_with']); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Update Schedule</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>