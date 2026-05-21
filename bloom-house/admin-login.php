<?php
// Author: Zahra Mohsen

// Start the session so we can store and read admin login information.
session_start();

require_once 'db_connect.php';

// If the admin is already logged in, send them directly to the admin dashboard.
if (isset($_SESSION['admin_id'])) {
    header('Location: admin-dashboard.php');
    exit();
}

// Create variables to store success and error messages shown on the login page.
$error = '';
$success = '';

// Show a success message when the admin comes back after logging out.
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'You have been logged out successfully.';
}

// Run the login checking code only when the form is submitted using POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the email and password from the form and remove extra spaces.
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Make sure the admin did not leave the email or password fields empty.
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {

        // First, check if the entered email belongs to a normal customer account.
        // This prevents normal users from logging in through the admin login page.
        $check_user = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
        $check_user->bind_param('s', $email);
        $check_user->execute();
        $user_result = $check_user->get_result();

        // If the email exists in the user table, show a message and do not continue admin login.
        if ($user_result->num_rows > 0) {
            $error = 'This account belongs to a user. Please use the user login page.';
        } else {

            // Check the admin table for an admin account with the entered email.
            $stmt = $conn->prepare("SELECT admin_id, full_name, password FROM admin WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // If one admin account is found, fetch its data from the database.
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                // Compare the entered password with the admin password stored in the database.
                if ($password === $admin['password']) {

                    // Save the admin information in the session after successful login.
                    $_SESSION['admin_id']   = $admin['admin_id'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['user_type']  = 'admin';

                    // Redirect the logged-in admin to the dashboard and stop this page.
                    header('Location: admin-dashboard.php');
                    exit();
                } else {

                    // Show an error if the password is wrong.
                    $error = 'Incorrect email or password.';
                }
            } else {

                // Show an error if no admin account is found with this email.
                $error = 'Incorrect email or password.';
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
    <title>Bloom House | Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-pages.css">
    <link rel="stylesheet" href="css/admin-login.css">
</head>
<body>

<!-- Main container that holds the left information side and the login form side. -->
<div class="admin-login-shell">

    <!-- Left side section that shows the Bloom House admin panel branding. -->
    <div class="admin-login-side">
        <div>
            <!-- Brand link that takes the admin back to the website home page. -->
            <a class="admin-brand" href="index.php">
                🌱 Bloom House
                <span>Admin Panel</span>
            </a>

            <!-- Short text explaining what the admin can manage after login. -->
            <h1>Manage your store with a clean admin dashboard.</h1>
            <p>Sign in to update products, review orders, manage customer messages, and keep Bloom House organized.</p>
        </div>

        <!-- Small security note to show that this page is only for authorized admins. -->
        <div class="side-note">
            <i class="bi bi-shield-check"></i>
            Authorized admin access only.
        </div>
    </div>

    <!-- Right side section that contains the admin login card. -->
    <div class="admin-login-form-area">
        <div class="admin-login-card">

            <!-- Login card heading section. -->
            <div class="login-kicker"><i class="bi bi-grid"></i> Admin Login</div>
            <h2>Welcome back</h2>
            <p class="login-sub">Enter your admin email and password to continue.</p>

            <!-- Show logout success message when the admin logs out correctly. -->
            <?php if ($success): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- Show login error messages such as empty fields or wrong account details. -->
            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Admin login form that sends the email and password to this same PHP page. -->
            <form method="POST" action="admin-login.php" id="adminLoginForm">

                <!-- Email input field for the admin account email. -->
                <div class="login-form-group">
                    <label for="email">Admin Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" id="email" name="email"
                               placeholder="admin@bloomhouse.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required autocomplete="email">
                    </div>
                </div>

                <!-- Password input field with an eye icon to show or hide the password. -->
                <div class="login-form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="password" name="password"
                               placeholder="Enter your password"
                               required autocomplete="current-password">
                        <i class="fas fa-eye toggle-pw" onclick="togglePw()"></i>
                    </div>
                </div>

                <!-- Submit button used to send the admin login form. -->
                <button type="submit" class="btn-admin-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In as Admin
                </button>
            </form>

            <!-- Link that allows the admin to return to the normal website. -->
            <a href="index.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Website</a>
        </div>
    </div>
</div>

<script>
    // This function switches the password field between hidden and visible.
    function togglePw() {
        const input = document.getElementById('password');
        const icon  = document.querySelector('.toggle-pw');

        // If the password is hidden, show it and change the eye icon.
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            // If the password is visible, hide it again and return the eye icon.
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Get the admin login form so we can validate it before sending it to PHP.
    var adminLoginForm = document.getElementById('adminLoginForm');

    // Add client-side validation only if the form exists on the page.
    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', function(e) {

            // Read the email and password values from the form fields.
            var email = document.getElementById('email').value.trim();
            var password = document.getElementById('password').value.trim();

            // Simple pattern to check that the email format is valid.
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            // Stop the form if any required field is empty.
            if (email === '' || password === '') {
                alert('Please fill in all fields.');
                e.preventDefault();
                return;
            }

            // Stop the form if the email format is not correct.
            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address.');
                e.preventDefault();
            }
        });
    }
</script>

</body>
</html>
