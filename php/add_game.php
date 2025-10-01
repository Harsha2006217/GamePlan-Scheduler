<?php
/**
 * GamePlan Scheduler - Enhanced Professional Game Management System
 * Advanced Gaming Content Administration with Complete Validation
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Game Addition System
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Enhanced security validation - verify user authentication and admin privileges
if (!isLoggedIn()) {
    header("Location: login.php?error=auth_required");
    exit;
}

// Advanced admin privilege checking with detailed logging
if (!isAdmin()) {
    logSecurityEvent($_SESSION['user_id'], 'unauthorized_admin_access', 'add_game.php');
    header("Location: games.php?error=insufficient_privileges");
    exit;
}

// Professional message handling system
$message = '';
$messageType = '';
$validationErrors = [];

// Enhanced POST request processing with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF token validation for enhanced security
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new SecurityException('Invalid CSRF token');
        }
        
        // Advanced input sanitization and validation
        $gameData = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'description' => sanitizeTextarea($_POST['description'] ?? ''),
            'genre' => sanitizeInput($_POST['genre'] ?? ''),
            'platform' => sanitizeInput($_POST['platform'] ?? ''),
            'release_year' => intval($_POST['release_year'] ?? 0),
            'max_players' => intval($_POST['max_players'] ?? 0),
            'min_players' => intval($_POST['min_players'] ?? 1),
            'average_session_time' => intval($_POST['average_session_time'] ?? 0),
            'rating' => $_POST['rating'] ?? 'E',
            'developer' => sanitizeInput($_POST['developer'] ?? ''),
            'image_url' => sanitizeURL($_POST['image_url'] ?? '')
        ];
        
        // Comprehensive validation with professional error handling
        $validationErrors = validateGameData($gameData);
        
        if (empty($validationErrors)) {
            // Advanced database insertion with transaction management
            $pdo->beginTransaction();
            
            try {
                $gameId = addGameAdvanced($gameData);
                
                if ($gameId) {
                    // Log successful game addition for audit trail
                    logAdminAction($_SESSION['user_id'], 'game_added', [
                        'game_id' => $gameId,
                        'title' => $gameData['title'],
                        'genre' => $gameData['genre']
                    ]);
                    
                    $pdo->commit();
                    
                    // Professional success redirection with detailed feedback
                    $_SESSION['success_message'] = "Game '{$gameData['title']}' succesvol toegevoegd aan de database!";
                    header("Location: games.php?success=game_added&id=" . $gameId);
                    exit;
                } else {
                    throw new DatabaseException('Failed to insert game data');
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
        } else {
            $messageType = 'danger';
            $message = 'Er zijn validatiefouten gevonden. Controleer je invoer en probeer opnieuw.';
        }
        
    } catch (SecurityException $e) {
        logSecurityEvent($_SESSION['user_id'], 'csrf_violation', 'add_game.php');
        $messageType = 'danger';
        $message = 'Beveiligingsfout: ongeldige aanvraag gedetecteerd.';
        
    } catch (DatabaseException $e) {
        error_log("Database error in add_game.php: " . $e->getMessage());
        $messageType = 'danger';
        $message = 'Database fout: het spel kon niet worden toegevoegd. Probeer het later opnieuw.';
        
    } catch (Exception $e) {
        error_log("Unexpected error in add_game.php: " . $e->getMessage());
        $messageType = 'danger';
        $message = 'Er is een onverwachte fout opgetreden. Probeer het later opnieuw.';
    }
}

// Generate CSRF token for form security
$csrfToken = generateCSRFToken();

// Retrieve current year for validation
$currentYear = date('Y');
$maxYear = $currentYear + 2;

// Retrieve available genres for dropdown
$availableGenres = getAvailableGenres();

// Retrieve available platforms for enhanced user experience
$availablePlatforms = getAvailablePlatforms();

// Enhanced page title and meta information
$pageTitle = 'Nieuw Spel Toevoegen - GamePlan Scheduler';
$pageDescription = 'Voeg een nieuw spel toe aan de GamePlan Scheduler database met uitgebreide metadata en validatie.';
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="author" content="Harsha Kanaparthi">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Enhanced Bootstrap and Custom Styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Professional Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    
    <!-- Enhanced Meta Tags for Professional Appearance -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="msapplication-TileColor" content="#0d6efd">
</head>

<body class="bg-dark text-light">
    <!-- Professional Navigation Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Enhanced Loading Overlay -->
    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-none" style="z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Laden...</span>
                </div>
                <p class="text-light">Spel wordt toegevoegd...</p>
            </div>
        </div>
    </div>
    
    <!-- Main Content Container -->
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-xxl-8 col-xl-10 col-lg-11">
                
                <!-- Professional Page Header -->
                <div class="card bg-gradient-gaming shadow-lg border-0 mb-4">
                    <div class="card-header bg-primary bg-opacity-10 border-bottom border-primary">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="h3 mb-0 text-primary">
                                    <i class="bi bi-plus-circle-fill me-2"></i>
                                    Nieuw Spel Toevoegen
                                </h1>
                                <p class="text-muted mb-0 mt-1">
                                    Voeg een nieuw spel toe aan de GamePlan Scheduler database met uitgebreide metadata
                                </p>
                            </div>
                            <div class="col-auto">
                                <a href="games.php" class="btn btn-outline-light">
                                    <i class="bi bi-arrow-left me-1"></i>
                                    Terug naar Spellen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Professional Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-<?php echo $messageType === 'danger' ? 'exclamation-triangle' : 'check-circle'; ?>-fill me-2"></i>
                            <div class="flex-grow-1">
                                <?php echo htmlspecialchars($message); ?>
                                
                                <?php if (!empty($validationErrors)): ?>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($validationErrors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Enhanced Game Addition Form -->
                <div class="card bg-dark border-secondary shadow-lg">
                    <div class="card-header bg-secondary bg-opacity-25 border-bottom border-secondary">
                        <h2 class="h5 mb-0 text-light">
                            <i class="bi bi-controller me-2"></i>
                            Spel Informatie
                        </h2>
                    </div>
                    
                    <div class="card-body p-4">
                        <form method="POST" id="addGameForm" novalidate>
                            <!-- CSRF Security Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            
                            <!-- Basic Game Information Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h3 class="h6 text-primary mb-3">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Basis Informatie
                                    </h3>
                                </div>
                                
                                <!-- Game Title -->
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label text-light">
                                        <i class="bi bi-tag me-1"></i>
                                        Spel Titel <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           id="title" 
                                           name="title" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                           required 
                                           maxlength="100"
                                           placeholder="Bijv. Counter-Strike 2"
                                           data-validation="required|max:100|trim">
                                    <div class="form-text text-muted">
                                        Maximaal 100 karakters. Mag geen spaties alleen bevatten.
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldige speltitel in (3-100 karakters).
                                    </div>
                                </div>
                                
                                <!-- Developer -->
                                <div class="col-md-6 mb-3">
                                    <label for="developer" class="form-label text-light">
                                        <i class="bi bi-building me-1"></i>
                                        Ontwikkelaar <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           id="developer" 
                                           name="developer" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           value="<?php echo htmlspecialchars($_POST['developer'] ?? ''); ?>"
                                           required 
                                           maxlength="100"
                                           placeholder="Bijv. Valve Corporation"
                                           data-validation="required|max:100">
                                    <div class="form-text text-muted">
                                        Naam van de game ontwikkelaar of studio.
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldige ontwikkelaar in.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Game Description -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="description" class="form-label text-light">
                                        <i class="bi bi-card-text me-1"></i>
                                        Beschrijving <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="description" 
                                              name="description" 
                                              class="form-control bg-dark text-light border-secondary" 
                                              rows="4" 
                                              required 
                                              maxlength="1000"
                                              placeholder="Voer een uitgebreide beschrijving van het spel in, inclusief gameplay-elementen, doelgroep en speciale functies..."
                                              data-validation="required|max:1000"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    <div class="form-text text-muted">
                                        Uitgebreide beschrijving van het spel (maximaal 1000 karakters).
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een beschrijving in van minimaal 10 karakters.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Game Categories Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h3 class="h6 text-primary mb-3">
                                        <i class="bi bi-grid me-1"></i>
                                        Categorisatie
                                    </h3>
                                </div>
                                
                                <!-- Genre -->
                                <div class="col-md-4 mb-3">
                                    <label for="genre" class="form-label text-light">
                                        <i class="bi bi-collection me-1"></i>
                                        Genre <span class="text-danger">*</span>
                                    </label>
                                    <select id="genre" 
                                            name="genre" 
                                            class="form-select bg-dark text-light border-secondary" 
                                            required
                                            data-validation="required">
                                        <option value="">Selecteer een genre</option>
                                        <?php foreach ($availableGenres as $genre): ?>
                                            <option value="<?php echo htmlspecialchars($genre); ?>" 
                                                    <?php echo ($_POST['genre'] ?? '') === $genre ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($genre); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Selecteer een genre voor het spel.
                                    </div>
                                </div>
                                
                                <!-- Platform -->
                                <div class="col-md-4 mb-3">
                                    <label for="platform" class="form-label text-light">
                                        <i class="bi bi-device-ssd me-1"></i>
                                        Platform <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           id="platform" 
                                           name="platform" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           value="<?php echo htmlspecialchars($_POST['platform'] ?? ''); ?>"
                                           required 
                                           maxlength="200"
                                           placeholder="PC, PlayStation 5, Xbox Series X/S"
                                           data-validation="required|max:200"
                                           list="platformSuggestions">
                                    <datalist id="platformSuggestions">
                                        <?php foreach ($availablePlatforms as $platform): ?>
                                            <option value="<?php echo htmlspecialchars($platform); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                    <div class="form-text text-muted">
                                        Platforms gescheiden door komma's.
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer minimaal één platform in.
                                    </div>
                                </div>
                                
                                <!-- Rating -->
                                <div class="col-md-4 mb-3">
                                    <label for="rating" class="form-label text-light">
                                        <i class="bi bi-star me-1"></i>
                                        Leeftijdsrating <span class="text-danger">*</span>
                                    </label>
                                    <select id="rating" 
                                            name="rating" 
                                            class="form-select bg-dark text-light border-secondary" 
                                            required
                                            data-validation="required">
                                        <option value="">Selecteer rating</option>
                                        <option value="E" <?php echo ($_POST['rating'] ?? 'E') === 'E' ? 'selected' : ''; ?>>
                                            E - Everyone (Iedereen)
                                        </option>
                                        <option value="T" <?php echo ($_POST['rating'] ?? '') === 'T' ? 'selected' : ''; ?>>
                                            T - Teen (13+ jaar)
                                        </option>
                                        <option value="M" <?php echo ($_POST['rating'] ?? '') === 'M' ? 'selected' : ''; ?>>
                                            M - Mature (17+ jaar)
                                        </option>
                                        <option value="AO" <?php echo ($_POST['rating'] ?? '') === 'AO' ? 'selected' : ''; ?>>
                                            AO - Adults Only (18+ jaar)
                                        </option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Selecteer een leeftijdsrating.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Game Specifications Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h3 class="h6 text-primary mb-3">
                                        <i class="bi bi-gear me-1"></i>
                                        Technische Specificaties
                                    </h3>
                                </div>
                                
                                <!-- Release Year -->
                                <div class="col-md-3 mb-3">
                                    <label for="release_year" class="form-label text-light">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        Release Jaar <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           id="release_year" 
                                           name="release_year" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           value="<?php echo intval($_POST['release_year'] ?? $currentYear); ?>"
                                           required 
                                           min="1970" 
                                           max="<?php echo $maxYear; ?>"
                                           data-validation="required|min:1970|max:<?php echo $maxYear; ?>">
                                    <div class="form-text text-muted">
                                        Jaar tussen 1970 en <?php echo $maxYear; ?>.
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldig release jaar in.
                                    </div>
                                </div>
                                
                                <!-- Minimum Players -->
                                <div class="col-md-3 mb-3">
                                    <label for="min_players" class="form-label text-light">
                                        <i class="bi bi-person me-1"></i>
                                        Min. Spelers <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           id="min_players" 
                                           name="min_players" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           value="<?php echo intval($_POST['min_players'] ?? 1); ?>"
                                           required 
                                           min="1" 
                                           max="1000"
                                           data-validation="required|min:1|max:1000">
                                    <div class="form-text text-muted">
                                        Minimum aantal spelers (1-1000).
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldig minimum aantal spelers in.
                                    </div>
                                </div>
                                
                                <!-- Maximum Players -->
                                <div class="col-md-3 mb-3">
                                    <label for="max_players" class="form-label text-light">
                                        <i class="bi bi-people me-1"></i>
                                        Max. Spelers <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           id="max_players" 
                                           name="max_players" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           value="<?php echo intval($_POST['max_players'] ?? 1); ?>"
                                           required 
                                           min="1" 
                                           max="10000"
                                           data-validation="required|min:1|max:10000">
                                    <div class="form-text text-muted">
                                        Maximum aantal spelers (1-10000).
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldig maximum aantal spelers in.
                                    </div>
                                </div>
                                
                                <!-- Average Session Time -->
                                <div class="col-md-3 mb-3">
                                    <label for="average_session_time" class="form-label text-light">
                                        <i class="bi bi-clock me-1"></i>
                                        Gem. Sessie (min) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           id="average_session_time" 
                                           name="average_session_time" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           value="<?php echo intval($_POST['average_session_time'] ?? 30); ?>"
                                           required 
                                           min="5" 
                                           max="1440"
                                           data-validation="required|min:5|max:1440">
                                    <div class="form-text text-muted">
                                        Gemiddelde sessietijd in minuten (5-1440).
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldige sessietijd in.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Optional Media Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h3 class="h6 text-primary mb-3">
                                        <i class="bi bi-image me-1"></i>
                                        Media (Optioneel)
                                    </h3>
                                </div>
                                
                                <!-- Image URL -->
                                <div class="col-12 mb-3">
                                    <label for="image_url" class="form-label text-light">
                                        <i class="bi bi-link-45deg me-1"></i>
                                        Afbeelding URL
                                    </label>
                                    <input type="url" 
                                           id="image_url" 
                                           name="image_url" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>"
                                           maxlength="500"
                                           placeholder="https://example.com/game-image.jpg"
                                           data-validation="url|max:500">
                                    <div class="form-text text-muted">
                                        Optionele URL naar een afbeelding van het spel (HTTPS aanbevolen).
                                    </div>
                                    <div class="invalid-feedback">
                                        Voer een geldige URL in of laat dit veld leeg.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Professional Form Actions -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                        <div class="form-text text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Velden gemarkeerd met <span class="text-danger">*</span> zijn verplicht.
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    onclick="resetForm()">
                                                <i class="bi bi-arrow-clockwise me-1"></i>
                                                Reset
                                            </button>
                                            
                                            <a href="games.php" 
                                               class="btn btn-outline-light">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Annuleren
                                            </a>
                                            
                                            <button type="submit" 
                                                    class="btn btn-primary btn-lg" 
                                                    id="submitBtn">
                                                <i class="bi bi-plus-circle me-1"></i>
                                                Spel Toevoegen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Professional Help Section -->
                <div class="card bg-info bg-opacity-10 border-info mt-4">
                    <div class="card-body">
                        <h3 class="h6 text-info mb-2">
                            <i class="bi bi-lightbulb me-1"></i>
                            Tips voor het Toevoegen van Spellen
                        </h3>
                        <ul class="mb-0 text-muted small">
                            <li>Gebruik duidelijke en specifieke titels voor betere zoekresultaten</li>
                            <li>Voeg uitgebreide beschrijvingen toe voor betere gebruikerservaring</li>
                            <li>Controleer altijd de leeftijdsrating voor de juiste doelgroep</li>
                            <li>Vermeld alle beschikbare platforms gescheiden door komma's</li>
                            <li>De gemiddelde sessietijd helpt spelers bij planning</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Professional Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Enhanced JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    
    <!-- Advanced Form Validation and Enhancement Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';
        
        // Enhanced form validation and user experience
        const form = document.getElementById('addGameForm');
        const submitBtn = document.getElementById('submitBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        // Real-time validation for better user experience
        initializeRealTimeValidation();
        
        // Enhanced form submission handling
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                showLoadingState();
                
                // Simulate processing time for better UX
                setTimeout(() => {
                    form.submit();
                }, 500);
            }
        });
        
        /**
         * Initialize real-time validation for all form fields
         */
        function initializeRealTimeValidation() {
            const fields = form.querySelectorAll('[data-validation]');
            
            fields.forEach(field => {
                // Validate on blur for immediate feedback
                field.addEventListener('blur', function() {
                    validateField(this);
                });
                
                // Validate on input for real-time feedback
                field.addEventListener('input', function() {
                    // Debounce input validation
                    clearTimeout(this.validationTimeout);
                    this.validationTimeout = setTimeout(() => {
                        validateField(this);
                    }, 300);
                });
            });
            
            // Special validation for player count relationship
            const minPlayers = document.getElementById('min_players');
            const maxPlayers = document.getElementById('max_players');
            
            [minPlayers, maxPlayers].forEach(field => {
                field.addEventListener('input', function() {
                    validatePlayerCounts();
                });
            });
        }
        
        /**
         * Validate individual form field with comprehensive rules
         */
        function validateField(field) {
            const rules = field.dataset.validation.split('|');
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';
            
            for (const rule of rules) {
                const [ruleName, ruleValue] = rule.split(':');
                
                switch (ruleName) {
                    case 'required':
                        if (!value) {
                            isValid = false;
                            errorMessage = 'Dit veld is verplicht.';
                        } else if (/^\s+$/.test(field.value)) {
                            isValid = false;
                            errorMessage = 'Dit veld mag niet alleen uit spaties bestaan.';
                        }
                        break;
                        
                    case 'max':
                        if (value.length > parseInt(ruleValue)) {
                            isValid = false;
                            errorMessage = `Maximaal ${ruleValue} karakters toegestaan.`;
                        }
                        break;
                        
                    case 'min':
                        if (field.type === 'number') {
                            if (parseInt(value) < parseInt(ruleValue)) {
                                isValid = false;
                                errorMessage = `Minimum waarde is ${ruleValue}.`;
                            }
                        } else if (value.length > 0 && value.length < parseInt(ruleValue)) {
                            isValid = false;
                            errorMessage = `Minimaal ${ruleValue} karakters vereist.`;
                        }
                        break;
                        
                    case 'url':
                        if (value && !isValidURL(value)) {
                            isValid = false;
                            errorMessage = 'Voer een geldige URL in (bijv. https://example.com).';
                        }
                        break;
                        
                    case 'trim':
                        if (value !== field.value.trim()) {
                            field.value = value;
                        }
                        break;
                }
                
                if (!isValid) break;
            }
            
            // Update field appearance and feedback
            updateFieldValidation(field, isValid, errorMessage);
            
            return isValid;
        }
        
        /**
         * Validate player count relationship
         */
        function validatePlayerCounts() {
            const minPlayers = document.getElementById('min_players');
            const maxPlayers = document.getElementById('max_players');
            const minValue = parseInt(minPlayers.value);
            const maxValue = parseInt(maxPlayers.value);
            
            let isValid = true;
            let errorMessage = '';
            
            if (minValue && maxValue && minValue > maxValue) {
                isValid = false;
                errorMessage = 'Minimum spelers kan niet groter zijn dan maximum spelers.';
                updateFieldValidation(maxPlayers, false, errorMessage);
                updateFieldValidation(minPlayers, false, 'Controleer de speler aantallen.');
            } else {
                updateFieldValidation(maxPlayers, true, '');
                updateFieldValidation(minPlayers, true, '');
            }
            
            return isValid;
        }
        
        /**
         * Update field validation appearance
         */
        function updateFieldValidation(field, isValid, errorMessage) {
            const feedbackElement = field.parentNode.querySelector('.invalid-feedback');
            
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
                if (feedbackElement) {
                    feedbackElement.textContent = '';
                }
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                if (feedbackElement) {
                    feedbackElement.textContent = errorMessage;
                }
            }
        }
        
        /**
         * Comprehensive form validation before submission
         */
        function validateForm() {
            let isFormValid = true;
            const fields = form.querySelectorAll('[data-validation]');
            
            // Validate all fields
            fields.forEach(field => {
                if (!validateField(field)) {
                    isFormValid = false;
                }
            });
            
            // Validate player counts relationship
            if (!validatePlayerCounts()) {
                isFormValid = false;
            }
            
            // Show error summary if form is invalid
            if (!isFormValid) {
                showValidationError('Er zijn fouten gevonden in het formulier. Controleer de gemarkeerde velden.');
                
                // Focus first invalid field
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            
            return isFormValid;
        }
        
        /**
         * Show loading state during form submission
         */
        function showLoadingState() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Toevoegen...';
            loadingOverlay.classList.remove('d-none');
        }
        
        /**
         * Show validation error message
         */
        function showValidationError(message) {
            // Create or update error alert
            let alertContainer = document.querySelector('.validation-alert');
            if (!alertContainer) {
                alertContainer = document.createElement('div');
                alertContainer.className = 'alert alert-danger alert-dismissible fade show validation-alert';
                alertContainer.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div class="flex-grow-1">
                            <span class="alert-message">${message}</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                // Insert before form
                form.parentNode.insertBefore(alertContainer, form);
            } else {
                alertContainer.querySelector('.alert-message').textContent = message;
            }
            
            // Scroll to alert
            alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        /**
         * Reset form to initial state
         */
        window.resetForm = function() {
            if (confirm('Weet je zeker dat je alle velden wilt wissen?')) {
                form.reset();
                
                // Remove validation classes
                const fields = form.querySelectorAll('.is-valid, .is-invalid');
                fields.forEach(field => {
                    field.classList.remove('is-valid', 'is-invalid');
                });
                
                // Remove validation alert
                const alert = document.querySelector('.validation-alert');
                if (alert) {
                    alert.remove();
                }
                
                // Focus first field
                const firstField = form.querySelector('input, select, textarea');
                if (firstField) {
                    firstField.focus();
                }
            }
        };
        
        /**
         * Enhanced URL validation
         */
        function isValidURL(string) {
            try {
                const url = new URL(string);
                return url.protocol === 'http:' || url.protocol === 'https:';
            } catch (_) {
                return false;
            }
        }
        
        // Initialize character counters for text fields
        initializeCharacterCounters();
        
        /**
         * Initialize character counters for better UX
         */
        function initializeCharacterCounters() {
            const textFields = form.querySelectorAll('input[maxlength], textarea[maxlength]');
            
            textFields.forEach(field => {
                const maxLength = parseInt(field.getAttribute('maxlength'));
                const counter = document.createElement('small');
                counter.className = 'text-muted character-counter';
                
                // Insert counter after field
                field.parentNode.appendChild(counter);
                
                // Update counter on input
                function updateCounter() {
                    const remaining = maxLength - field.value.length;
                    counter.textContent = `${field.value.length}/${maxLength} karakters`;
                    
                    if (remaining < 50) {
                        counter.className = 'text-warning character-counter';
                    } else if (remaining < 10) {
                        counter.className = 'text-danger character-counter';
                    } else {
                        counter.className = 'text-muted character-counter';
                    }
                }
                
                field.addEventListener('input', updateCounter);
                updateCounter(); // Initial count
            });
        }
        
        console.log('Enhanced Game Addition Form initialized successfully');
    });
    </script>
</body>
</html>