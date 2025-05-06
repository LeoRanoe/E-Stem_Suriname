<?php
require_once __DIR__ . '/config.php';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Temporary session SQL mode to remove ONLY_FULL_GROUP_BY
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection error: " . $e->getMessage());
    die("Er is een fout opgetreden bij het verbinden met de database. Probeer het later opnieuw.");
}
?> 