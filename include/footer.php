<footer class="footer-container">
    <div class="footer-content">
        <!-- Left Section: Logo and Description -->
        <div class="footer-left">
            <a href="#" class="footer-logo">
                <i class="fas fa-crow me-2"></i>
                <span class="logo">E-Stem Suriname</span>
            </a>
            <p class="footer-description">
                Empowering innovation and education in Suriname. Join us on our journey to inspire the next generation.
            </p>
        </div>

        <!-- Middle Section: Quick Links -->
        <div class="footer-middle">
            <h5 class="footer-title">Quick Links</h5>
            <ul class="footer-links">
                <li><a href="#">Home</a></li>
                <li><a href="#">About Us</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
            </ul>
        </div>

        <!-- Right Section: Contact Information -->
        <div class="footer-right">
            <h5 class="footer-title">Contact Us</h5>
            <p class="footer-contact">
                <i class="fas fa-map-marker-alt me-2"></i>Paramaribo, Suriname
            </p>
            <p class="footer-contact">
                <i class="fas fa-envelope me-2"></i>info@estemsuriname.com
            </p>
            <p class="footer-contact">
                <i class="fas fa-phone me-2"></i>+597 123 4567
            </p>
        </div>
    </div>

    <!-- Bottom Section: Copyright -->
    <div class="footer-bottom">
        <p class="footer-copyright">
            &copy; 2023 E-Stem Suriname. All rights reserved.
        </p>
    </div>
</footer>

<style>
    /* Footer Container */
    .footer-container {
        background-color: #f4f4f4; /* Lighter background for contrast */
        padding: 40px 20px;
        margin-top: 60px; /* Pushes footer to the bottom of the page */
        font-family: 'Montserrat', sans-serif;
        color: #333;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
    }

    /* Footer Content Layout */
    .footer-content {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Footer Sections */
    .footer-left,
    .footer-middle,
    .footer-right {
        flex: 1;
        margin: 10px;
        min-width: 250px; /* Ensures sections don't get too small */
    }

    /* Footer Logo */
    .footer-logo {
        font-size: 26px; /* Slightly larger font size */
        text-decoration: none;
        display: flex;
        align-items: center;
        color: #4C864F; /* Logo color */
        transition: transform 0.3s ease, color 0.3s ease; /* Added color transition */
    }

    .footer-logo:hover {
        transform: scale(1.05);
        color: #7ABD7E; /* Change color on hover */
    }

    /* Footer Description */
    .footer-description {
        font-size: 15px; /* Increased font size */
        line-height: 1.8; /* Improved line height for readability */
        margin-top: 10px;
        color: #555;
    }

    /* Footer Title */
    .footer-title {
        font-size: 20px; /* Increased font size */
        font-weight: bold;
        color: #4C864F;
        margin-bottom: 15px;
        border-bottom: 2px solid #7ABD7E; /* Underline effect */
        padding-bottom: 5px; /* Space between title and underline */
    }

    /* Footer Links */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 10px;
    }

    .footer-links a {
        text-decoration: none;
        color: #333;
        transition: color 0.3s ease, padding-left 0.3s ease; /* Added padding transition */
        padding-left: 5px; /* Space for hover effect */
    }

    .footer-links a:hover {
        color: #7ABD7E;
        padding-left: 10px; /* Slide effect on hover */
    }

    /* Footer Contact Information */
    .footer-contact {
        font-size: 15px; /* Increased font size */
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        color: #555; /* Consistent color */
    }

    /* Footer Bottom (Copyright) */
    .footer-bottom {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ccc;
    }

    .footer-copyright {
        font-size: 14px;
        color: #777; /* Slightly lighter color for copyright */
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
        }

        .footer-left,
        .footer-middle,
        .footer-right {
            margin: 20px 0;
        }
    }
</style>