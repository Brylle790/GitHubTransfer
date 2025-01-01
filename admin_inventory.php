<?php 
include 'db.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
    header('Location: login.php');
}

$error_msg = "";

if (isset($_POST['delete-btn'])) {
  $delete_id = $_POST['delete_id'];


  $delete_sizes_query = "DELETE FROM `product_sizes` WHERE `product_id` = $delete_id";
  if (!mysqli_query($conn, $delete_sizes_query)) {
    die("Error deleting sizes: " . mysqli_error($conn));
  }


  $delete_query = "DELETE FROM `products` WHERE `id` = $delete_id";
  if (mysqli_query($conn, $delete_query)) {

    $delete_image = mysqli_query($conn, "SELECT product_image FROM `products` WHERE id = '$delete_id'");
    $fetch_delete_image = mysqli_fetch_assoc($delete_image);
    
    if ($fetch_delete_image && isset($fetch_delete_image['product_image'])) {
        unlink('IMG/' . $fetch_delete_image['product_image']);
    }
    
    header('Location: admin_inventory.php');
  } else {
    die("Error deleting product: " . mysqli_error($conn));
  }
}

if (isset($_POST['add-product'])) {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $price = $_POST['price'];
  $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
  $image = $_FILES['image'];
  
  // Handle the image upload
  $image_name = time() . "_" . basename($image['name']);
  $image_tmp_name = $image['tmp_name'];
  $image_folder = 'IMG/' . $image_name;


  $sizes = $_POST['sizes'];  
  $stocks = $_POST['stocks']; 


  $select_name = mysqli_query($conn, "SELECT product_name FROM `products` WHERE product_name = '$name'");
  if (!$select_name) {
      die("Failed Query: " . mysqli_error($conn));
  }

  if (mysqli_num_rows($select_name) > 0) {
      $error_msg = "Product has already been added";
      echo $error_msg;
  } else {
      $add_query = mysqli_query($conn, "INSERT INTO `products` (product_image, product_name, gender, price) 
                                        VALUES ('$image_name', '$name', '$gender', '$price')");
      if (!$add_query) {
          die("Error adding product: " . mysqli_error($conn));
      }


      $product_id = mysqli_insert_id($conn);


      $total_qty = 0;

      foreach ($sizes as $index => $size) {
          $stock = $stocks[$index];
          $add_size_query = mysqli_query($conn, "INSERT INTO `product_sizes` (product_id, size, stock) 
                                                VALUES ($product_id, '$size', $stock)");

          if (!$add_size_query) {
              die("Error adding size and stock: " . mysqli_error($conn));
          }

          $total_qty += $stock;
      }


      $update_qty_query = "UPDATE `products` SET qty = $total_qty WHERE id = $product_id";
      if (!mysqli_query($conn, $update_qty_query)) {
          die("Error updating total quantity: " . mysqli_error($conn));
      }

      if (move_uploaded_file($image_tmp_name, $image_folder)) {
          header("Location: admin_inventory.php?success=1&total_qty=$total_qty");
          exit;
      } else {
          echo "Failed to upload image.";
      }
  }
}



if(isset($_GET['fetch_inventory'])){
  $result = mysqli_query($conn, "SELECT * FROM `products`");
  $inventory = mysqli_fetch_all($result, MYSQLI_ASSOC);

  echo json_encode($inventory);
  exit();
}


if (isset($_POST['edit-product'])) {
  $id = $_POST['id'];
  $update_name = mysqli_real_escape_string($conn, $_POST['name']);
  $update_price = $_POST['price'];
  $gender = $_POST['gender'];
  $update_image = $_FILES['image'];


  $query = "UPDATE `products` 
            SET product_name = '$update_name', 
                gender = '$gender', 
                price = '$update_price'
            WHERE id = $id";


  if ($update_image['name']) {
      $imagename = time() . "_" . basename($update_image['name']);
      $targetPath = "IMG/" . $imagename;
      move_uploaded_file($update_image['tmp_name'], $targetPath);

      $query = "UPDATE `products` 
                SET product_image = '$imagename', 
                    product_name = '$update_name', 
                    gender = '$gender', 
                    price = '$update_price'
                WHERE id = $id";
  }


  $update_result = mysqli_query($conn, $query);


  if ($update_result) {

      $sizes = $_POST['sizes'];
      $stocks = $_POST['stocks'];
      
      $total_qty = 0;

      foreach ($sizes as $index => $size) {
          $stock = $stocks[$index];

          
          $check_size = mysqli_query($conn, "SELECT * FROM `product_sizes` WHERE product_id = $id AND size = '$size'");
          if (mysqli_num_rows($check_size) > 0) {
              
              $update_size_query = mysqli_query($conn, "UPDATE `product_sizes` SET stock = $stock WHERE product_id = $id AND size = '$size'");
          } else {
              
              $add_size_query = mysqli_query($conn, "INSERT INTO `product_sizes` (product_id, size, stock) VALUES ($id, '$size', $stock)");
          }

          if (!$add_size_query && !$update_size_query) {
              die("Error adding or updating sizes: " . mysqli_error($conn));
          }

          
          $total_qty += $stock;
      }

      // Update the total qty in the `products` table based on the sum of stocks
      $update_qty_query = "UPDATE `products` SET qty = $total_qty WHERE id = $id";
      mysqli_query($conn, $update_qty_query);

      header('Location: admin_inventory.php');
  } else {
      die("Error updating product: " . mysqli_error($conn));
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><Inventory></Inventory></title>
    <link rel="stylesheet" href="css/inventory.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>

<div class="d-flex vh-100">
    <!-- Sidebar -->
    <div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark h-100" style="width: 280px;">
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
          <a href="admin_inventory.php" class="nav-link text-white active">
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
    <main class="flex-grow-1 p-4">
    <div class="container" style="margin-top: 10vh;">
            <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProduct">Add Item</button>
            </div>
            <table class="table table-bordered" id="inventory_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Image</th>
                        <th>Product Name</th>
                        <th>Gender</th>
                        <th>Price</th>
                        <th>Qty.</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="table-group-divider h-auto">
                <?php 
                  $fetch_products = mysqli_query($conn, "
                      SELECT p.id, p.product_image, p.product_name, p.gender, p.price, p.qty, 
                            SUM(ps.stock) AS total_stock
                      FROM `products` p
                      LEFT JOIN `product_sizes` ps ON p.id = ps.product_id
                      GROUP BY p.id
                  ");
                  while($row = mysqli_fetch_assoc($fetch_products)) {
                ?>
                  <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><img src="IMG/<?php echo $row["product_image"]; ?>" alt="<?php echo $row['product_name']; ?>" width="50"></td>
                    <td><?php echo $row['product_name'] ?></td>
                    <td><?php echo $row['gender'] ?></td>
                    <td>₱<?php echo number_format($row['price'], 2)?></td>
                    <td><?php echo $row['total_stock']; ?></td>
                    <td>
                      <button class="btn btn-secondary me-2" 
                      type="button" 
                        data-id="<?= $row['id']?>"
                        data-name="<?= $row['product_name']?>"
                        data-gender="<?= $row['gender']?>"
                        data-price="<?= $row['price']?>"
                        data-qty="<?= $row['qty']?>"
                        data-bs-toggle="modal" data-bs-target="#editProduct" id="editBtn">Edit</button>
                      <button type="button" 
                        class="btn btn-danger delete-btn"
                        data-id="<?php echo $row['id']; ?>" 
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
        </div>
    </main>
</div>
        <!-- Add Product -->
<div class="modal fade" id="addProduct" tabindex="-1" aria-labelledby="addProductLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addProductLabel">Add Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="" method="post" enctype="multipart/form-data">
          <div class="input-group mb-3">
            <input type="text" name="name" class="form-control" placeholder="Enter Product Name" required>
          </div>

          <div class="input-group mb-3">
            <input type="number" name="price" class="form-control" placeholder="Enter Price" min="0" required>
          </div>

          <div class="input-group mb-3">
            <input type="number" name="qty" class="form-control" placeholder="Enter Quantity" min="0">
          </div>

          <div class="input-group mb-3">
            <label class="form-label">Sizes and Stocks:</label>
            <div id="sizeStockInputs">
              <!-- Initially, one pair of size and stock inputs -->
              <div class="input-group mb-2">
                <input type="text" name="sizes[]" class="form-control" placeholder="Size (e.g., S)" required>
                <input type="number" name="stocks[]" class="form-control" placeholder="Stock for this size" min="0" required>
                <button type="button" class="btn btn-danger remove-size-btn">Remove</button>
              </div>
            </div>
            <button type="button" class="btn btn-primary" id="addSizeStockBtn">Add Size and Stock</button>
          </div>

          <div class="input-group mb-3">
            <input type="file" class="form-control" name="image" accept="image/jpg, image/jpeg, image/png" id="inputGroupFile02">
            <label class="input-group-text" for="inputGroupFile02">Upload</label>
          </div>

          <div class="input-group mb-3">
            <label class="form-check-text ">Gender:</label>
            <div class="form-check">
              <input class="form-check-input ms-2" type="radio" name="gender" id="flexRadioDefault1" value="Female">
              <label class="form-check-label" for="flexRadioDefault1">
                Female
              </label>
            </div>

            <div class="form-check">
              <input class="form-check-input ms-2" type="radio" name="gender" id="flexRadioDefault2" value="Male">
              <label class="form-check-label" for="flexRadioDefault2">
                Male
              </label>
            </div>

            <div class="form-check">
              <input class="form-check-input ms-2" type="radio" name="gender" id="flexRadioDefault3" value="None">
              <label class="form-check-label" for="flexRadioDefault3">
                None
              </label>
            </div>
          </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <input type="submit" value="Add Product" name="add-product" class="btn btn-primary" id="addProduct">
      </div>
        </form>
    </div>
  </div>
</div>


        <!-- Edit Product -->
        <div class="modal fade" id="editProduct" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
              <form action="" method="post" enctype="multipart/form-data">
                  <input type="hidden" name="id" id="editId">

                  <div class="input-group mb-3">                    
                    <input type="text" name="name" class="form-control" id="editName" placeholder="Enter Product Name" required>
                  </div>

                  <div class="input-group mb-3">                    
                    <input type="number" name="price" class="form-control" id="editPrice" placeholder="Enter Price" min="0" required>
                  </div>

                  <div class="input-group mb-3">                    
                    <input type="text" id="editQty" name="qty" class="form-control" readonly />
                  </div>

                  <div class="input-group mb-3">
                    <input type="file" class="form-control" name="image" accept="image/jpg, image/jpeg, image/png" id="inputGroupFile02">
                    <label class="input-group-text" for="inputGroupFile02">Upload</label>
                  </div>

                  <div class="input-group mb-3">
                    <label class="form-label">Sizes and Stocks:</label>
                    <div id="editSizeStockInputs">
                        <!-- Existing size and stock fields will be injected here -->
                    </div>
                    <button type="button" id="addEditSizeStockBtn" class="btn btn-secondary">Add Another Size</button>
                  </div>


                  <div class="input-group mb-3">
                    <label class="form-check-text ">Gender:</label>                    
                    <div class="form-check">
                      <input class="form-check-input ms-2" type="radio" name="gender" id="editGenderFemale" value="Female">
                      <label class="form-check-label" for="editGenderFemale">
                        Female
                      </label>
                    </div>
  
                    <div class="form-check">
                      <input class="form-check-input ms-2" type="radio" name="gender" id="editGenderMale" value="Male">
                      <label class="form-check-label" for="editGenderMale">
                        Male
                      </label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input ms-2" type="radio" name="gender" id="editGenderNone" value="None">
                      <label class="form-check-label" for="editGenderNone">
                        None
                      </label>
                    </div>
                  </div>

              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <input type="submit" value="Edit Product" name="edit-product" class="btn btn-primary">
              </div>
                </form>
            </div>
          </div>
        </div>

        <!-- Error/Success Modal -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Success</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                Product added successfully!
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                An error occurred while adding the product. Please try again.
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
           </div>
        </div>
        
        <!-- Delete Confirmation -->
        <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmationLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
                <div class="modal-body">
                  Are you sure you want to delete this item?
                </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteForm">
                  <input type="hidden" name="delete_id" id="deleteProductId">
                  <button type="submit" name="delete-btn" class="btn btn-danger">Delete</button>
                </form>
              </div>
            </div>
          </div>
        </div>
    </main>

    <script src="JS/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
