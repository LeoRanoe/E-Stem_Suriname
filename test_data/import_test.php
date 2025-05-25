<?php
use App\Controllers\ImportController;

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../src/controllers/ImportController.php';

// Initialize test counters
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

// Helper function to count records in table
function countTableRecords(PDO $db, string $table): int {
    $stmt = $db->query("SELECT COUNT(*) FROM $table");
    return (int)$stmt->fetchColumn();
}

// Helper function to run test case
function runTest(\App\Controllers\ImportController $importer, PDO $db, string $filePath, string $tableName, string $description): bool {
    global $totalTests, $passedTests, $failedTests;
    
    $totalTests++;
    $initialCount = countTableRecords($db, $tableName);
    
    try {
        $result = $importer->importFromTable($filePath, $tableName);
        
        if (!$result['success']) {
            echo "[FAIL] $tableName - $description - Import failed: " . $result['message'] . "\n";
            $failedTests++;
            return false;
        }
        
        $newCount = countTableRecords($db, $tableName);
        $expectedCount = $initialCount + $result['imported'];
        
        if ($newCount !== $expectedCount) {
            echo "[FAIL] $tableName - $description - Record count mismatch (expected $expectedCount, got $newCount)\n";
            $failedTests++;
            return false;
        }
        
        echo "[PASS] $tableName - $description - Imported {$result['imported']} records\n";
        $passedTests++;
        return true;
    } catch (Exception $e) {
        echo "[FAIL] $tableName - $description - Exception: " . $e->getMessage() . "\n";
        $failedTests++;
        return false;
    }
}

// Helper function to run error test case
function runErrorTest(\App\Controllers\ImportController $importer, PDO $db, string $filePath, string $tableName, string $description, string $expectedError): bool {
    global $totalTests, $passedTests, $failedTests;
    
    $totalTests++;
    
    try {
        $result = $importer->importFromTable($filePath, $tableName);
        
        if ($result['success']) {
            echo "[FAIL] $tableName - $description - Expected failure but import succeeded\n";
            $failedTests++;
            return false;
        }
        
        if (strpos($result['message'], $expectedError) === false) {
            echo "[FAIL] $tableName - $description - Wrong error message: {$result['message']}\n";
            $failedTests++;
            return false;
        }
        
        echo "[PASS] $tableName - $description - Correctly failed with: {$result['message']}\n";
        $passedTests++;
        return true;
    } catch (Exception $e) {
        echo "[FAIL] $tableName - $description - Unexpected exception: " . $e->getMessage() . "\n";
        $failedTests++;
        return false;
    }
}

echo "Starting import tests...\n\n";

try {
    // Create ImportController instance
    $importer = new \App\Controllers\ImportController($pdo);
    
    // Test valid imports for all supported tables
    runTest($importer, $pdo, __DIR__ . '/csv_import_test/voters.csv', 'voters', 'Valid voters import');
    runTest($importer, $pdo, __DIR__ . '/csv_import_test/admins.csv', 'admins', 'Valid admins import');
    runTest($importer, $pdo, __DIR__ . '/csv_import_test/candidates.csv', 'candidates', 'Valid candidates import');
    runTest($importer, $pdo, __DIR__ . '/csv_import_test/districten.csv', 'districten', 'Valid districten import');
    runTest($importer, $pdo, __DIR__ . '/csv_import_test/elections.csv', 'elections', 'Valid elections import');
    runTest($importer, $pdo, __DIR__ . '/csv_import_test/parties.csv', 'parties', 'Valid parties import');
    
    // Test error cases
    runErrorTest($importer, $pdo, __DIR__ . '/csv_import_test/malformed.csv', 'voters', 'Malformed CSV', 'Invalid ID number format');
    runErrorTest($importer, $pdo, __DIR__ . '/csv_import_test/wrong_headers.csv', 'voters', 'Invalid headers', 'Invalid CSV header');
    runErrorTest($importer, $pdo, __DIR__ . '/csv_import_test/voters.csv', 'nonexistent_table', 'Invalid table name', 'Unsupported table');
    
    echo "\nTest summary:\n";
    echo "Total tests: $totalTests\n";
    echo "Passed: $passedTests\n";
    echo "Failed: $failedTests\n";
    echo "Success rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n";
    
    exit($failedTests > 0 ? 1 : 0);
} catch (Exception $e) {
    echo "Fatal error during testing: " . $e->getMessage() . "\n";
    exit(1);
}