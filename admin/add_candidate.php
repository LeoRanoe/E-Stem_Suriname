<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $firstName = $_POST['firstName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $party_id = $_POST['party_id'] ?? '';
        $election_id = $_POST['election_id'] ?? '';
        $district_id = $_POST['district_id'] ?? '';

        if (empty($firstName) || empty($lastName) || empty($party_id) || empty($election_id) || empty($district_id)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        // Combine first and last name
        $full_name = $firstName . ' ' . $lastName;

        // Handle photo upload
        $photo_url = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
            }

            if ($_FILES['photo']['size'] > $max_size) {
                throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
            }

            $upload_dir = '../uploads/candidates/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $photo_url = 'uploads/candidates/' . $file_name;
            }
        }

        // Insert candidate into database
        $stmt = $pdo->prepare("
            INSERT INTO candidates (
                Name, PartyID, ElectionID, DistrictID,
                Photo, CreatedAt
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $full_name,
            $party_id,
            $election_id,
            $district_id,
            $photo_url
        ]);

        $_SESSION['success_message'] = "Kandidaat is succesvol toegevoegd.";
        header('Location: candidates.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: candidates.php');
        exit;
    }
} else {
    // If someone tries to access this file directly without POST data
    header('Location: candidates.php');
    exit;
} 