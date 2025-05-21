<?php
// src/views/import_users.php

// Include necessary files
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';

// Connect to the database
$db = new DbConnect();
$conn = $db->connect();

// Retrieve districts from the database
$query = "SELECT id, name FROM districts";
$stmt = $conn->prepare($query);
$stmt->execute();
$districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Users</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Import Users</h1>
        <form action="import_users.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="csvFile" class="form-label">CSV File</label>
                <input type="file" class="form-control" id="csvFile" name="csvFile" required>
            </div>
            <div class="mb-3">
                <label for="district" class="form-label">District</label>
                <select class="form-select" id="district" name="district" required>
                    <option value="">Select District</option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?php echo $district['id']; ?>"><?php echo $district['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Import</button>
        </form>
    </div>
</body>
</html>