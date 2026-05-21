<?php
// Author: Zahra Mohsen
// This file includes simple comments to explain the main sections.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}
?>
