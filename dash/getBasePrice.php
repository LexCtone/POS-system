<?php
// Database connection (replace with your actual connection details)
require '../connect.php';

$barcode = $_GET['barcode'];  // Get barcode from the URL parameter

// Prepare and execute the query to fetch the base price for the product
$query = "SELECT cost_price FROM products WHERE Barcode = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $barcode);
$stmt->execute();
$stmt->bind_result($basePrice);
$stmt->fetch();
$stmt->close();

// Return the base price as JSON
// Ensure basePrice is returned as a number, not a string
echo json_encode(['basePrice' => (float)$basePrice]);
?>
