<?php
$page_title = "Schedules";
require_once 'header.php';

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Handle filters
$filter_date = $_GET['date'] ?? '';
$filter_game = $_GET['game'] ?? '';
$filter_type = $_GET['type'] ?? '';

// Get user schedules with filters
$where_conditions = [];
$params = [':user_id' => $user_id];

if (!empty($filter_date)) {
    $where_conditions[] = "s.schedule_date = :filter_date";
    $params[':filter_date'] = $filter_date;
}

if (!empty($filter_game) && $filter_game !== 'all') {
    $where_conditions[] = "s.game_id = :filter_game";
    $params[':filter_game'] = $filter_game;
}

if (!empty($filter_type) && $filter_type !== 'all') {
    $where_conditions[] = "s.recurring = :filter_type";
    $params[':filter_type'] = $filter_type;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = ' AND ' . implode(' AND ', $where_conditions);
}

$query = "SELECT s.*, g.game_title, g.genre, u.username as host_username 
          FROM schedules s 
          JOIN games g ON s.game_id = g.game_id 
          JOIN users u ON s.user_id = u.user_id 
          WHERE s.user_id = :user_id $where_clause
          ORDER BY s.schedule_date, s.start_time";

$stmt = $gameplan->conn->prepare($query);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// Get user games for filter dropdown
$user_games = $gameplan->getUserGames($user_id);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-alt me-2 text-primary"></i>My Schedules
            </h1>
            <a href="add_schedule.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>New Schedule
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter me-2 text-primary"></i>Filters
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="filter_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="filter_date" name="date" 
                               value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filter_game" class="form-label">Game</label>
                        <select class="form-select" id="filter_game" name="game">
                            <option value="all">All Games</option>
                            <?php foreach ($user_games as $game): ?>
                                <option value="<?php echo $game['game_id']; ?>" 
                                    <?php echo ($filter_game == $game['game_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($game['game_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_type" class="form-label">Recurring</label>
                        <select class="form-select" id="filter_type" name="type">
                            <option value="all">All Types</option>
                            <option value="None" <?php echo ($filter_type == 'None') ? 'selected' : ''; ?>>One-time</option>
                            <option value="Daily" <?php echo ($filter_type == 'Daily') ? 'selected' : ''; ?>>Daily</option>
                            <option value="Weekly" <?php echo ($filter_type == 'Weekly') ? 'selected' : ''; ?>>Weekly</option>
                            <option value="Monthly" <?php echo ($filter_type == 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Apply
                            </button>
                            <a href="schedules.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Scheduled Sessions
                    <span class="badge bg-primary ms-2"><?php echo count($schedules); ?> sessions</span>
                </h5>
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown">
                        <i class="fas fa-sort me-1"></i>Sort
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?sort=date">By Date</a></li>
                        <li><a class="dropdown-item" href="?sort=game">By Game</a></li>
                        <li><a class="dropdown-item" href="?sort=recent">Most Recent</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($schedules)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No schedules found</h5>
                        <p class="text-muted">
                            <?php if (!empty($filter_date) || !empty($filter_game) || !empty($filter_type)): ?>
                                Try adjusting your filters or 
                            <?php endif; ?>
                            Create your first gaming schedule to get started!
                        </p>
                        <a href="add_schedule.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create Schedule
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Game & Title</th>
                                    <th>Date & Time</th>
                                    <th>Duration</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                    <?php
                                    $schedule_datetime = $gameplan->formatDateTime($schedule['schedule_date'], $schedule['start_time']);
                                    $time_remaining = $gameplan->getTimeRemaining($schedule_datetime);
                                    $is_upcoming = strtotime($schedule_datetime) > time();
                                    $is_past = strtotime($schedule_datetime) < time();
                                    
                                    $status_class = $is_upcoming ? 'bg-warning' : ($is_past ? 'bg-secondary' : 'bg-success');
                                    $status_text = $is_upcoming ? 'Upcoming' : ($is_past ? 'Completed' : 'In Progress');
                                    ?>
                                    <tr class="<?php echo $is_past ? 'table-light' : ''; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="game-icon bg-light rounded p-2 me-3">
                                                    <i class="fas fa-gamepad text-primary"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($schedule['schedule_title']); ?></strong>
                                                    <div class="small text-muted">
                                                        <?php echo htmlspecialchars($schedule['game_title']); ?>
                                                        <?php if (!empty($schedule['genre'])): ?>
                                                            • <?php echo htmlspecialchars($schedule['genre']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($schedule['schedule_description'])): ?>
                                                        <div class="small text-muted mt-1">
                                                            <?php echo htmlspecialchars(substr($schedule['schedule_description'], 0, 100)); ?>
                                                            <?php if (strlen($schedule['schedule_description']) > 100): ?>...<?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo date('M j, Y', strtotime($schedule['schedule_date'])); ?></strong>
                                            <div class="small text-muted">
                                                <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                            </div>
                                            <?php if ($is_upcoming): ?>
                                                <div class="small text-warning">
                                                    <i class="fas fa-clock me-1"></i><?php echo $time_remaining; ?> left
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $start = strtotime($schedule['start_time']);
                                            $end = strtotime($schedule['end_time']);
                                            $duration = ($end - $start) / 3600;
                                            echo number_format($duration, 1) . ' hours';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($schedule['recurring'] !== 'None'): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-repeat me-1"></i><?php echo htmlspecialchars($schedule['recurring']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">One-time</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                                   class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                                   class="btn btn-outline-success" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?type=schedule&id=<?php echo $schedule['schedule_id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this schedule?')"
                                                   title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0">
                    <?php
                    $upcoming_count = count(array_filter($schedules, function($s) {
                        return strtotime($gameplan->formatDateTime($s['schedule_date'], $s['start_time'])) > time();
                    }));
                    echo $upcoming_count;
                    ?>
                </h4>
                <p class="mb-0 small">Upcoming</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4 class="mb-0">
                    <?php
                    $weekly_count = count(array_filter($schedules, function($s) {
                        return $s['recurring'] === 'Weekly';
                    }));
                    echo $weekly_count;
                    ?>
                </h4>
                <p class="mb-0 small">Weekly</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0">
                    <?php
                    $total_hours = array_sum(array_map(function($s) {
                        $start = strtotime($s['start_time']);
                        $end = strtotime($s['end_time']);
                        return ($end - $start) / 3600;
                    }, $schedules));
                    echo number_format($total_hours, 0);
                    ?>
                </h4>
                <p class="mb-0 small">Total Hours</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h4 class="mb-0">
                    <?php
                    $different_games = count(array_unique(array_column($schedules, 'game_id')));
                    echo $different_games;
                    ?>
                </h4>
                <p class="mb-0 small">Games Scheduled</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>