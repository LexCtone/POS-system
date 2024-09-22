<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sv_hardware_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

$response = [];

// Check if POST request and process JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['error'] = 'Invalid JSON data format.';
    } else {
        $referenceNo = $data['referenceNo'] ?? '';
        $stockInBy = $data['stockInBy'] ?? '';
        $vendorId = $data['vendor'] ?? '';
        $stockInDate = $data['stockInDate'] ?? '';
        $products = $data['products'] ?? [];

        if (!empty($referenceNo) && !empty($stockInBy) && !empty($vendorId) && !empty($stockInDate) && !empty($products)) {
            // Fetch vendor name
            $vendorNameQuery = $conn->prepare("SELECT vendor FROM vendor WHERE id = ?");
            if ($vendorNameQuery) {
                $vendorNameQuery->bind_param('i', $vendorId);
                $vendorNameQuery->execute();
                $vendorNameQuery->bind_result($vendorName);
                $vendorNameQuery->fetch();
                $vendorNameQuery->close();

                if (!$vendorName) {
                    $response['error'] = 'Vendor not found.';
                } else {
                    // Insert stock entries
                    $stmt = $conn->prepare("INSERT INTO stock_in_history (reference, Barcode, Description, Quantity, stock_in_date, stock_in_by, vendor) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) {
                        $response['error'] = 'Prepare failed: ' . htmlspecialchars($conn->error);
                    } else {
                        $hasError = false;

                        foreach ($products as $product) {
                            $Barcode = $product['Barcode'] ?? '';
                            $description = $product['description'] ?? '';
                            $quantity = $product['quantity'] ?? '';
                        
                            if (empty($Barcode) || empty($description) || empty($quantity)) {
                                $response['error'] = 'Product details cannot be null. Barcode: ' . htmlspecialchars($Barcode) . ', Description: ' . htmlspecialchars($description) . ', Quantity: ' . htmlspecialchars($quantity);
                                $hasError = true;
                                break;
                            }

                            $stmt->bind_param('sssssss', $referenceNo, $Barcode, $description, $quantity, $stockInDate, $stockInBy, $vendorName);
                            if (!$stmt->execute()) {
                                $response['error'] = 'Error inserting product: ' . htmlspecialchars($stmt->error);
                                $hasError = true;
                                break;
                            }
                        }

                        if (!$hasError) {
                            $response['success'] = 'Stock entry saved successfully!';
                        }
                        $stmt->close();
                    }
                }
            } else {
                $response['error'] = 'Failed to prepare vendor name query.';
            }
        } else {
            $response['error'] = 'All fields are required, and there should be at least one product.';
        }
    }
} else {
    $response['error'] = 'Invalid request method';
}

$conn->close();
echo json_encode($response);
?>
