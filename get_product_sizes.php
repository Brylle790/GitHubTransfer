<?php
include 'db.php';

// get_product_sizes.php
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Sanitize the ID to avoid SQL injection
    $id = mysqli_real_escape_string($conn, $id);

    // Run the query to get the sizes and stocks for the product
    $result = mysqli_query($conn, "SELECT size, stock FROM product_sizes WHERE product_id = $id");

    // Check if the query executed successfully
    if ($result) {
        // Fetch all the results
        $sizes = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        // Return the data as JSON
        echo json_encode($sizes);
    } else {
        // Handle the error if the query fails
        echo json_encode(['error' => 'Failed to fetch data']);
    }
} else {
    // Return an error if the ID is not set
    echo json_encode(['error' => 'No product ID provided']);
}
?>

