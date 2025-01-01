<?php 
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
    header('Location: login.php');
}

if(isset($_POST['checkout'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $getTotal = (float)$_POST['total'];
    $placed_on = date("Y-m-d"); 
    $orderID = 'ORDER-' . str_pad(mt_rand(1, 999999), 6, "0", STR_PAD_LEFT);
    
    mysqli_begin_transaction($conn);
    
    try {
        $cart_items = [];
    
        // Fetch cart items
        $cart_query = "SELECT product_id, quantity, name, size FROM `cart_items` WHERE session_id = ?";
        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows === 0) {
            throw new Exception("Cart is empty. Cannot place an order.");
        }
    
        $update_stock_query = "UPDATE product_sizes
                               SET stock = stock - ?
                               WHERE product_id = ? AND size = ? AND stock >= ?;";
        $update_stmt = $conn->prepare($update_stock_query);
    
        while ($row = $result->fetch_assoc()) {
            $productID = $row['product_id'];
            $quantity = $row['quantity'];
            $size = $row['size'];
            $productName = $row['name'];
    
            $cart_items[] = $productName . "(" . $size . ", " . $quantity . ")";
    
            $update_stmt->bind_param("iisi", $quantity, $productID, $size, $quantity);
            $update_stmt->execute();
    
            if ($update_stmt->affected_rows === 0) {
                throw new Exception("Insufficient stock for product ID $productID with size $size.");
            }
        }
    
        $cart_item = implode('<br>', $cart_items);
    
        $order_query = "INSERT INTO `orders` (id, name, email, products, total_price, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param("ssssds", $orderID, $name, $email, $cart_item, $getTotal, $placed_on);
        $order_stmt->execute();

        $deleteCart = "DELETE FROM `cart_items` WHERE session_id = ?";
        $delete_stmt = $conn->prepare($deleteCart);
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
    
        mysqli_commit($conn);
        header('Location: checkout.php');
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Failed to place order: " . $e->getMessage();
    }
    
}


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/sample.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

        <title>Checkout</title>
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
                            <a class="nav-link" href="order.php">Uniform</a>
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
    <h2 class="text-center mt-lg-3">Checkout</h2>

    <div class="container-fluid mt-4">
        <!-- Cart Items Display -->
        <div class="row d-flex justify-content-center overflow-hidden">
                <div class="col-md-5">
                    <div class="row">
                        <?php 
                            $subTotal = 0;
                            $priceTotal = 0;
                            $cartQuery = mysqli_query($conn, "
                                SELECT 
                                    c.*, 
                                    ps.stock 
                                FROM 
                                    cart_items c
                                JOIN 
                                    product_sizes ps 
                                ON 
                                    c.product_id = ps.product_id AND c.size = ps.size
                                WHERE 
                                    c.session_id = $user_id") or die('Failed to obtain query.');
                            if(mysqli_num_rows($cartQuery) > 0){
                                while($row = mysqli_fetch_assoc($cartQuery)){
                            $price = ($row['price'] * $row['quantity']);
                            $subTotal += $price;
                            $priceTotal += $price;
                        ?>
                        <div class="card mb-3 w-100" style="max-width: 600px;">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="<?php echo $row['image'];?>" class="img-fluid rounded-start mt-lg-4 py-3" alt="<?php echo $row['name']; ?>">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <p class="d-none" id="productID"><?php echo $row['product_id'];?></p>
                                        <h5 class="card-title" id="productTitle"><?php echo $row['name']; ?></h5>
                                        <p class="card-text fs-5" id="productPrice"><?php echo 'Price: ₱'.$row['price']; ?></p>
                                        <p class="card-text fs-5" id="productStocks"><?php echo 'Stocks: '.$row['stock']; ?></p>
                                        <p class="card-text fs-5" id="productSize"><?php echo 'Size: '.$row['size']; ?></p>
                                        <p class="card-text fs-5" id="productQty"><?php echo 'Quantity: '.$row['quantity']; ?></p>
                                        <p class="card-text fs-5" id="subTotal"><?php echo 'Subtotal: ₱'. number_format($price); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php 
                            }
                        } else {
                            echo '<p>Your Cart is empty.</p>';
                        }
                        ?>
                    </div>
                </div>
            
                <div class="col-md-4 border border-2 rounded-3 d-flex flex-column justify-content-center align-items-center overflow-hidden" id="container2" style="height: 300px;">
                    <form action="" method="post" class="w-100 p-3">
                        <!-- User Information Section -->
                        <div class="row">
                            <div class="col">
                                <input 
                                    type="text" 
                                    class="form-control text-black-50"
                                    name="name" 
                                    placeholder="Name" 
                                    value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" 
                                    readonly 
                                    aria-label="User Name">
                            </div>
                            <div class="col">
                                <input 
                                    type="email"
                                    class="form-control text-black-50" 
                                    placeholder="Your Email" 
                                    value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" 
                                    readonly 
                                    aria-label="User Email">
                            </div>
                        </div>

                        <!-- Total Price Section -->
                         
                        <input 
                            type="text" 
                            name="total" 
                            id="total" 
                            class="form-control mt-4 text-center text-black-50" 
                            value="<?php echo 'Total: ₱' . htmlspecialchars($priceTotal ?? '0.00'); ?>" 
                            readonly 
                            aria-label="Total Price">

                        <!-- Checkout Button -->

                        <input type="hidden" name="name" value="<?php echo $_SESSION['user_name'];?>">
                        <input type="hidden" name="email" value="<?php echo $_SESSION['user_email'];?>">
                        <input type="hidden" name="total" value="<?php echo $priceTotal;?>">

                        <div class="d-grid">
                            <button 
                            type="submit" 
                            class="btn btn-outline-primary fs-4 text-center mt-lg-4" 
                            name="checkout">
                            <i class="bx bxs-shopping-bags me-2"></i>Checkout
                            </button>
                        <small class="text-warning fst-italic">Note: Please double check your items before checking out.</small>
                        </div>
                    </form>
                </div>
        </div>
    </div>
    
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>