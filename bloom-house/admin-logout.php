<?php
// Author: Zahra Mohsen
// This file includes simple comments to explain the main sections.

session_start();

// Clear admin session values
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);

// Destroy the current session completely
session_destroy();

// Redirect admin to login page with logout success message
header('Location: admin-login.php?logout=success');
exit();
?>
