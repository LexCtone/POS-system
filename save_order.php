<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'connect.php';

// Get JSON data from request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate data
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert purchase order header
    $stmt = $conn->prepare("INSERT INTO purchase_orders (po_number, order_date, vendor_id, delivery_date, total_amount) 
                            VALUES (?, ?, ?, ?, ?)");
    
    // Calculate total amount
    $totalAmount = array_reduce($data['items'], function($carry, $item) {
        return $carry + floatval($item['total']);
    }, 0);

    $stmt->bind_param("ssiss", 
        $data['poNumber'],
        $data['orderDate'],
        $data['vendor'],
        $data['deliveryDate'],
        $totalAmount
    );
    $stmt->execute();

    $poId = $conn->insert_id;

    // Insert purchase order items
    $stmt = $conn->prepare("INSERT INTO purchase_order_items (po_id, product_id, quantity, unit_price, total_price) 
                            VALUES (?, ?, ?, ?, ?)");

    foreach ($data['items'] as $item) {
        $stmt->bind_param("iiddd",
            $poId,
            $item['product_id'],
            $item['quantity'],
            $item['cost_price'],
            $item['total']
        );
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'po_id' => $poId]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback(); // Simply call rollback without checking inTransaction()
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close the database connection
$conn->close();
?>
