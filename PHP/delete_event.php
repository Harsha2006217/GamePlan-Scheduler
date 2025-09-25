<?php
require 'functions.php';
if (!isLoggedIn()) header("Location: login.php");
$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
// Check of het van de gebruiker is
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = :id AND user_id = :user");
$stmt->bindParam(':id', $id);
$stmt->bindParam(':user', $user_id);
$stmt->execute();
if ($stmt->fetch()) {
    deleteEvent($id);
}
header("Location: index.php");
exit;
?>