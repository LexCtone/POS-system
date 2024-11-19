<?php
// Prevent any output before our JSON response
ob_start();

// Error handling to catch any PHP errors
function exception_error_handler($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

try {
    include 'connect.php';

    header('Content-Type: application/json');

    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $barcode = trim($_POST['barcode']);
        $generatedBarcode = trim($_POST['generatedBarcode']);
        $description = trim($_POST['description'] ?? '');
        $brand = trim($_POST['brand']);
        $category = trim($_POST['category']);
        $price = trim($_POST['price']);
        $cost_price = trim($_POST['cost_price']);
        $vendor_id = trim($_POST['vendor']);
        $quantity = 0; // Default quantity

        // Validate required fields
        if (
            empty($barcode) || empty($generatedBarcode) || empty($brand) || 
            empty($category) || empty($vendor_id) || !is_numeric($price) || 
            !is_numeric($cost_price) || !is_numeric($vendor_id)
        ) {
            throw new Exception('Required fields are missing or invalid');
        }

        // Begin transaction
        $conn->begin_transaction();

        // Prepare and execute the statement
        $stmt = $conn->prepare(
            "INSERT INTO products 
                (Barcode, GeneratedBarcode, Description, Brand, Category, Price, cost_price, Quantity, vendor_id) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssddii', $barcode, $generatedBarcode, $description, $brand, $category, $price, $cost_price, $quantity, $vendor_id);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Commit transaction
        $conn->commit();

        // JSON response
        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully',
            'productId' => $conn->insert_id,
            'barcode' => $barcode,
            'generatedBarcode' => $generatedBarcode
        ]);

        $stmt->close();
    } else {
        throw new Exception('Invalid request method');
    }

} catch (Exception $e) {
    // Rollback transaction if an error occurs
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }

    // JSON error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }

    // Clear the output buffer and send the response
    ob_end_flush();
}
?>
