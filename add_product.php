<?php 
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = $_POST['price'];
    $qty = $_POST['qty'];
    $sizes = $_POST['sizes']; // Assume sizes come as an array from the form
    $image = $_FILES['image']['name'];
    $img_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = 'IMG/' . $image;

    $response = [];

    // Check if the product already exists
    $select_name = mysqli_query($conn, "SELECT id, qty, product_image FROM `products` WHERE product_name = '$name'");
    
    if (mysqli_num_rows($select_name) > 0) {
        // Update quantity if product exists
        $row = mysqli_fetch_assoc($select_name);
        $new_qty = $row['qty'] + $qty;

        $update_query = mysqli_query($conn, "UPDATE `products` SET qty = $new_qty WHERE id = " . $row['id']);

        if ($update_query) {
            $response['success'] = true;
            $response['product'] = [
                'id' => $row['id'],
                'image' => 'IMG/' . $row['product_image'], // Use existing image
                'name' => $name,
                'price' => number_format($price, 2),
                'qty' => $new_qty,
            ];
        } else {
            $response['success'] = false;
            $response['error'] = "Database Error: " . mysqli_error($conn);
        }
    } else {
        // Insert new product if not exists
        $add_query = mysqli_query($conn, "INSERT INTO `products` (product_image, product_name, qty, price) VALUES ('$image', '$name', $qty, '$price')");

        if ($add_query) {
            $product_id = mysqli_insert_id($conn);
            move_uploaded_file($img_tmp_name, $image_folder); // Move image to folder

            // Insert sizes and quantities for the new product
            foreach ($sizes as $size => $size_qty) {
                $size = mysqli_real_escape_string($conn, $size);
                $size_qty = (int)$size_qty;

                $size_query = mysqli_query($conn, "INSERT INTO product_sizes (product_id, size, qty) VALUES ($product_id, '$size', $size_qty)");

                if (!$size_query) {
                    $response['success'] = false;
                    $response['error'] = "Database Error: " . mysqli_error($conn);
                    echo json_encode($response);
                    exit();
                }
            }

            $response['success'] = true;
            $response['product'] = [
                'id' => $product_id,
                'image' => $image_folder,
                'name' => $name,
                'price' => number_format($price, 2),
                'qty' => $qty,
                'sizes' => $sizes // Add the sizes to the response
            ];
        } else {
            $response['success'] = false;
            $response['error'] = "Database Error: " . mysqli_error($conn);
        }
    }

    echo json_encode($response);
    exit();
}

// Handle fetching inventory
if (isset($_GET['fetch_inventory'])) {
    $result = mysqli_query($conn, "SELECT * FROM `products`");

    if (!$result) {
        echo json_encode(['error' => 'Database query failed']);
        exit();
    }

    $inventory = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if (empty($inventory)) {
        echo json_encode(['error' => 'No inventory found']);
        exit();
    }

    // Fetch sizes for each product
    foreach ($inventory as &$item) {
        $sizes_result = mysqli_query($conn, "SELECT * FROM product_sizes WHERE product_id = " . $item['id']);
        $sizes = mysqli_fetch_all($sizes_result, MYSQLI_ASSOC);
        $item['sizes'] = $sizes; 
    }

    echo json_encode($inventory);
    exit();
}
?>
