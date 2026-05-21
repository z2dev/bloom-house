<?php
//Author: Reham Alfaifi 
//Page: forget

session_start();
include("db_connect.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email == "" || $password == "") {

        $message = "Please fill in all fields.";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";

    } else {

        $check = $conn->prepare("SELECT password FROM `user` WHERE email=?");

        if (!$check) {
            die("SQL Error: " . $conn->error);
        }

        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows == 0) {

            $message = "Email not found";

        } else {

            $user = $result->fetch_assoc();

            if ($user["password"] == $password) {

                $message = "This password is already being used";

            } else {

                $stmt = $conn->prepare("UPDATE `user` SET password=? WHERE email=?");

                if (!$stmt) {
                    die("SQL Error: " . $conn->error);
                }

                $stmt->bind_param("ss", $password, $email);

                if ($stmt->execute()) {

                    $message = "Password reset successfully";

                } else {

                    $message = "Something went wrong. Please try again.";
                }
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
    <title>Bloom House | Reset Password</title>

    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/forget.css?v=20">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
</head>

<body>

<header class="header">
    <div class="header-inner">
        <a class="logo" href="index.php">🌱Bloom House</a>

        <nav class="main-nav">
            <a href="index.php" class="nav-link">Home</a>
            <a href="products.php" class="nav-link">Products</a>
            <a href="about.php" class="nav-link">About</a>
            <a href="contact.php" class="nav-link">Contact</a>
        </nav>

        <div class="header-actions">
            <a href="search.php" class="user-icon"><i class="fas fa-search"></i></a>
            <a href="cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
            <a href="login.php" class="user-icon"><i class="fas fa-user"></i></a>
        </div>
    </div>
</header>

<div class="reset-page">
    <div class="reset-card">

        <div class="reset-logo">🌱</div>
        <h1 class="reset-title">Reset Your Password</h1>
        <p class="reset-sub">Enter your email and new password</p>

        <?php if ($message != ""): ?>

    <div class="alert-message <?php echo (
    $message == 'Email not found' || 
    $message == 'Password must be at least 6 characters.' ||
    $message == 'Please fill in all fields.'
) ? 'error' : 'success'; ?>">

        <i class="fas fa-info-circle"></i>

        <?php echo htmlspecialchars($message); ?>

    </div>

<?php endif; ?>

        <form method="POST" action="forget.php">

            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                       
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter new password"
                    >
                    <i class="fas fa-eye toggle-pw" onclick="togglePw()"></i>
                </div>
            </div>

            <button class="btn-reset" type="submit">Reset Password</button>

        </form>

        <p class="back-login">
            Back to <a href="login.php">Login</a>
        </p>

    </div>
</div>

<script src="js/forget.js"></script>

</body>
</html>