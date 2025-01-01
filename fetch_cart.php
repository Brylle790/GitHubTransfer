<?php
session_start();
require 'db.php';
$userID = $_SESSION['user_id'];

$session_id = $userID;

$query = "SELECT product_id AS id, name, price, quantity, size, image FROM cart_items WHERE session_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $session_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
while ($row = $result->fetch_assoc()) {
    $cart[] = $row;
}

echo json_encode(['status' => 'success', 'cart' => $cart]);
?>
