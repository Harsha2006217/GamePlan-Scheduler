<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$friends = getFriends($user_id);
$pending_requests = getPendingFriendRequests($user_id);
$sent_requests = getSentFriendRequests($user_id);

// Handle friend request responses
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    
    if ($request_id && $action) {
        switch ($action) {
            case 'accept':
                if (acceptFriendRequest($request_id, $user_id)) {
                    header("Location: friends.php?success=accepted");
                    exit;
                }
                break;
            case 'decline':
                if (declineFriendRequest($request_id, $user_id)) {
                    header("Location: friends.php?success=declined");
                    exit;
                }
                break;
            case 'block':
                if (blockUser($request_id, $user_id)) {
                    header("Location: friends.php?success=blocked");
                    exit;
                }
                break;
            case 'cancel':
                if (cancelFriendRequest($request_id, $user_id)) {
                    header("Location: friends.php?success=cancelled");
                    exit;
                }
                break;
        }
    }
}

$message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'accepted':
            $message = '<div class="alert alert-success">Vriendschapsverzoek geaccepteerd!</div>';
            break;
        case 'declined':
            $message = '<div class="alert alert-info">Vriendschapsverzoek afgewezen.</div>';
            break;
        case 'blocked':
            $message = '<div class="alert alert-warning">Gebruiker geblokkeerd.</div>';
            break;
        case 'cancelled':
            $message = '<div class="alert alert-info">Vriendschapsverzoek geannuleerd.</div>';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vrienden - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Vrienden Beheer</h2>
        <?php echo $message; ?>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Friend Requests Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="friendsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="friends-tab" data-bs-toggle="tab" href="#friends" role="tab">
                            Vrienden
                            <?php if (count($friends) > 0): ?>
                                <span class="badge bg-secondary ms-1"><?php echo count($friends); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="requests-tab" data-bs-toggle="tab" href="#requests" role="tab">
                            Verzoeken
                            <?php if (count($pending_requests) > 0): ?>
                                <span class="badge bg-primary ms-1"><?php echo count($pending_requests); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="sent-tab" data-bs-toggle="tab" href="#sent" role="tab">
                            Verzonden
                            <?php if (count($sent_requests) > 0): ?>
                                <span class="badge bg-secondary ms-1"><?php echo count($sent_requests); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="friendsTabsContent">
                    <!-- Friends List -->
                    <div class="tab-pane fade show active" id="friends" role="tabpanel">
                        <div class="list-group shadow-sm">
                            <?php if (empty($friends)): ?>
                                <div class="list-group-item text-center text-muted">
                                    Geen vrienden toegevoegd.
                                </div>
                            <?php else: ?>
                                <?php foreach ($friends as $friend): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="me-2"><?php echo htmlspecialchars($friend['username']); ?></span>
                                            <span class="badge <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?>
                                            </span>
                                        </div>
                                        <div class="btn-group">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $friend['friend_id']; ?>">
                                                <button type="submit" name="action" value="block" class="btn btn-sm btn-outline-danger" onclick="return confirm('Weet je zeker dat je deze gebruiker wilt blokkeren?')">
                                                    Blokkeren
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pending Requests -->
                    <div class="tab-pane fade" id="requests" role="tabpanel">
                        <div class="list-group shadow-sm">
                            <?php if (empty($pending_requests)): ?>
                                <div class="list-group-item text-center text-muted">
                                    Geen openstaande vriendschapsverzoeken.
                                </div>
                            <?php else: ?>
                                <?php foreach ($pending_requests as $request): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($request['username']); ?></span>
                                        <div class="btn-group">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $request['friend_id']; ?>">
                                                <button type="submit" name="action" value="accept" class="btn btn-sm btn-success me-2">
                                                    Accepteren
                                                </button>
                                                <button type="submit" name="action" value="decline" class="btn btn-sm btn-danger">
                                                    Afwijzen
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sent Requests -->
                    <div class="tab-pane fade" id="sent" role="tabpanel">
                        <div class="list-group shadow-sm">
                            <?php if (empty($sent_requests)): ?>
                                <div class="list-group-item text-center text-muted">
                                    Geen verzonden vriendschapsverzoeken.
                                </div>
                            <?php else: ?>
                                <?php foreach ($sent_requests as $request): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($request['username']); ?></span>
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?php echo $request['friend_id']; ?>">
                                            <button type="submit" name="action" value="cancel" class="btn btn-sm btn-outline-secondary">
                                                Annuleren
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="add_friend.php" class="btn btn-primary btn-lg me-2">Vriend toevoegen</a>
                    <a href="index.php" class="btn btn-outline-primary btn-lg">Terug naar dashboard</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>