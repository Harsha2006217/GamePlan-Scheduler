<?php
/**
 * GamePlan Scheduler - Enhanced Status Widget Implementation
 * Advanced Real-Time User Status Management with Professional Gaming Interface
 * Author: Harsha Kanaparthi
 * Version: 2.0 Professional Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Status Widget
 */

// Ensure user is logged in before displaying widget
if (!isset($_SESSION['user_id'])) {
    return;
}

// Enhanced error handling and database connection check
try {
    // Get current user status with advanced error handling
    $current_user_status = getUserStatus($_SESSION['user_id']);
    $current_user = getUserById($_SESSION['user_id']);
    $all_games = getGames();
    
    // Validate data integrity
    if (!$current_user) {
        throw new Exception('User data not found');
    }
    
    if (!is_array($all_games)) {
        $all_games = [];
    }
    
} catch (Exception $e) {
    error_log('Status Widget Error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">Status widget kon niet worden geladen.</div>';
    return;
}

// Generate unique widget ID for multiple instances
$widget_id = 'status_widget_' . uniqid();
$modal_id = 'updateStatusModal_' . uniqid();

// Enhanced status mapping with gaming terminology
$status_labels = [
    'offline' => ['label' => 'Offline', 'icon' => 'bi-circle', 'color' => '#dc3545'],
    'online' => ['label' => 'Online', 'icon' => 'bi-circle-fill', 'color' => '#198754'],
    'playing' => ['label' => 'Gaming', 'icon' => 'bi-controller', 'color' => '#0d6efd'],
    'looking' => ['label' => 'LFG', 'icon' => 'bi-search', 'color' => '#ffc107'],
    'break' => ['label' => 'AFK', 'icon' => 'bi-cup-hot', 'color' => '#6c757d']
];

$current_status = $current_user_status['status_type'] ?? 'offline';
$current_status_info = $status_labels[$current_status] ?? $status_labels['offline'];
?>

<!-- Enhanced GamePlan Scheduler Status Widget -->
<div id="<?php echo htmlspecialchars($widget_id); ?>" class="gameplan-status-widget card shadow-lg mb-4">
    <div class="card-header bg-gradient-primary text-white position-relative">
        <div class="header-glow"></div>
        <h5 class="card-title mb-0 d-flex align-items-center">
            <i class="<?php echo htmlspecialchars($current_status_info['icon']); ?> status-indicator me-2" 
               style="color: <?php echo htmlspecialchars($current_status_info['color']); ?>; text-shadow: 0 0 10px <?php echo htmlspecialchars($current_status_info['color']); ?>;"></i>
            <span class="status-title">Gaming Status</span>
            <div class="status-pulse"></div>
        </h5>
        <small class="status-subtitle">
            <i class="bi bi-person-fill me-1"></i>
            <?php echo htmlspecialchars($current_user['username'] ?? 'Gamer'); ?>
        </small>
    </div>
    
    <div class="card-body p-4">
        <!-- Current Status Display -->
        <div class="current-status-display mb-4">
            <div class="status-main d-flex align-items-center justify-content-between mb-3">
                <div class="status-info">
                    <div class="d-flex align-items-center mb-2">
                        <span class="status-badge me-3" data-status="<?php echo htmlspecialchars($current_status); ?>"></span>
                        <div>
                            <span class="current-status-text h6 mb-0">
                                <?php echo htmlspecialchars($current_status_info['label']); ?>
                            </span>
                            <div class="status-timestamp small text-muted">
                                <i class="bi bi-clock me-1"></i>
                                Laatst actief: <span id="last-activity-time">
                                    <?php 
                                    $last_activity = $current_user['last_activity'] ?? date('Y-m-d H:i:s');
                                    echo htmlspecialchars(date('H:i', strtotime($last_activity))); 
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($current_user_status['game_id'] && isset($current_user_status['game_name'])): ?>
                    <div class="current-game">
                        <div class="game-info p-3 rounded bg-dark bg-opacity-25">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-controller text-primary me-2"></i>
                                <div>
                                    <strong class="text-primary">Nu aan het spelen:</strong><br>
                                    <span class="game-title"><?php echo htmlspecialchars($current_user_status['game_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($current_user_status['status_message'])): ?>
                    <div class="status-message mt-2">
                        <div class="message-bubble p-2 rounded bg-info bg-opacity-10">
                            <i class="bi bi-chat-quote text-info me-1"></i>
                            <em>"<?php echo htmlspecialchars($current_user_status['status_message']); ?>"</em>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="status-actions">
                    <div class="activity-indicator">
                        <div class="pulse-ring"></div>
                        <div class="pulse-dot"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Controls -->
        <div class="status-controls">
            <button type="button" 
                    class="btn btn-primary btn-lg w-100 status-update-btn" 
                    data-bs-toggle="modal" 
                    data-bs-target="#<?php echo htmlspecialchars($modal_id); ?>">
                <i class="bi bi-pencil-square me-2"></i>
                Status Bijwerken
                <div class="btn-glow"></div>
            </button>
            
            <!-- Quick Status Buttons -->
            <div class="quick-status-grid mt-3">
                <div class="row g-2">
                    <div class="col-6">
                        <button class="btn btn-outline-success btn-sm w-100 quick-status-btn" 
                                data-status="online" 
                                data-game="">
                            <i class="bi bi-circle-fill me-1"></i>Online
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-warning btn-sm w-100 quick-status-btn" 
                                data-status="looking" 
                                data-game="">
                            <i class="bi bi-search me-1"></i>LFG
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gaming Statistics -->
        <div class="gaming-stats mt-4 pt-3 border-top">
            <div class="row text-center">
                <div class="col-4">
                    <div class="stat-item">
                        <div class="stat-number text-primary">
                            <?php echo count($all_games); ?>
                        </div>
                        <div class="stat-label">Games</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-item">
                        <div class="stat-number text-success" id="online-time">
                            <?php 
                            $online_minutes = max(0, (time() - strtotime($current_user['last_activity'] ?? 'now')) / 60);
                            echo $online_minutes > 60 ? round($online_minutes/60, 1) . 'h' : round($online_minutes) . 'm';
                            ?>
                        </div>
                        <div class="stat-label">Online</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-item">
                        <div class="stat-number text-info">
                            <?php echo $current_status === 'playing' ? '1' : '0'; ?>
                        </div>
                        <div class="stat-label">Sessions</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Status Update Modal -->
<div class="modal fade gameplan-modal" id="<?php echo htmlspecialchars($modal_id); ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="bi bi-gear-fill text-primary me-2"></i>
                    Gaming Status Bijwerken
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Sluiten"></button>
            </div>
            
            <div class="modal-body p-4">
                <form id="statusUpdateForm_<?php echo htmlspecialchars($widget_id); ?>" novalidate>
                    <!-- Status Type Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">
                            <i class="bi bi-lightning-fill me-1"></i>
                            Status Type
                        </label>
                        <div class="status-type-grid">
                            <div class="row g-3">
                                <?php foreach ($status_labels as $status_key => $status_data): ?>
                                <div class="col-6">
                                    <input type="radio" 
                                           class="btn-check" 
                                           name="status_type" 
                                           id="status_<?php echo htmlspecialchars($status_key . '_' . $widget_id); ?>" 
                                           value="<?php echo htmlspecialchars($status_key); ?>" 
                                           autocomplete="off"
                                           <?php echo $current_status === $status_key ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary w-100 p-3 status-option" 
                                           for="status_<?php echo htmlspecialchars($status_key . '_' . $widget_id); ?>">
                                        <i class="<?php echo htmlspecialchars($status_data['icon']); ?> d-block mb-2" 
                                           style="font-size: 1.5rem; color: <?php echo htmlspecialchars($status_data['color']); ?>;"></i>
                                        <span class="fw-bold"><?php echo htmlspecialchars($status_data['label']); ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Game Selection (shown when playing) -->
                    <div id="gameSelection_<?php echo htmlspecialchars($widget_id); ?>" 
                         class="mb-4 <?php echo $current_status !== 'playing' ? 'd-none' : ''; ?>">
                        <label for="game_id_<?php echo htmlspecialchars($widget_id); ?>" class="form-label fw-bold text-success">
                            <i class="bi bi-controller me-1"></i>
                            Welke Game Speel Je?
                        </label>
                        <select class="form-select form-select-lg bg-dark text-white border-success" 
                                id="game_id_<?php echo htmlspecialchars($widget_id); ?>" 
                                name="game_id">
                            <option value="">Selecteer een game...</option>
                            <?php foreach ($all_games as $game): ?>
                                <option value="<?php echo htmlspecialchars($game['game_id']); ?>"
                                        <?php echo ($current_user_status['game_id'] ?? '') == $game['game_id'] ? 'selected' : ''; ?>>
                                    🎮 <?php echo htmlspecialchars($game['titel']); ?>
                                    <?php if (!empty($game['genre'])): ?>
                                        <small>(<?php echo htmlspecialchars($game['genre']); ?>)</small>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Kies je huidige game om vrienden te laten weten wat je speelt
                        </div>
                    </div>

                    <!-- Custom Status Message -->
                    <div class="mb-4">
                        <label for="status_message_<?php echo htmlspecialchars($widget_id); ?>" class="form-label fw-bold text-warning">
                            <i class="bi bi-chat-dots me-1"></i>
                            Status Bericht (Optioneel)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-warning text-warning">
                                <i class="bi bi-quote"></i>
                            </span>
                            <input type="text" 
                                   class="form-control form-control-lg bg-dark text-white border-warning" 
                                   id="status_message_<?php echo htmlspecialchars($widget_id); ?>" 
                                   name="status_message" 
                                   maxlength="255" 
                                   placeholder="Bijvoorbeeld: 'Zoek team voor ranked!' of 'Nieuwe game aan het proberen'"
                                   value="<?php echo htmlspecialchars($current_user_status['status_message'] ?? ''); ?>">
                        </div>
                        <div class="form-text text-muted">
                            <i class="bi bi-lightbulb me-1"></i>
                            Laat je vrienden weten wat je van plan bent!
                        </div>
                    </div>
                    
                    <!-- Preview Section -->
                    <div class="status-preview p-3 rounded bg-secondary bg-opacity-25 border border-secondary">
                        <h6 class="text-primary mb-2">
                            <i class="bi bi-eye me-1"></i>
                            Voorbeeld Status
                        </h6>
                        <div id="status-preview-content">
                            <div class="d-flex align-items-center">
                                <span class="status-badge me-2" data-status="<?php echo htmlspecialchars($current_status); ?>"></span>
                                <span class="preview-text"><?php echo htmlspecialchars($current_status_info['label']); ?></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>
                    Annuleren
                </button>
                <button type="button" 
                        class="btn btn-primary btn-lg" 
                        id="updateStatusBtn_<?php echo htmlspecialchars($widget_id); ?>">
                    <i class="bi bi-check-circle me-1"></i>
                    Status Bijwerken
                    <div class="btn-spinner d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced CSS Styling -->
<style>
/* GamePlan Scheduler Status Widget Professional Styling */
.gameplan-status-widget {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border: 2px solid #00d4ff;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
    overflow: hidden;
    position: relative;
}

.gameplan-status-widget .card-header {
    background: linear-gradient(90deg, #000000 0%, #1a1a1a 100%);
    border-bottom: 2px solid #00d4ff;
    position: relative;
    overflow: hidden;
}

.header-glow {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.2), transparent);
    animation: headerGlow 3s ease-in-out infinite;
}

@keyframes headerGlow {
    0%, 100% { left: -100%; }
    50% { left: 100%; }
}

.status-title {
    font-family: 'Orbitron', 'Courier New', monospace;
    font-weight: 600;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
}

.status-subtitle {
    opacity: 0.8;
    font-size: 0.85rem;
}

.status-indicator {
    font-size: 1.2rem;
    animation: statusPulse 2s ease-in-out infinite;
}

@keyframes statusPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.status-pulse {
    position: absolute;
    right: 15px;
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

.status-badge {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: inline-block;
    border: 2px solid rgba(255, 255, 255, 0.3);
    position: relative;
}

.status-badge[data-status="online"] { 
    background: #198754; 
    box-shadow: 0 0 10px #198754;
}
.status-badge[data-status="playing"] { 
    background: #0d6efd; 
    box-shadow: 0 0 10px #0d6efd;
}
.status-badge[data-status="looking"] { 
    background: #ffc107; 
    box-shadow: 0 0 10px #ffc107;
}
.status-badge[data-status="break"] { 
    background: #6c757d; 
    box-shadow: 0 0 10px #6c757d;
}
.status-badge[data-status="offline"] { 
    background: #dc3545; 
    box-shadow: 0 0 10px #dc3545;
}

.current-status-text {
    font-weight: 600;
    color: #00d4ff;
    text-shadow: 0 0 5px rgba(0, 212, 255, 0.3);
}

.game-info {
    border-left: 4px solid #00d4ff;
    transition: all 0.3s ease;
}

.game-info:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0, 212, 255, 0.2);
}

.status-update-btn {
    background: linear-gradient(135deg, #00d4ff 0%, #0a58ca 100%);
    border: none;
    position: relative;
    overflow: hidden;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.status-update-btn:hover {
    background: linear-gradient(135deg, #0a58ca 0%, #084298 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
}

.btn-glow {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.status-update-btn:hover .btn-glow {
    left: 100%;
}

.quick-status-btn {
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.quick-status-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.gaming-stats {
    background: rgba(0, 212, 255, 0.05);
    border-radius: 10px;
    padding: 15px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    display: block;
}

.stat-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.gameplan-modal .modal-content {
    border: 2px solid #00d4ff;
    box-shadow: 0 20px 50px rgba(0, 212, 255, 0.3);
}

.status-type-grid .status-option {
    transition: all 0.3s ease;
    border: 2px solid rgba(0, 212, 255, 0.3);
}

.status-type-grid .btn-check:checked + .status-option {
    background: linear-gradient(135deg, #00d4ff 0%, #0a58ca 100%);
    border-color: #00d4ff;
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
}

.activity-indicator {
    position: relative;
    width: 40px;
    height: 40px;
}

.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 30px;
    height: 30px;
    border: 3px solid #00d4ff;
    border-radius: 50%;
    animation: pulsering 1.5s ease-out infinite;
}

.pulse-dot {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: #00d4ff;
    border-radius: 50%;
}

@keyframes pulsering {
    0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
}

.status-preview {
    background: rgba(0, 212, 255, 0.1);
    border: 1px solid rgba(0, 212, 255, 0.3);
}

.message-bubble {
    border-left: 4px solid #17a2b8;
    background: rgba(23, 162, 184, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .gameplan-status-widget .card-body {
        padding: 1rem;
    }
    
    .status-type-grid .col-6 {
        margin-bottom: 0.5rem;
    }
    
    .gaming-stats .col-4 {
        margin-bottom: 1rem;
    }
    
    .stat-number {
        font-size: 1.25rem;
    }
}

/* Loading States */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Success Animation */
.success-flash {
    animation: successFlash 0.6s ease-in-out;
}

@keyframes successFlash {
    0%, 100% { background-color: transparent; }
    50% { background-color: rgba(25, 135, 84, 0.2); }
}
</style>

<!-- Enhanced JavaScript Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const widgetId = '<?php echo $widget_id; ?>';
    const modalId = '<?php echo $modal_id; ?>';
    const statusForm = document.getElementById(`statusUpdateForm_${widgetId}`);
    const updateBtn = document.getElementById(`updateStatusBtn_${widgetId}`);
    const gameSelection = document.getElementById(`gameSelection_${widgetId}`);
    
    // Initialize current status
    const currentStatus = '<?php echo addslashes($current_status); ?>';
    const currentGameId = '<?php echo addslashes($current_user_status['game_id'] ?? ''); ?>';
    
    // Set initial form state
    if (currentStatus) {
        const statusRadio = document.getElementById(`status_${currentStatus}_${widgetId}`);
        if (statusRadio) {
            statusRadio.checked = true;
            updateGameSelectionVisibility(currentStatus);
        }
    }
    
    if (currentGameId && gameSelection) {
        const gameSelect = document.getElementById(`game_id_${widgetId}`);
        if (gameSelect) {
            gameSelect.value = currentGameId;
        }
    }
    
    // Enhanced status type change handler
    if (statusForm) {
        statusForm.querySelectorAll('[name="status_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const selectedStatus = this.value;
                updateGameSelectionVisibility(selectedStatus);
                updateStatusPreview();
                
                // Add visual feedback
                this.closest('.status-option').classList.add('status-selected');
                setTimeout(() => {
                    this.closest('.status-option').classList.remove('status-selected');
                }, 300);
            });
        });
    }
    
    // Game selection change handler
    const gameSelect = document.getElementById(`game_id_${widgetId}`);
    if (gameSelect) {
        gameSelect.addEventListener('change', updateStatusPreview);
    }
    
    // Status message input handler
    const statusMessageInput = document.getElementById(`status_message_${widgetId}`);
    if (statusMessageInput) {
        statusMessageInput.addEventListener('input', updateStatusPreview);
    }
    
    // Enhanced status update handler
    if (updateBtn) {
        updateBtn.addEventListener('click', async function() {
            await handleStatusUpdate();
        });
    }
    
    // Quick status buttons
    document.querySelectorAll('.quick-status-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const status = this.dataset.status;
            const game = this.dataset.game || '';
            
            // Add loading state
            this.disabled = true;
            this.innerHTML = '<span class="loading-spinner me-1"></span>Updating...';
            
            try {
                await updateUserStatus(status, game, '');
                showStatusUpdateSuccess();
                await refreshStatusDisplay();
            } catch (error) {
                showStatusUpdateError(error.message);
            } finally {
                // Restore button
                this.disabled = false;
                this.innerHTML = this.dataset.originalText || this.innerHTML;
            }
        });
    });
    
    // Functions
    function updateGameSelectionVisibility(statusType) {
        if (!gameSelection) return;
        
        const gameSelect = document.getElementById(`game_id_${widgetId}`);
        
        if (statusType === 'playing') {
            gameSelection.classList.remove('d-none');
            if (gameSelect) {
                gameSelect.required = true;
                gameSelect.focus();
            }
        } else {
            gameSelection.classList.add('d-none');
            if (gameSelect) {
                gameSelect.required = false;
                gameSelect.value = '';
            }
        }
    }
    
    function updateStatusPreview() {
        const previewContent = document.getElementById('status-preview-content');
        if (!previewContent) return;
        
        const selectedStatus = statusForm.querySelector('[name="status_type"]:checked');
        const gameSelect = document.getElementById(`game_id_${widgetId}`);
        const messageInput = document.getElementById(`status_message_${widgetId}`);
        
        if (!selectedStatus) return;
        
        const statusLabels = <?php echo json_encode($status_labels); ?>;
        const statusInfo = statusLabels[selectedStatus.value] || statusLabels.offline;
        
        let previewHtml = `
            <div class="d-flex align-items-center mb-2">
                <span class="status-badge me-2" data-status="${selectedStatus.value}"></span>
                <strong>${statusInfo.label}</strong>
            </div>
        `;
        
        if (selectedStatus.value === 'playing' && gameSelect && gameSelect.value) {
            const gameOption = gameSelect.querySelector(`option[value="${gameSelect.value}"]`);
            if (gameOption) {
                previewHtml += `
                    <div class="text-muted small">
                        <i class="bi bi-controller me-1"></i>
                        Speelt: ${gameOption.textContent.replace('🎮 ', '')}
                    </div>
                `;
            }
        }
        
        if (messageInput && messageInput.value.trim()) {
            previewHtml += `
                <div class="text-info small mt-1">
                    <i class="bi bi-chat-quote me-1"></i>
                    "${messageInput.value.trim()}"
                </div>
            `;
        }
        
        previewContent.innerHTML = previewHtml;
    }
    
    async function handleStatusUpdate() {
        const formData = new FormData(statusForm);
        const statusType = formData.get('status_type');
        const gameId = formData.get('game_id');
        const statusMessage = formData.get('status_message');
        
        // Enhanced validation
        if (!statusType) {
            showValidationError('Selecteer een status type');
            return;
        }
        
        if (statusType === 'playing' && !gameId) {
            showValidationError('Selecteer een game wanneer je aan het spelen bent');
            const gameSelect = document.getElementById(`game_id_${widgetId}`);
            if (gameSelect) {
                gameSelect.focus();
                gameSelect.classList.add('is-invalid');
            }
            return;
        }
        
        // Show loading state
        updateBtn.disabled = true;
        const spinner = updateBtn.querySelector('.btn-spinner');
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Status Bijwerken...';
        
        try {
            await updateUserStatus(statusType, gameId || '', statusMessage || '');
            
            // Success feedback
            showStatusUpdateSuccess();
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
            if (modal) {
                modal.hide();
            }
            
            // Refresh display
            await refreshStatusDisplay();
            
        } catch (error) {
            showStatusUpdateError(error.message);
        } finally {
            // Restore button
            updateBtn.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }
            updateBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Status Bijwerken';
        }
    }
    
    async function updateUserStatus(statusType, gameId, statusMessage) {
        const response = await fetch('update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
            },
            body: JSON.stringify({
                status_type: statusType,
                game_id: gameId,
                status_message: statusMessage
            })
        });
        
        if (!response.ok) {
            throw new Error(`Network error: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Status update failed');
        }
        
        return data;
    }
    
    async function refreshStatusDisplay() {
        try {
            const response = await fetch('get_status.php', {
                headers: {
                    'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                }
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            if (data.success && data.my_status) {
                updateStatusDisplayElements(data.my_status);
            }
        } catch (error) {
            console.error('Error refreshing status:', error);
        }
    }
    
    function updateStatusDisplayElements(status) {
        // Update status badge
        const statusBadge = document.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.setAttribute('data-status', status.status_type);
        }
        
        // Update status text
        const statusText = document.querySelector('.current-status-text');
        if (statusText) {
            const statusLabels = <?php echo json_encode($status_labels); ?>;
            const statusInfo = statusLabels[status.status_type] || statusLabels.offline;
            statusText.textContent = statusInfo.label;
        }
        
        // Update game info
        const gameInfo = document.querySelector('.current-game');
        if (status.status_type === 'playing' && status.game_name) {
            if (!gameInfo) {
                // Create game info element
                const gameDiv = document.createElement('div');
                gameDiv.className = 'current-game';
                gameDiv.innerHTML = `
                    <div class="game-info p-3 rounded bg-dark bg-opacity-25">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-controller text-primary me-2"></i>
                            <div>
                                <strong class="text-primary">Nu aan het spelen:</strong><br>
                                <span class="game-title">${escapeHtml(status.game_name)}</span>
                            </div>
                        </div>
                    </div>
                `;
                document.querySelector('.status-info').appendChild(gameDiv);
            } else {
                const gameTitle = gameInfo.querySelector('.game-title');
                if (gameTitle) {
                    gameTitle.textContent = status.game_name;
                }
            }
        } else if (gameInfo) {
            gameInfo.remove();
        }
        
        // Update status message
        const statusMessageDiv = document.querySelector('.status-message');
        if (status.status_message && status.status_message.trim()) {
            if (!statusMessageDiv) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'status-message mt-2';
                messageDiv.innerHTML = `
                    <div class="message-bubble p-2 rounded bg-info bg-opacity-10">
                        <i class="bi bi-chat-quote text-info me-1"></i>
                        <em>"${escapeHtml(status.status_message)}"</em>
                    </div>
                `;
                document.querySelector('.status-info').appendChild(messageDiv);
            } else {
                const messageText = statusMessageDiv.querySelector('em');
                if (messageText) {
                    messageText.textContent = `"${status.status_message}"`;
                }
            }
        } else if (statusMessageDiv) {
            statusMessageDiv.remove();
        }
        
        // Update last activity
        const lastActivityTime = document.getElementById('last-activity-time');
        if (lastActivityTime && status.last_activity) {
            const activityDate = new Date(status.last_activity);
            lastActivityTime.textContent = activityDate.toLocaleTimeString('nl-NL', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }
    }
    
    function showStatusUpdateSuccess() {
        const widget = document.getElementById(widgetId);
        if (widget) {
            widget.classList.add('success-flash');
            setTimeout(() => {
                widget.classList.remove('success-flash');
            }, 600);
        }
        
        // Show toast notification
        showToast('Status succesvol bijgewerkt!', 'success');
    }
    
    function showStatusUpdateError(message) {
        showToast(`Fout bij bijwerken status: ${message}`, 'error');
    }
    
    function showValidationError(message) {
        showToast(message, 'warning');
    }
    
    function showToast(message, type) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'success'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>
                    ${escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        // Add to page
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.appendChild(toast);
        
        // Show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove after hiding
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
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
    
    // Auto-refresh status every 30 seconds
    setInterval(async function() {
        await refreshStatusDisplay();
    }, 30000);
    
    // Initialize status preview
    updateStatusPreview();
});
</script>