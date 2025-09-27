<?php
// Geavanceerde databaseverbinding met PDO voor GamePlan Scheduler
// Inclusief connection pooling simulatie via persistent connections
// Error handling met logging, en attributes voor security en performance
// Gebruik van environment variables voor config in productie

// Laad config uit environment or fallback to defaults for local dev
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'gameplan_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

// Probeer verbinding te maken met persistent optie voor betere performance bij herhaalde calls
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_PERSISTENT => true,  // Persistent connection voor pooling-effect
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"  // Zorg voor juiste charset
    ]);
} catch (PDOException $e) {
    // Log error naar file voor debugging, toon gebruiker vriendelijke melding
    error_log("Database verbinding mislukt op " . date('Y-m-d H:i:s') . ": " . $e->getMessage(), 3, 'errors.log');
    die("Sorry, er is een probleem met de verbinding. Probeer het later opnieuw.");
}

// Functie om PDO statement te debuggen (voor development, comment out in productie)
function debugPDO($stmt) {
    ob_start();
    $stmt->debugDumpParams();
    return ob_get_clean();
}

// Extra security: Stel timeout in voor queries om DoS te voorkomen (bijv. 5 seconden)
$pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);
?>