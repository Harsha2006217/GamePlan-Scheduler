<?php
/**
 * Advanced Events Management System
 * GamePlan Scheduler - Professional Gaming Event Listing
 * 
 * This module displays comprehensive event listings with sorting,
 * filtering, and advanced management features for gaming events.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

session_start();
require 'functions.php';

// Advanced security check with session validation
if (!isLoggedIn() || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Advanced filtering and sorting parameters
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?? 'date';
$sort_order = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING) ?? 'ASC';
$filter_type = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_STRING) ?? 'all';
$search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';

// Validate sorting parameters
$valid_sorts = ['date', 'title', 'type', 'reminder', 'participants'];
$valid_orders = ['ASC', 'DESC'];
if (!in_array($sort_by, $valid_sorts)) $sort_by = 'date';
if (!in_array($sort_order, $valid_orders)) $sort_order = 'ASC';

try {
    // Get events with advanced filtering
    $events = getEventsWithFiltering($user_id, $sort_by, $sort_order, $filter_type, $search_query);
    
    // Get user statistics for dashboard
    $user_stats = getUserEventStatistics($user_id);
    
} catch (Exception $e) {
    error_log("Error loading events in events.php: " . $e->getMessage());
    $events = [];
    $user_stats = [
        'total_events' => 0,
        'upcoming_events' => 0,
        'past_events' => 0,
        'shared_events' => 0
    ];
}

// Process success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evenementen Overzicht - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body class="bg-dark text-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-gamepad"></i> GamePlan</a>
            <div class="navbar-nav ms-auto">
                <a href="add_event.php" class="btn btn-success me-2">Nieuw Evenement</a>
                <a href="logout.php" class="btn btn-outline-light">Uitloggen</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <!-- Page Header with Statistics -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-5 mb-3">
                    <i class="fas fa-calendar-star me-3"></i>
                    Gaming Evenementen
                </h1>
                <p class="lead">Beheer je gaming evenementen, toernooien en meetups</p>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h3 class="mb-0"><?php echo $user_stats['total_events']; ?></h3>
                                <small>Totaal Evenementen</small>
                            </div>
                            <div class="col-6">
                                <h3 class="mb-0"><?php echo $user_stats['upcoming_events']; ?></h3>
                                <small>Aankomend</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
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
        
        <!-- Controls and Filters -->
        <div class="card bg-secondary mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <a href="add_event.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>
                            Nieuw Evenement
                        </a>
                    </div>
                    <div class="col-md-8">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="date" <?php echo ($sort_by === 'date') ? 'selected' : ''; ?>>
                                        <i class="fas fa-calendar"></i> Datum
                                    </option>
                                    <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>
                                        <i class="fas fa-font"></i> Titel
                                    </option>
                                    <option value="type" <?php echo ($sort_by === 'type') ? 'selected' : ''; ?>>
                                        <i class="fas fa-tags"></i> Type
                                    </option>
                                    <option value="participants" <?php echo ($sort_by === 'participants') ? 'selected' : ''; ?>>
                                        <i class="fas fa-users"></i> Deelnemers
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="order" class="form-select" onchange="this.form.submit()">
                                    <option value="ASC" <?php echo ($sort_order === 'ASC') ? 'selected' : ''; ?>>
                                        <i class="fas fa-sort-up"></i> Oplopend
                                    </option>
                                    <option value="DESC" <?php echo ($sort_order === 'DESC') ? 'selected' : ''; ?>>
                                        <i class="fas fa-sort-down"></i> Aflopend
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="filter" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo ($filter_type === 'all') ? 'selected' : ''; ?>>Alle Evenementen</option>
                                    <option value="upcoming" <?php echo ($filter_type === 'upcoming') ? 'selected' : ''; ?>>Aankomend</option>
                                    <option value="past" <?php echo ($filter_type === 'past') ? 'selected' : ''; ?>>Afgelopen</option>
                                    <option value="tournament" <?php echo ($filter_type === 'tournament') ? 'selected' : ''; ?>>Tournaments</option>
                                    <option value="meetup" <?php echo ($filter_type === 'meetup') ? 'selected' : ''; ?>>Meetups</option>
                                    <option value="shared" <?php echo ($filter_type === 'shared') ? 'selected' : ''; ?>>Gedeeld</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Zoeken..." 
                                           value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-outline-light" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Events Table -->
        <?php if (!empty($events)): ?>
            <div class="card bg-secondary">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Evenementen Overzicht (<?php echo count($events); ?> evenementen)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>
                                        <i class="fas fa-trophy me-2"></i>Titel
                                    </th>
                                    <th>
                                        <i class="fas fa-tags me-2"></i>Type
                                    </th>
                                    <th>
                                        <i class="fas fa-calendar me-2"></i>Datum & Tijd
                                    </th>
                                    <th>
                                        <i class="fas fa-align-left me-2"></i>Beschrijving
                                    </th>
                                    <th>
                                        <i class="fas fa-bell me-2"></i>Herinnering
                                    </th>
                                    <th>
                                        <i class="fas fa-share me-2"></i>Gedeeld met
                                    </th>
                                    <th>
                                        <i class="fas fa-cogs me-2"></i>Acties
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <?php
                                    $event_datetime = strtotime($event['date'] . ' ' . $event['time']);
                                    $is_upcoming = $event_datetime > time();
                                    $is_today = date('Y-m-d') === $event['date'];
                                    $row_class = $is_today ? 'table-warning' : ($is_upcoming ? '' : 'table-secondary');
                                    ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!$is_upcoming): ?>
                                                    <i class="fas fa-history text-muted me-2"></i>
                                                <?php elseif ($is_today): ?>
                                                    <i class="fas fa-clock text-warning me-2"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-calendar-plus text-success me-2"></i>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                                    <?php if (isset($event['max_participants']) && $event['max_participants'] > 0): ?>
                                                        <br><small class="text-muted">Max <?php echo $event['max_participants']; ?> deelnemers</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $type_icons = [
                                                'tournament' => 'fas fa-trophy text-warning',
                                                'meetup' => 'fas fa-users text-info',
                                                'streaming' => 'fas fa-video text-danger',
                                                'practice' => 'fas fa-dumbbell text-success',
                                                'other' => 'fas fa-gamepad text-primary'
                                            ];
                                            $type_labels = [
                                                'tournament' => 'Tournament',
                                                'meetup' => 'Meetup',
                                                'streaming' => 'Streaming',
                                                'practice' => 'Practice',
                                                'other' => 'Andere'
                                            ];
                                            $event_type = $event['event_type'] ?? 'other';
                                            ?>
                                            <span class="badge bg-secondary">
                                                <i class="<?php echo $type_icons[$event_type]; ?> me-1"></i>
                                                <?php echo $type_labels[$event_type]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('j M Y', strtotime($event['date'])); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('H:i', strtotime($event['time'])); ?>
                                            </div>
                                            <?php if ($is_today): ?>
                                                <small class="text-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Vandaag!
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($event['description'])): ?>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($event['description']); ?>">
                                                    <?php echo htmlspecialchars(substr($event['description'], 0, 50)); ?>
                                                    <?php if (strlen($event['description']) > 50): ?>...<?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">Geen beschrijving</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($event['reminder'])): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-bell me-1"></i>
                                                    <?php echo htmlspecialchars($event['reminder']); ?>
                                                </span>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-bell-slash me-1"></i>Geen
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($event['shared_with'])): ?>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php 
                                                    $shared_count = count($event['shared_with']);
                                                    $display_limit = 3;
                                                    ?>
                                                    <?php for ($i = 0; $i < min($shared_count, $display_limit); $i++): ?>
                                                        <span class="badge bg-primary" title="<?php echo htmlspecialchars($event['shared_with'][$i]['username']); ?>">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?php echo htmlspecialchars(substr($event['shared_with'][$i]['username'], 0, 8)); ?>
                                                        </span>
                                                    <?php endfor; ?>
                                                    <?php if ($shared_count > $display_limit): ?>
                                                        <span class="badge bg-secondary">
                                                            +<?php echo ($shared_count - $display_limit); ?> meer
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-lock me-1"></i>Privé
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Bewerken">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_event.php?id=<?php echo $event['event_id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Verwijderen"
                                                   onclick="return confirm('Weet je zeker dat je dit evenement wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="card bg-secondary">
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-times text-muted" style="font-size: 4rem;"></i>
                    <h3 class="mt-3">Geen evenementen gevonden</h3>
                    <?php if (!empty($search_query) || $filter_type !== 'all'): ?>
                        <p class="text-muted">
                            Geen evenementen gevonden met de huidige filters. 
                            <a href="events.php" class="text-decoration-none">Toon alle evenementen</a>
                        </p>
                    <?php else: ?>
                        <p class="text-muted mb-4">
                            Begin met het creëren van je eerste gaming evenement om je planning te organiseren.
                        </p>
                        <a href="add_event.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>
                            Eerste Evenement Maken
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-lightbulb me-2"></i>Tips voor evenementen</h5>
                        <ul class="mb-0">
                            <li>Gebruik herinneringen om niets te missen</li>
                            <li>Deel evenementen met vrienden voor meer plezier</li>
                            <li>Koppel evenementen aan gaming schema's</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-chart-line me-2"></i>Jouw statistieken</h5>
                        <div class="row">
                            <div class="col-6">
                                <small>Gedeelde evenementen:</small>
                                <div class="h4"><?php echo $user_stats['shared_events']; ?></div>
                            </div>
                            <div class="col-6">
                                <small>Afgelopen evenementen:</small>
                                <div class="h4"><?php echo $user_stats['past_events']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-white text-center p-3 mt-5">
        <div class="container">
            <p class="mb-0">
                © 2025 GamePlan Scheduler door Harsha Kanaparthi | 
                <a href="privacy.php" class="text-white text-decoration-none">Privacybeleid</a> | 
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    
    <script>
        // Auto-submit form when filters change
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Add row hover effects
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.transition = 'all 0.2s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>