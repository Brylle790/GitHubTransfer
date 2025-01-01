<?php 
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
    header('Location: login.php');
}

?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/order.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <title>Order Uniforms</title>
    </head>
    <body>
        <nav class="navbar sticky-top navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Brand</a>
                
                <div class="navbar-center">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="#">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="order.php">Uniforms</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Services</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Contact</a>
                        </li>
                    </ul>
                </div>
                
                <div class="d-flex align-items-center">
                    <!-- Cart Icon -->
                    <div class="cart-icon me-3">
                        <button type="button" class="btn" id="cart-icon">                    
                            <i class='bx bx-cart bx-sm text-white'></i>
                        </button>
                        <span class="cart-badge" id="cartCounter">0</span>
                    </div>
                    
                    <!-- Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-secondary bg-white text-black dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Profile
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <div class="d-flex align-items-center p-3">
                                    <div>
                                        <h6 class="mb-0">
                                            <?php 
                                        $getUser = $_SESSION['user_name'];
                                        echo $getUser;
                                        ?>
                                </h6>
                                <small class="text-muted">
                                    <?php 
                                        $getUser = $_SESSION['user_email'];
                                        echo $getUser;
                                        ?>
                                </small>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<main>
    
    <!-- Products -->
<div class="container mt-5">
    <div id="inventoryContainer" class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
        <?php 
            $fetch_products = mysqli_query($conn, "SELECT * FROM `products`");
            while($row = mysqli_fetch_assoc($fetch_products)) {
                // Fetch sizes and stock for the product
                $product_id = $row['id'];
                $sizes_query = mysqli_query($conn, "SELECT * FROM `product_sizes` WHERE product_id = '$product_id'");
                $sizes = [];
                while ($size_row = mysqli_fetch_assoc($sizes_query)) {
                    $sizes[] = [
                        'size' => $size_row['size'],
                        'stock' => $size_row['stock']
                    ];
                }
                $sizes_json = json_encode($sizes);
            ?>
            <div class="col">
                <div class="card h-100">
                    <img src="IMG/<?php echo $row["product_image"] ?>" class="card-img-top" alt="<?php echo $row["product_name"] ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $row['product_name'] ?></h5>
                        <p class="card-text fw-semibold">₱<?php echo number_format($row['price'], 2) ?></p>
                        <button type="button" 
                                class="btn btn-primary d-flex align-items-center justify-content-center"
                                data-id="<?= $row['id'] ?>"
                                data-name="<?= $row['product_name'] ?>"
                                data-image="IMG/<?php echo $row['product_image'] ?>"
                                data-price="<?= number_format($row['price'], 2) ?>"
                                data-qty="<?= $row['qty'] ?>"
                                data-sizes='<?= $sizes_json ?>'
                                data-bs-toggle="modal"
                                data-bs-target="#productModal">
                            <i class='bx bxs-cart-add fs-5 me-2'></i>
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        <?php 
            } 
        ?>
    </div>
</div>



<!-- Product Modal -->

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalTitle">Product Details</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row align-items-start">

          <div class="col-md-4 text-center">
            <img id="modalImage" src="" alt="Product Image" class="img-fluid" style="width: 100%; height: auto; object-fit: cover;">
          </div>

          <div class="col-md-8">
            <form action="">

              <p id="productID" class="d-none"></p>

              <!-- Product Price -->
              <div class="mb-3">
                <p id="modalPrice"></p>
              </div>

              <div class="mb-3">
                  <p id="modalQty">Stock: N/A</p>
              </div>

              <div class="mb-3">
                <label for="sizes" class="form-label">Size:</label>
                <select name="sizes" id="sizes" class="form-select"></select>
                </div>

            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" name="add_to_cart" class="btn btn-primary d-flex align-items-center justify-content-center" onclick="addToCart()">
          <i class="bx bxs-cart-add fs-5 me-2"></i>
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</div>




<!-- Cart -->
<div id="cart-section" class="cart-section">
    <div class="cart-header d-flex justify-content-between align-items-center">
        <h2>Your cart</h2>
        <i class='bx bx-left-arrow-alt' id="close-cart"></i>
    </div>
    
    <div id="cart-content" class="cart-content">
        <div class="cart-item d-flex align-items-center justify-content-between" id="cart-item">
            <?php 
                echo $user_id;

            ?>
            <!-- <img src="https://via.placeholder.com/50" alt="Product Image" class="h-25 w-25">
            <div>
                <p class="m-0">Product Name</p>
                <p class="m-0">Size:<span></span></p>
                <p class="m-0">Qty. <i class='bx bx-chevron-left'></i> <span class="text-center">1</span> <i class='bx bx-chevron-right'></i></p>
            </div>
            <i class='bx bx-trash' id="deleteItem"></i> -->
        </div> 
    </div>
    
    <div class="cart-footer mt-3">
        <button onclick="clearAll()" class="btn btn-danger w-100 mb-2">Clear All</button>
        <button class="btn btn-success w-100" onclick="toCheckout()">Checkout</button>
    </div>
</div>
</main>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="JS/script2.js"></script>
</body>
</html>