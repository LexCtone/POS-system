<?php
// StockAdjustment.php

session_start();
include 'connect.php';

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . "StockAdjustment.php: " . $message . "\n", 3, "error.log");
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    logError("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Fetch products from the database
$sql = "SELECT p.id, p.Barcode, p.Description, p.Category, p.Quantity as current_quantity, p.Price, p.last_update, sh.reference, sh.quantity as history_quantity, sh.id as stock_in_id
        FROM products p
        LEFT JOIN stock_in_history sh ON p.Barcode = sh.Barcode
        WHERE sh.id = (SELECT MAX(id) FROM stock_in_history WHERE Barcode = p.Barcode)";
$result = $conn->query($sql);

if (!$result) {
    logError("Query error: " . $conn->error);
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
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
            <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
        </ul>
    </nav>
    <div class="content">
        <div id="message" class="message"></div>
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
                            <th>STOCK-IN QTY</th>
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
                                echo "<tr data-product-id='" . $row['id'] . "' data-reference='" . $row['reference'] . "' data-current-quantity='" . $row['current_quantity'] . "' data-history-quantity='" . $row['history_quantity'] . "' data-stock-in-id='" . $row['stock_in_id'] . "'>";
                                echo "<td>" . $rowNumber . "</td>";
                                echo "<td>" . htmlspecialchars($row['Barcode']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Category']) . "</td>";
                                echo "<td class='current-quantity'>" . htmlspecialchars($row['current_quantity']) . "</td>";
                                echo "<td class='history-quantity'>" . htmlspecialchars($row['history_quantity']) . "</td>";
                                echo "<td>₱" . number_format($row['Price'], 2) . "</td>";
                                echo "<td>" . htmlspecialchars($row['last_update']) . "</td>";
                                echo "<td><input type='radio' name='product_id' value='" . $row['id'] . "' required></td>";
                                echo "</tr>";
                                $rowNumber++;
                            }
                        } else {
                            echo "<tr><td colspan='9' class='no-records'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <form id="adjustmentForm" class="horizontal-form">
                <div class="form-group">
                    <label for="current_quantity">Current Quantity:</label>
                    <input type="number" id="current_quantity" readonly>
                </div>
                <div class="form-group">
                    <label for="history_quantity">Stock-in Quantity:</label>
                    <input type="number" id="history_quantity" readonly>
                </div>
                <div class="form-group">
                    <label for="adjustment_quantity">New Stock-in Quantity:</label>
                    <input type="number" name="adjustment_quantity" id="adjustment_quantity" required>
                </div>
                <div class="form-group">
                    <label for="adjustment_reason">Reason for Adjustment:</label>
                    <textarea name="adjustment_reason" id="adjustment_reason" required></textarea>
                </div>
                <input type="hidden" id="reference" name="reference">
                <input type="hidden" id="stock_in_id" name="stock_in_id">
                <button type="submit" class="submit-button">Submit Adjustment</button>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('input[name="product_id"]').on('change', function() {
            var $selectedRow = $(this).closest('tr');
            var currentQuantity = $selectedRow.data('current-quantity');
            var historyQuantity = $selectedRow.data('history-quantity');
            var reference = $selectedRow.data('reference');
            var stockInId = $selectedRow.data('stock-in-id');
            $('#current_quantity').val(currentQuantity);
            $('#history_quantity').val(historyQuantity);
            $('#adjustment_quantity').val(historyQuantity);
            $('#reference').val(reference);
            $('#stock_in_id').val(stockInId);
        });

        $('#adjustmentForm').on('submit', function(e) {
            e.preventDefault();
            
            var productId = $('input[name="product_id"]:checked').val();
            if (!productId) {
                showMessage('Please select a product.', 'error');
                return;
            }

            var historyQuantity = parseInt($('#history_quantity').val());
            var newQuantity = parseInt($('#adjustment_quantity').val());
            var adjustmentQuantity = newQuantity - historyQuantity;

            var formData = $(this).serialize() + '&product_id=' + productId + '&adjustment_quantity=' + adjustmentQuantity;

            $.ajax({
                url: 'process_adjustment.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showMessage(response.message, 'success');
                        updateProductQuantities(productId, response.new_current_quantity, response.new_history_quantity);
                        resetForm();
                    } else {
                        showMessage(response.message, 'error');
                        console.error('Error:', response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    var errorMessage = 'An error occurred. Please try again.';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }
                    showMessage(errorMessage, 'error');
                    console.error('AJAX Error:', textStatus, errorThrown);
                    logClientError('AJAX Error in StockAdjustment.php: ' + textStatus + ' - ' + errorThrown);
                }
            });
        });

        function showMessage(message, type) {
            $('#message').text(message).removeClass().addClass(type).show();
            setTimeout(function() {
                $('#message').fadeOut();
            }, 5000);
        }

        function updateProductQuantities(productId, newCurrentQuantity, newHistoryQuantity) {
            var $row = $('tr[data-product-id="' + productId + '"]');
            $row.find('.current-quantity').text(newCurrentQuantity);
            $row.find('.history-quantity').text(newHistoryQuantity);
            $row.data('current-quantity', newCurrentQuantity);
            $row.data('history-quantity', newHistoryQuantity);
        }

        function resetForm() {
            $('#adjustmentForm')[0].reset();
            $('input[name="product_id"]').prop('checked', false);
            $('#current_quantity').val('');
            $('#history_quantity').val('');
            $('#reference').val('');
            $('#stock_in_id').val('');
        }

        function logClientError(errorMessage) {
            $.ajax({
                url: 'log_error.php',
                type: 'POST',
                data: { error: errorMessage },
                dataType: 'json',
                success: function(response) {
                    if (response.status !== 'success') {
                        console.error('Failed to log error:', response.message);
                    }
                },
                error: function() {
                    console.error('Failed to log error');
                }
            });
        }
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>