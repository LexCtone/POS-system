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
        // Process each sale
        foreach ($sales as $sale) {
            // Validate each sale entry
            if (!isset($sale['barcode'], $sale['description'], $sale['price'], $sale['quantity'], $sale['discount_amount'], $sale['total'])) {
                throw new Exception('Invalid sale entry: ' . json_encode($sale));
            }

            // Escape and validate the individual sale data
            $barcode = $conn->real_escape_string($sale['barcode']);
            $description = $conn->real_escape_string($sale['description']);
            $price = floatval($sale['price']);
            $quantity = intval($sale['quantity']);
            $discount_amount = floatval($sale['discount_amount']);
            $total = floatval($sale['total']);

            // Insert into sales table
            $query = "INSERT INTO sales (invoice, barcode, description, price, quantity, discount_amount, total, sale_date, cashier_name)
                      VALUES ('$invoice', '$barcode', '$description', $price, $quantity, $discount_amount, $total, NOW(), '$cashier_name')";
    
            logError('Executing query: ' . $query);

            if (!$conn->query($query)) {
                throw new Exception('Error inserting sale: ' . $conn->error);
            }
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction saved successfully']);
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $conn->rollback();
        logError('Error in transaction: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    // Close connection
    $conn->close();
} else {
    logError('Invalid input: ' . print_r($data, true));
    echo json_encode(['success' => false, 'message' => 'Invalid input structure']);
}
?>