<?php
// delete.php - Advanced Delete Handler
// Author: Harsha Kanaparthi
// Date: 30-09-2025

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

// Validate input
if (!in_array($type, ['favorite', 'friend', 'schedule', 'event']) || !is_numeric($id)) {
    setMessage('danger', 'Invalid deletion request.');
    header("Location: index.php");
    exit;
}

// Perform deletion based on type
switch ($type) {
    case 'favorite':
        $error = deleteFavoriteGame($userId, $id);
        $redirect = 'profile.php';
        break;
        
    case 'friend':
        $error = deleteFriend($userId, $id);
        $redirect = 'friends.php';
        break;
        
    case 'schedule':
        $error = deleteSchedule($userId, $id);
        $redirect = 'index.php';
        break;
        
    case 'event':
        $error = deleteEvent($userId, $id);
        $redirect = 'index.php';
        break;
        
    default:
        $error = 'Invalid type specified.';
        $redirect = 'index.php';
}

if ($error) {
    setMessage('danger', $error);
} else {
    $typeNames = [
        'favorite' => 'Favorite game',
        'friend' => 'Friend',
        'schedule' => 'Schedule',
        'event' => 'Event'
    ];
    setMessage('success', $typeNames[$type] . ' deleted successfully!');
}

header("Location: $redirect");
exit;
?>