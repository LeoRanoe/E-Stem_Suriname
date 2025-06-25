<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';

echo "<h3>Admin Setup & Repair Script</h3>";

try {
    // 1. Ensure 'admins' table exists with a minimal schema
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `AdminID` INT AUTO_INCREMENT PRIMARY KEY,
            `Email` VARCHAR(100) NOT NULL UNIQUE,
            `Password` VARCHAR(255) NOT NULL,
            `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✔️ Base `admins` table structure checked/created.</p>";

    // 2. Add columns if they don't exist to prevent errors.
    $columns_to_add = [
        'Username' => "VARCHAR(50) NOT NULL UNIQUE AFTER `AdminID`",
        'FirstName' => "VARCHAR(50) AFTER `Password`",
        'LastName' => "VARCHAR(50) AFTER `FirstName`",
        'Status' => "VARCHAR(20) DEFAULT 'active' AFTER `LastName`"
    ];

    foreach ($columns_to_add as $column => $definition) {
        try {
            $pdo->exec("ALTER TABLE `admins` ADD COLUMN `$column` $definition");
            echo "<p>✔️ Column `$column` successfully added to the `admins` table.</p>";
        } catch (PDOException $e) {
            // If the column already exists, a 'Duplicate column name' error (SQLSTATE 42S21) is thrown.
            // We can safely ignore it and continue.
            if (str_contains($e->getMessage(), 'Duplicate column name')) {
                echo "<p>✔️ Column `$column` already exists, no action needed.</p>";
            } else {
                throw $e; // Re-throw any other unexpected errors.
            }
        }
    }

    // 3. Define the admin user details
    $username = 'superadmin';
    $email = 'admin@example.com';
    $password = 'Admin123!';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 4. Check if the admin user already exists (by email)
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE Email = :email");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin) {
        // If user exists, update their password and username
        $stmt = $pdo->prepare("UPDATE admins SET Password = :password, Username = :username WHERE Email = :email");
        $stmt->execute(['password' => $hashedPassword, 'username' => $username, 'email' => $email]);
        echo "<p>✔️ Admin user '{$username}' already existed. Their password has been reset.</p>";
    } else {
        // If user does not exist, create them
        $stmt = $pdo->prepare("
            INSERT INTO admins (Username, Email, Password, FirstName, LastName, Status) 
            VALUES (:username, :email, :password, 'Super', 'Admin', 'active')
        ");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);
        echo "<p>✔️ New admin user '{$username}' created successfully.</p>";
    }

    echo "<h4>Setup Complete!</h4>";
    echo "<p>You can now log in with:</p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> {$email}</li>";
    echo "<li><strong>Password:</strong> {$password}</li>";
    echo "</ul>";
    echo "<p style='color:red;'><b>Important:</b> Please delete this file (`setup_admin.php`) immediately after use.</p>";

} catch (PDOException $e) {
    die("<p style='color:red;'><b>Database Error:</b> " . $e->getMessage() . "</p>");
}
?> 