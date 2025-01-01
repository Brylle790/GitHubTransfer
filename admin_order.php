<?php 
include 'db.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
    header('Location: login.php');
}

if (isset($_POST['delete-btn'])) {
  $delete_id = mysqli_real_escape_string($conn, $_POST['delete_id']);
  $order_status = mysqli_real_escape_string($conn, $_POST['orderStatus']);;
    
    
  $check_query = "SELECT id, products FROM `orders` WHERE id = ?";
  $check_stmt = $conn->prepare($check_query);
  $check_stmt->bind_param("s", $delete_id);
  $check_stmt->execute();
  $result = $check_stmt->get_result();

  if ($result->num_rows === 0) {
      die("Order with ID $delete_id does not exist.");
  }

  $order = $result->fetch_assoc();
  $products = $order['products']; // This should now work without errors.


  if ($order_status === 'Pending' || $order_status === 'Ready To Pick-Up') {
    // Split products into individual items
    $product_items = explode("<br>", $products);

    // Fetch all products from the database once
    $getProduct_query = "SELECT id, product_name FROM `products`";
    $productDB_stmt = $conn->prepare($getProduct_query);
    $productDB_stmt->execute();
    $result = $productDB_stmt->get_result();

    // Store products in an associative array (product_name => id)
    $product_map = [];
    while ($row = $result->fetch_assoc()) {
        $product_map[$row['product_name']] = $row['id'];
    }

    foreach ($product_items as $item) {
        if (preg_match('/^(.+)\((.+),\s*(\d+)\)$/', $item, $matches)) {
            $product_name = trim($matches[1]); // Product name or ID
            $size = trim($matches[2]);        // Size (e.g., "S", "L")
            $quantity = (int)$matches[3];     // Quantity (e.g., 3)

            if(isset($product_map[$product_name])){
                $product_id = $product_map[$product_name];

                $updateStock_query = "UPDATE `product_sizes` SET stock = stock + ? WHERE product_id = ? AND size = ?";
                $updatestmt = $conn->prepare($updateStock_query);
                $updatestmt->bind_param("iis", $quantity, $product_id, $size);
                $updatestmt->execute();

                echo 'Stock Successfully Updated';
            } else {
                    die("Product not found: " . $product_name);
            }
        }
    }
  }

  
  // Delete the order from the orders table
  $delete_query = "DELETE FROM `orders` WHERE `id` = ?";
  $stmt = $conn->prepare($delete_query);
  $stmt->bind_param("s", $delete_id); // Bind as integer


  // Execute the query
  if ($stmt->execute()) {
      // If successful, redirect back
      header('Location: admin_order.php');
      exit;
  } else {
      die("Error deleting order: " . mysqli_error($conn)); // Error handling
  }

}





?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex vh-100">
    <!-- Sidebar -->
    <div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark h-100 position-fixed" style="width: 280px;">
      <a href="admin.html" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
      <p class="fs-4 mb-0">Order<span class="text-primary">Admin</span></p>
      </a>
      <hr>
      <ul class="nav nav-pills flex-column mb-auto">
        <li>
          <a href="admin.php" class="nav-link text-white">
            <i class='bx bxs-dashboard me-2'></i>
            Dashboard
          </a>
        </li>
        <li>
          <a href="admin_order.php" class="nav-link text-white active" id="orders-sidebar-item">
            <i class="bi bi-box-fill me-2"></i>
            Orders
          </a>
        </li>
        <li>
          <a href="admin_inventory.php" class="nav-link text-white">
            <i class="bi bi-clipboard-fill me-2"></i>
            Inventory
          </a>
        </li>
        <li>
          <a href="admin_sales.php" class="nav-link text-white">
            <i class="bi bi-graph-up-arrow me-2"></i>
            Sales
          </a>
        </li>
        <li>
          <a href="admin_users.php" class="nav-link text-white">
            <i class='bx bxs-user me-2'></i>
            Users
          </a>
        </li>
      </ul>
      <hr>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-person-fill me-2"></i>
          <strong><?php 
            $getAdmin = $_SESSION['admin_name'];

            echo $getAdmin;
          ?>
          </strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
          <li><a class="dropdown-item" href="#">Sign out</a></li>
        </ul>
      </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1" style="margin-left: 35vh; padding: 20px;">
      <h3>Manage Orders</h3>
      <table class="table table-hover table-striped table-bordered">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Products:(Size, Quantity)</th>
            <th>Total Price:</th>
            <th>Order Status:</th>
            <th>Created at:</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="orderContainer">
          <?php 
            $fetch_orders = mysqli_query($conn, "SELECT * FROM `orders`");
            while($row = mysqli_fetch_assoc($fetch_orders)){
              $order_id = $row['id'];
              $status = $row['order_status'];
          ?>
          <tr>
            <td><?php echo $order_id;?></td>
            <td><?php echo htmlspecialchars($row['name']);?></td>
            <td><?php echo htmlspecialchars($row['email']);?></td>
            <td><?php echo htmlspecialchars_decode($row['products']);?></td>
            <td><?php echo htmlspecialchars('₱'.$row['total_price']);?></td>
            <td>
                      <select class="form-select order-status" data-order-id="<?php echo $order_id; ?>">
                          <option value="Pending" <?php if ($status == 'Pending') echo 'selected'; ?>>Pending</option>
                          <option value="Ready To Pick-Up" <?php if ($status == 'Ready To Pick-Up') echo 'selected'; ?>>Ready To Pick-Up</option>
                          <option value="Completed" <?php if ($status == 'Completed') echo 'selected'; ?>>Completed</option>
                      </select>
            </td>
            <td><?php echo htmlspecialchars($row['created_at']);?></td>
            <td>
            <button type="button" 
                        class="btn btn-danger delete-btn"
                        data-id="<?php echo $order_id; ?>"
                        data-status="<?php echo $status;?>" 
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteConfirmationModal">
                          Delete
                      </button>
            </td>
          </tr>
          <?php 
            }
          ?>
        </tbody>
      </table>
    </main>

    <!-- Order Status Success Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
          <i class='bx bxs-check-circle me-2'></i>
          <strong class="me-auto">Success!</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          Order Status has successfully changed.
        </div>
      </div>
    </div>

    <!-- Order Status Failed Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div id="failedToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
          <i class='bx bxs-x-circle'></i>
          <strong class="me-auto">Fail!</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          Order Status has failed to update.
        </div>
      </div>
    </div>

    <!-- Delete Confirmation -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
              <div class="modal-body">
                Are you sure you want to delete this order?
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="" method="POST" id="deleteForm">
                  <input type="hidden" name="orderStatus" id="getorder_status">
                  <input type="hidden" name="delete_id" id="deleteProductId">
                  <button type="submit" name="delete-btn" class="btn btn-danger">Delete</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="JS/admin_order.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
