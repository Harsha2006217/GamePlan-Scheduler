<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$profile = getProfile($user_id);
$message = '';

// Handle friend addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Ongeldige beveiligingstoken.</div>';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add_friend') {
            $friend_id = filter_input(INPUT_POST, 'friend_id', FILTER_VALIDATE_INT);
            if (!$friend_id) {
                $message = '<div class="alert alert-danger">Ongeldige gebruiker geselecteerd.</div>';
            } elseif ($friend_id === $user_id) {
                $message = '<div class="alert alert-danger">Je kunt jezelf niet toevoegen.</div>';
            } else {
                if (addFriendById($user_id, $friend_id)) {
                    $message = '<div class="alert alert-success">Vriend toegevoegd.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Gebruiker niet gevonden of al vriend.</div>';
                }
            }
        }
    }
}

// Get suggested friends (users with similar game interests)
$suggested_friends = getSuggestedFriends($user_id);

// Get popular users (users with most friends/activities)
$popular_users = getPopularUsers($user_id);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vriend toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Vriend toevoegen</h2>
        <?php echo $message; ?>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="search-container mb-4">
                            <input type="text" 
                                   id="userSearch" 
                                   class="form-control form-control-lg" 
                                   placeholder="Zoek gebruikers op username, games of interesses..."
                                   autocomplete="off">
                            <div id="searchResults" class="search-results d-none">
                                <!-- Search results will be populated here -->
                            </div>
                        </div>

                        <!-- Suggested Friends Section -->
                        <div class="mb-4">
                            <h4><i class="bi bi-people-fill"></i> Aanbevolen Vrienden</h4>
                            <div class="suggested-friends">
                                <?php if (empty($suggested_friends)): ?>
                                    <p class="text-muted">Geen aanbevelingen beschikbaar.</p>
                                <?php else: ?>
                                    <?php foreach ($suggested_friends as $friend): ?>
                                        <div class="user-card p-3 border rounded mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($friend['username']); ?></h5>
                                                    <p class="mb-1 text-muted small">
                                                        <?php echo htmlspecialchars($friend['common_games']); ?> games in common
                                                    </p>
                                                </div>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="add_friend">
                                                    <input type="hidden" name="friend_id" value="<?php echo $friend['user_id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-person-plus-fill"></i> Toevoegen
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Popular Users Section -->
                        <div>
                            <h4><i class="bi bi-star-fill"></i> Populaire Gebruikers</h4>
                            <div class="popular-users">
                                <?php if (empty($popular_users)): ?>
                                    <p class="text-muted">Geen populaire gebruikers gevonden.</p>
                                <?php else: ?>
                                    <?php foreach ($popular_users as $user): ?>
                                        <div class="user-card p-3 border rounded mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                                                    <p class="mb-1 text-muted small">
                                                        <?php echo $user['friend_count']; ?> vrienden • 
                                                        <?php echo $user['activity_count']; ?> activiteiten
                                                    </p>
                                                </div>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="add_friend">
                                                    <input type="hidden" name="friend_id" value="<?php echo $user['user_id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-person-plus-fill"></i> Toevoegen
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-house-door-fill"></i> Terug naar dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('userSearch');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.add('d-none');
                return;
            }

            searchTimeout = setTimeout(() => {
                searchUsers(query);
            }, 300);
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                searchResults.classList.remove('d-none');
            }
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('d-none');
            }
        });

        async function searchUsers(query) {
            try {
                const response = await fetch(`search_users.php?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'X-CSRF-Token': '<?php echo generateCSRFToken(); ?>'
                    }
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.json();
                displaySearchResults(data);
            } catch (error) {
                console.error('Error searching users:', error);
                searchResults.innerHTML = '<div class="p-3 text-danger">Error searching users</div>';
            }
        }

        function displaySearchResults(users) {
            if (users.length === 0) {
                searchResults.innerHTML = '<div class="p-3 text-muted">Geen gebruikers gevonden</div>';
            } else {
                searchResults.innerHTML = users.map(user => `
                    <div class="search-result-item p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${escapeHtml(user.username)}</h6>
                                <small class="text-muted">
                                    ${user.common_games} games in common
                                </small>
                            </div>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="add_friend">
                                <input type="hidden" name="friend_id" value="${user.user_id}">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-person-plus-fill"></i> Toevoegen
                                </button>
                            </form>
                        </div>
                    </div>
                `).join('');
            }
            searchResults.classList.remove('d-none');
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    });
    </script>

    <style>
    .search-container {
        position: relative;
    }
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        z-index: 1000;
        max-height: 300px;
        overflow-y: auto;
    }
    .search-result-item:hover {
        background-color: #f8f9fa;
    }
    .suggested-friends, .popular-users {
        max-height: 300px;
        overflow-y: auto;
    }
    </style>
</body>
</html>