<?php 

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['order_status'];

    $query = "UPDATE `orders` SET order_status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $new_status, $order_id);

    if ($stmt->execute()) {
        echo "Order status updated successfully.";
    } else {
        echo "Failed to update the order status.";
        error_log($conn->error);
    }

    $stmt->close();

    if ($new_status === 'Completed') {
        $getDate = date("Y-m-d");
        $getQuery = "SELECT * FROM `orders` WHERE id = ?";
        $fetch_stmt = $conn->prepare($getQuery);
        $fetch_stmt->bind_param("s", $order_id);

        if ($fetch_stmt->execute()) {
            $result = $fetch_stmt->get_result();

            if ($result->num_rows > 0) {
                $getOrder = $result->fetch_assoc();

                $insert_query = "INSERT INTO `sales` (id, name, email, products, total_price, completed_at) VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ssssds", $getOrder['id'], $getOrder['name'], $getOrder['email'], $getOrder['products'], $getOrder['total_price'], $getDate);

                if ($insert_stmt->execute()) {
                    $delete_query = "DELETE FROM `orders` WHERE id = ?";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bind_param("s", $order_id);

                    if ($delete_stmt->execute()) {
                        echo "Order deleted successfully.";
                    } else {
                        error_log("Failed to delete order: " . $conn->error);
                    }

                    $delete_stmt->close();
                } else {
                    error_log("Failed to insert into sales: " . $conn->error);
                }

                $insert_stmt->close();
            } else {
                error_log("Order not found for ID: $order_id");
            }
        } else {
            error_log("Failed to fetch order: " . $conn->error);
        }

        $fetch_stmt->close();
    }

    $conn->close();
}

?>