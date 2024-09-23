<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'connect.php';

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

// Get query parameters
$barcode = $_GET['barcode'] ?? '';
$name = $_GET['name'] ?? '';
$vendor = $_GET['vendor'] ?? '';

// Query to fetch products
$query = "SELECT id, Barcode, Description, Price, Quantity, Category FROM products WHERE 1";
$params = [];
$types = '';

if ($barcode) {
    $query .= " AND Barcode = ?";
    $params[] = $barcode;
    $types .= 's';
}

if ($name) {
    $query .= " AND Description LIKE ?";
    $params[] = "%$name%";
    $types .= 's';
}

if ($vendor) {
    $query .= " AND Vendor LIKE ?";
    $params[] = "%$vendor%";
    $types .= 's';
}

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['error' => 'Failed to prepare statement.']);
    exit();
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Return an empty array if no products found
if (empty($products)) {
    echo json_encode([]);
} else {
    echo json_encode($products);
}

$stmt->close();
$conn->close();
?>
