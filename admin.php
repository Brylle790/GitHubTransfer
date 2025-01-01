<?php 
include 'db.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
    header('Location: login.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
  <div class="d-flex vh-100">
    <!-- Sidebar -->
    <div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark h-100" style="width: 280px;">
      <a href="admin.html" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <p class="fs-4 mb-0">Order<span>Admin</span></p>
      </a>
      <hr>
      <ul class="nav nav-pills flex-column mb-auto">
        <li>
          <a href="admin.php" class="nav-link text-white active">
            <i class='bx bxs-dashboard me-2' style='color:#ffffff'></i>
            Dashboard
          </a>
        </li>
        <li>
          <a href="admin_order.php" class="nav-link text-white">
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
          <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
        </ul>
      </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1 p-4">

    <h3>Dashboard</h3>

      <div class="row gap-3 ms-lg-3">
        <div class="card" style="width: 10rem;">
            <div class="card-body">
              <h5 class="card-title text-center">Products</h5>
              <p class="card-tex text-center h3 mt-4">
                <?php 
                  $select_products = mysqli_query($conn, "SELECT * FROM `products`") or die('Query failed.');
                  $getTotalProducts = mysqli_num_rows($select_products);
  
                  echo $getTotalProducts;
                ?>
              </p>
            </div>
        </div>
  
        <div class="card" style="width: 10rem;">
            <div class="card-body">
              <h5 class="card-title text-center fs-5">Total Orders</h5>
              <p class="card-tex text-center h3 mt-3">
                <?php 
                  $select_products = mysqli_query($conn, "SELECT * FROM `orders`") or die('Query failed.');
                  $getTotalProducts = mysqli_num_rows($select_products);
  
                  echo htmlspecialchars($getTotalProducts);
                ?>
              </p>
            </div>
        </div>
      </div>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
  