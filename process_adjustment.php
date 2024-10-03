<?php
// process_adjustment.php

session_start();
include 'connect.php';

header('Content-Type: application/json');

function sendResponse($status, $message, $data = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . "process_adjustment.php: " . $message . "\n", 3, "error.log");
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    logError("Invalid request method: " . $_SERVER["REQUEST_METHOD"]);
    sendResponse('error', 'Invalid request method.');
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$adjustment_quantity = filter_input(INPUT_POST, 'adjustment_quantity', FILTER_VALIDATE_INT);
$adjustment_reason = filter_input(INPUT_POST, 'adjustment_reason', FILTER_SANITIZE_STRING);
$barcode = filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING);
$reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING);

logError("Received data - product_id: $product_id, adjustment_quantity: $adjustment_quantity, adjustment_reason: $adjustment_reason, barcode: $barcode, reference: $reference");

if (!$product_id || $adjustment_quantity === null || !$adjustment_reason || !$barcode || !$reference) {
    logError("Invalid input. product_id: $product_id, adjustment_quantity: $adjustment_quantity, adjustment_reason: $adjustment_reason, barcode: $barcode, reference: $reference");
    sendResponse('error', 'Invalid input. Please check all fields.');
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->begin_transaction();

    // Get current product data
    $get_product_sql = "SELECT id, Quantity FROM products WHERE id = ? AND Barcode = ?";
    $get_product_stmt = $conn->prepare($get_product_sql);
    if (!$get_product_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $get_product_stmt->bind_param("is", $product_id, $barcode);
    if (!$get_product_stmt->execute()) {
        throw new Exception("Execute failed: " . $get_product_stmt->error);
    }
    $product_result = $get_product_stmt->get_result();
    $product_row = $product_result->fetch_assoc();

    if (!$product_row) {
        throw new Exception("Product not found.");
    }

    $current_quantity = $product_row['Quantity'];

    // Calculate new quantity
    $new_quantity = $current_quantity + $adjustment_quantity;

    if ($new_quantity < 0) {
        throw new Exception("Adjustment would result in negative stock.");
    }

    // Update product quantity
    $update_product_sql = "UPDATE products SET Quantity = ?, last_update = CURRENT_TIMESTAMP WHERE id = ?";
    $update_product_stmt = $conn->prepare($update_product_sql);
    if (!$update_product_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $update_product_stmt->bind_param("ii", $new_quantity, $product_id);
    if (!$update_product_stmt->execute()) {
        throw new Exception("Failed to update product quantity: " . $update_product_stmt->error);
    }

    // Insert into stock_adjustment
    $insert_adjustment_sql = "INSERT INTO stock_adjustment (product_id, barcode, adjustment_type, original_quantity, adjustment_quantity, new_quantity, running_balance, adjustment_reason, adjusted_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_adjustment_stmt = $conn->prepare($insert_adjustment_sql);
    if (!$insert_adjustment_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $adjustment_type = $adjustment_quantity > 0 ? 'increase' : 'decrease';
    $adjusted_by = $_SESSION['username'] ?? 'Unknown';
    $insert_adjustment_stmt->bind_param("issiiiiss", $product_id, $barcode, $adjustment_type, $current_quantity, $adjustment_quantity, $new_quantity, $new_quantity, $adjustment_reason, $adjusted_by);
    if (!$insert_adjustment_stmt->execute()) {
        throw new Exception("Failed to record stock adjustment: " . $insert_adjustment_stmt->error);
    }

    // Commit transaction
    $conn->commit();

    sendResponse('success', 'Stock adjustment processed successfully.', [
        'new_quantity' => $new_quantity
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    logError($e->getMessage());
    sendResponse('error', 'Error processing adjustment: ' . $e->getMessage());
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>