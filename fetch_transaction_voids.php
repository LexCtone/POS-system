<?php
// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Set the content type to JSON
header('Content-Type: application/json');

// Function to output JSON and exit
function output_json($data) {
    echo json_encode($data);
    exit;
}

try {
    // Include your existing database connection file
    require_once 'connect.php';

    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';

    // Determine which query to run based on the filename
    $currentFile = basename($_SERVER['PHP_SELF']);
    if ($currentFile === 'fetch_item_voids.php') {
        $query = "SELECT iv.id, iv.sale_id, iv.product_id, iv.void_quantity, iv.cancelled_by, iv.reason, iv.add_to_inventory, iv.void_date, 
                         p.description, p.barcode, s.price, s.invoice, u.username as cancelled_by_username
                  FROM item_voids iv
                  LEFT JOIN products p ON iv.product_id = p.id
                  LEFT JOIN sales s ON iv.sale_id = s.id
                  LEFT JOIN accounts u ON iv.cancelled_by = u.id
                  WHERE 1=1";
    } elseif ($currentFile === 'fetch_transaction_voids.php') {
        $query = "SELECT * FROM transaction_voids WHERE 1=1";
    } else {
        throw new Exception("Invalid file access");
    }

    if ($startDate && $endDate) {
        $query .= " AND void_date BETWEEN ? AND ?";
    }

    $query .= " ORDER BY void_date DESC";

    $stmt = $conn->prepare($query);

    if ($startDate && $endDate) {
        $stmt->bind_param("ss", $startDate, $endDate);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conn->close();

    output_json($data);

} catch (Exception $e) {
    output_json(['error' => $e->getMessage()]);
}