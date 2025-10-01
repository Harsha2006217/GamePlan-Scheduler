<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';
$genre = $_GET['genre'] ?? '';
$platform = $_GET['platform'] ?? '';
$sort = $_GET['sort'] ?? 'popularity';

// Get all distinct genres and platforms for filters
global $pdo;
$genres = $pdo->query("SELECT DISTINCT genre FROM Games WHERE genre IS NOT NULL ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);
$platforms = $pdo->query("SELECT DISTINCT platform FROM Games WHERE platform IS NOT NULL ORDER BY platform")->fetchAll(PDO::FETCH_COLUMN);

// Build the query for games
$query = "
    SELECT g.*, 
           COALESCE(AVG(ug.rating), 0) as average_rating,
           COUNT(DISTINCT ug.user_id) as total_players,
           ug2.rating as user_rating,
           ug2.is_currently_playing,
           ug2.favorite_mode
    FROM Games g
    LEFT JOIN UserGames ug ON g.game_id = ug.game_id
    LEFT JOIN UserGames ug2 ON g.game_id = ug2.game_id AND ug2.user_id = :user_id
    WHERE 1=1
";

$params = ['user_id' => $user_id];

if ($search) {
    $query .= " AND (g.titel LIKE :search OR g.description LIKE :search)";
    $params['search'] = "%$search%";
}

if ($genre) {
    $query .= " AND g.genre = :genre";
    $params['genre'] = $genre;
}

if ($platform) {
    $query .= " AND g.platform LIKE :platform";
    $params['platform'] = "%$platform%";
}

$query .= " GROUP BY g.game_id";

switch ($sort) {
    case 'rating':
        $query .= " ORDER BY average_rating DESC, g.popularity_score DESC";
        break;
    case 'name':
        $query .= " ORDER BY g.titel";
        break;
    case 'newest':
        $query .= " ORDER BY g.release_year DESC";
        break;
    default: // popularity
        $query .= " ORDER BY g.popularity_score DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games Catalogus - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Games Catalogus</h2>

        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-md-8 offset-md-2">
                <form method="GET" class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Zoek games..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="genre" class="form-select">
                                    <option value="">Alle genres</option>
                                    <?php foreach ($genres as $g): ?>
                                        <option value="<?php echo htmlspecialchars($g); ?>" <?php if ($g === $genre) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($g); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="platform" class="form-select">
                                    <option value="">Alle platforms</option>
                                    <?php foreach ($platforms as $p): ?>
                                        <option value="<?php echo htmlspecialchars($p); ?>" <?php if ($p === $platform) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($p); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="sort" class="form-select">
                                    <option value="popularity" <?php if ($sort === 'popularity') echo 'selected'; ?>>Populair</option>
                                    <option value="rating" <?php if ($sort === 'rating') echo 'selected'; ?>>Beoordeling</option>
                                    <option value="name" <?php if ($sort === 'name') echo 'selected'; ?>>Naam</option>
                                    <option value="newest" <?php if ($sort === 'newest') echo 'selected'; ?>>Nieuwste</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="games.php" class="btn btn-outline-secondary ms-2">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Games Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($games as $game): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if ($game['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($game['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($game['titel']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($game['titel']); ?></h5>
                            <p class="card-text small">
                                <i class="bi bi-controller"></i> <?php echo htmlspecialchars($game['genre']); ?><br>
                                <i class="bi bi-laptop"></i> <?php echo htmlspecialchars($game['platform']); ?><br>
                                <i class="bi bi-calendar"></i> <?php echo htmlspecialchars($game['release_year']); ?>
                            </p>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($game['description'], 0, 100))); ?>...</p>
                            
                            <!-- Rating Display -->
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?php echo ($i <= round($game['average_rating'])) ? '-fill' : ''; ?> text-warning"></i>
                                <?php endfor; ?>
                                <small class="text-muted ms-2">(<?php echo $game['total_players']; ?> spelers)</small>
                            </div>

                            <!-- User's Rating -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group">
                                    <a href="view_game.php?id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                    <?php if ($game['is_currently_playing']): ?>
                                        <button type="button" class="btn btn-sm btn-success" disabled>
                                            <i class="bi bi-controller"></i> Speelt
                                        </button>
                                    <?php else: ?>
                                        <a href="add_to_games.php?id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-outline-success">
                                            Toevoegen
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php if ($game['user_rating']): ?>
                                    <small class="text-muted">Jouw score: <?php echo $game['user_rating']; ?>/5</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($games)): ?>
            <div class="text-center text-muted mt-4">
                <p>Geen games gevonden met de huidige filters.</p>
            </div>
        <?php endif; ?>

        <!-- Admin Actions -->
        <?php if (isAdmin()): ?>
            <div class="text-center mt-4">
                <a href="add_game.php" class="btn btn-primary">Game Toevoegen</a>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-primary">Terug naar dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>