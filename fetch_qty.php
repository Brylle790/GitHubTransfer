<?php 
include 'db.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']); // Get the product ID securely
    
    $query = "SELECT qty FROM `products` WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id); // Bind the product ID to the query
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo $row['qty']; // Output the quantity
    } else {
        echo "0"; // No quantity found
    }
} else {
    echo "Invalid Request";
}

?>