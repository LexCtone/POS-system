<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

function output_json($data) {
    echo json_encode($data);
    exit;
}

try {
    include 'connect.php';

    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
    $soldBy = isset($_GET['soldBy']) ? $_GET['soldBy'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    $query = "SELECT s.*, COALESCE(iv.void_quantity, 0) as void_quantity 
              FROM sales s
              LEFT JOIN item_voids iv ON s.id = iv.sale_id
              WHERE 1=1";
    $params = [];

    if (!empty($startDate)) {
        $query .= " AND DATE(s.sale_date) >= ?";
        $params[] = $startDate;
    }

    if (!empty($endDate)) {
        $query .= " AND DATE(s.sale_date) <= ?";
        $params[] = $endDate;
    }

    if (!empty($soldBy)) {
        $query .= " AND s.cashier_name = ?";
        $params[] = $soldBy;
    }

    if (!empty($status)) {
        $query .= " AND s.status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY s.sale_date DESC";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        if (!$stmt->bind_param($types, ...$params)) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception("Getting result set failed: " . $stmt->error);
    }

    $sales = [];
    $totalActiveSales = 0;
    $totalVoidedSales = 0;
    $voidedTransactions = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'voided') {
            $row['quantity'] = $row['void_quantity'];
            $voidedTransactions[] = $row;
            $totalVoidedSales += $row['total'];
        } else {
            $totalActiveSales += $row['total'];
        }
        $sales[] = $row;
    }

    $stmt->close();
    $conn->close();

    output_json([
        'sales' => $sales,
        'totalActiveSales' => $totalActiveSales,
        'totalVoidedSales' => $totalVoidedSales,
        'voidedTransactions' => $voidedTransactions
    ]);

} catch (Exception $e) {
    output_json([
        'error' => true,
        'message' => 'An error occurred while processing your request: ' . $e->getMessage()
    ]);
}