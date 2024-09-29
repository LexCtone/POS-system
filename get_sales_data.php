<?php
// get_sales_data.php

header('Content-Type: application/json');

include 'connect.php';

$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'total_amount';
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'DESC';

// Validate and sanitize inputs
$sortBy = in_array($sortBy, ['quantity', 'total_amount']) ? $sortBy : 'total_amount';
$sortOrder = in_array($sortOrder, ['ASC', 'DESC']) ? $sortOrder : 'DESC';

$sql = "SELECT barcode, description, SUM(quantity) AS total_qty, SUM(total) AS total_sales,
               MIN(sale_date) AS first_sale_date, MAX(sale_date) AS last_sale_date
        FROM sales 
        WHERE status != 'voided'";  // Add this condition to exclude voided items

if ($startDate && $endDate) {
    $sql .= " AND sale_date BETWEEN ? AND ?";
}

$sql .= " GROUP BY barcode, description
          ORDER BY " . ($sortBy == 'quantity' ? 'total_qty' : 'total_sales') . " $sortOrder
          LIMIT 10";

$stmt = $conn->prepare($sql);

if ($startDate && $endDate) {
    $stmt->bind_param("ss", $startDate, $endDate);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
    
$stmt->close();
$conn->close();
?>