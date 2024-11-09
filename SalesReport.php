<?php
session_start();
include 'connect.php';

// Fetch the username of the logged-in admin
$admin_name = "ADMINISTRATOR";
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

// Filter data by date
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;

// Default values for cashier, status, and sortBy
$cashier = isset($_GET['cashier']) ? $_GET['cashier'] : 'All Cashier';
$status = isset($_GET['status']) ? $_GET['status'] : 'All Status';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : ''; // Add sortBy handling

// SQL query to fetch sales data with profit calculation
$query_sales = "
    SELECT s.invoice, s.sale_date, s.description, s.total, s.discount_amount, 
           s.quantity, (s.total - s.discount_amount - (p.cost_price * s.quantity)) AS profit 
    FROM sales s 
    JOIN products p ON s.barcode = p.Barcode 
    WHERE s.status = 'active'";

// Add date filters if selected
if ($startDate && $endDate) {
    $query_sales .= " AND DATE(s.sale_date) BETWEEN ? AND ?";
}

// Add sorting condition based on the selected dropdown option
if ($sortBy == 'quantity') {
    $query_sales .= " ORDER BY s.quantity DESC"; // Sort by quantity in descending order
} elseif ($sortBy == 'totalAmount') {
    $query_sales .= " ORDER BY s.total DESC"; // Sort by total amount in descending order
}

$stmt = $conn->prepare($query_sales);

if ($startDate && $endDate) {
    $stmt->bind_param("ss", $startDate, $endDate);
}

$stmt->execute();
$result = $stmt->get_result();

// Initialize total sales, total discounts, and total profit
$total_sales = 0;
$total_discounts = 0;
$total_profit = 0;

// SQL query to calculate total sales, discounts, and profit based on filters
$sql = "
    SELECT SUM(s.total) AS total_sales, 
           SUM(s.discount_amount) AS total_discounts, 
           SUM(s.total - s.discount_amount - (p.cost_price * s.quantity)) AS total_profit
    FROM sales s 
    JOIN products p ON s.barcode = p.Barcode 
    WHERE s.status = 'active'";

// Add date range filter
if ($startDate && $endDate) {
    $sql .= " AND DATE(s.sale_date) BETWEEN '$startDate' AND '$endDate'";
}

// Add cashier filter if applicable
if ($cashier != 'All Cashier') {
    $sql .= " AND s.cashier_name = '$cashier'";
}

// Add status filter if applicable
if ($status != 'All Status') {
    $sql .= " AND s.status = '$status'";
}

// Execute the total sales, discounts, and profit query
$total_sales_profit_result = mysqli_query($conn, $sql);

if ($total_sales_profit_result) {
    $row = mysqli_fetch_assoc($total_sales_profit_result);
    $total_sales = $row['total_sales'] ?? 0; // Safely handle null values
    $total_discounts = $row['total_discounts'] ?? 0;
    $total_profit = $row['total_profit'] ?? 0; // Safely handle null values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales Report</title>
  <link rel="stylesheet" href="CSS/SalesReport.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <header>
    <h2 class="StockHeader">Sales Report</h2>
  </header>
  
  <nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li>
        <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product
        <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i> <!-- Submenu symbol added -->
        </a></li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="PurchaseOrder.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php" class="selected"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="account-box">
        <div class="button-container">
            <button class="btn" onclick="location.href='Records.php'">Top Selling</button>
            <button class="btn" onclick="location.href='SalesHistory.php'">Sales History</button>
            <button class="btn" onclick="location.href='CancelledOrder.php'">Cancelled Order</button>
            <button class="btn" onclick="location.href='StockHistory.php'">Stock In History</button>
            <button class="btn" onclick="location.href='ArchivedProducts.php'">Archived Products</button>
            <button class="btn selected" onclick="location.href='SalesReport.php'">Sales Report</button>
            <button class="btn" onclick="location.href='CriticalStocks.php'">Critical Stocks</button>
            <button class="btn" onclick="location.href='InventoryList.php'">Inventory List</button>
        </div>
        <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>

        <div class="form">
            <form id="filter-form" method="GET" action="SalesReport.php">
                <div class="form-group">
                    <label for="startDate" class="date-label">Filter by</label>
                    <input type="date" id="startDate" name="startDate" class="date-input" value="<?php echo isset($_GET['startDate']) ? htmlspecialchars($_GET['startDate']) : ''; ?>">
                    <input type="date" id="endDate" name="endDate" class="date-input" value="<?php echo isset($_GET['endDate']) ? htmlspecialchars($_GET['endDate']) : ''; ?>">
                    <label for="sortBy"></label>
                    <select id="sortBy" name="sortBy" class="dropdown-input">
                        <option value="">Recent</option>
                        <option value="quantity" <?php echo ($sortBy == 'quantity') ? 'selected' : ''; ?>>Quantity</option>
                        <option value="totalAmount" <?php echo ($sortBy == 'totalAmount') ? 'selected' : ''; ?>>Total Amount</option>
                    </select>
                    <button type="submit" class="load-data-button">
                      <i class="fa fa-refresh"></i>
                      <span class="load-data-text">Load Data</span>
                    </button>
                    <div class="print-preview-button" onclick="window.print()">
                      <i class="fa-solid fa-print"></i>
                      <span class="print-preview-text">Print Preview</span>
                    </div>
                    <div class="total-sales-display" style="background-color: black; color: white; padding: 10px; margin-left: 20px; border-radius: 5px;">
                      Total Sales: ₱<?php echo number_format($total_sales, 2); ?>
                  </div>
                  <div class="total-profit-display" style="background-color: black; color: white; padding: 10px; margin-left: 20px; border-radius: 5px;">
                      Total Profit: ₱<?php echo number_format($total_profit, 2); ?>
                  </div>
                </div>
            </form>
        </div>

        <div class="content">
            <div class="table-container">
                <table class="table" id="product-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Date & Time</th>
                            <th>Description</th>
                            <th>Quantity</th> <!-- Add quantity here -->
                            <th>Total Amount</th>
                            <th>Discount</th>
                            <th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo $row['invoice']; ?></td> <!-- Display Invoice -->
                            <td><?php echo $row['sale_date']; ?></td>
                            <td><?php echo $row['description']; ?></td>
                            <td><?php echo $row['quantity']; ?></td> <!-- Display quantity -->
                            <td><?php echo number_format($row['total'], 2); ?></td>
                            <td><?php echo number_format($row['discount_amount'], 2); ?></td>
                            <td><?php echo number_format($row['profit'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
        // Function to show the modal
        function confirmLogout() {
            document.getElementById("logoutModal").style.display = "block"; // Show the modal
            return false; // Prevent the default link action
        }

        // Function to close the modal
        function closeLogoutModal() {
            document.getElementById("logoutModal").style.display = "none"; // Hide the modal
        }

        // Close the modal if the user clicks anywhere outside of it
        window.onclick = function(event) {
            var modal = document.getElementById("logoutModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };

        // Confirm logout action
        document.getElementById("confirmLogout").onclick = function() {
            window.location.href = "Login.php"; // Redirect to the login page or handle logout
        };
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
