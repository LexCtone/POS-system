<?php
session_start();
error_log(print_r($_SESSION, true)); // Log session variables
include '../connect.php';

// Check if the user is logged in and is a cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header("Location: access_denied.php");
    exit();
}

// Debugging line
error_log(print_r($_SESSION, true)); // This will log the session array to the server log

// Fetch user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, role FROM accounts WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Store the username in the session
$_SESSION['username'] = $user['username'];

// Pass the server's timestamp to JavaScript
$serverTimestamp = time() * 1000; // PHP time() * 1000 to get JS timestamp

// Handle AJAX request for fetching sales data
if (isset($_GET['action']) && $_GET['action'] === 'fetch_sales') {
    // Get filter parameters
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';
    $cashierId = $_GET['cashierId'] ?? 'all';
    $view = $_GET['view'] ?? 'item'; // Default view is 'item'

    // Initialize base query
    $sql = "";
    if ($view === 'transaction') {
        // Query for transaction summary
        $sql = "SELECT s.invoice, MIN(s.sale_date) as date, a.username as cashier_name, SUM(s.total) as total
        FROM sales s
        JOIN accounts a ON s.cashier_name = a.username
        WHERE s.status != 'voided'";

    } else {
        // Query for item-level details
        $sql = "SELECT s.id, s.invoice, s.barcode, s.description, s.price, s.quantity, s.discount_amount, s.total, a.username as cashier_name, s.sale_date
        FROM sales s
        JOIN accounts a ON s.cashier_name = a.username
        WHERE s.status != 'voided'";

    }

    // Add filters for date range and cashier
    if ($dateFrom) {
        $sql .= " AND DATE(s.sale_date) >= ?";
    }
    if ($dateTo) {
        $sql .= " AND DATE(s.sale_date) <= ?";
    }
    if ($cashierId !== 'all') {
        $sql .= " AND a.id = ?";
    }

    // For transaction view, group by invoice
    if ($view === 'transaction') {
        $sql .= " GROUP BY s.invoice, a.username";
        $sql .= " ORDER BY MIN(s.sale_date) DESC"; // Order by sale date for transaction view
    } else {
        $sql .= " ORDER BY s.sale_date DESC"; // Order by sale date for item view
    }

    // Prepare and bind the parameters
    $stmt = $conn->prepare($sql);
    $types = '';
    $params = [];

    if ($dateFrom) {
        $types .= 's';
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $types .= 's';
        $params[] = $dateTo;
    }
    if ($cashierId !== 'all') {
        $types .= 'i';
        $params[] = $cashierId;
    }

    // Bind the parameters if there are any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    // Execute the query and handle the result
    $stmt->execute();
    $result = $stmt->get_result();

    $sales = [];
    $totalSales = 0;

    // Fetch the result set
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($view === 'transaction') {
                // Transaction view: Fetch items for each transaction (invoice)
                $invoice = $row['invoice'];
                $itemsStmt = $conn->prepare("SELECT barcode, description, price, quantity, discount_amount, total FROM sales WHERE invoice = ?");
                $itemsStmt->bind_param("s", $invoice);
                $itemsStmt->execute();
                $itemsResult = $itemsStmt->get_result();

                $items = [];
                while ($item = $itemsResult->fetch_assoc()) {
                    $items[] = $item; // Collect items for this transaction
                }
                $itemsStmt->close();

                // Add the transaction details and its items to the sales array
                $sales[] = [
                    'invoice' => $row['invoice'],
                    'date' => $row['date'],
                    'cashier_name' => $row['cashier_name'],
                    'total' => $row['total'],
                    'items' => $items // Add items array here
                ];
            } else {
                // Item view: Add each item directly to the sales array
                $sales[] = [
                    'id' => $row['id'],
                    'invoice' => $row['invoice'],
                    'barcode' => $row['barcode'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'discount_amount' => $row['discount_amount'],
                    'total' => $row['total'],
                    'cashier_name' => $row['cashier_name'],
                    'sale_date' => $row['sale_date']
                ];
            }

            // Accumulate total sales for both views
            $totalSales += $row['total'];
        }
    }

    // Close the main statement
    $stmt->close();

    // Return the sales data as a JSON response
    echo json_encode(['sales' => $sales, 'totalSales' => $totalSales]);
    exit();
}


// Query for daily sales
$query_daily_sales = "SELECT SUM(total) as daily_sales FROM sales WHERE DATE(sale_date) = CURDATE()";
$result_daily_sales = $conn->query($query_daily_sales);
$daily_sales = 0;
if ($result_daily_sales && $row = $result_daily_sales->fetch_assoc()) {
    $daily_sales = $row['daily_sales'] ? $row['daily_sales'] : 0;
}

// Query for stock on hand
$query_stock_on_hand = "SELECT SUM(Quantity) as stock_on_hand FROM products";
$result_stock_on_hand = $conn->query($query_stock_on_hand);
$stock_on_hand = 0;
if ($result_stock_on_hand && $row = $result_stock_on_hand->fetch_assoc()) {
    $stock_on_hand = $row['stock_on_hand'] ? $row['stock_on_hand'] : 0;
}

// Query for critical items
$query_critical_items = "SELECT COUNT(*) as critical_items FROM products WHERE Quantity <= 10";
$result_critical_items = $conn->query($query_critical_items);
$critical_items = 0;
if ($result_critical_items && $row = $result_critical_items->fetch_assoc()) {
    $critical_items = $row['critical_items'] ? $row['critical_items'] : 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Transaction</title>
    <link rel="stylesheet" href="transaction.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>New Transaction</h1>
        <div class="sales-box">        
        <span>Total Amount:</span> <strong id="headerTotalSales">₱0.00</strong>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="transaction-details">
    <div class="transaction-content">
        <div class="transaction-info">
            <div class="transaction-item">
                <label>Transaction No.</label>
                <span id="transactionNo"></span>
            </div>
            <div class="transaction-item">
                <label>Transaction Date:</label>
                <span id="transactionDateDisplay"></span>
                </div>
            <div class="transaction-item">
                <label>Cashier:</label>
                <span id="cashierName"><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
            <div class="transaction-item barcode">
                <div class="input-container">
                    <label>Barcode</label>
                    <input type="text" id="barcodeInput" placeholder="Enter Barcode">
                    <button id="searchProductBtn" class="clear-btn">Search Product</button>
                    <button class="settle-btn">Settle Payment</button>
                    <button class="clear-btn">Clear Cart</button>
                </div>
            </div>
        </div>
               <!-- Box Container on the Right -->
               <div class="box-container">
            <div class="box orange" id="box1">
                <?= number_format($daily_sales, 2) ?><br> DAILY SALES
            </div>
            <div class="box yellow" id="box2">
                <?= number_format($stock_on_hand) ?><br> STOCK ON HAND
            </div>
            <div class="box green" id="box3">
                <?= number_format($critical_items) ?><br> CRITICAL ITEMS
            </div>
        </div>
    </div>
</div>
        <div class="transaction-table">
            <table id="transactionTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Description</th>
                        <th>Original Price</th>
                        <th>Quantity</th>
                        <th>Discount Amount</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows will be added dynamically here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sidebar on the right -->
    <div class="sidebar">
        <ul class="menu">
            <li><a href="#" id="addDiscountBtn"><i class='fa fa-percent'></i> Add Discount</a></li>
            <li><a href="#" id="dailySalesBtn"><i class='fa fa-chart-line'></i> Daily Sales</a></li>
          <!--  <li><a href="#" id="userSettingsBtn"><i class='fa fa-cogs'></i> User Settings</a></li>-->
            <li><a href="..\Login.php"><i class='fa fa-sign-out'></i> Logout</a></li>
        </ul>
    </div>

    <!-- Search Product Modal -->
    <div id="searchProductModal" class="modal search-product-modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Search Product</h2>
            <input type="text" id="search-barcode" placeholder="Enter Barcode" value="">
            <table id="searchProductTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Barcode</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="product-table-body">
                    <!-- Search results will be injected here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Settle Payment Modal -->
    <div id="settle_payment" class="modal settle-payment-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Settle Payment</h2>
                <button class="close-button">&times;</button>
            </div>
            <div class="payment-info">
                <div class="info-row">
                    <span>Total Amount</span>
                    <span id="total-amount" class="amount">₱0.00</span>
                </div>
                <div class="info-row">
                    <span>Payment</span>
                    <input type="text" id="payment-amount" class="amount">
                </div>
                <div class="info-row">
                    <span>Change</span>
                    <span id="change-amount" class="amount">₱0.00</span>
                </div>
            </div>
            <div class="calculator-buttons">
                <button class="calc-btn" onclick="addToDisplay('7')">7</button>
                <button class="calc-btn" onclick="addToDisplay('8')">8</button>
                <button class="calc-btn" onclick="addToDisplay('9')">9</button>
                <button class="calc-btn" onclick="addToDisplay('4')">4</button>
                <button class="calc-btn" onclick="addToDisplay('5')">5</button>
                <button class="calc-btn" onclick="addToDisplay('6')">6</button>
                <button class="calc-btn" onclick="addToDisplay('1')">1</button>
                <button class="calc-btn" onclick="addToDisplay('2')">2</button>
                <button class="calc-btn" onclick="addToDisplay('3')">3</button>
                <button class="calc-btn" onclick="addToDisplay('0')">0</button>
                <button class="calc-btn" onclick="addToDisplay('00')">00</button>
                <button class="calc-btn" onclick="addToDisplay('.')">.</button>
                <button class="calc-btn clear" onclick="clearDisplay()">C</button>
                <button class="calc-btn enter" onclick="calculateChange()">Enter</button>
            </div>
        </div>
    </div>

<!-- Discount Modal -->
<div class="modal discount-modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Apply Discount</h2>
        <p>Product: <span id="discountProductName"></span></p>
        <p>Base Price: <strong id="basePrice">₱0.00</span></p> <!-- Added base price here -->
        <label for="discountPercent">Discount Percentage:</label>
        <input type="number" id="discountPercent" min="0" max="100" step="0.01">
        <label for="discountAmount">Discount Amount:</label>
        <input type="text" id="discountAmount" readonly>
        <label for="totalPrice">Total Price:</label>
        <input type="text" id="totalPrice" readonly>
        <button id="confirmDiscount">Apply Discount</button>
    </div>
</div>


    <div id="perPurchaseDiscountModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Apply Per-Purchase Discount</h2>
        <p>Total Amount: <span id="perPurchaseTotalAmount"></span></p>
        <div>
            <label for="perPurchaseDiscountPercent">Discount Percentage:</label>
            <input type="number" id="perPurchaseDiscountPercent" min="0" max="100" step="0.01">
        </div>
        <div>
            <label for="perPurchaseDiscountAmount">Discount Amount:</label>
            <input type="number" id="perPurchaseDiscountAmount" readonly>
        </div>
        <p>Final Amount: <span id="perPurchaseFinalAmount"></span></p>
        <button id="applyPerPurchaseDiscount">Apply Discount</button>
    </div>
</div>

<!-- Daily Sales Modal -->
<div id="dailySalesModal" class="modal daily-sales-modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Daily Sales</h2> 
        
        <div class="sales-print-container">
            <div class="filters filter-controls">
                <label for="dateFrom">From:</label>
                <input type="date" id="dateFrom">
                <label for="dateTo">To:</label>
                <input type="date" id="dateTo">
                <label for="cashier">Cashier:</label>
                <select id="cashier">
                    <option value="all">All Cashiers</option>
                    <?php
                    // Fetch all cashiers
                    $stmt = $conn->prepare("SELECT id, username FROM accounts WHERE role = 'cashier'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['username']) . "</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <button>Filter</button>
                <div class="toggle-container">
                 <button class="toggler" id="toggleView">Switch to Transaction View</button>
                </div>   
                 </div> 
            <div class="total-sales">
                <span id="modalTotalSales">₱0.00</span>
            </div>
                </div>
                
                    <!-- Table with scrollable tbody -->
        <table class="scrollable-table" id="salesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice</th>
                    <th>Barcode</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Discount</th>
                    <th>Total</th>
                    <th>Cashier</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="salesData" class="scrollable-tbody">
                <!-- Sales data will be populated here dynamically -->
            </tbody>
        </table>
    </div>
</div>
        </div>
    </div>


<!-- per item Cancel Order Modal -->
<div id="cancelOrderModal" class=" modal cancel-modal">
    <div class="cancel-modal-content">
        <div class="cancel-modal-header">
            <h2>CANCEL ORDER DETAILS</h2>
            <span class="close-button">&times;</span>
        </div>
        <div class="cancel-modal-body">
            <div class="grid">
                <div>
                    <h3 class="section-title">SOLD ITEM</h3>
                    <div class="cancel-form-group">
                        <label for="id">ID</label>
                        <input type="text" id="id" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="productCode">BARCODE</label>
                        <input type="text" id="productCode" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="description">DESCRIPTION</label>
                        <input type="text" id="description" readonly>
                    </div>
                </div>
                <div>
                    <h3 class="section-title">TRANSACTION</h3>
                    <div class="cancel-form-group">
                        <label for="transaction">TRANSACTION NO.</label>
                        <input type="text" id="transaction" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="price">PRICE</label>
                        <input type="text" id="price" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="qtyDiscount">QTY & DISCOUNT</label>
                        <input type="text" id="qtyDiscount" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="total">TOTAL PRICE</label>
                        <input type="text" id="total" readonly>
                    </div>
                </div>
            </div>
            <h3 class="section-title">CANCEL ITEM(S)</h3>
            <div class="grid">
                <div>
                    <div class="cancel-form-group">
                        <label for="voidBy">VOID BY</label>
                        <input type="text" id="voidBy" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="cancelledBy">CANCELLED BY</label>
                        <input type="text" id="cancelledBy" value="cashier" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="addToInventory">ADD TO INVENTORY?</label>
                        <select id="addToInventory">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="cancel-form-group">
                        <label for="cancelQty">CANCEL QTY</label>
                        <input type="number" id="cancelQty">
                    </div>
                    <div class="cancel-form-group">
                        <label for="cancelReason">REASON(S)</label>
                        <textarea id="cancelReason" rows="4"></textarea>
                    </div>
                </div>
            </div>
            <button class="cancel-modal-btn">CANCEL ORDER</button>
        </div>
    </div>
</div>

<!-- Per Transaction Void Modal -->
<div id="cancelTransactionModal" class=" modal cancel-modal">
    <div class="cancel-modal-content">
        <div class="cancel-modal-header">
            <h2>CANCEL TRANSACTION</h2>
            <span class="close-button">&times;</span>
        </div>
        <div class="cancel-modal-body">
            <div class="grid">
                <div>
                    <h3 class="section-title">TRANSACTION DETAILS</h3>
                    <div class="cancel-form-group">
                        <label for="transactionId">TRANSACTION ID</label>
                        <input type="text" id="transactionId" value="TRX-000000" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="transactionTotal">TOTAL AMOUNT</label>
                        <input type="text" id="transactionTotal" value="₱0.00" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="transactionDate">DATE</label>
                        <input type="text" id="transactionDate" value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                    </div>
                </div>
                <div>
                    <h3 class="section-title">CANCELLATION DETAILS</h3>
                    <div class="cancel-form-group">
                        <label for="transactionVoidBy">VOID BY</label>
                        <input type="text" id="transactionVoidBy" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                    </div>
                    <div class="cancel-form-group">
                        <label for="transactionCancelReason">REASON(S)</label>
                        <textarea id="transactionCancelReason" rows="4" placeholder="Enter reason for cancellation"></textarea>
                    </div>
                </div>
            </div>
            <button class="cancel-modal-btn" id="cancelTransactionBtn">CANCEL TRANSACTION</button>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div id="transactionModal" class="details-modal">
    <div class="details-modal-content">
        <span class="close-button">&times;</span>
        <h2>Transaction Details</h2>
        <div id="transactionDetailsContent">
            <!-- Transaction details will be injected here -->
        </div>
    </div>
</div>


<div id="printReceiptModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Print Receipt</h2>
        <div id="receiptContent"></div>
    </div>
</div>

<!-- User Settings Modal 
<div id="userSettingsModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Change Password</h2>
        <form id="changePasswordForm">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
            </div>
            <div>
                <label for="currentPassword">Current Password:</label>
                <input type="password" id="currentPassword" required>
            </div>
            <div>
                <label for="newPassword">New Password:</label>
                <input type="password" id="newPassword" required>
            </div>
            <div>
                <label for="confirmPassword">Confirm New Password:</label>
                <input type="password" id="confirmPassword" required>
            </div>
            <p id="passwordError" class="error-message" style="display: none; color: red;"></p>
            <button type="submit">Change Password</button>
        </form>
    </div>
</div>
-->

<!-- Admin Password Modal -->
<div id="adminPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeAdminPasswordModal()">&times;</span>
        <h2>Enter Admin Password</h2>
        <form id="adminPasswordForm">
            <div>
                <label for="adminPasswordInput">Admin Password:</label>
                <input type="password" id="adminPasswordInput" required>
            </div>
            <p id="adminPasswordError" class="error-message" style="display: none; color: red;"></p>
            <button type="submit">Submit</button>
        </form>
    </div>
</div>
<style>
        /* Styles for the custom alert modal */
        .custom-alert {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            background-color: rgba(0, 0, 0, 0.5); /* Black with opacity */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        .alert-content {
            background-color: white; /* White background */
            padding: 20px;
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow effect */
            text-align: center; /* Centered text */
            width: 300px; /* Fixed width */
        }

        .alert-title {
            font-size: 18px; /* Title font size */
            margin-bottom: 10px; /* Space below title */
        }

        .alert-message {
            margin-bottom: 20px; /* Space below message */
        }

        .alert-button {
            background-color: #005b99; /* Button color */
            color: white; /* White text */
            border: none; /* No borders */
            padding: 10px 20px; /* Some padding */
            border-radius: 5px; /* Rounded corners */
            cursor: pointer; /* Pointer cursor */
            transition: background-color 0.3s; /* Smooth transition */
        }

        .alert-button:hover {
            background-color: #004080; /* Darker blue on hover */
        }
    </style>


<div>
    <!-- Footer --> 
    <footer class="footer"> 
        <div id="clock" class="time"></div> 
        <div id="date" class="date"></div> 
    </footer>
</div>

    <script src="transaction.js"> defer</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Custom Alert Modal -->
<div id="customAlert" class="custom-alert">
    <div class="alert-content">
        <div class="alert-title" id="alertTitle">Success</div>
        <div class="alert-message" id="alertMessage">Transaction completed successfully!</div>
        <button class="alert-button" onclick="closeCustomAlert()">OK</button>
    </div>
</div>

<script src="transaction.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
// JavaScript for custom alert modal
function showCustomAlert(message, title) {
    document.getElementById('alertMessage').innerText = message;
    document.getElementById('alertTitle').innerText = title;
    document.getElementById('customAlert').style.display = 'flex'; // Show modal as flex to center it
}

function closeCustomAlert() {
    document.getElementById('customAlert').style.display = 'none'; // Hide modal
}

// Close the modal if the user clicks anywhere outside of it
window.onclick = function(event) {
    var modal = document.getElementById('customAlert');
    if (event.target == modal) {
        closeCustomAlert();
    }
};

// Modify your existing function to call showCustomAlert
async function settlePayment() {
    try {
        await saveTransaction(totalAmount, paymentAmount, change);
        printReceipt(totalAmount, paymentAmount, change);
        showCustomAlert('Transaction completed successfully!', 'Success');
        
        // Close the settle payment modal
        closeModal('settle_payment');

        // Clear the transaction table and generate new transaction number
        clearTransactionTable();
        generateTransactionNo();
    } catch (error) {
        console.error('Error saving transaction:', error);
        showCustomAlert('An error occurred while saving the transaction. Receipt printing failed.', 'Error');
    }
}
</script>
</body>
</html>