<?php
//Author: Reham Alfaifi 
//Page: register

session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Welcome</title>

    <link rel="stylesheet" href="css/start.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
</head>

<body>

<div class="start-page">

    <div class="left-side">
        <div class="overlay"></div>

        <div class="brand-content">
            <div class="logo">🌱Bloom House</div>
            <h1>Bring Nature Into Your Home</h1>
            <p>Discover beautiful indoor plants and simple care guidance for every space.</p>
        </div>
    </div>

    <div class="right-side">

        <div class="login-panel">

            <div class="small-logo">🌱</div>
            <h2>Welcome to Bloom House</h2>
            <p class="subtitle">Choose how you want to continue</p>

            <div class="options">

                <a href="login.php" class="option-card">
                    <i class="fa-solid fa-user"></i>
                    <span>User Login</span>
                </a>

                <a href="admin-login.php" class="option-card">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Admin Login</span>
                </a>

            </div>
                 <a href="index.php" class="guest-link">
                    <span>Continue as Guest</span>
                 </a>
            <a href="register.php" class="create-link">Create a new account</a>

        </div>

    </div>

</div>

<script src="js/start.js"></script>
</body>
</html>