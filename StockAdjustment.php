<?php
// StockAdjustment.php

// Database connection
include 'connect.php'; // Ensure this path is correct and the file exists

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch products from the database
$sql = "SELECT id, Barcode, Description, Category, Quantity, Price, last_update FROM products";
$result = $conn->query($sql);

if (!$result) {
    die("Error: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Adjustment</title>
    <link rel="stylesheet" href="CSS/StockAdjustment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header>
        <h2 class="StockHeader">Stock Adjustment</h2>
    </header>

    <div class="button-container">
        <button onclick="location.href='StockEntry.php'">Stock Entry</button>
        <button onclick="location.href='StockinHistory.php'">Stock in History</button> 
        <button onclick="location.href='StockAdjustment.php'">Stock Adjustments</button> 
    </div>

    <nav class="sidebar">
        <header>
            <img src="profile.png" alt="profile"/>
            <br>ADMINISTRATOR
        </header>
        <ul>
            <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Home</a></li>
            <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product</a></li>
            <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
            <li><a href="StockEntry.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
            <li><a href="Brand.php"><i class='fa-solid fa-tag' style='font-size:30px'></i>Brand</a></li>
            <li><a href="Category.php"><i class='fa-solid fa-layer-group' style='font-size:30px'></i>Category</a></li>
            <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
            <li><a href="SalesHistory.php"><i class='fa-solid fa-clock-rotate-left' style='font-size:30px'></i>Sales History</a></li>
            <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
            <li><a href="logout.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
        </ul>
    </nav>

    <div class="content">
        <div class="modals">
        <div class="table-container">
            
            <table id="product-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>BARCODE</th>
                        <th>DESCRIPTION</th>
                        <th>CATEGORY</th>
                        <th>CURRENT QTY</th>
                        <th>PRICE</th>
                        <th>LAST UPDATE</th>
                        <th>SELECT PRODUCT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $rowNumber = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $rowNumber . "</td>";
                            echo "<td>" . htmlspecialchars($row['Barcode']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Category']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Quantity']) . "</td>";
                            echo "<td>₱" . number_format($row['Price'], 2) . "</td>";
                            echo "<td>" . htmlspecialchars($row['last_update']) . "</td>";
                            echo "<td><input type='radio' name='product_id' value='" . $row['id'] . "' required></td>";
                            echo "</tr>";
                            $rowNumber++;
                        }
                    } else {
                        echo "<tr><td colspan='8' class='no-records'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
                    <form action="process_adjustment.php" method="POST" id="adjustmentForm" class="horizontal-form">
                <div class="form-group">
                    <label for="adjustment_type">Adjustment Type:</label>
                    <select name="adjustment_type" id="adjustment_type" required>
                        <option value="increase">Increase</option>
                        <option value="decrease">Decrease</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="adjustment_quantity">Adjustment Quantity:</label>
                    <input type="number" name="adjustment_quantity" id="adjustment_quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="adjustment_reason">Reason for Adjustment:</label>
                    <textarea name="adjustment_reason" id="adjustment_reason" required></textarea>
                </div>
                <div class="form-group">
                    <label for="apply_to_inventory">Apply to Inventory:</label>
                    <select name="apply_to_inventory" id="apply_to_inventory">
                        <option value="">Select an option</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </form>
            <button type="submit" form="adjustmentForm" class="submit-button">Submit Adjustment</button>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>