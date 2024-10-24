<?php
session_start();
// Include your database connection file here
require_once 'connect.php';

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

// Ensure the database connection is established
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? "Unknown error"));
}

// Fetch products with quantity below 20
$query = "SELECT id, Barcode, Description, Price, Quantity FROM products WHERE Quantity < 20 ORDER BY Quantity ASC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Critical Stocks</title>
  <link rel="stylesheet" href="CSS\CriticalStocks.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header>
    <h2 class="StockHeader">Records</h2>
  </header>
  
  <nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li> <!-- Added Dashboard back -->
        <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product</a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="StockEntry.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
        <li><a href="Records.php" class="selected"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>
  <div class="container">
    <div class="account-box">
      <div class="button-container">
        <button class="btn" onclick="location.href='Records.php'">Top Selling</button>
        <button class="btn" onclick="location.href='SalesHistory.php'">Sales History</button>
        <button class="btn selected" onclick="location.href='CriticalStocks.php'">Critical Stocks</button>
        <button class="btn" onclick="location.href='InventoryList.php'">Inventory List</button>
        <button class="btn" onclick="location.href='CancelledOrder.php'">Cancelled Order</button>
        <button class="btn" onclick="location.href='StockHistory.php'">Stock In History</button>
        <button class="btn" onclick="location.href='ArchivedProducts.php'">Archived Products</button>
      </div>
      <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>
      <div class="print-preview-button" onclick="window.print()">
      <i class="fa-solid fa-print"></i>
        <span class="print-preview-text">Print Preview</span>
    </div>
</div>
<div class="content">
      <div class="table-container">
       <table class="table" id="critical-table">
        <thead>
            <tr>
              <th>#</th>
              <th>BARCODE</th>
              <th>DESCRIPTION</th>
              <th>PRICE</th>
              <th>No. OF STOCKS</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $counter . "</td>";
                    echo "<td>" . htmlspecialchars($row["Barcode"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["Description"]) . "</td>";
                    echo "<td>₱" . number_format($row["Price"], 2) . "</td>";
                    echo "<td>" . $row["Quantity"] . "</td>";
                    echo "</tr>";
                    $counter++;
                }
            } else {
              echo "<tr><td colspan='5' style='text-align: center;'>No critical stocks found</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script>
    // Add any JavaScript you need here
  </script>
</body>
</html>