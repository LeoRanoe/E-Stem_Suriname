<?php
// Set the header to output an image
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suriname Placeholder Images</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: #007847;
            text-align: center;
        }
        
        .placeholders {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .placeholder {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .placeholder-title {
            background: #007847;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
        }
        
        .placeholder-image {
            width: 100%;
            height: 200px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }
        
        .placeholder-details {
            padding: 15px;
            font-size: 14px;
            color: #555;
        }
        
        .placeholder-link {
            display: block;
            margin-top: 10px;
            color: #007847;
            text-decoration: none;
        }
        
        .placeholder-link:hover {
            text-decoration: underline;
        }
        
        /* Suriname Flag Pattern */
        .suriname-flag {
            background: linear-gradient(to bottom,
                #007847 0%,
                #007847 20%,
                white 20%,
                white 40%,
                #C8102E 40%,
                #C8102E 60%,
                white 60%,
                white 80%,
                #007847 80%,
                #007847 100%
            );
        }
        
        /* Star overlay */
        .suriname-flag::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background-color: #FFD100;
            clip-path: polygon(
                50% 0%, 
                61% 35%, 
                98% 35%, 
                68% 57%, 
                79% 91%, 
                50% 70%, 
                21% 91%, 
                32% 57%, 
                2% 35%, 
                39% 35%
            );
        }
        
        /* Suriname Pattern */
        .suriname-pattern {
            background-color: #007847;
            background-image: 
                radial-gradient(rgba(255, 255, 255, 0.2) 1px, transparent 1px),
                radial-gradient(rgba(255, 255, 255, 0.2) 1px, transparent 1px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
        }
        
        /* Suriname Nature Pattern */
        .suriname-nature {
            background: #007847;
            background-image: 
                linear-gradient(45deg, rgba(0, 0, 0, 0.1) 25%, transparent 25%, transparent 75%, rgba(0, 0, 0, 0.1)),
                linear-gradient(-45deg, rgba(0, 0, 0, 0.1) 25%, transparent 25%, transparent 75%, rgba(0, 0, 0, 0.1));
            background-size: 60px 60px;
        }
        
        /* Login Background */
        .login-bg {
            background-color: #f0f8f4;
            background-image: 
                radial-gradient(rgba(0, 120, 71, 0.1) 1px, transparent 1px),
                radial-gradient(rgba(0, 120, 71, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
        }
        
        /* Admin Dashboard Background */
        .admin-bg {
            background-color: #007847;
            background-image: 
                linear-gradient(30deg, rgba(255, 255, 255, 0.1) 12%, transparent 12.5%, transparent 87%, rgba(255, 255, 255, 0.1) 87.5%, rgba(255, 255, 255, 0.1)),
                linear-gradient(150deg, rgba(255, 255, 255, 0.1) 12%, transparent 12.5%, transparent 87%, rgba(255, 255, 255, 0.1) 87.5%, rgba(255, 255, 255, 0.1)),
                linear-gradient(30deg, rgba(255, 255, 255, 0.1) 12%, transparent 12.5%, transparent 87%, rgba(255, 255, 255, 0.1) 87.5%, rgba(255, 255, 255, 0.1)),
                linear-gradient(150deg, rgba(255, 255, 255, 0.1) 12%, transparent 12.5%, transparent 87%, rgba(255, 255, 255, 0.1) 87.5%, rgba(255, 255, 255, 0.1));
            background-size: 80px 140px;
            background-position: 0 0, 0 0, 40px 70px, 40px 70px;
        }
        
        .btn {
            display: inline-block;
            background: #007847;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .btn:hover {
            background: #006238;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Suriname Placeholder Images</h1>
        <p style="text-align: center;">These are visual references for the E-Stem Suriname application styling.</p>
        
        <div class="placeholders">
            <div class="placeholder">
                <div class="placeholder-title">Suriname Flag</div>
                <div class="placeholder-image suriname-flag">
                    <span>800 × 400</span>
                </div>
                <div class="placeholder-details">
                    Flag-inspired pattern with the Surinamese colors: green, white, red, and the yellow star.
                    <a href="suriname-flag.png" class="placeholder-link" download>Download Image</a>
                </div>
            </div>
            
            <div class="placeholder">
                <div class="placeholder-title">Suriname Pattern</div>
                <div class="placeholder-image suriname-pattern">
                    <span>800 × 800</span>
                </div>
                <div class="placeholder-details">
                    Dot pattern using the Surinamese green color as a subtle background texture.
                    <a href="suriname-pattern.png" class="placeholder-link" download>Download Image</a>
                </div>
            </div>
            
            <div class="placeholder">
                <div class="placeholder-title">Suriname Nature</div>
                <div class="placeholder-image suriname-nature">
                    <span>800 × 600</span>
                </div>
                <div class="placeholder-details">
                    Pattern inspired by Surinamese nature and jungle motifs.
                    <a href="suriname-nature.png" class="placeholder-link" download>Download Image</a>
                </div>
            </div>
            
            <div class="placeholder">
                <div class="placeholder-title">Login Background</div>
                <div class="placeholder-image login-bg">
                    <span>1200 × 800</span>
                </div>
                <div class="placeholder-details">
                    Subtle dot pattern on light green background for login screens.
                    <a href="login-bg.png" class="placeholder-link" download>Download Image</a>
                </div>
            </div>
            
            <div class="placeholder">
                <div class="placeholder-title">Admin Dashboard</div>
                <div class="placeholder-image admin-bg">
                    <span>1200 × 800</span>
                </div>
                <div class="placeholder-details">
                    Geometric pattern for admin dashboard backgrounds.
                    <a href="admin-bg.png" class="placeholder-link" download>Download Image</a>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p>These patterns can be directly used in CSS using the provided classes in suriname-style.css.</p>
            <a href="../../../index.php" class="btn">Return to Homepage</a>
        </div>
    </div>
</body>
</html> 