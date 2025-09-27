<?php
// AJAX endpoint voor zoeken gebruikers (voor add_friend)
// Beperkt resultaten tot 10 voor performance, exclude zelf en vrienden

require 'functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$query = $_GET['q'] ?? '';

if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

$users = searchUsers($query, $user_id);

header('Content-Type: application/json');
echo json_encode($users);
?>