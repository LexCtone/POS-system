<?php
include('connect.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $adjustment_type = $_POST['adjustment_type'];
    $adjustment_quantity = $_POST['adjustment_quantity'];
    $adjustment_reason = $_POST['adjustment_reason'];
    $apply_to_inventory = isset($_POST['apply_to_inventory']) ? 1 : 0;
    $adjusted_by = 'admin'; // Replace with session user id or username
    $adjustment_date = date('Y-m-d H:i:s');

    // Insert the adjustment record into the stock_adjustment table
    $query = "INSERT INTO stock_adjustment (product_id, adjustment_type, adjustment_quantity, adjustment_reason, adjusted_by, adjustment_date, apply_to_inventory)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isisssi", $product_id, $adjustment_type, $adjustment_quantity, $adjustment_reason, $adjusted_by, $adjustment_date, $apply_to_inventory);
    $stmt->execute();

    if ($apply_to_inventory) {
        // Update the stock in the products table based on adjustment
        if ($adjustment_type == 'increase') {
            $update_stock = "UPDATE products SET Quantity = Quantity + ? WHERE id = ?";
        } else {
            $update_stock = "UPDATE products SET Quantity = Quantity - ? WHERE id = ? AND Quantity >= ?";
        }

        $stmt = $conn->prepare($update_stock);
        $stmt->bind_param("iii", $adjustment_quantity, $product_id, $adjustment_quantity);
        $stmt->execute();
    }

    // Redirect or display a success message
    header("Location: stock_adjustment_success.php");
}
?>
