<?php
session_start();
require_once 'include/db_connect.php';
require_once 'include/auth.php';

// Check if user is logged in and is a voter
requireVoter();

// Get current user's data
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stem Succesvol - E-Stem Suriname</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007749;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        .main-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
            text-align: center;
        }
        .success-icon {
            color: #007749;
            font-size: 4em;
            margin-bottom: 20px;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            background-color: #007749;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #006241;
        }
        .info-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>E-Stem Suriname</h1>
        <p>Bedankt voor uw stem!</p>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="success-icon">✓</div>
            
            <div class="success-message">
                <h2>Uw stem is succesvol geregistreerd!</h2>
                <p>Bedankt voor uw deelname aan de verkiezingen.</p>
            </div>

            <div class="info-box">
                <h3>Belangrijke informatie:</h3>
                <ul>
                    <li>U kunt slechts één keer stemmen per verkiezing</li>
                    <li>Uw stem is anoniem en kan niet worden teruggehaald</li>
                    <li>De verkiezingsresultaten worden bekendgemaakt na sluiting van de stembus</li>
                </ul>
            </div>

            <a href="index.php" class="btn">Terug naar home</a>
        </div>
    </div>
</body>
</html>