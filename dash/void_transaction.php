<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// Include the connect.php file for database connection (using MySQLi)
include '../connect.php'; 

try {
    // Check if the stored procedure exists and create it if not
    $procedureExistsStmt = $conn->query("SHOW PROCEDURE STATUS WHERE Name = 'void_transaction'");
    if ($procedureExistsStmt->num_rows == 0) {
        // Create the procedure if it does not exist
        $create_procedure = "
CREATE PROCEDURE void_transaction(
    IN p_invoice VARCHAR(50),
    IN p_total_amount DECIMAL(10, 2),
    IN p_void_by VARCHAR(50),
    IN p_reason TEXT
)
BEGIN
    START TRANSACTION;

    -- Insert into transaction_voids table using the invoice
    INSERT INTO transaction_voids (invoice, total_amount, void_by, reason)
    VALUES (p_invoice, p_total_amount, p_void_by, p_reason);

    -- Update the sales status to 'voided' using invoice
    UPDATE sales
    SET status = 'voided'
    WHERE invoice = p_invoice;

    -- Set quantity to 0 in sales table
    UPDATE sales
    SET quantity = 0
    WHERE invoice = p_invoice;

    -- Restore product quantities back to inventory using barcode
    UPDATE products p
    JOIN sales s ON p.barcode = s.barcode
    SET p.Quantity = p.Quantity + s.quantity
    WHERE s.invoice = p_invoice;

    COMMIT;
END
";

        $conn->query($create_procedure);
    }

    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields and ensure they are not arrays
    $required_fields = ['invoice', 'totalAmount', 'voidBy', 'cancelReason'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || is_array($data[$field])) {
            throw new Exception("Invalid or missing required field: $field");
        }
    }

    // Prepare and bind
    $stmt = $conn->prepare("CALL void_transaction(?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind the parameters
    $stmt->bind_param('sdss', $data['invoice'], $data['totalAmount'], $data['voidBy'], $data['cancelReason']);

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    echo json_encode(["success" => true, "message" => "Transaction voided successfully"]);
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
