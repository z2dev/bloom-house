<?php
include("auth.php");
include("db_connect.php");

$user_id = (int) $_SESSION['user_id'];


$user_stmt = $conn->prepare("SELECT * FROM `user` WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$fav_stmt = $conn->prepare("
    SELECT DISTINCT plants.*
    FROM favourite
    INNER JOIN plants ON favourite.plant_id = plants.plant_id
    WHERE favourite.user_id = ?
");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$fav_result = $fav_stmt->get_result();
$fav_plants = $fav_result->fetch_all(MYSQLI_ASSOC);
if(isset($_POST['add_to_cart'])){

    $plant_id = (int)$_POST['plant_id'];

    if(!isset($_SESSION['cart'])){
        $_SESSION['cart'] = [];
    }

    if(isset($_SESSION['cart'][$plant_id])){
        $_SESSION['cart'][$plant_id]++;
    } else {
        $_SESSION['cart'][$plant_id] = 1;
    }
	$cart_message = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Favourites</title>
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<script src="js/favourites.js"></script>
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
            <a href="profile.php" class="user-icon"><i class="fas fa-user"></i></a>
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
                        <span><?php echo strtoupper(substr($user['first_name'], 0, 1)); ?></span>
                    </div>

                    <div class="user-meta">
                        <div class="user-name">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </div>
                        <div class="user-email">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                    </div>
                </div>

                <div class="sidebar-section-title">ACCOUNT</div>
                <ul class="sidebar-menu">
                    <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                    <li><a href="favourites.php" class="active"><i class="fa-solid fa-heart"></i> Favourite</a></li>
                </ul>

                <div class="sidebar-section-title">ORDERS & SUPPORT</div>
                <ul class="sidebar-menu">
                    <li><a href="past-orders.php"><i class="fa-solid fa-box"></i> Past Orders</a></li>
					<li><a href="faq.php"><i class="fa-solid fa-circle-question"></i> FAQ</a></li>
                    <li><a href="logout.php" class="logout-link">
                                                  <i class="fa-solid fa-right-from-bracket"></i> Logout
                                      </a></li>
                    
                </ul>
            </aside>

            <section class="profile-content-card">
			<?php if(isset($cart_message)): ?>

<div class="cart-toast show">

    <div class="cart-toast-icon">
        <i class="bi bi-check-lg"></i>
    </div>

    <div class="cart-toast-content">
        <strong>Added to Cart</strong>
        <p>Product added successfully.</p>
    </div>

    <div class="cart-toast-actions">
        <a href="products.php" class="toast-link">
            Continue Shopping
        </a>

        <a href="cart.php" class="toast-link primary">
            View Cart
        </a>
    </div>

</div>

<?php endif; ?>
                <nav class="breadcrumb">
                    <a href="profile.php">Profile</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Favourites</span>
                </nav>

                <div class="products-title" style="margin-bottom: 30px;">
                    <h1>Your Favourite Plants</h1>
                </div>

                <div class="products-grid">
                    <?php if (!empty($fav_plants)): ?>
                        <?php foreach ($fav_plants as $plant): ?>
                            <article class="product-card" data-plant-id="<?php echo (int)$plant['plant_id']; ?>">

                                <button type="button" class="fav-btn remove-fav-btn active" title="Remove from favourites">
                                    <i class="bi bi-heart-fill" style="color:#d11a2a;"></i>
                                </button>

                                <a class="product-media" href="product-details.php?id=<?php echo (int)$plant['plant_id']; ?>">
                                    <img src="images/plants/<?php echo htmlspecialchars($plant['image']); ?>"
                                         alt="<?php echo htmlspecialchars($plant['plant_name']); ?>">
                                </a>

                                <div class="product-info">
                                    <h3 class="product-name">
                                        <?php echo htmlspecialchars($plant['plant_name']); ?>
                                    </h3>

                                    <div class="product-price">
                                        <?php echo number_format($plant['price'], 0); ?>
                                        <span class="currency">
                                            <img src="images/icons/sar.png" class="sar-icon" alt="SAR">
                                        </span>
                                    </div>

                                     <form method="POST" class="cart-form">

                                    <input type="hidden"
                                     name="plant_id"
                                     value="<?php echo (int)$plant['plant_id']; ?>">

                                   <button type="submit"
                                    name="add_to_cart"
                                    class="btn-cart">

                                    <span class="btn-wrapper">
                                      <span class="btn-text">Add to Cart</span>
                                         <span class="btn-icon"> 
                                          <i class="bi bi-cart2"></i>
                                          </span>
                                    </span>

                                   </button>

                                    </form> 
                                </div>

                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="font-size:18px; color:#777;">
                            No favourite plants yet.
                            <a href="products.php" style="color:#2f5e4a;">Browse plants</a>
                        </p>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>
</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <a class="footer-logo" href="index.php">🌱 Bloom House</a>
            <p>Bringing nature into your home with beautiful indoor plants.</p>
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
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; 2026 Bloom House. All rights reserved.</p>
    </div>
</footer>





</body>
</html>

