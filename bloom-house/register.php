<?php
//Author: Reham Alfaifi 
//Page: register

session_start();
require_once 'db_connect.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name    = trim($_POST['first_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $confirm_pw    = trim($_POST['confirm_pw'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $gender        = trim($_POST['gender'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');

    if ($gender === '') {
        $gender = null;
    }

    if ($date_of_birth === '') {
        $date_of_birth = null;
    }

    /* VALIDATION */

    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($email) ||
        empty($password) ||
        empty($phone)
    ) {

        $error = 'Please fill in all required fields.';
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';
    }

    elseif (strlen($password) < 6) {

        $error = 'Password must be at least 6 characters.';
    }

    elseif ($password !== $confirm_pw) {

        $error = 'Passwords do not match.';
    }

    else {

        $check = $conn->prepare(
            "SELECT user_id FROM user WHERE email = ?"
        );

        $check->bind_param('s', $email);

        $check->execute();

        $check->store_result();

        if ($check->num_rows > 0) {

            $error =
            'This email is already registered.
            <a href="login.php">Sign in instead</a>.';
        }

        else {

            $stmt = $conn->prepare("
                INSERT INTO user
                (
                    first_name,
                    last_name,
                    email,
                    password,
                    phone,
                    gender,
                    date_of_birth
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'sssssss',
                $first_name,
                $last_name,
                $email,
                $password,
                $phone,
                $gender,
                $date_of_birth
            );

            if ($stmt->execute()) {

                $_SESSION['user_id'] = $conn->insert_id;

                $_SESSION['user_name'] =
                $first_name . ' ' . $last_name;

                header('Location: index.php');

                exit();
            }

            else {

                $error =
                'Something went wrong. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Bloom House | Register</title>

    <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="css/layout.css">

    <link rel="stylesheet"
          href="css/register.css?v=40">

</head>

<body>

<header class="header">

    <div class="header-inner">

        <a class="logo" href="index.php">
            🌱Bloom House
        </a>

        <nav class="main-nav">

            <a href="index.php"
               class="nav-link">
               Home
            </a>

            <a href="products.php"
               class="nav-link">
               Products
            </a>

            <a href="about.php"
               class="nav-link">
               About
            </a>

            <a href="contact.php"
               class="nav-link">
               Contact
            </a>

        </nav>

        <div class="header-actions">

            <a href="search.php"
               class="user-icon">
               <i class="fas fa-search"></i>
            </a>

            <a href="cart.php"
               class="cart-icon">
               <i class="fas fa-shopping-cart"></i>
            </a>

            <a href="login.php"
               class="user-icon">
               <i class="fas fa-user"></i>
            </a>

        </div>

    </div>

</header>

<div class="register-page">

    <div class="register-card">

        <div class="register-logo">🌱</div>

        <h1 class="register-title">
            Create Account
        </h1>

        <p class="register-sub">
            Join Bloom House and start your plant journey
        </p>

        <!-- PHONE MESSAGE -->

        <div id="phoneMessage"
             class="phone-message">
        </div>

        <!-- PHP ERROR -->

        <?php if ($error): ?>

            <div class="alert-error">

                <i class="fas fa-exclamation-circle"></i>

                <span><?= $error ?></span>

            </div>

        <?php endif; ?>

        <form method="POST"
              action="register.php">

            <!-- NAME -->

            <div class="form-row">

                <div class="form-group">

                    <label for="first_name">
                        First Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-user icon"></i>

                        <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        placeholder="Reham"
                        value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label for="last_name">
                        Last Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-user icon"></i>

                        <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        placeholder="Alfaifi"
                        value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                        >

                    </div>

                </div>

            </div>

            <!-- EMAIL -->

<div class="form-group">

    <label for="email">
        Email Address
    </label>

    <div class="input-wrapper">

        <i class="fas fa-envelope icon"></i>

        <input
        type="text"
        id="email"
        name="email"
        placeholder="you@example.com"
        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
        autocomplete="email">

    </div>
</div>
          
            <!-- PHONE -->

            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <div class="phone-wrapper">

                    <div class="input-wrapper country-select">

                        <select
                        id="country_code"
                        name="country_code"
                        onchange="updatePhonePlaceholder()">

                            <option
                            value="+966"
                            data-placeholder="05XXXXXXXX"
                            data-length="10">
                            🇸🇦 +966
                            </option>

                            <option
                            value="+965"
                            data-placeholder="5XXXXXXX"
                            data-length="8">
                            🇰🇼 +965
                            </option>

                            <option
                            value="+974"
                            data-placeholder="3XXXXXXX"
                            data-length="8">
                            🇶🇦 +974
                            </option>

                        </select>

                    </div>

                    <div class="input-wrapper">

                        <input
                        type="tel"
                        name="phone"
                        id="phone"
                        placeholder="05XXXXXXXX"
                        >

                    </div>

                </div>

            </div>

            <!-- GENDER + DOB -->

            <div class="form-row">

                <div class="form-group">

                    <label for="gender">
                        Gender
                        <span class="optional">
                            (Optional)
                        </span>
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-venus-mars icon"></i>

                        <select id="gender"
                                name="gender">

                            <option value=""
                            disabled
                            <?= empty($_POST['gender']) ? 'selected' : '' ?>>

                                Select

                            </option>

                            <option value="Female"
                            <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>

                                Female

                            </option>

                            <option value="Male"
                            <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>

                                Male

                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-group">

                    <label for="date_of_birth">
                        Date of Birth
                        <span class="optional">
                            (Optional)
                        </span>
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-calendar icon"></i>

                        <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">

                    </div>

                </div>

            </div>

            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-lock icon"></i>

                    <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="At least 6 characters"
                    autocomplete="new-password"
                    oninput="checkStrength(this.value)">

                    <i class="fas fa-eye toggle-pw"
                       onclick="togglePw('password', this)">
                    </i>

                </div>

                <div class="pw-strength">

                    <div class="pw-strength-bar"
                         id="strengthBar">
                    </div>

                </div>

            </div>

            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_pw">
                    Confirm Password
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-lock icon"></i>

                    <input
                    type="password"
                    id="confirm_pw"
                    name="confirm_pw"
                    placeholder="Repeat your password"
                    autocomplete="new-password">

                    <i class="fas fa-eye toggle-pw"
                       onclick="togglePw('confirm_pw', this)">
                    </i>

                </div>

            </div>

            <!-- BUTTON -->

            <button type="submit"
                    class="btn-register">

                Create Account

            </button>

        </form>

        <div class="divider">or</div>

        <p class="login-row">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </p>

    </div>

</div>

<script src="js/register.js?v=50"></script>

</body>
</html>