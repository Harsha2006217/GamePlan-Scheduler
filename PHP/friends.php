<?php
// GamePlan Scheduler - Professional Friend Management
// Advanced friend system with search, online status, and management

require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$friends = getFriends($userId);
$onlineFriends = getOnlineFriends($userId);

// Handle friend search
$searchQuery = trim($_GET['search'] ?? '');
$searchResults = [];

if (!empty($searchQuery)) {
    $searchResults = searchUsers($searchQuery, $userId);
}

// Handle add friend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_friend'])) {
    $friendUsername = trim($_POST['friend_username'] ?? '');

    try {
        if (empty($friendUsername)) {
            throw new Exception('Please enter a username');
        }

        addFriend($userId, $friendUsername);

        $_SESSION['message'] = 'Friend request sent successfully!';
        $_SESSION['message_type'] = 'success';

        header('Location: friends.php');
        exit;

    } catch (Exception $e) {
        $addFriendError = $e->getMessage();
    }
}

// Handle remove friend
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $friendId = (int)$_GET['remove'];

    try {
        removeFriend($userId, $friendId);

        $_SESSION['message'] = 'Friend removed successfully!';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }

    header('Location: friends.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - My Friends</title>
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
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="friends.php" class="active">Friends</a></li>
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
                        <h1><i class="fas fa-users"></i> My Friends</h1>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFriendModal">
                            <i class="fas fa-user-plus"></i> Add Friend
                        </button>
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

            <!-- Online Friends -->
            <?php if (!empty($onlineFriends)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-circle text-success"></i> Online Now (<?php echo count($onlineFriends); ?>)</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($onlineFriends as $friend): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body text-center">
                                                    <div class="avatar-circle mx-auto mb-2">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <h6 class="card-title"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                                    <span class="badge bg-success">Online</span>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            Last seen: <?php echo date('g:i A', strtotime($friend['last_activity'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All Friends -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3><i class="fas fa-users"></i> All Friends (<?php echo count($friends); ?>)</h3>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="text" class="form-control" id="friendSearch" placeholder="Search friends...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($friends)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">No friends yet</h4>
                                    <p class="text-muted">Start building your gaming community by adding friends!</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFriendModal">
                                        <i class="fas fa-user-plus"></i> Add Your First Friend
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="row" id="friendsList">
                                    <?php foreach ($friends as $friend): ?>
                                        <div class="col-md-6 col-lg-4 mb-4 friend-item">
                                            <div class="card h-100">
                                                <div class="card-body text-center">
                                                    <div class="avatar-circle mx-auto mb-2">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <h6 class="card-title"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                                    <div class="mb-2">
                                                        <span class="badge <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted d-block mb-3">
                                                        Last active: <?php echo date('M j, Y g:i A', strtotime($friend['last_activity'])); ?>
                                                    </small>
                                                    <div class="btn-group w-100">
                                                        <button class="btn btn-outline-primary btn-sm" onclick="startChat('<?php echo htmlspecialchars($friend['username']); ?>')">
                                                            <i class="fas fa-comment"></i> Message
                                                        </button>
                                                        <a href="friends.php?remove=<?php echo $friend['user_id']; ?>"
                                                           class="btn btn-outline-danger btn-sm"
                                                           onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($friend['username']); ?> from your friends?')">
                                                            <i class="fas fa-user-minus"></i> Remove
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Results (if searching) -->
            <?php if (!empty($searchQuery) && !empty($searchResults)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-search"></i> Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($searchResults as $user): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <div class="avatar-circle mx-auto mb-2">
                                                        <i class="fas fa-user text-secondary"></i>
                                                    </div>
                                                    <h6><?php echo htmlspecialchars($user['username']); ?></h6>
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="friend_username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                        <button type="submit" name="add_friend" class="btn btn-success btn-sm">
                                                            <i class="fas fa-user-plus"></i> Add Friend
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Friend Modal -->
    <div class="modal fade" id="addFriendModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add Friend</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($addFriendError)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($addFriendError); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="friend_username" class="form-label">Friend's Username</label>
                            <input type="text" class="form-control" id="friend_username" name="friend_username"
                                   placeholder="Enter username" required>
                            <div class="form-text">Search for users by their username</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="add_friend" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Send Friend Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        // Friend search functionality
        document.getElementById('friendSearch').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const friends = document.querySelectorAll('.friend-item');

            friends.forEach(friend => {
                const username = friend.querySelector('.card-title').textContent.toLowerCase();
                if (username.includes(query)) {
                    friend.style.display = 'block';
                } else {
                    friend.style.display = 'none';
                }
            });
        });

        // Placeholder for chat functionality
        function startChat(username) {
            alert(`Chat feature with ${username} coming soon!`);
            // In a real implementation, this would open a chat window or redirect to a chat page
        }

        // Auto-focus search input if there's a search query
        <?php if (!empty($searchQuery)): ?>
            document.getElementById('friendSearch').focus();
            document.getElementById('friendSearch').value = '<?php echo htmlspecialchars($searchQuery); ?>';
        <?php endif; ?>
    </script>
</body>
</html>