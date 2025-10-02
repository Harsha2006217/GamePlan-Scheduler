<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    setMessage('error', 'Invalid ID.');
    header('Location: index.php');
    exit;
}
$result = false;
if ($type === 'schedule') {
    $result = deleteSchedule($id);
} elseif ($type === 'event') {
    $result = deleteEvent($id);
}
if ($result) {
    setMessage('success', ucfirst($type) . ' deleted successfully.');
} else {
    setMessage('error', ucfirst($type) . ' not found or no permission.');
}
header('Location: index.php');
exit;
?>