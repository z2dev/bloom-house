<?php
// Product details page
// Author: Zahra Mohsen

// Start the session so the page can use cart data and logged-in user data.
session_start();

// Connect this page to the database file so we can retrieve plants, reviews, and favourites.
include("db_connect.php");
// This helper function protects printed data from HTML injection before displaying it on the page.
function clean($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
// This helper function converts a numeric rating into filled and empty star symbols.
function starsText($rating)
{
    $rating = (int) $rating;
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= "★";
        } else {
            $stars .= "☆";
        }
    }
    return $stars;
}
// This helper function changes the review date into a friendly format like "Just now" or "2 days ago".
function timeAgo($dateTime)
{
    $time = strtotime($dateTime);
    if (!$time) {
        return "Recently";
    }

    $diff = time() - $time;
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . " month" . ($months > 1 ? "s" : "") . " ago";
    } else {
        $years = floor($diff / 31536000);
        return $years . " year" . ($years > 1 ? "s" : "") . " ago";
    }
}

// Check that the product ID exists in the URL and is a valid number before using it.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product ID.");
}

// Convert the product ID from the URL into an integer for safer database use.
$plant_id = (int) $_GET['id'];

// Prepare an array to store the recently viewed product IDs using cookies.
$recentlyViewed = [];

// If the recently viewed cookie already exists, decode it so we can update the list.
if (isset($_COOKIE['recently_viewed'])) {
    $recentlyViewed = json_decode($_COOKIE['recently_viewed'], true);
}

// Add the current product to the beginning of the recently viewed list.
if (!in_array($plant_id, $recentlyViewed)) {
    array_unshift($recentlyViewed, $plant_id);
} else {
    // If the product already exists in the list, move it to the first position.
    $recentlyViewed = array_diff($recentlyViewed, [$plant_id]);
    array_unshift($recentlyViewed, $plant_id);
}

// Keep only the latest 4 viewed products so the section stays short and clean.
$recentlyViewed = array_slice($recentlyViewed, 0, 4);

// Save the updated recently viewed list in a cookie for 30 days.
setcookie(
    'recently_viewed',
    json_encode($recentlyViewed),
    time() + (86400 * 30),
    '/'
);

// Handle the Add to Cart request that comes from JavaScript using AJAX without refreshing the page.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_cart') {
    // Tell the browser that this PHP response will be returned as JSON.
    header('Content-Type: application/json');

    // Create the cart session array if this is the first product added by the user.
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Get the plant ID and quantity sent from the Add to Cart form.
    // Get the plant ID from the favourite button and get the current user ID from the session.
    $postPlantId = isset($_POST['plant_id']) ? (int) $_POST['plant_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

    // Stop the request if the plant ID is missing or invalid.
    if ($postPlantId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid product.'));
        exit();
    }

    // Make sure the quantity is at least 1 before adding it to the cart.
    if ($quantity < 1) {
        $quantity = 1;
    }

    // Check the product stock from the database before adding it to the cart.
    $checkSql = "SELECT stock_quantity FROM plants WHERE plant_id = $postPlantId";
    $checkResult = mysqli_query($conn, $checkSql);

    // If the product exists, compare the requested quantity with the available stock.
    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $checkRow = mysqli_fetch_assoc($checkResult);
        $stock = (int) $checkRow['stock_quantity'];
        $currentCartQty = isset($_SESSION['cart'][$postPlantId]) ? (int) $_SESSION['cart'][$postPlantId] : 0;

        // If there is no stock, return an error message to JavaScript.
        if ($stock <= 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Sorry, this product is out of stock.'));
            exit();
        }

        // Add the quantity to the cart only if the total cart quantity does not exceed stock.
        if (($currentCartQty + $quantity) <= $stock) {
            $_SESSION['cart'][$postPlantId] = $currentCartQty + $quantity;
            echo json_encode(array('status' => 'success', 'message' => 'Product added to cart successfully.'));
            exit();
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Sorry, the required quantity is not available.'));
            exit();
        }
    }

    echo json_encode(array('status' => 'error', 'message' => 'Product not found.'));
    exit();
}

// Handle the Add to Favourites request that comes from JavaScript using AJAX.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_favourite') {
    header('Content-Type: application/json');

    // The user must be logged in before adding a plant to favourites.
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array('status' => 'not_logged_in', 'message' => 'Please login first to add this plant to favourites.'));
        exit();
    }

    $postPlantId = isset($_POST['plant_id']) ? (int) $_POST['plant_id'] : 0;
    $user_id = (int) $_SESSION['user_id'];

    if ($postPlantId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid product.'));
        exit();
    }

    // Check if this plant is already saved in the user's favourites.
    $checkFavSql = "SELECT favourite_id FROM favourite WHERE user_id = $user_id AND plant_id = $postPlantId";
    $checkFavResult = mysqli_query($conn, $checkFavSql);

    if ($checkFavResult && mysqli_num_rows($checkFavResult) > 0) {
        echo json_encode(array('status' => 'exists', 'message' => 'This plant is already in your favourites.'));
        exit();
    }

    // Insert the plant into the favourites table with the current date and time.
    $insertFavSql = "INSERT INTO favourite (user_id, plant_id, date_added) VALUES ($user_id, $postPlantId, NOW())";
    if (mysqli_query($conn, $insertFavSql)) {
        echo json_encode(array('status' => 'added', 'message' => 'Plant added to favourites successfully.'));
        exit();
    }

    echo json_encode(array('status' => 'error', 'message' => 'Could not add to favourites.'));
    exit();
}

// Retrieve the selected plant details from the database using the product ID.
$sql = "SELECT * FROM plants WHERE plant_id = $plant_id";
$result = mysqli_query($conn, $sql);

// Stop the page if the product ID does not match any plant in the database.
if (!$result || mysqli_num_rows($result) == 0) {
    die("Product not found.");
}

// Store the selected plant record in an array so it can be displayed in the HTML.
$product = mysqli_fetch_assoc($result);

// Prepare the product height text and support different possible height column names.
$height = "Not specified";
if (isset($product['height_cm']) && $product['height_cm'] != '') {
    $height = $product['height_cm'] . " cm";
} elseif (isset($product['plant_height']) && $product['plant_height'] != '') {
    $height = $product['plant_height'];
} elseif (isset($product['height']) && $product['height'] != '') {
    $height = $product['height'];
}

// Prepare the humidity text based on the humidity value stored in the database.
$humidity = "Moderate humidity";
if (isset($product['humidity_requirement']) && $product['humidity_requirement'] != '') {
    if ($product['humidity_requirement'] == 'High') {
        $humidity = "High humidity";
    } elseif ($product['humidity_requirement'] == 'Low') {
        $humidity = "Low humidity";
    } else {
        $humidity = "Moderate humidity";
    }
}

// Check whether the current plant is already in the logged-in user's favourites.
$isFavourite = false;
if (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
    $favSql = "SELECT favourite_id FROM favourite WHERE user_id = $user_id AND plant_id = $plant_id";
    $favResult = mysqli_query($conn, $favSql);
    if ($favResult && mysqli_num_rows($favResult) > 0) {
        $isFavourite = true;
    }
}

// Get products from the same category to display them as related products.
$category = mysqli_real_escape_string($conn, $product['category']);
$relatedSql = "SELECT plant_id, plant_name, price, image FROM plants WHERE category = '$category' AND plant_id != $plant_id LIMIT 4";
$relatedProducts = mysqli_query($conn, $relatedSql);

// Prepare review variables to store reviews, review count, average rating, and star totals.
$reviewRows = array();
$reviewCount = 0;
$averageRating = 0;
$ratingCounts = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0);

// Retrieve all reviews for this plant with the reviewer's first and last name.
$reviewsSql = "SELECT r.*, u.first_name, u.last_name
               FROM reviews r
               INNER JOIN user u ON r.user_id = u.user_id
               WHERE r.plant_id = $plant_id
               ORDER BY r.created_at DESC";
$reviewsResult = mysqli_query($conn, $reviewsSql);

// Loop through the reviews to calculate the average rating and rating percentages.
if ($reviewsResult) {
    $totalRating = 0;
    while ($review = mysqli_fetch_assoc($reviewsResult)) {
        $reviewRows[] = $review;
        $rating = (int) $review['rating'];
        if ($rating < 1) {
            $rating = 1;
        }
        if ($rating > 5) {
            $rating = 5;
        }
        $ratingCounts[$rating]++;
        $totalRating += $rating;
        $reviewCount++;
    }

    if ($reviewCount > 0) {
        $averageRating = round($totalRating / $reviewCount, 1);
    }
}

// Prepare the final rating text and stars that will appear in the product page.
$displayRating = $reviewCount > 0 ? $averageRating : "0.0";
$displayStars = starsText(round($averageRating));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bloom House | <?php echo clean($product['plant_name']); ?></title>
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/product-details.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
</head>

<body>
    <!-- Show the signup discount bar only for guests who are not logged in. -->
    <?php if (!isset($_SESSION['user_id'])) { ?>
        <div class="promo-bar">
            <div class="container promo-content">
                <p class="promo-text">
                    Sign up and get <strong>15% OFF</strong> your first plant order
                    <a href="register.php" class="promo-link">Sign up now</a>
                </p>
            </div>
        </div>
    <?php } ?>

    <header class="header">
        <div class="header-inner">
            <a class="logo" href="index.php">🌱Bloom House</a>

            <!-- Navigation -->
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

    <!-- Toast message that appears after adding an item to cart or favourites. -->
    <div id="cartToast" class="cart-toast">
        <div class="cart-toast-icon"><i class="bi bi-check2"></i></div>
        <div class="cart-toast-content">
            <strong id="cartToastTitle">Added to Cart</strong>
            <p id="cartToastMessage">Product added successfully.</p>
        </div>
        <div class="cart-toast-actions">
            <button type="button" id="continueShoppingBtn" class="toast-link">Continue Shopping</button>
            <a href="cart.php" id="toastPrimaryLink" class="toast-link primary">View Cart</a>
        </div>
    </div>

    <!-- Main product details content. -->
    <main class="pd-page">
        <div class="pd-container">

            <!-- Breadcrumb links show the user where they are in the website. -->
            <nav class="breadcrumb">
                <a href="index.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="products.php">Products</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo clean($product['plant_name']); ?></span>
            </nav>

            <section class="pd-hero">

                <!-- Product image gallery. -->
                <div class="pd-gallery">
                    <div class="pd-main-img">
                        <img src="images/plants/<?php echo clean($product['image']); ?>"
                            alt="<?php echo clean($product['plant_name']); ?>">
                    </div>

                    <div class="pd-thumbs">
                        <a class="pd-thumb" href="#">
                            <img src="images/plants/<?php echo clean($product['image']); ?>"
                                alt="<?php echo clean($product['plant_name']); ?>">
                        </a>
                        <a class="pd-thumb" href="#">
                            <img src="images/plants/<?php echo clean($product['image']); ?>"
                                alt="<?php echo clean($product['plant_name']); ?>">
                        </a>
                        <a class="pd-thumb" href="#">
                            <img src="images/plants/<?php echo clean($product['image']); ?>"
                                alt="<?php echo clean($product['plant_name']); ?>">
                        </a>
                    </div>
                </div>

                <!-- Product information, price, rating, quantity, cart, and favourite button. -->
                <aside class="pd-info">

                    <!-- Product status and category badges. -->
                    <div class="pd-head-row">
                        <div class="pd-badge-row">
                            <span
                                class="pd-badge pd-available"><?php echo clean($product['availability_status']); ?></span>
                            <span class="pd-badge soft"><?php echo clean($product['category']); ?></span>
                        </div>
                    </div>

                    <!-- Product name from the database. -->
                    <h1 class="pd-title"><?php echo clean($product['plant_name']); ?></h1>

                    <!-- Product rating summary calculated from reviews. -->
                    <div class="pd-rating">
                        <span class="stars"><?php echo $displayStars; ?></span>
                        <span class="rate"><?php echo clean($displayRating); ?></span>
                        <span class="muted">(<?php echo $reviewCount; ?> reviews)</span>
                    </div>

                    <!-- Product price and height information. -->
                    <div class="pd-price-row">
                        <div class="pd-price">
                            <span class="amount"><?php echo clean($product['price']); ?></span>
                            <img src="images/icons/sar.png" alt="SAR" class="sar-icon">
                        </div>
                        <span class="pd-dot">•</span>
                        <span class="pd-height"><?php echo clean($height); ?></span>
                    </div>

                    <!-- Short product description. -->
                    <p class="pd-desc">
                        <?php echo clean($product['description']); ?>
                    </p>

                    <div class="pd-buy-row">

                        <!-- Add to cart form sends plant ID and quantity to PHP using AJAX. -->
                        <form method="POST" action="product-details.php?id=<?php echo $plant_id; ?>"
                            class="pd-cart-form" id="detailsCartForm">
                            <input type="hidden" name="plant_id" value="<?php echo $plant_id; ?>">
                            <input type="hidden" name="action" value="add_cart">

                            <div class="pd-qty">
                                <button type="button" class="qty-chip qty-minus"
                                    aria-label="Decrease quantity">-</button>
                                <input class="qty-input" id="quantityInput" type="number" name="quantity" value="1"
                                    min="1" max="<?php echo (int) $product['stock_quantity']; ?>" aria-label="Quantity">
                                <button type="button" class="qty-chip qty-plus"
                                    aria-label="Increase quantity">+</button>
                            </div>

                            <button class="btn-cart pd-cart-btn" type="submit" <?php if ((int) $product['stock_quantity'] <= 0) {
                                echo 'disabled';
                            } ?>>
                                <span class="btn-wrapper">
                                    <span class="btn-text">Add to Cart</span>
                                    <span class="btn-icon"><i class="bi bi-cart2"></i></span>
                                </span>
                            </button>
                        </form>

                        <!-- Favourite button changes heart style depending on whether the plant is already saved. -->
                        <button type="button" class="pd-fav-btn <?php echo $isFavourite ? 'active' : ''; ?>"
                            title="Add to favourites" id="favButton" data-plant-id="<?php echo $plant_id; ?>">
                            <i class="bi <?php echo $isFavourite ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                        </button>
                    </div>

                    <div class="pd-divider"></div>

                    <!-- Small service badges shown under the product actions. -->
                    <div class="pd-pills">
                        <div class="pill"><i class="bi bi-truck"></i> Fast Delivery</div>
                        <div class="pill"><i class="bi bi-shield-check"></i> Safe Packaging</div>
                        <div class="pill"><i class="bi bi-arrow-repeat"></i> Easy Return</div>
                    </div>

                    <!-- Help button opens a simple pop-up window for plant choosing support. -->
                    <button type="button" class="help-btn" id="helpOpen">
                        Need help?
                    </button>

                    <!-- Help modal. -->
                    <div id="helpModal" class="help-modal" aria-hidden="true">
                        <div class="help-modal-box" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
                            <button type="button" class="help-close" id="helpClose">&times;</button>
                            <div class="help-icon">
                                <i class="bi bi-question-circle"></i>
                            </div>
                            <h3 id="helpTitle">Need help choosing?</h3>
                            <p>
                                If you are not sure whether this plant is suitable for your space, check the care
                                information below
                                or contact us. We can help you choose a plant based on light, watering, and room
                                conditions.
                            </p>
                            <div class="help-actions">
                                <a href="contact.php" class="toast-link primary">Contact Us</a>
                                <button type="button" class="toast-link" id="helpOk">Got it</button>
                            </div>
                        </div>
                    </div>

                </aside>
            </section>

            <!-- Product tabs section for description, care instructions, and reviews. -->
            <section class="pd-tabs">

                <!-- Hidden radio inputs control which tab content is shown. -->
                <input class="pd-tab-radio" type="radio" name="pdtab" id="tab-desc" checked>
                <input class="pd-tab-radio" type="radio" name="pdtab" id="tab-care">
                <input class="pd-tab-radio" type="radio" name="pdtab" id="tab-rev">

                <!-- Tab labels that use to switch between sections. -->
                <div class="pd-tab-head">
                    <label class="pd-tab-btn" for="tab-desc">Description</label>
                    <label class="pd-tab-btn" for="tab-care">How to care</label>
                    <label class="pd-tab-btn" for="tab-rev">Review</label>
                </div>

                <div class="pd-tab-panels">

                    <!-- Description tab displays product details and main specifications. -->
                    <div class="pd-panel panel-desc">
                        <div class="pd-panel-card">
                            <h3>About this plant</h3>
                            <p><?php echo clean($product['description']); ?></p>

                            <div class="pd-specs">
                                <div class="spec">
                                    <span class="k">Price</span>
                                    <span class="v"><?php echo clean($product['price']); ?> SAR</span>
                                </div>

                                <div class="spec">
                                    <span class="k">Height</span>
                                    <span class="v"><?php echo clean($height); ?></span>
                                </div>

                                <div class="spec">
                                    <span class="k">Availability</span>
                                    <span
                                        class="v availability-text"><?php echo clean($product['availability_status']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Care tab displays light, water, and humidity instructions. -->
                    <div class="pd-panel panel-care">
                        <div class="care-grid">

                            <div class="care-card c1">
                                <div class="care-ico"><i class="bi bi-sun"></i></div>
                                <h4>Light</h4>
                                <p><?php echo clean($product['light_requirement']); ?></p>
                            </div>

                            <div class="care-card c2">
                                <div class="care-ico"><i class="bi bi-droplet"></i></div>
                                <h4>Water</h4>
                                <p><?php echo clean($product['watering_instruction']); ?></p>
                            </div>

                            <div class="care-card c3">
                                <div class="care-ico"><i class="bi bi-moisture"></i></div>
                                <h4>Humidity</h4>
                                <p><?php echo clean($humidity); ?></p>
                            </div>

                        </div>
                    </div>

                    <!-- Review tab displays rating summary and customer reviews. -->
                    <div class="pd-panel panel-rev">
                        <div class="rev-layout">
                            <div class="rev-summary">
                                <div class="rev-score">
                                    <div class="num"><?php echo clean($displayRating); ?></div>
                                    <div class="outof">out of 5</div>
                                </div>

                                <div class="rev-stars-big"><?php echo $displayStars; ?></div>
                                <div class="rev-count">(<?php echo $reviewCount; ?> Reviews)</div>
                            </div>

                            <!-- Rating bars show the percentage of each star level. -->
                            <div class="rev-bars">
                                <?php for ($i = 5; $i >= 1; $i--) {
                                    $percent = $reviewCount > 0 ? round(($ratingCounts[$i] / $reviewCount) * 100) : 0;
                                    ?>
                                    <div class="bar-row">
                                        <span class="label"><?php echo $i; ?> Star</span>
                                        <div class="bar"><span style="width: <?php echo $percent; ?>%"></span></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Review list heading and total number of reviews. -->
                        <div class="rev-list-head">
                            <div class="left">
                                <h3>Review List</h3>
                                <p>Showing <?php echo $reviewCount; ?> review<?php echo $reviewCount == 1 ? '' : 's'; ?>
                                    for this plant</p>
                            </div>
                        </div>

                        <!-- Customer review cards generated from the reviews table. -->
                        <div class="rev-items">
                            <?php if ($reviewCount > 0) { ?>
                                <?php foreach ($reviewRows as $review) {
                                    $firstName = $review['first_name'];
                                    $lastName = $review['last_name'];
                                    $initial = strtoupper(substr($firstName, 0, 1));
                                    $rating = (int) $review['rating'];
                                    ?>
                                    <article class="rev-item">
                                        <div class="rev-avatar"><?php echo clean($initial); ?></div>
                                        <div class="rev-content">
                                            <div class="rev-top">
                                                <div class="who">
                                                    <span class="name"><?php echo clean($firstName . " " . $lastName); ?></span>
                                                    <span class="verified">(Verified)</span>
                                                </div>
                                                <div class="when"><?php echo clean(timeAgo($review['created_at'])); ?></div>
                                            </div>

                                            <h4 class="rev-title"><?php echo clean($review['review_title']); ?></h4>
                                            <p class="rev-text"><?php echo clean($review['review_text']); ?></p>

                                            <div class="rev-stars"><?php echo starsText($rating); ?> <span
                                                    class="rate"><?php echo number_format($rating, 1); ?></span></div>
                                        </div>
                                    </article>
                                <?php } ?>
                            <?php } else { ?>
                                <p class="empty-reviews">No reviews yet for this plant.</p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Related products section displays products from the same category. -->
            <section class="pd-related">
                <h2 class="pd-related-title">Explore Related Products</h2>
                <div class="pd-related-grid">
                    <?php if ($relatedProducts && mysqli_num_rows($relatedProducts) > 0) { ?>
                        <?php while ($related = mysqli_fetch_assoc($relatedProducts)) { ?>
                            <article class="product-card">
                                <a class="product-media"
                                    href="product-details.php?id=<?php echo (int) $related['plant_id']; ?>">
                                    <img src="images/plants/<?php echo clean($related['image']); ?>"
                                        alt="<?php echo clean($related['plant_name']); ?>">
                                </a>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo clean($related['plant_name']); ?></h3>
                                    <div class="product-price">
                                        <?php echo clean($related['price']); ?>
                                        <span class="currency">
                                            <img src="images/icons/sar.png" alt="SAR" class="sar-icon">
                                        </span>
                                    </div>
                                </div>
                            </article>
                        <?php } ?>
                    <?php } else { ?>
                        <p>No related products found.</p>
                    <?php } ?>
                </div>
            </section>

            <!-- Recently viewed section uses the cookie created at the top of the page. -->
            <?php
            if (isset($_COOKIE['recently_viewed'])) {

                $recentlyViewed = json_decode($_COOKIE['recently_viewed'], true);
                $recentlyViewed = array_diff($recentlyViewed, [$plant_id]);

                if (!empty($recentlyViewed)) {

                    $ids = implode(',', array_map('intval', $recentlyViewed));

                    $recentQuery = mysqli_query($conn, "
            SELECT plant_id, plant_name, price, image
            FROM plants
            WHERE plant_id IN ($ids)
            LIMIT 4
        ");
                    ?>

                    <section class="pd-related recently-viewed-section">
                        <h2 class="pd-related-title">Recently Viewed</h2>

                        <div class="pd-related-grid">

                            <?php if ($recentQuery && mysqli_num_rows($recentQuery) > 0) { ?>
                                <?php while ($recent = mysqli_fetch_assoc($recentQuery)) { ?>

                                    <article class="product-card">
                                        <a class="product-media" href="product-details.php?id=<?php echo (int) $recent['plant_id']; ?>">
                                            <img src="images/plants/<?php echo clean($recent['image']); ?>"
                                                alt="<?php echo clean($recent['plant_name']); ?>">
                                        </a>

                                        <div class="product-info">
                                            <h3 class="product-name">
                                                <?php echo clean($recent['plant_name']); ?>
                                            </h3>

                                            <div class="product-price">
                                                <?php echo clean($recent['price']); ?>
                                                <span class="currency">
                                                    <img src="images/icons/sar.png" alt="SAR" class="sar-icon">
                                                </span>
                                            </div>
                                        </div>
                                    </article>

                                <?php } ?>
                            <?php } ?>

                        </div>
                    </section>

                    <?php
                }
            }
            ?>

        </div>
    </main>


    <!-- Footer section. -->
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

    <!-- JavaScript file -->
    <script src="js/products.js"></script>

</body>

</html>