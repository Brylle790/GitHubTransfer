<?php
session_start();

if(!isset($_SESSION['temp_user'])){
    echo 'You do not have access to this page';
}

if (isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];
    if ($entered_otp == $_SESSION['otp']) {
        // OTP is valid; insert the user into the database
        include 'db.php';

        $user = $_SESSION['temp_user'];
        $query = "INSERT INTO `users` (id, name, email, password, access_type) 
                  VALUES ('{$user['id']}', '{$user['name']}', '{$user['email']}', '{$user['password']}', '{$user['access_type']}')";
        if (mysqli_query($conn, $query)) {
            unset($_SESSION['otp'], $_SESSION['temp_user']); // Clear session data
            echo "<script>
                alert('Registration successful!');
                window.location.href = 'login.php';
                </script>";
            exit();
        } else {
            die('Query Error: ' . mysqli_error($conn));
        }
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Verify OTP</title>
</head>
<body>
<div class="container mt-5 py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h3>Verify OTP</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="otp" class="form-label">Enter OTP</label>
                            <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter OTP" required>
                        </div>
                        <button type="submit" name="verify" class="btn btn-primary w-100">Verify</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
