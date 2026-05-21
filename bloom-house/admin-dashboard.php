<?php
// Admin dashboard page
// Author: Zahra Mohsen

include("admin-auth.php");
include 'db_connect.php';

// ---------- Admin name ----------
$adminFirstName = 'Admin';
$adminLetter = 'A';

$admin_id = intval($_SESSION['admin_id']);
$adminQuery = "SELECT full_name FROM admin WHERE admin_id = $admin_id";

$adminResult = mysqli_query($conn, $adminQuery);
if ($adminResult && mysqli_num_rows($adminResult) > 0) {
    $admin = mysqli_fetch_assoc($adminResult);
    $fullName = $admin['full_name'];
    $nameParts = explode(' ', trim($fullName));
    $adminFirstName = $nameParts[0];
    $adminLetter = strtoupper(substr($adminFirstName, 0, 1));
}

// ---------- Top cards ----------
$totalProducts = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM plants");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $totalProducts = $row['total'];
}

$totalOrders = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $totalOrders = $row['total'];
}

$processingOrders = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE order_status = 'Processing'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $processingOrders = $row['total'];
}

$totalUsers = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $totalUsers = $row['total'];
}

// ---------- Orders overview based on actual database statuses ----------
$shippedOrders = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE order_status = 'Shipped'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $shippedOrders = $row['total'];
}

$deliveredOrders = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE order_status = 'Delivered'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $deliveredOrders = $row['total'];
}

// ---------- Low stock plants ----------
$lowStockPlants = [];
$lowStockCount = 0;
$result = mysqli_query($conn, "SELECT plant_name, stock_quantity FROM plants WHERE stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 4");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $lowStockPlants[] = $row;
    }
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM plants WHERE stock_quantity <= 5");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $lowStockCount = $row['total'];
}

// ---------- Contact messages ----------
$totalMessages = 0;
$latestMessages = [];
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM contact_messages");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $totalMessages = $row['total'];
}

$result = mysqli_query($conn, "SELECT full_name, subject, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 3");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $latestMessages[] = $row;
    }
}

// ---------- Sales overview ----------
$totalSales = 0;
$result = mysqli_query($conn, "SELECT SUM(quantity * unit_price) AS total FROM orders_items");
if ($result && $row = mysqli_fetch_assoc($result)) {
    if ($row['total'] != NULL) {
        $totalSales = $row['total'];
    }
}

$averageOrder = 0;
if ($totalOrders > 0) {
    $averageOrder = $totalSales / $totalOrders;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Page head section -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Bloom House</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-pages.css">
</head>

<body>
    <!-- Dashboard content starts here -->

    <?php
    $adminPageTitle = 'Dashboard';
    $activePage = 'dashboard';
    include 'admin.php';
    ?>
    <div class="welcome-text">
        <h2>Welcome back, <?php echo htmlspecialchars($adminFirstName); ?></h2>
        <p>Here is a quick overview of Bloom House store activity.</p>
    </div>

    <div class="dashboard-card weather-card">
        <div class="card-head">
            <h3>Weather</h3>
            <span class="weather-city">Qatif</span>
        </div>

        <div class="weather-main">
            <i class="bi bi-cloud-sun"></i>
            <div>
                <div id="weatherTemp" class="weather-temp">--°C</div>
                <p id="weatherDesc">Loading weather...</p>
            </div>
        </div>
    </div>

    <div class="stats-row dashboard-stats">
        <a href="manage-products.php" class="stat-card stat-card-link">
            <div class="stat-icon si-green"><i class="bi bi-box-seam"></i></div>
            <div class="stat-info">
                <p>Total Products</p>
                <strong><?php echo $totalProducts; ?></strong>
            </div>
        </a>

        <a href="manage-orders.php" class="stat-card stat-card-link">
            <div class="stat-icon si-blue"><i class="bi bi-bag-check"></i></div>
            <div class="stat-info">
                <p>Total Orders</p>
                <strong><?php echo $totalOrders; ?></strong>
            </div>
        </a>

        <?php
        // Count unread contact messages
        $unreadMessagesQuery = "SELECT COUNT(*) AS total_unread 
                        FROM contact_messages 
                        WHERE status = 'Unread'";

        $unreadMessagesResult = mysqli_query($conn, $unreadMessagesQuery);
        $unreadMessages = mysqli_fetch_assoc($unreadMessagesResult)['total_unread'];
        ?>

        <a href="admin-contact.php" class="stat-card stat-card-link">
            <div class="stat-icon si-orange">
                <i class="bi bi-envelope-fill"></i>
            </div>

            <div class="stat-info">
                <p>New Messages</p>
                <strong><?php echo $unreadMessages; ?></strong>
            </div>
        </a>

        <div class="stat-card">
            <div class="stat-icon si-purple"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <p>Total Users</p>
                <strong><?php echo $totalUsers; ?></strong>
            </div>
        </div>
    </div>

    <!-- Dashboard overview sections -->
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-head">
                <h3>Orders Overview</h3>
                <a href="manage-orders.php" class="mini-link">View Orders</a>
            </div>

            <div class="overview-list">
                <div class="overview-item">
                    <span>Processing</span>
                    <strong><?php echo $processingOrders; ?></strong>
                </div>
                <div class="overview-item">
                    <span>Shipped</span>
                    <strong><?php echo $shippedOrders; ?></strong>
                </div>
                <div class="overview-item">
                    <span>Delivered</span>
                    <strong><?php echo $deliveredOrders; ?></strong>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-head">
                <h3>Low Stock Plants</h3>
                <a href="manage-products.php" class="mini-link">Manage Products</a>
            </div>

            <div class="overview-list">
                <div class="overview-item highlight-item">
                    <span>Total low stock plants</span>
                    <strong><?php echo $lowStockCount; ?></strong>
                </div>

                <?php if (!empty($lowStockPlants)): ?>
                    <?php foreach ($lowStockPlants as $plant): ?>
                        <div class="overview-item">
                            <span><?php echo htmlspecialchars($plant['plant_name']); ?></span>
                            <strong><?php echo htmlspecialchars($plant['stock_quantity']); ?> left</strong>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="overview-item">
                        <span>No low stock plants</span>
                        <strong>Good</strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-grid dashboard-bottom-grid">
        <div class="dashboard-card">
            <div class="card-head">
                <h3>Messages</h3>
                <a href="admin-contact.php" class="mini-link">View Messages</a>
            </div>

            <div class="message-summary">
                <div class="message-icon"><i class="bi bi-envelope-paper"></i></div>
                <div>
                    <h4>You have <?php echo $totalMessages; ?>
                        message<?php if ($totalMessages != 1)
                            echo 's'; ?></h4>
                </div>
            </div>

            <div class="mini-orders">
                <?php if (!empty($latestMessages)): ?>
                    <?php foreach ($latestMessages as $message): ?>
                        <div class="mini-order-row">
                            <div>
                                <div class="mini-order-id"><?php echo htmlspecialchars($message['subject']); ?></div>
                                <div class="mini-order-user"><?php echo htmlspecialchars($message['full_name']); ?>
                                </div>
                            </div>
                            <span class="mini-date"><?php echo htmlspecialchars($message['created_at']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mini-order-row">
                        <div>
                            <div class="mini-order-id">No messages yet</div>
                            <div class="mini-order-user">Contact messages will appear here.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card sales-card">
            <div class="card-head">
                <h3>Sales Overview</h3>
            </div>

            <div class="sales-number">
                <?php echo number_format($totalSales, 2); ?> SAR
            </div>
            <p class="sales-note">Total sales are calculated from order items.</p>

            <div class="overview-list">
                <div class="overview-item">
                    <span>Total Orders</span>
                    <strong><?php echo $totalOrders; ?></strong>
                </div>
                <div class="overview-item">
                    <span>Average Order Value</span>
                    <strong><?php echo number_format($averageOrder, 2); ?> SAR</strong>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    <script>
        // Weather API settings for the Qatif weather card.
        var weatherApiKey = "96148f03192225b7f42e0ca069d1ada4";
        var weatherCity = "Al Qatif";

        fetch("https://api.openweathermap.org/data/2.5/weather?q=" + weatherCity + ",SA&units=metric&appid=" + weatherApiKey)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.cod != 200) {
                    document.getElementById("weatherDesc").textContent = data.message;
                    return;
                }

                document.getElementById("weatherTemp").textContent = Math.round(data.main.temp) + "°C";
                document.getElementById("weatherDesc").textContent = data.weather[0].description;
            })
            .catch(function () {
                document.getElementById("weatherDesc").textContent = "Could not load weather";
            });
    </script>
</body>

</html>