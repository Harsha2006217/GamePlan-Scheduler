<?php
$page_title = "Events";
require_once 'header.php';

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Get user events (both created and participating)
$query = "
    SELECT e.*, s.schedule_title, s.schedule_date, s.start_time, s.end_time, 
           g.game_title, g.genre, u.username as host_username,
           ep.role, ep.join_status
    FROM events e
    JOIN schedules s ON e.schedule_id = s.schedule_id
    JOIN games g ON s.game_id = g.game_id
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN event_participants ep ON e.event_id = ep.event_id AND ep.user_id = :user_id
    WHERE s.user_id = :user_id OR ep.user_id = :user_id
    ORDER BY s.schedule_date DESC, s.start_time DESC
";

$stmt = $gameplan->conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$events = $stmt->fetchAll();

// Separate events by status
$upcoming_events = [];
$past_events = [];
$my_hosted_events = [];

foreach ($events as $event) {
    $event_datetime = $gameplan->formatDateTime($event['schedule_date'], $event['start_time']);
    if (strtotime($event_datetime) > time()) {
        $upcoming_events[] = $event;
        if ($event['role'] === 'Host') {
            $my_hosted_events[] = $event;
        }
    } else {
        $past_events[] = $event;
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-trophy me-2 text-primary"></i>Events & Tournaments
            </h1>
            <a href="add_event.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Create Event
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Stats -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-start border-primary border-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                            Upcoming Events
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            <?php echo count($upcoming_events); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-start border-success border-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">
                            Hosting
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            <?php echo count($my_hosted_events); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-crown fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-start border-info border-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">
                            Tournaments
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            <?php
                            $tournament_count = count(array_filter($events, function($e) {
                                return $e['event_type'] === 'Tournament';
                            }));
                            echo $tournament_count;
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-trophy fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card border-start border-warning border-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                            Completed
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            <?php echo count($past_events); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-flag-checkered fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Upcoming Events -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-rocket me-2 text-success"></i>
                    Upcoming Events
                    <span class="badge bg-success ms-2"><?php echo count($upcoming_events); ?></span>
                </h5>
                <div class="dropdown">
                    <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?filter=all">All Events</a></li>
                        <li><a class="dropdown-item" href="?filter=hosting">My Hosted</a></li>
                        <li><a class="dropdown-item" href="?filter=participating">Participating</a></li>
                        <li><a class="dropdown-item" href="?filter=tournament">Tournaments</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_events)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-plus fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No upcoming events</h5>
                        <p class="text-muted">Create your first event or join existing ones to get started!</p>
                        <a href="add_event.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create Event
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="col-md-6 mb-4">
                                <div class="event-card card h-100">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge 
                                                <?php 
                                                switch($event['event_type']) {
                                                    case 'Tournament': echo 'bg-danger'; break;
                                                    case 'Practice': echo 'bg-info'; break;
                                                    case 'Ranked': echo 'bg-warning'; break;
                                                    default: echo 'bg-primary';
                                                }
                                                ?>
                                            ">
                                                <?php echo htmlspecialchars($event['event_type']); ?>
                                            </span>
                                            <?php if ($event['role'] === 'Host'): ?>
                                                <span class="badge bg-success ms-1">Host</span>
                                            <?php elseif ($event['join_status'] === 'Accepted'): ?>
                                                <span class="badge bg-info ms-1">Participant</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-<?php echo $event['status'] === 'Scheduled' ? 'warning' : 'success'; ?>">
                                            <?php echo htmlspecialchars($event['status']); ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($event['event_title']); ?></h6>
                                        <p class="card-text small text-muted mb-2">
                                            <?php echo htmlspecialchars($event['game_title']); ?> • 
                                            <?php echo htmlspecialchars($event['genre']); ?>
                                        </p>
                                        
                                        <?php if (!empty($event['event_description'])): ?>
                                            <p class="card-text small">
                                                <?php echo htmlspecialchars(substr($event['event_description'], 0, 100)); ?>
                                                <?php if (strlen($event['event_description']) > 100): ?>...<?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="event-details mt-3">
                                            <div class="small text-muted mb-1">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('M j, Y', strtotime($event['schedule_date'])); ?>
                                            </div>
                                            <div class="small text-muted mb-1">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('g:i A', strtotime($event['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($event['end_time'])); ?>
                                            </div>
                                            <div class="small text-warning">
                                                <i class="fas fa-hourglass-half me-1"></i>
                                                <?php 
                                                $time_remaining = $gameplan->getTimeRemaining(
                                                    $gameplan->formatDateTime($event['schedule_date'], $event['start_time'])
                                                );
                                                echo $time_remaining . ' left';
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <div class="btn-group w-100">
                                            <a href="view_event.php?id=<?php echo $event['event_id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            <?php if ($event['role'] === 'Host'): ?>
                                                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" 
                                                   class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($event['join_status'] !== 'Accepted' && $event['role'] !== 'Host'): ?>
                                                <a href="join_event.php?id=<?php echo $event['event_id']; ?>" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-sign-in-alt me-1"></i>Join
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Events -->
        <?php if (!empty($past_events)): ?>
        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2 text-muted"></i>
                    Past Events
                    <span class="badge bg-secondary ms-2"><?php echo count($past_events); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Game</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($past_events, 0, 10) as $event): ?>
                                <tr class="table-light">
                                    <td>
                                        <strong><?php echo htmlspecialchars($event['event_title']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['game_title']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($event['schedule_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo htmlspecialchars($event['event_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?php echo $event['role'] === 'Host' ? 'bg-success' : 'bg-info'; ?>">
                                            <?php echo htmlspecialchars($event['role']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($past_events) > 10): ?>
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-outline-secondary btn-sm">
                            View All Past Events
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2 text-warning"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="add_event.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Event
                    </a>
                    <a href="schedules.php" class="btn btn-success">
                        <i class="fas fa-calendar-plus me-2"></i>From Schedule
                    </a>
                    <a href="#" class="btn btn-info">
                        <i class="fas fa-search me-2"></i>Browse Events
                    </a>
                </div>
            </div>
        </div>

        <!-- Event Types Info -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2 text-info"></i>
                    Event Types
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-danger me-2">Tournament</span>
                    <small class="text-muted">Competitive events with prizes</small>
                </div>
                <div class="mb-3">
                    <span class="badge bg-warning me-2">Ranked</span>
                    <small class="text-muted">Serious matches affecting rankings</small>
                </div>
                <div class="mb-3">
                    <span class="badge bg-info me-2">Practice</span>
                    <small class="text-muted">Training and skill development</small>
                </div>
                <div class="mb-3">
                    <span class="badge bg-primary me-2">Casual</span>
                    <small class="text-muted">Fun, relaxed gaming sessions</small>
                </div>
            </div>
        </div>

        <!-- Upcoming Tournaments -->
        <?php
        $tournaments = array_filter($upcoming_events, function($e) {
            return $e['event_type'] === 'Tournament';
        });
        ?>
        <?php if (!empty($tournaments)): ?>
        <div class="card shadow">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-trophy me-2"></i>
                    Featured Tournaments
                </h5>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($tournaments, 0, 3) as $tournament): ?>
                    <div class="mb-3 pb-3 border-bottom">
                        <h6 class="mb-1"><?php echo htmlspecialchars($tournament['event_title']); ?></h6>
                        <div class="small text-muted mb-1">
                            <?php echo htmlspecialchars($tournament['game_title']); ?>
                        </div>
                        <div class="small text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('M j', strtotime($tournament['schedule_date'])); ?> • 
                            <?php echo date('g:i A', strtotime($tournament['start_time'])); ?>
                        </div>
                        <a href="view_event.php?id=<?php echo $tournament['event_id']; ?>" 
                           class="btn btn-sm btn-outline-danger mt-2 w-100">
                            View Details
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>