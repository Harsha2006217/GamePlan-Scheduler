<?php
$page_title = "My Profile";
require_once 'header.php';

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $bio = $_POST['bio'] ?? '';

        if (!$gameplan->validateEmail($email)) {
            throw new Exception("Invalid email format");
        }

        $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, 
                  email = :email, bio = :bio WHERE user_id = :user_id";
        
        $stmt = $gameplan->conn->prepare($query);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            
            $success = "Profile updated successfully!";
        } else {
            throw new Exception("Failed to update profile");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle game addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    try {
        $game_title = $_POST['game_title'] ?? '';
        $hours_played = $_POST['hours_played'] ?? 0;
        $skill_level = $_POST['skill_level'] ?? 'Beginner';
        $favorite = isset($_POST['favorite']) ? 1 : 0;

        if (empty($game_title)) {
            throw new Exception("Game title is required");
        }

        // First, check if game exists
        $search_games = $gameplan->searchGames($game_title);
        $game_id = null;

        if (!empty($search_games)) {
            $game_id = $search_games[0]['game_id'];
        } else {
            // Create new game
            $query = "INSERT INTO games (game_title, genre, platform) VALUES (:game_title, :genre, :platform)";
            $stmt = $gameplan->conn->prepare($query);
            $stmt->bindParam(':game_title', $game_title);
            $stmt->bindValue(':genre', 'Other');
            $stmt->bindValue(':platform', 'Multiple');
            $stmt->execute();
            $game_id = $gameplan->conn->lastInsertId();
        }

        // Add game to user's collection
        if ($gameplan->addGameToUser($user_id, $game_id, $hours_played, $skill_level, $favorite)) {
            $success = "Game added to your collection successfully!";
        } else {
            throw new Exception("Failed to add game to collection");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user data
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $gameplan->conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch();

$user_games = $gameplan->getUserGames($user_id);
$popular_games = $gameplan->searchGames('');
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-user me-2 text-primary"></i>My Profile
            </h1>
            <div class="btn-group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGameModal">
                    <i class="fas fa-plus me-1"></i>Add Game
                </button>
            </div>
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
    <!-- Profile Information -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-circle me-2 text-primary"></i>
                    Profile Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="text-center mb-4">
                        <div class="profile-avatar mx-auto mb-3">
                            <i class="fas fa-user fa-4x text-muted"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h5>
                        <p class="text-muted">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="<?php echo htmlspecialchars($user_data['last_name']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                        <div class="form-text">Username cannot be changed</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3" 
                                  placeholder="Tell us about yourself and your gaming preferences..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card shadow mt-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>
                    Gaming Stats
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="stat-number text-primary fw-bold fs-4">
                            <?php echo count($user_games); ?>
                        </div>
                        <div class="stat-label text-muted small">Games</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-number text-success fw-bold fs-4">
                            <?php
                            $total_hours = array_sum(array_column($user_games, 'hours_played'));
                            echo number_format($total_hours);
                            ?>
                        </div>
                        <div class="stat-label text-muted small">Hours Played</div>
                    </div>
                    <div class="col-6">
                        <div class="stat-number text-info fw-bold fs-4">
                            <?php
                            $favorite_count = count(array_filter($user_games, function($game) {
                                return $game['favorite'];
                            }));
                            echo $favorite_count;
                            ?>
                        </div>
                        <div class="stat-label text-muted small">Favorites</div>
                    </div>
                    <div class="col-6">
                        <div class="stat-number text-warning fw-bold fs-4">
                            <?php
                            $expert_games = count(array_filter($user_games, function($game) {
                                return $game['skill_level'] === 'Expert';
                            }));
                            echo $expert_games;
                            ?>
                        </div>
                        <div class="stat-label text-muted small">Expert Level</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Game Collection -->
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-gamepad me-2 text-primary"></i>
                    My Game Collection
                </h5>
                <span class="badge bg-primary"><?php echo count($user_games); ?> games</span>
            </div>
            <div class="card-body">
                <?php if (empty($user_games)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-gamepad fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No games in your collection</h5>
                        <p class="text-muted">Start by adding your favorite games to build your gaming profile!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGameModal">
                            <i class="fas fa-plus me-1"></i>Add Your First Game
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($user_games as $game): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="game-card card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($game['game_title']); ?></h6>
                                            <?php if ($game['favorite']): ?>
                                                <i class="fas fa-star text-warning" title="Favorite"></i>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($game['genre'])): ?>
                                            <span class="badge bg-light text-dark small mb-2"><?php echo htmlspecialchars($game['genre']); ?></span>
                                        <?php endif; ?>
                                        
                                        <div class="game-stats">
                                            <div class="small text-muted mb-1">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo number_format($game['hours_played'], 1); ?> hours
                                            </div>
                                            <div class="small text-muted">
                                                <i class="fas fa-trophy me-1"></i>
                                                <?php echo htmlspecialchars($game['skill_level']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <div class="btn-group w-100">
                                                <button class="btn btn-outline-primary btn-sm" 
                                                        onclick="editGame(<?php echo $game['user_game_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="delete.php?type=usergame&id=<?php echo $game['user_game_id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm"
                                                   onclick="return confirm('Remove this game from your collection?')">
                                                    <i class="fas fa-trash"></i>
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

        <!-- Popular Games Suggestion -->
        <?php if (!empty($popular_games)): ?>
        <div class="card shadow mt-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-fire me-2 text-danger"></i>
                    Popular Games You Might Like
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $user_game_ids = array_column($user_games, 'game_id');
                    $suggestions = array_filter($popular_games, function($game) use ($user_game_ids) {
                        return !in_array($game['game_id'], $user_game_ids);
                    });
                    $suggestions = array_slice($suggestions, 0, 6);
                    ?>
                    
                    <?php foreach ($suggestions as $game): ?>
                        <div class="col-md-4 col-lg-2 mb-3">
                            <div class="suggestion-card text-center">
                                <div class="game-icon bg-light rounded p-3 mb-2">
                                    <i class="fas fa-gamepad fa-2x text-primary"></i>
                                </div>
                                <h6 class="small mb-1"><?php echo htmlspecialchars($game['game_title']); ?></h6>
                                <button class="btn btn-sm btn-outline-primary w-100" 
                                        onclick="quickAddGame(<?php echo $game['game_id']; ?>, '<?php echo addslashes($game['game_title']); ?>')">
                                    <i class="fas fa-plus me-1"></i>Add
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Game Modal -->
<div class="modal fade" id="addGameModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add Game to Collection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="add_game" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="game_title" class="form-label">Game Title *</label>
                        <input type="text" class="form-control" id="game_title" name="game_title" 
                               placeholder="Enter game title..." required>
                        <div class="form-text">Start typing to search existing games</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hours_played" class="form-label">Hours Played</label>
                                <input type="number" class="form-control" id="hours_played" name="hours_played" 
                                       value="0" min="0" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="skill_level" class="form-label">Skill Level</label>
                                <select class="form-select" id="skill_level" name="skill_level">
                                    <option value="Beginner">Beginner</option>
                                    <option value="Intermediate">Intermediate</option>
                                    <option value="Advanced">Advanced</option>
                                    <option value="Expert">Expert</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="favorite" name="favorite" value="1">
                        <label class="form-check-label" for="favorite">Mark as favorite</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Game
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function quickAddGame(gameId, gameTitle) {
    document.getElementById('game_title').value = gameTitle;
    var modal = new bootstrap.Modal(document.getElementById('addGameModal'));
    modal.show();
}

function editGame(userGameId) {
    // Implementation for editing game details
    alert('Edit feature for game ID: ' + userGameId + ' - This would open an edit modal in a full implementation.');
}

// Game search autocomplete
document.getElementById('game_title').addEventListener('input', function() {
    // In a full implementation, this would fetch search results from the server
    console.log('Searching for:', this.value);
});
</script>

<?php require_once 'footer.php'; ?>