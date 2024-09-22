<?php
include 'connect.php'; // Ensure this path is correct and the file exists

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product-id'])) {
    $productId = (int)$_POST['product-id']; // Sanitize as integer
    $barcode = trim($_POST['barcode']);
    $description = trim($_POST['description']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);

    // Validate input
    if (!empty($barcode) && !empty($description) && !empty($brand) && !empty($category) && is_numeric($price)) {
        // Update the product in the database
        $stmt = $conn->prepare("UPDATE products SET Barcode = ?, Description = ?, Brand = ?, Category = ?, Price = ? WHERE id = ?");
        $stmt->bind_param('sssssi', $barcode, $description, $brand, $category, $price, $productId);

        if ($stmt->execute()) {
            echo 'Product updated successfully';
        } else {
            echo 'Failed to update product: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'All fields are required and price must be numeric';
    }
}

mysqli_close($conn);
?>
