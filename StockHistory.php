<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock History</title>
  <link rel="stylesheet" href="CSS\StockHistory.css">
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
      </div>
      <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>

      <div class="form">
        <form id="filter-form" method="GET" action="StockHistory.php">
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
                <th>BARCODE</th>
                <th>REF#</th>
                <th>Description</th>
                <th>QTY</th>
                <th>STOCK IN DATE</th>
                <th>STOCK IN BY</th>
                <th>VENDOR</th>
              </tr>
            </thead>
            <tbody>
            <?php
            // Include database connection
            include 'connect.php';

            // Initialize variables for date range filtering
            $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
            $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

            // Base query
            $query = "SELECT * FROM stock_in_history";

            // Apply date range filtering if both dates are provided
            if ($startDate && $endDate) {
                $query .= " WHERE stock_in_date BETWEEN ? AND ?";
            }

            // Default sorting
            $query .= " ORDER BY stock_in_date DESC";

            // Prepare the statement
            $stmt = $conn->prepare($query);

            // Bind parameters if filtering
            if ($startDate && $endDate) {
                $stmt->bind_param("ss", $startDate, $endDate);
            }

            // Execute the query
            $stmt->execute();
            $result = $stmt->get_result();

            $rowId = 1;
            // Check if the Barcode column data is fetched
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $rowId++ . "</td>";
                    echo "<td>" . htmlspecialchars($row['Barcode']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['reference']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['stock_in_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['stock_in_by']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['vendor']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8'>No records found.</td></tr>";
            }

            $stmt->close();
            $conn->close();
            ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script>
    function clearFilters() {
      window.location.href = 'StockHistory.php';
    }
  </script>
</body>
</html>
