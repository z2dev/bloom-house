<?php
// Admin profile page
// Author: Zahra Mohsen

include("admin-auth.php");
include("db_connect.php");

// Fetch admin profile information from database
$admin_id = intval($_SESSION['admin_id']);
$message = "";
$profileUpdated = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($first_name == '' || $last_name == '' || $email == '' || $password == '') {
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $full_name = $first_name . " " . $last_name;

        $stmt = $conn->prepare("UPDATE admin SET full_name = ?, email = ?, password = ? WHERE admin_id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $password, $admin_id);

        if ($stmt->execute()) {
            $profileUpdated = true;
        } else {
            $message = "Error updating profile.";
        }
    }
}

// Get the latest admin data after update
$stmt = $conn->prepare("SELECT * FROM admin WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$name_parts = explode(" ", $admin['full_name']);
$first_name = $name_parts[0];
$last_name = "";

if (isset($name_parts[1])) {
    $last_name = $name_parts[1];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile – Bloom House</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-pages.css">
</head>

<body>

    <?php
    $adminPageTitle = 'Admin Profile';
    $activePage = 'profile';
    include 'admin.php';
    ?>

    <div class="profile-wrap">
        <div class="profile-panel">

            <!-- Profile header with avatar and basic info -->
            <div class="profile-header-clean">
                <div class="profile-avatar-clean">
                    <?php echo substr($admin['full_name'], 0, 1); ?>
                </div>

                <div class="profile-header-text">
                    <h2><?php echo htmlspecialchars($admin['full_name']); ?></h2>
                    <p>Manage your administrator account information</p>
                </div>
            </div>

            <!-- Show error message if validation fails -->
            <?php if ($message != "") { ?>
                <div class="alert-success">
                    <i class="bi bi-exclamation-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <!-- Admin profile update form -->
            <form class="profile-form-clean" method="POST" action="admin-profile.php" id="adminProfileForm">

                <div class="profile-grid-two">
                    <div class="form-group-clean">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first_name"
                            value="<?php echo htmlspecialchars($first_name); ?>" required>
                    </div>

                    <div class="form-group-clean">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last_name"
                            value="<?php echo htmlspecialchars($last_name); ?>" required>
                    </div>
                </div>

                <div class="form-group-clean">
                    <label for="email-address">Email Address</label>
                    <input type="email" id="email-address" name="email"
                        value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                </div>

                <div class="form-group-clean">
                    <label for="password">Password</label>
                    <div class="password-field-clean">
                        <input type="password" id="password" name="password"
                            value="<?php echo htmlspecialchars($admin['password']); ?>" required>
                        <button type="button" class="edit-password-btn">Edit</button>
                    </div>
                </div>

                <div class="profile-actions-clean">
                    <button type="submit" class="btn-save-clean">Save Changes</button>
                </div>

            </form>
        </div>
    </div>

    <script>
        // Show JavaScript success notification after updating the profile
        <?php if ($profileUpdated) { ?>
            var toast = document.createElement("div");

            toast.textContent = "Profile updated successfully!";
            toast.style.position = "fixed";
            toast.style.top = "90px";
            toast.style.right = "24px";
            toast.style.background = "#e8f3ed";
            toast.style.color = "#2f5e4a";
            toast.style.border = "1px solid #cfe2d8";
            toast.style.borderRadius = "12px";
            toast.style.padding = "13px 18px";
            toast.style.fontSize = ".9rem";
            toast.style.fontWeight = "600";
            toast.style.zIndex = "9999";
            toast.style.boxShadow = "0 8px 22px rgba(28, 43, 34, 0.10)";
            toast.style.transition = "opacity .3s ease, transform .3s ease";

            document.body.appendChild(toast);

            setTimeout(function () {
                toast.style.opacity = "0";
                toast.style.transform = "translateY(-10px)";
            }, 2600);

            setTimeout(function () {
                toast.remove();
            }, 3200);
        <?php } ?>

        // Validate admin profile form before submitting
        var adminProfileForm = document.getElementById('adminProfileForm');

        if (adminProfileForm) {
            adminProfileForm.addEventListener('submit', function (e) {
                var firstName = document.getElementById('first-name').value.trim();
                var lastName = document.getElementById('last-name').value.trim();
                var email = document.getElementById('email-address').value.trim();
                var password = document.getElementById('password').value.trim();
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (firstName === '' || lastName === '' || email === '' || password === '') {
                    alert('Please fill in all fields.');
                    e.preventDefault();
                    return;
                }

                if (!emailPattern.test(email)) {
                    alert('Please enter a valid email address.');
                    e.preventDefault();
                    return;
                }

                if (password.length < 6) {
                    alert('Password must be at least 6 characters.');
                    e.preventDefault();
                }
            });
        }
    </script>

</body>

</html>

<?php
mysqli_close($conn);
?>