<?php
//Author: Reham Alfaifi 
//Page: login


session_start();
include("db_connect.php");

$remembered_email = $_COOKIE['remember_email'] ?? '';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {

        $error = "Please fill in all fields.";
    }

    else {

        /* CHECK IF ADMIN */

        $check_admin =
        mysqli_query(
            $conn,
            "SELECT admin_id FROM admin WHERE email='$email'"
        );

        if (mysqli_num_rows($check_admin) > 0) {

            $error =
            "This account belongs to an admin. Please use the admin login page.";
        }

        else {

            /* CHECK NORMAL USER */

            $user_sql =
            "SELECT * FROM user
             WHERE email='$email'
             AND password='$password'";

            $user_result =
            mysqli_query($conn, $user_sql);

            if (mysqli_num_rows($user_result) == 1) {

                $user =
                mysqli_fetch_assoc($user_result);

                $_SESSION['user_id'] =
                $user['user_id'];

                if(isset($_POST['remember'])) {

                    setcookie(
                        "remember_email",
                        $email,
                        time() + (86400 * 30),
                        "/"
                    );
                }

                $_SESSION['user_name'] =
                $user['first_name']
                . ' ' .
                $user['last_name'];

                $_SESSION['user_type'] =
                'user';

                header("Location: index.php");

                exit();
            }

            else {

                $error =
                "Incorrect email or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/layout.css">
	<link rel="stylesheet" href="css/login.css?v=10">
    
</head>
<body>

    <!-- HEADER  -->
    <header class="header">
        <div class="header-inner">
            <a class="logo" href="index.php">🌱Bloom House</a>
            <nav class="main-nav">
                <a href="index.php"    class="nav-link">Home</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="about.php"   class="nav-link">About</a>
                <a href="contact.php" class="nav-link">Contact</a>
            </nav>
            <div class="header-actions">
                <a href="search.php"  class="user-icon"><i class="fas fa-search"></i></a>
                <a href="cart.php"    class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
                <a href="login.php"   class="user-icon"><i class="fas fa-user"></i></a>
            </div>
        </div>
    </header>

    <!-- LOGIN CARD -->
    <div class="login-page">
        <div class="login-card">

            <div class="login-logo">🌱</div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-sub">Sign in to your Bloom House account</p>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input
                         type="email"
                         id="email"
                         name="email"
                         placeholder="you@example.com"
                         value="<?= htmlspecialchars($remembered_email ?: ($_POST['email'] ?? '')) ?>"
                         autocomplete="email"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"                         
                            autocomplete="current-password"
                        >
                        <i class="fas fa-eye toggle-pw" onclick="togglePw()"></i>
                    </div>
                </div>

                <div class="forgot-row">
                    <a class="link" href="forget.php">Forgot password?</a>
                </div>
                   <label class="remember-me">
                <input type="checkbox" name="remember" id="remember">
                <span class="custom-check"></span>
                <span class="remember-text">Remember Me</span>
                   </label>
                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <div class="divider">or</div>

            <p class="register-row">
                Don't have an account? <a href="register.php">Create one</a>
            </p>

        </div>
    </div>
<script src="js/login.js?v=2"></script>
</body>
</html>
