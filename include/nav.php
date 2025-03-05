<?php
// Fetch user details if logged in
$userName = '';
if (isset($_SESSION['User ID'])) {
    $stmt = $conn->prepare("SELECT Voornaam FROM users WHERE UserID = :userID");
    $stmt->execute(['userID' => $_SESSION['User ID']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userName = $user ? htmlspecialchars($user['Voornaam']) : 'User ';
}
?>
<div class="navbar-container">
    <nav class="navbar navbar-expand-lg navbar-light rounded-pill shadow-sm" style="background-color: #D9D9D9; margin-top: 20px; max-width: 90%; margin-left: auto; margin-right: auto;">
        <div class="container-fluid px-3">
            <button class="navbar-toggler border-0 hamburger-button" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand d-lg-none mobile-logo" href="#" style="font-family: 'Montserrat', sans-serif; font-weight: bold; color: #4C864F;">
                <i class="fas fa-crow me-2" style="color: #7ABD7E;"></i>
                <span class="logo">Logo</span>
            </a>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link menu-item" href="../index.php" aria-current="page">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-item" href="#">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-item" href="#">Contact</a>
                    </li>
                    <!-- Add more links or dropdowns here -->
                </ul>
                
                <a class="navbar-brand d-none d-lg-flex align-items-center desktop-logo" href="#" style="font-family: 'Montserrat', sans-serif; font-weight: bold; color: #4C864F; position: absolute; left: 50%; transform: translateX(-50%);">
                    <i class="fas fa-crow me-2" style="color: #7ABD7E;"></i>
                    <span class="logo">Logo</span>
                </a>
                
                <div class="d-lg-none mt-3 mb-2 text-center">
                    <?php if (isset($_SESSION['User ID'])): ?>
                        <button class="btn btn-success me-2 pulse-on-hover" style="background-color: #7ABD7E; border: none;">
                            <i class="fas fa-user me-1"></i><?= $userName ?>
                        </button>
                        <a href="../logout.php" class="btn btn-outline-danger mt-2 mt-sm-0 pulse-on-hover" style="border-color: #4C864F; color: #4C864F;">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <a href="../login.php" class="btn btn-success pulse-on-hover" style="background-color: #7ABD7E; border: none;">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-none d-lg-flex align-items-center ms-auto">
                <?php if (isset($_SESSION['User ID'])): ?>
                    <button class="btn btn-success me-2 pulse-on-hover" style="background-color: #7ABD7E; border: none;">
                        <i class="fas fa-user me-1"></i><?= $userName ?>
                    </button>
                    <a href="../logout.php" class="btn btn-outline-danger pulse-on-hover" style="border-color: #4C864F; color: #4C864F;">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a href="../login.php" class="btn btn-success pulse-on-hover" style="background-color: #7ABD7E; border: none;">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</div>

<style>
    /* Custom Font */
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap');

    /* Navbar Styles */
    .navbar {
        padding-top: 10px;
        padding-bottom: 10px;
        margin-bottom: 50px;
    }

    .navbar-brand {
        font-size: 20px;
        transition: transform 0.3s ease, color 0.3s ease;
    }

    .navbar-brand:hover {
        transform: scale(1.05);
        color: #4C864F !important;
    }

    .nav-link {
        position: relative;
        font-family: 'Montserrat', sans-serif;
        font-weight: 500;
        color: black;
        transition: color 0.3s ease;
    }

    .nav-link:hover {
        color: #7ABD7E !important;
    }

    .nav-link.active {
        color: #7ABD7E !important;
        font-weight: bold;
    }

    /* Button Styles */
    .btn {
        border-radius: 50px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .navbar-brand {
            font-size: 18px;
        }
        .nav-link {
            font-size: 14px;
            text-align: center;
            margin: 5px 0;
        }
        .navbar-collapse {
            background-color: #D9D9D9;
            border-radius: 15px;
            padding: 10px;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    }

    @media (max-width: 768px) {
        .navbar-brand {
            font-size: 16px;
        }
        .nav-link {
            font-size: 12px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>