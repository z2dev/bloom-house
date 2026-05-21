<?php
// Start the session so the page can know if the user is logged in.
session_start();

// Connect this page with the Bloom House database.
require_once 'db_connect.php';

// Prepare variables used to show feedback messages on the page.
$success = '';
$error = '';
$userFullName = '';
$userMessages = [];

// Get the logged-in user's full name from the database.
if (isset($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];

    $userStmt = $conn->prepare("SELECT first_name, last_name FROM user WHERE user_id = ?");
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult && $userResult->num_rows === 1) {
        $userData = $userResult->fetch_assoc();
        $userFullName = trim($userData['first_name'] . ' ' . $userData['last_name']);
    }
}

// Insert a new contact message when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = isset($_SESSION['user_id']) && $userFullName !== ''
        ? $userFullName
        : trim($_POST['full_name'] ?? '');

    $subject = trim($_POST['subject'] ?? '');
    $messageBody = trim($_POST['message_body'] ?? '');

    // Server-side validation before saving the message.
    if ($fullName === '' || $subject === '' || $messageBody === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($messageBody) < 10) {
        $error = 'Message is too short. Please write at least 10 characters.';
    } else {
        $status = 'Unread';
        $createdAt = date('Y-m-d H:i:s');

        $insertStmt = $conn->prepare("INSERT INTO contact_messages (full_name, subject, message_body, status, created_at) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->bind_param('sssss', $fullName, $subject, $messageBody, $status, $createdAt);

        if ($insertStmt->execute()) {
            $success = 'Your message has been sent successfully! We will get back to you soon.';
            $_POST = [];
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

// Display only the logged-in user's sent messages and admin replies.
if (isset($_SESSION['user_id']) && $userFullName !== '') {
    $messagesStmt = $conn->prepare("SELECT message_id, subject, message_body, status, created_at, admin_reply, replied_at FROM contact_messages WHERE full_name = ? ORDER BY created_at DESC");
    $messagesStmt->bind_param('s', $userFullName);
    $messagesStmt->execute();
    $messagesResult = $messagesStmt->get_result();

    while ($row = $messagesResult->fetch_assoc()) {
        $userMessages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom House | Contact Us</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/contact.css">
</head>

<body>

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
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="contact.php" class="nav-link active">Contact</a>
            </nav>
            <div class="header-actions">
                <a href="search.php" class="user-icon"><i class="fas fa-search"></i></a>
                <a href="cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
                <a href="profile.php" class="user-icon"><i class="fas fa-user"></i></a>
            </div>
        </div>
    </header>

    <!-- Page introduction section. -->
    <section class="page-hero">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
    </section>

    <!-- Contact information and message form. -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-grid">

                <!-- Store contact information card. -->
                <div class="info-card">
                    <h2>Get in Touch</h2>
                    <p class="subtitle">You can contact Bloom House using any of the details below.</p>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-text">
                            <strong>Email</strong>
                            <span>support@bloomhouse.com</span>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone"></i></div>
                        <div class="info-text">
                            <strong>Phone</strong>
                            <span>+966 123 456 789</span>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="info-text">
                            <strong>Store Location</strong>
                            <span>AlGadier Plantation, Sanabis, Tarout 32615</span>
                            <a class="map-link"
                                href="https://www.google.com/maps?q=H3MF+M3+AlGadier+Plantation,+Sanabis,+Tarout+32615"
                                target="_blank">Open in Google Maps</a>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <div class="info-text">
                            <strong>Working Hours</strong>
                            <span>Sat–Thu: 7AM–9PM<br>Fri: 11AM–10PM</span>
                        </div>
                    </div>

                    <hr class="divider">

                    <div class="social-row">
                        <a href="https://wa.me/966563650663" target="_blank" class="social-pill"><i
                                class="bi bi-whatsapp"></i> WhatsApp</a>
                        <a href="https://www.instagram.com/alghadir_443/" target="_blank" class="social-pill"><i
                                class="bi bi-instagram"></i> Instagram</a>
                        <a href="https://www.snapchat.com/@aboreza" target="_blank" class="social-pill"><i
                                class="bi bi-snapchat"></i> Snapchat</a>
                    </div>
                </div>

                <!-- Contact form card. -->
                <div class="form-card">
                    <h2>Send a Message</h2>
                    <p class="subtitle">Fill in the form and our team will reply when possible.</p>

                    <?php if ($success): ?>
                        <div class="success-msg show"><i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="error-msg"><i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="contact.php" id="contactForm" novalidate>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" placeholder="Your full name"
                                    value="<?php echo htmlspecialchars($userFullName !== '' ? $userFullName : ($_POST['full_name'] ?? '')); ?>"
                                    maxlength="100" <?php echo isset($_SESSION['user_id']) ? 'readonly' : ''; ?>
                                    required>
                                <small class="field-error" id="nameError"></small>
                            </div>

                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" placeholder="What is this about?"
                                    value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" maxlength="150"
                                    required>
                                <small class="field-error" id="subjectError"></small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message_body">Message</label>
                            <textarea id="message_body" name="message_body" placeholder="Write your message here..."
                                maxlength="1000"
                                required><?php echo htmlspecialchars($_POST['message_body'] ?? ''); ?></textarea>
                            <div class="char-count"><span id="charCount">0</span> / 1000</div>
                            <small class="field-error" id="messageError"></small>
                        </div>

                        <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Send
                            Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Logged-in user messages and admin replies. -->
    <section class="user-messages-section">
        <div class="container">
            <div class="messages-card">
                <h2>My Sent Messages</h2>
                <p class="subtitle">Here you can see your contact messages and whether the admin replied.</p>

                <?php if (!isset($_SESSION['user_id'])): ?>
                    <p class="empty-msg">Please <a href="login.php">login</a> to view your sent messages.</p>
                <?php elseif (empty($userMessages)): ?>
                    <p class="empty-msg">You have not sent any messages yet.</p>
                <?php else: ?>
                    <div class="messages-table-wrap">
                        <table class="user-messages-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Sent Date</th>
                                    <th>Reply Status</th>
                                    <th>Admin Reply</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userMessages as $message): ?>
                                    <?php $hasReply = !empty($message['admin_reply']); ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($message['message_body'])); ?></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <?php if ($hasReply): ?>
                                                <span class="reply-badge replied">Replied</span>
                                            <?php else: ?>
                                                <span class="reply-badge not-replied">Not replied yet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hasReply): ?>
                                                <div class="reply-box">
                                                    <?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?>
                                                    <?php if (!empty($message['replied_at'])): ?>
                                                        <span><?php echo date('d M Y, h:i A', strtotime($message['replied_at'])); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="muted-text">No reply yet.</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

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
        // Count the message characters while the user is typing.
        const messageInput = document.getElementById('message_body');
        const charCount = document.getElementById('charCount');

        function updateCount() {
            charCount.textContent = messageInput.value.length;
        }

        messageInput.addEventListener('input', updateCount);
        updateCount();

        // Validate the contact form on the client side before sending it to PHP.
        document.getElementById('contactForm').addEventListener('submit', function (event) {
            let isValid = true;

            const fullName = document.getElementById('full_name');
            const subject = document.getElementById('subject');
            const message = document.getElementById('message_body');

            document.getElementById('nameError').textContent = '';
            document.getElementById('subjectError').textContent = '';
            document.getElementById('messageError').textContent = '';

            if (fullName.value.trim() === '') {
                document.getElementById('nameError').textContent = 'Full name is required.';
                isValid = false;
            }

            if (subject.value.trim() === '') {
                document.getElementById('subjectError').textContent = 'Subject is required.';
                isValid = false;
            }

            if (message.value.trim().length < 10) {
                document.getElementById('messageError').textContent = 'Message must be at least 10 characters.';
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
            }
        });

    </script>

</body>

</html>