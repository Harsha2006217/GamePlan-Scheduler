<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$schedules = getSchedules($user_id);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema's - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Schema's</h2>
        <div class="table-responsive">
            <table class="table table-dark table-bordered shadow-sm">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>Game</th>
                        <th>Datum</th>
                        <th>Tijd</th>
                        <th>Vrienden</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Geen schema's toegevoegd.</td></tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($schedule['game_titel']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['date']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['time']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['friends']); ?></td>
                                <td>
                                    <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-warning btn-sm me-1">Bewerken</a>
                                    <a href="delete_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Weet je zeker dat je dit schema wilt verwijderen?');">Verwijderen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-center">
            <a href="add_schedule.php" class="btn btn-primary btn-lg me-2">Schema toevoegen</a>
            <a href="index.php" class="btn btn-outline-primary btn-lg">Terug naar dashboard</a>
        </div>
    </div>
</body>
</html>