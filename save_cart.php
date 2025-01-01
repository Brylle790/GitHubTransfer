<?php
session_start();
require 'db.php';

$userID = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartData = json_decode($_POST['cart'], true);
    $session_id = $userID;

    // Clear existing cart data for this session
    $clearQuery = "DELETE FROM cart_items WHERE session_id = ?";
    $stmt = $conn->prepare($clearQuery);
    $stmt->bind_param('s', $session_id);
    $stmt->execute();

    // Reinsert updated cart data
    $insertQuery = "INSERT INTO cart_items (product_id, name, price, quantity, size, image, session_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);

    foreach ($cartData as $item) {
        $stmt->bind_param(
            'ssdisss',
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity'],
            $item['size'],
            $item['image'],
            $session_id
        );
        $stmt->execute();
    }

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
