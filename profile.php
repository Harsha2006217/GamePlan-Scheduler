<?php
// profile.php - Advanced Profile Management
// Author: Harsha Kanaparthi
// Date: 30-09-2025

require_once 'functions.php';
checkSessionTimeout();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$favorites = getFavoriteGames($userId);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_favorite'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $note = $_POST['note'] ?? '';
    
    $error = addFavoriteGame($userId, $title, $description, $note);
    if (!$error) {
        setMessage('success', 'Favorite game added successfully!');
        header("Location: profile.php");
        exit;
    }
}
?>
<?php include 'header.php'; ?>

<div class="container-fluid">
    <?php echo getMessage(); ?>
    
    <!-- Profile Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="advanced-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-user fs-2 text-white"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h2 class="mb-1"><?php echo safeEcho($_SESSION['username']); ?></h2>
                            <p class="text-muted mb-0">Manage your profile and favorite games</p>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-success fs-6">
                                <i class="fas fa-circle me-1"></i>Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Add Favorite Game Form -->
        <div class="col-lg-4 mb-4">
            <div class="advanced-card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Add Favorite Game
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
                            <label for="title" class="form-label fw-semibold">Game Title *</label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   class="form-control form-control-advanced real-time-validate" 
                                   required 
                                   maxlength="100"
                                   placeholder="Enter game title (e.g., Fortnite, Minecraft)">
                            <div class="form-text">Enter the name of your favorite game</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-control form-control-advanced" 
                                      rows="3" 
                                      maxlength="500"
                                      placeholder="Brief description of the game (optional)"></textarea>
                            <div class="form-text">Maximum 500 characters</div>
                        </div>

                        <div class="mb-4">
                            <label for="note" class="form-label fw-semibold">Personal Note</label>
                            <textarea id="note" 
                                      name="note" 
                                      class="form-control form-control-advanced" 
                                      rows="2"
                                      placeholder="Your personal notes about this game (optional)"></textarea>
                            <div class="form-text">Why is this your favorite? What do you enjoy about it?</div>
                        </div>

                        <button type="submit" name="add_favorite" class="btn btn-primary btn-advanced w-100">
                            <i class="fas fa-plus me-2"></i>Add to Favorites
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Favorite Games List -->
        <div class="col-lg-8">
            <div class="advanced-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-heart me-2"></i>Your Favorite Games
                        <span class="badge bg-primary ms-2"><?php echo count($favorites); ?></span>
                    </h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-sort me-1"></i>Sort
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="#">By Title (A-Z)</a></li>
                            <li><a class="dropdown-item" href="#">By Title (Z-A)</a></li>
                            <li><a class="dropdown-item" href="#">Recently Added</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($favorites)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-gamepad display-1 text-muted mb-4"></i>
                            <h4 class="text-muted mb-3">No Favorite Games Yet</h4>
                            <p class="text-muted mb-4">Start by adding your first favorite game using the form on the left.</p>
                            <div class="row justify-content-center">
                                <div class="col-auto">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-arrow-left fs-2 text-primary mb-2"></i>
                                        <small class="text-muted">Use the form to add games</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-advanced table-hover">
                                <thead>
                                    <tr>
                                        <th>Game Title</th>
                                        <th>Description</th>
                                        <th>Personal Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($favorites as $game): ?>
                                        <tr class="fade-in">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-gamepad text-primary me-3 fs-5"></i>
                                                    <div>
                                                        <strong class="d-block"><?php echo safeEcho($game['titel']); ?></strong>
                                                        <small class="text-muted">Added: <?php echo date('M j, Y', strtotime($game['created_at'] ?? 'now')); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo safeEcho($game['description'] ?: 'No description'); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($game['note']): ?>
                                                    <span class="badge bg-info text-dark"><?php echo safeEcho($game['note']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">No note</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit_favorite.php?id=<?php echo $game['user_game_id']; ?>" 
                                                       class="btn btn-warning"
                                                       data-bs-toggle="tooltip"
                                                       title="Edit Game Details">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?type=favorite&id=<?php echo $game['user_game_id']; ?>" 
                                                       class="btn btn-danger advanced-confirm"
                                                       data-confirm-message="Are you sure you want to remove '<?php echo safeEcho($game['titel']); ?>' from your favorites?"
                                                       data-confirm-action="Remove Game">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="advanced-card bg-secondary">
                                    <div class="card-body py-3">
                                        <div class="row align-items-center">
                                            <div class="col">
                                                <small class="text-light">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    You have <?php echo count($favorites); ?> favorite games. 
                                                    <?php if (count($favorites) >= 5): ?>
                                                        Great collection! 🎮
                                                    <?php elseif (count($favorites) >= 2): ?>
                                                        Keep building your gaming profile!
                                                    <?php else: ?>
                                                        Add more games to build your profile.
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="col-auto">
                                                <button class="btn btn-sm btn-outline-light" onclick="exportFavorites()">
                                                    <i class="fas fa-download me-1"></i>Export List
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportFavorites() {
    // Simple export functionality
    const games = <?php echo json_encode(array_column($favorites, 'titel')); ?>;
    const content = "My Favorite Games:\n\n" + games.join('\n');
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'my-favorite-games.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    // Show success message
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 100px; right: 20px; z-index: 1060; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>Favorite games exported successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}
</script>

<?php include 'footer.php'; ?>