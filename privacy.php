<?php
// privacy.php - Privacy Policy Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Displays the privacy policy with secure session check.
// Complies with AVG/GDPR as per project ethics.

require_once 'functions.php';

checkSessionTimeout();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5 mb-5">
        <h1 class="text-center mb-4">Privacy Policy</h1>
        <div class="card bg-secondary text-light p-4 rounded-3">
            <p>We value your privacy and are committed to protecting your personal information. This policy outlines how we collect, use, and safeguard your data.</p>
            <h5 class="mt-4">Data Collection</h5>
            <p>We collect names, emails, favorite games, and schedules solely for app functionality. No unnecessary data is stored.</p>
            <h5>Security Measures</h5>
            <p>Passwords are hashed using bcrypt. Inputs are sanitized to prevent SQL injections. Sessions timeout after 30 minutes of inactivity.</p>
            <h5>Data Usage</h5>
            <p>Your data is used only for planning and sharing within the app. It is not shared with third parties without consent.</p>
            <h5>User Rights</h5>
            <p>You can delete your data via profile settings. We comply with AVG/GDPR regulations.</p>
            <h5>Contact</h5>
            <p>For questions, contact us at support@gameplanscheduler.com.</p>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>