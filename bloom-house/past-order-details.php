<?php
// Start session for logged-in user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
include("db_connect.php");

// Protect output from XSS attacks
function safe($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Format prices and totals
function money($value)
{
    return number_format((float) $value, 0);
}

// Redirect guest users to login page
function redirect_login_required($redirect)
{
    header("Location: login-required.php?redirect=" . urlencode($redirect));
    exit();
}

// Check if a database table exists
function table_exists($conn, $table)
{
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

// Get all columns from a table
function columns_of($conn, $table)
{
    $cols = [];
    if (!table_exists($conn, $table))
        return $cols;
    $res = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($row = $res->fetch_assoc())
            $cols[] = $row['Field'];
    }
    return $cols;
}

function has_col($cols, $name)
{
    return in_array($name, $cols, true);
}

// Add reordered items back into cart
function add_item_to_cart_table($conn, $user_id, $plant_id, $qty)
{
    if ($plant_id <= 0 || $qty <= 0)
        return;

    if (table_exists($conn, 'cart')) {
        $cols = columns_of($conn, 'cart');
        if (has_col($cols, 'user_id') && has_col($cols, 'plant_id') && has_col($cols, 'quantity')) {
            $check = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND plant_id = ? LIMIT 1");
            $check->bind_param("ii", $user_id, $plant_id);
            $check->execute();
            $found = $check->get_result()->fetch_assoc();
            $check->close();

            if ($found) {
                $update = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND plant_id = ?");
                $update->bind_param("iii", $qty, $user_id, $plant_id);
                $update->execute();
                $update->close();
            } else {
                $insert = $conn->prepare("INSERT INTO cart (user_id, plant_id, quantity) VALUES (?, ?, ?)");
                $insert->bind_param("iii", $user_id, $plant_id, $qty);
                $insert->execute();
                $insert->close();
            }
        }
    }
}

// Verify that user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    redirect_login_required('past-orders.php');
}

$user_id = (int) $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if ($order_id <= 0) {
    header("Location: past-orders.php");
    exit();
}

// Get current logged-in user information
$user_sql = "SELECT first_name, last_name, email FROM user WHERE user_id = ? LIMIT 1";
$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    redirect_login_required('past-orders.php');
}
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if (!$user_result || $user_result->num_rows === 0) {
    $_SESSION = array();
    session_unset();
    session_destroy();
    redirect_login_required('past-orders.php');
}
$current_user = $user_result->fetch_assoc();
$user_stmt->close();

$user_first = $current_user['first_name'] ?? 'User';
$user_last = $current_user['last_name'] ?? '';
$user_email = $current_user['email'] ?? '';
$user_full_name = trim($user_first . " " . $user_last);
$user_initial = strtoupper(substr($user_first, 0, 1));

// Handle reorder action and restore items into cart
if (isset($_GET['action']) && $_GET['action'] === 'reorder') {
    $items = $conn->prepare("SELECT oi.plant_id, oi.quantity FROM orders_items oi INNER JOIN orders o ON oi.order_id = o.order_id WHERE oi.order_id = ? AND o.user_id = ?");
    $items->bind_param("ii", $order_id, $user_id);
    $items->execute();
    $items_result = $items->get_result();

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    while ($item = $items_result->fetch_assoc()) {
        $plant_id = intval($item['plant_id']);
        $qty = max(1, intval($item['quantity']));
        if ($plant_id > 0) {
            $_SESSION['cart'][$plant_id] = ($_SESSION['cart'][$plant_id] ?? 0) + $qty;
            add_item_to_cart_table($conn, $user_id, $plant_id, $qty);
        }
    }
    $items->close();

    header("Location: cart.php?reorder=success");
    exit();
}

// Get selected order details from database
$order_sql = "SELECT 
                o.order_id, o.order_date, o.order_status, o.shipping_address, o.payment_method,
                o.city, o.phone, o.discount,
                u.first_name, u.last_name, u.email,
                COUNT(oi.order_item_id) AS items_count,
                COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS subtotal
              FROM orders o
              LEFT JOIN user u ON o.user_id = u.user_id
              LEFT JOIN orders_items oi ON o.order_id = oi.order_id
              WHERE o.order_id = ? AND o.user_id = ?
              GROUP BY o.order_id";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: past-orders.php");
    exit();
}

// Get ordered products and review status
$items_sql = "SELECT oi.quantity, oi.unit_price, p.plant_id, p.plant_name, p.image,
                     (SELECT review_id FROM reviews r
                      WHERE r.user_id = ? AND r.order_id = oi.order_id AND r.plant_id = oi.plant_id
                      LIMIT 1) AS review_id
              FROM orders_items oi
              LEFT JOIN plants p ON oi.plant_id = p.plant_id
              WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param("ii", $user_id, $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];
while ($row = $items_result->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt->close();

$status = strtolower($order['order_status']);
$status_class = "status-badge status-pending";
if ($status == "delivered")
    $status_class = "status-badge status-delivered";
elseif ($status == "shipped")
    $status_class = "status-badge status-shipped";
elseif ($status == "processing")
    $status_class = "status-badge status-processing";

$statusClass = "sp-processing";
if ($status == "pending")
    $statusClass = "sp-pending";
elseif ($status == "processing")
    $statusClass = "sp-processing";
elseif ($status == "shipped")
    $statusClass = "sp-shipped";
elseif ($status == "delivered")
    $statusClass = "sp-delivered";
elseif ($status == "completed")
    $statusClass = "sp-completed";
elseif ($status == "cancelled")
    $statusClass = "sp-cancelled";

$subtotal = floatval($order['subtotal']);
$discount = floatval($order['discount']);
$total = max(0, $subtotal - $discount);
$delivery_date = date("j M Y", strtotime($order['order_date'] . " +3 days"));
$is_pickup = (stripos($order['shipping_address'] ?? '', 'PICKUP') !== false || stripos($order['shipping_address'] ?? '', 'Store Pickup') !== false);

$timeline_steps = [
    "pending" => ["Order Placed"],
    "processing" => ["Order Placed", "Order Confirmed", "Preparing Order"],
    "shipped" => ["Order Placed", "Order Confirmed", "Order Shipped", "Out for Delivery"],
    "delivered" => ["Order Placed", "Order Confirmed", "Order Shipped", "Out for Delivery", "Delivered"]
];
$active_steps = $timeline_steps[$status] ?? ["Order Placed"];

// Generate printable invoice page
if (isset($_GET['invoice']) && $_GET['invoice'] === '1') {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bloom-House-Invoice-<?php echo safe($order_id); ?></title>
        <link rel="stylesheet" href="css/past-order-invoice.css">
    </head>

    <body>
        <div class="actions"><button onclick="window.print()">Download PDF</button><a
                href="past-order-details.php?order_id=<?php echo safe($order_id); ?>">Back</a></div>
        <main class="invoice-page">
            <section class="top">
                <div class="brand">
                    <h2>Bloom House</h2>
                    <p>Indoor plants store</p>
                </div>
                <div class="title">
                    <h1>INVOICE</h1>
                    <p>#<?php echo safe($order['order_id']); ?></p>
                </div>
            </section>
            <section class="row">
                <div class="box">
                    <h3>Bill To</h3>
                    <p class="bold"><?php echo safe($user_full_name ?: 'Customer'); ?></p>
                    <p><?php echo safe($user_email); ?></p>
                    <p><?php echo safe($order['phone']); ?></p>
                </div>
                <div class="box">
                    <h3>Ship To</h3>
                    <p><?php echo safe($order['shipping_address']); ?><?php echo !empty($order['city']) ? ', ' . safe($order['city']) : ''; ?>
                    </p>
                    <p>Payment: <?php echo safe($order['payment_method']); ?></p>
                    <p>Status: <?php echo safe(ucfirst($order['order_status'])); ?></p>
                </div>
            </section>
            <section class="meta">
                <div><span>Order Date</span><strong><?php echo date("j M Y", strtotime($order['order_date'])); ?></strong>
                </div>
                <div><span>Items</span><strong><?php echo safe($order['items_count']); ?></strong></div>
                <div><span>Total</span><strong><?php echo money($total); ?> SAR</strong></div>
            </section>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="center">Qty</th>
                        <th class="right">Unit Price</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item) {
                        $item_total = $item['quantity'] * $item['unit_price']; ?>
                        <tr>
                            <td><span class="bold"><?php echo safe($item['plant_name'] ?: 'Plant'); ?></span></td>
                            <td class="center"><?php echo safe($item['quantity']); ?></td>
                            <td class="right"><?php echo money($item['unit_price']); ?> SAR</td>
                            <td class="right"><?php echo money($item_total); ?> SAR</td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="3" class="right bold">Subtotal</td>
                        <td class="right"><?php echo money($subtotal); ?> SAR</td>
                    </tr>
                    <?php if ($discount > 0) { ?>
                        <tr>
                            <td colspan="3" class="right bold">Discount</td>
                            <td class="right">-<?php echo money($discount); ?> SAR</td>
                        </tr><?php } ?>
                    <tr>
                        <td colspan="3" class="right bold">Shipping</td>
                        <td class="right">Free</td>
                    </tr>
                    <tr class="total">
                        <td colspan="3" class="right">Grand Total</td>
                        <td class="right"><?php echo money($total); ?> SAR</td>
                    </tr>
                </tbody>
            </table>
            <p class="note">Thank you for shopping with Bloom House.</p>
        </main>
    </body>

    </html>
    <?php exit();
} ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Order Details</title>
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/past-order-details.css?v=50">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
</head>

<body>
    <!-- Main website header -->
    <header class="header no-print">
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
            <h1 class="account-main-title no-print">My Account</h1>
            <div class="account-layout">
                <!-- Account sidebar navigation -->
                <aside class="account-sidebar no-print">
                    <div class="user-box">
                        <div class="user-avatar"><span><?php echo safe($user_initial); ?></span></div>
                        <div class="user-meta">
                            <div class="user-name"><?php echo safe($user_full_name); ?></div>
                            <div class="user-email"><?php echo safe($user_email); ?></div>
                        </div>
                    </div>
                    <div class="sidebar-section-title">ACCOUNT</div>
                    <ul class="sidebar-menu">
                        <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                        <li><a href="favourites.php"><i class="fa-solid fa-heart"></i> Favourite</a></li>
                    </ul>
                    <div class="sidebar-section-title">ORDERS & SUPPORT</div>
                    <ul class="sidebar-menu">
                        <li><a href="past-orders.php" class="active"><i class="fa-solid fa-box"></i> Past Orders</a>
                        </li>
                        <li><a href="faq.php"><i class="fa-solid fa-circle-question"></i> FAQ</a></li>
                        <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>
                                Logout</a></li>
                    </ul>
                </aside>

                <section class="order-details-container invoice-card" id="invoiceArea">
                    <nav class="breadcrumb no-print">
                        <a href="past-orders.php">Past Orders</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Order Details</span>
                    </nav>

                    <div class="page-title details-title-block no-print">
                        <h1>Order Details</h1>
                        <p class="page-description">Review your invoice, delivery information, and order status.</p>
                    </div>

                    <!-- Invoice summary header -->
                    <div class="invoice-header-card">
                        <div>
                            <p class="page-label">INVOICE</p>
                            <h2>#BH-<?php echo safe($order['order_id']); ?></h2>
                            <p class="muted-text">Placed on <?php echo safe($order['order_date']); ?></p>
                        </div>
                        <div class="invoice-actions no-print">
                            <span
                                class="status-pill <?php echo safe($statusClass); ?>"><?php echo safe(ucfirst($order['order_status'])); ?></span>
                            <button class="btn-soft-pdf" type="button" onclick="downloadInvoicePDF()"><i
                                    class="fas fa-file-pdf"></i> Download PDF</button>
                        </div>
                    </div>

                    <!-- Quick order information cards -->
                    <div class="order-info-grid">
                        <div class="info-card">
                            <div class="info-icon"><i class="far fa-calendar-alt"></i></div>
                            <div class="info-content">
                                <h4>Order Date</h4>
                                <p><?php echo date("j M Y", strtotime($order['order_date'])); ?></p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-truck"></i></div>
                            <div class="info-content">
                                <h4>Expected Delivery</h4>
                                <p><?php echo $delivery_date; ?></p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-box"></i></div>
                            <div class="info-content">
                                <h4>Items</h4>
                                <p><?php echo safe($order['items_count']); ?> items</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-tag"></i></div>
                            <div class="info-content">
                                <h4>Total</h4>
                                <p><?php echo money($total); ?> SAR</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ordered products section -->
                    <h2 class="section-title"><i class="fas fa-seedling"></i> Ordered Items</h2>
                    <div class="table-wrap">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th class="no-print">Review</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item) {
                                    $item_total = $item['quantity'] * $item['unit_price'];
                                    $img = !empty($item['image']) ? "images/plants/" . $item['image'] : "images/plants/monstera.jpeg";
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="product-info">
                                                <div class="product-image"><img src="<?php echo safe($img); ?>"
                                                        alt="<?php echo safe($item['plant_name']); ?>"></div>
                                                <div class="product-details">
                                                    <h4><?php echo safe($item['plant_name'] ?: 'Plant'); ?></h4>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo safe($item['quantity']); ?></td>
                                        <td class="price"><?php echo money($item['unit_price']); ?> SAR</td>
                                        <td class="price"><?php echo money($item_total); ?> SAR</td>
                                        <td class="no-print">
                                            <?php if (!empty($item['plant_id'])) { ?>
                                                <?php if (!empty($item['review_id'])) { ?>
                                                    <a class="rate-link rated"
                                                        href="review-plant.php?order_id=<?php echo safe($order_id); ?>&plant_id=<?php echo safe($item['plant_id']); ?>">
                                                        <i class="fas fa-heart"></i> Edit Review
                                                    </a>
                                                <?php } else { ?>
                                                    <a class="rate-link"
                                                        href="review-plant.php?order_id=<?php echo safe($order_id); ?>&plant_id=<?php echo safe($item['plant_id']); ?>">
                                                        <i class="far fa-heart"></i> Rate Plant
                                                    </a>
                                                <?php } ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr class="summary-row">
                                    <td colspan="4">Subtotal</td>
                                    <td><?php echo money($subtotal); ?> SAR</td>
                                </tr>
                                <?php if ($discount > 0) { ?>
                                    <tr class="summary-row">
                                        <td colspan="4">Discount</td>
                                        <td>-<?php echo money($discount); ?> SAR</td>
                                    </tr><?php } ?>
                                <tr class="summary-row">
                                    <td colspan="4">Shipping</td>
                                    <td>Free</td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="4">Grand Total</td>
                                    <td><?php echo money($total); ?> SAR</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Delivery and payment details -->
                    <h2 class="section-title"><i class="fas fa-truck"></i> Delivery & Payment</h2>
                    <div class="delivery-grid">
                        <div class="delivery-card">
                            <div class="delivery-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="delivery-content">
                                <h4><?php echo $is_pickup ? 'Pickup Address' : 'Delivery Address'; ?></h4>
                                <p><?php echo safe($order['shipping_address']); ?><?php echo !empty($order['city']) ? ', ' . safe($order['city']) : ''; ?>
                                </p>
                                <p><?php echo safe($order['phone']); ?></p>
                            </div>
                        </div>
                        <div class="delivery-card">
                            <div class="delivery-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="delivery-content">
                                <h4>Payment Method</h4>
                                <p><?php echo safe($order['payment_method']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Order tracking timeline -->
                    <h2 class="section-title no-print"><i class="fas fa-clock"></i> Order Timeline</h2>
                    <div class="timeline no-print">
                        <?php foreach ($active_steps as $index => $step) { ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <h4><?php echo safe($step); ?></h4>
                                    <p><?php echo date("j M Y - g:i A", strtotime($order['order_date'] . " +" . ($index * 4) . " hours")); ?>
                                    </p>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Hidden invoice template for PDF download -->
                    <div id="pdfInvoice" class="pdf-only">
                        <div class="pdf-invoice">
                            <div class="pdf-head">
                                <div class="pdf-brand">
                                    <div class="pdf-logo">🌱</div>
                                    <div>
                                        <h2>Bloom House</h2>
                                        <p>Indoor plants online store</p>
                                    </div>
                                </div>
                                <div class="pdf-title">
                                    <h1>Invoice</h1>
                                    <p>#BH-<?php echo safe($order['order_id']); ?></p>
                                </div>
                            </div>
                            <div class="pdf-meta">
                                <p><strong>Order Date:</strong> <?php echo safe($order['order_date']); ?></p>
                                <p><strong>Status:</strong> <?php echo safe(ucfirst($order['order_status'])); ?></p>
                            </div>
                            <div class="pdf-grid">
                                <div class="pdf-box">
                                    <h3>Customer Information</h3>
                                    <p><strong>Name:</strong> <?php echo safe($user_full_name ?: 'Customer'); ?></p>
                                    <p><strong>Email:</strong> <?php echo safe($user_email); ?></p>
                                    <p><strong>Phone:</strong> <?php echo safe($order['phone']); ?></p>
                                </div>
                                <div class="pdf-box">
                                    <h3>Shipping & Payment</h3>
                                    <p><strong>Delivery:</strong>
                                        <?php echo $is_pickup ? 'Store Pickup' : 'Delivery'; ?></p>
                                    <p><strong>Payment:</strong> <?php echo safe($order['payment_method']); ?></p>
                                    <p><strong>City:</strong> <?php echo safe($order['city']); ?></p>
                                    <p><strong>Address:</strong> <?php echo safe($order['shipping_address']); ?></p>
                                </div>
                            </div>
                            <table class="pdf-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item) {
                                        $item_total = $item['quantity'] * $item['unit_price']; ?>
                                        <tr>
                                            <td><?php echo safe($item['plant_name'] ?: 'Plant'); ?></td>
                                            <td><?php echo safe($item['quantity']); ?></td>
                                            <td>SAR <?php echo money($item['unit_price']); ?></td>
                                            <td>SAR <?php echo money($item_total); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <div class="pdf-summary">
                                <p><span>Subtotal</span><strong>SAR <?php echo money($subtotal); ?></strong></p>
                                <?php if ($discount > 0) { ?>
                                    <p><span>Discount</span><strong>SAR <?php echo money($discount); ?></strong></p>
                                <?php } ?>
                                <p><span>Shipping</span><strong>Free</strong></p>
                                <p class="total"><span>Total</span><strong>SAR <?php echo money($total); ?></strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons section -->
                    <div class="action-buttons no-print">
                        <a href="past-orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to
                            Orders</a>
                        <a href="past-order-details.php?order_id=<?php echo safe($order_id); ?>&action=reorder"
                            class="btn btn-primary"><i class="fas fa-redo-alt"></i> Reorder</a>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Website footer -->
    <footer class="footer no-print">
        <div class="footer-container">
            <div class="footer-brand"><a class="footer-logo" href="index.php">🌱 Bloom House</a>
                <p>Bringing nature into your home with beautiful indoor plants.</p>
                <div class="social-icons"><a href="#"><i class="bi bi-whatsapp"></i></a><a href="#"><i
                            class="bi bi-instagram"></i></a><a href="#"><i class="bi bi-snapchat"></i></a></div>
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

    <!-- PDF generation library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Download invoice as PDF
        function downloadInvoicePDF() {
            var invoice = document.getElementById('pdfInvoice');
            if (!invoice) {
                return;
            }

            var clone = invoice.cloneNode(true);
            clone.style.display = 'block';
            clone.classList.remove('pdf-only');

            var opt = {
                margin: 0,
                filename: 'Bloom-House-Invoice-BH-<?php echo safe($order_id); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'px', format: [794, 1123], orientation: 'portrait' }
            };

            html2pdf().set(opt).from(clone).save();
        }
    </script>
</body>

</html>