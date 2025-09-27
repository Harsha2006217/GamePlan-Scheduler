<?php
// Universele delete pagina voor schedules, events, friends
// Geavanceerd met type check en rechten validatie

require 'functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;

try {
    validateInput($id, 'string', 0, 0, '/^\d+$/', 'Ongeldig ID.');
    validateInput($type, 'string', 0, 0, '/^(schedule|event|friend)$/', 'Ongeldig type.');

    switch ($type) {
        case 'schedule':
            deleteSchedule($id, $user_id);
            break;
        case 'event':
            deleteEvent($id, $user_id);
            break;
        case 'friend':
            deleteFriend($user_id, $id);
            break;
        default:
            throw new Exception("Ongeldig type voor verwijderen.");
    }

    $_SESSION['msg'] = ucfirst($type) . " verwijderd!";
    header("Location: " . ($type == 'friend' ? 'friends.php' : 'index.php'));
    exit;
} catch (Exception $e) {
    $_SESSION['msg'] = $e->getMessage();
    header("Location: index.php");
    exit;
}
?>