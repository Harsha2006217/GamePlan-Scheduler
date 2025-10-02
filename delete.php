<?php
require_once 'functions.php';

requireLogin();
checkTimeout();

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;

if (!is_numeric($id)) {
    setMessage('danger', 'Invalid ID');
    header('Location: index.php');
    exit;
}

if ($type == 'schedule') {
    $result = deleteSchedule($id);
    $message = 'Schedule deleted successfully';
} elseif ($type == 'event') {
    $result = deleteEvent($id);
    $message = 'Event deleted successfully';
} else {
    setMessage('danger', 'Invalid type');
    header('Location: index.php');
    exit;
}

if ($result) {
    setMessage('success', $message);
} else {
    setMessage('danger', 'Failed to delete');
}

header('Location: index.php');
exit;
?>