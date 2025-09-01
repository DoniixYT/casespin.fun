<?php
// Database configuration
$host = 'localhost';
$dbname = 'cashplay'; // Adjust database name as needed
$username = 'root';   // Adjust username as needed
$password = '';       // Adjust password as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Don't expose database details in production
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}
?>
