<?php
session_start();
include("db_connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('status' => 'not_logged_in', 'message' => 'Please login first to use favourites.'));
    exit();
}

if (!isset($_POST['plant_id']) || !is_numeric($_POST['plant_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid product.'));
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$plant_id = (int) $_POST['plant_id'];

$check_stmt = $conn->prepare("SELECT favourite_id FROM favourite WHERE user_id = ? AND plant_id = ?");
$check_stmt->bind_param("ii", $user_id, $plant_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $delete_stmt = $conn->prepare("DELETE FROM favourite WHERE user_id = ? AND plant_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $plant_id);
    $delete_stmt->execute();

    echo json_encode(array('status' => 'removed', 'message' => 'Plant removed from favourites.'));
    exit();
}

$insert_stmt = $conn->prepare("INSERT INTO favourite (user_id, plant_id, date_added) VALUES (?, ?, NOW())");
$insert_stmt->bind_param("ii", $user_id, $plant_id);

if ($insert_stmt->execute()) {
    echo json_encode(array('status' => 'added', 'message' => 'Plant added to favourites successfully.'));
    exit();
}

echo json_encode(array('status' => 'error', 'message' => 'Could not update favourites.'));
exit();
?>
