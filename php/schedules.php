<?php
session_start();
require 'functions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Enhanced Sorting functionality - Fix #1006
$sort_by = $_GET['sort'] ?? 'date';
$order = $_GET['order'] ?? 'ASC';
$filter_game = $_GET['filter_game'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

$allowed_sorts = ['date', 'time', 'game_titel', 'created_at'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'date';
if (!in_array($order, $allowed_orders)) $order = 'ASC';

// Get schedules with advanced sorting and filtering
global $pdo;
$where_conditions = ["s.user_id = ?"];
$params = [$user_id];

// Add filters
if (!empty($filter_game)) {
    $where_conditions[] = "g.titel LIKE ?";
    $params[] = "%$filter_game%";
}

if (!empty($filter_date)) {
    $where_conditions[] = "DATE(s.date) = ?";
    $params[] = $filter_date;
}

$where_clause = implode(" AND ", $where_conditions);

$stmt = $pdo->prepare("SELECT s.*, g.titel as game_titel, g.genre, g.description,
                      COUNT(DISTINCT f.friend_id) as friend_count,
                      GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as friend_names
                      FROM Schedules s 
                      LEFT JOIN Games g ON s.game_id = g.game_id 
                      LEFT JOIN ScheduleFriends sf ON s.schedule_id = sf.schedule_id
                      LEFT JOIN Friends f ON sf.friend_id = f.friend_id
                      LEFT JOIN Users u ON f.friend_user_id = u.user_id
                      WHERE $where_clause
                      GROUP BY s.schedule_id
                      ORDER BY " . $sort_by . " " . $order . ", s.time ASC");
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// Get filter options
$stmt = $pdo->prepare("SELECT DISTINCT g.titel FROM Schedules s 
                      LEFT JOIN Games g ON s.game_id = g.game_id 
                      WHERE s.user_id = ? ORDER BY g.titel");
$stmt->execute([$user_id]);
$game_options = $stmt->fetchAll();

// Get schedule statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_schedules,
    COUNT(DISTINCT DATE(date)) as unique_dates,
    COUNT(DISTINCT game_id) as unique_games,
    MIN(date) as earliest_date,
    MAX(date) as latest_date
    FROM Schedules WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Success/Error Messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema's - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
    
    <style>
        .schedule-card {
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }
        
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .schedule-card.past {
            opacity: 0.7;
            border-left-color: #6c757d;
        }
        
        .schedule-card.today {
            border-left-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
        }
        
        .schedule-card.upcoming {
            border-left-color: #ffc107;
        }
        
        .filter-section {
            background: rgba(248, 249, 250, 0.8);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 15px;
        }
        
        .view-toggle {
            background: white;
            border: 2px solid #007bff;
            border-radius: 25px;
            padding: 5px;
        }
        
        .view-toggle .btn {
            border-radius: 20px;
            border: none;
            padding: 8px 16px;
        }
        
        .view-toggle .btn.active {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
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

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-6">
                    <i class="fas fa-calendar-alt me-3 text-primary"></i>
                    Gaming Schema's
                </h1>
                <p class="text-muted">Plan en beheer je gaming sessies met vrienden</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="add_schedule.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Nieuw Schema
                </a>
                <div class="btn-group ms-2" role="group">
                    <button type="button" class="btn btn-outline-secondary" onclick="exportSchedules()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-upload me-1"></i>Import
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <h3><?php echo $stats['total_schedules'] ?? 0; ?></h3>
                        <p class="mb-0">Totaal Schema's</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-gamepad fa-2x mb-2"></i>
                        <h3><?php echo $stats['unique_games'] ?? 0; ?></h3>
                        <p class="mb-0">Verschillende Games</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <h3><?php echo $stats['unique_dates'] ?? 0; ?></h3>
                        <p class="mb-0">Unieke Dagen</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h3><?php echo count($schedules) > 0 ? array_sum(array_column($schedules, 'friend_count')) : 0; ?></h3>
                        <p class="mb-0">Vrienden Uitgenodigd</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="filter-section">
            <form method="GET" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-gamepad me-1"></i>Filter op Game
                        </label>
                        <select name="filter_game" class="form-select">
                            <option value="">Alle Games</option>
                            <?php foreach ($game_options as $game): ?>
                                <option value="<?php echo htmlspecialchars($game['titel']); ?>" 
                                        <?php echo $filter_game === $game['titel'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($game['titel']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-calendar me-1"></i>Filter op Datum
                        </label>
                        <input type="date" name="filter_date" class="form-select" value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-sort me-1"></i>Sorteer Op
                        </label>
                        <select name="sort" class="form-select">
                            <option value="date" <?php echo $sort_by === 'date' ? 'selected' : ''; ?>>Datum</option>
                            <option value="time" <?php echo $sort_by === 'time' ? 'selected' : ''; ?>>Tijd</option>
                            <option value="game_titel" <?php echo $sort_by === 'game_titel' ? 'selected' : ''; ?>>Game</option>
                            <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Aangemaakt</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-arrow-up me-1"></i>Volgorde
                        </label>
                        <div class="btn-group d-block">
                            <input type="hidden" name="order" value="<?php echo $order; ?>">
                            <button type="button" class="btn btn-outline-primary <?php echo $order === 'ASC' ? 'active' : ''; ?>" 
                                    onclick="setOrder('ASC')">
                                <i class="fas fa-arrow-up"></i> Oplopend
                            </button>
                            <button type="button" class="btn btn-outline-primary <?php echo $order === 'DESC' ? 'active' : ''; ?>" 
                                    onclick="setOrder('DESC')">
                                <i class="fas fa-arrow-down"></i> Aflopend
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i>Filter Toepassen
                        </button>
                        <a href="schedules.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Wissen
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- View Toggle -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="view-toggle">
                <button class="btn active" id="cardView" onclick="switchView('card')">
                    <i class="fas fa-th-large me-1"></i>Kaarten
                </button>
                <button class="btn" id="listView" onclick="switchView('list')">
                    <i class="fas fa-list me-1"></i>Lijst
                </button>
                <button class="btn" id="calendarView" onclick="switchView('calendar')">
                    <i class="fas fa-calendar me-1"></i>Kalender
                </button>
            </div>
            <div class="text-muted">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    <?php echo count($schedules); ?> schema's gevonden
                </small>
            </div>
        </div>

        <!-- Schedules Display -->
        <div id="schedulesContainer">
            <?php if (!empty($schedules)): ?>
                <!-- Card View (Default) -->
                <div id="cardViewContainer" class="row">
                    <?php foreach ($schedules as $schedule): 
                        $schedule_date = strtotime($schedule['date']);
                        $today = strtotime('today');
                        $tomorrow = strtotime('tomorrow');
                        
                        $date_class = '';
                        $date_badge = '';
                        
                        if ($schedule_date < $today) {
                            $date_class = 'past';
                            $date_badge = 'bg-secondary';
                        } elseif ($schedule_date == $today) {
                            $date_class = 'today';
                            $date_badge = 'bg-success';
                        } elseif ($schedule_date == $tomorrow) {
                            $date_class = 'upcoming';
                            $date_badge = 'bg-warning';
                        } else {
                            $date_badge = 'bg-primary';
                        }
                    ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card schedule-card h-100 <?php echo $date_class; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-gamepad text-primary me-2 fa-lg"></i>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($schedule['game_titel']); ?></h6>
                                    </div>
                                    <span class="badge <?php echo $date_badge; ?> rounded-pill">
                                        <?php 
                                        if ($date_class === 'today') echo 'Vandaag';
                                        elseif ($date_class === 'past') echo 'Voorbij';
                                        elseif ($date_class === 'upcoming') echo 'Morgen';
                                        else echo date('j M', $schedule_date);
                                        ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="d-flex align-items-center text-muted">
                                                <i class="fas fa-calendar me-2"></i>
                                                <small><?php echo date('j M Y', strtotime($schedule['date'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex align-items-center text-muted">
                                                <i class="fas fa-clock me-2"></i>
                                                <small><?php echo date('H:i', strtotime($schedule['time'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($schedule['genre'])): ?>
                                        <div class="mb-2">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($schedule['genre']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($schedule['description'])): ?>
                                        <p class="text-muted small mb-3">
                                            <?php echo htmlspecialchars(substr($schedule['description'], 0, 100) . (strlen($schedule['description']) > 100 ? '...' : '')); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="friends-section">
                                        <?php if ($schedule['friend_count'] > 0): ?>
                                            <div class="d-flex align-items-center text-success mb-2">
                                                <i class="fas fa-users me-2"></i>
                                                <small>
                                                    <strong><?php echo $schedule['friend_count']; ?> vrienden</strong>
                                                    uitgenodigd
                                                </small>
                                            </div>
                                            <div class="friend-names">
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($schedule['friend_names']); ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center text-muted">
                                                <i class="fas fa-user me-2"></i>
                                                <small>Solo gaming sessie</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100" role="group">
                                        <a href="view_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Bekijk
                                        </a>
                                        <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                           class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-edit me-1"></i>Bewerk
                                        </a>
                                        <a href="delete_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirmDelete('<?php echo htmlspecialchars($schedule['game_titel']); ?>')">
                                            <i class="fas fa-trash me-1"></i>Verwijder
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- List View (Hidden by default) -->
                <div id="listViewContainer" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-gamepad me-1"></i>Game</th>
                                    <th><i class="fas fa-calendar me-1"></i>Datum</th>
                                    <th><i class="fas fa-clock me-1"></i>Tijd</th>
                                    <th><i class="fas fa-users me-1"></i>Vrienden</th>
                                    <th><i class="fas fa-cogs me-1"></i>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-gamepad text-primary me-2"></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($schedule['game_titel']); ?></strong>
                                                    <?php if (!empty($schedule['genre'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($schedule['genre']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo date('j M Y', strtotime($schedule['date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo date('H:i', strtotime($schedule['time'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($schedule['friend_count'] > 0): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-users me-1"></i>
                                                    <?php echo $schedule['friend_count']; ?> vrienden
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Solo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Bekijk">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                                   class="btn btn-outline-warning" 
                                                   title="Bewerk">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirmDelete('<?php echo htmlspecialchars($schedule['game_titel']); ?>')"
                                                   title="Verwijder">
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

                <!-- Calendar View (Hidden by default) -->
                <div id="calendarViewContainer" class="d-none">
                    <div class="calendar-grid">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Kalender Weergave</strong><br>
                            Hieronder zie je je schema's gerangschikt per datum. Klik op een schema voor meer details.
                        </div>
                        
                        <?php 
                        $grouped_schedules = [];
                        foreach ($schedules as $schedule) {
                            $date_key = date('Y-m-d', strtotime($schedule['date']));
                            if (!isset($grouped_schedules[$date_key])) {
                                $grouped_schedules[$date_key] = [];
                            }
                            $grouped_schedules[$date_key][] = $schedule;
                        }
                        ksort($grouped_schedules);
                        ?>
                        
                        <?php foreach ($grouped_schedules as $date => $day_schedules): ?>
                            <div class="calendar-day mb-4">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-calendar-day me-2 text-primary"></i>
                                    <?php echo date('l j F Y', strtotime($date)); ?>
                                    <span class="badge bg-primary ms-2"><?php echo count($day_schedules); ?> schema's</span>
                                </h5>
                                <div class="row">
                                    <?php foreach ($day_schedules as $schedule): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card border-start border-primary border-3">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($schedule['game_titel']); ?></h6>
                                                        <small class="text-primary fw-bold"><?php echo date('H:i', strtotime($schedule['time'])); ?></small>
                                                    </div>
                                                    <?php if ($schedule['friend_count'] > 0): ?>
                                                        <small class="text-success">
                                                            <i class="fas fa-users me-1"></i>
                                                            <?php echo $schedule['friend_count']; ?> vrienden
                                                        </small>
                                                    <?php endif; ?>
                                                    <div class="mt-2">
                                                        <a href="view_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                                           class="btn btn-sm btn-primary me-1">Details</a>
                                                        <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                                           class="btn btn-sm btn-outline-warning">Bewerk</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                    <h3>Nog geen schema's gepland</h3>
                    <p class="text-muted mb-4">Begin met het plannen van je eerste gaming sessie!</p>
                    <a href="add_schedule.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Eerste Schema Toevoegen
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schema's Importeren</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Selecteer bestand</label>
                            <input type="file" class="form-control" id="importFile" accept=".csv,.json">
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Ondersteunde formaten: CSV, JSON
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                    <button type="button" class="btn btn-primary" onclick="importSchedules()">Importeren</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center p-3 mt-5">
        <div class="container">
            <p class="mb-0">
                © 2025 GamePlan Scheduler door Harsha Kanaparthi | 
                <a href="privacy.php" class="text-white text-decoration-none">Privacy</a> | 
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    
    <script>
        // Enhanced schedule management functionality
        
        // View switching functionality
        function switchView(viewType) {
            // Hide all views
            document.getElementById('cardViewContainer').classList.add('d-none');
            document.getElementById('listViewContainer').classList.add('d-none');
            document.getElementById('calendarViewContainer').classList.add('d-none');
            
            // Remove active class from all buttons
            document.querySelectorAll('.view-toggle .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected view and activate button
            switch(viewType) {
                case 'card':
                    document.getElementById('cardViewContainer').classList.remove('d-none');
                    document.getElementById('cardView').classList.add('active');
                    break;
                case 'list':
                    document.getElementById('listViewContainer').classList.remove('d-none');
                    document.getElementById('listView').classList.add('active');
                    break;
                case 'calendar':
                    document.getElementById('calendarViewContainer').classList.remove('d-none');
                    document.getElementById('calendarView').classList.add('active');
                    break;
                default:
                    document.getElementById('cardViewContainer').classList.remove('d-none');
                    document.getElementById('cardView').classList.add('active');
            }
            
            // Save preference to localStorage
            localStorage.setItem('gameplan_schedules_view', viewType);
        }
        
        // Order setting functionality
        function setOrder(newOrder) {
            document.querySelector('input[name="order"]').value = newOrder;
            
            // Update active button appearance
            document.querySelectorAll('.btn-group button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            if (newOrder === 'ASC') {
                document.querySelector('button[onclick="setOrder(\'ASC\')"]').classList.add('active');
            } else {
                document.querySelector('button[onclick="setOrder(\'DESC\')"]').classList.add('active');
            }
            
            // Submit form
            document.getElementById('filterForm').submit();
        }
        
        // Confirm delete functionality
        function confirmDelete(gameTitle) {
            return confirm(`Weet je zeker dat je het schema voor "${gameTitle}" wilt verwijderen?\n\nDeze actie kan niet ongedaan gemaakt worden.`);
        }
        
        // Export functionality
        function exportSchedules() {
            const schedules = [];
            
            // Collect schedule data from the current view
            document.querySelectorAll('.schedule-card').forEach((card, index) => {
                const gameTitle = card.querySelector('h6').textContent;
                const dateText = card.querySelector('.fa-calendar').parentElement.textContent.trim();
                const timeText = card.querySelector('.fa-clock').parentElement.textContent.trim();
                const friendsText = card.querySelector('.friends-section .text-success')?.textContent || 'Solo sessie';
                
                schedules.push({
                    game: gameTitle,
                    date: dateText,
                    time: timeText,
                    friends: friendsText,
                    exported_at: new Date().toISOString()
                });
            });
            
            // Create CSV content
            let csvContent = "Game,Datum,Tijd,Vrienden,Geëxporteerd op\n";
            schedules.forEach(schedule => {
                csvContent += `"${schedule.game}","${schedule.date}","${schedule.time}","${schedule.friends}","${schedule.exported_at}"\n`;
            });
            
            // Download CSV
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `gameplan_schedules_${new Date().getFullYear()}-${(new Date().getMonth() + 1).toString().padStart(2, '0')}-${new Date().getDate().toString().padStart(2, '0')}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showToast('Export Gelukt', `${schedules.length} schema's geëxporteerd naar CSV`, 'success');
        }
        
        // Import functionality
        function importSchedules() {
            const fileInput = document.getElementById('importFile');
            if (!fileInput.files.length) {
                showToast('Geen bestand', 'Selecteer eerst een bestand om te importeren', 'warning');
                return;
            }
            
            const file = fileInput.files[0];
            const fileType = file.name.split('.').pop().toLowerCase();
            
            if (!['csv', 'json'].includes(fileType)) {
                showToast('Ongeldig bestand', 'Alleen CSV en JSON bestanden worden ondersteund', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    let importData = [];
                    
                    if (fileType === 'json') {
                        importData = JSON.parse(e.target.result);
                    } else if (fileType === 'csv') {
                        const lines = e.target.result.split('\n');
                        const headers = lines[0].split(',').map(h => h.replace(/"/g, ''));
                        
                        for (let i = 1; i < lines.length; i++) {
                            if (lines[i].trim()) {
                                const values = lines[i].split(',').map(v => v.replace(/"/g, ''));
                                const obj = {};
                                headers.forEach((header, index) => {
                                    obj[header] = values[index] || '';
                                });
                                importData.push(obj);
                            }
                        }
                    }
                    
                    if (importData.length > 0) {
                        // In a real application, you would send this data to the server
                        showToast('Import Gesimuleerd', `${importData.length} items gevonden. In een echte applicatie zouden deze worden geïmporteerd.`, 'info');
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('importModal'));
                        modal.hide();
                    } else {
                        showToast('Geen data', 'Er zijn geen geldige schema\'s gevonden in het bestand', 'warning');
                    }
                    
                } catch (error) {
                    showToast('Importfout', 'Er is een fout opgetreden bij het lezen van het bestand', 'error');
                    console.error('Import error:', error);
                }
            };
            
            reader.readAsText(file);
        }
        
        // Enhanced filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved view preference
            const savedView = localStorage.getItem('gameplan_schedules_view');
            if (savedView) {
                switchView(savedView);
            }
            
            // Auto-submit filter form on changes
            const filterForm = document.getElementById('filterForm');
            filterForm.querySelectorAll('select, input[type="date"]').forEach(element => {
                element.addEventListener('change', function() {
                    // Add a small delay to allow users to make multiple changes
                    clearTimeout(window.filterTimeout);
                    window.filterTimeout = setTimeout(() => {
                        filterForm.submit();
                    }, 300);
                });
            });
            
            // Add search functionality
            addSearchFunctionality();
            
            // Initialize tooltips
            initializeTooltips();
            
            // Add keyboard shortcuts
            addKeyboardShortcuts();
        });
        
        // Search functionality
        function addSearchFunctionality() {
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'form-control form-control-sm';
            searchInput.placeholder = 'Zoek in schema\'s...';
            searchInput.style.maxWidth = '200px';
            
            const searchContainer = document.createElement('div');
            searchContainer.className = 'input-group';
            searchContainer.style.maxWidth = '250px';
            
            const searchIcon = document.createElement('span');
            searchIcon.className = 'input-group-text';
            searchIcon.innerHTML = '<i class="fas fa-search"></i>';
            
            searchContainer.appendChild(searchInput);
            searchContainer.appendChild(searchIcon);
            
            // Add to the view toggle area
            const viewToggleArea = document.querySelector('.d-flex.justify-content-between');
            if (viewToggleArea) {
                const rightDiv = viewToggleArea.children[1];
                rightDiv.appendChild(searchContainer);
            }
            
            // Add search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                // Search in card view
                document.querySelectorAll('.schedule-card').forEach(card => {
                    const gameTitle = card.querySelector('h6').textContent.toLowerCase();
                    const friendsText = card.querySelector('.friends-section').textContent.toLowerCase();
                    
                    if (gameTitle.includes(searchTerm) || friendsText.includes(searchTerm)) {
                        card.closest('.col-lg-4').style.display = 'block';
                    } else {
                        card.closest('.col-lg-4').style.display = 'none';
                    }
                });
                
                // Search in list view
                document.querySelectorAll('#listViewContainer tbody tr').forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    if (rowText.includes(searchTerm)) {
                        row.style.display = 'table-row';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Search in calendar view
                document.querySelectorAll('.calendar-day .card').forEach(card => {
                    const cardText = card.textContent.toLowerCase();
                    if (cardText.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
        
        // Keyboard shortcuts
        function addKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Only activate shortcuts when not typing in inputs
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    return;
                }
                
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case '1':
                            e.preventDefault();
                            switchView('card');
                            break;
                        case '2':
                            e.preventDefault();
                            switchView('list');
                            break;
                        case '3':
                            e.preventDefault();
                            switchView('calendar');
                            break;
                        case 'e':
                            e.preventDefault();
                            exportSchedules();
                            break;
                        case 'n':
                            e.preventDefault();
                            window.location.href = 'add_schedule.php';
                            break;
                    }
                }
            });
        }
        
        // Initialize tooltips
        function initializeTooltips() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
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
        
        // Auto-refresh functionality (optional)
        function startAutoRefresh() {
            if (document.visibilityState === 'visible') {
                setInterval(() => {
                    if (!document.hidden) {
                        // In a real application, you could refresh data here
                        console.log('Auto-refresh check...');
                    }
                }, 300000); // 5 minutes
            }
        }
        
        // Initialize auto-refresh when page becomes visible
        document.addEventListener('visibilitychange', startAutoRefresh);
        
        // Show helpful tips
        function showHelpfulTips() {
            const tips = [
                'Tip: Gebruik Ctrl+1/2/3 om snel tussen weergaven te wisselen!',
                'Tip: Gebruik Ctrl+E om je schema\'s te exporteren!',
                'Tip: Gebruik Ctrl+N om snel een nieuw schema toe te voegen!',
                'Tip: Klik op de sorteerknoppen om je schema\'s te organiseren!'
            ];
            
            const randomTip = tips[Math.floor(Math.random() * tips.length)];
            
            // Show tip after a delay, but only once per session
            if (!sessionStorage.getItem('tip_shown')) {
                setTimeout(() => {
                    showToast('GamePlan Tip', randomTip, 'info');
                    sessionStorage.setItem('tip_shown', 'true');
                }, 3000);
            }
        }
        
        // Initialize tips
        document.addEventListener('DOMContentLoaded', showHelpfulTips);
    </script>
</body>
</html>