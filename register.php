<?php 
include 'db.php';
session_start();
require 'vendor/autoload.php'; // Include Composer's autoloader for SwiftMailer

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

if (isset($_POST['submit'])) {
    // Check database connection
    if (!$conn) {
        die('Database connection failed: ' . mysqli_connect_error());
    }

    $id = mysqli_real_escape_string($conn, $_POST['stdID']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, md5($_POST['password']));
    $cpassword = mysqli_real_escape_string($conn, md5($_POST['cpassword']));
    $access_type = "user";

    // Check if the email already exists
    $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die('Query Failed.');

    if (mysqli_num_rows($select_users) > 0) {
        echo "<script>
                document.getElementById('modalMessage').textContent = 'Account already exists!';
                var myModal = new bootstrap.Modal(document.getElementById('infoModal'));
                myModal.show();
            </script>";
    } else {
        if ($password != $cpassword) {
            echo "<script>
                 document.getElementById('modalMessage').textContent = 'Passwords do not match!';
                 var myModal = new bootstrap.Modal(document.getElementById('infoModal'));
                 myModal.show();
                </script>";
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp; // Store OTP in session
            $_SESSION['email'] = $email; // Store email for OTP validation

            $_SESSION['temp_user'] = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'access_type' => $access_type
            ];

            // Send OTP via email using SwiftMailer
            try {
                $transport = Transport::fromDsn("smtp://$email:tsnspzgbplrtpsnu@smtp.office365.com.:587?encryption=tls");
                $mailer = new Mailer($transport);

                $message = (new Email())
                    ->from('your-email@example.com') // Replace with your sender email
                    ->to($email)
                    ->subject('Your OTP for Registration')
                    ->text("Your OTP is: $otp\n\nPlease use this to complete your registration.");

                $mailer->send($message);
                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const modalText = document.getElementById('modalMessage');
                    modalText.innerHTML = '<p> OTP sent to your email! Please verify </p>';
                    const showModal = new bootstrap.Modal(document.getElementById('infoModal'));
                    showModal.show();
                });
                </script>";

                header('Location: verify_otp.php');
                exit();

            } catch (Exception $e) {
                die('Error sending OTP email: ' . $e->getMessage());
            }
        }
    }
}
// if (isset($_POST['submit'])) {
//     // Check database connection
//     if (!$conn) {
//         die('Database connection failed: ' . mysqli_connect_error());
//     }

//     $id = mysqli_real_escape_string($conn, $_POST['stdID']);
//     $name = mysqli_real_escape_string($conn, $_POST['name']);
//     $email = mysqli_real_escape_string($conn, $_POST['email']);
//     $password = mysqli_real_escape_string($conn, md5($_POST['password']));
//     $cpassword = mysqli_real_escape_string($conn, md5($_POST['cpassword']));
//     $access_type = "user";

//     // Check if the email already exists
//     $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die('Query Failed.');

//     if (mysqli_num_rows($select_users) > 0) {
//         echo "<script>
//                 document.getElementById('modalMessage').textContent = 'Account already exists!';
//                 var myModal = new bootstrap.Modal(document.getElementById('infoModal'));
//                 myModal.show();
//             </script>";
//     } else {
//         if ($password != $cpassword) {
//             echo "<script>
//                  document.getElementById('modalMessage').textContent = 'Passwords do not match!';
//                  var myModal = new bootstrap.Modal(document.getElementById('infoModal'));
//                  myModal.show();
//                 </script>";
//         } else {
//             // Use a proper query with or without `id`
//             $query = "INSERT INTO `users` (id, name, email, password, access_type) VALUES ('$id', '$name', '$email', '$password', '$access_type')";
//             if (mysqli_query($conn, $query)) {
//                 echo "<script>
//                 document.getElementById('modalMessage').textContent = 'Registration successful! Redirecting to login page...';
//                 var myModal = new bootstrap.Modal(document.getElementById('infoModal'));
//                 myModal.show();
//                 setTimeout(function() {
//                     window.location.href = 'login.php';
//                 }, 3000); // Redirect after 3 seconds
//                     </script>";
//                 header('Location: login.php');
//                 exit();
//             } else {
//                 die('Query Error: ' . mysqli_error($conn));
//             }
//         }
//     }
// }
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Register</title>
</head>
<body>
<div class="container mt-5 py-5 ">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Register</h3>
                    </div>
                    <div class="card-body">
                        <form id="regForm" method="POST">
                            <div class="mb-3">
                                <input type="number" class="form-control" id="regID" name="stdID" placeholder="Enter your Student ID eg.(0200)">
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="regName" name="name" placeholder="Enter your full name">
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" id="regEmail" name="email" placeholder="Enter your email">
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" id="regPassword" name="password" placeholder="Enter your password">
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" id="confirmPass" name="cpassword" placeholder="Rewrite your password">
                            </div>
                            <button type="submit" class="btn btn-primary w-100" name="submit">Register</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- Modal Template -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="infoModalLabel">Registration Info</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalMessage">
        <!-- This content will be dynamically updated -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



    <script src="JS/form.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>