<?php
// ===== Session Start =====
session_start();
 
// ===== Database Connection =====
require_once 'db_connect.php';
 
//  ===== cookie ===== 
$last_visit_msg = "";
//  check if cookie here before massage
if (isset($_COOKIE['last_visit'])) {
    $last_visit_msg = "Welcome back! Your last visit was on " . $_COOKIE['last_visit'];
}
 
// update cookie in the date and time (ends in 30 days)
setcookie('last_visit', date("Y-m-d H:i:s"), time() + (86400 * 30), "/");
 
 
// ===== Add to Cart Handler (AJAX) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plant_id']) && isset($_POST['action']) && $_POST['action'] === 'add_cart') {
    header('Content-Type: application/json');
 
    $plant_id = (int) $_POST['plant_id'];
 
    $check = $conn->prepare("SELECT stock_quantity FROM plants WHERE plant_id = ?");
    $check->bind_param('i', $plant_id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
 
    if ($row && $row['stock_quantity'] > 0) {
        if (!isset($_SESSION['cart']))
            $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$plant_id])) {
            $_SESSION['cart'][$plant_id]++;
        } else {
            $_SESSION['cart'][$plant_id] = 1;
        }
        echo json_encode(['status' => 'success', 'message' => 'Plant added to cart successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Sorry, this product is out of stock.']);
    }
    exit();
}
 
// ===== Fetch Featured Plants (latest 8) =====
$stmt = $conn->prepare("SELECT * FROM plants WHERE availability_status = 'Available' ORDER BY plant_id DESC LIMIT 8");
$stmt->execute();
$featured_plants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
 
// ===== Fetch Indoor Plants for slider =====
$stmt2 = $conn->prepare("SELECT * FROM plants WHERE category = 'Indoor' AND availability_status = 'Available' LIMIT 6");
$stmt2->execute();
$indoor_plants = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
 
// ===== Fetch Flower Plants =====
$stmt3 = $conn->prepare("SELECT * FROM plants WHERE category = 'Flower' AND availability_status = 'Available' LIMIT 6");
$stmt3->execute();
$flower_plants = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
?>
 
<!DOCTYPE html>
<html lang="en">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bloom House - Beautiful indoor plants for your home.">
    <title>Bloom House | Home</title>
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/home-search.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .product-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .product-link:hover .product-media img {
            transform: scale(1.04);
            transition: transform 0.3s ease;
        }
        .product-link:hover h3 {
            color: #2f5e4a;
        }
    </style>
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
                <a href="index.php" class="nav-link active">Home</a>
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
 
    <?php if (!empty($last_visit_msg)): ?>
        <div class="cookie-banner"
            style="background: #e8f5ee; color: #2f5e4a; text-align: center; padding: 12px; font-size: 0.9rem; border-bottom: 1px solid #d0e5d9; font-weight: 500;">
            <i class="fas fa-history" style="margin-right: 8px;"></i> <?= htmlspecialchars($last_visit_msg) ?>
        </div>
    <?php endif; ?>
 
    <div id="cartToast" class="cart-toast">
        <div class="cart-toast-icon"><i class="bi bi-check2"></i></div>
        <div class="cart-toast-content">
            <strong id="cartToastTitle">Added to Cart</strong>
            <p id="cartToastMessage">Plant added to cart successfully.</p>
        </div>
        <div class="cart-toast-actions">
            <button type="button" id="continueShoppingBtn" class="toast-link">Continue Shopping</button>
            <a href="cart.php" id="toastPrimaryLink" class="toast-link primary">View Cart</a>
        </div>
    </div>
 
    <main>
 
        <section class="hero">
            <div class="container hero-container">
                <div class="hero-text">
                    <h1 class="hero-title">Bring Nature<br>Into Your Home 🌿</h1>
                    <p class="hero-description">Discover our beautiful collection of indoor plants, carefully selected
                        to brighten your space and purify your air.</p>
                    <div class="hero-buttons">
                        <a href="products.php" class="btn btn-primary">Shop Now</a>
                        <a href="about.php" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="images/plants/monstera.jpeg" alt="Beautiful indoor plant">
                </div>
            </div>
        </section>
 
        <section class="featured-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Featured Plants</h2>
                    <p class="section-subtitle">Hand-picked plants to brighten your space</p>
                </div>
                <div class="slider-container">
                    <div class="product-slider" id="featuredSlider">
                        <?php if (!empty($featured_plants)): ?>
                            <?php foreach ($featured_plants as $plant): ?>
                                <div class="product-slide">
                                    <article class="product-card">
                                        <?php if ($plant['stock_quantity'] <= 3): ?>
                                            <span class="product-badge">Low Stock</span>
                                        <?php endif; ?>
                                        <a href="product-details.php?id=<?= $plant['plant_id'] ?>" class="product-link">
                                            <div class="product-media">
                                                <img src="images/plants/<?= htmlspecialchars($plant['image']) ?>"
                                                    alt="<?= htmlspecialchars($plant['plant_name']) ?>">
                                            </div>
                                            <h3><?= htmlspecialchars($plant['plant_name']) ?></h3>
                                        </a>
                                        <div class="price">
                                            <?= number_format($plant['price'], 0) ?>
                                            <img src="images/icons/sar.png" class="sar-icon" alt="SAR">
                                        </div>
                                        <button type="button" class="btn-add ajax-cart-btn"
                                            data-plant-id="<?= $plant['plant_id'] ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="padding:20px; color:#5b6f65;">No plants available at the moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
 
        <?php if (!empty($indoor_plants)): ?>
            <section class="featured-section" style="background:#f8fdfa; padding: 40px 0;">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Indoor Plants</h2>
                        <p class="section-subtitle">Perfect for every room in your home</p>
                    </div>
                    <div class="slider-container">
                        <div class="product-slider" id="indoorSlider">
                            <?php foreach ($indoor_plants as $plant): ?>
                                <div class="product-slide">
                                    <article class="product-card">
                                        <a href="product-details.php?id=<?= $plant['plant_id'] ?>" class="product-link">
                                            <div class="product-media">
                                                <img src="images/plants/<?= htmlspecialchars($plant['image']) ?>"
                                                    alt="<?= htmlspecialchars($plant['plant_name']) ?>">
                                            </div>
                                            <h3><?= htmlspecialchars($plant['plant_name']) ?></h3>
                                        </a>
                                        <div class="price">
                                            <?= number_format($plant['price'], 0) ?>
                                            <img src="images/icons/sar.png" class="sar-icon" alt="SAR">
                                        </div>
                                        <button type="button" class="btn-add ajax-cart-btn"
                                            data-plant-id="<?= $plant['plant_id'] ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
 
        <?php if (!empty($flower_plants)): ?>
            <section class="featured-section" style="padding: 40px 0;">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Flowering Plants</h2>
                        <p class="section-subtitle">Add color and fragrance to your space</p>
                    </div>
                    <div class="slider-container">
                        <div class="product-slider" id="flowerSlider">
                            <?php foreach ($flower_plants as $plant): ?>
                                <div class="product-slide">
                                    <article class="product-card">
                                        <a href="product-details.php?id=<?= $plant['plant_id'] ?>" class="product-link">
                                            <div class="product-media">
                                                <img src="images/plants/<?= htmlspecialchars($plant['image']) ?>"
                                                    alt="<?= htmlspecialchars($plant['plant_name']) ?>">
                                            </div>
                                            <h3><?= htmlspecialchars($plant['plant_name']) ?></h3>
                                        </a>
                                        <div class="price">
                                            <?= number_format($plant['price'], 0) ?>
                                            <img src="images/icons/sar.png" class="sar-icon" alt="SAR">
                                        </div>
                                        <button type="button" class="btn-add ajax-cart-btn"
                                            data-plant-id="<?= $plant['plant_id'] ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
 
        <section class="why-us">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Why Choose Us</h2>
                </div>
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-leaf"></i></div>
                        <h3>Fresh Plants</h3>
                        <p>All plants are carefully selected and delivered fresh to your door.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-truck"></i></div>
                        <h3>Fast Delivery</h3>
                        <p>We deliver across Saudi Arabia within 2–5 business days.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h3>Quality Guarantee</h3>
                        <p>Not satisfied? We'll replace or refund your order.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-headset"></i></div>
                        <h3>Expert Support</h3>
                        <p>Our plant experts are here to help you care for your plants.</p>
                    </div>
                </div>
            </div>
        </section>
 
    </main>
 
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
 
    <style>
        /* ===== Cart Toast Styling ===== */
        .cart-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.13);
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 18px 22px;
            z-index: 9999;
            min-width: 300px;
            max-width: 370px;
            transform: translateY(120px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(.4, 0, .2, 1);
            pointer-events: none;
        }
 
        .cart-toast.show {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
 
        .cart-toast.error .cart-toast-icon {
            background: #ffeaea;
            color: #c0392b;
        }
 
        .cart-toast-icon {
            width: 38px;
            height: 38px;
            background: #e8f5ee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #2f5e4a;
            flex-shrink: 0;
        }
 
        .cart-toast-content strong {
            display: block;
            color: #1a3328;
            font-size: 0.95rem;
            margin-bottom: 2px;
        }
 
        .cart-toast-content p {
            color: #5b6f65;
            font-size: 0.83rem;
            margin: 0;
        }
 
        .cart-toast-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
 
        .toast-link {
            font-size: 0.83rem;
            font-weight: 600;
            color: #2f5e4a;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            font-family: inherit;
        }
 
        .toast-link.primary {
            background: #2f5e4a;
            color: #fff;
            padding: 5px 14px;
            border-radius: 20px;
        }
    </style>
 
    <script>
        // ===== AJAX Add to Cart =====
        const toast = document.getElementById('cartToast');
        const toastTitle = document.getElementById('cartToastTitle');
        const toastMsg = document.getElementById('cartToastMessage');
        const continueBtn = document.getElementById('continueShoppingBtn');
        let toastTimer;
 
        function showToast(success, message) {
            toastTitle.textContent = success ? 'Added to Cart' : 'Error';
            toastMsg.textContent = message;
            toast.classList.toggle('error', !success);
            toast.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('show'), 4000);
        }
 
        continueBtn.addEventListener('click', () => toast.classList.remove('show'));
 
        document.querySelectorAll('.ajax-cart-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const plantId = this.dataset.plantId;
                const formData = new FormData();
                formData.append('action', 'add_cart');
                formData.append('plant_id', plantId);
 
                fetch('index.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => showToast(data.status === 'success', data.message))
                    .catch(() => showToast(false, 'Something went wrong. Please try again.'));
            });
        });
    </script>
 
</body>
 
</html>