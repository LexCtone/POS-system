<?php
// save_sales.php

session_start();
header('Content-Type: application/json');

// Include the database connection
require '../connect.php'; // Adjust path as needed

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to log errors
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'error_log.txt');
}

// Check if $conn is defined and working
if (!$conn) {
    logError('Database connection not established');
    echo json_encode(['success' => false, 'message' => 'Database connection not established']);
    exit();
}

// Check if the user is logged in and is a cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    logError('Unauthorized access attempt');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$cashier_name = $_SESSION['username']; // Assuming the username is stored in the session

// Read the input data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log the received data for debugging
logError('Received data: ' . print_r($data, true));

// Check if 'invoice' and 'sales' are provided in the request
if (isset($data['invoice']) && isset($data['sales']) && is_array($data['sales'])) {
    $invoice = $conn->real_escape_string($data['invoice']);
    $sales = $data['sales'];

    // Prepare to start transaction
    $conn->begin_transaction();

    try {
        // Prepare statements
        $insert_sale_stmt = $conn->prepare("INSERT INTO sales (invoice, barcode, description, price, quantity, discount_amount, total, sale_date, cashier_name) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        $update_product_stmt = $conn->prepare("UPDATE products SET Quantity = Quantity - ? WHERE Barcode = ?");

        if (!$insert_sale_stmt || !$update_product_stmt) {
            throw new Exception("Error preparing statements: " . $conn->error);
        }

        // Process each sale
        foreach ($sales as $sale) {
            // Validate each sale entry
            if (!isset($sale['barcode'], $sale['description'], $sale['price'], $sale['quantity'], $sale['discount_amount'], $sale['total'])) {
                throw new Exception('Invalid sale entry: ' . json_encode($sale));
            }

            // Insert into sales table
            $insert_sale_stmt->bind_param("sssdidds", 
                $invoice,
                $sale['barcode'],
                $sale['description'],
                $sale['price'],
                $sale['quantity'],
                $sale['discount_amount'],
                $sale['total'],
                $cashier_name
            );

            if (!$insert_sale_stmt->execute()) {
                throw new Exception('Error inserting sale: ' . $insert_sale_stmt->error);
            }

            // Update product quantity
            $update_product_stmt->bind_param("is", $sale['quantity'], $sale['barcode']);
            if (!$update_product_stmt->execute()) {
                throw new Exception('Error updating product quantity: ' . $update_product_stmt->error);
            }

            // Log the executed queries for debugging
            logError('Executed sale insert for barcode: ' . $sale['barcode']);
            logError('Executed product update for barcode: ' . $sale['barcode']);
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction saved and quantities updated successfully']);
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $conn->rollback();
        logError('Error in transaction: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    // Close prepared statements
    $insert_sale_stmt->close();
    $update_product_stmt->close();

    // Close connection
    $conn->close();
} else {
    logError('Invalid input: ' . print_r($data, true));
    echo json_encode(['success' => false, 'message' => 'Invalid input structure']);
}
?>