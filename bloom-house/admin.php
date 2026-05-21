<?php
// Shared admin layout for Bloom House admin pages.
// This file prints the sidebar, top header, profile avatar, logout button, and logout confirmation modal.

if (!isset($activePage)) {
    $activePage = '';
}

if (!isset($adminPageTitle)) {
    $adminPageTitle = 'Admin Panel';
}

if (!isset($adminLetter) || $adminLetter === '') {
    $adminLetter = 'A';

    if (isset($conn) && isset($_SESSION['admin_id'])) {
        $adminId = (int) $_SESSION['admin_id'];
        $adminResult = mysqli_query($conn, "SELECT full_name FROM admin WHERE admin_id = $adminId");

        if ($adminResult && $adminRow = mysqli_fetch_assoc($adminResult)) {
            $adminName = trim($adminRow['full_name']);
            if ($adminName !== '') {
                $adminLetter = strtoupper(substr($adminName, 0, 1));
            }
        }
    }
}
?>

<!-- Shared admin sidebar used by all admin pages. -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <a href="index.php">🌱 Bloom House<span>Admin Panel</span></a>
    </div>

    <div class="sidebar-section">Main</div>
    <a href="admin-dashboard.php" class="sidebar-link <?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>"><i class="bi bi-grid"></i> Dashboard</a>
    <a href="manage-products.php" class="sidebar-link <?php echo ($activePage === 'products') ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Manage Products</a>
    <a href="manage-orders.php" class="sidebar-link <?php echo ($activePage === 'orders') ? 'active' : ''; ?>"><i class="bi bi-bag"></i> Orders</a>

    <div class="sidebar-section">Content</div>
    <a href="admin-contact.php" class="sidebar-link <?php echo ($activePage === 'contact') ? 'active' : ''; ?>"><i class="bi bi-envelope"></i> Contact</a>
    <a href="admin-faq.php" class="sidebar-link <?php echo ($activePage === 'faq') ? 'active' : ''; ?>"><i class="bi bi-question-circle"></i> FAQs</a>
    <a href="admin-profile.php" class="sidebar-link <?php echo ($activePage === 'profile') ? 'active' : ''; ?>"><i class="bi bi-person"></i> Profile</a>

    <div class="sidebar-bottom">
        <a href="admin-logout.php" class="sidebar-logout js-admin-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </div>
</aside>

<!-- Shared main wrapper and top navigation. -->
<div class="main">
    <div class="topnav">
        <h1><?php echo htmlspecialchars($adminPageTitle); ?></h1>
        <div class="topnav-right">
            <a class="admin-avatar" href="admin-profile.php"><?php echo htmlspecialchars($adminLetter); ?></a>
        </div>
    </div>

    <!-- Shared logout confirmation modal. -->
    <div class="admin-logout-modal" id="adminLogoutModal" aria-hidden="true">
        <div class="admin-logout-box" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
            <div class="logout-plant-icon"><i class="bi bi-box-arrow-left"></i></div>
            <h3 id="logoutTitle">Logout from Bloom House?</h3>
            <p>Are you sure you want to leave the admin panel?</p>
            <div class="logout-modal-actions">
                <button type="button" class="btn-logout-cancel" id="logoutCancel">Cancel</button>
                <a href="admin-logout.php" class="btn-logout-confirm">Yes, logout</a>
            </div>
        </div>
    </div>

    <!-- Shared logout modal script. -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var logoutLinks = document.querySelectorAll('.js-admin-logout');
            var logoutModal = document.getElementById('adminLogoutModal');
            var logoutCancel = document.getElementById('logoutCancel');

            if (logoutLinks.length > 0 && logoutModal) {
                logoutLinks.forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        logoutModal.classList.add('show');
                        logoutModal.setAttribute('aria-hidden', 'false');
                    });
                });
            }

            if (logoutCancel && logoutModal) {
                logoutCancel.addEventListener('click', function () {
                    logoutModal.classList.remove('show');
                    logoutModal.setAttribute('aria-hidden', 'true');
                });
            }

            if (logoutModal) {
                logoutModal.addEventListener('click', function (e) {
                    if (e.target === logoutModal) {
                        logoutModal.classList.remove('show');
                        logoutModal.setAttribute('aria-hidden', 'true');
                    }
                });
            }
        });
    </script>

    <div class="content">
