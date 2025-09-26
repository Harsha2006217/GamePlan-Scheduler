<?php
/**
 * Advanced Profile Management System
 * GamePlan Scheduler - Professional Gaming Profile Manager
 * 
 * This module provides comprehensive profile management functionality with
 * favorite games selection, statistics, and advanced validation.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

require 'functions.php';

// Advanced security check with session validation
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get comprehensive profile data with error handling
try {
    $profile = getProfile($user_id);
    $games = getGames();
    $favorite_games = getFavoriteGames($user_id);
    
    // Get additional statistics
    $user_stats = getUserStats($user_id);
    
} catch (Exception $e) {
    error_log("Error loading profile data: " . $e->getMessage());
    $profile = ['username' => $_SESSION['username'] ?? 'Gebruiker'];
    $games = [];
    $favorite_games = [];
    $user_stats = ['friend_count' => 0, 'schedule_count' => 0, 'event_count' => 0];
}

$message = '';
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Enhanced validation - Fix #1001 (spaces validation)
    $selected_games = $_POST['favorite_games'] ?? [];
    
    // Advanced validation with proper error handling
    if (empty($selected_games)) {
        $message = '<div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i>Selecteer minstens één favoriete game om je profiel compleet te maken.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        // Validate all selected games
        $valid_games = [];
        foreach ($selected_games as $game_id) {
            if (is_numeric($game_id) && $game_id > 0) {
                // Verify game exists in database
                global $pdo;
                $stmt = $pdo->prepare("SELECT game_id FROM Games WHERE game_id = ?");
                $stmt->execute([$game_id]);
                if ($stmt->fetch()) {
                    $valid_games[] = $game_id;
                }
            }
        }
        
        if (empty($valid_games)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-times-circle me-2"></i>Ongeldige game selecties. Probeer opnieuw.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Remove old favorites with logging
                $stmt = $pdo->prepare("DELETE FROM UserGames WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Add new ones with comprehensive logging
                $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id, added_at, status) VALUES (?, ?, NOW(), 'active')");
                $games_added = 0;
                
                foreach ($valid_games as $game_id) {
                    $stmt->execute([$user_id, $game_id]);
                    $games_added++;
                }
                
                // Log the activity
                logUserActivity($user_id, 'profile_update', 'favorite_games', [
                    'games_count' => $games_added,
                    'game_ids' => implode(',', $valid_games)
                ]);
                
                $pdo->commit();
                
                $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Je favoriete games zijn succesvol opgeslagen! (' . $games_added . ' games toegevoegd)<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                
                // Refresh favorite games list
                $favorite_games = getFavoriteGames($user_id);
                
                // Update user stats
                $user_stats = getUserStats($user_id);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Profile update error for user {$user_id}: " . $e->getMessage());
                $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i>Er is een fout opgetreden bij het opslaan. Probeer het later opnieuw.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        }
    }
}

// Get current favorite game IDs for checkbox selection
$current_favorites = array_column($favorite_games, 'game_id');

// Calculate profile completion percentage
$profile_completion = calculateProfileCompletion($user_id);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiel - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <div class="container mt-4">
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

        <div class="row">
            <!-- Enhanced Profile Info -->
            <div class="col-md-4">
                <div class="card bg-secondary text-light">
                    <div class="card-header bg-primary text-white text-center">
                        <div class="position-relative">
                            <i class="fas fa-user-circle fa-4x mb-3"></i>
                            <div class="position-absolute top-0 end-0">
                                <span class="badge bg-success rounded-pill">
                                    <i class="fas fa-circle"></i>
                                </span>
                            </div>
                        </div>
                        <h4><?php echo htmlspecialchars($profile['username']); ?></h4>
                        <small class="opacity-75">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Lid sinds <?php echo date('M Y', strtotime($profile['created_at'] ?? 'now')); ?>
                        </small>
                    </div>
                    <div class="card-body">
                        <!-- Profile Completion -->
                        <div class="mb-4">
                            <h6 class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-chart-pie me-2 text-info"></i>Profiel Volledigheid</span>
                                <span class="badge bg-info"><?php echo $profile_completion; ?>%</span>
                            </h6>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: <?php echo $profile_completion; ?>%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <?php if ($profile_completion < 100): ?>
                                    <i class="fas fa-lightbulb me-1"></i>Voeg meer games toe om je profiel te verbeteren
                                <?php else: ?>
                                    <i class="fas fa-star me-1"></i>Je profiel is compleet!
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <!-- Enhanced Statistics -->
                        <h6><i class="fas fa-chart-bar me-2 text-warning"></i>Gaming Statistieken</h6>
                        <div class="row text-center g-3">
                            <div class="col-6">
                                <div class="card bg-dark border-primary">
                                    <div class="card-body p-2">
                                        <h4 class="text-primary mb-1"><?php echo $user_stats['friend_count'] ?? 0; ?></h4>
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i>Vrienden
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-dark border-success">
                                    <div class="card-body p-2">
                                        <h4 class="text-success mb-1"><?php echo count($favorite_games); ?></h4>
                                        <small class="text-muted">
                                            <i class="fas fa-gamepad me-1"></i>Games
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-dark border-info">
                                    <div class="card-body p-2">
                                        <h4 class="text-info mb-1"><?php echo $user_stats['schedule_count'] ?? 0; ?></h4>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>Schema's
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-dark border-warning">
                                    <div class="card-body p-2">
                                        <h4 class="text-warning mb-1"><?php echo $user_stats['event_count'] ?? 0; ?></h4>
                                        <small class="text-muted">
                                            <i class="fas fa-trophy me-1"></i>Events
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Enhanced Account Info -->
                        <h6><i class="fas fa-info-circle me-2 text-info"></i>Account Details</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-dark">
                                <tbody>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td class="text-end">
                                            <small><?php echo htmlspecialchars($profile['email'] ?? 'Niet beschikbaar'); ?></small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td class="text-end">
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                <?php echo ucfirst($profile['account_status'] ?? 'active'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php if (isset($profile['last_activity'])): ?>
                                        <tr>
                                            <td><strong>Laatst actief:</strong></td>
                                            <td class="text-end">
                                                <small class="text-muted">
                                                    <?php echo date('j M Y H:i', strtotime($profile['last_activity'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Profile Actions -->
                        <div class="d-grid gap-2 mt-3">
                            <button class="btn btn-outline-primary btn-sm" onclick="shareProfile()">
                                <i class="fas fa-share-alt me-2"></i>Profiel Delen
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="exportData()">
                                <i class="fas fa-download me-2"></i>Data Exporteren
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Gaming Activity Feed -->
                <div class="card bg-secondary text-light mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Recente Activiteit
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <i class="fas fa-gamepad text-primary"></i>
                                <small class="text-muted">Games bijgewerkt</small>
                            </div>
                            <div class="timeline-item">
                                <i class="fas fa-sign-in-alt text-success"></i>
                                <small class="text-muted">Ingelogd vandaag</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Favorite Games Management -->
            <div class="col-md-8">
                <div class="card bg-secondary text-light">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">
                                <i class="fas fa-gamepad me-2"></i>Favoriete Games Beheren
                            </h4>
                            <small class="text-muted">
                                Selecteer je favoriete games om ze te delen met vrienden en voor betere aanbevelingen
                            </small>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="filterGames('all')">Alle Games</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGames('selected')">Geselecteerd</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGames('action')">Actie</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGames('strategy')">Strategie</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGames('rpg')">RPG</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <!-- Current Favorites Display with Enhanced Design -->
                        <?php if (!empty($favorite_games)): ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">
                                        <i class="fas fa-heart me-2 text-danger"></i>
                                        Je huidige favorieten (<?php echo count($favorite_games); ?>):
                                    </h6>
                                    <button class="btn btn-outline-danger btn-sm" onclick="clearAllFavorites()">
                                        <i class="fas fa-trash me-1"></i>Alles Wissen
                                    </button>
                                </div>
                                <div class="row g-2">
                                    <?php foreach ($favorite_games as $game): ?>
                                        <div class="col-md-6">
                                            <div class="card bg-dark border-primary h-100">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="game-icon me-3">
                                                            <i class="fas fa-gamepad text-primary fa-2x"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($game['titel']); ?></h6>
                                                            <?php if (!empty($game['genre'])): ?>
                                                                <span class="badge bg-secondary mb-1"><?php echo htmlspecialchars($game['genre']); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($game['description'])): ?>
                                                                <p class="small text-muted mb-0">
                                                                    <?php echo htmlspecialchars(substr($game['description'], 0, 50) . (strlen($game['description']) > 50 ? '...' : '')); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-end">
                                                            <button class="btn btn-outline-danger btn-sm" 
                                                                    onclick="removeFavorite(<?php echo $game['game_id']; ?>)" 
                                                                    title="Verwijderen">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Enhanced Game Selection Form -->
                        <form method="POST" id="gameForm">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>Alle beschikbare games (<?php echo count($games); ?>):
                                </h6>
                                <div class="input-group" style="max-width: 300px;">
                                    <input type="text" class="form-control form-control-sm" 
                                           id="gameSearch" placeholder="Zoek games..." 
                                           onkeyup="searchGames()">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row game-grid" style="max-height: 500px; overflow-y: auto;" id="gameList">
                                <?php foreach ($games as $game): ?>
                                    <div class="col-md-6 col-lg-4 mb-3 game-item" 
                                         data-name="<?php echo strtolower($game['titel']); ?>" 
                                         data-genre="<?php echo strtolower($game['genre'] ?? ''); ?>">
                                        <div class="card bg-dark border hover-card h-100">
                                            <div class="card-body p-3">
                                                <div class="form-check d-flex align-items-start">
                                                    <input type="checkbox" 
                                                           name="favorite_games[]" 
                                                           value="<?php echo $game['game_id']; ?>" 
                                                           class="form-check-input me-3 mt-1"
                                                           id="game_<?php echo $game['game_id']; ?>"
                                                           <?php echo in_array($game['game_id'], $current_favorites) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label flex-grow-1" for="game_<?php echo $game['game_id']; ?>">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="fas fa-gamepad text-info me-2"></i>
                                                            <strong><?php echo htmlspecialchars($game['titel']); ?></strong>
                                                        </div>
                                                        <?php if (!empty($game['genre'])): ?>
                                                            <span class="badge bg-info mb-2"><?php echo htmlspecialchars($game['genre']); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($game['description'])): ?>
                                                            <p class="small text-muted mb-0">
                                                                <?php echo htmlspecialchars(substr($game['description'], 0, 80) . (strlen($game['description']) > 80 ? '...' : '')); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Enhanced Control Panel -->
                            <div class="card bg-dark border-primary mt-4">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-info-circle text-info me-2"></i>
                                                <span class="text-light">
                                                    <span id="selectedCount" class="fw-bold">0</span> games geselecteerd
                                                </span>
                                                <div class="ms-3">
                                                    <span class="badge bg-primary" id="actionBadge">Actie: 0</span>
                                                    <span class="badge bg-success" id="strategyBadge">Strategie: 0</span>
                                                    <span class="badge bg-warning" id="rpgBadge">RPG: 0</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAll()">
                                                    <i class="fas fa-check-double me-1"></i>Alles
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAll()">
                                                    <i class="fas fa-times me-1"></i>Wissen
                                                </button>
                                                <button type="button" class="btn btn-outline-info btn-sm" onclick="selectPopular()">
                                                    <i class="fas fa-star me-1"></i>Populair
                                                </button>
                                            </div>
                                            <button type="submit" class="btn btn-primary ms-2">
                                                <i class="fas fa-save me-1"></i>Favorieten Opslaan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center p-3 mt-5">
        <div class="container">
            <p class="mb-0">
                © 2025 GamePlan Scheduler door Harsha Kanaparthi | 
                <a href="privacy.php" class="text-white text-decoration-none">Privacy</a> | 
                <a href="contact.php" class="text-white text-decoration-none">Contact</a>
            </p>
        </div>
    </footer>
            
            <!-- Favorite Games -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-gamepad me-2"></i>Favoriete Games Beheren</h4>
                        <p class="mb-0 text-muted">Selecteer je favoriete games om ze te delen met vrienden</p>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <!-- Current Favorites Display -->
                        <?php if (!empty($favorite_games)): ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-heart me-2 text-danger"></i>Je huidige favorieten:</h6>
                                <div class="row">
                                    <?php foreach ($favorite_games as $game): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                                <i class="fas fa-gamepad text-primary me-2"></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($game['titel']); ?></strong>
                                                    <?php if (!empty($game['genre'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($game['genre']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Game Selection Form -->
                        <form method="POST" id="gameForm">
                            <h6><i class="fas fa-list me-2"></i>Alle beschikbare games:</h6>
                            <div class="row" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($games as $game): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check p-3 border rounded hover-highlight">
                                            <input type="checkbox" 
                                                   name="favorite_games[]" 
                                                   value="<?php echo $game['game_id']; ?>" 
                                                   class="form-check-input"
                                                   id="game_<?php echo $game['game_id']; ?>"
                                                   <?php echo in_array($game['game_id'], $current_favorites) ? 'checked' : ''; ?>>
                                            <label class="form-check-label d-flex align-items-center" for="game_<?php echo $game['game_id']; ?>">
                                                <div class="ms-2">
                                                    <strong><?php echo htmlspecialchars($game['titel']); ?></strong>
                                                    <?php if (!empty($game['genre'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($game['genre']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($game['description'])): ?>
                                                        <br><small class="text-secondary"><?php echo htmlspecialchars($game['description']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <span id="selectedCount">0</span> games geselecteerd
                                    </small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-secondary me-2" onclick="selectAll()">
                                        <i class="fas fa-check-double me-1"></i>Alles selecteren
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary me-2" onclick="clearAll()">
                                        <i class="fas fa-times me-1"></i>Alles deselecteren
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Opslaan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center p-3 mt-5">
        <div class="container">
            <p class="mb-0">
                © 2025 GamePlan Scheduler door Harsha Kanaparthi | 
                <a href="privacy.php" class="text-white text-decoration-none">Privacy</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    
    <script>
        // Enhanced game selection functionality with advanced features
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCount();
            updateGenreCounters();
            
            // Add event listeners to all checkboxes
            document.querySelectorAll('input[name="favorite_games[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectedCount();
                    updateGenreCounters();
                    updateVisualFeedback();
                });
            });
            
            // Add hover effects for cards
            document.querySelectorAll('.hover-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.transition = 'all 0.3s ease';
                    this.style.boxShadow = '0 8px 25px rgba(0,123,255,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    if (!this.querySelector('input[type="checkbox"]').checked) {
                        this.style.transform = 'translateY(0)';
                        this.style.boxShadow = '';
                    }
                });
            });
            
            // Set initial styles for checked items
            updateVisualFeedback();
        });
        
        function updateSelectedCount() {
            const selected = document.querySelectorAll('input[name="favorite_games[]"]:checked').length;
            document.getElementById('selectedCount').textContent = selected;
        }
        
        function updateGenreCounters() {
            const actionCount = document.querySelectorAll('input[name="favorite_games[]"]:checked[data-genre*="action"]').length;
            const strategyCount = document.querySelectorAll('input[name="favorite_games[]"]:checked[data-genre*="strategy"]').length;
            const rpgCount = document.querySelectorAll('input[name="favorite_games[]"]:checked[data-genre*="rpg"]').length;
            
            if (document.getElementById('actionBadge')) {
                document.getElementById('actionBadge').textContent = `Actie: ${actionCount}`;
                document.getElementById('strategyBadge').textContent = `Strategie: ${strategyCount}`;
                document.getElementById('rpgBadge').textContent = `RPG: ${rpgCount}`;
            }
        }
        
        function updateVisualFeedback() {
            document.querySelectorAll('input[name="favorite_games[]"]').forEach(checkbox => {
                const card = checkbox.closest('.hover-card');
                if (checkbox.checked) {
                    card.style.borderColor = '#007bff';
                    card.style.backgroundColor = '#1a2332';
                    card.style.transform = 'translateY(-2px)';
                    card.style.boxShadow = '0 4px 15px rgba(0,123,255,0.2)';
                } else {
                    card.style.borderColor = '';
                    card.style.backgroundColor = '';
                    card.style.transform = '';
                    card.style.boxShadow = '';
                }
            });
        }
        
        function selectAll() {
            const visibleCheckboxes = document.querySelectorAll('#gameList .game-item:not([style*="display: none"]) input[name="favorite_games[]"]');
            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectedCount();
            updateGenreCounters();
            updateVisualFeedback();
        }
        
        function clearAll() {
            document.querySelectorAll('input[name="favorite_games[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
            updateGenreCounters();
            updateVisualFeedback();
        }
        
        function selectPopular() {
            // Select popular games (first 10 visible)
            const popularGames = document.querySelectorAll('#gameList .game-item:not([style*="display: none"]) input[name="favorite_games[]"]');
            for (let i = 0; i < Math.min(10, popularGames.length); i++) {
                popularGames[i].checked = true;
            }
            updateSelectedCount();
            updateGenreCounters();
            updateVisualFeedback();
        }
        
        function clearAllFavorites() {
            if (confirm('Weet je zeker dat je al je favoriete games wilt verwijderen?')) {
                clearAll();
                document.getElementById('gameForm').submit();
            }
        }
        
        function removeFavorite(gameId) {
            if (confirm('Weet je zeker dat je deze game wilt verwijderen uit je favorieten?')) {
                const checkbox = document.querySelector(`input[value="${gameId}"]`);
                if (checkbox) {
                    checkbox.checked = false;
                    updateSelectedCount();
                    updateGenreCounters();
                    updateVisualFeedback();
                }
                
                // Submit form to save changes
                document.getElementById('gameForm').submit();
            }
        }
        
        function searchGames() {
            const searchTerm = document.getElementById('gameSearch').value.toLowerCase();
            const gameItems = document.querySelectorAll('.game-item');
            
            gameItems.forEach(item => {
                const gameName = item.dataset.name;
                const gameGenre = item.dataset.genre || '';
                
                if (gameName.includes(searchTerm) || gameGenre.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function filterGames(filterType) {
            const gameItems = document.querySelectorAll('.game-item');
            
            gameItems.forEach(item => {
                const isSelected = item.querySelector('input[type="checkbox"]').checked;
                const gameGenre = item.dataset.genre || '';
                
                switch(filterType) {
                    case 'all':
                        item.style.display = 'block';
                        break;
                    case 'selected':
                        item.style.display = isSelected ? 'block' : 'none';
                        break;
                    case 'action':
                        item.style.display = gameGenre.includes('action') ? 'block' : 'none';
                        break;
                    case 'strategy':
                        item.style.display = gameGenre.includes('strategy') ? 'block' : 'none';
                        break;
                    case 'rpg':
                        item.style.display = gameGenre.includes('rpg') ? 'block' : 'none';
                        break;
                    default:
                        item.style.display = 'block';
                }
            });
        }
        
        function shareProfile() {
            const profileUrl = window.location.origin + window.location.pathname;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Mijn GamePlan Scheduler Profiel',
                    text: 'Bekijk mijn gaming profiel en favoriete games!',
                    url: profileUrl
                }).then(() => {
                    showToast('Profiel Gedeeld', 'Je profiel is succesvol gedeeld!', 'success');
                }).catch(err => {
                    console.log('Error sharing:', err);
                    copyToClipboard(profileUrl);
                });
            } else {
                copyToClipboard(profileUrl);
            }
        }
        
        function exportData() {
            const favoriteGames = [];
            document.querySelectorAll('input[name="favorite_games[]"]:checked').forEach(checkbox => {
                const label = document.querySelector(`label[for="${checkbox.id}"]`);
                const gameTitle = label.querySelector('strong').textContent;
                favoriteGames.push(gameTitle);
            });
            
            const userData = {
                username: '<?php echo htmlspecialchars($profile['username']); ?>',
                favoriteGames: favoriteGames,
                profileCompletion: '<?php echo $profile_completion; ?>%',
                exportDate: new Date().toISOString()
            };
            
            const dataStr = JSON.stringify(userData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = 'gameplan_profile_data.json';
            link.click();
            
            showToast('Data Geëxporteerd', 'Je profielgegevens zijn gedownload als JSON bestand', 'success');
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Link Gekopieerd', 'Profiel link is gekopieerd naar het klembord!', 'info');
            }).catch(err => {
                console.error('Could not copy text: ', err);
                showToast('Fout', 'Kon link niet kopiëren', 'error');
            });
        }
        
        // Form validation with advanced checks
        document.getElementById('gameForm').addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('input[name="favorite_games[]"]:checked').length;
            if (selected === 0) {
                e.preventDefault();
                showToast('Validatiefout', 'Selecteer minstens één favoriete game voordat je opslaat.', 'warning');
                return false;
            }
            
            if (selected > 20) {
                e.preventDefault();
                showToast('Te veel games', 'Je kunt maximaal 20 favoriete games selecteren voor optimale prestaties.', 'warning');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Opslaan...';
            submitBtn.disabled = true;
            
            // Re-enable button if form submission fails
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 10000);
        });
        
        // Show toast notification
        function showToast(title, message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container') || createToastContainer();
            const toastId = 'toast-' + Date.now();
            
            const toastHTML = `
                <div class="toast align-items-center text-bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'info'}" 
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
            const toast = new bootstrap.Toast(document.getElementById(toastId), { delay: 5000 });
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
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'a':
                        e.preventDefault();
                        selectAll();
                        break;
                    case 'c':
                        if (e.shiftKey) {
                            e.preventDefault();
                            clearAll();
                        }
                        break;
                    case 's':
                        e.preventDefault();
                        document.getElementById('gameForm').submit();
                        break;
                }
            }
        });
        
        // Auto-save draft functionality
        let autoSaveTimer;
        document.addEventListener('change', function(e) {
            if (e.target.matches('input[name="favorite_games[]"]')) {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    const selected = Array.from(document.querySelectorAll('input[name="favorite_games[]"]:checked'))
                                        .map(cb => cb.value);
                    localStorage.setItem('gameplan_profile_draft', JSON.stringify(selected));
                }, 1000);
            }
        });
        
        // Load draft on page load
        window.addEventListener('load', function() {
            const draft = localStorage.getItem('gameplan_profile_draft');
            if (draft) {
                try {
                    const selectedIds = JSON.parse(draft);
                    selectedIds.forEach(id => {
                        const checkbox = document.querySelector(`input[value="${id}"]`);
                        if (checkbox && !checkbox.checked) {
                            checkbox.checked = true;
                        }
                    });
                    updateSelectedCount();
                    updateGenreCounters();
                    updateVisualFeedback();
                } catch (e) {
                    console.log('Error loading draft:', e);
                }
            }
        });
        
        // Clear draft on successful form submission
        document.getElementById('gameForm').addEventListener('submit', function() {
            localStorage.removeItem('gameplan_profile_draft');
        });
    </script>
</body>
</html>