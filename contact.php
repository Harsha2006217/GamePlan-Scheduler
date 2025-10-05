<?php
// contact.php - Contact Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Simple contact information page.

require_once 'functions.php';

checkSessionTimeout();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <h1>Contact Us</h1>
        <p>For support or inquiries, email us at support@gameplanscheduler.com
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>