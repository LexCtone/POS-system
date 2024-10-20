<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include 'connect.php'; // Ensure this path is correct and the file exists

// Check if the database connection was successful
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get the vendor ID from the request
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $vendorId = intval($_GET['id']);

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("SELECT contact, address FROM vendor WHERE id = ?");
    if ($stmt === false) {
        echo json_encode(['error' => 'Failed to prepare SQL statement']);
        exit;
    }

    $stmt->bind_param('i', $vendorId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the vendor details
        $vendor = $result->fetch_assoc();
        echo json_encode($vendor);
    } else {
        echo json_encode(['error' => 'Vendor not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'No vendor ID provided']);
}

$conn->close();
?>
