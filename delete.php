<?php
// delete.php - Delete Handler
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Handles soft deletion of items.
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
    $redirect = 'index.php';
} elseif ($type == 'event') {
    $error = deleteEvent($userId, $id);
    $redirect = 'index.php';
} elseif ($type == 'favorite') {
    $error = deleteFavoriteGame($userId, $id);
    $redirect = 'profile.php';
} elseif ($type == 'friend') {
    $error = deleteFriend($userId, $id);
    $redirect = 'add_friend.php';
} else {
    $error = 'Invalid type.';
    $redirect = 'index.php';
}
if ($error) {
    setMessage('danger', $error);
} else {
    setMessage('success', ucfirst($type) . ' deleted successfully!');
}
header("Location: " . $redirect);
exit;
?>