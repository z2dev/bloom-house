<?php
// Author: Zahra Mohsen

// Start the session so the page can check if the user is already logged in.
session_start();
$redirect = isset($_GET['redirect']) ? basename($_GET['redirect']) : 'faq.php';
$loginLink = 'login.php?redirect=' . urlencode($redirect);
http_response_code(403);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Access Denied</title>
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/error-pages.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
</head>

<body>
    <?php // Check if the user session already exists.
    if (!isset($_SESSION['user_id'])) { ?>
        <div class="promo-bar">
            <div class="container promo-content">
                <!-- Short message explaining that the user must log in first. -->
                <p class="promo-text">
                    Sign up and get <strong>15% OFF</strong> your first plant order
                    <!-- Button/link that takes new users to the registration page. -->
                    <a href="register.php" class="promo-link">Sign up now</a>
                </p>
            </div>
        </div>
    <?php } ?>

    <!-- Page header section -->
    <header class="header">
        <div class="header-inner">
            <a class="logo" href="index.php">🌱Bloom House</a>

            <!-- Navigation section -->
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="contact.php" class="nav-link">Contact</a>
            </nav>
            <div class="header-actions">
                <a href="search.php" class="user-icon"><i class="fas fa-search"></i></a>
                <a href="cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
                <a href="profile.php" class="user-icon"><i class="fas fa-user"></i></a>
            </div>
        </div>
    </header>


    <!-- Main section holds the login required message. -->
    <main class="error-page">
        <section class="error-section container">
            <div class="error-card access-card">
                <div class="error-content">
                    <nav class="breadcrumb">
                        <a href="index.php">Home</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Access Denied</span>
                    </nav>

                    <span class="error-label denied-label">Login Required</span>
                    <h1>You need to login first.</h1>
                    <p>
                        This page is available for registered users only. Please login to continue shopping,
                        add favourites, or complete your order.
                    </p>

                    <div class="error-actions">
                        <a href="<?php echo htmlspecialchars($loginLink); ?>" class="error-btn primary-btn">Login</a>
                        <a href="register.php" class="error-btn secondary-btn">Create Account</a>
                    </div>
                </div>

                <div class="error-visual" aria-hidden="true">
                    <div class="error-number access-number">403</div>
                    <div class="lock-plant">
                        <span class="lock-circle"></span>
                        <span class="lock-body"><i class="bi bi-lock-fill"></i></span>
                        <span class="small-leaf leaf-left"></span>
                        <span class="small-leaf leaf-right"></span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer section -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <a class="footer-logo" href="index.php">🌱 Bloom House</a>
                <p>Bringing nature into your home with beautiful indoor plants.</p>
                <div class="social-icons">
                    <a href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" aria-label="Snapchat"><i class="bi bi-snapchat"></i></a>
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
                    <li><a href="profile.php">Profile</a></li>
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
            <p>&copy; 2026 Bloom House. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>