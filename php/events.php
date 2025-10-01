<?php
/**
 * GamePlan Scheduler - Enhanced Professional Events Management
 * Advanced Events Display with RSVP Functionality and Sorting
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Events System
 */

// Enhanced security and session management
session_start();
require_once 'functions.php';
require_once 'includes/security_headers.php';

// Advanced authentication check with redirect protection
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get current user information with enhanced data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Gebruiker';
$message = '';
$error_message = '';

// Enhanced RSVP response handling with comprehensive validation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rsvp_action'])) {
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $response = filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING);
    
    // Advanced validation for RSVP data
    if (!$event_id || $event_id <= 0) {
        $error_message = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ongeldig evenement geselecteerd.</div>';
    } elseif (!in_array($response, ['pending', 'accepted', 'declined', 'maybe'])) {
        $error_message = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ongeldige RSVP-status geselecteerd.</div>';
    } else {
        // Update RSVP status with enhanced error handling
        if (updateRSVPStatus($event_id, $user_id, $response)) {
            $response_labels = [
                'accepted' => 'Geaccepteerd',
                'declined' => 'Afgewezen', 
                'maybe' => 'Misschien',
                'pending' => 'In behandeling'
            ];
            $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>RSVP status succesvol bijgewerkt naar: ' . htmlspecialchars($response_labels[$response]) . '</div>';
            
            // Log the RSVP action for audit purposes
            logUserActivity($user_id, 'rsvp_response', "Event ID: $event_id, Response: $response");
        } else {
            $error_message = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Fout bij het bijwerken van RSVP status. Probeer het opnieuw.</div>';
        }
    }
}

// Enhanced sorting and filtering options (addressing feedback #1006)
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?? 'date';
$sort_order = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING) ?? 'ASC';
$filter_type = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_STRING) ?? 'all';
$search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';

// Validate sorting parameters
$valid_sorts = ['date', 'time', 'title', 'event_type', 'created_at'];
$valid_orders = ['ASC', 'DESC'];
$valid_filters = ['all', 'upcoming', 'today', 'this_week', 'my_events', 'invited', 'tournament', 'meetup', 'practice', 'stream', 'competition', 'casual'];

if (!in_array($sort_by, $valid_sorts)) $sort_by = 'date';
if (!in_array($sort_order, $valid_orders)) $sort_order = 'ASC';
if (!in_array($filter_type, $valid_filters)) $filter_type = 'all';

// Enhanced events fetching with improved database query and user relationship mapping
try {
    $events = getEventsAdvanced($user_id, $sort_by, $sort_order, $filter_type, $search_query);
    $events_count = count($events);
    
    // Get additional statistics for dashboard overview
    $event_stats = getEventStatistics($user_id);
    
} catch (Exception $e) {
    error_log('Events fetch error: ' . $e->getMessage());
    $error_message = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Fout bij het laden van evenementen. Probeer de pagina te vernieuwen.</div>';
    $events = [];
    $events_count = 0;
    $event_stats = ['total' => 0, 'upcoming' => 0, 'today' => 0, 'invited' => 0];
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evenementen - GamePlan Scheduler</title>
    
    <!-- Enhanced meta tags for better SEO and social sharing -->
    <meta name="description" content="Beheer je gaming evenementen met GamePlan Scheduler - Toernooien, meetups en gaming sessies plannen">
    <meta name="keywords" content="gaming, evenementen, toernooien, planning, scheduler">
    <meta name="author" content="Harsha Kanaparthi">
    
    <!-- Bootstrap 5.3 with integrity check for security -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUa6xKNZjn8SBVZzFWD2S7J5jLBxb5S7P7K5GiYy4yKR9yOgDtG7JiJV3P5Y" crossorigin="anonymous">
    
    <!-- Bootstrap Icons for enhanced UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom professional styling -->
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Additional professional enhancements -->
    <style>
        .events-header {
            background: linear-gradient(135deg, var(--color-gaming-blue) 0%, var(--color-gaming-purple) 100%);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            color: white;
            box-shadow: var(--shadow-gaming);
        }
        
        .events-stats {
            background: var(--color-surface);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            border: 1px solid var(--color-border);
        }
        
        .stat-card {
            background: var(--color-card);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-lg);
            text-align: center;
            border: 1px solid var(--color-border);
            transition: var(--transition-normal);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--color-gaming-blue);
            display: block;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filters-section {
            background: var(--color-surface);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            border: 1px solid var(--color-border);
        }
        
        .event-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius-lg);
            transition: var(--transition-normal);
            overflow: hidden;
            position: relative;
        }
        
        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-gaming-blue), var(--color-gaming-purple));
        }
        
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            border-color: var(--color-gaming-blue);
        }
        
        .event-type-badge {
            font-size: 0.75rem;
            padding: 0.4em 0.8em;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .rsvp-buttons .btn {
            min-width: 80px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .creator-info {
            font-size: 0.875rem;
            color: var(--color-text-muted);
            border-top: 1px solid var(--color-border);
            padding-top: var(--spacing-md);
            margin-top: var(--spacing-md);
        }
        
        .search-section {
            position: relative;
        }
        
        .search-section .form-control {
            padding-left: 2.5rem;
        }
        
        .search-section .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
            z-index: 5;
        }
        
        .no-events {
            text-align: center;
            padding: var(--spacing-3xl);
            color: var(--color-text-muted);
        }
        
        .no-events i {
            font-size: 4rem;
            margin-bottom: var(--spacing-lg);
            opacity: 0.5;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .filters-section .row {
                gap: var(--spacing-md);
            }
            
            .stat-card {
                margin-bottom: var(--spacing-md);
            }
            
            .rsvp-buttons {
                flex-direction: column;
                gap: var(--spacing-sm);
            }
            
            .rsvp-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Enhanced navigation include -->
    <?php include 'includes/navigation.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Professional Events Header -->
        <div class="events-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-calendar-event me-3"></i>
                        Gaming Evenementen
                    </h1>
                    <p class="mb-0 opacity-90">
                        Beheer je gaming evenementen, toernooien en meetups
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                        <a href="add_event.php" class="btn btn-light btn-lg">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nieuw Evenement
                        </a>
                        <a href="index.php" class="btn btn-outline-light">
                            <i class="bi bi-house me-2"></i>
                            Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Statistics Dashboard -->
        <div class="events-stats">
            <h5 class="mb-3">
                <i class="bi bi-graph-up me-2"></i>
                Evenementen Overzicht
            </h5>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $event_stats['total'] ?? 0; ?></span>
                        <span class="stat-label">Totaal Evenementen</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $event_stats['upcoming'] ?? 0; ?></span>
                        <span class="stat-label">Aankomende</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $event_stats['today'] ?? 0; ?></span>
                        <span class="stat-label">Vandaag</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $event_stats['invited'] ?? 0; ?></span>
                        <span class="stat-label">Uitgenodigd</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display messages with enhanced styling -->
        <?php if (!empty($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <?php echo $error_message; ?>
        <?php endif; ?>

        <!-- Enhanced Filters and Search Section (addressing feedback #1006) -->
        <div class="filters-section">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-search me-1"></i>
                        Zoeken
                    </label>
                    <div class="search-section">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control" id="searchInput" 
                               value="<?php echo htmlspecialchars($search_query); ?>" 
                               placeholder="Zoek evenementen...">
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-funnel me-1"></i>
                        Filter
                    </label>
                    <select class="form-select" id="filterSelect">
                        <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>Alle Evenementen</option>
                        <option value="upcoming" <?php echo $filter_type === 'upcoming' ? 'selected' : ''; ?>>Aankomende</option>
                        <option value="today" <?php echo $filter_type === 'today' ? 'selected' : ''; ?>>Vandaag</option>
                        <option value="this_week" <?php echo $filter_type === 'this_week' ? 'selected' : ''; ?>>Deze Week</option>
                        <option value="my_events" <?php echo $filter_type === 'my_events' ? 'selected' : ''; ?>>Mijn Evenementen</option>
                        <option value="invited" <?php echo $filter_type === 'invited' ? 'selected' : ''; ?>>Uitgenodigd</option>
                        <optgroup label="Type">
                            <option value="tournament" <?php echo $filter_type === 'tournament' ? 'selected' : ''; ?>>Toernooien</option>
                            <option value="meetup" <?php echo $filter_type === 'meetup' ? 'selected' : ''; ?>>Meetups</option>
                            <option value="practice" <?php echo $filter_type === 'practice' ? 'selected' : ''; ?>>Oefensessies</option>
                            <option value="stream" <?php echo $filter_type === 'stream' ? 'selected' : ''; ?>>Streams</option>
                            <option value="competition" <?php echo $filter_type === 'competition' ? 'selected' : ''; ?>>Competities</option>
                            <option value="casual" <?php echo $filter_type === 'casual' ? 'selected' : ''; ?>>Casual</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-sort-down me-1"></i>
                        Sorteren op
                    </label>
                    <select class="form-select" id="sortSelect">
                        <option value="date" <?php echo $sort_by === 'date' ? 'selected' : ''; ?>>Datum</option>
                        <option value="time" <?php echo $sort_by === 'time' ? 'selected' : ''; ?>>Tijd</option>
                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Titel</option>
                        <option value="event_type" <?php echo $sort_by === 'event_type' ? 'selected' : ''; ?>>Type</option>
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Aangemaakt</option>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-arrow-up-down me-1"></i>
                        Volgorde
                    </label>
                    <select class="form-select" id="orderSelect">
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oplopend</option>
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Aflopend</option>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <button type="button" class="btn btn-primary w-100" id="applyFilters">
                        <i class="bi bi-funnel-fill me-1"></i>
                        Toepassen
                    </button>
                </div>
            </div>
            
            <!-- Active filters display -->
            <div id="activeFilters" class="mt-2">
                <!-- JavaScript will populate this with active filter tags -->
            </div>
        </div>

        <!-- Events Results Counter -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Evenementen 
                <span class="badge bg-primary ms-2"><?php echo $events_count; ?></span>
            </h5>
            
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="refreshEvents">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Vernieuwen
                </button>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="viewMode" id="cardView" checked>
                    <label class="btn btn-outline-secondary btn-sm" for="cardView">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </label>
                    
                    <input type="radio" class="btn-check" name="viewMode" id="listView">
                    <label class="btn btn-outline-secondary btn-sm" for="listView">
                        <i class="bi bi-list"></i>
                    </label>
                </div>
            </div>
        </div>

        <!-- Enhanced Events Display -->
        <div id="eventsContainer">
            <?php if (empty($events)): ?>
                <div class="no-events">
                    <i class="bi bi-calendar-x"></i>
                    <h4>Geen evenementen gevonden</h4>
                    <p>Er zijn geen evenementen die voldoen aan je zoekfilters.</p>
                    <a href="add_event.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Eerste Evenement Aanmaken
                    </a>
                </div>
            <?php else: ?>
                <div class="row" id="eventsGrid">
                    <?php foreach ($events as $event): ?>
                        <?php
                        // Enhanced event processing with additional metadata
                        $event_date = new DateTime($event['date']);
                        $today = new DateTime();
                        $is_today = $event_date->format('Y-m-d') === $today->format('Y-m-d');
                        $is_past = $event_date < $today;
                        $is_upcoming = $event_date > $today;
                        
                        // Calculate days until event
                        $days_until = $event_date->diff($today)->days;
                        if ($is_past) $days_until = -$days_until;
                        
                        // Get RSVP count for event owner
                        $rsvp_counts = getRSVPCounts($event['event_id']);
                        ?>
                        
                        <div class="col-lg-6 col-xl-4 mb-4 event-item" 
                             data-event-type="<?php echo htmlspecialchars($event['event_type']); ?>"
                             data-event-date="<?php echo htmlspecialchars($event['date']); ?>"
                             data-event-title="<?php echo htmlspecialchars(strtolower($event['title'])); ?>">
                            
                            <div class="event-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="event-type-badge bg-<?php echo getEventTypeBadgeClass($event['event_type']); ?>">
                                                <i class="bi <?php echo getEventTypeIcon($event['event_type']); ?> me-1"></i>
                                                <?php echo ucfirst(htmlspecialchars($event['event_type'])); ?>
                                            </span>
                                            
                                            <?php if ($is_today): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-clock-fill me-1"></i>
                                                    Vandaag
                                                </span>
                                            <?php elseif ($is_upcoming && $days_until <= 7): ?>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-calendar-week me-1"></i>
                                                    Over <?php echo $days_until; ?> dagen
                                                </span>
                                            <?php elseif ($is_past): ?>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    Voltooid
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <small class="text-muted">
                                            <i class="bi bi-person-circle me-1"></i>
                                            Door: <?php echo htmlspecialchars($event['creator_name']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </h5>
                                    
                                    <div class="event-details mb-3">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    <?php echo formatEventDate($event['date']); ?>
                                                </small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo formatEventTime($event['time']); ?>
                                                    <?php if (!empty($event['end_time'])): ?>
                                                        - <?php echo formatEventTime($event['end_time']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <?php if (!empty($event['location']) && $event['location'] !== 'Online'): ?>
                                                <div class="col-12">
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-geo-alt me-1"></i>
                                                        <?php echo htmlspecialchars($event['location']); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($event['max_participants'])): ?>
                                                <div class="col-12">
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-people me-1"></i>
                                                        Max <?php echo $event['max_participants']; ?> deelnemers
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($event['description'])): ?>
                                        <p class="card-text">
                                            <?php echo nl2br(htmlspecialchars(truncateText($event['description'], 100))); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Enhanced RSVP Section for invited users -->
                                    <?php if ($event['user_id'] != $user_id && !empty($event['response_status'])): ?>
                                        <div class="rsvp-section mt-3">
                                            <h6 class="mb-2">
                                                <i class="bi bi-reply me-1"></i>
                                                Jouw Reactie
                                            </h6>
                                            <form method="POST" class="rsvp-form">
                                                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                <input type="hidden" name="rsvp_action" value="1">
                                                
                                                <div class="rsvp-buttons d-flex gap-1">
                                                    <button type="submit" name="response" value="accepted" 
                                                            class="btn btn-sm <?php echo ($event['response_status'] == 'accepted') ? 'btn-success' : 'btn-outline-success'; ?>"
                                                            title="Deelnemen aan evenement">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Ja
                                                    </button>
                                                    <button type="submit" name="response" value="maybe" 
                                                            class="btn btn-sm <?php echo ($event['response_status'] == 'maybe') ? 'btn-warning' : 'btn-outline-warning'; ?>"
                                                            title="Mogelijk deelnemen">
                                                        <i class="bi bi-question-circle me-1"></i>
                                                        Misschien
                                                    </button>
                                                    <button type="submit" name="response" value="declined" 
                                                            class="btn btn-sm <?php echo ($event['response_status'] == 'declined') ? 'btn-danger' : 'btn-outline-danger'; ?>"
                                                            title="Niet deelnemen">
                                                        <i class="bi bi-x-circle me-1"></i>
                                                        Nee
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Enhanced Management Section for event owners -->
                                    <?php if ($event['user_id'] == $user_id): ?>
                                        <div class="management-section mt-3">
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm w-100">
                                                        <i class="bi bi-pencil me-1"></i>
                                                        Bewerken
                                                    </a>
                                                </div>
                                                <div class="col-md-6">
                                                    <a href="delete_event.php?id=<?php echo $event['event_id']; ?>" 
                                                       class="btn btn-outline-danger btn-sm w-100"
                                                       onclick="return confirm('Weet je zeker dat je dit evenement wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.')">
                                                        <i class="bi bi-trash me-1"></i>
                                                        Verwijderen
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Enhanced Footer with RSVP Stats for event owners -->
                                <?php if ($event['user_id'] == $user_id): ?>
                                    <div class="card-footer">
                                        <small class="text-muted">
                                            <i class="bi bi-people me-1"></i>
                                            Reacties: <?php echo getEventResponseCounts($event['event_id']); ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="creator-info">
                                        <small class="text-muted">
                                            <i class="bi bi-clock-history me-1"></i>
                                            Aangemaakt op <?php echo formatEventDate($event['created_at']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Enhanced Pagination (if needed for large datasets) -->
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Evenementen paginering">
                <!-- Pagination will be added here if needed -->
            </nav>
        </div>
    </div>

    <!-- Enhanced Professional JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- Custom Enhanced JavaScript for Events Management -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced filter and search functionality
            const searchInput = document.getElementById('searchInput');
            const filterSelect = document.getElementById('filterSelect');
            const sortSelect = document.getElementById('sortSelect');
            const orderSelect = document.getElementById('orderSelect');
            const applyFiltersBtn = document.getElementById('applyFilters');
            const refreshBtn = document.getElementById('refreshEvents');
            
            // Advanced search with debouncing for better performance
            let searchTimeout;
            searchInput?.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 500);
            });
            
            // Filter change handlers
            [filterSelect, sortSelect, orderSelect].forEach(element => {
                element?.addEventListener('change', applyFilters);
            });
            
            applyFiltersBtn?.addEventListener('click', applyFilters);
            refreshBtn?.addEventListener('click', refreshEvents);
            
            // Enhanced filter application with URL state management
            function applyFilters() {
                const searchValue = searchInput?.value || '';
                const filterValue = filterSelect?.value || 'all';
                const sortValue = sortSelect?.value || 'date';
                const orderValue = orderSelect?.value || 'ASC';
                
                // Build URL with current filters
                const params = new URLSearchParams();
                if (searchValue) params.append('search', searchValue);
                if (filterValue !== 'all') params.append('filter', filterValue);
                if (sortValue !== 'date') params.append('sort', sortValue);
                if (orderValue !== 'ASC') params.append('order', orderValue);
                
                // Update URL and reload with new filters
                const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.location.href = newUrl;
            }
            
            // Enhanced refresh functionality
            function refreshEvents() {
                const refreshIcon = refreshBtn?.querySelector('i');
                if (refreshIcon) {
                    refreshIcon.classList.add('fa-spin');
                    refreshBtn.disabled = true;
                }
                
                // Maintain current filters on refresh
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
            
            // Enhanced RSVP form handling with loading states
            document.querySelectorAll('.rsvp-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = e.submitter;
                    if (submitBtn) {
                        // Add loading state
                        const originalContent = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Laden...';
                        submitBtn.disabled = true;
                        
                        // Re-enable after submission (in case of validation errors)
                        setTimeout(() => {
                            submitBtn.innerHTML = originalContent;
                            submitBtn.disabled = false;
                        }, 3000);
                    }
                });
            });
            
            // Enhanced view mode switching
            const cardViewBtn = document.getElementById('cardView');
            const listViewBtn = document.getElementById('listView');
            const eventsGrid = document.getElementById('eventsGrid');
            
            cardViewBtn?.addEventListener('change', function() {
                if (this.checked && eventsGrid) {
                    eventsGrid.className = 'row';
                    eventsGrid.querySelectorAll('.event-item').forEach(item => {
                        item.className = 'col-lg-6 col-xl-4 mb-4 event-item';
                    });
                }
            });
            
            listViewBtn?.addEventListener('change', function() {
                if (this.checked && eventsGrid) {
                    eventsGrid.className = 'list-group list-group-flush';
                    eventsGrid.querySelectorAll('.event-item').forEach(item => {
                        item.className = 'list-group-item event-item';
                    });
                }
            });
            
            // Enhanced active filters display
            updateActiveFiltersDisplay();
            
            function updateActiveFiltersDisplay() {
                const activeFiltersContainer = document.getElementById('activeFilters');
                if (!activeFiltersContainer) return;
                
                const activeFilters = [];
                const urlParams = new URLSearchParams(window.location.search);
                
                // Build active filter tags
                if (urlParams.has('search') && urlParams.get('search')) {
                    activeFilters.push({
                        type: 'search',
                        label: `Zoeken: "${urlParams.get('search')}"`,
                        value: urlParams.get('search')
                    });
                }
                
                if (urlParams.has('filter') && urlParams.get('filter') !== 'all') {
                    const filterLabels = {
                        'upcoming': 'Aankomende',
                        'today': 'Vandaag',
                        'this_week': 'Deze Week',
                        'my_events': 'Mijn Evenementen',
                        'invited': 'Uitgenodigd',
                        'tournament': 'Toernooien',
                        'meetup': 'Meetups',
                        'practice': 'Oefensessies',
                        'stream': 'Streams',
                        'competition': 'Competities',
                        'casual': 'Casual'
                    };
                    activeFilters.push({
                        type: 'filter',
                        label: `Filter: ${filterLabels[urlParams.get('filter')] || urlParams.get('filter')}`,
                        value: urlParams.get('filter')
                    });
                }
                
                if (urlParams.has('sort') && urlParams.get('sort') !== 'date') {
                    const sortLabels = {
                        'time': 'Tijd',
                        'title': 'Titel',
                        'event_type': 'Type',
                        'created_at': 'Aangemaakt'
                    };
                    activeFilters.push({
                        type: 'sort',
                        label: `Sorteren: ${sortLabels[urlParams.get('sort')] || urlParams.get('sort')}`,
                        value: urlParams.get('sort')
                    });
                }
                
                // Display active filters
                if (activeFilters.length > 0) {
                    const filtersHtml = activeFilters.map(filter => 
                        `<span class="badge bg-secondary me-2 mb-2">
                            ${filter.label}
                            <button type="button" class="btn-close btn-close-white ms-2" 
                                    style="font-size: 0.6em;" 
                                    onclick="removeFilter('${filter.type}', '${filter.value}')"></button>
                        </span>`
                    ).join('');
                    
                    activeFiltersContainer.innerHTML = `
                        <div class="d-flex align-items-center">
                            <small class="text-muted me-2">Actieve filters:</small>
                            ${filtersHtml}
                            <button type="button" class="btn btn-link btn-sm text-decoration-none" onclick="clearAllFilters()">
                                <i class="bi bi-x-circle me-1"></i>Alles wissen
                            </button>
                        </div>
                    `;
                } else {
                    activeFiltersContainer.innerHTML = '';
                }
            }
            
            // Make filter functions globally available
            window.removeFilter = function(type, value) {
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.delete(type);
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.location.href = newUrl;
            };
            
            window.clearAllFilters = function() {
                window.location.href = window.location.pathname;
            };
            
            // Enhanced keyboard shortcuts for power users
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + F to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    searchInput?.focus();
                }
                
                // Escape to clear search
                if (e.key === 'Escape' && searchInput === document.activeElement) {
                    searchInput.value = '';
                    applyFilters();
                }
            });
            
            // Enhanced auto-refresh for real-time updates (optional)
            let autoRefreshInterval;
            const enableAutoRefresh = false; // Set to true if needed
            
            if (enableAutoRefresh) {
                autoRefreshInterval = setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        // Silently refresh data in background
                        fetch(window.location.href + '&ajax=1')
                            .then(response => response.text())
                            .then(html => {
                                // Update events container with new data
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = html;
                                const newEventsContainer = tempDiv.querySelector('#eventsContainer');
                                if (newEventsContainer) {
                                    document.getElementById('eventsContainer').innerHTML = newEventsContainer.innerHTML;
                                }
                            })
                            .catch(error => console.error('Auto-refresh failed:', error));
                    }
                }, 60000); // Refresh every minute
            }
            
            // Cleanup on page unload
            window.addEventListener('beforeunload', function() {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                }
            });
        });
    </script>
</body>
</html>

<?php
/**
 * Enhanced Helper Functions for Events Display
 * Professional utility functions with comprehensive error handling
 */

/**
 * Get appropriate Bootstrap badge class for event type
 * @param string $type Event type
 * @return string Bootstrap badge class
 */
function getEventTypeBadgeClass($type) {
    $badge_classes = [
        'tournament' => 'danger',
        'competition' => 'warning', 
        'practice' => 'info',
        'stream' => 'primary',
        'meetup' => 'success',
        'casual' => 'secondary'
    ];
    return $badge_classes[$type] ?? 'secondary';
}

/**
 * Get appropriate Bootstrap icon for event type
 * @param string $type Event type
 * @return string Bootstrap icon class
 */
function getEventTypeIcon($type) {
    $icon_classes = [
        'tournament' => 'bi-trophy-fill',
        'competition' => 'bi-award-fill',
        'practice' => 'bi-controller',
        'stream' => 'bi-broadcast',
        'meetup' => 'bi-people-fill',
        'casual' => 'bi-dice-3-fill'
    ];
    return $icon_classes[$type] ?? 'bi-calendar-event';
}

/**
 * Enhanced RSVP response counts with comprehensive formatting
 * @param int $event_id Event ID
 * @return string Formatted response counts
 */
function getEventResponseCounts($event_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT response_status, COUNT(*) as count 
            FROM EventUserMap 
            WHERE event_id = :event_id 
            GROUP BY response_status
        ");
        $stmt->execute(['event_id' => $event_id]);
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        $total_responses = 0;
        
        foreach ($counts as $count) {
            $total_responses += $count['count'];
            switch ($count['response_status']) {
                case 'accepted':
                    $result[] = '<span class="text-success fw-bold">' . $count['count'] . ' <i class="bi bi-check-circle-fill"></i></span>';
                    break;
                case 'maybe':
                    $result[] = '<span class="text-warning fw-bold">' . $count['count'] . ' <i class="bi bi-question-circle-fill"></i></span>';
                    break;
                case 'declined':
                    $result[] = '<span class="text-danger fw-bold">' . $count['count'] . ' <i class="bi bi-x-circle-fill"></i></span>';
                    break;
                case 'pending':
                    $result[] = '<span class="text-secondary fw-bold">' . $count['count'] . ' <i class="bi bi-clock-fill"></i></span>';
                    break;
            }
        }
        
        return empty($result) ? 
            '<span class="text-muted">Geen reacties</span>' : 
            implode(' • ', $result) . ' <small class="text-muted">(' . $total_responses . ' totaal)</small>';
            
    } catch (Exception $e) {
        error_log('Error getting RSVP counts: ' . $e->getMessage());
        return '<span class="text-muted">Fout bij laden reacties</span>';
    }
}

/**
 * Enhanced date formatting for events
 * @param string $date Date string
 * @return string Formatted date
 */
function formatEventDate($date) {
    try {
        $date_obj = new DateTime($date);
        $today = new DateTime();
        $tomorrow = new DateTime('+1 day');
        
        if ($date_obj->format('Y-m-d') === $today->format('Y-m-d')) {
            return 'Vandaag';
        } elseif ($date_obj->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
            return 'Morgen';
        } else {
            return $date_obj->format('d-m-Y');
        }
    } catch (Exception $e) {
        return htmlspecialchars($date);
    }
}

/**
 * Enhanced time formatting for events
 * @param string $time Time string
 * @return string Formatted time
 */
function formatEventTime($time) {
    try {
        return date('H:i', strtotime($time));
    } catch (Exception $e) {
        return htmlspecialchars($time);
    }
}

/**
 * Text truncation with word boundary respect
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return string Truncated text
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $truncated = substr($text, 0, $length);
    $last_space = strrpos($truncated, ' ');
    
    if ($last_space !== false) {
        $truncated = substr($truncated, 0, $last_space);
    }
    
    return $truncated . '...';
}

/**
 * Get comprehensive event statistics for dashboard
 * @param int $user_id User ID
 * @return array Event statistics
 */
function getEventStatistics($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN date = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN user_id != :user_id THEN 1 ELSE 0 END) as invited
            FROM Events e
            LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
            WHERE e.user_id = :user_id OR eum.friend_id = :user_id
        ");
        
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0, 
            'upcoming' => 0, 
            'today' => 0, 
            'invited' => 0
        ];
        
    } catch (Exception $e) {
        error_log('Error getting event statistics: ' . $e->getMessage());
        return ['total' => 0, 'upcoming' => 0, 'today' => 0, 'invited' => 0];
    }
}

/**
 * Enhanced events fetching with advanced filtering and sorting
 * @param int $user_id User ID
 * @param string $sort_by Sort field
 * @param string $sort_order Sort order
 * @param string $filter_type Filter type
 * @param string $search_query Search query
 * @return array Events data
 */
function getEventsAdvanced($user_id, $sort_by = 'date', $sort_order = 'ASC', $filter_type = 'all', $search_query = '') {
    global $pdo;
    
    try {
        // Base query with comprehensive joins
        $base_query = "
            SELECT DISTINCT e.*, u.username as creator_name, 
                   eum.response_status, eum.participation_type,
                   s.game_id as linked_game_id
            FROM Events e
            LEFT JOIN Users u ON e.user_id = u.user_id
            LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id AND eum.friend_id = :user_id
            LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
            WHERE (e.user_id = :user_id OR eum.friend_id = :user_id)
        ";
        
        $params = ['user_id' => $user_id];
        
        // Apply filters
        switch ($filter_type) {
            case 'upcoming':
                $base_query .= " AND e.date >= CURDATE()";
                break;
            case 'today':
                $base_query .= " AND e.date = CURDATE()";
                break;
            case 'this_week':
                $base_query .= " AND e.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'my_events':
                $base_query .= " AND e.user_id = :user_id";
                break;
            case 'invited':
                $base_query .= " AND e.user_id != :user_id AND eum.friend_id = :user_id";
                break;
            case 'tournament':
            case 'meetup':
            case 'practice':
            case 'stream':
            case 'competition':
            case 'casual':
                $base_query .= " AND e.event_type = :event_type";
                $params['event_type'] = $filter_type;
                break;
        }
        
        // Apply search
        if (!empty($search_query)) {
            $base_query .= " AND (e.title LIKE :search OR e.description LIKE :search)";
            $params['search'] = '%' . $search_query . '%';
        }
        
        // Apply sorting
        $valid_sorts = ['date', 'time', 'title', 'event_type', 'created_at'];
        $valid_orders = ['ASC', 'DESC'];
        
        if (!in_array($sort_by, $valid_sorts)) $sort_by = 'date';
        if (!in_array($sort_order, $valid_orders)) $sort_order = 'ASC';
        
        $base_query .= " ORDER BY e.{$sort_by} {$sort_order}, e.time ASC";
        
        $stmt = $pdo->prepare($base_query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log('Error fetching events: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get RSVP counts for an event
 * @param int $event_id Event ID  
 * @return array RSVP counts by status
 */
function getRSVPCounts($event_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT response_status, COUNT(*) as count
            FROM EventUserMap 
            WHERE event_id = :event_id 
            GROUP BY response_status
        ");
        $stmt->execute(['event_id' => $event_id]);
        
        $counts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[$row['response_status']] = $row['count'];
        }
        
        return $counts;
        
    } catch (Exception $e) {
        error_log('Error getting RSVP counts: ' . $e->getMessage());
        return [];
    }
}
?>