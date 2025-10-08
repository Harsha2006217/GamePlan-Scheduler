<?php
// header.php - Common Header
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Fixed header with logo, navigation menu, and user profile icon.
?>
<header class="fixed-top bg-primary p-0 shadow">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="text-decoration-none">
            <h1 class="h4 mb-0 text-white">GamePlan Scheduler</h1>
        </a>
        <nav class="navbar navbar-expand-lg">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link text-white" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="add_friend.php">Friends</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="add_schedule.php">Add Schedule</a></li>
                    <li class="nav-item"><a class="nav-link text-white btn btn-success ms-2" href="add_event.php">Add Event</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="index.php?logout=1">Logout</a></li>
                </ul>
            </div>
        </nav>
    </div>
</header>