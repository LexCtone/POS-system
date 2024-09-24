<?php
session_start();
include '../connect.php';

// Check if the user is a cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header("Location: access_denied.php");
    exit();
}

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
    // Check if the user is logged in and is a cashier
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
        echo json_encode(['error' => 'Unauthorized access']);
        exit();
    }

    // Get filter parameters
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';
    $cashierId = $_GET['cashierId'] ?? 'all';

    // Prepare the SQL query
    $sql = "SELECT s.id, s.invoice, s.barcode, s.description, s.price, s.quantity, s.discount_amount, s.total, a.username as cashier_name
            FROM sales s
            JOIN accounts a ON s.cashier_name = a.username
            WHERE 1=1";

    if ($dateFrom) {
        $sql .= " AND DATE(s.sale_date) >= ?";
    }
    if ($dateTo) {
        $sql .= " AND DATE(s.sale_date) <= ?";
    }
    if ($cashierId !== 'all') {
        $sql .= " AND a.id = ?";
    }

    $sql .= " ORDER BY s.sale_date DESC";

    $stmt = $conn->prepare($sql);

    // Bind parameters
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

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $sales = [];
    $totalSales = 0;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
            $totalSales += $row['total'];
        }
    }

    $stmt->close();

    echo json_encode(['sales' => $sales, 'totalSales' => $totalSales]);
    exit();
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
            <span>Total Sales: </span>
            <strong id="headerTotalSales">₱0.00</strong>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="transaction-details">
        <div class="transaction-info">
            <div class="transaction-item">
                <label>Transaction No.</label>
                <span id="transactionNo"></span>
            </div>
            <div class="transaction-item">
                <label>Transaction Date:</label>
                <span id="transactionDate"></span>
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
            <li><a href="Cashier_dashboard.php"><i class="fa fa-pie-chart"></i> Dashboard</a></li>
            <li><a href="transaction.php"><i class='fa fa-plus'></i> New Transaction</a></li>
            <li><a href="#" id="addDiscountBtn"><i class='fa fa-percent'></i> Add Discount</a></li>
            <li><a href="#" id="dailySalesBtn"><i class='fa fa-chart-line'></i> Daily Sales</a></li>
            <li><a href="#"><i class='fa fa-cogs'></i> User Settings</a></li>
            <li><a href="..\login.php"><i class='fa fa-sign-out'></i> Logout</a></li>
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
            <div class="filters filter-controls"> <!-- Added class 'filter-controls' here -->
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
                <button>Filter</button> <!-- This button is now inside 'filter-controls' -->
            </div>
            <div class="total-sales">
                <span id="modalTotalSales">₱0.00</span>
            </div>
        </div>

<!-- Table with scrollable tbody -->
<table class="scrollable-table">
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
            <th>Action</th> <!-- Added column for Void button -->
        </tr>
    </thead>
    <tbody id="salesData" class="scrollable-tbody">
        <!-- Sales data will be populated here dynamically -->
        <!-- Example row with Void button -->
        <tr>
            <td>1</td>
            <td>20240924102337722</td>
            <td>6935280818516</td>
            <td>Keyboard</td>
            <td>₱279.00</td>
            <td>1</td>
            <td>₱0.00</td>
            <td>₱279.00</td>
            <td>lex</td>
            <td><button class="void-btn">Void</button></td> <!-- Void button -->
        </tr>
        <!-- Additional rows will follow the same structure -->
    </tbody>
</table>
    </div>
</div>


    <!-- User Settings Modal -->
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


    <!-- Footer --> 
    <footer class="footer"> 
        <div id="clock" class="time"></div> 
        <div id="date" class="date"></div> 
    </footer>
    <script>
    // Pass server-generated data to JavaScript
    const serverTimestamp = <?php echo $serverTimestamp; ?>;
    </script>
    <script src="transaction.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</body>
</html>