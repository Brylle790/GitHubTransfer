<?php
require 'db.php';


header('Content-Type: application/json'); // Make sure the response is treated as JSON

try {
    //Ensure year and month are passed as POST parameters
    if (!isset($_POST['year']) || !isset($_POST['month'])) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }

    $year = $_POST['year'];
    $month = $_POST['month'];

    // Check if year and month are valid
    // if (!$year || !$month || $month < 1 || $month > 12) {
    //     echo json_encode(['error' => 'Invalid parameters']);
    //     exit;
    // }

    // Fetch the sales data based on the year and month
    $query = "SELECT id, name, email, products, total_price, order_status, created_at FROM sales WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $year, $month);
    $stmt->execute();
    $salesData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    function getProductSummary($sales) {
        $productSummary = [];
    
        foreach ($sales as $sale) {
            $products = explode("<br>", $sale['products']); // Split by line breaks
            foreach ($products as $product) {
                preg_match('/^(.*?)\(([^,]+),\s*(\d+)\)$/', $product, $matches);
                if ($matches) {
                    $productName = trim($matches[1]);
                    $size = trim($matches[2]);
                    $quantity = (int) $matches[3];
    
                    $key = $productName . '|' . $size; // Unique key for product and size
                    if (!isset($productSummary[$key])) {
                        $productSummary[$key] = [
                            'name' => $productName,
                            'size' => $size,
                            'total' => 0
                        ];
                    }
                    $productSummary[$key]['total'] += $quantity;
                }
            }
        }
    
        return array_values($productSummary); // Return as a sequential array
    }

    // Prepare product summary
    $productSummary = getProductSummary($salesData); // Using the helper function for product summary

    // Return the sales data and product summary as a JSON response
    echo json_encode([
        'sales' => $salesData,
        'summary' => $productSummary
    ]);
} catch (Exception $e) {
    // If an error occurs, return the error message as JSON
    echo json_encode(['error' => $e->getMessage()]);
}
?>