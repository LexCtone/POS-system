<?php
session_start();
// Database connection
include 'connect.php'; // Ensure this path is correct and the file exists

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Records</title>
  <link rel="stylesheet" href="CSS\Records.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
    <h2 class="PageHeader">
        <div class="flex-container">
            <span class="header-title">Top Selling</span>
        </div>
    </h2>
</header>

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
        <li><a href="PurchaseOrder.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php" class="selected"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav> 
  <div class="container">
    <div class="account-box">
      <div class="button-container">
        <button class="btn selected" onclick="location.href='Records.php'" class="selected">Top Selling</button>
        <button class="btn" onclick="location.href='SalesHistory.php'">Sales History</button>
        <button class="btn" onclick="location.href='CancelledOrder.php'">Cancelled Order</button>
        <button class="btn" onclick="location.href='StockHistory.php'">Stock In History</button>
        <button class="btn" onclick="location.href='ArchivedProducts.php'">Archived Products</button>
        <button class="btn" onclick="location.href='SalesReport.php'">Sales Report</button>
        <button class="btn" onclick="location.href='CriticalStocks.php'">Critical Stocks</button>
        <button class="btn" onclick="location.href='InventoryList.php'">Inventory List</button>
        <button class="btn" onclick="location.href='Barcodes.php'">Barcodes</button>
      </div>
      <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>
      <div class="form">
        <div class="form-group">
          <label for="startDate" class="date-label">Filter by</label>
          <input type="date" id="startDate" name="startDate" class="date-input">
          <input type="date" id="endDate" name="endDate" class="date-input">
          <select id="sortBy" class="vendor" name="sortBy">
            <option value="" selected>Sort by</option>
            <option value="quantity">Quantity</option>
            <option value="total_amount">Total Amount</option>
          </select>
          <select id="sortOrder" class="vendor" name="sortOrder">
            <option value="DESC" selected>Descending</option>
            <option value="ASC">Ascending</option>
          </select>
          <div class="load-data-button" onclick="loadData()">
            <i class="fa fa-refresh"></i>
            <span class="load-data-text">Load Data</span>
          </div>
          <div class="print-preview-button" onclick="printTable()">
              <i class="fa-solid fa-print"></i>
              <span class="print-preview-text">Print Table</span>
          </div>
        </div>
      </div>


      <div class="content">
        <!-- Left Column: Table -->

        <div class="table-container">
        <div id="reportHeader" class="report-header" style="display: none;">
            <!-- Content will be dynamically populated by JavaScript -->
          </div>
        <div id="sortInfo" class="sort-info" style="display: none;">

          </div>
          <table class="table" id="sales-table">
            <thead>
              <tr>
                <th>#</th>
                <th>BARCODE</th>
                <th>DESCRIPTION</th>
                <th>QUANTITY</th>
                <th>TOTAL SALES</th>
              </tr>
            </thead>
            <tbody>
              <!-- Table body will be populated by JavaScript -->
            </tbody>
          </table>
        </div>
        <!-- Right Column: Chart -->
        <div class="chart-container">
          <div class="chart-legend" id="chartLegend"></div>
          <canvas id="salesChart"></canvas>
        </div>
      </div>
    </div>
  </div>
<script type="text/javascript" src="JAVASCRIPT/Records.js" defer></script>
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