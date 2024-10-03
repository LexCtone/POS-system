<?php
session_start();
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch the product from archived_products
        $fetch_sql = "SELECT * FROM archived_products WHERE id = ?";
        $fetch_stmt = $conn->prepare($fetch_sql);
        $fetch_stmt->bind_param('i', $id);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            throw new Exception("Product not found for restoration.");
        }

        // Check if a product with the same ID already exists in the products table
        $check_sql = "SELECT id FROM products WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("A product with this ID already exists in the active products.");
        }

        // Insert the product back into the products table with the same ID
        $insert_sql = "INSERT INTO products (id, Barcode, Description, Brand, Category, Price, Quantity) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('issssdi', $product['id'], $product['Barcode'], $product['Description'], $product['Brand'], $product['Category'], $product['Price'], $product['Quantity']);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Error restoring product: " . $insert_stmt->error);
        }

        // Move stock adjustment records back to stock_adjustment table
        $restore_adjustments_sql = "INSERT INTO stock_adjustment 
                                    SELECT * FROM archived_stock_adjustment 
                                    WHERE product_id = ?";
        $restore_adjustments_stmt = $conn->prepare($restore_adjustments_sql);
        $restore_adjustments_stmt->bind_param('i', $id);
        
        if (!$restore_adjustments_stmt->execute()) {
            throw new Exception("Error restoring stock adjustments: " . $restore_adjustments_stmt->error);
        }

        // Delete the archived stock adjustment records
        $delete_archived_adjustments_sql = "DELETE FROM archived_stock_adjustment WHERE product_id = ?";
        $delete_archived_adjustments_stmt = $conn->prepare($delete_archived_adjustments_sql);
        $delete_archived_adjustments_stmt->bind_param('i', $id);
        
        if (!$delete_archived_adjustments_stmt->execute()) {
            throw new Exception("Error deleting archived stock adjustments: " . $delete_archived_adjustments_stmt->error);
        }

        // Delete the product from archived_products
        $delete_sql = "DELETE FROM archived_products WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('i', $id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Error deleting archived product: " . $delete_stmt->error);
        }

        // Log the restoration action
        $admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';
        
        $log_sql = "INSERT INTO product_logs (action, product_barcode, performed_by) VALUES ('Restored', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param('ss', $product['Barcode'], $admin_username);
        
        if (!$log_stmt->execute()) {
            throw new Exception("Error logging restoration: " . $log_stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Product restored successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error in restore_product.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>