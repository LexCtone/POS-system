<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connect.php';

$poNumber = $_GET['po'] ?? '';

if (empty($poNumber)) {
    echo json_encode(['error' => 'No PO number provided']);
    exit;
}

try {
    // Updated SQL query to include p.Description as description
    $sql = "
        SELECT po.*, v.vendor, p.Description AS description, poi.quantity, p.Barcode AS barcode
        FROM purchase_orders po 
        JOIN vendor v ON po.vendor_id = v.id
        JOIN purchase_order_items poi ON po.id = poi.po_id 
        JOIN products p ON poi.product_id = p.id
        WHERE po.po_number = ?
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $poNumber);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }
    
    $poDetails = [
        'vendor' => '',
        'items' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        if (empty($poDetails['vendor'])) {
            $poDetails['vendor'] = $row['vendor'];
        }
        $poDetails['items'][] = [
            'description' => $row['description'],  // Use 'description' instead of 'product'
            'quantity' => $row['quantity'],
            'barcode' => $row['barcode']
        ];
    }
    
    if (empty($poDetails['items'])) {
        echo json_encode(['error' => 'No PO details found for the given PO number']);
    } else {
        echo json_encode($poDetails);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
