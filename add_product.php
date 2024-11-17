<?php
include 'connect.php'; // Ensure this path is correct and the file exists

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $barcode = trim($_POST['barcode']);
    $description = trim($_POST['description']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $cost_price = trim($_POST['cost_price']); // New cost_price field

    // Validate input
    if (!empty($barcode) && !empty($description) && !empty($brand) && !empty($category) && is_numeric($price) && is_numeric($cost_price)) {
        // Insert into the database
        $stmt = $conn->prepare("INSERT INTO products (Barcode, Description, Brand, Category, Price, cost_price, Quantity) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param('ssssdi', $barcode, $description, $brand, $category, $price, $cost_price);

        if ($stmt->execute()) {
            echo 'Product added successfully';
        } else {
            echo 'Failed to add product: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'All fields are required, and price/cost price must be numeric';
    }
}

$conn->close();
?>
