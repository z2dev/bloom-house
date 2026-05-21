<?php
// Products page
// Author: Zahra Mohsen

// Start the session 
session_start();

include("db_connect.php");

// Set the current products page information used for pagination.
$currentPage = 1;
$currentFile = "products.php";

// Set how many products should appear on this page.
$limit = 8;

// Calculate the first product position for the SQL LIMIT/OFFSET query.
$offset = ($currentPage - 1) * $limit;

//  Add to Cart request by JavaScript using POST.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_cart') {
    // Tell the browser that the response will be JSON.
    header('Content-Type: application/json');

    // Create the cart session array if it does not already exist.
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Get the plant ID from the form and convert it to an integer for safer use in the query.
    $plant_id = isset($_POST['plant_id']) ? (int) $_POST['plant_id'] : 0;

    // Each click on Add to Cart adds one item by default.
    $quantity = 1;

    // Stop the process if the product ID is missing or invalid.
    if ($plant_id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid product.'));
        exit();
    }

    // Check the selected plant stock before adding it to the cart.
    $checkSql = "SELECT stock_quantity FROM plants WHERE plant_id = $plant_id";
    $checkResult = mysqli_query($conn, $checkSql);

    // If the plant exists, read its stock quantity from the database result.
    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $checkRow = mysqli_fetch_assoc($checkResult);
        $stock = (int) $checkRow['stock_quantity'];

        // Add the plant only if it is available in stock.
        if ($stock > 0) {
            // If the plant is already in the cart, increase its quantity.
            if (isset($_SESSION['cart'][$plant_id])) {
                $_SESSION['cart'][$plant_id] = $_SESSION['cart'][$plant_id] + $quantity;
            } else {
                // If it is not in the cart yet, add it with quantity 1.
                $_SESSION['cart'][$plant_id] = $quantity;
            }

            // Send a success response to JavaScript so the toast message can appear.
            echo json_encode(array('status' => 'success', 'message' => 'Product added to cart successfully.'));
            exit();
        } else {
            // Send an error response if the product is out of stock.
            echo json_encode(array('status' => 'error', 'message' => 'Sorry, this product is out of stock.'));
            exit();
        }
    }

    // Send an error response if the plant ID was not found in the database.
    echo json_encode(array('status' => 'error', 'message' => 'Product not found.'));
    exit();
}

// Add to Favourite request by JavaScript using POST.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_favourite') {
    // Tell the browser that the response will be JSON.
    header('Content-Type: application/json');

    // Favourites require login, so stop if the user is not logged in.
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array('status' => 'not_logged_in', 'message' => 'Please login first to add this plant to favourites.'));
        exit();
    }

    // Get the selected plant ID and the logged-in user ID.
    $plant_id = isset($_POST['plant_id']) ? (int) $_POST['plant_id'] : 0;
    $user_id = (int) $_SESSION['user_id'];

    // Stop the process if the plant ID is invalid.
    if ($plant_id <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid product.'));
        exit();
    }

    // Check if this plant is already saved in the user's favourites.
    $checkFavSql = "SELECT favourite_id FROM favourite WHERE user_id = $user_id AND plant_id = $plant_id";
    $checkFavResult = mysqli_query($conn, $checkFavSql);

    // Prevent duplicate favourite records for the same plant and user.
    if ($checkFavResult && mysqli_num_rows($checkFavResult) > 0) {
        echo json_encode(array('status' => 'exists', 'message' => 'This plant is already in your favourites.'));
        exit();
    }

    // Insert the selected plant into the favourite table with the current date and time.
    $insertFavSql = "INSERT INTO favourite (user_id, plant_id, date_added) VALUES ($user_id, $plant_id, NOW())";
    if (mysqli_query($conn, $insertFavSql)) {
        echo json_encode(array('status' => 'added', 'message' => 'Plant added to favourites successfully.'));
        exit();
    }

    // Send an error response if the insert query fails.
    echo json_encode(array('status' => 'error', 'message' => 'Could not add to favourites.'));
    exit();
}

// Read filter and sorting values from the URL query string.
$categories = isset($_GET['category']) ? (array) $_GET['category'] : array();
$sizes = isset($_GET['size']) ? (array) $_GET['size'] : array();
$lights = isset($_GET['light']) ? (array) $_GET['light'] : array();
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (int) $_GET['max_price'] : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';

// Define the only values allowed for filters and sorting.
$allowedCategories = array('Indoor', 'Flower', 'Water');
$allowedSizes = array('small', 'medium', 'large');
$allowedLights = array('low', 'medium', 'bright', 'direct');
$allowedSort = array('price_asc', 'price_desc', 'newest');

// Remove any filter value that is not allowed.
$categories = array_values(array_intersect($categories, $allowedCategories));
$sizes = array_values(array_intersect($sizes, $allowedSizes));
$lights = array_values(array_intersect($lights, $allowedLights));

// Make sure the maximum price stays inside the accepted range.
if ($maxPrice !== '' && ($maxPrice < 0 || $maxPrice > 200)) {
    $maxPrice = '';
}

// Reset sorting if the selected sort option is not allowed.
if (!in_array($sort, $allowedSort)) {
    $sort = '';
}

// This array will collect all SQL filter conditions.
$whereParts = array();

// Build category filter conditions based on the selected categories.
if (!empty($categories)) {
    $categoryConditions = array();
    foreach ($categories as $category) {
        // Match indoor category names used in the database.
        if ($category == 'Indoor') {
            $categoryConditions[] = "category IN ('Indoor', 'Indoor Plants')";
            // Match flowering category names used in the database.
        } elseif ($category == 'Flower') {
            $categoryConditions[] = "category IN ('Flower', 'Flowering', 'Flowering Plants')";
            // Match water category names used in the database.
        } elseif ($category == 'Water') {
            $categoryConditions[] = "category IN ('Water', 'Water Plants')";
        }
    }

    // Add the category conditions to the final WHERE parts.
    if (!empty($categoryConditions)) {
        $whereParts[] = "(" . implode(" OR ", $categoryConditions) . ")";
    }
}

// Build size filter conditions using the plant height from the database.
if (!empty($sizes)) {
    $sizeConditions = array();
    foreach ($sizes as $size) {
        // Small plants are below 50 cm.
        if ($size == 'small') {
            $sizeConditions[] = "height_cm < 50";
            // Medium plants are from 50 cm to 100 cm.
        } elseif ($size == 'medium') {
            $sizeConditions[] = "height_cm >= 50 AND height_cm <= 100";
            // Large plants are above 100 cm.
        } elseif ($size == 'large') {
            $sizeConditions[] = "height_cm > 100";
        }
    }

    // Add the size conditions to the final WHERE parts.
    if (!empty($sizeConditions)) {
        $whereParts[] = "(" . implode(" OR ", $sizeConditions) . ")";
    }
}

// Build light requirement filter conditions.
if (!empty($lights)) {
    $lightConditions = array();
    foreach ($lights as $light) {
        // Search for low light plants.
        if ($light == 'low') {
            $lightConditions[] = "light_requirement LIKE '%Low%'";
            // Search for medium light plants.
        } elseif ($light == 'medium') {
            $lightConditions[] = "light_requirement LIKE '%Medium%'";
            // Search for plants that need bright indirect light.
        } elseif ($light == 'bright') {
            $lightConditions[] = "light_requirement LIKE '%Bright indirect%'";
            // Search for plants that can handle bright/direct light.
        } elseif ($light == 'direct') {
            $lightConditions[] = "light_requirement LIKE '%Bright light%'";
        }
    }

    // Add the light conditions to the final WHERE parts.
    if (!empty($lightConditions)) {
        $whereParts[] = "(" . implode(" OR ", $lightConditions) . ")";
    }
}

// Add the price filter if the user selected a maximum price.
if ($maxPrice !== '') {
    $whereParts[] = "price <= $maxPrice";
}

// Combine all filter conditions into one SQL WHERE statement.
$whereSql = '';
if (!empty($whereParts)) {
    $whereSql = " WHERE " . implode(" AND ", $whereParts);
}

// Set the default products order by plant ID.
$orderSql = " ORDER BY plant_id ASC";

// Change the SQL ORDER BY clause based on the selected sorting option.
if ($sort == 'price_asc') {
    $orderSql = " ORDER BY price ASC";
} elseif ($sort == 'price_desc') {
    $orderSql = " ORDER BY price DESC";
} elseif ($sort == 'newest') {
    $orderSql = " ORDER BY plant_id DESC";
}

// Count the total number of plants after applying filters.
$countSql = "SELECT COUNT(*) AS total FROM plants" . $whereSql;
$countResult = mysqli_query($conn, $countSql);
$countRow = mysqli_fetch_assoc($countResult);
$totalPlants = (int) $countRow['total'];

// Calculate the total number of pages.
$totalPages = ceil($totalPlants / $limit);

// Make sure there is at least one page even if no products are found.
if ($totalPages < 1) {
    $totalPages = 1;
}

// If the current page is greater than the total pages, adjust it to the last available page.
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $limit;
}

// Select the products that will be displayed on this page.
$productSql = "SELECT plant_id, plant_name, price, image, category, light_requirement FROM plants" . $whereSql . $orderSql . " LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $productSql);

// Store all selected products in an array to display them later in HTML.
$products = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// Prepare the item range text shown in the filters sidebar.
$startItem = 0;
$endItem = 0;
if ($totalPlants > 0 && !empty($products)) {
    $startItem = $offset + 1;
    $endItem = $offset + count($products);
}

// Create a page link while keeping the current filters in the URL.
function pageLink($fileName)
{
    // Copy the current GET parameters.
    $params = $_GET;

    // Remove action values because they are not needed in pagination links.
    unset($params['action']);
    unset($params['plant_id']);

    // Convert the parameters array into a URL query string.
    $query = http_build_query($params);

    // Return the file name with filters if there are any query parameters.
    if ($query != '') {
        return $fileName . '?' . $query;
    }

    // Return only the file name if there are no filters.
    return $fileName;
}

// Return checked for a checkbox if its value exists in the selected filter array.
function isChecked($array, $value)
{
    return in_array($value, $array) ? 'checked' : '';
}

// Create a favourite link while keeping the current filters in the URL.
function favLink($plantId, $fileName)
{
    // Copy the current GET parameters.
    $params = $_GET;

    // Add favourite action information to the URL.
    $params['action'] = 'fav';
    $params['plant_id'] = $plantId;

    // Return the file name with the new query string.
    return $fileName . '?' . http_build_query($params);
}

// Store the logged-in user's favourite plant IDs to show filled heart icons.
$favouritePlantIds = array();
if (isset($_SESSION['user_id'])) {
    // Get the current logged-in user ID.
    $user_id = (int) $_SESSION['user_id'];

    // Select all plants that this user added to favourites.
    $favListSql = "SELECT plant_id FROM favourite WHERE user_id = $user_id";
    $favListResult = mysqli_query($conn, $favListSql);

    // Add each favourite plant ID to the array.
    if ($favListResult) {
        while ($favRow = mysqli_fetch_assoc($favListResult)) {
            $favouritePlantIds[] = (int) $favRow['plant_id'];
        }
    }
}

// Set the price slider value. If no price is selected, show the maximum value 200.
$priceValue = ($maxPrice !== '') ? $maxPrice : 200;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Products</title>
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">

</head>

<body>
    <!-- Promo bar appears only for visitors who are not logged in. -->
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

            <!-- Navigation section -->
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="products.php" class="nav-link active">Products</a>
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


    <!-- Products page content. -->
    <main class="products-page">
        <div class="products-layout">
            <!-- Filters sidebar lets the user filter products by category, size, light, and price. -->
            <aside class="filters">
                <div class="filters-head">
                    <h2>Filters</h2>
                    <a class="filters-clear" href="products.php">Clear All</a>
                </div>

                <details class="filter-box" open>
                    <summary class="filter-title">Category</summary>
                    <div class="filter-body">
                        <label class="check">
                            <input type="checkbox" class="clear-filter-key" data-key="category" <?php echo empty($categories) ? 'checked' : ''; ?>>
                            <span>All Plants</span>
                        </label>
                        <label class="check"><input type="checkbox" class="product-filter" data-key="category"
                                data-value="Indoor" <?php echo isChecked($categories, 'Indoor'); ?>><span>Indoor
                                Plants</span></label>
                        <label class="check"><input type="checkbox" class="product-filter" data-key="category"
                                data-value="Flower" <?php echo isChecked($categories, 'Flower'); ?>><span>Flowering
                                Plants</span></label>
                        <label class="check"><input type="checkbox" class="product-filter" data-key="category"
                                data-value="Water" <?php echo isChecked($categories, 'Water'); ?>><span>Water
                                Plants</span></label>
                    </div>
                </details>

                <details class="filter-box" open>
                    <summary class="filter-title">Size</summary>
                    <div class="filter-body">
                        <label class="check"><input type="checkbox" class="product-filter" data-key="size"
                                data-value="small" <?php echo isChecked($sizes, 'small'); ?>><span>Small (Under 60
                                cm)</span></label>
                        <label class="check"><input type="checkbox" class="product-filter" data-key="size"
                                data-value="medium" <?php echo isChecked($sizes, 'medium'); ?>><span>Medium (60–120
                                cm)</span></label>
                        <label class="check"><input type="checkbox" class="product-filter" data-key="size"
                                data-value="large" <?php echo isChecked($sizes, 'large'); ?>><span>Large (120+
                                cm)</span></label>
                    </div>
                </details>

                <details class="filter-box" open>
                    <summary class="filter-title">Light Requirement</summary>
                    <div class="filter-body">
                        <label class="check"><input type="checkbox" class="product-filter" data-key="light"
                                data-value="low" <?php echo isChecked($lights, 'low'); ?>><span>Low Light</span></label>
                        <label class="check"><input type="checkbox" class="product-filter" data-key="light"
                                data-value="medium" <?php echo isChecked($lights, 'medium'); ?>><span>Medium
                                Light</span></label>
                        <label class="check"><input type="checkbox" class="product-filter" data-key="light"
                                data-value="bright" <?php echo isChecked($lights, 'bright'); ?>><span>Bright
                                Indirect</span></label>
                        <label class="check"><input type="checkbox" class="product-filter" data-key="light"
                                data-value="direct" <?php echo isChecked($lights, 'direct'); ?>><span>Direct
                                Sun</span></label>
                    </div>
                </details>

                <details class="filter-box" open>
                    <summary class="filter-title">Price Range</summary>
                    <div class="filter-body">
                        <div class="range">
                            <div class="range-row">
                                <span class="range-min">0 SAR</span>
                                <span class="range-max">200 SAR</span>
                            </div>
                            <input class="range-input" type="range" min="0" max="200"
                                value="<?php echo $priceValue; ?>">
                            <p class="range-hint">Up to: <strong class="price-value"><?php echo $priceValue; ?>
                                    SAR</strong></p>
                        </div>
                    </div>
                </details>

                <p class="filters-note">Showing <?php echo $startItem; ?>–<?php echo $endItem; ?> of
                    <?php echo $totalPlants; ?> plants
                </p>
            </aside>

            <!-- Products section displays the page title, sorting options, product cards, and pagination. -->
            <section class="products">
                <header class="products-head">
                    <div class="products-title">
                        <nav class="breadcrumb">
                            <a href="index.php">Home</a>
                            <i class="fas fa-chevron-right"></i>
                            <span>Products</span>
                        </nav>
                        <h1>Explore All Products</h1>
                    </div>

                    <div class="products-sort">
                        <span class="sort-label">Sort by</span>
                        <select class="sort-select">
                            <option value="" <?php echo $sort == '' ? 'selected' : ''; ?>>Popular</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to
                                High</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High
                                to Low</option>
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                        </select>
                    </div>
                </header>

                <!-- Product grid loops through database products and displays each product card. -->
                <div class="products-grid">
                    <?php // Check if there are products to display. ?>
                    <?php if (!empty($products)): ?>
                        <?php // Loop through every product record and show it as a card. ?>
                        <?php foreach ($products as $row): ?>
                            <article class="product-card">
                                <?php // Check if this product is already in the user favourites list. ?>
                                <?php $rowIsFavourite = in_array((int) $row['plant_id'], $favouritePlantIds); ?>
                                <button type="button"
                                    class="fav-btn product-fav-btn <?php echo $rowIsFavourite ? 'active' : ''; ?>"
                                    data-plant-id="<?php echo (int) $row['plant_id']; ?>" title="Add to favourites">
                                    <i class="bi <?php echo $rowIsFavourite ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                </button>

                                <a class="product-media" href="product-details.php?id=<?php echo (int) $row['plant_id']; ?>">
                                    <img src="images/plants/<?php echo htmlspecialchars($row['image']); ?>"
                                        alt="<?php echo htmlspecialchars($row['plant_name']); ?>">
                                </a>

                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($row['plant_name']); ?></h3>
                                    <div class="product-price"><?php echo htmlspecialchars($row['price']); ?> <span
                                            class="currency"><img src="images/icons/sar.png" alt="SAR" class="sar-icon"></span>
                                    </div>

                                    <!-- Add to cart form sends the selected plant ID to PHP. -->
                                    <form method="POST" class="cart-form">
                                        <input type="hidden" name="action" value="add_cart">
                                        <input type="hidden" name="plant_id" value="<?php echo (int) $row['plant_id']; ?>">
                                        <button class="btn-cart" type="submit">
                                            <span class="btn-wrapper">
                                                <span class="btn-text">Add to Cart</span>
                                                <span class="btn-icon"><i class="bi bi-cart2"></i></span>
                                            </span>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-products">No plants found.</p>
                    <?php endif; ?>
                </div>

                <!-- Pagination links move between product pages while keeping filters. -->
                <nav class="pagination">
                    <a class="page-arrow <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>"
                        href="<?php echo $currentPage <= 1 ? '#' : pageLink('products.php'); ?>">&lt;</a>
                    <a class="page <?php echo $currentPage == 1 ? 'active' : ''; ?>"
                        href="<?php echo pageLink('products.php'); ?>">1</a>
                    <?php if ($totalPages >= 2): ?>
                        <a class="page <?php echo $currentPage == 2 ? 'active' : ''; ?>"
                            href="<?php echo pageLink('products2.php'); ?>">2</a>
                    <?php endif; ?>
                    <a class="page-arrow <?php echo ($currentPage >= $totalPages || $totalPages == 1) ? 'disabled' : ''; ?>"
                        href="<?php echo ($currentPage >= $totalPages || $totalPages == 1) ? '#' : pageLink('products2.php'); ?>">&gt;</a>
                </nav>
            </section>
        </div>
    </main>

    <!-- Toast message shown after adding a product to cart or favourites. -->
    <div id="cartToast" class="cart-toast">
        <div class="cart-toast-icon"><i class="bi bi-check2"></i></div>
        <div class="cart-toast-content">
            <strong id="cartToastTitle">Added to Cart</strong>
            <p id="cartToastMessage">Product added to cart successfully.</p>
        </div>
        <div class="cart-toast-actions">
            <button type="button" id="continueShoppingBtn" class="toast-link">Continue Shopping</button>
            <a href="cart.php" id="toastPrimaryLink" class="toast-link primary">View Cart</a>
        </div>
    </div>


    <!-- Footer. -->
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

    <!-- JavaScript file. -->
    <script src="js/products.js"></script>

</body>

</html>