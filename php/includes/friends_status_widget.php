<?php
/**
 * GamePlan Scheduler - Enhanced Professional Friends Status Widget
 * Advanced Real-Time Friend Status Management with Gaming Interface
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Friends Status Widget
 */

// Ensure user is logged in before displaying widget
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-warning">U moet ingelogd zijn om vrienden status te bekijken.</div>';
    return;
}

// Enhanced error handling and database connection check
try {
    // Get comprehensive friends status with advanced gaming data
    $friends_status = getFriendsStatus($_SESSION['user_id']);
    $current_user_status = getUserStatus($_SESSION['user_id']);
    
    // Validate data integrity
    if (!is_array($friends_status)) {
        $friends_status = [];
    }
    
} catch (Exception $e) {
    error_log('Friends Status Widget Error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">Vrienden status kon niet worden geladen.</div>';
    return;
}

// Advanced status categorization for gaming
$online_friends = array_filter($friends_status, function($friend) {
    return in_array($friend['status_type'], ['online', 'playing', 'looking']);
});

$playing_friends = array_filter($friends_status, function($friend) {
    return $friend['status_type'] === 'playing';
});

$looking_friends = array_filter($friends_status, function($friend) {
    return $friend['status_type'] === 'looking';
});

// Generate unique widget ID for multiple instances
$widget_id = 'friends_status_widget_' . uniqid();
?>

<!-- Enhanced GamePlan Scheduler Friends Status Widget -->
<div id="<?php echo htmlspecialchars($widget_id); ?>" class="friends-status-widget card shadow-lg mb-4">
    <div class="card-header bg-gradient-gaming text-white position-relative">
        <div class="header-pulse"></div>
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="bi bi-people-fill me-2 gaming-icon"></i>
                <span class="gaming-title">Gaming Squad</span>
                <div class="status-indicator-pulse"></div>
            </h5>
            <div class="friends-counter-group">
                <span class="badge bg-success me-1" id="onlineFriendsCount_<?php echo $widget_id; ?>">
                    <?php echo count($online_friends); ?> Online
                </span>
                <span class="badge bg-primary me-1" id="playingFriendsCount_<?php echo $widget_id; ?>">
                    <?php echo count($playing_friends); ?> Gaming
                </span>
                <span class="badge bg-warning" id="lookingFriendsCount_<?php echo $widget_id; ?>">
                    <?php echo count($looking_friends); ?> LFG
                </span>
            </div>
        </div>
        <div class="widget-subtitle">
            <small class="text-white-50">
                <i class="bi bi-globe2 me-1"></i>
                Real-time gaming status van je squad
            </small>
        </div>
    </div>
    
    <div class="card-body p-0">
        <!-- Gaming Status Filters -->
        <div class="status-filter-bar p-3 border-bottom">
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="statusFilter_<?php echo $widget_id; ?>" id="filter_all_<?php echo $widget_id; ?>" value="all" checked>
                <label class="btn btn-outline-primary btn-sm" for="filter_all_<?php echo $widget_id; ?>">
                    <i class="bi bi-people"></i> Alle Vrienden
                </label>
                
                <input type="radio" class="btn-check" name="statusFilter_<?php echo $widget_id; ?>" id="filter_online_<?php echo $widget_id; ?>" value="online">
                <label class="btn btn-outline-success btn-sm" for="filter_online_<?php echo $widget_id; ?>">
                    <i class="bi bi-circle-fill"></i> Online
                </label>
                
                <input type="radio" class="btn-check" name="statusFilter_<?php echo $widget_id; ?>" id="filter_playing_<?php echo $widget_id; ?>" value="playing">
                <label class="btn btn-outline-info btn-sm" for="filter_playing_<?php echo $widget_id; ?>">
                    <i class="bi bi-controller"></i> Gaming
                </label>
                
                <input type="radio" class="btn-check" name="statusFilter_<?php echo $widget_id; ?>" id="filter_looking_<?php echo $widget_id; ?>" value="looking">
                <label class="btn btn-outline-warning btn-sm" for="filter_looking_<?php echo $widget_id; ?>">
                    <i class="bi bi-search"></i> LFG
                </label>
            </div>
        </div>
        
        <!-- Friends Status List -->
        <div class="friends-status-container" style="max-height: 450px; overflow-y: auto;">
            <div class="list-group list-group-flush" id="friendsStatusList_<?php echo $widget_id; ?>">
                <?php if (empty($friends_status)): ?>
                    <div class="empty-state text-center py-5">
                        <i class="bi bi-person-plus-fill text-muted" style="font-size: 3rem;"></i>
                        <h6 class="text-muted mt-3">Geen vrienden gevonden</h6>
                        <p class="text-muted small">Voeg vrienden toe om hun gaming status te zien</p>
                        <a href="add_friend.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-person-plus me-1"></i>
                            Vrienden Toevoegen
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($friends_status as $friend): ?>
                        <?php
                        // Calculate activity status
                        $last_activity = strtotime($friend['last_activity']);
                        $current_time = time();
                        $time_diff = $current_time - $last_activity;
                        
                        $activity_status = 'Offline';
                        $activity_class = 'text-danger';
                        
                        if ($time_diff <= 300) { // 5 minutes
                            $activity_status = 'Nu Online';
                            $activity_class = 'text-success';
                        } elseif ($time_diff <= 1800) { // 30 minutes
                            $activity_status = 'Recent Actief';
                            $activity_class = 'text-warning';
                        } elseif ($time_diff <= 3600) { // 1 hour
                            $activity_status = 'Laatst actief: ' . round($time_diff / 60) . 'm geleden';
                            $activity_class = 'text-muted';
                        }
                        
                        // Status message truncation
                        $display_message = '';
                        if (!empty($friend['status_message'])) {
                            $display_message = strlen($friend['status_message']) > 50 
                                ? substr($friend['status_message'], 0, 47) . '...'
                                : $friend['status_message'];
                        }
                        ?>
                        
                        <div class="list-group-item friend-status-item border-0 friend-filter-item" 
                             data-user-id="<?php echo htmlspecialchars($friend['user_id']); ?>"
                             data-status="<?php echo htmlspecialchars($friend['status_type']); ?>"
                             data-game-id="<?php echo htmlspecialchars($friend['game_id'] ?? ''); ?>">
                            
                            <div class="d-flex justify-content-between align-items-start py-2">
                                <div class="friend-info flex-grow-1">
                                    <!-- Friend Header -->
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="status-indicator-container me-2">
                                            <span class="status-badge status-<?php echo htmlspecialchars($friend['status_type']); ?>" 
                                                  data-status="<?php echo htmlspecialchars($friend['status_type']); ?>"
                                                  title="<?php echo ucfirst(htmlspecialchars($friend['status_type'])); ?>"></span>
                                            <?php if ($friend['status_type'] === 'playing'): ?>
                                                <div class="playing-pulse"></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="friend-name-container flex-grow-1">
                                            <h6 class="friend-name mb-0 fw-bold">
                                                <?php echo htmlspecialchars($friend['username']); ?>
                                            </h6>
                                            <small class="<?php echo $activity_class; ?> fw-500">
                                                <?php echo htmlspecialchars($activity_status); ?>
                                            </small>
                                        </div>
                                        
                                        <?php if ($friend['status_type'] === 'online' && empty($friend['game_name'])): ?>
                                            <span class="badge bg-success badge-glow">Beschikbaar</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Current Game Info -->
                                    <?php if ($friend['status_type'] === 'playing' && !empty($friend['game_name'])): ?>
                                        <div class="current-game-info bg-dark bg-opacity-25 rounded p-2 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-controller text-primary me-2"></i>
                                                <div class="game-details">
                                                    <div class="game-title fw-bold text-primary">
                                                        <?php echo htmlspecialchars($friend['game_name']); ?>
                                                    </div>
                                                    <div class="game-activity small text-muted">
                                                        <i class="bi bi-clock me-1"></i>
                                                        Speelt nu
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Status Message -->
                                    <?php if (!empty($display_message)): ?>
                                        <div class="status-message-container">
                                            <div class="status-message bg-info bg-opacity-10 rounded p-2">
                                                <i class="bi bi-chat-quote text-info me-1"></i>
                                                <em class="text-info">
                                                    "<?php echo htmlspecialchars($display_message); ?>"
                                                </em>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="friend-actions ms-3">
                                    <div class="btn-group-vertical btn-group-sm">
                                        <?php if ($friend['status_type'] === 'playing' && !empty($friend['game_id'])): ?>
                                            <button class="btn btn-outline-primary btn-sm join-game-btn mb-1" 
                                                    data-user-id="<?php echo htmlspecialchars($friend['user_id']); ?>"
                                                    data-game-id="<?php echo htmlspecialchars($friend['game_id']); ?>"
                                                    data-username="<?php echo htmlspecialchars($friend['username']); ?>"
                                                    data-game-name="<?php echo htmlspecialchars($friend['game_name']); ?>"
                                                    title="Meedoen met <?php echo htmlspecialchars($friend['username']); ?>">
                                                <i class="bi bi-plus-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($friend['status_type'], ['online', 'looking'])): ?>
                                            <button class="btn btn-outline-success btn-sm invite-friend-btn mb-1" 
                                                    data-user-id="<?php echo htmlspecialchars($friend['user_id']); ?>"
                                                    data-username="<?php echo htmlspecialchars($friend['username']); ?>"
                                                    title="Uitnodigen voor gaming sessie">
                                                <i class="bi bi-envelope-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-outline-info btn-sm view-profile-btn" 
                                                data-user-id="<?php echo htmlspecialchars($friend['user_id']); ?>"
                                                data-username="<?php echo htmlspecialchars($friend['username']); ?>"
                                                title="Bekijk profiel van <?php echo htmlspecialchars($friend['username']); ?>">
                                            <i class="bi bi-person-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions Footer -->
        <?php if (!empty($friends_status)): ?>
            <div class="card-footer bg-dark bg-opacity-25 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-arrow-clockwise me-1 refresh-icon"></i>
                        Laatst bijgewerkt: <span id="lastUpdateTime_<?php echo $widget_id; ?>">nu</span>
                    </small>
                    <div class="quick-actions">
                        <button class="btn btn-outline-primary btn-sm me-2" id="refreshStatusBtn_<?php echo $widget_id; ?>">
                            <i class="bi bi-arrow-clockwise"></i>
                            Vernieuwen
                        </button>
                        <a href="add_friend.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-person-plus"></i>
                            Vriend Toevoegen
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Professional Styling -->
<style>
/* GamePlan Scheduler Friends Status Widget Professional Styling */
.friends-status-widget {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border: 2px solid #00d4ff;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
    overflow: hidden;
    transition: all 0.3s ease;
}

.friends-status-widget:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(0, 212, 255, 0.4);
}

.bg-gradient-gaming {
    background: linear-gradient(90deg, #000000 0%, #1a1a1a 100%);
    position: relative;
    overflow: hidden;
}

.header-pulse {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.2), transparent);
    animation: headerPulse 3s ease-in-out infinite;
}

@keyframes headerPulse {
    0%, 100% { left: -100%; }
    50% { left: 100%; }
}

.gaming-icon {
    font-size: 1.3rem;
    color: #00d4ff;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
    animation: iconPulse 2s ease-in-out infinite;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.gaming-title {
    font-family: 'Orbitron', 'Courier New', monospace;
    font-weight: 600;
    color: #00d4ff;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
}

.status-indicator-pulse {
    position: absolute;
    right: -10px;
    top: 50%;
    transform: translateY(-50%);
    width: 8px;
    height: 8px;
    background: #00d4ff;
    border-radius: 50%;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 212, 255, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(0, 212, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(0, 212, 255, 0); }
}

.friends-counter-group .badge {
    font-size: 0.75rem;
    padding: 0.4em 0.6em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-filter-bar {
    background: rgba(0, 212, 255, 0.05);
    border-bottom: 1px solid rgba(0, 212, 255, 0.2);
}

.status-filter-bar .btn-group .btn {
    border-color: rgba(0, 212, 255, 0.3);
    transition: all 0.3s ease;
}

.status-filter-bar .btn-check:checked + .btn {
    background: linear-gradient(135deg, #00d4ff 0%, #0a58ca 100%);
    border-color: #00d4ff;
    color: white;
    box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
}

.friend-status-item {
    background: transparent;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.friend-status-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: transparent;
    transition: all 0.3s ease;
}

.friend-status-item:hover {
    background: rgba(0, 212, 255, 0.05);
    transform: translateX(5px);
}

.friend-status-item:hover::before {
    background: linear-gradient(180deg, #00d4ff 0%, #8b5cf6 100%);
}

.status-indicator-container {
    position: relative;
    display: inline-block;
}

.status-badge {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    display: inline-block;
    border: 2px solid rgba(255, 255, 255, 0.3);
    position: relative;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
}

.status-online { 
    background: #198754; 
    box-shadow: 0 0 12px #198754;
    animation: statusGlow 2s ease-in-out infinite;
}

.status-playing { 
    background: #0d6efd; 
    box-shadow: 0 0 12px #0d6efd;
    animation: statusGlow 2s ease-in-out infinite;
}

.status-looking { 
    background: #ffc107; 
    box-shadow: 0 0 12px #ffc107;
    animation: statusGlow 2s ease-in-out infinite;
}

.status-break { 
    background: #6c757d; 
    box-shadow: 0 0 8px #6c757d;
}

.status-offline { 
    background: #dc3545; 
    box-shadow: 0 0 8px #dc3545;
}

@keyframes statusGlow {
    0%, 100% { box-shadow: 0 0 8px currentColor; }
    50% { box-shadow: 0 0 15px currentColor, 0 0 25px currentColor; }
}

.playing-pulse {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid #0d6efd;
    border-radius: 50%;
    animation: playingPulse 1.5s ease-out infinite;
}

@keyframes playingPulse {
    0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
}

.friend-name {
    color: #ffffff;
    font-size: 0.95rem;
}

.current-game-info {
    border-left: 3px solid #0d6efd;
    transition: all 0.3s ease;
}

.current-game-info:hover {
    transform: translateX(3px);
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
}

.game-title {
    font-size: 0.9rem;
    font-weight: 600;
}

.status-message {
    border-left: 3px solid #17a2b8;
    font-size: 0.85rem;
}

.friend-actions .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.friend-actions .btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.join-game-btn:hover {
    background: linear-gradient(135deg, #0d6efd 0%, #084298 100%);
    color: white;
}

.invite-friend-btn:hover {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: white;
}

.view-profile-btn:hover {
    background: linear-gradient(135deg, #17a2b8 0%, #0d6efd 100%);
    color: white;
}

.badge-glow {
    animation: badgeGlow 2s ease-in-out infinite;
}

@keyframes badgeGlow {
    0%, 100% { box-shadow: 0 0 5px rgba(25, 135, 84, 0.5); }
    50% { box-shadow: 0 0 15px rgba(25, 135, 84, 0.8); }
}

.empty-state {
    color: rgba(255, 255, 255, 0.6);
}

.empty-state i {
    opacity: 0.5;
}

.refresh-icon {
    animation: none;
    transition: transform 0.3s ease;
}

.refresh-icon.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .friends-counter-group {
        flex-direction: column;
        align-items: flex-end;
        gap: 0.25rem;
    }
    
    .friends-counter-group .badge {
        font-size: 0.7rem;
        padding: 0.3em 0.5em;
    }
    
    .status-filter-bar .btn-group {
        flex-direction: column;
    }
    
    .status-filter-bar .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 0.25rem;
    }
    
    .friend-actions {
        margin-left: 0.5rem;
    }
    
    .friend-actions .btn {
        width: 28px;
        height: 28px;
        margin-bottom: 0.25rem;
    }
    
    .current-game-info,
    .status-message {
        margin-left: 0;
        margin-top: 0.5rem;
    }
}

/* Animation performance optimization */
.friends-status-widget * {
    backface-visibility: hidden;
    -webkit-font-smoothing: antialiased;
}

/* Custom scrollbar for friends list */
.friends-status-container::-webkit-scrollbar {
    width: 6px;
}

.friends-status-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.friends-status-container::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #00d4ff 0%, #8b5cf6 100%);
    border-radius: 3px;
}

.friends-status-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #8b5cf6 0%, #00d4ff 100%);
}
</style>

<!-- Enhanced Professional JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const widgetId = '<?php echo $widget_id; ?>';
    const friendsList = document.getElementById(`friendsStatusList_${widgetId}`);
    const onlineCount = document.getElementById(`onlineFriendsCount_${widgetId}`);
    const playingCount = document.getElementById(`playingFriendsCount_${widgetId}`);
    const lookingCount = document.getElementById(`lookingFriendsCount_${widgetId}`);
    const lastUpdateTime = document.getElementById(`lastUpdateTime_${widgetId}`);
    const refreshBtn = document.getElementById(`refreshStatusBtn_${widgetId}`);
    
    // Initialize widget functionality
    initializeStatusFilters();
    initializeActionButtons();
    initializeAutoRefresh();
    
    /**
     * Initialize status filter functionality
     */
    function initializeStatusFilters() {
        const filterInputs = document.querySelectorAll(`input[name="statusFilter_${widgetId}"]`);
        
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                filterFriendsByStatus(this.value);
                updateFilterAnimation(this);
            });
        });
    }
    
    /**
     * Filter friends by status with smooth animations
     * @param {string} status - Status to filter by
     */
    function filterFriendsByStatus(status) {
        const friendItems = document.querySelectorAll('.friend-filter-item');
        
        friendItems.forEach(item => {
            const itemStatus = item.dataset.status;
            const shouldShow = status === 'all' || 
                             (status === 'online' && ['online', 'playing', 'looking'].includes(itemStatus)) ||
                             itemStatus === status;
            
            if (shouldShow) {
                item.style.display = 'block';
                item.style.opacity = '0';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 50);
            } else {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    item.style.display = 'none';
                }, 300);
            }
        });
        
        // Show empty state if no friends match filter
        setTimeout(() => {
            const visibleItems = Array.from(friendItems).filter(item => 
                window.getComputedStyle(item).display !== 'none'
            );
            
            if (visibleItems.length === 0 && !document.querySelector('.empty-state')) {
                showEmptyFilterState(status);
            } else {
                removeEmptyFilterState();
            }
        }, 350);
    }
    
    /**
     * Update filter button animation
     * @param {HTMLElement} activeInput - The active filter input
     */
    function updateFilterAnimation(activeInput) {
        const label = document.querySelector(`label[for="${activeInput.id}"]`);
        if (label) {
            label.style.transform = 'scale(1.05)';
            setTimeout(() => {
                label.style.transform = 'scale(1)';
            }, 150);
        }
    }
    
    /**
     * Initialize action button functionality
     */
    function initializeActionButtons() {
        // Join game buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.join-game-btn')) {
                handleJoinGame(e.target.closest('.join-game-btn'));
            }
        });
        
        // Invite friend buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.invite-friend-btn')) {
                handleInviteFriend(e.target.closest('.invite-friend-btn'));
            }
        });
        
        // View profile buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-profile-btn')) {
                handleViewProfile(e.target.closest('.view-profile-btn'));
            }
        });
        
        // Refresh button
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                refreshFriendsStatus();
            });
        }
    }
    
    /**
     * Handle join game action with enhanced UX
     * @param {HTMLElement} button - The join game button
     */
    async function handleJoinGame(button) {
        const userId = button.dataset.userId;
        const gameId = button.dataset.gameId;
        const username = button.dataset.username;
        const gameName = button.dataset.gameName;
        
        // Show loading state
        const originalHtml = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        button.disabled = true;
        
        try {
            // Update own status to playing the same game
            const statusResponse = await fetch('update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                },
                body: JSON.stringify({
                    status_type: 'playing',
                    game_id: gameId,
                    status_message: `Speelt met ${username}`
                })
            });
            
            if (!statusResponse.ok) {
                throw new Error('Failed to update status');
            }
            
            // Create notification for friend
            await fetch('create_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                },
                body: JSON.stringify({
                    user_id: userId,
                    type: 'game_join',
                    message: `<?php echo $_SESSION['username']; ?> is je game ${gameName} toegetreden!`
                })
            });
            
            // Show success feedback
            showToast(`Je bent ${gameName} toegetreden met ${username}!`, 'success');
            
            // Update button state
            button.innerHTML = '<i class="bi bi-check-circle text-success"></i>';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');
            
            // Refresh status after short delay
            setTimeout(() => {
                refreshFriendsStatus();
            }, 1500);
            
        } catch (error) {
            console.error('Error joining game:', error);
            showToast('Fout bij het toetreden tot de game', 'error');
            
            // Restore button
            button.innerHTML = originalHtml;
            button.disabled = false;
        }
    }
    
    /**
     * Handle invite friend action
     * @param {HTMLElement} button - The invite button
     */
    async function handleInviteFriend(button) {
        const userId = button.dataset.userId;
        const username = button.dataset.username;
        
        // Show invitation modal or quick invite
        const userConfirmed = confirm(`Wil je ${username} uitnodigen voor een gaming sessie?`);
        
        if (!userConfirmed) return;
        
        // Show loading state
        const originalHtml = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        button.disabled = true;
        
        try {
            // Send invitation notification
            const response = await fetch('create_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                },
                body: JSON.stringify({
                    user_id: userId,
                    type: 'game_invite',
                    message: `<?php echo $_SESSION['username']; ?> nodigt je uit voor een gaming sessie!`
                })
            });
            
            if (!response.ok) {
                throw new Error('Failed to send invitation');
            }
            
            // Show success feedback
            showToast(`Uitnodiging verzonden naar ${username}!`, 'success');
            
            // Update button state
            button.innerHTML = '<i class="bi bi-check-circle text-success"></i>';
            button.classList.remove('btn-outline-success');
            button.classList.add('btn-success');
            
            // Reset button after delay
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-success');
                button.disabled = false;
            }, 3000);
            
        } catch (error) {
            console.error('Error sending invitation:', error);
            showToast('Fout bij het verzenden van uitnodiging', 'error');
            
            // Restore button
            button.innerHTML = originalHtml;
            button.disabled = false;
        }
    }
    
    /**
     * Handle view profile action
     * @param {HTMLElement} button - The view profile button
     */
    function handleViewProfile(button) {
        const userId = button.dataset.userId;
        const username = button.dataset.username;
        
        // For now, show an info message. In a full implementation, this would open a profile modal
        showToast(`Profiel van ${username} - Feature komt binnenkort beschikbaar!`, 'info');
        
        // Add visual feedback
        button.style.transform = 'scale(1.2)';
        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 200);
    }
    
    /**
     * Refresh friends status with enhanced UX
     */
    async function refreshFriendsStatus() {
        const refreshIcon = refreshBtn.querySelector('i');
        refreshIcon.classList.add('spinning');
        refreshBtn.disabled = true;
        
        try {
            const response = await fetch('get_status.php', {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                }
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const data = await response.json();
            
            if (data.success && data.friends_status) {
                updateFriendsDisplay(data.friends_status);
                updateCounters(data.friends_status);
                updateLastUpdateTime();
                showToast('Vrienden status bijgewerkt!', 'success');
            } else {
                throw new Error(data.error || 'Failed to refresh status');
            }
            
        } catch (error) {
            console.error('Error refreshing friends status:', error);
            showToast('Fout bij het bijwerken van vrienden status', 'error');
        } finally {
            refreshIcon.classList.remove('spinning');
            refreshBtn.disabled = false;
        }
    }
    
    /**
     * Update friends display with new data
     * @param {Array} friendsData - Updated friends data
     */
    function updateFriendsDisplay(friendsData) {
        // This would update the friends list with new data
        // Implementation would rebuild the friends list HTML
        console.log('Updating friends display with new data:', friendsData);
        
        // For a complete implementation, this would:
        // 1. Clear current list
        // 2. Rebuild HTML with new data
        // 3. Reattach event listeners
        // 4. Apply current filter
    }
    
    /**
     * Update status counters
     * @param {Array} friendsData - Friends data for counting
     */
    function updateCounters(friendsData) {
        const onlineFriends = friendsData.filter(f => ['online', 'playing', 'looking'].includes(f.status_type));
        const playingFriends = friendsData.filter(f => f.status_type === 'playing');
        const lookingFriends = friendsData.filter(f => f.status_type === 'looking');
        
        if (onlineCount) onlineCount.textContent = `${onlineFriends.length} Online`;
        if (playingCount) playingCount.textContent = `${playingFriends.length} Gaming`;
        if (lookingCount) lookingCount.textContent = `${lookingFriends.length} LFG`;
    }
    
    /**
     * Update last update time
     */
    function updateLastUpdateTime() {
        if (lastUpdateTime) {
            const now = new Date();
            lastUpdateTime.textContent = now.toLocaleTimeString('nl-NL', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }
    }
    
    /**
     * Show empty filter state
     * @param {string} status - Filter status
     */
    function showEmptyFilterState(status) {
        const statusLabels = {
            'online': 'online vrienden',
            'playing': 'spelende vrienden',
            'looking': 'vrienden die zoeken naar een game'
        };
        
        const emptyStateHtml = `
            <div class="empty-filter-state text-center py-4">
                <i class="bi bi-funnel text-muted" style="font-size: 2rem;"></i>
                <h6 class="text-muted mt-2">Geen ${statusLabels[status] || 'vrienden'} gevonden</h6>
                <p class="text-muted small">Probeer een ander filter</p>
            </div>
        `;
        
        friendsList.insertAdjacentHTML('beforeend', emptyStateHtml);
    }
    
    /**
     * Remove empty filter state
     */
    function removeEmptyFilterState() {
        const emptyState = document.querySelector('.empty-filter-state');
        if (emptyState) {
            emptyState.remove();
        }
    }
    
    /**
     * Initialize auto-refresh functionality
     */
    function initializeAutoRefresh() {
        // Auto-refresh every 30 seconds
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                refreshFriendsStatus();
            }
        }, 30000);
        
        // Refresh when page becomes visible
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                refreshFriendsStatus();
            }
        });
    }
    
    /**
     * Show toast notification
     * @param {string} message - Message to show
     * @param {string} type - Type of notification
     */
    function showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toastId = 'toast_' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${getBootstrapType(type)} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${getIconClass(type)} me-2"></i>
                        ${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        // Initialize and show toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: type === 'error' ? 8000 : 5000
        });
        
        toast.show();
        
        // Remove from DOM after hiding
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    
    /**
     * Get Bootstrap color type for notifications
     * @param {string} type - Internal type
     * @returns {string} Bootstrap color class
     */
    function getBootstrapType(type) {
        const typeMap = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        return typeMap[type] || 'info';
    }
    
    /**
     * Get icon class for notification type
     * @param {string} type - Notification type
     * @returns {string} Icon class
     */
    function getIconClass(type) {
        const iconMap = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-exclamation-circle-fill',
            'warning': 'bi-exclamation-triangle-fill',
            'info': 'bi-info-circle-fill'
        };
        return iconMap[type] || 'bi-info-circle-fill';
    }
    
    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>