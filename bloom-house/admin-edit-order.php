<?php
// Admin edit order page 
// Author: Zahra Mohsen

include("admin-auth.php");
include("db_connect.php");

// Get the selected order ID from the URL
// Fetch order details from the database using SQL query
// Update order information in the database
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = "";

// Check if the optional delivery_method column exists without changing the database on page load.
$hasDeliveryMethod = false;
$columnCheck = // Run the SQL query
    mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'delivery_method'");
if ($columnCheck && mysqli_num_rows($columnCheck) > 0) {
    $hasDeliveryMethod = true;
}

// Check if admin submitted the edit order form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $allowed_statuses = array('Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled');
    $allowed_payments = array('Credit Card', 'Cash on Delivery', 'Apple Pay', 'Tamara');
    $allowed_delivery = array('Delivery', 'Store Pickup');

    $order_status = trim($_POST['order_status'] ?? '');
    $shipping_address_post = trim($_POST['shipping_address'] ?? '');
    $city_post = trim($_POST['city'] ?? '');
    $phone_post = trim($_POST['phone'] ?? '');
    $payment_method_post = trim($_POST['payment_method'] ?? '');
    $discount_post = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
    $delivery_method_post = trim($_POST['delivery_method'] ?? 'Delivery');

    if (!in_array($order_status, $allowed_statuses)) {
        $error = "Please choose a valid order status.";
    } elseif ($phone_post !== '' && !preg_match('/^05[0-9]{8}$/', $phone_post)) {
        $error = "Phone number must start with 05 and contain 10 digits.";
    } elseif (isset($_POST['city']) && $city_post == '') {
        $error = "City cannot be empty.";
    } elseif (isset($_POST['shipping_address']) && $shipping_address_post == '') {
        $error = "Shipping address cannot be empty.";
    } elseif ($discount_post < 0 || $discount_post > 100) {
        $error = "Discount must be between 0 and 100.";
    } elseif ($payment_method_post !== '' && !in_array($payment_method_post, $allowed_payments)) {
        $error = "Please choose a valid payment method.";
    } elseif ($delivery_method_post !== '' && !in_array($delivery_method_post, $allowed_delivery)) {
        $error = "Please choose a valid delivery method.";
    }

    if ($error == "" && isset($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $quantity) {
            if (intval($quantity) < 1) {
                $error = "Quantity must be at least 1.";
                break;
            }
        }
    }

    if ($error == "" && $order_id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $current_result = mysqli_stmt_get_result($stmt);
        $current_order = // Convert database result into an array to use in the page
            mysqli_fetch_assoc($current_result);
        mysqli_stmt_close($stmt);

        if ($current_order) {
            $shipping_address = isset($_POST['shipping_address']) ? $shipping_address_post : $current_order['shipping_address'];
            $city = isset($_POST['city']) ? $city_post : $current_order['city'];
            $phone = isset($_POST['phone']) ? $phone_post : $current_order['phone'];
            $payment_method = isset($_POST['payment_method']) ? $payment_method_post : $current_order['payment_method'];
            $discount = isset($_POST['discount']) ? $discount_post : floatval($current_order['discount']);
            $delivery_method = isset($_POST['delivery_method']) ? $delivery_method_post : ($hasDeliveryMethod && isset($current_order['delivery_method']) ? $current_order['delivery_method'] : 'Delivery');

            if ($discount < 0) {
                $discount = 0;
            }
            if ($discount > 100) {
                $discount = 100;
            }

            if ($hasDeliveryMethod) {
                $update_order = "UPDATE orders SET order_status = ?, shipping_address = ?, city = ?, phone = ?, payment_method = ?, discount = ?, delivery_method = ? WHERE order_id = ?";
                $stmt = mysqli_prepare($conn, $update_order);
                mysqli_stmt_bind_param($stmt, "sssssdsi", $order_status, $shipping_address, $city, $phone, $payment_method, $discount, $delivery_method, $order_id);
            } else {
                $update_order = "UPDATE orders SET order_status = ?, shipping_address = ?, city = ?, phone = ?, payment_method = ?, discount = ? WHERE order_id = ?";
                $stmt = mysqli_prepare($conn, $update_order);
                mysqli_stmt_bind_param($stmt, "sssssdi", $order_status, $shipping_address, $city, $phone, $payment_method, $discount, $order_id);
            }

            if ($stmt && mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                if (isset($_POST['quantity'])) {
                    $item_stmt = mysqli_prepare($conn, "UPDATE orders_items SET quantity = ?, unit_price = ? WHERE order_item_id = ? AND order_id = ?");
                    foreach ($_POST['quantity'] as $order_item_id => $quantity) {
                        $order_item_id = intval($order_item_id);
                        $quantity = intval($quantity);
                        if ($quantity < 1) {
                            $quantity = 1;
                        }
                        $unit_price = isset($_POST['unit_price'][$order_item_id]) ? floatval($_POST['unit_price'][$order_item_id]) : 0;
                        if ($unit_price < 0) {
                            $unit_price = 0;
                        }
                        mysqli_stmt_bind_param($item_stmt, "idii", $quantity, $unit_price, $order_item_id, $order_id);
                        mysqli_stmt_execute($item_stmt);
                    }
                    mysqli_stmt_close($item_stmt);
                }

                // Redirect admin after updating the order successfully
                header("Location: manage-orders.php?updated=1");
                exit();
            } else {
                if ($stmt) {
                    mysqli_stmt_close($stmt);
                }
                $error = "Sorry, the order was not updated.";
            }
        } else {
            $error = "Order not found.";
        }
    }
}

$order_select = "SELECT orders.*, user.first_name, user.last_name, user.email AS user_email
                 FROM orders
                 INNER JOIN user ON orders.user_id = user.user_id
                 WHERE orders.order_id = ?";
$stmt = mysqli_prepare($conn, $order_select);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);
mysqli_stmt_close($stmt);

$items = array();
$subtotal = 0;
$items_select = "SELECT orders_items.*, plants.plant_name
                 FROM orders_items
                 INNER JOIN plants ON orders_items.plant_id = plants.plant_id
                 WHERE orders_items.order_id = ?";
$stmt = mysqli_prepare($conn, $items_select);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
if ($items_result) {
    // Loop through order items and display them one by one
    while ($item = mysqli_fetch_assoc($items_result)) {
        $line_total = $item['quantity'] * $item['unit_price'];
        $subtotal = $subtotal + $line_total;
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
// Function section for reusable code

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
// Function section for reusable code

function checkedValue($value, $current)
{
    if (strtolower($value) == strtolower($current)) {
        echo "checked";
    }
}
// Function section for reusable code
function checkedPayment($value, $current)
{
    $currentLower = strtolower($current);
    $valueLower = strtolower($value);
    if ($valueLower == $currentLower) {
        echo "checked";
    } elseif ($value == "Cash on Delivery" && ($currentLower == "cash" || $currentLower == "cod")) {
        echo "checked";
    } elseif ($value == "Credit Card" && ($currentLower == "card" || $currentLower == "credit card")) {
        echo "checked";
    } elseif ($value == "Apple Pay" && $currentLower == "apple pay") {
        echo "checked";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order – Bloom House Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-pages.css">
    <link rel="stylesheet" href="css/admin-pages.css">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-order.css">
</head>

<body>

    <?php
    $adminPageTitle = 'Edit Order';
    $activePage = 'orders';
    include 'admin.php';
    ?>
    <div class="page-title-wrap">
        <a class="circle-back" href="manage-orders.php"><i class="fas fa-chevron-left"></i></a>
        <div class="admin-breadcrumb">
            <a href="manage-orders.php">Manage Orders</a><span class="slash-text">/</span><span>Edit
                Order</span>
        </div>
    </div>

    <?php if (!$order) { ?>
        <div class="dashboard-card">
            <h3>Order not found</h3>
            <p>This order does not exist.</p><a class="btn-light" href="manage-orders.php">Back to Orders</a>
        </div>
    <?php } else { ?>
        <?php if ($error != "") { ?>
            <div class="toast-note" style="background:#fff4f2;color:#c0392b;border-color:#f1d2cd;"><?php echo $error; ?>
            </div><?php } ?><!-- User form section -->

        <!-- Form used for editing or displaying order information -->
        <form method="POST" action="admin-edit-order.php?id=<?php echo (int) $order['order_id']; ?>" id="editOrderForm">
            <div class="invoice-header-card">
                <div>
                    <p class="page-label">EDIT ORDER</p>
                    <h2>#BH-<?php echo (int) $order['order_id']; ?></h2>
                    <p class="muted-text">Customer: <?php echo $order['first_name'] . " " . $order['last_name']; ?>
                    </p>
                </div>
                <div class="invoice-actions"><a class="btn-light"
                        href="admin-view-order.php?id=<?php echo (int) $order['order_id']; ?>"><i class="fas fa-eye"></i>
                        View</a><a class="btn-soft-pdf"
                        href="admin-view-order.php?id=<?php echo (int) $order['order_id']; ?>&download=1"><i
                            class="fas fa-file-pdf"></i> Download PDF</a></div>
            </div>

            <div class="dashboard-card" style="margin-bottom:18px;">
                <div class="card-head">
                    <h3>Order Status</h3>
                </div>
                <div class="option-card">
                    <label class="radio-box status-option pending"><input type="radio" name="order_status" value="Pending"
                            <?php checkedValue("Pending", $order['order_status']); ?> required>
                        Pending</label>
                    <label class="radio-box status-option processing"><input type="radio" name="order_status"
                            value="Processing" <?php checkedValue("Processing", $order['order_status']); ?>>
                        Processing</label>
                    <label class="radio-box status-option shipped"><input type="radio" name="order_status" value="Shipped"
                            <?php checkedValue("Shipped", $order['order_status']); ?>>
                        Shipped</label>
                    <label class="radio-box status-option delivered"><input type="radio" name="order_status"
                            value="Delivered" <?php checkedValue("Delivered", $order['order_status']); ?>>
                        Delivered</label>
                    <label class="radio-box status-option completed"><input type="radio" name="order_status"
                            value="Completed" <?php checkedValue("Completed", $order['order_status']); ?>>
                        Completed</label>
                    <label class="radio-box status-option cancelled"><input type="radio" name="order_status"
                            value="Cancelled" <?php checkedValue("Cancelled", $order['order_status']); ?>>
                        Cancelled</label>
                </div>
            </div>

            <div class="dashboard-card locked compact-info" id="infoCard" style="margin-bottom:18px;">

                <div class="card-head">
                    <h3>Order & Shipping Information</h3>

                    <!-- Action button -->
                    <button type="button" class="btn-light" id="unlockInfoBtn">
                        <i class="fas fa-unlock"></i> Edit Info
                    </button>
                </div>

                <div class="section-divider vertical-sections">

                    <!-- ORDER INFO -->
                    <div class="info-section-box">
                        <h3 class="mini-section-title">Order Info</h3>

                        <div class="profile-form-clean">
                            <div class="form-group-clean">
                                <label>Payment Method</label>

                                <div class="radio-line-group">
                                    <label class="status-radio-card">
                                        <input type="radio" name="payment_method" value="Credit Card" <?php checkedPayment("Credit Card", $order['payment_method']); ?> disabled>
                                        <span><i class="far fa-credit-card"></i> Credit Card</span>
                                    </label>

                                    <label class="status-radio-card">
                                        <input type="radio" name="payment_method" value="Cash on Delivery" <?php checkedPayment("Cash on Delivery", $order['payment_method']); ?> disabled>
                                        <span><i class="fas fa-money-bill-wave"></i> Cash on Delivery</span>
                                    </label>

                                    <label class="status-radio-card">
                                        <input type="radio" name="payment_method" value="Apple Pay" <?php checkedPayment("Apple Pay", $order['payment_method']); ?> disabled>
                                        <span><i class="fab fa-apple"></i> Apple Pay</span>
                                    </label>

                                    <label class="status-radio-card">
                                        <input type="radio" name="payment_method" value="Tamara" <?php checkedPayment("Tamara", $order['payment_method']); ?> disabled>
                                        <span><i class="far fa-calendar-check"></i> Tamara</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group-clean">
                                <label>Discount Percentage (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="discount"
                                    value="<?php echo h($discountPercent); ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <!-- SHIPPING INFO -->
                    <div class="info-section-box">
                        <h3 class="mini-section-title">Shipping Info</h3>

                        <div class="profile-form-clean">
                            <div class="form-group-clean">
                                <label>Delivery Method</label>

                                <div class="radio-line-group">
                                    <label class="status-radio-card">
                                        <input type="radio" name="delivery_method" value="Delivery" <?php checkedValue("Delivery", $deliveryValue); ?> disabled>
                                        <span><i class="fas fa-truck"></i> Delivery</span>
                                    </label>

                                    <label class="status-radio-card">
                                        <input type="radio" name="delivery_method" value="Store Pickup" <?php checkedValue("Store Pickup", $deliveryValue); ?> disabled>
                                        <span><i class="fas fa-store"></i> Store Pickup</span>
                                    </label>
                                </div>
                            </div>

                            <div class="profile-grid-two">
                                <div class="form-group-clean">
                                    <label>Phone</label>
                                    <input type="text" name="phone" value="<?php echo h($order['phone']); ?>" disabled>
                                </div>

                                <div class="form-group-clean">
                                    <label>City</label>
                                    <input type="text" name="city" value="<?php echo h($order['city']); ?>" disabled>
                                </div>
                            </div>

                            <div class="form-group-clean">
                                <label>Shipping Address</label>

                                <div class="address-search-wrap">
                                    <input type="text" id="shippingAddress" name="shipping_address"
                                        value="<?php echo h($order['shipping_address']); ?>"
                                        placeholder="Type address then click Search" disabled>

                                    <button type="button" id="searchAddressBtn" class="btn-light" disabled>
                                        <i class="fas fa-map-marker-alt"></i> Search
                                    </button>
                                </div>

                                <div id="addressResults" class="address-results"></div>
                            </div>


                        </div>
                    </div>

                </div>

            </div>

            <div class="invoice-table-card"><!-- Data table section -->

                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($items) > 0) {
                            foreach ($items as $item) {
                                $line_total = $item['quantity'] * $item['unit_price'];
                                print ("<tr><td><strong>" . h($item['plant_name']) . "</strong></td><td><input class='small-edit-input' type='number' min='1' name='quantity[" . (int) $item['order_item_id'] . "]' value='" . (int) $item['quantity'] . "' required></td><td><input class='small-edit-input' type='number' min='0' step='0.01' name='unit_price[" . (int) $item['order_item_id'] . "]' value='" . h($item['unit_price']) . "' required></td><td>SAR " . number_format($line_total, 2) . "</td></tr>");
                            }
                        } else {
                            print ("<tr><td colspan='4'>No items found.</td></tr>");
                        }
                        ?>
                    </tbody>
                </table>
                <div class="invoice-summary-box">
                    <p><span>Subtotal</span><strong>SAR <?php echo number_format($subtotal, 2); ?></strong></p>
                    <p><span>Discount (<?php echo number_format($discountPercent, 2); ?>%)</span><strong>SAR
                            <?php echo number_format($discountValue, 2); ?></strong></p>
                    <p class="grand-total"><span>Total</span><strong>SAR
                            <?php echo number_format($grand_total, 2); ?></strong></p>
                </div>
            </div>

            <div class="edit-order-actions"><button type="submit" class="btn-save-clean"><i class="fas fa-save"></i>
                    Save Changes</button><a class="btn-light" href="manage-orders.php">Cancel</a></div>
        </form>
    <?php } ?>
    </div>
    </div>

    <div class="confirm-overlay" id="confirmOverlay">
        <div class="store-confirm">
            <div class="store-confirm-icon"><i class="fas fa-exclamation"></i></div>
            <h3>Are you sure?</h3>
            <p>You are going to edit the order and shipping information. After saving, this change cannot be undone.</p>
            <div class="store-confirm-actions"><button type="button" class="btn-light"
                    id="cancelUnlock">Cancel</button><button type="button" class="btn-save-clean"
                    id="confirmUnlock">Yes, edit info</button></div>
        </div>
    </div>

    <script src="js/orders.js"></script>
</body>

</html>
<?php mysqli_close($conn); ?>