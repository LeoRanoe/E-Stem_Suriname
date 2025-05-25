<?php
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../controllers/QrCodeController.php';
require_once __DIR__ . '/../controllers/ImportController.php';

header('Content-Type: application/json');

// Enhanced debug logging
$logMessage = sprintf(
    "=== New Import Request [%s] ===\nIP: %s\nUser Agent: %s\n",
    date('Y-m-d H:i:s'),
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
);
file_put_contents('logs/import-debug.log', $logMessage, FILE_APPEND);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \RuntimeException('Only POST requests are allowed');
    }

    // Debug: Log received POST data
    file_put_contents('logs/import-debug.log',
        "POST Data: " . print_r($_POST, true) . "\n" .
        "FILES Data: " . print_r($_FILES, true) . "\n",
        FILE_APPEND);

    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
        file_put_contents('logs/import-debug.log', "Security Error: $error\n", FILE_APPEND);
        throw new \RuntimeException($error, 403);
    }

    // Validate import type
    if (empty($_POST['import_type']) || !in_array($_POST['import_type'], ['bulk', 'single'])) {
        $error = 'Invalid import type specified';
        file_put_contents('logs/import-debug.log', "Validation Error: $error\n", FILE_APPEND);
        throw new \InvalidArgumentException($error);
    }

    // Validate required fields
    if (empty($_FILES['file'])) {
        $error = 'No file uploaded';
        file_put_contents('logs/import-debug.log', "Validation Error: $error\n", FILE_APPEND);
        throw new \InvalidArgumentException($error, 400);
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validate file type and size with detailed error messages
    if ($ext !== 'csv') {
        $error = sprintf('Invalid file type "%s" - Only CSV files are allowed', $ext);
        file_put_contents('logs/import-debug.log', "Validation Error: $error\n", FILE_APPEND);
        throw new \InvalidArgumentException($error, 400);
    }

    if ($file['size'] > 10 * 1024 * 1024) { // 10MB max
        $error = sprintf('File size %.2fMB exceeds 10MB limit', $file['size'] / (1024 * 1024));
        file_put_contents('logs/import-debug.log', "Validation Error: $error\n", FILE_APPEND);
        throw new \InvalidArgumentException($error, 400);
    }

    // Validate target table if provided
    if (empty($_POST['target_table'])) {
        $error = 'No target table specified';
        file_put_contents('logs/import-debug.log', "Validation Error: $error\n", FILE_APPEND);
        throw new \InvalidArgumentException($error, 400);
    }

    $allowedTables = ['voters', 'admins', 'candidates', 'districten', 'elections', 'parties', 'qrcodes'];
    if (!in_array($_POST['target_table'], $allowedTables)) {
        $error = sprintf('Invalid target table "%s" specified', $_POST['target_table']);
        file_put_contents('logs/import-debug.log', "Validation Error: $error\n", FILE_APPEND);
        throw new \InvalidArgumentException($error, 400);
    }

    // Initialize controller and process import
    $qrCodeController = new \App\Controllers\QrCodeController();
    
    // Use new handleImport method if target_table is present (new flow)
    if (isset($_POST['target_table'])) {
        file_put_contents('logs/import-debug.log', "Processing with handleImport()\n", FILE_APPEND);
        $result = $qrCodeController->handleImport();
    } else {
        file_put_contents('logs/import-debug.log',
            "Processing with importUsers() - Election ID: " . ($_POST['election_id'] ?? 'null') . "\n",
            FILE_APPEND);
        // Fallback to old importUsers method for backward compatibility
        $importController = new \App\Controllers\ImportController($pdo);
        $result = $importController->importUsers([
            'file' => $file,
            'election_id' => $_POST['election_id'] ?? null
        ]);
    }
    // Standardize success response format
    $response = [
        'success' => true,
        'message' => $result['message'] ?? 'Import completed successfully',
        'data' => [
            'imported_count' => $result['imported_count'] ?? 0,
            'skipped_count' => $result['skipped_count'] ?? 0,
            'total_rows' => $result['total_rows'] ?? 0,
            'duration' => $result['duration'] ?? 0
        ],
        'timestamp' => date('c')
    ];

    // Debug: Log final result
    file_put_contents('logs/import-debug.log',
        "Success Response: " . print_r($response, true) . "\n" .
        "=== End Request ===\n\n", FILE_APPEND);
    
    echo json_encode($response);


} catch (\Exception $e) {
    $statusCode = $e->getCode() ?: ($e instanceof \InvalidArgumentException ? 400 : 500);
    http_response_code($statusCode);

    $errorData = [
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => get_class($e),
        'error_code' => $statusCode,
        'details' => [
            'file' => $_FILES['file']['name'] ?? null,
            'file_size' => $_FILES['file']['size'] ?? null,
            'target_table' => $_POST['target_table'] ?? null,
            'validation_errors' => ($e instanceof \InvalidArgumentException) ? [
                'file_type' => $ext ?? null,
                'file_size' => $_FILES['file']['size'] ?? null,
                'target_table' => $_POST['target_table'] ?? null
            ] : null
        ],
        'timestamp' => date('c')
    ];
    
    // Log full error details
    file_put_contents('logs/import-debug.log',
        "ERROR: " . print_r($errorData, true) . "\n" .
        "Stack Trace: " . $e->getTraceAsString() . "\n" .
        "Request Data: " . print_r($_REQUEST, true) . "\n" .
        "=== End Request ===\n\n",
        FILE_APPEND);
    
    echo json_encode($errorData);
}