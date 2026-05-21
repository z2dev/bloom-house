<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
include("db_connect.php");

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_login_required($redirect) {
    header("Location: login-required.php?redirect=" . urlencode($redirect));
    exit();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    redirect_login_required('past-orders.php');
}

$user_id = (int) $_SESSION['user_id'];

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

// Get all past orders for the current user
$sql = "SELECT 
            o.order_id,
            o.order_date,
            o.order_status,
            COUNT(oi.order_item_id) AS items_count,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_amount
        FROM orders o
        LEFT JOIN orders_items oi ON o.order_id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.order_id, o.order_date, o.order_status
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Past Orders</title>
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/order.css?v=50">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
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

<main class="account-page">
    <div class="container">
        <h1 class="account-main-title">My Account</h1>

        <div class="account-layout">
            <aside class="account-sidebar">
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
                    <li><a href="past-orders.php" class="active"><i class="fa-solid fa-box"></i> Past Orders</a></li>
                    <li><a href="faq.php"><i class="fa-solid fa-circle-question"></i> FAQ</a></li>
                    <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                </ul>
            </aside>

            <section class="profile-content-card orders-card">
                <nav class="breadcrumb">
                    <a href="profile.php">Profile</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Past Orders</span>
                </nav>

                <div class="page-title orders-title-block">
                    <h1>My Past Orders</h1>
                    <p class="page-description">View your previous orders and open the invoice details.</p>
                </div>

                <div class="table-wrap">
                    <!-- Orders table -->
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0) { ?>
                                <?php while ($row = $result->fetch_assoc()) { 
                                    $status = strtolower($row['order_status']);
                                    $class = "status-badge status-pending";
                                    if ($status == "delivered") $class = "status-badge status-delivered";
                                    elseif ($status == "shipped") $class = "status-badge status-shipped";
                                    elseif ($status == "processing") $class = "status-badge status-processing";
                                ?>
                                    <tr>
                                        <td>#<?php echo safe($row['order_id']); ?></td>
                                        <td><?php echo safe(date("j F Y", strtotime($row['order_date']))); ?></td>
                                        <td><?php echo safe($row['items_count']); ?></td>
                                        <td><?php echo safe(number_format($row['total_amount'], 0)); ?> SAR</td>
                                        <td><span class="<?php echo safe($class); ?>"><?php echo safe(ucfirst($row['order_status'])); ?></span></td>
                                        <td><a href="past-order-details.php?order_id=<?php echo safe($row['order_id']); ?>" class="view-link">View</a></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" class="empty-state">No orders found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
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
            <div class="social-icons">
                <a href="#"><i class="bi bi-whatsapp"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-snapchat"></i></a>
            </div>
        </div>
        <div class="footer-col"><h3>Customer Support</h3><ul><li><a href="faq.php">FAQs</a></li><li><a href="contact.php">Contact Us</a></li></ul></div>
        <div class="footer-col"><h3>Quick Links</h3><ul><li><a href="index.php">Home</a></li><li><a href="products.php">Products</a></li><li><a href="favourites.php">Favourites</a></li><li><a href="profile.php">Profile</a></li></ul></div>
        <div class="footer-col footer-contact"><h3>Contact</h3><ul><li><i class="bi bi-envelope"></i> support@bloomhouse.com</li><li><i class="bi bi-telephone"></i> +966 123 456 789</li><li><i class="bi bi-geo-alt"></i> Eastern Region, KSA</li><li><i class="bi bi-clock"></i> Sat–Thu: 7AM–9PM</li></ul></div>
    </div>
    <div class="footer-bottom"><p>&copy; 2026 Bloom House. All rights reserved.</p></div>
</footer>

</body>
</html>
<?php $stmt->close(); ?>
