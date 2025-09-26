<?php
/**
 * Advanced Friends Management System
 * GamePlan Scheduler - Professional Gaming Friends Network
 * 
 * This module displays comprehensive friend listings with advanced features
 * including online status, friend statistics, and interactive management.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

require 'functions.php';

// Advanced security check with session validation
if (!isLoggedIn() || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Advanced filtering and search parameters
$search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? 'all';
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?? 'username';

// Validate parameters
$valid_statuses = ['all', 'online', 'offline'];
$valid_sorts = ['username', 'status', 'last_activity', 'friend_since'];
if (!in_array($status_filter, $valid_statuses)) $status_filter = 'all';
if (!in_array($sort_by, $valid_sorts)) $sort_by = 'username';

try {
    // Get friends with advanced filtering
    $friends = getFriendsWithFiltering($user_id, $search_query, $status_filter, $sort_by);
    
    // Get friend statistics
    $friend_stats = getFriendStatistics($user_id);
    
    // Get user profile for display
    $user_profile = getProfile($user_id);
    
} catch (Exception $e) {
    error_log("Error loading friends in friends.php: " . $e->getMessage());
    $friends = [];
    $friend_stats = [
        'total_friends' => 0,
        'online_friends' => 0,
        'mutual_friends' => 0,
        'recent_activity' => 0
    ];
    $user_profile = null;
}

// Process success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vrienden Overzicht - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>
    
    <div class="container mt-5">
        <!-- Page Header with Statistics -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-5 mb-3">
                    <i class="fas fa-users me-3"></i>
                    Gaming Vrienden
                </h1>
                <p class="lead">Beheer je gaming netwerk en vind nieuwe gamers</p>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h3 class="mb-0"><?php echo $friend_stats['total_friends']; ?></h3>
                                <small>Totaal Vrienden</small>
                            </div>
                            <div class="col-6">
                                <h3 class="mb-0 text-warning"><?php echo $friend_stats['online_friends']; ?></h3>
                                <small>Nu Online</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Controls and Filters -->
        <div class="card bg-secondary mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <a href="add_friend.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Vriend Toevoegen
                        </a>
                    </div>
                    <div class="col-md-9">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Zoek vrienden..." 
                                           value="<?php echo htmlspecialchars($search_query); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>
                                        Alle Vrienden
                                    </option>
                                    <option value="online" <?php echo ($status_filter === 'online') ? 'selected' : ''; ?>>
                                        <i class="fas fa-circle text-success"></i> Online
                                    </option>
                                    <option value="offline" <?php echo ($status_filter === 'offline') ? 'selected' : ''; ?>>
                                        <i class="fas fa-circle text-secondary"></i> Offline
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="username" <?php echo ($sort_by === 'username') ? 'selected' : ''; ?>>
                                        Naam (A-Z)
                                    </option>
                                    <option value="status" <?php echo ($sort_by === 'status') ? 'selected' : ''; ?>>
                                        Status
                                    </option>
                                    <option value="last_activity" <?php echo ($sort_by === 'last_activity') ? 'selected' : ''; ?>>
                                        Laatst Actief
                                    </option>
                                    <option value="friend_since" <?php echo ($sort_by === 'friend_since') ? 'selected' : ''; ?>>
                                        Vrienden Sinds
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-light w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Friends Grid -->
        <?php if (!empty($friends)): ?>
            <div class="row">
                <?php foreach ($friends as $friend): ?>
                    <?php 
                    $is_online = ($friend['status'] === 'online');
                    $last_seen = $is_online ? 'Nu online' : 'Laatst gezien: ' . timeAgo($friend['last_activity']);
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4" data-friend-status="<?php echo $friend['status']; ?>">
                        <div class="card bg-secondary h-100 friend-card" data-friend-id="<?php echo $friend['user_id']; ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="position-relative">
                                        <div class="avatar-placeholder bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-user text-white fa-2x"></i>
                                        </div>
                                        <span class="position-absolute bottom-0 end-0 translate-middle-x badge rounded-pill bg-<?php echo $is_online ? 'success' : 'secondary'; ?>" 
                                              title="<?php echo $is_online ? 'Online' : 'Offline'; ?>">
                                            <i class="fas fa-circle" style="font-size: 8px;"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($friend['username']); ?></h5>
                                        <small class="text-<?php echo $is_online ? 'success' : 'muted'; ?>">
                                            <i class="fas fa-<?php echo $is_online ? 'wifi' : 'clock'; ?> me-1"></i>
                                            <?php echo $last_seen; ?>
                                        </small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-light dropdown-toggle" 
                                                type="button" 
                                                data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="friend_profile.php?id=<?php echo $friend['user_id']; ?>">
                                                    <i class="fas fa-user me-2"></i>Bekijk Profiel
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="add_schedule.php?invite=<?php echo $friend['user_id']; ?>">
                                                    <i class="fas fa-calendar-plus me-2"></i>Uitnodigen voor Schema
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="add_event.php?invite=<?php echo $friend['user_id']; ?>">
                                                    <i class="fas fa-trophy me-2"></i>Uitnodigen voor Event
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" 
                                                   href="remove_friend.php?id=<?php echo $friend['user_id']; ?>"
                                                   onclick="return confirm('Weet je zeker dat je <?php echo htmlspecialchars($friend['username']); ?> als vriend wilt verwijderen?');">
                                                    <i class="fas fa-user-minus me-2"></i>Verwijder Vriend
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Friend Statistics -->
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Gezamenlijke Schema's</small>
                                        <strong><?php echo $friend['shared_schedules'] ?? 0; ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Gezamenlijke Events</small>
                                        <strong><?php echo $friend['shared_events'] ?? 0; ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Vrienden Sinds</small>
                                        <strong><?php echo isset($friend['friend_since']) ? date('M Y', strtotime($friend['friend_since'])) : 'Recent'; ?></strong>
                                    </div>
                                </div>
                                
                                <!-- Favorite Games -->
                                <?php if (!empty($friend['favorite_games'])): ?>
                                    <div class="mt-3">
                                        <small class="text-muted d-block mb-2">Favoriete Games:</small>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php 
                                            $games = explode(',', $friend['favorite_games']);
                                            foreach (array_slice($games, 0, 3) as $game): 
                                            ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars(trim($game)); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($games) > 3): ?>
                                                <span class="badge bg-secondary">+<?php echo (count($games) - 3); ?> meer</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Quick Actions -->
                                <div class="mt-3 d-flex gap-2">
                                    <?php if ($is_online): ?>
                                        <button class="btn btn-sm btn-success flex-fill" onclick="inviteToPlay('<?php echo $friend['user_id']; ?>')">
                                            <i class="fas fa-gamepad me-1"></i>Uitnodigen
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-light flex-fill" onclick="sendMessage('<?php echo $friend['user_id']; ?>')">
                                        <i class="fas fa-comment me-1"></i>Bericht
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Load More Button (for pagination) -->
            <?php if (count($friends) >= 12): ?>
                <div class="text-center mt-4">
                    <button class="btn btn-outline-light" onclick="loadMoreFriends()">
                        <i class="fas fa-plus me-2"></i>Meer Vrienden Laden
                    </button>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="card bg-secondary">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-friends text-muted" style="font-size: 4rem;"></i>
                    <h3 class="mt-3">
                        <?php if (!empty($search_query)): ?>
                            Geen vrienden gevonden
                        <?php else: ?>
                            Nog geen vrienden toegevoegd
                        <?php endif; ?>
                    </h3>
                    <?php if (!empty($search_query)): ?>
                        <p class="text-muted">
                            Geen vrienden gevonden met "<?php echo htmlspecialchars($search_query); ?>". 
                            <a href="friends.php" class="text-decoration-none">Toon alle vrienden</a>
                        </p>
                    <?php else: ?>
                        <p class="text-muted mb-4">
                            Begin met het toevoegen van vrienden om je gaming netwerk uit te breiden en samen te spelen.
                        </p>
                        <a href="add_friend.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Eerste Vriend Toevoegen
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Friend Suggestions -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-lightbulb me-2"></i>Tips voor vrienden maken</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <ul class="mb-0">
                                    <li>Nodig vrienden uit voor gaming sessies</li>
                                    <li>Deel je favoriete games in je profiel</li>
                                    <li>Blijf actief voor online status</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <ul class="mb-0">
                                    <li>Organiseer toernooien met vrienden</li>
                                    <li>Plan regelmatige gaming meetups</li>
                                    <li>Deel je gaming schema's</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <ul class="mb-0">
                                    <li>Respecteer elkaars gaming tijd</li>
                                    <li>Communiceer via berichten</li>
                                    <li>Help elkaar beter worden</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-white text-center p-3 mt-5">
        <div class="container">
            <p class="mb-0">
                © 2025 GamePlan Scheduler door Harsha Kanaparthi | 
                <a href="privacy.php" class="text-white text-decoration-none">Privacybeleid</a> | 
                
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    
    <script>
        // Time ago helper function
        function timeAgo(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = Math.floor((now - time) / 1000);
            
            if (diff < 60) return 'net';
            if (diff < 3600) return Math.floor(diff / 60) + ' min geleden';
            if (diff < 86400) return Math.floor(diff / 3600) + ' uur geleden';
            if (diff < 2592000) return Math.floor(diff / 86400) + ' dagen geleden';
            
            return time.toLocaleDateString('nl-NL');
        }
        
        // Invite to play functionality
        function inviteToPlay(friendId) {
            if (confirm('Wil je deze vriend uitnodigen om te spelen?')) {
                fetch('invite_friend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        friend_id: friendId,
                        action: 'invite_to_play'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Uitnodiging verzonden!', 'Je vriend heeft een uitnodiging ontvangen.', 'success');
                    } else {
                        showToast('Fout', 'Er ging iets fout bij het verzenden van de uitnodiging.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Fout', 'Er ging iets fout bij het verzenden van de uitnodiging.', 'error');
                });
            }
        }
        
        // Send message functionality
        function sendMessage(friendId) {
            const message = prompt('Typ je bericht:');
            if (message && message.trim()) {
                fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        friend_id: friendId,
                        message: message.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Bericht verzonden!', 'Je bericht is verstuurd naar je vriend.', 'success');
                    } else {
                        showToast('Fout', 'Er ging iets fout bij het verzenden van het bericht.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Fout', 'Er ging iets fout bij het verzenden van het bericht.', 'error');
                });
            }
        }
        
        // Load more friends functionality
        function loadMoreFriends() {
            const currentCount = document.querySelectorAll('.friend-card').length;
            const loadButton = event.target;
            
            loadButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Laden...';
            loadButton.disabled = true;
            
            fetch(`friends.php?load_more=true&offset=${currentCount}`)
                .then(response => response.text())
                .then(data => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    const newFriends = tempDiv.querySelectorAll('.friend-card');
                    
                    if (newFriends.length > 0) {
                        const container = document.querySelector('.row');
                        newFriends.forEach(friend => {
                            container.appendChild(friend.parentElement);
                        });
                        
                        if (newFriends.length < 12) {
                            loadButton.style.display = 'none';
                        } else {
                            loadButton.innerHTML = '<i class="fas fa-plus me-2"></i>Meer Vrienden Laden';
                            loadButton.disabled = false;
                        }
                    } else {
                        loadButton.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadButton.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Fout bij laden';
                });
        }
        
        // Show toast notification
        function showToast(title, message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container') || createToastContainer();
            const toastId = 'toast-' + Date.now();
            
            const toastHTML = `
                <div class="toast align-items-center text-bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'}" 
                     role="alert" id="${toastId}">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}</strong><br>
                            ${message}
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remove toast after it's hidden
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }
        
        // Create toast container if it doesn't exist
        function createToastContainer() {
            const container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }
        
        // Friend card hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const friendCards = document.querySelectorAll('.friend-card');
            
            friendCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.transition = 'all 0.3s ease';
                    this.style.boxShadow = '0 8px 25px rgba(0,123,255,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-refresh online status every 30 seconds
            setInterval(function() {
                updateOnlineStatus();
            }, 30000);
        });
        
        // Update online status of friends
        function updateOnlineStatus() {
            const friendCards = document.querySelectorAll('.friend-card');
            const friendIds = Array.from(friendCards).map(card => card.dataset.friendId);
            
            if (friendIds.length > 0) {
                fetch('get_friend_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ friend_ids: friendIds })
                })
                .then(response => response.json())
                .then(data => {
                    data.forEach(friend => {
                        const card = document.querySelector(`[data-friend-id="${friend.user_id}"]`);
                        if (card) {
                            const statusBadge = card.querySelector('.badge');
                            const statusText = card.querySelector('.text-success, .text-muted');
                            const inviteButton = card.querySelector('.btn-success');
                            
                            if (friend.status === 'online') {
                                statusBadge.className = 'position-absolute bottom-0 end-0 translate-middle-x badge rounded-pill bg-success';
                                if (statusText) {
                                    statusText.className = 'text-success';
                                    statusText.innerHTML = '<i class="fas fa-wifi me-1"></i>Nu online';
                                }
                                if (!inviteButton) {
                                    const buttonContainer = card.querySelector('.d-flex.gap-2');
                                    buttonContainer.insertAdjacentHTML('afterbegin', 
                                        `<button class="btn btn-sm btn-success flex-fill" onclick="inviteToPlay('${friend.user_id}')">
                                            <i class="fas fa-gamepad me-1"></i>Uitnodigen
                                        </button>`
                                    );
                                }
                            } else {
                                statusBadge.className = 'position-absolute bottom-0 end-0 translate-middle-x badge rounded-pill bg-secondary';
                                if (statusText) {
                                    statusText.className = 'text-muted';
                                    statusText.innerHTML = `<i class="fas fa-clock me-1"></i>Laatst gezien: ${timeAgo(friend.last_activity)}`;
                                }
                                if (inviteButton) {
                                    inviteButton.remove();
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error updating status:', error));
            }
        }
    </script>
</body>
</html>