<?php
// Database verbinding met PDO voor veilige interactie met MySQL
$host = 'localhost';
$dbname = 'gameplan_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Voor betere security en performance
} catch (PDOException $e) {
    die("Verbinding met de database is mislukt: " . $e->getMessage());
}
?>