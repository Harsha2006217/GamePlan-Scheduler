<?php
/**
 * Friends status widget for displaying real-time friend statuses
 * Include this file in pages where you want to show friends' status
 */

$friends_status = getFriendsStatus($_SESSION['user_id']);
?>

<div class="friends-status-widget card shadow-sm mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-people-fill"></i>
            Vrienden Status
        </h5>
        <span class="badge bg-light text-primary" id="onlineFriendsCount">
            <?php echo count(array_filter($friends_status, fn($f) => $f['status_type'] !== 'offline')); ?> Online
        </span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush" id="friendsStatusList">
            <?php if (empty($friends_status)): ?>
                <div class="list-group-item text-center text-muted py-3">
                    <i class="bi bi-emoji-neutral"></i> Geen vrienden online
                </div>
            <?php else: ?>
                <?php foreach ($friends_status as $friend): ?>
                    <div class="list-group-item friend-status-item" data-user-id="<?php echo $friend['user_id']; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="d-flex align-items-center">
                                    <span class="status-badge me-2" data-status="<?php echo $friend['status_type']; ?>"></span>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                </div>
                                <?php if ($friend['status_type'] === 'playing' && $friend['game_name']): ?>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-controller"></i>
                                        Speelt <?php echo htmlspecialchars($friend['game_name']); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($friend['status_message']): ?>
                                    <small class="text-muted d-block mt-1">
                                        <?php echo htmlspecialchars($friend['status_message']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="action-buttons">
                                <?php if ($friend['status_type'] === 'playing'): ?>
                                    <button class="btn btn-sm btn-outline-primary join-game-btn" 
                                            data-user-id="<?php echo $friend['user_id']; ?>"
                                            data-game-id="<?php echo $friend['game_id']; ?>">
                                        <i class="bi bi-plus-circle"></i> Meedoen
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle join game buttons
    document.querySelectorAll('.join-game-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const userId = this.dataset.userId;
            const gameId = this.dataset.gameId;
            
            try {
                // First update own status to 'playing'
                await fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                    },
                    body: JSON.stringify({
                        status_type: 'playing',
                        game_id: gameId
                    })
                });
                
                // Then create a notification for the friend
                await fetch('create_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        type: 'game_join',
                        message: '<?php echo $_SESSION['username']; ?> wil met je meespelen!'
                    })
                });
                
                location.reload();
            } catch (error) {
                alert('Error joining game: ' + error.message);
            }
        });
    });
    
    // Auto-refresh friend statuses
    setInterval(async function() {
        try {
            const response = await fetch('get_status.php', {
                headers: {
                    'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                }
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            updateFriendsDisplay(data.friends_status);
        } catch (error) {
            console.error('Error refreshing friend statuses:', error);
        }
    }, 30000); // Update every 30 seconds
});

function updateFriendsDisplay(friends) {
    const listContainer = document.getElementById('friendsStatusList');
    const onlineCount = document.getElementById('onlineFriendsCount');
    
    // Update online friends count
    const onlineFriends = friends.filter(f => f.status_type !== 'offline');
    onlineCount.textContent = onlineFriends.length + ' Online';
    
    // Update friend list
    if (friends.length === 0) {
        listContainer.innerHTML = `
            <div class="list-group-item text-center text-muted py-3">
                <i class="bi bi-emoji-neutral"></i> Geen vrienden online
            </div>
        `;
        return;
    }
    
    const newHtml = friends.map(friend => `
        <div class="list-group-item friend-status-item" data-user-id="${friend.user_id}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="d-flex align-items-center">
                        <span class="status-badge me-2" data-status="${friend.status_type}"></span>
                        <h6 class="mb-0">${escapeHtml(friend.username)}</h6>
                    </div>
                    ${friend.status_type === 'playing' && friend.game_name ? `
                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-controller"></i>
                            Speelt ${escapeHtml(friend.game_name)}
                        </small>
                    ` : ''}
                    ${friend.status_message ? `
                        <small class="text-muted d-block mt-1">
                            ${escapeHtml(friend.status_message)}
                        </small>
                    ` : ''}
                </div>
                <div class="action-buttons">
                    ${friend.status_type === 'playing' ? `
                        <button class="btn btn-sm btn-outline-primary join-game-btn"
                                data-user-id="${friend.user_id}"
                                data-game-id="${friend.game_id}">
                            <i class="bi bi-plus-circle"></i> Meedoen
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
    
    listContainer.innerHTML = newHtml;
    
    // Reattach event listeners
    document.querySelectorAll('.join-game-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const gameId = this.dataset.gameId;
            // ... handle join game logic
        });
    });
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<style>
.friends-status-widget .list-group-item {
    transition: background-color 0.2s;
}
.friends-status-widget .list-group-item:hover {
    background-color: rgba(0,0,0,0.01);
}
.status-badge {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}
.status-badge[data-status="online"] { background-color: #198754; }
.status-badge[data-status="playing"] { background-color: #0d6efd; }
.status-badge[data-status="looking"] { background-color: #ffc107; }
.status-badge[data-status="break"] { background-color: #6c757d; }
.status-badge[data-status="offline"] { background-color: #dc3545; }
</style>