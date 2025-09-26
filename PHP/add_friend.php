<?php
require 'functions.php';

// Advanced security check with session validation
if (!isLoggedIn() || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Advanced data loading with error handling
try {
    $profile = getProfile($user_id);
    $recent_friends = getRecentFriends($user_id, 5);
    $friend_suggestions = getFriendSuggestions($user_id, 5);
} catch (Exception $e) {
    error_log("Profile loading error: " . $e->getMessage());
    $profile = null;
    $recent_friends = [];
    $friend_suggestions = [];
}

$message = '';
$validation_errors = [];

// Check if profile was successfully retrieved
if (!$profile) {
    $message = '<div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Fout bij het ophalen van gebruikersprofiel.</strong> 
        Probeer opnieuw in te loggen.
    </div>';
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $friend_username = trim(filter_input(INPUT_POST, 'friend_username', FILTER_SANITIZE_STRING));
    
    // Advanced validation checks
    if (empty($friend_username)) {
        $validation_errors[] = 'Username is verplicht.';
    } elseif (strlen($friend_username) < 3) {
        $validation_errors[] = 'Username moet minimaal 3 karakters lang zijn.';
    } elseif (strlen($friend_username) > 50) {
        $validation_errors[] = 'Username is te lang (max 50 karakters).';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $friend_username)) {
        $validation_errors[] = 'Username mag alleen letters, cijfers, underscore en streepjes bevatten.';
    } elseif (strtolower($friend_username) === strtolower($profile['username'])) {
        $validation_errors[] = 'Je kunt jezelf niet als vriend toevoegen.';
    } else {
        // Advanced friend adding with duplicate check
        $result = addFriend($user_id, $friend_username);
        if ($result['success']) {
            $message = '<div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Succesvol!</strong> ' . htmlspecialchars($result['message']) . '
                <br><small>Vriend toegevoegd op: ' . date('j M Y H:i') . '</small>
            </div>';
            
            // Log successful friend addition
            logUserActivity($user_id, 'friend_added', $result['friend_id']);
        } else {
            $message = '<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Fout:</strong> ' . htmlspecialchars($result['message']) . '
            </div>';
        }
    }
    
    // Show validation errors if any
    if (!empty($validation_errors)) {
        $message = '<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Validatiefouten:</strong>
            <ul class="mb-0 mt-2">';
        foreach ($validation_errors as $error) {
            $message .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $message .= '</ul></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vriend toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
    <header class="bg-dark text-white p-3">
        <div class="container d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-gamepad me-2"></i>GamePlan Scheduler</h1>
            <nav>
                <a href="index.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a href="friends.php" class="btn btn-outline-light">
                    <i class="fas fa-users me-1"></i>Alle Vrienden
                </a>
            </nav>
        </div>
    </header>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Vriend Toevoegen</h2>
                        <p class="mb-0 text-muted">Zoek en voeg nieuwe gaming buddies toe aan je netwerk</p>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <?php echo $message; ?>
                        <?php endif; ?>
                        
                        <?php if ($profile): ?>
                        <form method="POST" novalidate onsubmit="return validateForm(this);">
                            <div class="mb-3">
                                <label for="friend_username" class="form-label">
                                    <i class="fas fa-search me-2"></i>Username van vriend
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" 
                                           id="friend_username" 
                                           name="friend_username" 
                                           class="form-control" 
                                           placeholder="Voer username in..."
                                           value="<?php echo isset($_POST['friend_username']) ? htmlspecialchars($_POST['friend_username']) : ''; ?>"
                                           required
                                           maxlength="50"
                                           pattern="[a-zA-Z0-9_-]+"
                                           autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary" id="searchFriendBtn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Zoek op exacte username (hoofdlettergevoelig)
                                </div>
                                <div class="invalid-feedback">
                                    Username mag alleen letters, cijfers, underscore en streepjes bevatten
                                </div>
                            </div>
                            
                            <!-- Tips section -->
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-lightbulb me-2"></i>Tips voor het vinden van vrienden:</h6>
                                <ul class="mb-0">
                                    <li>Vraag vrienden naar hun exacte username</li>
                                    <li>Let op hoofdletters en kleine letters</li>
                                    <li>Controleer op typfouten als iemand niet gevonden wordt</li>
                                    <li>Vrienden moeten een account hebben om toegevoegd te worden</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Vriend Toevoegen
                                </button>
                                <a href="friends.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Terug naar vrienden
                                </a>
                            </div>
                        </form>
                        
                        <!-- Recent activity section -->
                        <div class="row mt-5">
                            <div class="col-12">
                                <h5><i class="fas fa-clock me-2"></i>Jouw Netwerk</h5>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4><?php echo $profile['friend_count'] ?? 0; ?></h4>
                                        <small class="text-muted">Vrienden</small>
                                    </div>
                                    <div class="col-4">
                                        <h4><?php echo $profile['schedule_count'] ?? 0; ?></h4>
                                        <small class="text-muted">Schema's</small>
                                    </div>
                                    <div class="col-4">
                                        <h4><?php echo $profile['event_count'] ?? 0; ?></h4>
                                        <small class="text-muted">Events</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Er is een fout opgetreden bij het laden van je profiel. Probeer opnieuw in te loggen.
                            </div>
                            <a href="login.php" class="btn btn-primary">Opnieuw inloggen</a>
                        <?php endif; ?>
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
        // Enhanced username validation
        document.getElementById('friend_username').addEventListener('input', function() {
            const username = this.value;
            const isValid = /^[a-zA-Z0-9_-]*$/.test(username);
            
            if (!isValid && username.length > 0) {
                this.classList.add('is-invalid');
                this.setCustomValidity('Alleen letters, cijfers, _ en - toegestaan');
            } else {
                this.classList.remove('is-invalid');
                this.setCustomValidity('');
            }
        });
        
        // Search friend functionality
        document.getElementById('searchFriendBtn').addEventListener('click', function() {
            const usernameInput = document.getElementById('friend_username');
            const username = usernameInput.value.trim();
            
            if (!username) {
                showAlert('Voer een username in om te zoeken', 'warning');
                usernameInput.focus();
                return;
            }
            
            if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                showAlert('Username mag alleen letters, cijfers, _ en - bevatten', 'warning');
                usernameInput.focus();
                return;
            }
            
            // Here you could add AJAX search functionality
            showAlert('Zoeken naar gebruiker: ' + username, 'info');
        });
        
        // Add suggested friend
        function addSuggestedFriend(username) {
            const usernameInput = document.getElementById('friend_username');
            usernameInput.value = username;
            
            if (confirm(`Wil je ${username} toevoegen als vriend?`)) {
                document.querySelector('form').submit();
            }
        }
        
        // Enhanced form validation
        function validateForm(form) {
            const username = form.friend_username.value.trim();
            
            if (!username) {
                showAlert('Voer een username in', 'warning');
                form.friend_username.focus();
                return false;
            }
            
            if (username.length < 3) {
                showAlert('Username moet minimaal 3 karakters lang zijn', 'warning');
                form.friend_username.focus();
                return false;
            }
            
            if (username.length > 50) {
                showAlert('Username is te lang (max 50 karakters)', 'warning');
                form.friend_username.focus();
                return false;
            }
            
            if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                showAlert('Username mag alleen letters, cijfers, _ en - bevatten', 'warning');
                form.friend_username.focus();
                return false;
            }
            
            return true;
        }
        
        // Show alert function
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.card-body');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on username input
            const usernameInput = document.getElementById('friend_username');
            if (usernameInput) {
                usernameInput.focus();
            }
        });
    </script>
</body>
</html>