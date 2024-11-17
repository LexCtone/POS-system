<?php
// Enable error reporting for debugging
ini_set('display_errors', 0); // Do not display errors in HTML
ini_set('log_errors', 1); 
ini_set('error_log', __DIR__ . '/void_transaction_custom_log.txt'); // Log errors to custom file
error_reporting(E_ALL);

// Include the connect.php file for database connection (using MySQLi)
include '../connect.php';

header("Content-Type: application/json");

try {
    // Check if the stored procedure exists and drop it if it does
    $procedureExistsStmt = $conn->query("SHOW PROCEDURE STATUS WHERE Name = 'void_transaction'");
    if ($procedureExistsStmt->num_rows == 0) {
        // Only create the procedure if it doesn't exist
        $create_procedure = "
        CREATE PROCEDURE void_transaction(
            IN p_invoice VARCHAR(50),
            IN p_total_amount DECIMAL(10, 2),
            IN p_void_by VARCHAR(50),
            IN p_reason TEXT
        )
        BEGIN
            -- Step 1: Insert into transaction_voids with explicit collation
            INSERT INTO transaction_voids (invoice, total_amount, void_by, reason)
            VALUES (p_invoice COLLATE utf8mb4_general_ci, p_total_amount, p_void_by COLLATE utf8mb4_general_ci, p_reason COLLATE utf8mb4_general_ci);
            -- Log debug message
            INSERT INTO debug_logs (log_message) VALUES ('Inserted into transaction_voids');
            
            -- Step 2: Restore product quantities in inventory with explicit collation
            UPDATE products p
            JOIN sales s ON p.barcode = s.barcode COLLATE utf8mb4_general_ci
            SET p.Quantity = p.Quantity + s.quantity
            WHERE s.invoice = p_invoice COLLATE utf8mb4_general_ci;
            -- Log debug message
            INSERT INTO debug_logs (log_message) VALUES ('Updated product quantities in products table');
        
            -- Step 3: Update sales status to 'voided' with explicit collation
            UPDATE sales
            SET status = 'voided'
            WHERE invoice = p_invoice COLLATE utf8mb4_general_ci;
            -- Log debug message
            INSERT INTO debug_logs (log_message) VALUES ('Updated sales status to voided');
        
            -- Step 4: Set quantity to 0 in sales with explicit collation
            UPDATE sales
            SET quantity = 0
            WHERE invoice = p_invoice COLLATE utf8mb4_general_ci;
            -- Log debug message
            INSERT INTO debug_logs (log_message) VALUES ('Set sales quantity to 0');
        END
        ";
        
        if (!$conn->query($create_procedure)) {
            throw new Exception("Failed to create stored procedure: " . $conn->error);
        }
    }

    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Received data: " . json_encode($data) . "\n", FILE_APPEND);

    // Validate required fields
    $required_fields = ['invoice', 'totalAmount', 'voidBy', 'cancelReason'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || is_array($data[$field])) {
            throw new Exception("Invalid or missing required field: $field");
        }
    }

    // Prepare and execute the stored procedure
    $stmt = $conn->prepare("CALL void_transaction(?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param('sdss', $data['invoice'], $data['totalAmount'], $data['voidBy'], $data['cancelReason']);

    // Begin transaction
    $conn->begin_transaction();
    try {
        $stmt->execute();
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Transaction voided successfully"]);
    } catch (Exception $e) {
        $conn->rollback();
        file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "An error occurred while processing the transaction."]);
    }

} catch (Exception $e) {
    file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
