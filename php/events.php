<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle RSVP responses
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rsvp_action'])) {
    $event_id = $_POST['event_id'] ?? 0;
    $response = $_POST['response'] ?? 'declined';
    
    if (updateRSVPStatus($event_id, $user_id, $response)) {
        $message = '<div class="alert alert-success">RSVP status updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating RSVP status.</div>';
    }
}

// Fetch all events the user is invited to or created
$events = [];
global $pdo;
$stmt = $pdo->prepare("
    SELECT e.*, u.username as creator_name, 
           eum.response_status, eum.participation_type
    FROM Events e
    LEFT JOIN Users u ON e.user_id = u.user_id
    LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id AND eum.friend_id = :user_id
    WHERE e.user_id = :user_id 
       OR eum.friend_id = :user_id
    ORDER BY e.date ASC, e.time ASC
");
$stmt->execute(['user_id' => $user_id]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evenementen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Evenementen</h2>
        <?php echo $message; ?>
        
        <div class="d-flex justify-content-end mb-4">
            <a href="add_event.php" class="btn btn-primary">Nieuw evenement</a>
        </div>

        <div class="row">
            <?php if (empty($events)): ?>
                <div class="col-12">
                    <p class="text-center text-muted">Geen evenementen gevonden.</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?php echo getEventTypeBadgeClass($event['event_type']); ?>">
                                    <?php echo ucfirst($event['event_type']); ?>
                                </span>
                                <small class="text-muted">Door: <?php echo htmlspecialchars($event['creator_name']); ?></small>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?php echo formatDate($event['date']); ?><br>
                                        <i class="bi bi-clock"></i> <?php echo formatTime($event['time']); ?>
                                    </small>
                                </p>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                
                                <?php if ($event['user_id'] != $user_id): ?>
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <input type="hidden" name="rsvp_action" value="1">
                                        <div class="btn-group w-100" role="group">
                                            <button type="submit" name="response" value="accepted" class="btn btn-sm <?php echo ($event['response_status'] == 'accepted') ? 'btn-success' : 'btn-outline-success'; ?>">
                                                Ja
                                            </button>
                                            <button type="submit" name="response" value="maybe" class="btn btn-sm <?php echo ($event['response_status'] == 'maybe') ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                                Misschien
                                            </button>
                                            <button type="submit" name="response" value="declined" class="btn btn-sm <?php echo ($event['response_status'] == 'declined') ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                                Nee
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="mt-3">
                                        <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-outline-primary btn-sm me-2">Bewerken</a>
                                        <a href="delete_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Weet je zeker dat je dit evenement wilt verwijderen?')">Verwijderen</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($event['user_id'] == $user_id): ?>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        Responses: 
                                        <?php echo getEventResponseCounts($event['event_id']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-primary">Terug naar dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function getEventTypeBadgeClass($type) {
    switch ($type) {
        case 'tournament': return 'danger';
        case 'practice': return 'info';
        case 'competition': return 'warning';
        case 'stream': return 'primary';
        case 'meetup': return 'success';
        default: return 'secondary';
    }
}

function getEventResponseCounts($event_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT response_status, COUNT(*) as count 
        FROM EventUserMap 
        WHERE event_id = :event_id 
        GROUP BY response_status
    ");
    $stmt->execute(['event_id' => $event_id]);
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($counts as $count) {
        switch ($count['response_status']) {
            case 'accepted':
                $result[] = '<span class="text-success">' . $count['count'] . ' Ja</span>';
                break;
            case 'maybe':
                $result[] = '<span class="text-warning">' . $count['count'] . ' Misschien</span>';
                break;
            case 'declined':
                $result[] = '<span class="text-danger">' . $count['count'] . ' Nee</span>';
                break;
            case 'pending':
                $result[] = '<span class="text-secondary">' . $count['count'] . ' Pending</span>';
                break;
        }
    }
    return implode(' | ', $result);
}

function formatDate($date) {
    return date('d-m-Y', strtotime($date));
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}