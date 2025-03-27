<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $district_id = $_POST['district_id'] ?? '';

        if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($district_id)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        if ($password !== $confirm_password) {
            throw new Exception('Wachtwoorden komen niet overeen.');
        }

        if (strlen($password) < 8) {
            throw new Exception('Wachtwoord moet minimaal 8 tekens lang zijn.');
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Dit e-mailadres is al in gebruik.');
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new voter
        $stmt = $pdo->prepare("
            INSERT INTO users (Name, Email, Password, UTypeID, DistrictID, CreatedAt)
            VALUES (?, ?, ?, (SELECT UTypeID FROM usertype WHERE UserType = 'voter'), ?, NOW())
        ");
        $stmt->execute([$name, $email, $hashed_password, $district_id]);
        
        $_SESSION['success_message'] = "Stemmer is succesvol toegevoegd.";
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Redirect back to voters page
header('Location: voters.php');
exit; 