<?php
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/admin_auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    $_SESSION['error_message'] = "You are not authorized to perform this action.";
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit();
}

try {
    // Truncate the voters table
    $pdo->exec("TRUNCATE TABLE voters");

    $_SESSION['success_message'] = "All voters have been deleted and the ID has been reset.";
} catch (PDOException $e) {
    // Log the error
    error_log("Error truncating voters table: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while deleting voters. Please check the logs.";
}

header('Location: ' . BASE_URL . '/admin/index.php');
exit(); 