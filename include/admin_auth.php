<?php
// Development mode - bypass authentication
define('DEVELOPMENT_MODE', false);

function isAdminLoggedIn() {
    if (DEVELOPMENT_MODE) {
        // Auto-login for development
        $_SESSION['AdminID'] = 1;
        $_SESSION['AdminName'] = 'Developer';
        $_SESSION['AdminEmail'] = 'dev@example.com';
        $_SESSION['AdminStatus'] = 'active';
        return true;
    }
    
    return isset($_SESSION['AdminID']);
}

// Authenticate admin credentials
function authenticateAdmin($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM admins 
            WHERE Email = :email AND Status = 'active'
        ");
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['Password'])) {
            return $admin;
        }
        return false;
    } catch(PDOException $e) {
        error_log("Admin Authentication Error: " . $e->getMessage());
        return false;
    }
}

// Login admin and set session variables
function loginAdmin($admin) {
    error_log("LoginAdmin - Previous session ID: " . session_id());
    error_log("LoginAdmin - Session status: " . print_r($_SESSION, true));
    $_SESSION['AdminID'] = $admin['AdminID'];
    $_SESSION['AdminName'] = $admin['FirstName'] . ' ' . $admin['LastName'];
    $_SESSION['AdminEmail'] = $admin['Email'];
    $_SESSION['AdminStatus'] = $admin['Status'];
}

// Logout admin
function logoutAdmin() {
    unset($_SESSION['AdminID']);
    unset($_SESSION['AdminName']);
    unset($_SESSION['AdminEmail']);
    unset($_SESSION['AdminStatus']);
    session_destroy();
}

// Require admin login
function requireAdminLogin() {
    // Temporary bypass for development
    $_SESSION['AdminID'] = 1;
    $_SESSION['AdminName'] = 'Developer';
    $_SESSION['AdminEmail'] = 'dev@example.com';
    $_SESSION['AdminStatus'] = 'active';
    
    error_log("Bypassing admin login check for development");
    return true;
}

// Alias for backward compatibility
function requireAdmin() {
    requireAdminLogin();
}

// Get current admin
function getCurrentAdmin() {
    global $pdo;
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE AdminID = ?");
        $stmt->execute([$_SESSION['AdminID']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching admin: " . $e->getMessage());
        return null;
    }
}