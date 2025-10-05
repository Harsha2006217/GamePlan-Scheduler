<?php
// privacy.php - Privacy Policy Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Simple privacy policy text based on design document.

require_once 'functions.php';

checkSessionTimeout();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <h1>Privacy Policy</h1>
        <p>We store only your name, email, favorite games, and schedules for planning purposes. Data is not sold or shared without permission. Passwords are hashed and secure. You can delete your data via profile settings. This complies with AVG/GDPR regulations.</p>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>