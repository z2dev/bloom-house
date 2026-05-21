<?php
include("auth.php");
include "db_connect.php";

$user_id = (int) $_SESSION['user_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $gender = $_POST["gender"];
   if (
    !empty($_POST["year"]) &&
    !empty($_POST["month"]) &&
    !empty($_POST["day"])
) {
    $date_of_birth = $_POST["year"] . "-" . $_POST["month"] . "-" . $_POST["day"];
} else {
    $date_of_birth = null;
}

$gender = $_POST["gender"] ?? null;

    $sql = "UPDATE user 
            SET first_name=?, last_name=?, email=?, phone=?, gender=?, date_of_birth=?
            WHERE user_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $gender, $date_of_birth, $user_id);

    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
    } else {
        $message = "Error updating profile.";
    }
}

$sql = "SELECT * FROM user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$dob = $user["date_of_birth"] ?? "";

if (!empty($dob)) {
    $dob_parts = explode("-", $dob);
    $year = $dob_parts[0] ?? "";
    $month = $dob_parts[1] ?? "";
    $day = $dob_parts[2] ?? "";
} else {
    $year = "";
    $month = "";
    $day = "";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bloom House | Profile</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">

  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/profile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">🌱Bloom House</a>

    <nav class="main-nav">
      <a href="index.php" class="nav-link">Home</a>
      <a href="products.php" class="nav-link">Products</a>
      <a href="about.php" class="nav-link">About</a>
      <a href="contact.php" class="nav-link">Contact</a>
    </nav>

    <div class="header-actions">
      <a href="search.php" class="user-icon"><i class="fa-solid fa-magnifying-glass"></i></a>
      <a href="cart.php" class="cart-icon"><i class="fa-solid fa-cart-shopping"></i></a>
      <a href="profile.php" class="user-icon"><i class="fa-solid fa-user"></i></a>
    </div>
  </div>
</header>

<main class="account-page">
  <div class="container">
    <h1 class="account-main-title">My Account</h1>

    <div class="account-layout">

      <aside class="account-sidebar">
        <div class="user-box">
          <div class="user-avatar">
            <span><?php echo strtoupper(substr($user["first_name"], 0, 1)); ?></span>
          </div>

          <div class="user-meta">
            <div class="user-name">
              <?php echo htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?>
            </div>
            <div class="user-email">
              <?php echo htmlspecialchars($user["email"]); ?>
            </div>
          </div>
        </div>

        <div class="sidebar-section-title">ACCOUNT</div>
        <ul class="sidebar-menu">
          <li><a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Profile</a></li>
          <li><a href="favourites.php"><i class="fa-solid fa-heart"></i> Favourite</a></li>
        </ul>

        <div class="sidebar-section-title">ORDERS & SUPPORT</div>
        <ul class="sidebar-menu">
          <li><a href="past-orders.php"><i class="fa-solid fa-box"></i> Past Orders</a></li>
          <li><a href="faq.php"><i class="fa-solid fa-circle-question"></i> FAQ</a></li>
          <li>
            <a href="logout.php" class="logout-link">
              <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
          </li>
        </ul>
      </aside>

      <section class="profile-card">
        <h2 class="profile-card-title">Edit Information</h2>

        <?php if ($message != "") { ?>
          <p style="color: green; margin-bottom: 15px;">
            <?php echo $message; ?>
          </p>
        <?php } ?>

        <form class="profile-form" method="POST" action="profile.php">

          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="first_name"
              value="<?php echo htmlspecialchars($user["first_name"]); ?>" required>
          </div>

          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="last_name"
              value="<?php echo htmlspecialchars($user["last_name"]); ?>" required>
          </div>

          <div class="form-group full-width">
    <label>
        Date of Birth <span class="optional">(Optional)</span>
    </label>

    <div class="dob-grid">

        <select name="month">
            <option value="">Month</option>

            <?php
            for ($m = 1; $m <= 12; $m++) {
                $value = str_pad($m, 2, "0", STR_PAD_LEFT);
                $selected = ($value == $month) ? "selected" : "";
                echo "<option value='$value' $selected>$value</option>";
            }
            ?>
        </select>

        <select name="day">
            <option value="">Day</option>

            <?php
            for ($d = 1; $d <= 31; $d++) {
                $value = str_pad($d, 2, "0", STR_PAD_LEFT);
                $selected = ($value == $day) ? "selected" : "";
                echo "<option value='$value' $selected>$value</option>";
            }
            ?>
        </select>

        <select name="year">
            <option value="">Year</option>

            <?php
            for ($y = 1980; $y <= 2010; $y++) {
                $selected = ($y == $year) ? "selected" : "";
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
        </select>

    </div>
</div>

<div class="form-group">
    <label for="gender">
        Gender <span class="optional">(Optional)</span>
    </label>

    <select id="gender" name="gender">

        <option value="">Select</option>

        <option value="Female"
            <?php if (($user["gender"] ?? '') == "Female") echo "selected"; ?>>
            Female
        </option>

        <option value="Male"
            <?php if (($user["gender"] ?? '') == "Male") echo "selected"; ?>>
            Male
        </option>

    </select>
</div>
           
        <div class="form-group">
  <label for="phone">Phone</label>
  <input type="text"
         id="phone"
         name="phone"
         maxlength="10"
         pattern="[0-9]{10}"
         inputmode="numeric"
         value="<?php echo htmlspecialchars($user["phone"]); ?>"
         required>
</div>

          <div class="form-group full-width">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
              value="<?php echo htmlspecialchars($user["email"]); ?>" required>
          </div>

          <div class="form-actions full-width">
            <button type="submit" class="btn-save">Save</button>
            <button type="reset" class="btn-cancel">Cancel</button>
          </div>

        </form>
      </section>

    </div>
  </div>
</main>
<script src="js/profile.js"></script>
</body>
</html>