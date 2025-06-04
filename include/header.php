<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    
    <!-- Include centralized styles -->
    <?php include_once __DIR__ . '/styles.php'; ?>
    
    <!-- Additional CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/suriname-style.css">
</head>
<body class="bg-gray-50 font-sans">
    <!-- Subtle Suriname pattern background -->
    <div class="fixed inset-0 z-0 pointer-events-none sr-bg-diagonal opacity-30"></div>