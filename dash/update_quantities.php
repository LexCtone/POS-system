<?php
// Disable error reporting for production
error_reporting(E_ALL); // Enable error reporting for debugging
ini_set('display_errors', 0); // Don't display errors directly
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', 'error_log.txt'); // Set path to error log file

// Include database connection
require_once '../connect.php'; // Adjust path as needed

// Function to log errors
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'error_log.txt');
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
$data = json_decode($json, true);

// Check if data is valid
if (!$data || !is_array($data)) {
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

    // Generate invoice number (current date + 4 random digits)
    $invoice = date('Ymd') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

    foreach ($data as $item) {
        // Validate item data
        if (!isset($item['product_id'], $item['barcode'], $item['description'], $item['price'], $item['quantity'], $item['discount_amount'], $item['total'])) {
            throw new Exception("Invalid item data: " . json_encode($item));
        }

        // Update product quantity
        $update_product_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        if (!$update_product_stmt->execute()) {
            throw new Exception("Error updating product quantity: " . $update_product_stmt->error);
        }

        // Insert sale record
        $insert_sale_stmt->bind_param("sssdidd", 
            $invoice,
            $item['barcode'],
            $item['description'],
            $item['price'],
            $item['quantity'],
            $item['discount_amount'],
            $item['total']
        );
        if (!$insert_sale_stmt->execute()) {
            throw new Exception("Error inserting sale record: " . $insert_sale_stmt->error);
        }
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Quantities updated and sales recorded successfully', 'invoice' => $invoice]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    logError("Transaction failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}

// Close prepared statements
if (isset($update_product_stmt)) {
    $update_product_stmt->close();
}
if (isset($insert_sale_stmt)) {
    $insert_sale_stmt->close();
}

// Close database connection
$conn->close();