<?php
// Manage Orders page for admin panel
// Author: Zahra Mohsen

include("admin-auth.php");
include("db_connect.php");

// Fetch orders data from database
$search = "";
$status = "";
$whereParts = array();
$params = array();
$types = "";
$allowedStatuses = array('pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled');

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if (isset($_GET['status'])) {
    $status = trim($_GET['status']);
}

if ($search != "") {
    $searchLike = "%" . $search . "%";
    $whereParts[] = "(CAST(orders.order_id AS CHAR) LIKE ? OR user.first_name LIKE ? OR user.last_name LIKE ?)";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

if ($status != "" && strtolower($status) != "all" && in_array(strtolower($status), $allowedStatuses)) {
    $whereParts[] = "LOWER(orders.order_status) = ?";
    $params[] = strtolower($status);
    $types .= "s";
}

$where = "";
if (!empty($whereParts)) {
    $where = " WHERE " . implode(" AND ", $whereParts);
}

$sql = "SELECT orders.order_id, orders.order_date, orders.order_status,
        user.first_name, user.last_name,
        SUM(orders_items.quantity * orders_items.unit_price) AS total
        FROM orders
        INNER JOIN user ON orders.user_id = user.user_id
        INNER JOIN orders_items ON orders.order_id = orders_items.order_id" .
    $where .
    " GROUP BY orders.order_id
        ORDER BY orders.order_id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Page metadata and styles -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders – Bloom House Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-pages.css">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-order.css">
</head>

<body>
    <!-- Main page content -->

    <?php
    $adminPageTitle = 'Manage Orders';
    $activePage = 'orders';
    include 'admin.php';
    ?>
    <?php if ((isset($_GET['updated']) && $_GET['updated'] == '1') || (isset($_GET['order_updated']) && $_GET['order_updated'] == '1')) { ?>
        <div id="orderToast" class="success-toast">
            Order updated successfully.
        </div>
    <?php } ?><!-- User form section -->


    <!-- Search form for filtering orders -->
    <form method="GET" action="manage-orders.php" class="toolbar orders-toolbar">
        <?php if ($status != "" && strtolower($status) != "all") { ?>
            <input type="hidden" name="status"
                value="<?php echo htmlspecialchars(strtolower($status), ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by order ID or customer..."
                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    </form>

    <?php
    $currentStatus = strtolower($status == "" ? "all" : $status);
    $searchQuery = $search != "" ? "&search=" . urlencode($search) : "";
    ?>

    <!-- Order status filter buttons -->
    <div class="orders-filter-tabs">
        <a href="manage-orders.php<?php echo $search != "" ? "?search=" . urlencode($search) : ""; ?>"
            class="filter-tab <?php echo ($currentStatus == 'all') ? 'active' : ''; ?>">All</a>

        <a href="manage-orders.php?status=pending<?php echo $searchQuery; ?>"
            class="filter-tab pending <?php echo ($currentStatus == 'pending') ? 'active' : ''; ?>">Pending</a>

        <a href="manage-orders.php?status=processing<?php echo $searchQuery; ?>"
            class="filter-tab processing <?php echo ($currentStatus == 'processing') ? 'active' : ''; ?>">Processing</a>

        <a href="manage-orders.php?status=shipped<?php echo $searchQuery; ?>"
            class="filter-tab shipped <?php echo ($currentStatus == 'shipped') ? 'active' : ''; ?>">Shipped</a>

        <a href="manage-orders.php?status=delivered<?php echo $searchQuery; ?>"
            class="filter-tab delivered <?php echo ($currentStatus == 'delivered') ? 'active' : ''; ?>">Delivered</a>

        <a href="manage-orders.php?status=completed<?php echo $searchQuery; ?>"
            class="filter-tab completed <?php echo ($currentStatus == 'completed') ? 'active' : ''; ?>">Completed</a>

        <a href="manage-orders.php?status=cancelled<?php echo $searchQuery; ?>"
            class="filter-tab cancelled <?php echo ($currentStatus == 'cancelled') ? 'active' : ''; ?>">Cancelled</a>
    </div>

    <div class="table-wrap orders-table-wrap"><!-- Data table section -->

        <!-- Orders table -->
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <!-- Orders rows -->
            <tbody>
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    // Loop through all orders
                    while ($row = mysqli_fetch_assoc($result)) {
                        $statusClass = "sp-processing";
                        $rowStatus = strtolower($row['order_status']);

                        if ($rowStatus == "pending") {
                            $statusClass = "sp-pending";
                        } elseif ($rowStatus == "processing") {
                            $statusClass = "sp-processing";
                        } elseif ($rowStatus == "shipped") {
                            $statusClass = "sp-shipped";
                        } elseif ($rowStatus == "delivered") {
                            $statusClass = "sp-delivered";
                        } elseif ($rowStatus == "completed") {
                            $statusClass = "sp-completed";
                        } elseif ($rowStatus == "cancelled") {
                            $statusClass = "sp-cancelled";
                        }

                        print ("<tr>");
                        print ("<td><strong>#BH-" . $row['order_id'] . "</strong></td>");
                        print ("<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>");
                        print ("<td>" . $row['order_date'] . "</td>");
                        print ("<td>SAR " . $row['total'] . "</td>");
                        print ("<td><span class='status-pill " . $statusClass . "'>" . $row['order_status'] . "</span></td>");
                        print ("<td>");
                        print ("<div class='action-btns'>");
                        print ("<a class='act-btn edit' title='View invoice' href='admin-view-order.php?id=" . $row['order_id'] . "'><i class='fas fa-eye'></i></a>");
                        print ("<a class='act-btn edit' title='Edit order' href='admin-edit-order.php?id=" . $row['order_id'] . "'><i class='fas fa-pen'></i></a>");
                        print ("</div>");
                        print ("</td>");
                        print ("</tr>");
                    }
                } else {
                    print ("<tr><td colspan='6'>No orders found.</td></tr>");
                }
                ?>
            </tbody>
        </table>

        <div class="table-footer">
            <span class="table-info">Showing <?php print (mysqli_num_rows($result)); ?> orders</span>
            <div class="pagination">
                <button class="pg-btn active">1</button>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script>

        // Hide the order update toast after a short time.
        var orderToast = document.getElementById("orderToast");

        if (orderToast) {
            setTimeout(function () {
                orderToast.style.display = "none";
            }, 3000);
        }
    </script>

</body>

</html>
<?php mysqli_close($conn); ?>