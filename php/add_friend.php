<?php
// Voeg vriend toe pagina voor GamePlan Scheduler
// Met zoekfunctie via AJAX voor live suggesties
// Validatie op zelf toevoegen en duplicaten

require 'functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $friend_username = $_POST['friend_username'] ?? '';
    try {
        addFriend($user_id, $friend_username);
        $_SESSION['msg'] = "Vriend toegevoegd!";
        header("Location: friends.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vriend Toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body class="bg-dark text-light">
    <header class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">GamePlan Scheduler</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profiel</a></li>
                    <li class="nav-item"><a class="nav-link" href="friends.php">Vrienden</a></li>
                    <li class="nav-item"><a class="nav-link" href="add_schedule.php">Schema Toevoegen</a></li>
                    <li class="nav-item"><a class="nav-link" href="add_event.php">Evenement Toevoegen</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container mt-5 pt-5">
        <h2>Vriend Toevoegen</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo sanitizeInput($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="addFriendForm">
            <div class="mb-3">
                <label for="friend_username" class="form-label">Gebruikersnaam van vriend</label>
                <input type="text" class="form-control" id="friend_username" name="friend_username" required placeholder="Zoek een gebruiker" pattern="^[a-zA-Z0-9]+$">
                <div id="searchSuggestions" class="list-group mt-2"></div>  <!-- Voor AJAX suggesties -->
            </div>
            <button type="submit" class="btn btn-primary">Toevoegen</button>
        </form>
    </main>

    <footer class="bg-primary text-center py-3 mt-auto">
        <p class="mb-0 text-light">© 2025 GamePlan Scheduler door Harsha Kanaparthi. <a href="privacy.php" class="text-light">Privacybeleid</a> | <a href="#" class="text-light">Contact</a></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // AJAX zoekfunctie voor vrienden suggesties
        document.getElementById('friend_username').addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 3) {
                document.getElementById('searchSuggestions').innerHTML = '';
                return;
            }

            fetch(`search_users.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(users => {
                    const suggestions = document.getElementById('searchSuggestions');
                    suggestions.innerHTML = '';
                    if (users.length === 0) {
                        suggestions.innerHTML = '<div class="list-group-item list-group-item-dark">Geen gebruikers gevonden.</div>';
                    } else {
                        users.forEach(user => {
                            const item = document.createElement('a');
                            item.classList.add('list-group-item', 'list-group-item-action', 'list-group-item-dark');
                            item.textContent = user.username;
                            item.onclick = function() {
                                document.getElementById('friend_username').value = user.username;
                                suggestions.innerHTML = '';
                            };
                            suggestions.appendChild(item);
                        });
                    }
                })
                .catch(error => console.error('Fout bij zoeken:', error));
        });
    </script>
</body>
</html>