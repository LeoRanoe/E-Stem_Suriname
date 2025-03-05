<?php
session_start();
require '../db.php'; // Include your database connection file

// Fetch districts from the database
try {
    $district_stmt = $conn->query("SELECT DistrictID, DistrictName FROM Districten");
    $districts = $district_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$error = ""; // Initialize error message

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voornaam = $_POST['voornaam'];
    $achternaam = $_POST['achternaam'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $district_id = $_POST['district'];
    $id_number = $_POST['id_number'];

    try {
        // Validate inputs
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if email or ID Number already exists
            $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Email = :email OR IDNumber = :id_number");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':id_number', $id_number, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Email or ID Number already registered.";
            } else {
                // Hash password and insert user into the database with default role "User"
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $role = "User"; // Default role
                $insert_stmt = $conn->prepare("INSERT INTO Users (Voornaam, Achternaam, Email, Password, District, Role, IDNumber) VALUES (:voornaam, :achternaam, :email, :password, :district, :role, :id_number)");
                $insert_stmt->bindParam(':voornaam', $voornaam, PDO::PARAM_STR);
                $insert_stmt->bindParam(':achternaam', $achternaam, PDO::PARAM_STR);
                $insert_stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $insert_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $insert_stmt->bindParam(':district', $district_id, PDO::PARAM_INT);
                $insert_stmt->bindParam(':role', $role, PDO::PARAM_STR);
                $insert_stmt->bindParam(':id_number', $id_number, PDO::PARAM_STR);

                if ($insert_stmt->execute()) {
                    header("Location: ../pages/login.php"); // Redirect to login page after successful registration
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
include '../include/nav.php'; // Corrected path to nav.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Body Styling */
        body {
            background-image: url('https://mdbcdn.b-cdn.net/img/Photos/new-templates/bootstrap-login-form/img3.webp');
            background-size: cover;
            background-position: center;
            font-family: Arial, sans-serif;
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Main Content Styling */
        .main-content {
            flex-grow: 1; /* Take up remaining space */
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 40px; /* Add spacing below the navbar */
        }

        /* Register Container */
        .register-container {
            max-width: 400px;
            width: 100%; /* Ensure it takes full width on smaller screens */
            background-color: rgba(255, 255, 255, 0.9); /* Semi-transparent background */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form Styling */
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-control {
            border: 1px solid #7ABD7E;
            border-radius: 20px;
            padding: 10px 40px; /* Extra padding for icons */
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #4C864F;
            box-shadow: none;
        }

        /* Icons */
        .form-group i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #7ABD7E;
        }

        /* Button Styling */
        .btn-register {
            background-color: #7ABD7E;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 10px;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-register:hover {
            background-color: #4C864F;
        }

        /* Error Message */
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
        }

        /* Small Text */
        .small-text {
            text-align: center;
            margin-top: 15px;
        }

        .small-text a {
            color: #7ABD7E;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .small-text a:hover {
            color: #4C864F;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <div class="register-container">
            <h3 class="text-center mb-4">Register</h3>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <!-- First Name Field -->
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="voornaam" class="form-control" name="voornaam" placeholder="First Name" required />
                </div>

                <!-- Last Name Field -->
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="achternaam" class="form-control" name="achternaam" placeholder="Last Name" required />
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" class="form-control" name="email" placeholder="Email Address" required />
                </div>

                <!-- ID Number Field -->
                <div class="form-group">
                    <i class="fas fa-id-card"></i>
                    <input type="text" id="id_number" class="form-control" name="id_number" placeholder="ID Number" required />
                </div>

                <!-- District Field -->
                <div class="form-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <select class="form-control" id="district" name="district" required>
                        <option value="" disabled selected>Select a district</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= htmlspecialchars($district['DistrictID']) ?>">
                                <?= htmlspecialchars($district['DistrictName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" class="form-control" name="password" placeholder="Password" required />
                </div>

                <!-- Confirm Password Field -->
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" class="form-control" name="confirm_password" placeholder="Confirm Password" required />
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-register">Register</button>
            </form>
            <div class="small-text">
                <p>Already have an account? <a href="../pages/login.php">Log in here</a></p>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
include '../include/footer.php'; 
?>