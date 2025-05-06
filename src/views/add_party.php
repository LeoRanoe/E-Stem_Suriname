<?php
session_start();
require_once '../include/db_connect.php';
require_once __DIR__ . '/../../include/admin_auth.php';

// Check if user is logged in and is admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $party_name = $_POST['partyName'] ?? '';

        if (empty($party_name)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        // Check if party name already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM parties WHERE PartyName = ?");
        $stmt->execute([$party_name]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Er bestaat al een partij met deze naam.');
        }

        // Handle logo upload
        $logo_url = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['logo']['type'], $allowed_types)) {
                throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
            }

            if ($_FILES['logo']['size'] > $max_size) {
                throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
            }

            $upload_dir = '../uploads/parties/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                $logo_url = 'uploads/parties/' . $file_name;
            }
        }

        // Insert party into database
        $stmt = $pdo->prepare("
            INSERT INTO parties (
                PartyName, Logo, CreatedAt
            ) VALUES (?, ?, NOW())
        ");
        
        $stmt->execute([
            $party_name,
            $logo_url
        ]);

        $_SESSION['success_message'] = "Partij is succesvol toegevoegd.";
        header('Location: parties.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: parties.php');
        exit;
    }
} else {
    // If someone tries to access this file directly without POST data
    header('Location: parties.php');
    exit;
} 