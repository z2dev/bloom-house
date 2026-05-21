<?php
include("db_connect.php");

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = null;
$items = array();
$subtotal = 0;
$pickupLocationText = "Bloom House Store - Al Qatif, Eastern Region, KSA";
$pickupMapLink = "https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($order_id > 0) {
    $order_sql = "SELECT orders.*, user.first_name, user.last_name, user.email AS user_email, user.phone AS user_phone
                  FROM orders
                  LEFT JOIN user ON orders.user_id = user.user_id
                  WHERE orders.order_id = ?";
    $stmt = mysqli_prepare($conn, $order_sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $order_result = mysqli_stmt_get_result($stmt);
    if ($order_result && mysqli_num_rows($order_result) > 0) {
        $order = mysqli_fetch_assoc($order_result);
    }
    mysqli_stmt_close($stmt);

    $items_sql = "SELECT orders_items.*, plants.plant_name, plants.image
                  FROM orders_items
                  INNER JOIN plants ON orders_items.plant_id = plants.plant_id
                  WHERE orders_items.order_id = ?";
    $stmt = mysqli_prepare($conn, $items_sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $items_result = mysqli_stmt_get_result($stmt);

    if ($items_result) {
        while ($item = mysqli_fetch_assoc($items_result)) {
            $item_total = floatval($item['quantity']) * floatval($item['unit_price']);
            $subtotal += $item_total;
            $items[] = $item;
        }
    }
    mysqli_stmt_close($stmt);
}

$isPickup = false;
if ($order && isset($order['shipping_address']) && strpos($order['shipping_address'], 'PICKUP:') === 0) {
    $isPickup = true;
}

$discountPercent = 0;
if ($order && isset($order['discount'])) {
    $discountPercent = floatval($order['discount']);
}
$discountValue = $subtotal * ($discountPercent / 100);
$shippingValue = 0;
$grand_total = $subtotal - $discountValue + $shippingValue;
if ($grand_total < 0) {
    $grand_total = 0;
}
$customerName = $order ? trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) : '';
if ($customerName == '') {
    $customerName = 'Customer';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bloom House | Order Confirmation</title>
  <!-- Updated invoice button to use Past Order Details invoice design -->

  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/layout.css" />
  <link rel="stylesheet" href="css/cart-checkout.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">

  <style>
    .pickup-map-card {
      display: block;
      margin-top: 12px;
      padding: 18px;
      border-radius: 14px;
      background: linear-gradient(135deg, #e9f8e7, #ffffff);
      border: 1px dashed #9fc39b;
      text-align: center;
      color: #2e6b3f;
      font-weight: 700;
      text-decoration: none;
    }
    .green-confirm-box {
      margin-bottom: 22px;
      padding: 20px;
      border-radius: 16px;
      background: #f3fff3;
      text-align: center;
      border: 1px solid #d9e2d8;
    }
    .green-confirm-box h2 { margin-bottom: 8px; }

    /* Hidden invoice style only for PDF download - it does not affect the confirmation page */
    .pdf-only { display:none; }
    .pdf-invoice{
      width: 760px;
      padding: 34px 38px;
      background:#ffffff;
      color:#1f2d25;
      font-family:'Poppins', Arial, sans-serif;
      box-sizing:border-box;
    }
    .pdf-head{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      border-bottom:2px solid #1f2d25;
      padding-bottom:22px;
      margin-bottom:22px;
    }
    .pdf-brand{ display:flex; align-items:center; gap:14px; }
    .pdf-logo{
      width:48px; height:48px; border-radius:12px;
      background:#eef7ee; display:flex; align-items:center; justify-content:center;
      font-size:28px;
    }
    .pdf-brand h2{ margin:0 0 4px; font-size:26px; color:#1f2d25; }
    .pdf-brand p{ margin:0; color:#5f6f66; font-size:16px; }
    .pdf-title{ text-align:right; }
    .pdf-title h1{ margin:0 0 8px; font-size:34px; color:#1f2d25; }
    .pdf-title p{ margin:0; color:#5f6f66; font-size:16px; }
    .pdf-meta{ margin-bottom:18px; font-size:16px; color:#4f5f55; }
    .pdf-meta p{ margin:7px 0; }
    .pdf-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:16px;
      margin-bottom:22px;
    }
    .pdf-box{
      border:1px solid #d9e2d8;
      border-radius:14px;
      padding:16px;
      min-height:128px;
      box-sizing:border-box;
    }
    .pdf-box h3{ margin:0 0 14px; font-size:20px; color:#1f2d25; }
    .pdf-box p{ margin:7px 0; font-size:14px; line-height:1.5; color:#1f2d25; }
    .pdf-table{ width:100%; border-collapse:collapse; margin-top:8px; font-size:14px; }
    .pdf-table th{
      background:#eef5ef;
      text-align:left;
      padding:13px;
      border:1px solid #cfd9cf;
      color:#1f2d25;
    }
    .pdf-table td{ padding:13px; border:1px solid #d9e2d8; color:#1f2d25; }
    .pdf-summary{
      width:42%;
      margin-left:auto;
      margin-top:26px;
      font-size:17px;
    }
    .pdf-summary p{
      display:flex;
      justify-content:space-between;
      margin:0;
      padding:12px 0;
      border-bottom:1px solid #d9e2d8;
    }
    .pdf-summary .total{
      font-size:24px;
      font-weight:800;
      color:#1f2d25;
    }

    .confirm-actions .secondary-btn{
      display:flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      font-size:18px;
      font-weight:600;
    }

    .confirm-actions .secondary-btn i{
      font-size:16px;
      position:relative;
      top:-1px;
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
        <a href="search.php" class="user-icon"><i class="fas fa-search"></i></a>
        <a href="cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
        <a href="profile.php" class="user-icon"><i class="fas fa-user"></i></a>
      </div>
    </div>
  </header>

  <main class="page">
    <section class="page-hero confirmation-hero">
      <div class="container">
        <h1>Order Confirmation</h1>

        <div class="confirm-check">
          <i class="bi bi-check-lg"></i>
        </div>

        <h2 class="confirm-title"> Thank you for choosing Bloom House!</h2>
        <p class="confirm-subtitle">
Your order has been placed successfully  </p>

        <p class="order-number">
          Order Number:
          <strong>
            #<?php echo $order ? $order['order_id'] : 'N/A'; ?>
          </strong>
        </p>
      </div>
    </section>

    <section class="page-content confirmation-content">
      <div class="container">
        <div class="green-confirm-box">
          <h2>🌿 Your plants are almost home!</h2>
          <?php if ($isPickup) { ?>
            <p>Your order will be prepared for store pickup. No shipping fee was added.</p>
          <?php } else { ?>
            <p>Your order will be delivered soon. Thank you for making your space greener 💚</p>
          <?php } ?>
        </div>

        <div class="confirmation-layout">

          <div class="card confirmation-summary">
            <h3 class="section-title">
              <i class="bi bi-receipt"></i>
              Order Details
            </h3>

            <?php if ($order) { ?>
              <div class="confirm-row">
                <span>Order Status</span>
                <span><?php echo htmlspecialchars($order['order_status']); ?></span>
              </div>

              <div class="confirm-row">
                <span>Order Date</span>
                <span><?php echo htmlspecialchars($order['order_date']); ?></span>
              </div>

              <div class="confirm-row">
                <span>Discount</span>
                <span class="money">
                  <?php echo htmlspecialchars($order['discount']); ?>
                  <span class="currency">
                    <img src="images/icons/sar.png" alt="SAR" class="sar-icon">
                  </span>
                </span>
              </div>
            <?php } else { ?>
              <p>No order data found.</p>
            <?php } ?>
          </div>

          <div class="confirmation-side">
            <div class="card side-card">
              <h3 class="section-title">
                <i class="bi bi-geo-alt"></i>
                <?php echo $isPickup ? 'Pickup Location' : 'Shipping Address'; ?>
              </h3>

              <div class="side-info">
                <?php if ($order && $isPickup) { ?>
                  <p><strong>Store Pickup Selected</strong></p>
                  <p><?php echo htmlspecialchars($pickupLocationText); ?></p>
                  <a class="pickup-map-card" href="<?php echo htmlspecialchars($pickupMapLink); ?>" target="_blank">
                    <i class="bi bi-geo-alt-fill"></i><br>
                    Open location in Google Maps
                  </a>
                  <p style="margin-top:12px;">Phone: <?php echo htmlspecialchars($order['phone']); ?></p>
                <?php } elseif ($order) { ?>
                  <p><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                  <p><?php echo htmlspecialchars($order['city']); ?></p>
                  <p>Phone: <?php echo htmlspecialchars($order['phone']); ?></p>
                <?php } else { ?>
                  <p>No shipping details available.</p>
                <?php } ?>
              </div>
            </div>

            <div class="card side-card">
              <h3 class="section-title">
                <i class="bi bi-credit-card"></i>
                Payment Method
              </h3>

              <div class="side-info">
                <?php if ($order) { ?>
                  <p><?php echo htmlspecialchars($order['payment_method']); ?></p>
                <?php } else { ?>
                  <p>No payment details available.</p>
                <?php } ?>
              </div>
            </div>

            <div class="confirm-actions">
              <?php if ($order) { ?>
                <button type="button" class="btn secondary-btn" onclick="downloadInvoicePDF()">
                  <i class="fas fa-file-pdf"></i> View Invoice
                </button>
              <?php } ?>
              <a href="past-orders.php" class="btn secondary-btn">View Past Orders</a>
              <a href="products.php" class="btn primary">Continue Shopping</a>
            </div>
          </div>

        </div>

        <section class="features-section">
          <div class="features-container">

            <div class="feature-box">
              <i class="bi bi-truck"></i>
              <h3>Flexible Receiving</h3>
              <p>Choose delivery or store pickup based on what suits you best.</p>
            </div>

            <div class="feature-box">
              <i class="bi bi-shield-lock"></i>
              <h3>Secure Checkout</h3>
              <p>Your information is safe and secure.</p>
            </div>

            <div class="feature-box">
              <i class="bi bi-headset"></i>
              <h3>24/7 Support</h3>
              <p>Our support team is available all day.</p>
            </div>

          </div>
        </section>

      </div>
    </section>
  </main>


  <?php if ($order) { ?>
    <!-- Hidden invoice copied from the admin PDF design. It appears only when downloading the PDF. -->
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
            <p>#BH-<?php echo (int)$order['order_id']; ?></p>
          </div>
        </div>

        <div class="pdf-meta">
          <p><strong>Order Date:</strong> <?php echo h($order['order_date']); ?></p>
          <p><strong>Status:</strong> <?php echo h($order['order_status']); ?></p>
        </div>

        <div class="pdf-grid">
          <div class="pdf-box">
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> <?php echo h($customerName); ?></p>
            <p><strong>Email:</strong> <?php echo h($order['user_email'] ?? ''); ?></p>
            <p><strong>Phone:</strong> <?php echo h($order['user_phone'] ?? $order['phone']); ?></p>
          </div>
          <div class="pdf-box">
            <h3>Shipping & Payment</h3>
            <p><strong>Delivery:</strong> <?php echo $isPickup ? 'Store Pickup' : 'Delivery'; ?></p>
            <p><strong>Payment:</strong> <?php echo h($order['payment_method']); ?></p>
            <p><strong>City:</strong> <?php echo h($order['city']); ?></p>
            <p><strong>Address:</strong> <?php echo $isPickup ? h($pickupLocationText) : h($order['shipping_address']); ?></p>
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
            <?php if (count($items) > 0) { ?>
              <?php foreach ($items as $item) {
                $item_total = floatval($item['quantity']) * floatval($item['unit_price']); ?>
                <tr>
                  <td><?php echo h($item['plant_name']); ?></td>
                  <td><?php echo (int)$item['quantity']; ?></td>
                  <td>SAR <?php echo number_format(floatval($item['unit_price']), 2); ?></td>
                  <td>SAR <?php echo number_format($item_total, 2); ?></td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr><td colspan="4">No items found.</td></tr>
            <?php } ?>
          </tbody>
        </table>

        <div class="pdf-summary">
          <p><span>Subtotal</span><strong>SAR <?php echo number_format($subtotal, 2); ?></strong></p>
          <p><span>Shipping</span><strong>Free</strong></p>
          <?php if ($discountValue > 0) { ?>
            <p><span>Discount</span><strong>SAR <?php echo number_format($discountValue, 2); ?></strong></p>
          <?php } ?>
          <p class="total"><span>Total</span><strong>SAR <?php echo number_format($grand_total, 2); ?></strong></p>
        </div>
      </div>
    </div>
  <?php } ?>

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
  function downloadInvoicePDF() {
    var invoice = document.getElementById("pdfInvoice");

    if (!invoice) {
      alert("Invoice content was not found.");
      return;
    }

    invoice.style.display = "block";

    var invoiceContent = invoice.querySelector(".pdf-invoice");

    var options = {
      margin: 0.2,
      filename: "Bloom-House-Invoice-BH-<?php echo (int)$order_id; ?>.pdf",
      image: { type: "jpeg", quality: 1 },
      html2canvas: {
        scale: 3,
        useCORS: true,
        scrollY: 0
      },
      jsPDF: {
        unit: "mm",
        format: "a4",
        orientation: "portrait"
      }
    };

    html2pdf().set(options).from(invoiceContent).save().then(function () {
      invoice.style.display = "none";
    });
  }
</script>
<script src="script.js"></script>
</body>
</html>