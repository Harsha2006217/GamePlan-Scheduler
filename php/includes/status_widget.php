<?php
/**
 * Status widget for displaying and updating user status
 * Include this file in pages where you want to show the status widget
 */

// Get current user status
$current_user_status = getUserStatus($_SESSION['user_id']);
$current_user = getUserById($_SESSION['user_id']);
$all_games = getGames();
?>

<div class="status-widget card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
            <i class="bi bi-circle-fill status-indicator"></i>
            Jouw Status
        </h5>
    </div>
    <div class="card-body">
        <div class="current-status mb-3">
            <div class="d-flex align-items-center mb-2">
                <span class="status-badge me-2"></span>
                <span class="current-status-text">
                    <?php echo ucfirst($current_user_status['status_type'] ?? 'offline'); ?>
                </span>
            </div>
            <?php if ($current_user_status['game_id']): ?>
                <div class="current-game small text-muted">
                    Speelt: <?php echo htmlspecialchars($current_user_status['game_name']); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="status-controls">
            <button type="button" class="btn btn-primary mb-2 w-100" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                <i class="bi bi-pencil-square"></i> Status Bijwerken
            </button>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Status Bijwerken</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="statusUpdateForm">
                    <div class="mb-3">
                        <label class="form-label">Status Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="status_type" id="status_online" value="online" autocomplete="off">
                            <label class="btn btn-outline-primary" for="status_online">
                                <i class="bi bi-circle-fill text-success"></i> Online
                            </label>

                            <input type="radio" class="btn-check" name="status_type" id="status_playing" value="playing" autocomplete="off">
                            <label class="btn btn-outline-primary" for="status_playing">
                                <i class="bi bi-controller"></i> Spelen
                            </label>

                            <input type="radio" class="btn-check" name="status_type" id="status_looking" value="looking" autocomplete="off">
                            <label class="btn btn-outline-primary" for="status_looking">
                                <i class="bi bi-search"></i> Zoeken
                            </label>

                            <input type="radio" class="btn-check" name="status_type" id="status_break" value="break" autocomplete="off">
                            <label class="btn btn-outline-primary" for="status_break">
                                <i class="bi bi-cup-hot"></i> Pauze
                            </label>
                        </div>
                    </div>

                    <div id="gameSelection" class="mb-3 d-none">
                        <label for="game_id" class="form-label">Game</label>
                        <select class="form-select" id="game_id" name="game_id">
                            <option value="">Selecteer game...</option>
                            <?php foreach ($all_games as $game): ?>
                                <option value="<?php echo $game['game_id']; ?>">
                                    <?php echo htmlspecialchars($game['titel']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="status_message" class="form-label">Status Bericht (Optioneel)</label>
                        <input type="text" class="form-control" id="status_message" name="status_message" 
                               maxlength="255" placeholder="Wat ben je aan het doen?">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                <button type="button" class="btn btn-primary" id="updateStatusBtn">Status Bijwerken</button>
            </div>
        </div>
    </div>
</div>

<style>
.status-widget .status-indicator {
    font-size: 0.75em;
    margin-right: 0.5em;
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusForm = document.getElementById('statusUpdateForm');
    const updateBtn = document.getElementById('updateStatusBtn');
    const gameSelection = document.getElementById('gameSelection');
    
    // Initialize status type buttons
    const currentStatus = '<?php echo $current_user_status['status_type'] ?? 'online'; ?>';
    document.getElementById('status_' + currentStatus)?.checked = true;
    
    if (currentStatus === 'playing') {
        gameSelection.classList.remove('d-none');
        document.getElementById('game_id').value = '<?php echo $current_user_status['game_id'] ?? ''; ?>';
    }
    
    // Show/hide game selection based on status type
    statusForm.querySelectorAll('[name="status_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            gameSelection.classList.toggle('d-none', this.value !== 'playing');
            if (this.value === 'playing') {
                document.getElementById('game_id').required = true;
            } else {
                document.getElementById('game_id').required = false;
            }
        });
    });
    
    // Handle status update
    updateBtn.addEventListener('click', async function() {
        const formData = new FormData(statusForm);
        const status_type = formData.get('status_type');
        
        if (!status_type) {
            alert('Selecteer een status type');
            return;
        }
        
        if (status_type === 'playing' && !formData.get('game_id')) {
            alert('Selecteer een game');
            return;
        }
        
        try {
            const response = await fetch('update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                },
                body: JSON.stringify({
                    status_type: status_type,
                    game_id: formData.get('game_id'),
                    status_message: formData.get('status_message')
                })
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                throw new Error(data.error || 'Could not update status');
            }
        } catch (error) {
            alert('Error updating status: ' + error.message);
        }
    });
    
    // Auto-refresh status
    setInterval(async function() {
        try {
            const response = await fetch('get_status.php', {
                headers: {
                    'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                }
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            updateStatusDisplay(data.my_status);
        } catch (error) {
            console.error('Error refreshing status:', error);
        }
    }, 30000); // Update every 30 seconds
});

function updateStatusDisplay(status) {
    const statusBadge = document.querySelector('.status-badge');
    const statusText = document.querySelector('.current-status-text');
    const currentGame = document.querySelector('.current-game');
    
    statusBadge.setAttribute('data-status', status.status_type);
    statusText.textContent = status.status_type.charAt(0).toUpperCase() + status.status_type.slice(1);
    
    if (status.game_id && status.game_name) {
        if (!currentGame) {
            const gameDiv = document.createElement('div');
            gameDiv.className = 'current-game small text-muted';
            gameDiv.textContent = 'Speelt: ' + status.game_name;
            document.querySelector('.current-status').appendChild(gameDiv);
        } else {
            currentGame.textContent = 'Speelt: ' + status.game_name;
        }
    } else if (currentGame) {
        currentGame.remove();
    }
}
</script>