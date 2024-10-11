<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "u429621164_sv_hardware_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
