<?php
function getUnreadNotificationCount($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM Notifications 
            WHERE user_id = :user_id 
            AND is_read = 0 
            AND (expire_at IS NULL OR expire_at > NOW())
        ");
        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

$unread_count = getUnreadNotificationCount($user_id);
$notifications = getNotifications($user_id, 5);
?>

<div class="dropdown">
    <button class="btn btn-link position-relative" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell-fill"></i>
        <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $unread_count; ?>
            </span>
        <?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notificationsDropdown" style="width: 300px;">
        <div class="p-2 border-bottom">
            <h6 class="mb-0">Notificaties</h6>
        </div>
        <div class="notification-list" style="max-height: 300px; overflow-y: auto;">
            <?php if (empty($notifications)): ?>
                <div class="p-3 text-center text-muted">
                    <small>Geen notificaties</small>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item p-2 border-bottom <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>"
                         data-id="<?php echo $notification['notification_id']; ?>"
                         onclick="markNotificationRead(<?php echo $notification['notification_id']; ?>)">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small class="text-muted">
                                    <?php echo timeAgo($notification['created_at']); ?>
                                </small>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <span class="badge bg-primary ms-2">Nieuw</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($notifications)): ?>
            <div class="p-2 border-top text-center">
                <a href="#" class="small text-decoration-none" onclick="markAllNotificationsRead()">
                    Alles als gelezen markeren
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function markNotificationRead(id) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notification = document.querySelector(`[data-id="${id}"]`);
            notification.classList.remove('bg-light');
            const badge = notification.querySelector('.badge');
            if (badge) badge.remove();
            updateNotificationCount();
        }
    });
}

function markAllNotificationsRead() {
    fetch('mark_all_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.remove('bg-light');
                const badge = item.querySelector('.badge');
                if (badge) badge.remove();
            });
            updateNotificationCount();
        }
    });
}

function updateNotificationCount() {
    const countBadge = document.querySelector('#notificationsDropdown .badge');
    if (countBadge) {
        const currentCount = parseInt(countBadge.textContent) - 1;
        if (currentCount <= 0) {
            countBadge.remove();
        } else {
            countBadge.textContent = currentCount;
        }
    }
}
</script>