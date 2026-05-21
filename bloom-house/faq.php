<?php
// Start session before any HTML output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("db_connect.php");

$userFullName = "Guest";
$userEmail = "Not logged in";
$userInitial = "G";
$isLoggedIn = false;

// If user is logged in, get user information.
// If not logged in, allow viewing FAQ as Guest.
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];

    $userSql = "SELECT first_name, last_name, email FROM user WHERE user_id = ? LIMIT 1";
    $userStmt = $conn->prepare($userSql);

    if ($userStmt) {
        $userStmt->bind_param("i", $user_id);
        $userStmt->execute();
        $userResult = $userStmt->get_result();

        if ($userResult && $userResult->num_rows > 0) {
            $user = $userResult->fetch_assoc();
            $userFullName = trim($user['first_name'] . " " . $user['last_name']);
            $userEmail = $user['email'];
            $userInitial = strtoupper(substr($user['first_name'], 0, 1));
            $isLoggedIn = true;
        }

        $userStmt->close();
    }
}

// Get FAQs from the database
$sql = "SELECT faq_id, question, answer FROM faqs ORDER BY faq_id ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | FAQ’s</title>
    <link rel="stylesheet" href="css/products-styles.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/faq.css?v=31">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
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
                        <div class="user-avatar"><span><?php echo htmlspecialchars($userInitial); ?></span></div>
                        <div class="user-meta">
                            <div class="user-name"><?php echo htmlspecialchars($userFullName); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($userEmail); ?></div>
                        </div>
                    </div>

                    <div class="sidebar-section-title">ACCOUNT</div>
                    <ul class="sidebar-menu">
                        <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                        <li><a href="favourites.php"><i class="fa-solid fa-heart"></i> Favourite</a></li>
                    </ul>

                    <div class="sidebar-section-title">ORDERS & SUPPORT</div>
                    <ul class="sidebar-menu">
                        <li><a href="past-orders.php"><i class="fa-solid fa-box"></i> Past Orders</a></li>
                        <li><a href="faq.php" class="active"><i class="fa-solid fa-circle-question"></i> FAQ</a></li>
                        <li>
                            <a href="logout.php" class="logout-link">
                                <i class="fa-solid fa-right-from-bracket"></i> Logout
                            </a>
                        </li>
                    </ul>
                </aside>

                <section class="profile-content-card">
                    <nav class="breadcrumb">
                        <a href="profile.php">Profile</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>FAQ</span>
                    </nav>

                    <div class="page-title">
                        <h1>FAQ</h1>
                        <p class="faq-description">Find quick answers about orders, delivery, payments, and plant care.
                        </p>

                        <div class="faq-search">
                            <input type="text" id="faqSearch" placeholder="Search questions...">
                        </div>
                    </div>

                    <?php
                    $categories = [
                        "General Questions" => [],
                        "Orders & Delivery" => [],
                        "Payment" => [],
                        "Plant Care" => [],
                        "Returns & Refunds" => []
                    ];

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $questionText = strtolower($row['question']);

                            if (
                                strpos($questionText, 'deliver') !== false ||
                                strpos($questionText, 'delivery') !== false ||
                                strpos($questionText, 'order') !== false ||
                                strpos($questionText, 'track') !== false ||
                                strpos($questionText, 'shipping') !== false
                            ) {
                                $categories["Orders & Delivery"][] = $row;
                            } elseif (
                                strpos($questionText, 'payment') !== false ||
                                strpos($questionText, 'pay') !== false ||
                                strpos($questionText, 'secure') !== false ||
                                strpos($questionText, 'cash') !== false ||
                                strpos($questionText, 'apple') !== false ||
                                strpos($questionText, 'card') !== false
                            ) {
                                $categories["Payment"][] = $row;
                            } elseif (
                                strpos($questionText, 'care') !== false ||
                                strpos($questionText, 'water') !== false ||
                                strpos($questionText, 'sunlight') !== false ||
                                strpos($questionText, 'plant arrives damaged') !== false ||
                                strpos($questionText, 'damaged') !== false
                            ) {
                                $categories["Plant Care"][] = $row;
                            } elseif (
                                strpos($questionText, 'return') !== false ||
                                strpos($questionText, 'refund') !== false
                            ) {
                                $categories["Returns & Refunds"][] = $row;
                            } else {
                                $categories["General Questions"][] = $row;
                            }
                        }

                        $count = 1;
                        $hasFaqs = false;

                        foreach ($categories as $categoryName => $items) {
                            if (count($items) > 0) {
                                $hasFaqs = true;
                                ?>
                                <div class="faq-category-block">
                                    <h2 class="faq-category">
                                        <?php echo $categoryName; ?>
                                    </h2>

                                    <?php foreach ($items as $row) { ?>
                                        <div class="faq-item">
                                            <input type="checkbox" class="faq-checkbox" id="faq<?php echo $count; ?>">
                                            <label class="faq-question" for="faq<?php echo $count; ?>">
                                                <span class="question-text"><?php echo htmlspecialchars($row['question']); ?></span>
                                                <span class="toggle-icon">+</span>
                                            </label>
                                            <div class="faq-answer">
                                                <div class="answer-content">
                                                    <p><?php echo htmlspecialchars($row['answer']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        $count++;
                                    } ?>
                                </div>
                                <?php
                            }
                        }

                        if (!$hasFaqs) {
                            echo "<p>No FAQs available right now.</p>";
                        }
                    } else {
                        echo "<p>No FAQs available right now.</p>";
                    }
                    ?>

                    <div class="no-results" id="noResults">
                        <h3>No FAQ matches your search.</h3>
                        <p>Please contact us if you need help.</p>
                        <a href="contact.php" class="contact-btn">Contact Support</a>
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

            <div class="footer-col">
                <h3>Customer Support</h3>
                <ul>
                    <li><a href="faq.php">FAQ’s</a></li>
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

    <script>
        const searchInput = document.getElementById('faqSearch');
        const faqItems = document.querySelectorAll('.faq-item');
        const categoryBlocks = document.querySelectorAll('.faq-category-block');
        const noResults = document.getElementById('noResults');

        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                let found = false;
                const value = this.value.toLowerCase().trim();

                faqItems.forEach(item => {
                    const text = item.innerText.toLowerCase();

                    if (text.includes(value)) {
                        item.style.display = 'block';
                        found = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                categoryBlocks.forEach(block => {
                    const visibleItems = block.querySelectorAll('.faq-item[style*="display: block"], .faq-item:not([style])');
                    let hasVisible = false;

                    block.querySelectorAll('.faq-item').forEach(item => {
                        if (item.style.display !== 'none') {
                            hasVisible = true;
                        }
                    });

                    block.style.display = hasVisible ? 'block' : 'none';
                });

                noResults.style.display = found ? 'none' : 'block';
            });
        }
    </script>

</body>

</html>