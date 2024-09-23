<?php
// Disable error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

// Include database connection
require_once '../connect.php';

// Function to log errors and debug information
function logMessage($message, $type = 'ERROR') {
    error_log(date('[Y-m-d H:i:s] ') . "[$type] " . $message . "\n", 3, 'error_log.txt');
}

// Ensure we're only outputting JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the raw POST data
$json = file_get_contents('php://input');
logMessage("Received data: " . $json, 'DEBUG');
$data = json_decode($json, true);

// Check if data is valid
if (!$data || !isset($data['sales']) || !is_array($data['sales'])) {
    logMessage("Invalid data format: " . print_r($data, true));
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Prepare statements
    $update_product_stmt = $conn->prepare("UPDATE products SET Quantity = Quantity - ? WHERE id = ?");
    if (!$update_product_stmt) {
        throw new Exception("Error preparing update_product_stmt: " . $conn->error);
    }

    $insert_sale_stmt = $conn->prepare("INSERT INTO sales (invoice, barcode, description, price, quantity, discount_amount, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$insert_sale_stmt) {
        throw new Exception("Error preparing insert_sale_stmt: " . $conn->error);
    }

    foreach ($data['sales'] as $item) {
        // Validate item data
        if (!isset($item['product_id'], $item['barcode'], $item['description'], $item['price'], $item['quantity'], $item['discount_amount'], $item['total'])) {
            logMessage("Invalid item data: " . json_encode($item));
            throw new Exception("Invalid item data: " . json_encode($item));
        }

        // Update product quantity
        $update_product_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        if (!$update_product_stmt->execute()) {
            logMessage("Error updating product quantity: " . $update_product_stmt->error);
            throw new Exception("Error updating product quantity: " . $update_product_stmt->error);
        }
        logMessage("Updated quantity for product ID {$item['product_id']}: -{$item['quantity']}", 'DEBUG');

        // Insert sale record
        $insert_sale_stmt->bind_param("sssdidd", 
            $data['invoice'],
            $item['barcode'],
            $item['description'],
            $item['price'],
            $item['quantity'],
            $item['discount_amount'],
            $item['total']
        );
        if (!$insert_sale_stmt->execute()) {
            logMessage("Error inserting sale record: " . $insert_sale_stmt->error);
            throw new Exception("Error inserting sale record: " . $insert_sale_stmt->error);
        }
        logMessage("Inserted sale record for invoice {$data['invoice']}", 'DEBUG');
    }

    // Commit transaction
    $conn->commit();
    logMessage("Transaction committed successfully", 'DEBUG');
    echo json_encode(['success' => true, 'message' => 'Quantities updated and sales recorded successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    logMessage("Transaction failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}

// Close prepared statements
$update_product_stmt->close();
$insert_sale_stmt->close();

// Close database connection
$conn->close();