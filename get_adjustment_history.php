<?php
session_start();
include 'connect.php';

header('Content-Type: application/json');

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$show_archived = isset($_GET['show_archived']) ? filter_var($_GET['show_archived'], FILTER_VALIDATE_BOOLEAN) : false;

if ($show_archived) {
    $sql = "SELECT * FROM archived_stock_adjustment ORDER BY adjustment_date DESC";
} else {
    $sql = "SELECT * FROM stock_adjustment ORDER BY adjustment_date DESC";
}

$result = $conn->query($sql);

$adjustments = [];
while ($row = $result->fetch_assoc()) {
    $adjustments[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $adjustments]);

$conn->close();
?>