<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
if (!in_array($type, ['schedule', 'event']) || !is_numeric($id)) {
    setMessage('danger', 'Invalid request.');
    header('Location: index.php');
    exit;
}
$result = $type == 'schedule' ? deleteSchedule($id) : deleteEvent($id);
if ($result) {
    setMessage('success', ucfirst($type) . ' deleted successfully.');
} else {
    setMessage('danger', ucfirst($type) . ' not found or no permission.');
}
header('Location: index.php');
exit;
?>