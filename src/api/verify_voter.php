<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/VoterAuth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get login credentials
$voucher_id = $_POST['voucher_id'] ?? '';
$password = $_POST['password'] ?? '';

// Validate input
if (empty($voucher_id) || empty($password)) {
    $_SESSION['login_error'] = 'Voucher ID en wachtwoord zijn vereist.';
    header('Location: ' . BASE_URL . '/voter/index.php');
    exit();
}

// Initialize VoterAuth
$voterAuth = new VoterAuth($pdo);

// Verify voucher
$voter = $voterAuth->verifyVoucher($voucher_id, $password);

if ($voter) {
    // Set session variables
    $_SESSION['voter_id'] = $voter['id'];
    $_SESSION['voter_name'] = $voter['first_name'] . ' ' . $voter['last_name'];
    $_SESSION['voter_district'] = $voter['district_id'];
    $_SESSION['voter_resort'] = $voter['resort_id'];
    $_SESSION['voucher_id'] = $voucher_id;
    
    // Redirect to voting page
    header('Location: ' . BASE_URL . '/pages/voting/index.php');
    exit();
} else {
    // Set error message and redirect back to login
    $_SESSION['login_error'] = 'Ongeldige Voucher ID of wachtwoord.';
    header('Location: ' . BASE_URL . '/voter/index.php');
    exit();
}
