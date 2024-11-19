<?php
include 'connect.php'; // Ensure this path is correct and the file exists

// Set the content type to JSON
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check connection
if (!$conn) {
    sendJsonResponse(false, "Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product-id'])) {
    $productId = (int)$_POST['product-id']; // Sanitize as integer
    $barcode = trim($_POST['barcode']);
    $description = trim($_POST['description']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $cost_price = trim($_POST['cost_price']); // Get cost_price
    $vendorId = (int)$_POST['vendor']; // Get the vendor ID and sanitize as an integer

    // Validate input
    if (
        !empty($barcode) &&
        !empty($description) &&
        !empty($brand) &&
        !empty($category) &&
        is_numeric($price) &&
        is_numeric($cost_price) &&
        $vendorId > 0 // Ensure vendor ID is valid
    ) {
        // Update the product in the database with cost_price and vendor_id
        $stmt = $conn->prepare(
            "UPDATE products 
             SET Barcode = ?, Description = ?, Brand = ?, Category = ?, Price = ?, cost_price = ?, vendor_id = ? 
             WHERE id = ?"
        );
        $stmt->bind_param(
            'ssssddii',
            $barcode,
            $description,
            $brand,
            $category,
            $price,
            $cost_price,
            $vendorId,
            $productId
        );

        if ($stmt->execute()) {
            sendJsonResponse(true, 'Product updated successfully');
        } else {
            sendJsonResponse(false, 'Failed to update product: ' . $stmt->error);
        }

        $stmt->close();
    } else {
        sendJsonResponse(false, 'All fields are required, price/cost price must be numeric, and a valid vendor must be selected');
    }
} else {
    sendJsonResponse(false, 'Invalid request method or missing product ID');
}

mysqli_close($conn);
?>
