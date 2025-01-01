<?php 
session_start();
include 'db.php';

$getOrder_query = "SELECT * FROM `orders` ORDER BY `id` DESC"; // Sort orders by id
$order_stmt = $conn->prepare($getOrder_query); 
$order_stmt->execute();
$result = $order_stmt->get_result();

if(!$result){
    echo json_encode(['error' => 'Database Query Failed']);
    exit();
}

$show_orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

if(empty($show_orders)){
    echo json_encode([]);
    exit();
}

echo json_encode($show_orders);
exit();
?>
