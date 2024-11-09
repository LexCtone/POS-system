<?php
include 'connect.php'; // Ensure this file includes your database connection

header('Content-Type: application/json');

$sql = "SELECT po_number FROM purchase_orders"; // Adjust this query if needed
$result = $conn->query($sql);

$purchaseOrders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $purchaseOrders[] = $row['po_number'];
    }
}

$conn->close();
echo json_encode($purchaseOrders);
