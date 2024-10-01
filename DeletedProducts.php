<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deleted Products</title>
  <link rel="stylesheet" href="CSS\DeletedProducts.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header>
    <h2 class="StockHeader">Deleted Products</h2>
  </header>
  
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

  <div class="container">
    <div class="account-box">
      <div class="button-container">
        <button class="btn" onclick="location.href='Records.php'">Top Selling</button>
        <button class="btn" onclick="location.href='SoldItems.php'">Sold Items</button>
        <button class="btn" onclick="location.href='CriticalStocks.php'">Critical Stocks</button>
        <button class="btn" onclick="location.href='InventoryList.php'">Inventory List</button>
        <button class="btn" onclick="location.href='CancelledOrder.php'">Cancelled Order</button>
        <button class="btn" onclick="location.href='StockHistory.php'">Stock In History</button>
        <button class="btn" onclick="location.href='DeletedProducts.php'">Deleted Products</button>
      </div>
      <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>

      <div class="form">
        <form id="filter-form" method="GET" action="DeletedProducts.php">
          <div class="form-group">
            <label for="startDate" class="date-label">Filter by</label>
            <input type="date" id="startDate" name="startDate" class="date-input" value="<?php echo isset($_GET['startDate']) ? htmlspecialchars($_GET['startDate']) : ''; ?>">
            <input type="date" id="endDate" name="endDate" class="date-input" value="<?php echo isset($_GET['endDate']) ? htmlspecialchars($_GET['endDate']) : ''; ?>">
            <button type="submit" class="load-data-button">
              <i class="fa fa-refresh"></i>
              <span class="load-data-text">Load Data</span>
            </button>
            <div class="print-preview-button" onclick="window.print()">
          <i class="fa-solid fa-print"></i>
          <span class="print-preview-text">Print Preview</span>
        </div>
          </div>
        </form>
      </div>

      <div class="content">
        <div class="table-container">
        <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Barcode</th>
                <th>Reference Number</th>
                <th>Description</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Price</th>
                <th>Vendor</th>
                <th>Deleted By</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Database connection
              require 'connect.php'; // Adjust this path to your actual connection file

              // Set default query
              $query = "SELECT * FROM deleted_products";

              // Check for date filtering
              if (isset($_GET['startDate']) && isset($_GET['endDate'])) {
                  $startDate = $_GET['startDate'];
                  $endDate = $_GET['endDate'];

                  // Adjust query to filter by date if both dates are provided
                  if (!empty($startDate) && !empty($endDate)) {
                      $query .= " WHERE DATE(date_deleted) BETWEEN '$startDate' AND '$endDate'";
                  }
              }

              $result = mysqli_query($conn, $query);

              if (mysqli_num_rows($result) > 0) {
                  $counter = 1;
                  while ($row = mysqli_fetch_assoc($result)) {
                      echo "<tr>";
                      echo "<td>" . $counter++ . "</td>";
                      echo "<td>" . htmlspecialchars($row['Barcode']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['reference']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Brand']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Category']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Price']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Vendor']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['deleted_by']) . "</td>"; // Displaying the admin who deleted the product
                      echo "</tr>";
                  }
              } else {
                echo "<tr><td colspan='9' style='text-align: center;'>No deleted products found.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
