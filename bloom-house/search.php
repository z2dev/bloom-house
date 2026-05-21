<?php
// ===== Session Start =====
session_start();
 
// ===== Database Connection =====
require_once 'db_connect.php';
 
// ===== Add to Cart Handler (AJAX) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['plant_id'])
    && isset($_POST['action'])
    && $_POST['action'] === 'add_cart') {
 
    header('Content-Type: application/json');
 
    $plant_id = (int) $_POST['plant_id'];
 
    $check = $conn->prepare("SELECT stock_quantity FROM plants WHERE plant_id = ?");
    $check->bind_param('i', $plant_id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
 
    if ($row && $row['stock_quantity'] > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$plant_id] = ($_SESSION['cart'][$plant_id] ?? 0) + 1;
 
        echo json_encode([
            'status' => 'success',
            'message' => 'Plant added to cart successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Sorry, this product is out of stock.'
        ]);
    }
    exit();
}
 
// ===== Input Validation & Sanitization =====
$search_query = '';
$results = [];
$total_results = 0;
$error_message = '';
 
if (isset($_GET['q'])) {
    $search_query = trim(strip_tags($_GET['q']));
    $search_query = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');
 
    if (strlen($search_query) > 100) {
        $error_message = 'Search query is too long. Please keep it under 100 characters.';
    } elseif (!empty($search_query)) {
        
        //  ===== cookie ===== 
        //  save last word for 7 days
        setcookie('last_search', $search_query, time() + (86400 * 7), "/");
 
        $search_param = '%' . $search_query . '%';
        $stmt = $conn->prepare("
            SELECT * FROM plants
            WHERE availability_status = 'Available'
            AND (
                plant_name LIKE ?
                OR description LIKE ?
                OR category LIKE ?
                OR light_requirement LIKE ?
            )
            ORDER BY plant_name ASC
        ");
        $stmt->bind_param('ssss', $search_param, $search_param, $search_param, $search_param);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $total_results = count($results);
    }
}
 
//  bring the cookie value if there is 
$last_search_cookie = isset($_COOKIE['last_search']) ? htmlspecialchars($_COOKIE['last_search'], ENT_QUOTES, 'UTF-8') : '';
 
// ===== Fetch Categories for filter =====
$cat_stmt = $conn->prepare("SELECT DISTINCT category FROM plants WHERE availability_status = 'Available' ORDER BY category");
$cat_stmt->execute();
$categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Search for indoor plants at Bloom House.">
    <title>Bloom House | Search</title>
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
                <a href="search.php" class="user-icon active"><i class="fas fa-search"></i></a>
                <a href="cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="user-icon"><i class="fas fa-user"></i></a>
                <?php else: ?>
                    <a href="login.php" class="user-icon"><i class="fas fa-user"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </header>
 
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
        <section class="search-section">
            <div class="container">
                <h1 class="page-title">Search Plants</h1>
 
                <form class="search-main-form" action="search.php" method="get">
                    <div class="search-wrapper">
                        <input
                            type="text"
                            name="q"
                            placeholder="What are you looking for?"
                            value="<?= htmlspecialchars($search_query) ?>"
                            maxlength="100"
                            autocomplete="off"
                        >
                        <button type="submit"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
 
                <?php if (!empty($categories)): ?>
                <div class="category-filters" style="text-align:center; margin-bottom: 2rem; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                    <a href="search.php" class="filter-btn <?= empty($search_query) ? 'active' : '' ?>">All</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="search.php?q=<?= urlencode($cat['category']) ?>"
                           class="filter-btn <?= $search_query === $cat['category'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['category']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
 
                <?php if (!empty($error_message)): ?>
                    <div class="search-error" style="text-align:center; color:#c0392b; margin-bottom:1.5rem; font-weight:500;">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>
 
                <?php if (!empty($search_query) && empty($error_message)): ?>
                    <div class="search-results">
                        <div class="section-header">
                            <h2 class="section-title">
                                <?php if ($total_results > 0): ?>
                                    <?= $total_results ?> Result<?= $total_results > 1 ? 's' : '' ?> Found
                                <?php else: ?>
                                    No Results Found
                                <?php endif; ?>
                            </h2>
                            <p class="section-subtitle">Results for "<strong><?= htmlspecialchars($search_query) ?></strong>"</p>
                        </div>
 
                        <?php if ($total_results > 0): ?>
                            <div class="slider-container">
                                <div class="product-slider" id="searchSlider">
                                    <?php foreach ($results as $plant): ?>
                                        <div class="product-slide">
                                            <article class="product-card">
                                                <?php if ($plant['stock_quantity'] <= 3): ?>
                                                    <span class="product-badge">Low Stock</span>
                                                <?php else: ?>
                                                    <span class="product-badge"><?= htmlspecialchars($plant['category']) ?></span>
                                                <?php endif; ?>
 
                                                <a href="product-details.php?id=<?= $plant['plant_id'] ?>" class="product-link">
                                                    <div class="product-media">
                                                        <img src="images/plants/<?= htmlspecialchars($plant['image']) ?>"
                                                             alt="<?= htmlspecialchars($plant['plant_name']) ?>"
                                                             onerror="this.src='images/plants/monstera.jpeg'">
                                                    </div>
                                                    <h3><?= htmlspecialchars($plant['plant_name']) ?></h3>
                                                </a>
                                                
                                                <p style="font-size:0.82rem; color:#5b6f65; margin-bottom:6px;">
                                                    <i class="fas fa-sun" style="color:#f0a500;"></i>
                                                    <?= htmlspecialchars($plant['light_requirement']) ?>
                                                </p>
 
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
                        <?php else: ?>
                            <div style="text-align:center; padding: 60px 20px;">
                                <div style="font-size:4rem; margin-bottom:1rem;">🌱</div>
                                <h3 style="color:#2f5e4a; margin-bottom:0.5rem;">No plants found</h3>
                                <p style="color:#5b6f65; margin-bottom:2rem;">Try searching for "Monstera", "Indoor", or "Snake Plant"</p>
                                <a href="products.php" class="btn btn-primary">Browse All Plants</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif (empty($search_query)): ?>
                    <div style="text-align:center; padding: 40px 20px;">
                        
                        <?php if (!empty($last_search_cookie)): ?>
                            <p style="color:#5b6f65; font-size:1rem; margin-bottom: 1.5rem; background: #e8f5ee; display: inline-block; padding: 10px 20px; border-radius: 30px;">
                                <i class="fas fa-history" style="color:#2f5e4a; margin-right:5px;"></i> 
                                Your last search: 
                                <a href="search.php?q=<?= urlencode($last_search_cookie) ?>" style="color:#2f5e4a; font-weight:600; text-decoration:underline;">
                                    <?= $last_search_cookie ?>
                                </a>
                            </p>
                        <?php endif; ?>
 
                        <p style="color:#5b6f65; font-size:1.1rem; margin-bottom:1.5rem;">Try searching for:</p>
                        <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                            <?php
                            $suggestions = ['Monstera', 'Snake Plant', 'Indoor', 'Flower', 'Areca Palm'];
                            foreach ($suggestions as $s):
                            ?>
                                <a href="search.php?q=<?= urlencode($s) ?>" class="filter-btn"><?= $s ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
        .cart-toast {
            position: fixed; bottom: 30px; right: 30px;
            background: #fff; border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.13);
            display: flex; align-items: flex-start; gap: 14px;
            padding: 18px 22px; z-index: 9999;
            min-width: 300px; max-width: 370px;
            transform: translateY(120px); opacity: 0;
            transition: all 0.35s cubic-bezier(.4,0,.2,1); pointer-events: none;
        }
        .cart-toast.show { transform: translateY(0); opacity: 1; pointer-events: auto; }
        .cart-toast.error .cart-toast-icon { background: #ffeaea; color: #c0392b; }
        .cart-toast-icon {
            width: 38px; height: 38px; background: #e8f5ee; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #2f5e4a; flex-shrink: 0;
        }
        .cart-toast-content strong { display: block; color: #1a3328; font-size: 0.95rem; margin-bottom: 2px; }
        .cart-toast-content p { color: #5b6f65; font-size: 0.83rem; margin: 0; }
        .cart-toast-actions { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
        .toast-link {
            font-size: 0.83rem; font-weight: 600; color: #2f5e4a;
            text-decoration: none; background: none; border: none; cursor: pointer;
            padding: 0; font-family: inherit;
        }
        .toast-link.primary { background: #2f5e4a; color: #fff; padding: 5px 14px; border-radius: 20px; }
        
        .filter-btn {
            display: inline-block; padding: 8px 20px; border-radius: 50px;
            background: #f0f5f2; color: #2f5e4a; text-decoration: none;
            font-weight: 500; font-size: 0.9rem; border: 2px solid transparent;
            transition: all 0.3s; cursor: pointer;
        }
        .filter-btn:hover, .filter-btn.active { background: #2f5e4a; color: white; border-color: #2f5e4a; }
    </style>
 
    <script>
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
 
                fetch('search.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => showToast(data.status === 'success', data.message))
                    .catch(() => showToast(false, 'Something went wrong. Please try again.'));
            });
        });
    </script>
</body>
</html>