<?php
// friends.php - Advanced Friends Management
// Author: Harsha Kanaparthi
// Date: 30-09-2025

require_once 'functions.php';
checkSessionTimeout();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$friends = getFriends($userId);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_friend'])) {
        $friendUsername = $_POST['friend_username'] ?? '';
        $note = $_POST['note'] ?? '';
        
        $error = addFriend($userId, $friendUsername, $note);
        if (!$error) {
            setMessage('success', 'Friend added successfully!');
            header("Location: friends.php");
            exit;
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="container-fluid">
    <?php echo getMessage(); ?>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">Friends Management</h1>
                    <p class="text-muted">Manage your gaming friends and connections</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary fs-6">
                        <i class="fas fa-users me-1"></i><?php echo count($friends); ?> Friends
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Add Friend Form -->
        <div class="col-lg-4 mb-4">
            <div class="advanced-card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Add New Friend
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo safeEcho($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="friend_username" class="form-label fw-semibold">Friend's Username *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-end-0">
                                    <i class="fas fa-at text-muted"></i>
                                </span>
                                <input type="text" 
                                       id="friend_username" 
                                       name="friend_username" 
                                       class="form-control form-control-advanced border-start-0 real-time-validate" 
                                       required 
                                       maxlength="50"
                                       placeholder="Enter username (e.g., gamer123)"
                                       value="<?php echo safeEcho($_POST['friend_username'] ?? ''); ?>">
                            </div>
                            <div class="form-text">Enter the exact username of your friend</div>
                        </div>

                        <div class="mb-4">
                            <label for="note" class="form-label fw-semibold">Friend Note</label>
                            <textarea id="note" 
                                      name="note" 
                                      class="form-control form-control-advanced" 
                                      rows="3"
                                      placeholder="Add a note about this friend (optional)"><?php echo safeEcho($_POST['note'] ?? ''); ?></textarea>
                            <div class="form-text">How do you know this friend? Favorite games to play together?</div>
                        </div>

                        <button type="submit" name="add_friend" class="btn btn-primary btn-advanced w-100">
                            <i class="fas fa-user-plus me-2"></i>Add Friend
                        </button>
                    </form>

                    <!-- Quick Add Suggestions -->
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">Quick Add Suggestions</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quickAdd('pro_gamer')">
                                pro_gamer
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quickAdd('epic_player')">
                                epic_player
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quickAdd('game_master')">
                                game_master
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Friends Statistics -->
            <div class="advanced-card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Friends Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $onlineCount = count(array_filter($friends, function($f) { return $f['status'] === 'Online'; }));
                    $offlineCount = count($friends) - $onlineCount;
                    ?>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stats-number text-success"><?php echo $onlineCount; ?></div>
                            <div class="stats-label">Online</div>
                        </div>
                        <div class="col-6">
                            <div class="stats-number text-muted"><?php echo $offlineCount; ?></div>
                            <div class="stats-label">Offline</div>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-success" 
                             style="width: <?php echo count($friends) ? ($onlineCount / count($friends)) * 100 : 0; ?>%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Friends List -->
        <div class="col-lg-8">
            <div class="advanced-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>My Friends List
                        <span class="badge bg-primary ms-2"><?php echo count($friends); ?></span>
                    </h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="#" onclick="filterFriends('all')">All Friends</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterFriends('online')">Online Only</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterFriends('offline')">Offline Only</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="sortFriends('username')">Sort by Username</a></li>
                            <li><a class="dropdown-item" href="#" onclick="sortFriends('status')">Sort by Status</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($friends)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-friends display-1 text-muted mb-4"></i>
                            <h4 class="text-muted mb-3">No Friends Yet</h4>
                            <p class="text-muted mb-4">Start building your gaming network by adding friends.</p>
                            <div class="row justify-content-center">
                                <div class="col-auto">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-arrow-left fs-2 text-primary mb-2"></i>
                                        <small class="text-muted">Use the form to add friends</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Search Bar -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control form-control-advanced border-start-0 real-time-search" 
                                           placeholder="Search friends by username or note...">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-advanced table-hover">
                                <thead>
                                    <tr>
                                        <th>Friend</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                        <th>Last Activity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($friends as $friend): ?>
                                        <tr class="friend-row fade-in" data-status="<?php echo strtolower($friend['status']); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="position-relative me-3">
                                                        <i class="fas fa-user-circle fs-2 text-primary"></i>
                                                        <?php if ($friend['status'] === 'Online'): ?>
                                                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle">
                                                                <span class="visually-hidden">Online</span>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <strong class="d-block"><?php echo safeEcho($friend['username']); ?></strong>
                                                        <small class="text-muted">User ID: <?php echo $friend['user_id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $friend['status'] === 'Online' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                                    <?php echo $friend['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($friend['note']): ?>
                                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                          data-bs-toggle="tooltip" title="<?php echo safeEcho($friend['note']); ?>">
                                                        <?php echo safeEcho($friend['note']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No note</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $friend['status'] === 'Online' ? 'Active now' : 'Recently'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit_friend.php?id=<?php echo $friend['friend_id']; ?>" 
                                                       class="btn btn-warning"
                                                       data-bs-toggle="tooltip"
                                                       title="Edit Friend Note">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-info"
                                                            data-bs-toggle="tooltip"
                                                            title="Send Message"
                                                            onclick="sendMessage('<?php echo safeEcho($friend['username']); ?>')">
                                                        <i class="fas fa-comment"></i>
                                                    </button>
                                                    <a href="delete.php?type=friend&id=<?php echo $friend['friend_id']; ?>" 
                                                       class="btn btn-danger advanced-confirm"
                                                       data-confirm-message="Are you sure you want to remove '<?php echo safeEcho($friend['username']); ?>' from your friends?"
                                                       data-confirm-action="Remove Friend">
                                                        <i class="fas fa-user-minus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Bulk Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="advanced-card bg-secondary">
                                    <div class="card-body py-3">
                                        <div class="row align-items-center">
                                            <div class="col">
                                                <small class="text-light">
                                                    <i class="fas fa-users me-2"></i>
                                                    You have <?php echo count($friends); ?> friends. 
                                                    <?php echo count(array_filter($friends, function($f) { return $f['status'] === 'Online'; })); ?> currently online.
                                                </small>
                                            </div>
                                            <div class="col-auto">
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-light" onclick="exportFriends()">
                                                        <i class="fas fa-download me-1"></i>Export List
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-light" onclick="refreshStatus()">
                                                        <i class="fas fa-sync-alt me-1"></i>Refresh Status
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Friend Activity Feed -->
            <div class="advanced-card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-rss me-2"></i>Recent Friend Activity
                    </h6>
                </div>
                <div class="card-body">
                    <div class="activity-feed">
                        <?php if (empty($friends)): ?>
                            <div class="text-center py-3">
                                <p class="text-muted mb-0">No friend activity to display</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            // Simulate friend activity
                            $activities = [
                                ' started playing Fortnite',
                                ' completed a mission in GTA V',
                                ' reached level 50 in COD',
                                ' is now online',
                                ' joined a tournament',
                                ' added a new game to favorites'
                            ];
                            shuffle($friends);
                            $recentFriends = array_slice($friends, 0, 4);
                            ?>
                            <?php foreach ($recentFriends as $friend): ?>
                                <div class="activity-item d-flex align-items-center mb-3">
                                    <i class="fas fa-user-circle text-primary me-3 fs-4"></i>
                                    <div class="flex-grow-1">
                                        <strong><?php echo safeEcho($friend['username']); ?></strong>
                                        <?php echo $activities[array_rand($activities)]; ?>
                                    </div>
                                    <small class="text-muted">Just now</small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickAdd(username) {
    document.getElementById('friend_username').value = username;
    document.getElementById('friend_username').focus();
}

function filterFriends(status) {
    const rows = document.querySelectorAll('.friend-row');
    rows.forEach(row => {
        if (status === 'all' || row.getAttribute('data-status') === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function sortFriends(by) {
    // This would typically be done server-side, but we can do client-side for demo
    const tbody = document.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        if (by === 'username') {
            const nameA = a.querySelector('strong').textContent.toLowerCase();
            const nameB = b.querySelector('strong').textContent.toLowerCase();
            return nameA.localeCompare(nameB);
        } else if (by === 'status') {
            const statusA = a.getAttribute('data-status');
            const statusB = b.getAttribute('data-status');
            return statusA.localeCompare(statusB);
        }
        return 0;
    });
    
    // Remove existing rows and append sorted ones
    rows.forEach(row => tbody.appendChild(row));
}

function sendMessage(username) {
    const modalHtml = `
        <div class="modal fade" id="messageModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content advanced-card">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">
                            <i class="fas fa-comment me-2"></i>Message ${username}
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control form-control-advanced" rows="4" placeholder="Type your message here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="sendMessageToFriend('${username}')">
                            <i class="fas fa-paper-plane me-1"></i>Send Message
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('messageModal'));
    modal.show();
    
    document.getElementById('messageModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

function sendMessageToFriend(username) {
    // Simulate sending message
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 100px; right: 20px; z-index: 1060; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>Message sent to ${username}!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('messageModal')).hide();
}

function exportFriends() {
    const friends = <?php echo json_encode(array_map(function($f) { 
        return ['username' => $f['username'], 'status' => $f['status'], 'note' => $f['note']]; 
    }, $friends)); ?>;
    
    let content = "My Friends List\\n\\n";
    friends.forEach(friend => {
        content += `Username: ${friend.username}\\n`;
        content += `Status: ${friend.status}\\n`;
        content += `Note: ${friend.note || 'No note'}\\n`;
        content += "---\\n";
    });
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'my-friends-list.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    // Show success message
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 100px; right: 20px; z-index: 1060; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>Friends list exported successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function refreshStatus() {
    // Simulate status refresh
    document.querySelectorAll('.status-indicator').forEach(indicator => {
        indicator.classList.add('loading-spinner');
    });
    
    setTimeout(() => {
        document.querySelectorAll('.status-indicator').forEach(indicator => {
            indicator.classList.remove('loading-spinner');
        });
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-info alert-dismissible fade show position-fixed';
        alert.style.cssText = 'top: 100px; right: 20px; z-index: 1060; min-width: 300px;';
        alert.innerHTML = `
            <i class="fas fa-sync-alt me-2"></i>Friend statuses updated!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 3000);
    }, 1000);
}
</script>

<?php include 'footer.php'; ?>