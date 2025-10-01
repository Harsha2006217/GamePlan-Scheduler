<?php
$page_title = "Friends";
require_once 'header.php';

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Handle friend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    try {
        $friend_username = $_POST['friend_username'] ?? '';
        
        if (empty($friend_username)) {
            throw new Exception("Please enter a username");
        }

        // Get friend user ID
        $query = "SELECT user_id FROM users WHERE username = :username AND user_id != :user_id";
        $stmt = $gameplan->conn->prepare($query);
        $stmt->bindParam(':username', $friend_username);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $friend = $stmt->fetch();
        
        if (!$friend) {
            throw new Exception("User not found");
        }

        if ($gameplan->sendFriendRequest($user_id, $friend['user_id'])) {
            $success = "Friend request sent to " . htmlspecialchars($friend_username);
            
            // Create notification for the friend
            $gameplan->createNotification(
                $friend['user_id'],
                'New Friend Request',
                $_SESSION['username'] . ' sent you a friend request!',
                'Friend Request',
                $user_id
            );
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle friend request response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_request'])) {
    try {
        $friendship_id = $_POST['friendship_id'] ?? '';
        $action = $_POST['action'] ?? '';
        
        if (empty($friendship_id) || empty($action)) {
            throw new Exception("Invalid request");
        }

        $query = "UPDATE friends SET status = :status WHERE friendship_id = :friendship_id AND friend_id = :user_id";
        $stmt = $gameplan->conn->prepare($query);
        $stmt->bindParam(':status', $action);
        $stmt->bindParam(':friendship_id', $friendship_id);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            if ($action === 'Accepted') {
                $success = "Friend request accepted!";
            } else {
                $success = "Friend request declined.";
            }
        } else {
            throw new Exception("Failed to process friend request");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$friends = $gameplan->getFriends($user_id);
$friend_requests = $gameplan->getFriendRequests($user_id);
$sent_requests = $gameplan->getSentRequests($user_id);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2 text-primary"></i>Friends
            </h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFriendModal">
                <i class="fas fa-user-plus me-1"></i>Add Friend
            </button>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row">
    <!-- Friend Requests -->
    <?php if (!empty($friend_requests)): ?>
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-clock me-2"></i>
                    Friend Requests
                    <span class="badge bg-dark ms-2"><?php echo count($friend_requests); ?> new</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($friend_requests as $request): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="friend-request-card card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="friend-avatar me-3">
                                            <i class="fas fa-user fa-2x text-muted"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($request['username']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <form method="POST" action="" class="d-grid gap-2">
                                        <input type="hidden" name="respond_request" value="1">
                                        <input type="hidden" name="friendship_id" value="<?php echo $request['friendship_id']; ?>">
                                        <div class="btn-group">
                                            <button type="submit" name="action" value="Accepted" class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>Accept
                                            </button>
                                            <button type="submit" name="action" value="Declined" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times me-1"></i>Decline
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- My Friends -->
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-friends me-2 text-primary"></i>
                    My Friends
                </h5>
                <span class="badge bg-primary"><?php echo count($friends); ?> friends</span>
            </div>
            <div class="card-body">
                <?php if (empty($friends)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No friends yet</h5>
                        <p class="text-muted">Start by sending friend requests to connect with other gamers!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFriendModal">
                            <i class="fas fa-user-plus me-1"></i>Add Your First Friend
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($friends as $friend): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="friend-card card h-100">
                                    <div class="card-body text-center">
                                        <div class="friend-avatar mx-auto mb-3">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                        <h6 class="card-title"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                        <p class="card-text small text-muted mb-2">
                                            <?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?>
                                        </p>
                                        
                                        <div class="friend-actions mt-3">
                                            <div class="btn-group w-100">
                                                <a href="#" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="delete.php?type=friend&id=<?php echo $friend['user_id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm"
                                                   onclick="return confirm('Remove this friend?')">
                                                    <i class="fas fa-user-times"></i>
                                                </a>
                                            </div>
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

    <!-- Friend Suggestions & Stats -->
    <div class="col-lg-4">
        <!-- Add Friend Card -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2 text-success"></i>
                    Add New Friend
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="send_request" value="1">
                    <div class="mb-3">
                        <label for="friend_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="friend_username" name="friend_username" 
                               placeholder="Enter username..." required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane me-1"></i>Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Friend Stats -->
        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2 text-info"></i>
                    Friend Stats
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="stat-number text-primary fw-bold fs-4">
                            <?php echo count($friends); ?>
                        </div>
                        <div class="stat-label text-muted small">Total Friends</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-number text-warning fw-bold fs-4">
                            <?php echo count($friend_requests); ?>
                        </div>
                        <div class="stat-label text-muted small">Pending</div>
                    </div>
                    <div class="col-6">
                        <div class="stat-number text-success fw-bold fs-4">
                            <?php echo count($sent_requests); ?>
                        </div>
                        <div class="stat-label text-muted small">Sent</div>
                    </div>
                    <div class="col-6">
                        <div class="stat-number text-info fw-bold fs-4">
                            <?php
                            $online_friends = 0; // This would be calculated based on last activity
                            echo $online_friends;
                            ?>
                        </div>
                        <div class="stat-label text-muted small">Online</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow mt-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2 text-warning"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addFriendModal">
                        <i class="fas fa-user-plus me-2"></i>Add Friend
                    </button>
                    <a href="#" class="btn btn-outline-success">
                        <i class="fas fa-search me-2"></i>Find Gamers
                    </a>
                    <a href="#" class="btn btn-outline-info">
                        <i class="fas fa-share-alt me-2"></i>Invite Friends
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Friend Modal -->
<div class="modal fade" id="addFriendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Add New Friend
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="send_request" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_friend_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="modal_friend_username" name="friend_username" 
                               placeholder="Enter username..." required>
                        <div class="form-text">Enter the exact username of the person you want to add</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Your friend will receive a notification and can accept your request.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Helper function to get sent requests
function getSentRequests($user_id) {
    global $gameplan;
    $query = "SELECT f.*, u.username, u.first_name, u.last_name 
              FROM friends f 
              JOIN users u ON f.friend_id = u.user_id 
              WHERE f.user_id = :user_id AND f.status = 'Pending'";
    
    $stmt = $gameplan->conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetchAll();
}
?>

<?php require_once 'footer.php'; ?>