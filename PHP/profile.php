<?php
// GamePlan Scheduler - Professional Profile Management
// Advanced user profile editing with favorite games management

require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$user = getUserProfile($userId);
$games = getGames();
$favoriteGames = getFavoriteGames($userId);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    try {
        // Validate bio length
        if (strlen($bio) > 500) {
            throw new Exception("Bio cannot exceed 500 characters");
        }

        // Update profile
        updateUserProfile($userId, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'bio' => $bio
        ]);

        $_SESSION['message'] = 'Profile updated successfully!';
        $_SESSION['message_type'] = 'success';

        // Refresh user data
        $user = getUserProfile($userId);

        header('Location: profile.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle favorite games update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_favorites'])) {
    $selectedGames = $_POST['favorite_games'] ?? [];

    try {
        // Remove all current favorites
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM UserGames WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Add selected favorites
        if (!empty($selectedGames)) {
            foreach ($selectedGames as $gameId) {
                if (getGameById($gameId)) { // Verify game exists
                    addFavoriteGame($userId, $gameId);
                }
            }
        }

        $_SESSION['message'] = 'Favorite games updated successfully!';
        $_SESSION['message_type'] = 'success';

        // Refresh favorite games
        $favoriteGames = getFavoriteGames($userId);

        header('Location: profile.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    try {
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Please fill in all password fields');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('New passwords do not match');
        }

        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            throw new Exception("New password must be at least " . PASSWORD_MIN_LENGTH . " characters");
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
            throw new Exception("New password must contain uppercase, lowercase, and number");
        }

        // Verify current password
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM Users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();

        if (!password_verify($currentPassword, $userData['password_hash'])) {
            throw new Exception('Current password is incorrect');
        }

        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        $stmt = $pdo->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$newPasswordHash, $userId]);

        logActivity($userId, 'password_change', 'User changed password');

        $_SESSION['message'] = 'Password changed successfully!';
        $_SESSION['message_type'] = 'success';

        header('Location: profile.php');
        exit;

    } catch (Exception $e) {
        $passwordError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - My Profile</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="logo">
                    <i class="fas fa-gamepad"></i> GamePlan Scheduler
                </a>
                <nav>
                    <ul class="d-flex">
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="profile.php" class="active">Profile</a></li>
                        <li><a href="friends.php">Friends</a></li>
                        <li><a href="schedules.php">Schedules</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="?logout=1">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1><i class="fas fa-user"></i> My Profile</h1>
                        <small class="text-muted">Manage your account and preferences</small>
                    </div>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_SESSION['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <!-- Profile Overview -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div class="avatar-circle mx-auto mb-3">
                                    <i class="fas fa-user fa-3x text-primary"></i>
                                </div>
                                <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                <p class="small text-muted">
                                    Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </p>
                            </div>

                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="stat">
                                        <div class="stat-number"><?php echo $user['friends_count']; ?></div>
                                        <div class="stat-label">Friends</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat">
                                        <div class="stat-number"><?php echo $user['schedules_count']; ?></div>
                                        <div class="stat-label">Schedules</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat">
                                        <div class="stat-number"><?php echo $user['events_count']; ?></div>
                                        <div class="stat-label">Events</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Settings -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3><i class="fas fa-edit"></i> Edit Profile</h3>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name"
                                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                               maxlength="50">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name"
                                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                               maxlength="50">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3"
                                              maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    <div class="form-text">Tell others about yourself (max 500 characters)</div>
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Favorite Games -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3><i class="fas fa-heart"></i> Favorite Games</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <?php foreach ($games as $game): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="game_<?php echo $game['game_id']; ?>"
                                                       name="favorite_games[]" value="<?php echo $game['game_id']; ?>"
                                                       <?php echo in_array($game['game_id'], array_column($favoriteGames, 'game_id')) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="game_<?php echo $game['game_id']; ?>">
                                                    <strong><?php echo htmlspecialchars($game['titel']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($game['genre']); ?></small>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="submit" name="update_favorites" class="btn btn-success">
                                    <i class="fas fa-heart"></i> Update Favorites
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <?php if (isset($passwordError)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($passwordError); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" novalidate>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div id="password-strength" class="form-text"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        // Character counter for bio
        document.getElementById('bio').addEventListener('input', function() {
            const maxLength = 500;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;

            let counter = this.parentNode.querySelector('.char-counter');
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'char-counter form-text';
                this.parentNode.appendChild(counter);
            }

            counter.textContent = `${currentLength}/${maxLength} characters`;
            counter.style.color = remaining < 50 ? '#dc3545' : '#6c757d';
        });

        // Password strength indicator for new password
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('password-strength');

            if (password.length === 0) {
                strengthIndicator.innerHTML = '';
                return;
            }

            let strength = 0;
            const checks = [
                password.length >= 8,
                /[a-z]/.test(password),
                /[A-Z]/.test(password),
                /\d/.test(password),
                /[^a-zA-Z\d]/.test(password)
            ];

            checks.forEach(check => {
                if (check) strength++;
            });

            const colors = ['text-danger', 'text-warning', 'text-info', 'text-primary', 'text-success'];
            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];

            strengthIndicator.innerHTML = `
                <span class="${colors[strength - 1] || 'text-muted'}">
                    <i class="fas fa-key"></i> ${labels[strength - 1] || 'Too Short'}
                </span>
            `;
        });

        // Form validation
        document.querySelector('form[name="change_password"]').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');

            let isValid = true;

            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                confirmPassword.nextElementSibling.textContent = 'Passwords do not match';
                isValid = false;
            } else {
                confirmPassword.classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>