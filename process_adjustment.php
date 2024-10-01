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
$reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING);

if (!$product_id || $adjustment_quantity === null || !$reference) {
    logError("Invalid input. product_id: $product_id, adjustment_quantity: $adjustment_quantity, reference: $reference");
    sendResponse('error', 'Invalid input. Please check all fields.');
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->begin_transaction();

    // Get current product and stock_in_history data
    $get_data_sql = "SELECT p.Quantity as current_quantity, p.Barcode, sh.quantity as history_quantity, sh.id as stock_in_id
                     FROM products p
                     LEFT JOIN stock_in_history sh ON p.Barcode = sh.Barcode AND sh.reference = ?
                     WHERE p.id = ?";
    $get_data_stmt = $conn->prepare($get_data_sql);
    if (!$get_data_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $get_data_stmt->bind_param("si", $reference, $product_id);
    if (!$get_data_stmt->execute()) {
        throw new Exception("Execute failed: " . $get_data_stmt->error);
    }
    $data_result = $get_data_stmt->get_result();
    $data_row = $data_result->fetch_assoc();

    if (!$data_row) {
        throw new Exception("Product or stock history not found.");
    }

    $current_quantity = $data_row['current_quantity'];
    $history_quantity = $data_row['history_quantity'];
    $stock_in_id = $data_row['stock_in_id'];
    $barcode = $data_row['Barcode'];

    if (!$stock_in_id) {
        throw new Exception("No matching stock_in_history record found.");
    }

    // Calculate new quantities
    $new_history_quantity = $history_quantity + $adjustment_quantity;
    $quantity_difference = $new_history_quantity - $history_quantity;
    $new_current_quantity = $current_quantity + $quantity_difference;

    // Update product quantity
    $update_product_sql = "UPDATE products SET Quantity = ?, last_update = CURRENT_TIMESTAMP WHERE id = ?";
    $update_product_stmt = $conn->prepare($update_product_sql);
    if (!$update_product_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $update_product_stmt->bind_param("ii", $new_current_quantity, $product_id);
    if (!$update_product_stmt->execute()) {
        throw new Exception("Failed to update product quantity: " . $update_product_stmt->error);
    }

    if ($update_product_stmt->affected_rows === 0) {
        throw new Exception("No rows affected when updating product quantity.");
    }

    // Update stock_in_history
    $update_history_sql = "UPDATE stock_in_history SET quantity = ?, stock_in_date = CURRENT_DATE WHERE id = ?";
    $update_history_stmt = $conn->prepare($update_history_sql);
    if (!$update_history_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $update_history_stmt->bind_param("ii", $new_history_quantity, $stock_in_id);
    if (!$update_history_stmt->execute()) {
        throw new Exception("Failed to update stock history: " . $update_history_stmt->error);
    }

    if ($update_history_stmt->affected_rows === 0) {
        throw new Exception("No rows affected when updating stock history.");
    }

    // Insert into stock_adjustment
    $insert_adjustment_sql = "INSERT INTO stock_adjustment (product_id, stock_in_history_id, adjustment_type, original_quantity, adjustment_quantity, new_quantity, adjustment_reason, adjusted_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_adjustment_stmt = $conn->prepare($insert_adjustment_sql);
    if (!$insert_adjustment_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $adjustment_type = $adjustment_quantity > 0 ? 'increase' : 'decrease';
    $adjusted_by = $_SESSION['username'] ?? 'Unknown';
    $insert_adjustment_stmt->bind_param("iisiiiis", $product_id, $stock_in_id, $adjustment_type, $history_quantity, $adjustment_quantity, $new_history_quantity, $adjustment_reason, $adjusted_by);
    if (!$insert_adjustment_stmt->execute()) {
        throw new Exception("Failed to record stock adjustment: " . $insert_adjustment_stmt->error);
    }

    if ($insert_adjustment_stmt->affected_rows === 0) {
        throw new Exception("No rows affected when inserting stock adjustment.");
    }

    // Commit transaction
    $conn->commit();

    sendResponse('success', 'Stock adjustment processed successfully.', [
        'new_current_quantity' => $new_current_quantity,
        'new_history_quantity' => $new_history_quantity
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