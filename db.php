<?php 
$servername = "localhost";
$user = "root";
$password = "";
$db = "ordersystem";

$conn = new mysqli($servername, $user, $password, $db);

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

?>