<?php
// StockAdjustment.php
session_start();
include 'connect.php';

// Fetch the username of the logged-in admin
$admin_name = "ADMINISTRATOR"; // Default value
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query_admin = "SELECT name FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($query_admin);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin_name = $row['name'];
    }
    $stmt->close();
}


function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . "StockAdjustment.php: " . $message . "\n", 3, "error.log");
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    logError("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Reorder IDs and reset AUTO_INCREMENT
$conn->query("SET @num := 0;");
$conn->query("UPDATE stock_adjustment SET id = (@num := @num + 1) ORDER BY adjustment_date;");
$conn->query("SET @max_id = (SELECT MAX(id) FROM stock_adjustment);");

// Fetch products from the database
$sql = "SELECT id, Barcode, Description, Category, Quantity as current_quantity, Price, last_update FROM products";
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
    <button onclick="location.href='PurchaseOrder.php'">Purchase Order</button>
    <button onclick="location.href='StockEntry.php'">Orders</button> 
        <button onclick="location.href='StockinHistory.php'">Stock in History</button> 
        <button onclick="location.href='StockAdjustment.php'"class="selected">Stock Adjustments</button> 
        <button id="showAdjustmentHistoryBtn">Show Adjustment History</button>
    </div>

    <nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li> <!-- Added Dashboard back -->
        <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product
        <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i> <!-- Submenu symbol added -->
        </a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="Purchase Order.php"  class="selected"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>
    <div class="content">
        <div id="message" class="message"></div>
        <div class="modalss">
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
                                echo "<tr data-product-id='" . $row['id'] . "' data-barcode='" . $row['Barcode'] . "' data-current-quantity='" . $row['current_quantity'] . "'>";
                                echo "<td>" . $rowNumber . "</td>";
                                echo "<td>" . htmlspecialchars($row['Barcode']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Category']) . "</td>";
                                echo "<td class='current-quantity'>" . htmlspecialchars($row['current_quantity']) . "</td>";
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
            <form id="adjustmentForm" class="horizontal-form">
                <div class="form-group">
                    <label for="barcode_display">Barcode:</label>
                    <input type="text" id="barcode_display" readonly>
                    <input type="hidden" id="barcode" name="barcode">
                </div>
                <div class="form-group">
                    <label for="description_display">Description:</label>
                    <input type="text" id="description_display" readonly>
                </div>
                <div class="form-group">
                    <label for="current_quantity">Current Quantity:</label>
                    <input type="number" id="current_quantity" readonly>
                </div>
                <div class="form-group">
                    <label for="adjustment_quantity">Adjustment Quantity:</label>
                    <input type="number" name="adjustment_quantity" id="adjustment_quantity" required>
                </div>
                <div class="form-group">
                    <label for="adjustment_reason">Reason for Adjustment:</label>
                    <textarea name="adjustment_reason" id="adjustment_reason" required></textarea>
                </div>
                <input type="hidden" id="product_id" name="product_id">
                <input type="hidden" name="reference" value="adjustment_reference">
                </div>
                <button type="submit" class="submit-button">Submit Adjustment</button>
            </form>

            <!-- Modal for Adjustment History -->
            <div id="adjustmentHistoryModal" class="modals">
                <div class="modal-contents">
                    <div class="modal-header">
                        <h3>Stock Adjustment History</h3>
                        <div class="modal-controls">
                            <label for="showArchivedAdjustments" class="archived-checkbox">
                                <input type="checkbox" id="showArchivedAdjustments">
                                Show Archived Adjustments
                            </label>
                            <span class="close">&times;</span>
                        </div>
                    </div>
                    <div class="table-container">
                        <table id="adjustmentHistoryTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Barcode</th>
                                    <th>Adjustment Type</th>
                                    <th>Original Quantity</th>
                                    <th>Adjustment Quantity</th>
                                    <th>New Quantity</th>
                                    <th>Reason</th>
                                    <th>Adjusted By</th>
                                    <th>Adjustment Date</th>
                                </tr>
                            </thead>
                            <tbody id="adjustmentHistoryBody">
                                <!-- Adjustment history data will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <style>
        /* Modal styles */
        .modal {
          display: none; /* Hidden by default */
          position: fixed; /* Stay in place */
          z-index: 1000; /* Sit on top */
          left: 0;
          top: 0;
          width: 100%; /* Full width */
          height: 100%; /* Full height */
          overflow: auto; /* Enable scroll if needed */
          background-color: rgba(0, 0, 0, 0.5); /* Black with opacity */
      }

      /* Modal content */
      .modal-content {
          background-color: #fefefe; /* White background */
          margin: 15% auto; /* 15% from the top and centered */
          padding: 20px;
          border: 1px solid #888; /* Gray border */
          width: 375px; /* Could be more or less, depending on screen size */
          border-radius: 8px; /* Rounded corners */
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow effect */
      }

      /* Close button */
      .close {
          color: #aaa; /* Light gray */
          float: right; /* Position to the right */
          font-size: 28px; /* Larger font size */
          font-weight: bold; /* Bold text */
      }

      .conf{
        font-size: 24px;
        font-weight: bolder;
      }

      .par{
        font-size: 18px;
        margin-left: 20px;
      }

      .close:hover,
      .close:focus {
          color: black; /* Change color on hover */
          text-decoration: none; /* No underline */
          cursor: pointer; /* Pointer cursor */
      }

      /* Button styles */
      .confirm-btn,
      .cancel-btn {
          background-color: #005b99; /* Blue background */
          border: none; /* No borders */
          color: white; /* White text */
          padding: 10px 20px; /* Some padding */
          text-align: center; /* Centered text */
          text-decoration: none; /* No underline */
          display: inline-block; /* Align buttons */
          font-size: 16px; /* Larger font */
          margin: 10px 2px; /* Margins around buttons */
          margin-left: 63px;
          margin-top: 20px;
          cursor: pointer; /* Pointer cursor */
          border-radius: 5px; /* Rounded corners */
          transition: background-color 0.3s; /* Smooth transition */
      }

      .cancel-btn {
          background-color: red; /* Gray background for cancel */
      }

      .cancel-btn:hover {
          background-color: maroon; /* Darker gray on hover */
      }

      .confirmLogout:hover{
        background-color: black; /* Darker gray on hover */
      }
    </style>

            <script>
            $(document).ready(function() {
                var modal = document.getElementById("adjustmentHistoryModal");
                var btn = document.getElementById("showAdjustmentHistoryBtn");
                var span = document.getElementsByClassName("close")[0];

                btn.onclick = function() {
                    modal.style.display = "block";
                    loadAdjustmentHistory();
                }

                span.onclick = function() {
                    modal.style.display = "none";
                }

                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                }

                $('input[name="product_id"]').on('change', function() {
                var $selectedRow = $(this).closest('tr');
                var currentQuantity = $selectedRow.data('current-quantity');
                var barcode = $selectedRow.data('barcode');
                var description = $selectedRow.find('td:eq(2)').text(); // Assuming description is in the third column
                var productId = $(this).val();
                
                $('#current_quantity').val(currentQuantity);
                $('#barcode').val(barcode);
                $('#barcode_display').val(barcode);
                $('#description_display').val(description);
                $('#product_id').val(productId);
            });

                $('#adjustmentForm').on('submit', function(e) {
                    e.preventDefault();
                    
                    var barcode = $('#barcode').val();
                    if (!barcode) {
                        showMessage('Please select a product.', 'error');
                        return;
                    }

                    var formData = $(this).serialize();

                    $.ajax({
                        url: 'process_adjustment.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                showMessage(response.message, 'success');
                                updateProductQuantity(barcode, response.new_quantity);
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
                            console.error('Response:', jqXHR.responseText);
                        }
                    });
                });
                function loadAdjustmentHistory(showArchived = false) {
                    $.ajax({
                        url: 'get_adjustment_history.php',
                        type: 'GET',
                        data: { show_archived: showArchived },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                displayAdjustmentHistory(response.data);
                            } else {
                                showMessage('Error loading adjustment history: ' + response.message, 'error');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            showMessage('Error loading adjustment history. Please try again.', 'error');
                            console.error('AJAX Error:', textStatus, errorThrown);
                        }
                    });
                }

                $('#showArchivedAdjustments').on('change', function() {
                    loadAdjustmentHistory(this.checked);
                });

                function displayAdjustmentHistory(data) {
                    var $tbody = $('#adjustmentHistoryBody');
                    $tbody.empty();
                    data.forEach(function(adjustment) {
                        $tbody.append(`
                            <tr>
                                <td>${adjustment.id}</td>
                                <td>${adjustment.barcode}</td>
                                <td>${adjustment.adjustment_type}</td>
                                <td>${adjustment.original_quantity}</td>
                                <td>${adjustment.adjustment_quantity}</td>
                                <td>${adjustment.new_quantity}</td>
                                <td>${adjustment.adjustment_reason}</td>
                                <td>${adjustment.adjusted_by}</td>
                                <td>${adjustment.adjustment_date}</td>
                            </tr>
                        `);
                    });
                }

                function showMessage(message, type) {
                    $('#message').text(message).removeClass().addClass(type).show();
                    setTimeout(function() {
                        $('#message').fadeOut();
                    }, 5000);
                }

                function updateProductQuantity(barcode, newQuantity) {
                    var $row = $('tr[data-barcode="' + barcode + '"]');
                    $row.find('.current-quantity').text(newQuantity);
                    $row.data('current-quantity', newQuantity);
                }

                function resetForm() {
                    $('#adjustmentForm')[0].reset();
                    $('input[name="product_id"]').prop('checked', false);
                    $('#current_quantity').val('');
                    $('#barcode').val('');
                    $('#barcode_display').val('');
                    $('#description_display').val('');
                    $('#product_id').val('');
                }
            });
            </script>
       <!-- Logout Confirmation Modal -->
 <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLogoutModal()">&times;</span>
            <h2 class="conf">Logout Confirmation</h2>
            <p class="par">Are you sure you want to log out?</p>
            <button id="confirmLogout" class="confirm-btn">Logout</button>
            <button class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>
        </div>
    </div>
    <script>
        // Function to show the logout modal
        function confirmLogout() {
            document.getElementById("logoutModal").style.display = "block"; // Show the modal
            return false; // Prevent the default link action
        }

        // Function to close the logout modal
        function closeLogoutModal() {
            document.getElementById("logoutModal").style.display = "none"; // Hide the modal
        }

        // Confirm logout action
        document.getElementById("confirmLogout").onclick = function() {
            window.location.href = "Login.php"; // Redirect to the login page or handle logout
        };

        // Close the modal if the user clicks anywhere outside of it
        window.onclick = function(event) {
            var logoutModal = document.getElementById("logoutModal");
            if (event.target == logoutModal) {
                closeLogoutModal();
            }
        };
    </script>
</body>
</html>
        <?php
        $conn->close();
        ?>