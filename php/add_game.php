<?php
require 'functions.php';
if (!isLoggedIn() || !isAdmin()) {
    header("Location: games.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $release_year = intval($_POST['release_year'] ?? 0);
    $max_players = intval($_POST['max_players'] ?? 0);
    $min_players = intval($_POST['min_players'] ?? 1);
    $average_session_time = intval($_POST['average_session_time'] ?? 0);
    $rating = $_POST['rating'] ?? 'E';
    $developer = trim($_POST['developer'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    if (addGame($title, $description, $genre, $platform, $release_year, $max_players, $min_players, 
                $average_session_time, $rating, $developer, $image_url)) {
        header("Location: games.php?success=added");
        exit;
    } else {
        $message = '<div class="alert alert-danger">Fout bij toevoegen van game.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Game Toevoegen</h2>
        <?php echo $message; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <form method="POST" class="card shadow" onsubmit="return validateForm(this);">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titel</label>
                            <input type="text" id="title" name="title" class="form-control" required maxlength="100">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Beschrijving</label>
                            <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="genre" class="form-label">Genre</label>
                                <select id="genre" name="genre" class="form-select" required>
                                    <option value="">Kies genre</option>
                                    <option value="Action">Action</option>
                                    <option value="Adventure">Adventure</option>
                                    <option value="RPG">RPG</option>
                                    <option value="Strategy">Strategy</option>
                                    <option value="Sports">Sports</option>
                                    <option value="Racing">Racing</option>
                                    <option value="Simulation">Simulation</option>
                                    <option value="FPS">FPS</option>
                                    <option value="MOBA">MOBA</option>
                                    <option value="Battle Royale">Battle Royale</option>
                                    <option value="MMO">MMO</option>
                                    <option value="Party">Party</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="platform" class="form-label">Platform</label>
                                <input type="text" id="platform" name="platform" class="form-control" required placeholder="PC, Console, Mobile">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="release_year" class="form-label">Release Jaar</label>
                                <input type="number" id="release_year" name="release_year" class="form-control" required min="1970" max="<?php echo date('Y') + 1; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="developer" class="form-label">Ontwikkelaar</label>
                                <input type="text" id="developer" name="developer" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="min_players" class="form-label">Min. Spelers</label>
                                <input type="number" id="min_players" name="min_players" class="form-control" required min="1" value="1">
                            </div>
                            <div class="col-md-4">
                                <label for="max_players" class="form-label">Max. Spelers</label>
                                <input type="number" id="max_players" name="max_players" class="form-control" required min="1">
                            </div>
                            <div class="col-md-4">
                                <label for="average_session_time" class="form-label">Sessie Tijd (min)</label>
                                <input type="number" id="average_session_time" name="average_session_time" class="form-control" required min="5">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="rating" class="form-label">Leeftijdsrating</label>
                                <select id="rating" name="rating" class="form-select" required>
                                    <option value="E">Everyone</option>
                                    <option value="T">Teen</option>
                                    <option value="M">Mature</option>
                                    <option value="AO">Adults Only</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="image_url" class="form-label">Afbeelding URL</label>
                                <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Game Toevoegen</button>
                            <a href="games.php" class="btn btn-outline-secondary">Annuleren</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateForm(form) {
            const maxPlayers = parseInt(form.max_players.value);
            const minPlayers = parseInt(form.min_players.value);
            
            if (minPlayers > maxPlayers) {
                alert('Minimum aantal spelers kan niet groter zijn dan maximum aantal spelers.');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>