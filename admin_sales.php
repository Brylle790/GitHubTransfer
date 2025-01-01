<?php 
include 'db.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
    header('Location: login.php');
}

$query = "SELECT DISTINCT YEAR(completed_at) AS year, MONTH(completed_at) AS month FROM sales ORDER BY year DESC, month DESC";
$result = $conn->query($query);
$months = $result->fetch_all(MYSQLI_ASSOC);

// Fetch sales data by month
function getSalesByMonth($conn, $year, $month) {
    $query = "SELECT id, name, email, products, total_price, order_status, completed_at FROM sales WHERE YEAR(completed_at) = ? AND MONTH(completed_at) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $year, $month);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Helper function to parse products and calculate totals
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales</title>
    <link rel="stylesheet" href="CSS/sales.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark h-100 position-fixed" style="top: 0; left: 0; width: 280px; height: 100vh; z-index: 1000;">
      <a href="admin.html" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <p class="fs-4 mb-0">Order<span class="text-primary">Admin</span></p>
      </a>
      <hr>
      <ul class="nav nav-pills flex-column mb-auto">
        <li>
          <a href="admin.php" class="nav-link text-white">
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
          <a href="admin_sales.php" class="nav-link text-white active">
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
    <main class="flex-grow-1" style="margin-left: 35vh;">
    <div class="container mt-5">
        <h1 class="mb-4">Sales Data</h1>
        <div class="accordion" id="salesAccordion">
            <?php foreach ($months as $index => $monthData): 
                $year = $monthData['year'];
                $month = $monthData['month'];
                $sales = getSalesByMonth($conn, $year, $month);
                $productSummary = getProductSummary($sales); // Get product summary
                $monthName = date('F', mktime(0, 0, 0, $month, 1));
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?= $index; ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index; ?>" aria-expanded="false" aria-controls="collapse<?= $index; ?>">
                        <?= "$monthName $year"; ?>
                    </button>
                </h2>
                <div id="collapse<?= $index; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index; ?>" data-bs-parent="#salesAccordion">
                    <div class="accordion-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Products</th>
                                    <th>Total Price</th>
                                    <th>Order Status</th>
                                    <th>Completed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalAmount = 0;
                                foreach ($sales as $sale):
                                    $totalAmount += $sale['total_price'];  
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($sale['id']); ?></td>
                                    <td><?= htmlspecialchars($sale['name']); ?></td>
                                    <td><?= htmlspecialchars($sale['email']); ?></td>
                                    <td><?= nl2br(htmlspecialchars_decode($sale['products'])); ?></td>
                                    <td><?= htmlspecialchars($sale['total_price']); ?></td>
                                    <td><?= htmlspecialchars($sale['order_status']); ?></td>
                                    <td><?= htmlspecialchars($sale['completed_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>Total Amount for this Month: <?php echo $totalAmount; ?></p>
                        <caption>Total Purchased Items</caption>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Size</th>
                                    <th>Total Number of Purchases</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productSummary as $summary): ?>
                                <tr>
                                    <td><?= htmlspecialchars($summary['name']); ?></td>
                                    <td><?= htmlspecialchars($summary['size']); ?></td>
                                    <td class="w-auto"><?= htmlspecialchars($summary['total']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <form method="post" action="download.php">
                            <input type="hidden" name="year" value="<?= $year; ?>">
                            <input type="hidden" name="month" value="<?= $month; ?>">
                            <button type="submit" class="btn btn-primary">Download <?= "$monthName $year"; ?> Data</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    </main>
</div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
