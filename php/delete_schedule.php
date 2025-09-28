<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM Schedules WHERE schedule_id = :id AND user_id = :user");
$stmt->bindParam(':id', $id);
$stmt->bindParam(':user', $user_id);
$stmt->execute();
if ($stmt->fetch()) {
    deleteSchedule($id);
}
header("Location: schedules.php");
exit;
?>