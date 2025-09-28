<?php
require 'functions.php';
if (!isLoggedIn()) header("Location: login.php");
$user_id = $_SESSION['user_id'];
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $friend_username = trim($_POST['friend_username']);
    if (empty($friend_username)) {
        $message = '<div class="alert alert-danger">Username verplicht.</div>';
    } elseif ($friend_username == $profile['username']) {
        $message = '<div class="alert alert-danger">Je kunt jezelf niet toevoegen.</div>';
    } else {
        if (addFriend($user_id, $friend_username)) {
            $message = '<div class="alert alert-success">Vriend toegevoegd.</div>';
        } else {
            $message = '<div class="alert alert-danger">Gebruiker niet gevonden of al vriend.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vriend toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Vriend toevoegen</h2>
        <?php echo $message; ?>
        <form method="POST">
            <div class="mb-3">
                <input type="text" name="friend_username" class="form-control" placeholder="Username van vriend" required>
            </div>
            <button type="submit" class="btn btn-primary">Toevoegen</button>
        </form>
    </div>
</body>
</html>