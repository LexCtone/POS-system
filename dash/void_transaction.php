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
    if ($procedureExistsStmt->num_rows > 0) {
        $conn->query("DROP PROCEDURE IF EXISTS void_transaction");
        file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Dropped existing procedure.\n", FILE_APPEND);
    }

    // Create the updated procedure
    $create_procedure = "
CREATE PROCEDURE void_transaction(
    IN p_invoice VARCHAR(50),
    IN p_total_amount DECIMAL(10, 2),
    IN p_void_by VARCHAR(50),
    IN p_reason TEXT
)
BEGIN
    -- Step 1: Insert into transaction_voids
    INSERT INTO transaction_voids (invoice, total_amount, void_by, reason)
    VALUES (p_invoice, p_total_amount, p_void_by, p_reason);
    INSERT INTO debug_logs (log_message) VALUES ('Inserted into transaction_voids');

    -- Step 2: Restore product quantities in inventory
    UPDATE products p
    JOIN sales s ON p.barcode COLLATE utf8mb4_general_ci = s.barcode COLLATE utf8mb4_general_ci
    SET p.Quantity = p.Quantity + s.quantity
    WHERE s.invoice COLLATE utf8mb4_general_ci = p_invoice COLLATE utf8mb4_general_ci;
    INSERT INTO debug_logs (log_message) VALUES ('Updated product quantities in products table');

    -- Step 3: Update sales status to 'voided'
    UPDATE sales
    SET status = 'voided'
    WHERE invoice COLLATE utf8mb4_general_ci = p_invoice COLLATE utf8mb4_general_ci;
    INSERT INTO debug_logs (log_message) VALUES ('Updated sales status to voided');

    -- Step 4: Set quantity to 0 in sales
    UPDATE sales
    SET quantity = 0
    WHERE invoice COLLATE utf8mb4_general_ci = p_invoice COLLATE utf8mb4_general_ci;
    INSERT INTO debug_logs (log_message) VALUES ('Set sales quantity to 0');
END
    ";

    if (!$conn->query($create_procedure)) {
        throw new Exception("Failed to create stored procedure: " . $conn->error);
    }
    file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Stored procedure created.\n", FILE_APPEND);

    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Received data: " . json_encode($data) . "\n", FILE_APPEND);

    // Validate required fields and ensure they are not arrays
    $required_fields = ['invoice', 'totalAmount', 'voidBy', 'cancelReason'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || is_array($data[$field])) {
            throw new Exception("Invalid or missing required field: $field");
        }
    }
    file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Data validated successfully.\n", FILE_APPEND);

    // Prepare and bind
    $stmt = $conn->prepare("CALL void_transaction(?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Statement prepared successfully.\n", FILE_APPEND);

    // Bind the parameters
    $stmt->bind_param('sdss', $data['invoice'], $data['totalAmount'], $data['voidBy'], $data['cancelReason']);

    // Execute the statement
    if (!$stmt->execute()) {
        // Log any SQL errors from the stored procedure
        $errorResult = $conn->query("SHOW ERRORS")->fetch_assoc();
        file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Stored Procedure Error: " . json_encode($errorResult) . "\n", FILE_APPEND);
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Statement executed successfully.\n", FILE_APPEND);

    echo json_encode(["success" => true, "message" => "Transaction voided successfully"]);
    
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/void_transaction_custom_log.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
