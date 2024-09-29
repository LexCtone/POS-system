<?php
include 'connect.php';

$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$soldBy = isset($_GET['soldBy']) ? $_GET['soldBy'] : '';

$query = "SELECT * FROM sales WHERE 1=1";
$params = [];

if (!empty($startDate)) {
    $query .= " AND DATE(sale_date) >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $query .= " AND DATE(sale_date) <= ?";
    $params[] = $endDate;
}

if (!empty($soldBy)) {
    $query .= " AND cashier_name = ?";
    $params[] = $soldBy;
}

$query .= " ORDER BY sale_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$sales = [];
$totalActiveSales = 0;
$totalVoidedSales = 0;
$voidedTransactions = [];

while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
    if ($row['status'] === 'Voided') {
        $voidedTransactions[] = $row;
        $totalVoidedSales += $row['total'];
    } else {
        $totalActiveSales += $row['total'];
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'sales' => $sales,
    'totalActiveSales' => $totalActiveSales,
    'totalVoidedSales' => $totalVoidedSales,
    'voidedTransactions' => $voidedTransactions
]);