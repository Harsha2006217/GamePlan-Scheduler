<?php
require 'functions.php';
if (!isLoggedIn()) header("Location: login.php");
$user_id = $_SESSION['user_id'];
$events = getEvents($user_id);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evenementen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Evenementen</h2>
        <table class="table table-dark">
            <thead><tr><th>Titel</th><th>Datum</th><th>Tijd</th><th>Beschrijving</th><th>Herinnering</th><th>Gedeeld met</th><th>Acties</th></tr></thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['date']); ?></td>
                        <td><?php echo htmlspecialchars($event['time']); ?></td>
                        <td><?php echo htmlspecialchars($event['description']); ?></td>
                        <td><?php echo htmlspecialchars($event['reminder']); ?></td>
                        <td>
                            <?php foreach ($event['shared_with'] as $shared): ?>
                                <?php echo htmlspecialchars($shared['username']); ?>,
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-warning">Bewerken</a>
                            <a href="delete_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Zeker weten?');">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="add_event.php" class="btn btn-primary">Evenement toevoegen</a>
    </div>
</body>
</html>