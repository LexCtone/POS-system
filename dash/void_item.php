<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// Include the connect.php file for database connection
include '../connect.php';

try {
    // Check if the stored procedure exists and create it if not
    $procedureExistsStmt = $conn->query("SHOW PROCEDURE STATUS WHERE Name = 'void_item'");
    if ($procedureExistsStmt->num_rows == 0) {
        // Create the procedure if it does not exist
        $create_procedure = "
        CREATE PROCEDURE void_item(
            IN p_sale_id INT,
            IN p_product_code VARCHAR(50),
            IN p_void_quantity INT,
            IN p_void_by VARCHAR(50),
            IN p_cancelled_by VARCHAR(50),
            IN p_reason TEXT,
            IN p_add_to_inventory BOOLEAN
        )
        BEGIN
            DECLARE v_current_quantity INT;
            DECLARE v_unit_price DECIMAL(10, 2);
            DECLARE v_product_id INT;

            START TRANSACTION;

            -- Get the product_id based on the product_code
            SELECT id INTO v_product_id
            FROM products
            WHERE Barcode = p_product_code;

            SELECT quantity, price INTO v_current_quantity, v_unit_price
            FROM sales
            WHERE id = p_sale_id;

            -- Check if the void quantity exceeds the current quantity
            IF p_void_quantity > v_current_quantity THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Void quantity exceeds current quantity';
            END IF;

            -- Insert void entry into the item_voids table
            INSERT INTO item_voids (sale_id, product_id, void_quantity, void_by, cancelled_by, reason, add_to_inventory)
            VALUES (p_sale_id, v_product_id, p_void_quantity, p_void_by, p_cancelled_by, p_reason, p_add_to_inventory);

            -- Update sales quantity and total
            UPDATE sales
            SET quantity = quantity - p_void_quantity,
                total = (quantity - p_void_quantity) * v_unit_price  
            WHERE id = p_sale_id;

            -- Add back to inventory if requested
            IF p_add_to_inventory THEN
                UPDATE products
                SET Quantity = Quantity + p_void_quantity
                WHERE id = v_product_id;
            END IF;
                
            -- Mark the sale as voided if quantity is zero
            IF (v_current_quantity - p_void_quantity) = 0 THEN
                UPDATE sales
                SET status = 'voided'
                WHERE id = p_sale_id;
            END IF;

            COMMIT;
        END
        ";

        $conn->query($create_procedure);
    }

    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);

    $required_fields = ['saleId', 'productCode', 'cancelQty', 'voidBy', 'cancelledBy', 'cancelReason', 'addToInventory'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Invalid or missing required field: $field");
        }
    }
    
    // Prepare and bind
    $stmt = $conn->prepare("CALL void_item(?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    // Convert addToInventory to boolean
    $addToInventory = $data['addToInventory'] === true || $data['addToInventory'] === 'true' || $data['addToInventory'] === 1;
    
    // Bind the parameters
    $stmt->bind_param('isiissi', 
        $data['saleId'], 
        $data['productCode'], 
        $data['cancelQty'], 
        $data['voidBy'], 
        $data['cancelledBy'], 
        $data['cancelReason'], 
        $addToInventory
    );

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    echo json_encode(["success" => true, "message" => "Item voided successfully"]);
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>