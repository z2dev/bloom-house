<?php
// Start session to access logged-in user data and cart
session_start();
// Connect to database
include("db_connect.php");

// Security check: user must log in before accessing checkout.
if (!isset($_SESSION['user_id'])) {
    header("Location: login-required.php?redirect=checkout.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Recheck FIRST coupon eligibility in checkout to prevent using it after a previous order.
$isFirstOrder = false;
$orderCheckSql = "SELECT COUNT(*) AS order_count FROM orders WHERE user_id = $user_id";
$orderCheckResult = mysqli_query($conn, $orderCheckSql);
if ($orderCheckResult) {
    $orderCheckRow = mysqli_fetch_assoc($orderCheckResult);
    $isFirstOrder = ((int)$orderCheckRow['order_count'] === 0);
}

if (!empty($_SESSION['coupon_code'])) {
    if ($_SESSION['coupon_code'] === 'FIRST' && !$isFirstOrder) {
        unset($_SESSION['coupon_code']);
        $_SESSION['discount_rate'] = 0;
    } elseif ($_SESSION['coupon_code'] === 'NADA15') {
        $_SESSION['discount_rate'] = 0.15;
    } elseif ($_SESSION['coupon_code'] === 'FIRST') {
        $_SESSION['discount_rate'] = 0.15;
    } else {
        unset($_SESSION['coupon_code']);
        $_SESSION['discount_rate'] = 0;
    }
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$cartItems = array();
$itemsCount = 0;
$subtotal = 0;
$shipping = 15;
$freeShippingThreshold = 150;
$pickupLocationName = "Bloom House Store";
$pickupLocationText = "Bloom House Store - Al Qatif, Eastern Region, KSA";
$pickupMapLink = "https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic";
$discount = 0;


// Automatically load customer information from profile/session.
$customerName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? '';
$customerPhone = $_SESSION['phone'] ?? $_SESSION['phone_number'] ?? $_SESSION['mobile'] ?? '';
$customerEmail = $_SESSION['email'] ?? '';

// Fill missing customer information from database tables
function fillCustomerFromRow($row, &$customerName, &$customerPhone, &$customerEmail) {
    if ($customerName === '') {
        if (!empty($row['full_name'])) {
            $customerName = $row['full_name'];
        } elseif (!empty($row['name'])) {
            $customerName = $row['name'];
        } elseif (!empty($row['username'])) {
            $customerName = $row['username'];
        } elseif (!empty($row['first_name']) || !empty($row['last_name'])) {
            $customerName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        }
    }

    if ($customerPhone === '') {
        foreach (['phone', 'phone_number', 'mobile', 'contact_number'] as $phoneColumn) {
            if (!empty($row[$phoneColumn])) {
                $customerPhone = $row[$phoneColumn];
                break;
            }
        }
    }

    if ($customerEmail === '' && !empty($row['email'])) {
        $customerEmail = $row['email'];
    }
}

// Search for user information inside possible account tables.
$possibleAccountTables = [
    'user' => ['user_id', 'id'],
    'users' => ['user_id', 'id'],
    'customers' => ['customer_id', 'user_id', 'id']
];

foreach ($possibleAccountTables as $tableName => $idColumns) {
    if ($customerName !== '' && $customerPhone !== '' && $customerEmail !== '') {
        break;
    }

    foreach ($idColumns as $idColumn) {
        $accountSql = "SELECT * FROM `$tableName` WHERE `$idColumn` = $user_id LIMIT 1";
        $accountResult = @mysqli_query($conn, $accountSql);
        if ($accountResult && mysqli_num_rows($accountResult) > 0) {
            $accountRow = mysqli_fetch_assoc($accountResult);
            fillCustomerFromRow($accountRow, $customerName, $customerPhone, $customerEmail);
            break 2;
        }
    }
}

$customerName = $customerName !== '' ? $customerName : 'Logged-in Customer';
$customerPhone = $customerPhone !== '' ? $customerPhone : 'Not added';

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

                if (isset($row['stock_quantity']) && $qty > (int)$row['stock_quantity']) {
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
}

$discountRate = isset($_SESSION['discount_rate']) ? (float)$_SESSION['discount_rate'] : 0;
$discount = $subtotal * $discountRate;
$amountAfterDiscount = $subtotal - $discount;

if ($amountAfterDiscount >= $freeShippingThreshold || empty($cartItems)) {
    $shipping = 0;
}

$total = $amountAfterDiscount + $shipping;
$installment = ($total > 0) ? ($total / 4) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($cartItems)) {
        header("Location: cart.php");
        exit();
    }

    $full_name = mysqli_real_escape_string($conn, $customerName);
    $phone = mysqli_real_escape_string($conn, $customerPhone);
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $postal = mysqli_real_escape_string($conn, $_POST['postal'] ?? '');

    // Browser Geolocation API values. No Google API key is required.
    $location_lat = trim($_POST['location_lat'] ?? '');
    $location_lng = trim($_POST['location_lng'] ?? '');
    $location_map_link = trim($_POST['location_map_link'] ?? '');

    $delivery_method = $_POST['delivery_method'] ?? 'delivery';
    $allowed_delivery_methods = ['delivery', 'pickup'];
    if (!in_array($delivery_method, $allowed_delivery_methods)) {
        $delivery_method = 'delivery';
    }

    if ($delivery_method == 'pickup') {
        $shipping = 0;
        $address = mysqli_real_escape_string($conn, 'PICKUP: ' . $pickupLocationText . ' | ' . $pickupMapLink);
        $city = mysqli_real_escape_string($conn, 'Store Pickup');
    } else {
        $shipping = ($amountAfterDiscount >= $freeShippingThreshold) ? 0 : 15;

        // If the customer uses the location button, save the readable address text from the map location.
        // The coordinates are kept only as extra delivery details, not as a Google Maps link.
        if ($location_lat !== '' && $location_lng !== '') {
            $readableAddress = trim($_POST['address'] ?? '');
            $readableCity = trim($_POST['city'] ?? '');

            if ($readableAddress === '' || $readableAddress === 'Current delivery location selected') {
                $readableAddress = 'Current delivery location from device';
            }
            if ($readableCity === '' || $readableCity === 'Selected from map') {
                $readableCity = 'Detected from location';
            }

            $address = mysqli_real_escape_string($conn, $readableAddress);
            $city = mysqli_real_escape_string($conn, $readableCity);
        }
    }

    $total = $amountAfterDiscount + $shipping;
    $installment = ($total > 0) ? ($total / 4) : 0;

    $payment_method = $_POST['payment_method'] ?? 'Credit Card';
    $allowed_methods = ['Credit Card', 'Cash on Delivery', 'Apple Pay', 'Tamara'];
    if (!in_array($payment_method, $allowed_methods)) {
        $payment_method = 'Credit Card';
    }
    $payment_method = mysqli_real_escape_string($conn, $payment_method);

    $order_date = date("Y-m-d H:i:s");
    $order_status = "pending";

    $sql = "INSERT INTO orders (user_id, order_date, order_status, shipping_address, payment_method, city, phone, discount)
            VALUES ('$user_id', '$order_date', '$order_status', '$address', '$payment_method', '$city', '$phone', '$discount')";

    if ($conn->query($sql) === TRUE) {
        $order_id = $conn->insert_id;

        foreach ($cartItems as $item) {
            $plant_id = (int)$item['plant_id'];
            $quantity = (int)$item['quantity'];
            $unit_price = (float)$item['price'];

            $sql_item = "INSERT INTO orders_items (order_id, plant_id, quantity, unit_price)
                         VALUES ('$order_id', '$plant_id', '$quantity', '$unit_price')";
            $conn->query($sql_item);

            $sql_stock = "UPDATE plants SET stock_quantity = stock_quantity - $quantity WHERE plant_id = $plant_id";
            $conn->query($sql_stock);
        }

        // Keep the last order details for the confirmation page
        $_SESSION['last_order'] = array(
            'order_id' => $order_id,
            'full_name' => $full_name,
            'phone' => $phone,
            'delivery_method' => $delivery_method,
            'payment_method' => $payment_method,
            'subtotal' => $subtotal,
            'coupon_code' => $_SESSION['coupon_code'] ?? '',
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => $total
        );

        $_SESSION['cart'] = array();
        unset($_SESSION['coupon_code']);
        $_SESSION['discount_rate'] = 0;
        unset($_SESSION['coupon_message']);
        header("Location: order-confirmation.php?order_id=" . $order_id);
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Bloom House | Checkout</title>

<link rel="stylesheet" href="css/styles.css" />
<link rel="stylesheet" href="css/layout.css" />
<link rel="stylesheet" href="css/cart-checkout.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
<link rel="stylesheet"
href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">


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
<a href="cart.php">Cart</a>
<i class="fas fa-chevron-right"></i>
<span>Checkout</span>
</nav>
<h1>Checkout</h1>
<p class="subtext">Choose delivery or pickup, add shipping details, and complete your order.</p>
</div>
</section>

<section class="page-content">
<div class="container checkout-layout">

<form method="POST" class="checkout-layout" style="width:100%; display:contents;">

<div class="checkout-main">

<div class="card">
<h2 class="card-title">Delivery Method</h2>

<div class="delivery-methods">
<label class="delivery-option">
<input type="radio" name="delivery_method" value="delivery" checked>
<span><i class="bi bi-truck"></i> Delivery</span>
</label>

<label class="delivery-option">
<input type="radio" name="delivery_method" value="pickup">
<span><i class="bi bi-shop"></i> Store Pickup</span>
</label>
</div>

<div id="pickup-location-box" class="pickup-location-box">
<strong>Pickup Location</strong><br>
<?php echo htmlspecialchars($pickupLocationText); ?>
<a class="map-preview" href="<?php echo htmlspecialchars($pickupMapLink); ?>" target="_blank">
<i class="bi bi-geo-alt-fill"></i><br>
Open location in Google Maps
</a>
<p class="muted" style="margin-top:10px;">If you choose pickup, shipping will be free.</p>
</div>
</div>

<div class="card" id="shipping-address-card">
<h2 class="card-title" id="shipping-card-title">Shipping Address</h2>

<div class="payment-form">
<div class="account-info-box">
<div class="account-info-item">
<span>Customer Name</span>
<strong><?php echo htmlspecialchars($customerName); ?></strong>
</div>
<div class="account-info-item">
<span>Phone Number</span>
<strong><?php echo htmlspecialchars($customerPhone); ?></strong>
</div>
<?php if (!empty($customerEmail)) { ?>
<div class="account-info-item">
<span>Email</span>
<strong><?php echo htmlspecialchars($customerEmail); ?></strong>
</div>
<?php } ?>
</div>
<input type="hidden" name="full_name" value="<?php echo htmlspecialchars($customerName); ?>">
<input type="hidden" name="phone" value="<?php echo htmlspecialchars($customerPhone); ?>">

<div class="delivery-location-api shipping-field">
<strong class="delivery-location-title"><i class="bi bi-geo-alt-fill"></i> Delivery Location</strong>
<p class="location-note">Choose your current location using the browser Geolocation API, or enter your address manually.</p>

<div class="location-choice-actions">
<button type="button" class="location-api-btn" id="use-current-location">
<i class="bi bi-crosshair"></i> Use My Current Location
</button>
<button type="button" class="location-manual-btn" id="enter-manual-address">
Enter Address Manually
</button>
</div>

<div class="selected-location-card" id="selected-location-card">
<div class="location-success-layout">
<div class="location-success-icon"><i class="bi bi-check"></i></div>
<div style="width:100%;">
<strong class="location-success-title">Location detected successfully!</strong>
<div class="location-small-label">Address added automatically:</div>
<div class="location-address-line">
<i class="bi bi-geo-alt-fill"></i>
<span id="selected-location-main">Your current delivery area</span>
</div>
<div class="location-address-details" id="selected-location-details">Your address will be filled automatically from your current location.</div>
<div class="location-card-divider"></div>
<a href="#" id="selected-location-link" target="_blank">
<i class="bi bi-map"></i> View on Google Maps
</a>
</div>
</div>
</div>

<p class="location-note" id="location-status">You can also type your delivery address manually below.</p>
</div>

<input type="hidden" id="location_lat" name="location_lat">
<input type="hidden" id="location_lng" name="location_lng">
<input type="hidden" id="location_map_link" name="location_map_link">

<div id="manual-address-fields" class="manual-address-panel">
<div class="form-group shipping-field">
<label for="address">Address</label>
<input type="text" id="address" name="address" placeholder="Street name and building number" required>
</div>

<div class="form-row-2 shipping-field">
<div class="form-group">
<label for="city">City</label>
<input type="text" id="city" name="city" placeholder="City" required>
</div>

<div class="form-group">
<label for="postal">Postal Code</label>
<input type="text" id="postal" name="postal" placeholder="Postal Code">
</div>
</div>
</div>
</div>
</div>

<div class="card card-payment">
<h2 class="card-title">Payment Information</h2>

<div class="payment-methods">
<label class="payment-option">
<input type="radio" name="payment_method" value="Credit Card" checked>
<span><i class="bi bi-credit-card"></i> Credit Card</span>
</label>

<label class="payment-option">
<input type="radio" name="payment_method" value="Cash on Delivery">
<span><i class="bi bi-cash-coin"></i> Cash on Delivery</span>
</label>

<label class="payment-option">
<input type="radio" name="payment_method" value="Apple Pay">
<span><i class="bi bi-apple"></i> Apple Pay</span>
</label>

<label class="payment-option">
<input type="radio" name="payment_method" value="Tamara">
<span><i class="bi bi-calendar-check"></i> Tamara</span>
</label>

</div>

<div class="payment-extra cod-note installment-box">
<strong>Cash on Delivery selected.</strong><br>
You will pay the full amount when your order is delivered.
</div>

<div class="payment-extra apple-note installment-box">
<strong>Apple Pay selected.</strong><br>
You can continue and complete the payment using Apple Pay.
</div>

<div class="payment-extra tamara-note installment-box">
<strong>Tamara selected.</strong><br>
Pay in 4 payments of <strong><span id="tamara-amount"><?php echo number_format($installment, 2); ?></span> SAR</strong>.
</div>


<div class="payment-form credit-card-fields">
<div class="form-group">
<label for="card-number">Card Number</label>
<input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456">
</div>

<div class="form-row-2">
<div class="form-group">
<label for="expiry-date">Expiry Date</label>
<input type="text" id="expiry-date" name="expiry_date" placeholder="MM/YY">
</div>

<div class="form-group">
<label for="cvv">CVV</label>
<input type="password" id="cvv" name="cvv" placeholder="***">
</div>
</div>

<div class="form-group">
<label for="card-name">Name on Card</label>
<input type="text" id="card-name" name="card_name" placeholder="Your name">
</div>
</div>

<div class="payment-form">

<button type="submit" class="btn primary">
Confirm Order
</button>
</div>
</div>

</div>

<aside class="checkout-side">
<div class="card sticky">
<h2 class="card-title">Order Summary</h2>

<div class="summary-list">
<?php if (empty($cartItems)) { ?>
<div class="summary-item">
<span>Your cart is empty.</span>
<span class="muted">0</span>
</div>
<?php } else { ?>
<?php foreach ($cartItems as $item) { ?>
<div class="summary-item">
<span><?php echo htmlspecialchars($item['plant_name']); ?> <span class="muted">x<?php echo (int)$item['quantity']; ?></span></span>
<span class="money">
<?php echo number_format((float)$item['line_subtotal'], 2); ?>
<span class="currency">
<img src="images/icons/sar.png" alt="SAR" class="sar-icon">
</span>
</span>
</div>
<?php } ?>
<?php } ?>
</div>

<div class="divider"></div>

<div class="summary-row">
<span class="muted">Subtotal</span>
<span class="money">
<?php echo number_format($subtotal, 2); ?>
<span class="currency">
<img src="images/icons/sar.png" alt="SAR" class="sar-icon">
</span>
</span>
</div>

<div class="summary-row" id="shipping-row">
<span class="muted">Shipping</span>
<span class="money" id="shipping-display">
<?php if ($shipping == 0) { ?>
<span class="shipping-free">FREE</span>
<?php } else { ?>
<span><?php echo number_format($shipping, 2); ?></span>
<span class="currency">
<img src="images/icons/sar.png" alt="SAR" class="sar-icon">
</span>
<?php } ?>
</span>
</div>

<div id="free-shipping-message" class="free-shipping-message"></div>

<div class="summary-row">
<span class="muted">Discount</span>
<span class="money">
<?php echo number_format($discount, 2); ?>
<span class="currency">
<img src="images/icons/sar.png" alt="SAR" class="sar-icon">
</span>
</span>
</div>

<div class="divider"></div>

<div class="summary-row total">
<span>Total</span>
<span class="money">
<span id="total-display"><?php echo number_format($total, 2); ?></span>
<span class="currency">
<img src="images/icons/sar.png" alt="SAR" class="sar-icon">
</span>
</span>
</div>

<p class="checkout-note">
<i class="bi bi-shield-check"></i>
Secure checkout and protected payment information.
</p>
</div>
</aside>

</form>

</div>
</section>
</main>

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
<script>
window.BLOOM_CHECKOUT = {
    subtotalAmount: <?php echo json_encode((float)$subtotal); ?>,
    discountAmount: <?php echo json_encode((float)$discount); ?>,
    freeShippingThreshold: <?php echo json_encode((float)$freeShippingThreshold); ?>,
    normalShipping: 15
};
</script>

<script src="js/cart-checkout.js?v=20"></script>

</body>
</html>
