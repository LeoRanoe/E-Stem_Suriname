<?php
// Session configuration - must be set before session starts
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    session_start();
}

// Base configuration
define('BASE_PATH', '/E-Stem_Suriname');
define('BASE_URL', 'http://localhost/E-Stem_Suriname');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'e-stem_suriname');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_NAME', 'E-Stem Suriname');
define('SITE_DESCRIPTION', 'Online Voting System for Suriname');

// Surinamese colors
define('COLORS', [
    'green' => '#007749',      // Suriname green
    'dark-green' => '#006241',
    'red' => '#C8102E',        // Suriname red
    'dark-red' => '#a50d26',
    'yellow' => '#FFD700',     // Gold
    'white' => '#FFFFFF',
    'black' => '#000000'
]);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Time zone
date_default_timezone_set('America/Paramaribo');

// Database connection
try {
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
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
} 