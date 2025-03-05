<?php
session_start();
require '../db.php'; // Include your database connection file
$error = ""; // Initialize error message

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT UserID, Password, Role FROM Users WHERE Email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['Password'])) {
            // Set session variables
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['Role'] = $user['Role'];

            // Redirect based on role
            if ($user['Role'] === 'Admin') {
                header("Location: ../admin_dashboard.php"); // Admin dashboard
            } else {
                header("Location: ../user_dashboard.php"); // User dashboard
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Include the navbar
include '../include/nav.php'; // Corrected path to nav.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
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

        /* Login Container */
        .login-container {
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
        .btn-login {
            background-color: #7ABD7E;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 10px;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-login:hover {
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

        /* Add margin below the navbar */
        nav {
            margin-bottom: 40px; /* Adjust this value as needed */
        }

        /* Add margin above the footer */
        footer {
            margin-top: 40px; /* Adjust this value as needed */
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <div class="login-container">
            <h3 class="text-center mb-4">Log In</h3>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <!-- Email Field -->
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" class="form-control" name="email" placeholder="Email Address" required />
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" class="form-control" name="password" placeholder="Password" required />
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-login">Login</button>
            </form>
            <div class="small-text">
                <p><a href="#!">Forgot password?</a></p>
                <p>Don't have an account? <a href="../pages/register.php">Register here</a></p>
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