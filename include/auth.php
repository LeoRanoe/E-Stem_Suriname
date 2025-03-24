<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['User ID']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['Role']) && $_SESSION['Role'] === 'admin';
}

// Check if user is voter
function isVoter() {
    return isset($_SESSION['Role']) && $_SESSION['Role'] === 'voter';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

// Require admin access
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

// Require voter access
function requireVoter() {
    requireLogin();
    if (!isVoter()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

// Login user
function loginUser($user) {
    $_SESSION['User ID'] = $user['UserID'];
    $_SESSION['Name'] = $user['Voornaam'] . ' ' . $user['Achternaam'];
    $_SESSION['Email'] = $user['Email'];
    $_SESSION['Role'] = $user['Role'];
    $_SESSION['DistrictID'] = $user['DistrictID'];
}

// Logout user
function logoutUser() {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

// Get current user
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
        $stmt->execute([$_SESSION['User ID']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
} 