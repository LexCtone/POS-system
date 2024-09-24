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

    // Validate input
    if (!empty($barcode) && !empty($description) && !empty($brand) && !empty($category) && is_numeric($price)) {
        // Update the product in the database
        $stmt = $conn->prepare("UPDATE products SET Barcode = ?, Description = ?, Brand = ?, Category = ?, Price = ? WHERE id = ?");
        $stmt->bind_param('ssssdi', $barcode, $description, $brand, $category, $price, $productId);

        if ($stmt->execute()) {
            sendJsonResponse(true, 'Product updated successfully');
        } else {
            sendJsonResponse(false, 'Failed to update product: ' . $stmt->error);
        }

        $stmt->close();
    } else {
        sendJsonResponse(false, 'All fields are required and price must be numeric');
    }
} else {
    sendJsonResponse(false, 'Invalid request method or missing product ID');
}

mysqli_close($conn);
?>