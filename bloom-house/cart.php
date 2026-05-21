<?php
// Start the session so cart items and coupon data stay saved between pages
session_start();

// Connect this page with the database
include("db_connect.php");

// Create an empty cart session if the user does not have a cart yet
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Check if the logged-in customer has previous orders.
// The FIRST coupon is available only for the customer's first order.
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$isFirstOrder = false;

if ($user_id > 0) {
    $orderCheckSql = "SELECT COUNT(*) AS order_count FROM orders WHERE user_id = $user_id";
    $orderCheckResult = mysqli_query($conn, $orderCheckSql);

    if ($orderCheckResult) {
        $orderCheckRow = mysqli_fetch_assoc($orderCheckResult);
        $isFirstOrder = ((int)$orderCheckRow['order_count'] === 0);
    }
}

// Handle all cart actions sent from forms, such as update, remove, clear, and coupon
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $plant_id = isset($_POST['plant_id']) ? (int) $_POST['plant_id'] : 0;

    // Apply discount code and save the result in the session
    if ($action == "apply_coupon") {
        $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

        // Only one coupon can be active at a time because the selected code
        // is saved in one session value: $_SESSION['coupon_code'].
        if ($coupon_code === "FIRST") {
            if ($user_id <= 0) {
                unset($_SESSION['coupon_code']);
                $_SESSION['discount_rate'] = 0;
                $_SESSION['coupon_message'] = "Please login to use the FIRST discount code.";
            } elseif ($isFirstOrder) {
                $_SESSION['coupon_code'] = "FIRST";
                $_SESSION['discount_rate'] = 0.15;
                $_SESSION['coupon_message'] = "Discount code applied successfully.";
            } else {
                unset($_SESSION['coupon_code']);
                $_SESSION['discount_rate'] = 0;
                $_SESSION['coupon_message'] = "Invalid discount code.";
            }
        } elseif ($coupon_code === "NADA15") {
            $_SESSION['coupon_code'] = "NADA15";
            $_SESSION['discount_rate'] = 0.15;
            $_SESSION['coupon_message'] = "Discount code applied successfully.";
        } else {
            unset($_SESSION['coupon_code']);
            $_SESSION['discount_rate'] = 0;
            $_SESSION['coupon_message'] = "Invalid discount code.";
        }

        header("Location: cart.php");
        exit();
    }

    if ($action == "clear") {
        $_SESSION['cart'] = array();
        header("Location: cart.php");
        exit();
    }

    if ($plant_id > 0 && isset($_SESSION['cart'][$plant_id])) {
        if ($action == "increase") {
            $_SESSION['cart'][$plant_id]++;
        } elseif ($action == "decrease") {
            $_SESSION['cart'][$plant_id]--;
            if ($_SESSION['cart'][$plant_id] <= 0) {
                unset($_SESSION['cart'][$plant_id]);
            }
        } elseif ($action == "remove") {
            unset($_SESSION['cart'][$plant_id]);
        }
    }

    header("Location: cart.php");
    exit();
}

$cartItems = array();
$itemsCount = 0;
$subtotal = 0;
$shipping = 0;
$discount = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array();
    foreach ($_SESSION['cart'] as $id => $qty) {
        if ((int)$qty > 0) {
            $ids[] = (int)$id;
        }
    }

    if (!empty($ids)) {
        $idsList = implode(',', $ids);
        $sql = "SELECT plant_id, plant_name, price, image, category, stock_quantity FROM plants WHERE plant_id IN ($idsList)";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $id = (int)$row['plant_id'];
                $qty = isset($_SESSION['cart'][$id]) ? (int)$_SESSION['cart'][$id] : 0;

                if ($qty < 1) {
                    unset($_SESSION['cart'][$id]);
                    continue;
                }

                if ($qty > (int)$row['stock_quantity']) {
                    $qty = (int)$row['stock_quantity'];
                    $_SESSION['cart'][$id] = $qty;
                }

                if ($qty < 1) {
                    unset($_SESSION['cart'][$id]);
                    continue;
                }

                $lineSubtotal = (float)$row['price'] * $qty;
                $subtotal += $lineSubtotal;
                $itemsCount += $qty;

                $row['quantity'] = $qty;
                $row['line_subtotal'] = $lineSubtotal;
                $cartItems[] = $row;
            }
        }
    }
}

if (empty($cartItems)) {
    $shipping = 0;
    unset($_SESSION['coupon_code']);
    $_SESSION['discount_rate'] = 0;
}

// Do not keep FIRST discount if the customer is not eligible anymore.
if (!empty($_SESSION['coupon_code']) && $_SESSION['coupon_code'] === 'FIRST' && (!$isFirstOrder || $user_id <= 0)) {
    unset($_SESSION['coupon_code']);
    $_SESSION['discount_rate'] = 0;
}

// Clear any old FIRST message saved in the session from previous versions.
// The first-order offer message should appear only inside the FIRST offer box below.
if ($user_id <= 0 || !$isFirstOrder) {
    if (!empty($_SESSION['coupon_message']) && stripos($_SESSION['coupon_message'], 'FIRST') !== false) {
        unset($_SESSION['coupon_message']);
    }
}

$discountRate = isset($_SESSION['discount_rate']) ? (float)$_SESSION['discount_rate'] : 0;
$discount = $subtotal * $discountRate;

$total = $subtotal - $discount;

// Suggested products shown under the cart to improve user interaction
$suggestedProducts = array();
$excludeIds = array();

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $qty) {
        if ((int)$qty > 0) {
            $excludeIds[] = (int)$id;
        }
    }
}

// Get related products for the "You might also like" section.
// We do not use old saved session IDs here, so the section will not appear empty
// if products were changed in the database.
$suggestionSql = "SELECT plant_id, plant_name, price, image, category FROM plants";
if (!empty($excludeIds)) {
    $suggestionSql .= " WHERE plant_id NOT IN (" . implode(',', $excludeIds) . ")";
}
$suggestionSql .= " ORDER BY RAND() LIMIT 4";

$suggestionResult = mysqli_query($conn, $suggestionSql);
if ($suggestionResult) {
    while ($suggestion = mysqli_fetch_assoc($suggestionResult)) {
        $suggestedProducts[] = $suggestion;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bloom House | Cart</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="css/layout.css" />
    <link rel="stylesheet" href="css/cart-checkout.css?v=4" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">

</head>

<body>
    <!-- Cart page layout: shows cart items, order summary, coupon box, and product suggestions -->
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

    <main class="page">
        <section class="page-hero">
            <div class="container">
                <nav class="breadcrumb">
                    <a href="index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Cart</span>
                </nav>
                <h1>Shopping Cart</h1>
                <p class="subtext">Review your selected plants. Shipping will be selected in checkout.</p>
            </div>
        </section>

        <section class="page-content">
            <div class="container <?php echo empty($cartItems) ? '' : 'grid-2'; ?>">

                <?php if (empty($cartItems)) { ?>
                    <div class="card" style="text-align:center; padding:55px 25px;">
                        <h2 class="card-title">🪴 Your garden needs plants</h2>
                        <p class="muted" style="margin:12px 0 25px;">Let’s grow something beautiful together 🌱</p>
                        <a class="btn primary" href="products.php">Start Shopping</a>
                    </div>
                <?php } else { ?>

                    <div>
                    <div class="card">
                        <div class="card-head">
                            <h2 class="card-title">Items</h2>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="action" value="clear">
                                <button class="link-btn" type="submit" aria-label="Clear cart">Clear</button>
                            </form>
                        </div>

                        <div class="table">
                            <div class="t-row t-head cart-grid">
                                <div>Plant</div>
                                <div class="t-right">Price</div>
                                <div class="t-center">Qty</div>
                                <div class="t-right">Subtotal</div>
                                <div class="t-right">Action</div>
                            </div>

                            <?php foreach ($cartItems as $item) { ?>
                                <div class="t-row cart-row cart-grid">
                                    <div class="plant-name">
                                        <img src="images/plants/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['plant_name']); ?>" class="plant-img" />
                                        <div class="plant-info">
                                            <strong><?php echo htmlspecialchars($item['plant_name']); ?></strong>
                                            <span class="muted"><?php echo htmlspecialchars($item['category']); ?> Plant</span>
                                        </div>
                                    </div>

                                    <div class="t-right money">
                                        <span class="price-number"><?php echo number_format((float)$item['price'], 2); ?></span>
                                        <span class="currency">
                                            <img src="images/icons/sar.png" alt="SAR" class="sar-icon" />
                                        </span>
                                    </div>

                                    <div class="t-center">
                                        <div class="qty">
                                            <form method="POST" action="cart.php" style="display:inline;">
                                                <input type="hidden" name="plant_id" value="<?php echo (int)$item['plant_id']; ?>">
                                                <input type="hidden" name="action" value="decrease">
                                                <button class="qty-btn" type="submit" aria-label="Decrease quantity">−</button>
                                            </form>

                                            <input class="qty-input" type="number" min="1" value="<?php echo (int)$item['quantity']; ?>" readonly />

                                            <form method="POST" action="cart.php" style="display:inline;">
                                                <input type="hidden" name="plant_id" value="<?php echo (int)$item['plant_id']; ?>">
                                                <input type="hidden" name="action" value="increase">
                                                <button class="qty-btn" type="submit" aria-label="Increase quantity">+</button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="t-right money">
                                        <span class="line-subtotal"><?php echo number_format((float)$item['line_subtotal'], 2); ?></span>
                                        <span class="currency">
                                            <img src="images/icons/sar.png" alt="SAR" class="sar-icon" />
                                        </span>
                                    </div>

                                    <div class="t-right">
                                        <form method="POST" action="cart.php">
                                            <input type="hidden" name="plant_id" value="<?php echo (int)$item['plant_id']; ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button class="pill danger" type="submit">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <?php if (!empty($suggestedProducts)) { ?>
                        <div class="card suggestions-card">
                            <div class="card-head suggestions-head">
                                <div>
                                    <h2 class="card-title">🪴 You might also like</h2>
                                    <p class="muted suggestions-subtitle">Your plants might need friends.</p>
                                </div>
                            </div>

                            <div class="suggestions-grid">
                                <?php foreach ($suggestedProducts as $product) { ?>
                                    <a class="suggestion-card" href="product-details.php?id=<?php echo (int)$product['plant_id']; ?>">
                                        <div class="suggestion-img-wrap">
                                            <img src="images/plants/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['plant_name']); ?>" class="suggestion-img">
                                        </div>
                                        <div class="suggestion-info">
                                            <strong><?php echo htmlspecialchars($product['plant_name']); ?></strong>
                                            <span class="muted"><?php echo htmlspecialchars($product['category']); ?> Plant</span>
                                            <div class="money suggestion-price">
                                                <span><?php echo number_format((float)$product['price'], 2); ?></span>
                                                <span class="currency"><img src="images/icons/sar.png" alt="SAR" class="sar-icon" /></span>
                                            </div>
                                        </div>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    </div>

                    <aside class="card sticky">
                        <h2 class="card-title">Cart Summary</h2>

                        <div class="divider"></div>

                        <div class="summary-row">
                            <span class="muted">Items</span>
                            <span><?php echo $itemsCount; ?></span>
                        </div>

                        <div class="summary-row">
                            <span class="muted">Subtotal</span>
                            <span class="money">
                                <span><?php echo number_format($subtotal, 2); ?></span>
                                <span class="currency"><img src="images/icons/sar.png" alt="SAR" class="sar-icon" /></span>
                            </span>
                        </div>


                        <?php if ($user_id > 0 && $isFirstOrder) { ?>
                            <div class="first-discount-box" style="margin: 14px 0; padding: 12px; border: 1px solid #d7e5dc; border-radius: 14px; background: #f7fbf8;">
                                <p class="muted" style="margin: 0 0 10px; font-size: 13px;">
                                    You can use <strong>FIRST</strong> discount code for your first order.
                                </p>
                                <form method="POST" action="cart.php" style="margin:0;">
                                    <input type="hidden" name="action" value="apply_coupon">
                                    <input type="hidden" name="coupon_code" value="FIRST">
                                    <button type="submit" class="pill">Use FIRST</button>
                                </form>
                            </div>
                        <?php } ?>

                        <form method="POST" action="cart.php" style="margin: 14px 0;">
                            <input type="hidden" name="action" value="apply_coupon">
                            <div style="display: flex; gap: 8px;">
                                <input type="text" name="coupon_code" placeholder="Enter discount code" value="<?php echo isset($_SESSION['coupon_code']) ? htmlspecialchars($_SESSION['coupon_code']) : ''; ?>" style="flex: 1; padding: 10px 12px; border: 1px solid #ddd; border-radius: 12px; font-family: inherit;">
                                <button type="submit" class="pill">Apply</button>
                            </div>
                            <?php if (!empty($_SESSION['coupon_message'])) { ?>
                                <p class="muted" style="margin-top: 8px; font-size: 13px;"><?php echo htmlspecialchars($_SESSION['coupon_message']); ?></p>
                            <?php } ?>
                        </form>

                        <div class="summary-row">
                            <span class="muted">Discount</span>
                            <span class="money">
                                − <span><?php echo number_format($discount, 2); ?></span>
                                <span class="currency"><img src="images/icons/sar.png" alt="SAR" class="sar-icon" /></span>
                            </span>
                        </div>

                        <div class="divider"></div>

                        <div class="summary-row total">
                            <span>Cart Total</span>
                            <span class="money">
                                <span><?php echo number_format($total, 2); ?></span>
                                <span class="currency"><img src="images/icons/sar.png" alt="SAR" class="sar-icon" /></span>
                            </span>
                        </div>

                        <a class="btn primary" href="checkout.php">Proceed to Checkout</a>

                        <div class="mini-features">
                            <div class="mini">
                                <i class="bi bi-truck"></i>
                                <div>
                                    <strong>Fast Delivery</strong>
                                    <span class="muted">Delivered across KSA.</span>
                                </div>
                            </div>
                            <div class="mini">
                                <i class="bi bi-shield-lock"></i>
                                <div>
                                    <strong>Secure Checkout</strong>
                                    <span class="muted">Your data is protected.</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                <?php } ?>
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
<script src="js/cart-checkout.js?v=10"></script>
</body>
</html>
