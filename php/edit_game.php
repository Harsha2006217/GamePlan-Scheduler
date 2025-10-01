<?php
/**
 * GamePlan Scheduler - Enhanced Professional Game Edit Management
 * Advanced Gaming Content Management with Comprehensive Validation
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Game Edit System
 */

// Enhanced security includes and session management
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Advanced session validation with comprehensive security checks
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Enhanced admin privilege verification with detailed logging
if (!isAdmin()) {
    logSecurityEvent($_SESSION['user_id'], 'unauthorized_access_attempt', 'edit_game.php');
    header("Location: games.php?error=insufficient_privileges");
    exit;
}

// Advanced input validation and sanitization
$game_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$game_id || $game_id <= 0) {
    header("Location: games.php?error=invalid_game_id");
    exit;
}

// Comprehensive game data retrieval with error handling
try {
    $game = getGameById($game_id);
    if (!$game) {
        logAdminAction($_SESSION['user_id'], 'game_edit_not_found', $game_id);
        header("Location: games.php?error=game_not_found");
        exit;
    }
} catch (Exception $e) {
    error_log('Game Edit Error: ' . $e->getMessage());
    header("Location: games.php?error=database_error");
    exit;
}

// Professional message handling system
$message = '';
$message_type = '';
$validation_errors = [];

// Enhanced form processing with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Advanced CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Beveiligingsfout: CSRF token mismatch.';
        $message_type = 'danger';
    } else {
        // Comprehensive input validation and sanitization
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
        $genre = trim(filter_input(INPUT_POST, 'genre', FILTER_SANITIZE_STRING));
        $platform = trim(filter_input(INPUT_POST, 'platform', FILTER_SANITIZE_STRING));
        $release_year = filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT);
        $max_players = filter_input(INPUT_POST, 'max_players', FILTER_VALIDATE_INT);
        $min_players = filter_input(INPUT_POST, 'min_players', FILTER_VALIDATE_INT);
        $average_session_time = filter_input(INPUT_POST, 'average_session_time', FILTER_VALIDATE_INT);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_STRING);
        $developer = trim(filter_input(INPUT_POST, 'developer', FILTER_SANITIZE_STRING));
        $image_url = trim(filter_input(INPUT_POST, 'image_url', FILTER_VALIDATE_URL));
        
        // Advanced validation rules with specific error messages
        if (empty($title)) {
            $validation_errors[] = 'Titel is verplicht en mag niet leeg zijn.';
        } elseif (strlen($title) < 2) {
            $validation_errors[] = 'Titel moet minimaal 2 karakters bevatten.';
        } elseif (strlen($title) > 100) {
            $validation_errors[] = 'Titel mag maximaal 100 karakters bevatten.';
        } elseif (preg_match('/^\s*$/', $title)) {
            $validation_errors[] = 'Titel mag niet alleen uit spaties bestaan.';
        }
        
        if (empty($description)) {
            $validation_errors[] = 'Beschrijving is verplicht en mag niet leeg zijn.';
        } elseif (strlen($description) < 10) {
            $validation_errors[] = 'Beschrijving moet minimaal 10 karakters bevatten.';
        } elseif (strlen($description) > 1000) {
            $validation_errors[] = 'Beschrijving mag maximaal 1000 karakters bevatten.';
        }
        
        if (empty($genre)) {
            $validation_errors[] = 'Genre is verplicht.';
        }
        
        if (empty($platform)) {
            $validation_errors[] = 'Platform is verplicht en mag niet leeg zijn.';
        } elseif (strlen($platform) > 100) {
            $validation_errors[] = 'Platform mag maximaal 100 karakters bevatten.';
        }
        
        if (!$release_year || $release_year < 1970 || $release_year > (date('Y') + 2)) {
            $validation_errors[] = 'Release jaar moet tussen 1970 en ' . (date('Y') + 2) . ' liggen.';
        }
        
        if (!$min_players || $min_players < 1 || $min_players > 1000) {
            $validation_errors[] = 'Minimum aantal spelers moet tussen 1 en 1000 liggen.';
        }
        
        if (!$max_players || $max_players < 1 || $max_players > 10000) {
            $validation_errors[] = 'Maximum aantal spelers moet tussen 1 en 10000 liggen.';
        }
        
        if ($min_players && $max_players && $min_players > $max_players) {
            $validation_errors[] = 'Minimum aantal spelers kan niet groter zijn dan maximum aantal spelers.';
        }
        
        if (!$average_session_time || $average_session_time < 1 || $average_session_time > 1440) {
            $validation_errors[] = 'Gemiddelde sessietijd moet tussen 1 en 1440 minuten liggen.';
        }
        
        $valid_ratings = ['E', 'T', 'M', 'AO'];
        if (!in_array($rating, $valid_ratings)) {
            $validation_errors[] = 'Ongeldige leeftijdsrating geselecteerd.';
        }
        
        if (empty($developer)) {
            $validation_errors[] = 'Ontwikkelaar is verplicht en mag niet leeg zijn.';
        } elseif (strlen($developer) > 100) {
            $validation_errors[] = 'Ontwikkelaar naam mag maximaal 100 karakters bevatten.';
        }
        
        if (!empty($_POST['image_url']) && !$image_url) {
            $validation_errors[] = 'Ongeldige afbeelding URL opgegeven.';
        }
        
        // Check for duplicate titles (excluding current game)
        if (empty($validation_errors)) {
            $existing_game = getGameByTitle($title);
            if ($existing_game && $existing_game['game_id'] != $game_id) {
                $validation_errors[] = 'Een game met deze titel bestaat al in het systeem.';
            }
        }
        
        // Process update if no validation errors
        if (empty($validation_errors)) {
            try {
                $update_result = editGame(
                    $game_id, 
                    $title, 
                    $description, 
                    $genre, 
                    $platform, 
                    $release_year, 
                    $max_players, 
                    $min_players,
                    $average_session_time, 
                    $rating, 
                    $developer, 
                    $image_url
                );
                
                if ($update_result) {
                    // Log successful admin action
                    logAdminAction($_SESSION['user_id'], 'game_updated', $game_id, [
                        'title' => $title,
                        'genre' => $genre,
                        'platform' => $platform
                    ]);
                    
                    // Regenerate CSRF token for security
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    header("Location: games.php?success=game_updated&title=" . urlencode($title));
                    exit;
                } else {
                    $message = 'Er is een onverwachte fout opgetreden bij het bijwerken van de game.';
                    $message_type = 'danger';
                }
            } catch (Exception $e) {
                error_log('Game Update Error: ' . $e->getMessage());
                $message = 'Database fout bij het bijwerken van de game.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Er zijn validatiefouten gevonden. Controleer de gemarkeerde velden.';
            $message_type = 'danger';
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define available genres with professional gaming categories
$available_genres = [
    'Action' => 'Action',
    'Adventure' => 'Adventure', 
    'RPG' => 'RPG (Role-Playing Game)',
    'Strategy' => 'Strategy',
    'Sports' => 'Sports',
    'Racing' => 'Racing',
    'Simulation' => 'Simulation',
    'FPS' => 'FPS (First-Person Shooter)',
    'MOBA' => 'MOBA (Multiplayer Online Battle Arena)',
    'Battle Royale' => 'Battle Royale',
    'MMO' => 'MMO (Massively Multiplayer Online)',
    'MMORPG' => 'MMORPG (Massively Multiplayer Online RPG)',
    'Party' => 'Party',
    'Puzzle' => 'Puzzle',
    'Platformer' => 'Platformer',
    'Fighting' => 'Fighting',
    'Survival' => 'Survival',
    'Horror' => 'Horror',
    'Sandbox' => 'Sandbox',
    'Indie' => 'Indie'
];

// Define rating descriptions for better UX
$rating_descriptions = [
    'E' => 'Everyone - Geschikt voor alle leeftijden',
    'T' => 'Teen - Geschikt voor 13 jaar en ouder',
    'M' => 'Mature - Geschikt voor 17 jaar en ouder',
    'AO' => 'Adults Only - Alleen voor volwassenen'
];
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GamePlan Scheduler - Professional Game Edit Management">
    <meta name="author" content="Harsha Kanaparthi">
    <title>Game Bewerken: <?php echo htmlspecialchars($game['titel']); ?> - GamePlan Scheduler</title>
    
    <!-- Enhanced Bootstrap and CSS imports -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Professional favicon and meta tags -->
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <meta name="theme-color" content="#0d6efd">
</head>

<body class="bg-dark text-light">
    <!-- Enhanced Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-primary">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../index.php">
                <i class="bi bi-controller me-2"></i>
                GamePlan Scheduler
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="games.php">
                    <i class="bi bi-arrow-left me-1"></i>
                    Terug naar Games
                </a>
                <a class="nav-link text-warning" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Uitloggen
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-5">
        <!-- Professional Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary rounded-circle p-3 me-3">
                        <i class="bi bi-pencil-square text-white fs-4"></i>
                    </div>
                    <div>
                        <h1 class="h2 mb-1 text-primary">Game Bewerken</h1>
                        <p class="text-muted mb-0">
                            Bewerk de details van: <strong><?php echo htmlspecialchars($game['titel']); ?></strong>
                        </p>
                    </div>
                </div>
                
                <!-- Professional breadcrumb navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="../index.php" class="text-decoration-none">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="games.php" class="text-decoration-none">Games</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php echo htmlspecialchars($game['titel']); ?> bewerken
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Enhanced Message Display System -->
        <?php if (!empty($message)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?php echo $message_type === 'danger' ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Advanced Validation Errors Display -->
        <?php if (!empty($validation_errors)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-danger" role="alert">
                        <h6 class="alert-heading">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Validatiefouten gevonden:
                        </h6>
                        <ul class="mb-0">
                            <?php foreach ($validation_errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Professional Game Edit Form -->
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card shadow-lg border-0 bg-dark">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="bi bi-joystick me-2"></i>
                            Game Informatie Bewerken
                        </h3>
                    </div>
                    
                    <div class="card-body p-4">
                        <form method="POST" class="needs-validation" novalidate onsubmit="return validateGameForm(this);">
                            <!-- Enhanced CSRF Protection -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            
                            <!-- Basic Game Information Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3 border-bottom border-primary pb-2">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Basis Informatie
                                    </h5>
                                </div>
                            </div>

                            <!-- Game Title Field -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label fw-semibold">
                                        <i class="bi bi-card-text me-1"></i>
                                        Game Titel *
                                    </label>
                                    <input type="text" 
                                           id="title" 
                                           name="title" 
                                           class="form-control form-control-lg <?php echo in_array('titel', array_column($validation_errors, 0)) ? 'is-invalid' : ''; ?>" 
                                           required 
                                           maxlength="100"
                                           minlength="2"
                                           value="<?php echo htmlspecialchars($game['titel']); ?>"
                                           placeholder="Voer de officiële game titel in">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Minimaal 2 karakters, maximaal 100 karakters
                                    </div>
                                    <div class="invalid-feedback">
                                        Titel is verplicht en moet tussen 2-100 karakters bevatten.
                                    </div>
                                </div>

                                <!-- Genre Selection -->
                                <div class="col-md-6">
                                    <label for="genre" class="form-label fw-semibold">
                                        <i class="bi bi-tags me-1"></i>
                                        Genre *
                                    </label>
                                    <select id="genre" name="genre" class="form-select form-select-lg" required>
                                        <option value="">Selecteer genre...</option>
                                        <?php foreach ($available_genres as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" 
                                                    <?php echo ($game['genre'] === $value) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Selecteer een geldige game genre.
                                    </div>
                                </div>
                            </div>

                            <!-- Game Description -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="description" class="form-label fw-semibold">
                                        <i class="bi bi-file-text me-1"></i>
                                        Beschrijving *
                                    </label>
                                    <textarea id="description" 
                                              name="description" 
                                              class="form-control" 
                                              rows="4" 
                                              required 
                                              maxlength="1000"
                                              minlength="10"
                                              placeholder="Geef een uitgebreide beschrijving van de game..."><?php echo htmlspecialchars($game['description']); ?></textarea>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Minimaal 10 karakters, maximaal 1000 karakters
                                        <span class="float-end">
                                            <span id="description-count">0</span>/1000 karakters
                                        </span>
                                    </div>
                                    <div class="invalid-feedback">
                                        Beschrijving is verplicht en moet tussen 10-1000 karakters bevatten.
                                    </div>
                                </div>
                            </div>

                            <!-- Platform and Developer Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="platform" class="form-label fw-semibold">
                                        <i class="bi bi-device-ssd me-1"></i>
                                        Platform(s) *
                                    </label>
                                    <input type="text" 
                                           id="platform" 
                                           name="platform" 
                                           class="form-control" 
                                           required 
                                           maxlength="100"
                                           value="<?php echo htmlspecialchars($game['platform']); ?>" 
                                           placeholder="bijv. PC, PlayStation 5, Xbox Series X">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Gescheiden door komma's voor meerdere platforms
                                    </div>
                                    <div class="invalid-feedback">
                                        Platform informatie is verplicht.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="developer" class="form-label fw-semibold">
                                        <i class="bi bi-building me-1"></i>
                                        Ontwikkelaar *
                                    </label>
                                    <input type="text" 
                                           id="developer" 
                                           name="developer" 
                                           class="form-control" 
                                           required 
                                           maxlength="100"
                                           value="<?php echo htmlspecialchars($game['developer']); ?>" 
                                           placeholder="bijv. Epic Games, Valve Corporation">
                                    <div class="invalid-feedback">
                                        Ontwikkelaar naam is verplicht.
                                    </div>
                                </div>
                            </div>

                            <!-- Game Specifications Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3 border-bottom border-primary pb-2">
                                        <i class="bi bi-gear me-2"></i>
                                        Game Specificaties
                                    </h5>
                                </div>
                            </div>

                            <!-- Release Year and Rating -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="release_year" class="form-label fw-semibold">
                                        <i class="bi bi-calendar me-1"></i>
                                        Release Jaar *
                                    </label>
                                    <input type="number" 
                                           id="release_year" 
                                           name="release_year" 
                                           class="form-control" 
                                           required 
                                           min="1970" 
                                           max="<?php echo date('Y') + 2; ?>" 
                                           value="<?php echo htmlspecialchars($game['release_year']); ?>">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Van 1970 tot <?php echo date('Y') + 2; ?>
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldig release jaar in.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="rating" class="form-label fw-semibold">
                                        <i class="bi bi-shield-check me-1"></i>
                                        Leeftijdsrating *
                                    </label>
                                    <select id="rating" name="rating" class="form-select" required>
                                        <?php foreach ($rating_descriptions as $value => $description): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" 
                                                    <?php echo ($game['rating'] === $value) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($description); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Selecteer een geldige leeftijdsrating.
                                    </div>
                                </div>
                            </div>

                            <!-- Player Count and Session Time -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="min_players" class="form-label fw-semibold">
                                        <i class="bi bi-person me-1"></i>
                                        Min. Spelers *
                                    </label>
                                    <input type="number" 
                                           id="min_players" 
                                           name="min_players" 
                                           class="form-control" 
                                           required 
                                           min="1" 
                                           max="1000"
                                           value="<?php echo htmlspecialchars($game['min_players']); ?>">
                                    <div class="invalid-feedback">
                                        Minimum spelers moet tussen 1-1000 liggen.
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="max_players" class="form-label fw-semibold">
                                        <i class="bi bi-people me-1"></i>
                                        Max. Spelers *
                                    </label>
                                    <input type="number" 
                                           id="max_players" 
                                           name="max_players" 
                                           class="form-control" 
                                           required 
                                           min="1" 
                                           max="10000"
                                           value="<?php echo htmlspecialchars($game['max_players']); ?>">
                                    <div class="invalid-feedback">
                                        Maximum spelers moet tussen 1-10000 liggen.
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="average_session_time" class="form-label fw-semibold">
                                        <i class="bi bi-clock me-1"></i>
                                        Sessietijd (min) *
                                    </label>
                                    <input type="number" 
                                           id="average_session_time" 
                                           name="average_session_time" 
                                           class="form-control" 
                                           required 
                                           min="1" 
                                           max="1440"
                                           value="<?php echo htmlspecialchars($game['average_session_time']); ?>">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Gemiddelde speeltijd in minuten
                                    </div>
                                    <div class="invalid-feedback">
                                        Sessietijd moet tussen 1-1440 minuten liggen.
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Image URL -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="image_url" class="form-label fw-semibold">
                                        <i class="bi bi-image me-1"></i>
                                        Afbeelding URL (optioneel)
                                    </label>
                                    <input type="url" 
                                           id="image_url" 
                                           name="image_url" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($game['image_url'] ?? ''); ?>" 
                                           placeholder="https://example.com/game-image.jpg">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Optioneel: URL naar een afbeelding van de game
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldige URL in (bijv. https://example.com/image.jpg).
                                    </div>
                                </div>
                            </div>

                            <!-- Form Action Buttons -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="games.php" class="btn btn-outline-secondary btn-lg me-md-2">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Annuleren
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-check-circle me-2"></i>
                                            Wijzigingen Opslaan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Game Preview Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-eye me-2"></i>
                            Game Preview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="text-primary"><?php echo htmlspecialchars($game['titel']); ?></h6>
                                <p class="text-muted mb-2">
                                    <span class="badge bg-primary me-2"><?php echo htmlspecialchars($game['genre']); ?></span>
                                    <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($game['platform']); ?></span>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($game['rating']); ?></span>
                                </p>
                                <p class="mb-2"><?php echo htmlspecialchars($game['description']); ?></p>
                                <small class="text-muted">
                                    <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($game['developer']); ?> • 
                                    <i class="bi bi-calendar me-1"></i><?php echo htmlspecialchars($game['release_year']); ?> • 
                                    <i class="bi bi-people me-1"></i><?php echo htmlspecialchars($game['min_players']); ?>-<?php echo htmlspecialchars($game['max_players']); ?> spelers •
                                    <i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($game['average_session_time']); ?> min
                                </small>
                            </div>
                            <?php if (!empty($game['image_url'])): ?>
                                <div class="col-md-4">
                                    <img src="<?php echo htmlspecialchars($game['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($game['titel']); ?>" 
                                         class="img-fluid rounded shadow-sm"
                                         style="max-height: 200px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Professional JavaScript Enhancement -->
    <script>
        /**
         * GamePlan Scheduler - Advanced Game Edit Form Validation
         * Professional client-side validation with real-time feedback
         */
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize form validation and enhancements
            initializeFormValidation();
            initializeCharacterCounters();
            initializePlayerValidation();
            initializeRealTimeValidation();
        });

        /**
         * Advanced form validation with comprehensive checks
         * @param {HTMLFormElement} form - The form to validate
         * @returns {boolean} - Validation result
         */
        function validateGameForm(form) {
            let isValid = true;
            const errors = [];

            // Title validation with comprehensive checks
            const title = form.title.value.trim();
            if (!title) {
                errors.push('Titel is verplicht.');
                isValid = false;
            } else if (title.length < 2) {
                errors.push('Titel moet minimaal 2 karakters bevatten.');
                isValid = false;
            } else if (title.length > 100) {
                errors.push('Titel mag maximaal 100 karakters bevatten.');
                isValid = false;
            } else if (/^\s+$/.test(title)) {
                errors.push('Titel mag niet alleen uit spaties bestaan.');
                isValid = false;
            }

            // Description validation
            const description = form.description.value.trim();
            if (!description) {
                errors.push('Beschrijving is verplicht.');
                isValid = false;
            } else if (description.length < 10) {
                errors.push('Beschrijving moet minimaal 10 karakters bevatten.');
                isValid = false;
            } else if (description.length > 1000) {
                errors.push('Beschrijving mag maximaal 1000 karakters bevatten.');
                isValid = false;
            }

            // Genre validation
            if (!form.genre.value) {
                errors.push('Selecteer een genre.');
                isValid = false;
            }

            // Platform validation
            const platform = form.platform.value.trim();
            if (!platform) {
                errors.push('Platform is verplicht.');
                isValid = false;
            } else if (platform.length > 100) {
                errors.push('Platform mag maximaal 100 karakters bevatten.');
                isValid = false;
            }

            // Developer validation
            const developer = form.developer.value.trim();
            if (!developer) {
                errors.push('Ontwikkelaar is verplicht.');
                isValid = false;
            } else if (developer.length > 100) {
                errors.push('Ontwikkelaar naam mag maximaal 100 karakters bevatten.');
                isValid = false;
            }

            // Release year validation
            const releaseYear = parseInt(form.release_year.value);
            const currentYear = new Date().getFullYear();
            if (!releaseYear || releaseYear < 1970 || releaseYear > currentYear + 2) {
                errors.push(`Release jaar moet tussen 1970 en ${currentYear + 2} liggen.`);
                isValid = false;
            }

            // Player count validation
            const minPlayers = parseInt(form.min_players.value);
            const maxPlayers = parseInt(form.max_players.value);
            
            if (!minPlayers || minPlayers < 1 || minPlayers > 1000) {
                errors.push('Minimum aantal spelers moet tussen 1 en 1000 liggen.');
                isValid = false;
            }
            
            if (!maxPlayers || maxPlayers < 1 || maxPlayers > 10000) {
                errors.push('Maximum aantal spelers moet tussen 1 en 10000 liggen.');
                isValid = false;
            }
            
            if (minPlayers && maxPlayers && minPlayers > maxPlayers) {
                errors.push('Minimum aantal spelers kan niet groter zijn dan maximum aantal spelers.');
                isValid = false;
            }

            // Session time validation
            const sessionTime = parseInt(form.average_session_time.value);
            if (!sessionTime || sessionTime < 1 || sessionTime > 1440) {
                errors.push('Gemiddelde sessietijd moet tussen 1 en 1440 minuten liggen.');
                isValid = false;
            }

            // Rating validation
            if (!form.rating.value) {
                errors.push('Selecteer een leeftijdsrating.');
                isValid = false;
            }

            // Image URL validation (if provided)
            const imageUrl = form.image_url.value.trim();
            if (imageUrl && !isValidUrl(imageUrl)) {
                errors.push('Voer een geldige URL in voor de afbeelding.');
                isValid = false;
            }

            // Display validation errors if any
            if (!isValid) {
                showValidationErrors(errors);
            }

            return isValid;
        }

        /**
         * Initialize comprehensive form validation
         */
        function initializeFormValidation() {
            const form = document.querySelector('form');
            
            // Add Bootstrap validation classes
            form.classList.add('needs-validation');
            
            // Prevent default browser validation
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity() || !validateGameForm(form)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        }

        /**
         * Initialize character counters for text fields
         */
        function initializeCharacterCounters() {
            const descriptionField = document.getElementById('description');
            const descriptionCounter = document.getElementById('description-count');
            
            if (descriptionField && descriptionCounter) {
                // Update counter on load
                updateCharacterCount();
                
                // Update counter on input
                descriptionField.addEventListener('input', updateCharacterCount);
                
                function updateCharacterCount() {
                    const length = descriptionField.value.length;
                    descriptionCounter.textContent = length;
                    
                    // Add visual feedback
                    if (length > 900) {
                        descriptionCounter.className = 'text-warning fw-bold';
                    } else if (length > 950) {
                        descriptionCounter.className = 'text-danger fw-bold';
                    } else {
                        descriptionCounter.className = 'text-muted';
                    }
                }
            }
        }

        /**
         * Initialize player count validation
         */
        function initializePlayerValidation() {
            const minPlayersField = document.getElementById('min_players');
            const maxPlayersField = document.getElementById('max_players');
            
            if (minPlayersField && maxPlayersField) {
                minPlayersField.addEventListener('input', validatePlayerCounts);
                maxPlayersField.addEventListener('input', validatePlayerCounts);
                
                function validatePlayerCounts() {
                    const minPlayers = parseInt(minPlayersField.value);
                    const maxPlayers = parseInt(maxPlayersField.value);
                    
                    // Clear previous custom validity
                    minPlayersField.setCustomValidity('');
                    maxPlayersField.setCustomValidity('');
                    
                    if (minPlayers && maxPlayers && minPlayers > maxPlayers) {
                        const errorMessage = 'Minimum aantal spelers kan niet groter zijn dan maximum.';
                        minPlayersField.setCustomValidity(errorMessage);
                        maxPlayersField.setCustomValidity(errorMessage);
                    }
                }
            }
        }

        /**
         * Initialize real-time validation feedback
         */
        function initializeRealTimeValidation() {
            const inputs = document.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateSingleField(this);
                });
                
                input.addEventListener('input', function() {
                    // Clear validation state on input
                    this.classList.remove('is-valid', 'is-invalid');
                });
            });
        }

        /**
         * Validate a single form field
         * @param {HTMLElement} field - The field to validate
         */
        function validateSingleField(field) {
            const value = field.value.trim();
            
            // Skip validation for empty optional fields
            if (!value && !field.hasAttribute('required')) {
                field.classList.remove('is-valid', 'is-invalid');
                return;
            }
            
            // Check field validity
            if (field.checkValidity()) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
            }
        }

        /**
         * Validate URL format
         * @param {string} url - URL to validate
         * @returns {boolean} - Validation result
         */
        function isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch (error) {
                return false;
            }
        }

        /**
         * Display validation errors in a professional alert
         * @param {Array} errors - Array of error messages
         */
        function showValidationErrors(errors) {
            if (errors.length === 0) return;
            
            const errorHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Validatiefouten gevonden:
                    </h6>
                    <ul class="mb-0">
                        ${errors.map(error => `<li>${escapeHtml(error)}</li>`).join('')}
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Insert error message at the top of the form
            const form = document.querySelector('form');
            form.insertAdjacentHTML('beforebegin', errorHtml);
            
            // Scroll to top to show errors
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        /**
         * Escape HTML to prevent XSS
         * @param {string} text - Text to escape
         * @returns {string} - Escaped text
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Enhanced form submission with loading state
         */
        function handleFormSubmission() {
            const form = document.querySelector('form');
            const submitButton = form.querySelector('button[type="submit"]');
            
            if (submitButton) {
                form.addEventListener('submit', function() {
                    if (form.checkValidity() && validateGameForm(form)) {
                        // Show loading state
                        submitButton.disabled = true;
                        submitButton.innerHTML = `
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Opslaan...
                        `;
                    }
                });
            }
        }

        // Initialize form submission handling
        document.addEventListener('DOMContentLoaded', handleFormSubmission);
    </script>
</body>
</html>