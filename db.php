<?php
// db.php
$host = "localhost";       // Hostname (e.g., localhost or your server IP)
$dbname = "e-stem_suriname";   // Database name
$username = "root";        // MySQL username
$password = "";            // MySQL password (leave empty if no password)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>