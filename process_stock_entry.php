<?php
header('Content-Type: application/json');

// Database connection
include 'connect.php'; // Ensure this path is correct

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log('Connection failed: ' . $conn->connect_error);
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

$response = [];

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
            $vendorNameQuery->bind_param('i', $vendorId);
            $vendorNameQuery->execute();
            $vendorNameQuery->bind_result($vendorName);
            $vendorNameQuery->fetch();
            $vendorNameQuery->close();

            if (!$vendorName) {
                $response['error'] = 'Vendor not found.';
                error_log('Vendor not found. ID: ' . $vendorId);
            } else {
                // Insert into stock_in_history
                $stmt = $conn->prepare("INSERT INTO stock_in_history (reference, Barcode, Description, Quantity, stock_in_date, stock_in_by, vendor) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    $response['error'] = 'Prepare failed: ' . htmlspecialchars($conn->error);
                    error_log('Prepare failed: ' . htmlspecialchars($conn->error));
                } else {
                    $hasError = false;

                    foreach ($products as $product) {
                        $Barcode = $product['Barcode'] ?? '';
                        $description = $product['description'] ?? '';
                        $quantity = $product['quantity'] ?? 0; // Default to 0 if not set

                        if (empty($Barcode) || empty($description) || $quantity === '') {
                            $response['error'] = 'Product details cannot be null. Barcode: ' . htmlspecialchars($Barcode) . ', Description: ' . htmlspecialchars($description) . ', Quantity: ' . htmlspecialchars($quantity);
                            error_log('Product details error. Barcode: ' . htmlspecialchars($Barcode) . ', Description: ' . htmlspecialchars($description) . ', Quantity: ' . htmlspecialchars($quantity));
                            $hasError = true;
                            break;
                        }

                        // Insert into stock_in_history
                        $stmt->bind_param('sssssss', $referenceNo, $Barcode, $description, $quantity, $stockInDate, $stockInBy, $vendorName);
                        if (!$stmt->execute()) {
                            $response['error'] = 'Error inserting product: ' . htmlspecialchars($stmt->error);
                            error_log('Error inserting product: ' . htmlspecialchars($stmt->error));
                            $hasError = true;
                            break;
                        }

                        // Update the products table with the new quantity
                        $updateStmt = $conn->prepare("UPDATE products SET Quantity = Quantity + ? WHERE Barcode = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param('is', $quantity, $Barcode);
                            if (!$updateStmt->execute()) {
                                $response['error'] = 'Error updating product quantity: ' . htmlspecialchars($updateStmt->error);
                                error_log('Error updating product quantity: ' . htmlspecialchars($updateStmt->error));
                                $hasError = true;
                                break;
                            }
                            $updateStmt->close();
                        } else {
                            $response['error'] = 'Failed to prepare update statement for products.';
                            error_log('Failed to prepare update statement for products.');
                            $hasError = true;
                            break;
                        }
                    }

                    if (!$hasError) {
                        $response['success'] = 'Stock entry saved successfully and products updated!';
                    }
                    $stmt->close();
                }
            }
        } else {
            $response['error'] = 'All fields are required, and there should be at least one product.';
            error_log('Missing fields or empty products.');
        }
    }
} else {
    $response['error'] = 'Invalid request method';
    error_log('Invalid request method.');
}

$conn->close();
echo json_encode($response);
?>
