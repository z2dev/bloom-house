<?php
// Author: Zahra Mohsen
// This file includes simple comments to explain the main sections.

// Start session for logged-in users
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to the database
include("db_connect.php");
// Function section for reusable code

function safe($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
// Function section for reusable code

function redirect_login_required($redirect)
{
    header("Location: login-required.php?redirect=" . urlencode($redirect));
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    redirect_login_required('past-orders.php');
}

$user_id = (int) $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_POST['order_id']) ? intval($_POST['order_id']) : 0);
$plant_id = isset($_GET['plant_id']) ? intval($_GET['plant_id']) : (isset($_POST['plant_id']) ? intval($_POST['plant_id']) : 0);

if ($order_id <= 0 || $plant_id <= 0) {
    header("Location: past-orders.php");
    exit();
}

// Get current user information
$user_sql = "SELECT first_name, last_name, email FROM user WHERE user_id = ? LIMIT 1";
$user_stmt = $conn->prepare($user_sql);
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

// Get selected ordered plant information
$item_sql = "SELECT o.order_id, o.order_date, o.order_status,
                    oi.quantity, oi.unit_price,
                    p.plant_id, p.plant_name, p.image
             FROM orders o
             INNER JOIN orders_items oi ON o.order_id = oi.order_id
             INNER JOIN plants p ON oi.plant_id = p.plant_id
             WHERE o.order_id = ? AND o.user_id = ? AND p.plant_id = ?
             LIMIT 1";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("iii", $order_id, $user_id, $plant_id);
$item_stmt->execute();
$item = $item_stmt->get_result()->fetch_assoc();
$item_stmt->close();

if (!$item) {
    header("Location: past-orders.php");
    exit();
}

// Check if the user already reviewed this plant
$review_sql = "SELECT review_id, rating, review_title, review_text FROM reviews WHERE user_id = ? AND order_id = ? AND plant_id = ? LIMIT 1";
$review_stmt = $conn->prepare($review_sql);
$review_stmt->bind_param("iii", $user_id, $order_id, $plant_id);
$review_stmt->execute();
$existing_review = $review_stmt->get_result()->fetch_assoc();
$review_stmt->close();

$rating = $existing_review ? (int) $existing_review['rating'] : 0;
$review_title = $existing_review['review_title'] ?? '';
$review_text = $existing_review['review_text'] ?? '';
$message = '';
$error = '';

// Handle review form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $review_title = trim($_POST['review_title'] ?? '');
    $review_text = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = "Please choose a rate from 1 to 5 hearts.";
    } elseif ($review_title === '') {
        $error = "Please write a review title.";
    } elseif ($review_text === '') {
        $error = "Please write your review.";
    } else {
        // Update existing review
        if ($existing_review) {
            $update = $conn->prepare("UPDATE reviews SET rating = ?, review_title = ?, review_text = ?, created_at = NOW() WHERE review_id = ? AND user_id = ?");
            $review_id = (int) $existing_review['review_id'];
            $update->bind_param("issii", $rating, $review_title, $review_text, $review_id, $user_id);
            $update->execute();
            $update->close();
            $message = "Your review has been updated successfully.";
        } else {
            // Insert new review into database
            $insert = $conn->prepare("INSERT INTO reviews (user_id, plant_id, order_id, rating, review_title, review_text, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $insert->bind_param("iiiiss", $user_id, $plant_id, $order_id, $rating, $review_title, $review_text);
            $insert->execute();
            $insert->close();
            $message = "Your review has been submitted successfully.";
        }

        $review_stmt = $conn->prepare($review_sql);
        $review_stmt->bind_param("iii", $user_id, $order_id, $plant_id);
        $review_stmt->execute();
        $existing_review = $review_stmt->get_result()->fetch_assoc();
        $review_stmt->close();
    }
}

$img = !empty($item['image']) ? "images/plants/" . $item['image'] : "images/plants/monstera.jpeg";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Rate Plant</title>
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/past-order-details.css?v=51">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
</head>

<body>
    <!-- Main website header --><!-- Page header section -->

    <header class="header">
        <div class="header-inner">
            <a class="logo" href="index.php">🌱Bloom House</a><!-- Navigation section -->

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
    </header><!-- Main page content -->


    <main class="account-page">
        <div class="container">
            <h1 class="account-main-title">My Account</h1>
            <div class="account-layout">
                <!-- Account sidebar navigation -->
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
                        <li><a href="past-orders.php" class="active"><i class="fa-solid fa-box"></i> Past Orders</a>
                        </li>
                        <li><a href="faq.php"><i class="fa-solid fa-circle-question"></i> FAQ</a></li>
                        <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>
                                Logout</a></li>
                    </ul>
                </aside>

                <section class="order-details-container invoice-card">
                    <nav class="breadcrumb">
                        <a href="past-orders.php">Past Orders</a>
                        <i class="fas fa-chevron-right"></i>
                        <a href="past-order-details.php?order_id=<?php echo safe($order_id); ?>">Order Details</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Rate Plant</span>
                    </nav>

                    <!-- Review page title -->
                    <div class="page-title details-title-block">
                        <div>
                            <h1>Rate Your Plant</h1>
                            <p class="page-description">Share your experience about the plant you ordered.</p>
                        </div>
                    </div>

                    <?php if ($message !== '') { ?>
                        <div class="review-alert success"><i class="fas fa-check-circle"></i> <?php echo safe($message); ?>
                        </div>
                    <?php } ?>
                    <?php if ($error !== '') { ?>
                        <div class="review-alert error"><i class="fas fa-circle-exclamation"></i>
                            <?php echo safe($error); ?></div>
                    <?php } ?>

                    <!-- Review product and form section -->
                    <div class="review-layout">
                        <div class="review-product-card">
                            <div class="review-product-img"><img src="<?php echo safe($img); ?>"
                                    alt="<?php echo safe($item['plant_name']); ?>"></div>
                            <div>
                                <p class="page-label">ORDER #BH-<?php echo safe($order_id); ?></p>
                                <h2><?php echo safe($item['plant_name']); ?></h2>
                                <p class="muted-text">Purchased on
                                    <?php echo date("j M Y", strtotime($item['order_date'])); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Review submission form --><!-- User form section -->

                        <form class="review-form" method="post" onsubmit="return validateReviewForm();">
                            <input type="hidden" name="order_id" value="<?php echo safe($order_id); ?>">
                            <input type="hidden" name="plant_id" value="<?php echo safe($plant_id); ?>">

                            <label class="form-label">Review Title</label>
                            <input type="text" name="review_title" id="reviewTitle" class="review-input" maxlength="150"
                                placeholder="Example: Amazing plant!" value="<?php echo safe($review_title); ?>"
                                required>

                            <label class="form-label">Your Review</label>
                            <textarea name="review_text" id="reviewText" class="review-textarea" rows="6"
                                placeholder="Write your review here..."
                                required><?php echo safe($review_text); ?></textarea>

                            <label class="form-label">Rate</label>
                            <!-- Heart rating system -->
                            <div class="heart-rating" aria-label="Plant rating">
                                <?php for ($i = 5; $i >= 1; $i--) { ?>
                                    <input type="radio" id="heart<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>"
                                        <?php echo ($rating === $i) ? 'checked' : ''; ?> required>
                                    <label for="heart<?php echo $i; ?>" title="<?php echo $i; ?> hearts"><i
                                            class="fas fa-heart"></i></label>
                                <?php } ?>
                            </div>
                            <p class="small-muted">Choose from 1 to 5 hearts.</p>

                            <div class="action-buttons review-actions">
                                <a href="past-order-details.php?order_id=<?php echo safe($order_id); ?>"
                                    class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-heart"></i> Save
                                    Review</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Website footer --><!-- Footer section -->

    <footer class="footer">
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

    <script>
        // Validate review form before submission
// Function section for reusable code
        function validateReviewForm() {
            var title = document.getElementById('reviewTitle').value.trim();
            var text = document.getElementById('reviewText').value.trim();
            var rating = document.querySelector('input[name="rating"]:checked');

            if (title === '' || text === '' || !rating) {
                alert('Please complete the title, review text, and heart rating.');
                return false;
            }
            return true;
        }
    </script>
</body>

</html>