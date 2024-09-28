<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// Include the connect.php file for database connection
include '../connect.php'; 

try {
    // Establish a PDO connection using variables from connect.php
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure that the stored procedure exists
    $create_procedure = "
    CREATE PROCEDURE IF NOT EXISTS void_item(
        IN p_sale_id INT,
        IN p_product_id INT,
        IN p_void_quantity INT,
        IN p_void_by VARCHAR(50),
        IN p_cancelled_by VARCHAR(50),
        IN p_reason TEXT,
        IN p_add_to_inventory BOOLEAN
    )
    BEGIN
        DECLARE v_current_quantity INT;
        DECLARE v_unit_price DECIMAL(10, 2);

        START TRANSACTION;

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
        VALUES (p_sale_id, p_product_id, p_void_quantity, p_void_by, p_cancelled_by, p_reason, p_add_to_inventory);

        -- Update sales quantity and total
        UPDATE sales
        SET quantity = quantity - p_void_quantity,
            total = (quantity - p_void_quantity) * v_unit_price  
        WHERE id = p_sale_id;

        -- Add back to inventory if requested
        IF p_add_to_inventory THEN
            UPDATE products
            SET stock_quantity = stock_quantity + p_void_quantity
            WHERE id = p_product_id;
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
    $conn->exec($create_procedure);

    // Retrieve POST data
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    $required_fields = ['saleId', 'productId', 'cancelQty', 'voidBy', 'cancelledBy', 'cancelReason', 'addToInventory'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Convert addToInventory to boolean
    $addToInventory = strtolower($data['addToInventory']) === 'yes';

    // Log the values being sent to the stored procedure
    error_log("Voiding Product: ID = {$data['productId']}, Quantity = {$data['cancelQty']}, Add to Inventory = " . ($addToInventory ? 'true' : 'false'));

    // Prepare the stored procedure call
    $stmt = $conn->prepare("
        CALL void_item(
            :sale_id, 
            :product_id, 
            :void_quantity, 
            :void_by, 
            :cancelled_by, 
            :reason, 
            :add_to_inventory
        )
    ");

    // Bind parameters
    $stmt->bindParam(':sale_id', $data['saleId'], PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $data['productId'], PDO::PARAM_INT);
    $stmt->bindParam(':void_quantity', $data['cancelQty'], PDO::PARAM_INT);
    $stmt->bindParam(':void_by', $data['voidBy'], PDO::PARAM_STR);
    $stmt->bindParam(':cancelled_by', $data['cancelledBy'], PDO::PARAM_STR);
    $stmt->bindParam(':reason', $data['cancelReason'], PDO::PARAM_STR);
    $stmt->bindParam(':add_to_inventory', $addToInventory, PDO::PARAM_BOOL);

    // Execute the stored procedure
    $stmt->execute();

    // Return success response
    echo json_encode(["success" => true, "message" => "Item voided successfully"]);
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    // Handle other exceptions
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
