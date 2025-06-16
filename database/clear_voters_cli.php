<?php
// This script is intended to be run from the command line.
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/../include/db_connect.php';

echo "Attempting to clear all voters and related data from the database...\n";

try {
    // Temporarily disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Tables to clear
    $tables = ['votes', 'qrcodes', 'voter_logins', 'voters'];

    foreach ($tables as $table) {
        echo "Clearing table: $table...\n";
        $pdo->exec("TRUNCATE TABLE `$table`");
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "SUCCESS: All voters and related data have been deleted, and all table IDs have been reset.\n";

} catch (PDOException $e) {
    // Re-enable foreign key checks just in case the script fails
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "ERROR: An error occurred while clearing the tables.\n";
    echo "Error message: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0); 