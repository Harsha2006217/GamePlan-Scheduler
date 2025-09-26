<header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-gamepad me-2"></i>
            GamePlan Scheduler
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <?php if (isset($_SESSION['user_id'])): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Profiel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="friends.php">
                            <i class="fas fa-user-friends"></i> Vrienden
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">
                            <i class="fas fa-calendar"></i> Schema's
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="fas fa-calendar-day"></i> Evenementen
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav ms-auto d-flex align-items-center">
                    <a href="add_event.php" class="btn btn-success btn-sm me-3">
                        <i class="fas fa-plus"></i> Evenement toevoegen
                    </a>
                    <span class="nav-item nav-link text-light me-3">
                        <i class="fas fa-user-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Uitloggen
                    </a>
                </div>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Inloggen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-2"></i>
                            Registreren
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="header-spacer"></div>
