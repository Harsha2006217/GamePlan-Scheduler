<?php
// delete.php - Delete Handler
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Handles deletion of schedules or events with confirmation.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
$userId = getUserId();

$error = '';
if ($type == 'schedule') {
    $error = deleteSchedule($userId, $id);
} elseif ($type == 'event') {
    $error = deleteEvent($userId, $id);
} elseif ($type == 'favorite') {
    $error = deleteFavoriteGame($userId, $id);
} elseif ($type == 'friend') {
    $error = deleteFriend($userId, $id);
} else {
    $error = 'Invalid type.';
}

if ($error) {
    setMessage('danger', $error);
} else {
    setMessage('success', ucfirst($type) . ' deleted successfully!');
}

header("Location: index.php");
exit;
?>