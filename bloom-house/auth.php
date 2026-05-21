<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $redirect = basename($_SERVER['PHP_SELF']);
    header("Location: login-required.php?redirect=" . urlencode($redirect));
    exit();
}

include("db_connect.php");

$auth_user_id = (int) $_SESSION['user_id'];
$auth_stmt = $conn->prepare("SELECT user_id FROM user WHERE user_id = ? LIMIT 1");

if (!$auth_stmt) {
    header("Location: login-required.php?redirect=" . urlencode(basename($_SERVER['PHP_SELF'])));
    exit();
}

$auth_stmt->bind_param("i", $auth_user_id);
$auth_stmt->execute();
$auth_result = $auth_stmt->get_result();

if (!$auth_result || $auth_result->num_rows === 0) {
    $_SESSION = array();
    session_unset();
    session_destroy();
    header("Location: login-required.php?redirect=" . urlencode(basename($_SERVER['PHP_SELF'])));
    exit();
}

$auth_stmt->close();
?>
