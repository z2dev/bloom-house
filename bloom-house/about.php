<?php
//Author: Reham Alfaifi 
//Page: about


session_start();

$profileLink = "login-required.php";

if (isset($_SESSION["user_id"])) {
    $profileLink = "profile.php";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | Bloom House</title>

    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/about.css?v=30">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
</head>

<body>
<?php if (!isset($_SESSION['user_id'])) { ?>
    <div class="promo-bar">
        <div class="container promo-content">
            <p class="promo-text">
                Sign up and get <strong>15% OFF</strong> your first plant order
                <a href="register.php" class="promo-link">Sign up now</a>
            </p>
        </div>
    </div>
    <?php } ?>
<header class="header">
    <div class="header-inner">

        <a class="logo" href="index.php">🌱Bloom House</a>

        <nav class="main-nav">
            <a href="index.php" class="nav-link">Home</a>
            <a href="products.php" class="nav-link">Products</a>
            <a href="about.php" class="nav-link active">About</a>
            <a href="contact.php" class="nav-link">Contact</a>
        </nav>

        <div class="header-actions">
            <a href="search.php" class="user-icon"><i class="fas fa-search"></i></a>
            <a href="cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
            <a href="<?php echo $profileLink; ?>" class="user-icon"><i class="fas fa-user"></i></a>
        </div>

    </div>
</header>

<section class="hero">

    <h1>About Bloom House</h1>

    <p>
        We bring nature closer to you by offering carefully selected indoor plants
        and simple care guidance for every home.
    </p>

</section>

<section class="about-card">

    <h2>Who We Are</h2>

    <p>
        At Bloom House, we believe that every home deserves a touch of nature.
        Our mission is to make indoor plants simple, beautiful, and accessible for everyone.
    </p>

    <p>
        We carefully select healthy, high-quality plants and provide clear care instructions
        so you can grow with confidence.
    </p>

    <p>
        Whether you're a beginner or a plant lover,
        we’re here to help you create a calm and green space.
    </p>

    <div class="features">

        <div class="feature-box">
            <i class="fas fa-seedling"></i>
            <h3>Quality Plants</h3>
            <p>Carefully selected healthy indoor plants.</p>
        </div>

        <div class="feature-box">
            <i class="fas fa-book-open"></i>
            <h3>Care Guidance</h3>
            <p>Simple instructions for confident plant care.</p>
        </div>

        <div class="feature-box">
            <i class="fas fa-truck"></i>
            <h3>Fast Delivery</h3>
            <p>Safe and quick delivery to your doorstep.</p>
        </div>

    </div>

</section>

<footer class="footer">

    <div class="footer-container">

        <div class="footer-brand">

            <a class="footer-logo" href="index.php">🌱 Bloom House</a>

            <p>Bringing nature into your home with beautiful indoor plants.</p>

            <div class="social-icons">
                <a href="#"><i class="bi bi-whatsapp"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-snapchat"></i></a>
            </div>

        </div>

        <div class="footer-col">

            <h3>Customer Support</h3>

            <ul>
                <li><a href="faq.php">FAQs</a></li>
                <li><a href="contact.php">Contact Us</a></li>
            </ul>

        </div>

        <div class="footer-col">

            <h3>Quick Links</h3>

            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="favourites.php">Favourites</a></li>
                <li><a href="<?php echo $profileLink; ?>">Profile</a></li>
            </ul>

        </div>

        <div class="footer-col footer-contact">

            <h3>Contact</h3>

            <ul>
                <li><i class="bi bi-envelope"></i> support@bloomhouse.com</li>
                <li><i class="bi bi-telephone"></i> +966 123 456 789</li>
                <li><i class="bi bi-geo-alt"></i> Eastern Region, KSA</li>
                <li><i class="bi bi-clock"></i> Sat–Thu: 7AM–9PM • Fri: 11PM–10PM</li>
            </ul>

        </div>

    </div>

    <div class="footer-bottom">
        <p>© 2026 Bloom House. All rights reserved.</p>
    </div>

</footer>
<script src="js/about.js"></script>
</body>

</html>