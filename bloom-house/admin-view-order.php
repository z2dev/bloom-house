<?php
// Author: Zahra Mohsen

include("admin-auth.php");
// Connect the page to the database file.
include("db_connect.php");

// Read the order ID from the URL so we know which order to display.
// Prepare the SQL query that gets the main order information.
// Prepare the SQL query that gets all products/items inside this order.
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if the optional delivery_method column exists without changing the database on page load.
$hasDeliveryMethod = false;
$columnCheck = // Run the SQL query on the database.
    mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'delivery_method'");
if ($columnCheck && mysqli_num_rows($columnCheck) > 0) {
    $hasDeliveryMethod = true;
}

$order_sql = "SELECT orders.*, user.first_name, user.last_name, user.email AS user_email,
             user.phone AS user_phone
             FROM orders
             INNER JOIN user ON orders.user_id = user.user_id
             WHERE orders.order_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = // Store the fetched order data in an array so it can be printed in the HTML.
    mysqli_fetch_assoc($order_result);
mysqli_stmt_close($stmt);

$items_sql = "SELECT orders_items.*, plants.plant_name, plants.image
              FROM orders_items
              INNER JOIN plants ON orders_items.plant_id = plants.plant_id
              WHERE orders_items.order_id = ?";
$stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

// Create a subtotal variable to calculate the order total before discount.
$subtotal = 0;
$items = array();
if ($items_result) {
    // Loop through the order items and display each item in the invoice table.
    while ($item = mysqli_fetch_assoc($items_result)) {
        $item_total = $item['quantity'] * $item['unit_price'];
        $subtotal = $subtotal + $item_total;
        $items[] = $item;
    }
}
mysqli_stmt_close($stmt);

$discountPercent = 0;
$deliveryValue = "Delivery";
if ($order) {
    $discountPercent = floatval($order['discount']);
    if ($hasDeliveryMethod && isset($order['delivery_method']) && $order['delivery_method'] != "") {
        $deliveryValue = $order['delivery_method'];
    }
}
$discountValue = $subtotal * ($discountPercent / 100);
$grand_total = $subtotal - $discountValue;
if ($grand_total < 0) {
    $grand_total = 0;
}

$statusClass = "sp-processing";
if ($order) {
    if ($order['order_status'] == "Pending") {
        $statusClass = "sp-pending";
    } elseif ($order['order_status'] == "Processing") {
        $statusClass = "sp-processing";
    } elseif ($order['order_status'] == "Shipped") {
        $statusClass = "sp-shipped";
    } elseif ($order['order_status'] == "Delivered") {
        $statusClass = "sp-delivered";
    } elseif ($order['order_status'] == "Completed") {
        $statusClass = "sp-completed";
    } elseif ($order['order_status'] == "Cancelled") {
        $statusClass = "sp-cancelled";
    }
}
// Function section for reusable code

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order – Bloom House Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-pages.css">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-order.css">
</head>

<body>

    <?php
    $adminPageTitle = 'Order Details';
    $activePage = 'orders';
    include 'admin.php';
    ?>
    <!-- Page title and breadcrumb section. -->
    <div class="page-title-wrap">
        <a class="circle-back" href="manage-orders.php"><i class="fas fa-chevron-left"></i></a>
        <div class="admin-breadcrumb">
            <a href="manage-orders.php">Manage Orders</a><span class="slash-text">/</span><span>View
                Order</span>
        </div>
    </div>

    <?php // Check if the order was not found and stop the page safely.
    if (!$order) { ?>
        <div class="dashboard-card">
            <h3>Order not found</h3>
            <p>This order does not exist.</p>
            <a class="btn-light" href="manage-orders.php">Back to Orders</a>
        </div>
    <?php } else { ?>

        <!-- Invoice header showing order number and main actions. -->
        <div class="invoice-header-card">
            <div>
                <p class="page-label">INVOICE</p>
                <h2>#BH-<?php echo (int) $order['order_id']; ?></h2>
                <p class="muted-text">Placed on <?php echo h($order['order_date']); ?></p>
            </div>
            <div class="invoice-actions">
                <span class="status-pill <?php echo h($statusClass); ?>"><?php echo h($order['order_status']); ?></span>
                <a class="btn-light" href="admin-edit-order.php?id=<?php echo (int) $order['order_id']; ?>"><i
                        class="fas fa-pen"></i> Edit</a>
                <button class="btn-soft-pdf" type="button" onclick="downloadInvoicePDF()"><i class="fas fa-file-pdf"></i>
                    Download PDF</button>
            </div>
        </div>

        <!-- Order and customer information cards. -->
        <div class="order-info-grid">
            <div class="dashboard-card">
                <div class="card-head">
                    <h3>Customer Information</h3>
                </div>
                <p><strong>Name:</strong> <?php echo h($order['first_name'] . " " . $order['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo h($order['user_email']); ?></p>
                <p><strong>Account Phone:</strong> <?php echo h($order['user_phone']); ?></p>
            </div>

            <div class="dashboard-card">
                <div class="card-head">
                    <h3>Shipping & Payment</h3>
                </div>
                <p><strong>Delivery:</strong> <?php echo h($deliveryValue); ?></p>
                <p><strong>Payment:</strong> <?php echo h($order['payment_method']); ?></p>
                <p><strong>Phone:</strong> <?php echo h($order['phone']); ?></p>
                <p><strong>City:</strong> <?php echo h($order['city']); ?></p>
                <p><strong>Address:</strong> <?php echo h($order['shipping_address']); ?></p>
            </div>
        </div>

        <!-- Invoice table section showing all ordered plants. -->
        <div class="invoice-table-card"><!-- Data table section -->

            <!-- Table used to display order item details. -->
            <table>
                <!-- Table header row with item column names. -->
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <!-- Table body where order items are printed dynamically. -->
                <tbody>
                    <?php
                    if (count($items) > 0) {
                        foreach ($items as $item) {
                            $item_total = $item['quantity'] * $item['unit_price'];
                            print ("<tr>");
                            print ("<td><strong>" . h($item['plant_name']) . "</strong></td>");
                            print ("<td>" . $item['quantity'] . "</td>");
                            print ("<td>SAR " . number_format($item['unit_price'], 2) . "</td>");
                            print ("<td>SAR " . number_format($item_total, 2) . "</td>");
                            print ("</tr>");
                        }
                    } else {
                        print ("<tr><td colspan='4'>No items found.</td></tr>");
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Summary box showing subtotal, discount, and final total. -->
        <div class="invoice-summary-card">
            <p><span>Subtotal</span><strong>SAR <?php echo number_format($subtotal, 2); ?></strong></p>
            <p><span>Discount (<?php echo number_format($discountPercent, 2); ?>%)</span><strong>SAR
                    <?php echo number_format($discountValue, 2); ?></strong></p>
            <p class="grand-total"><span>Total</span><strong>SAR
                    <?php echo number_format($grand_total, 2); ?></strong></p>
        </div>

        <!-- Hidden PDF invoice layout used when downloading the invoice. -->
        <div id="pdfInvoice" class="pdf-only" style="display:none;">
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
                        <p>#BH-<?php echo (int) $order['order_id']; ?></p>
                    </div>
                </div>
                <div class="pdf-meta">
                    <p><strong>Order Date:</strong> <?php echo h($order['order_date']); ?></p>
                    <p><strong>Status:</strong> <?php echo h($order['order_status']); ?></p>
                </div>
                <div class="pdf-grid">
                    <div class="pdf-box">
                        <h3>Customer Information</h3>
                        <p><strong>Name:</strong> <?php echo h($order['first_name'] . " " . $order['last_name']); ?>
                        </p>
                        <p><strong>Email:</strong> <?php echo h($order['user_email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo h($order['user_phone']); ?></p>
                    </div>
                    <div class="pdf-box">
                        <h3>Shipping & Payment</h3>
                        <p><strong>Delivery:</strong> <?php echo h($deliveryValue); ?></p>
                        <p><strong>Payment:</strong> <?php echo h($order['payment_method']); ?></p>
                        <p><strong>City:</strong> <?php echo h($order['city']); ?></p>
                        <p><strong>Address:</strong> <?php echo h($order['shipping_address']); ?></p>
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
                        <?php foreach ($items as $item) {
                            $item_total = $item['quantity'] * $item['unit_price']; ?>
                            <tr>
                                <td><?php echo $item['plant_name']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>SAR <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>SAR <?php echo number_format($item_total, 2); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="pdf-summary">
                    <p><span>Subtotal</span><strong>SAR <?php echo number_format($subtotal, 2); ?></strong></p>
                    <p><span>Discount</span><strong>SAR <?php echo number_format($discountValue, 2); ?></strong></p>
                    <p class="total"><span>Total</span><strong>SAR
                            <?php echo number_format($grand_total, 2); ?></strong></p>
                </div>
            </div>
        </div>
    <?php } ?>
    </div>
    </div>

    <!-- JavaScript files and page scripts are loaded here. -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        // Download the hidden invoice section as a PDF file.
        function downloadInvoicePDF() {
            var invoice = document.getElementById("pdfInvoice");

            if (!invoice) {
                alert("Invoice content was not found.");
                return;
            }

            invoice.style.display = "block";

            var options = {
                margin: 0.4,
                filename: "Bloom-House-Invoice-<?php echo (int) $order_id; ?>.pdf",
                image: { type: "jpeg", quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: "in", format: "a4", orientation: "portrait" }
            };

            html2pdf().set(options).from(invoice).save().then(function () {
                invoice.style.display = "none";
            });
        }

        // If the page is opened from the Download PDF button, download automatically.
        var urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get("download") === "1") {
            window.addEventListener("load", function () {
                downloadInvoicePDF();
            });
        }
    </script>
</body>

</html>
<?php mysqli_close($conn); ?>