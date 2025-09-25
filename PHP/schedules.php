<?php
require 'functions.php';
if (!isLoggedIn()) header("Location: login.php");
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
        <h2>Schema's</h2>
        <table class="table table-dark">
            <thead><tr><th>Game</th><th>Datum</th><th>Tijd</th><th>Vrienden</th><th>Acties</th></tr></thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($schedule['game_titel']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['date']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['time']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['friends']); ?></td>
                        <td>
                            <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-warning">Bewerken</a>
                            <a href="delete_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Zeker weten?');">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="add_schedule.php" class="btn btn-primary">Schema toevoegen</a>
    </div>
</body>
</html>