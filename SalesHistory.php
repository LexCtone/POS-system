<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Records</title>
  <link rel="stylesheet" href="CSS\SalesHistory.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="JAVASCRIPT/sales_history.js" defer></script>
</head>
<body>
  <header>
    <h2 class="StockHeader">Sales History</h2>
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
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
      </ul>
    </nav>
     <div class="container">
      <div class="account-box">
      <div class="button-container">
          <button class="btn" onclick="location.href='Records.php'">Top Selling</button>
          <button class="btn" onclick="location.href='SalesHistory.php'">Sales History</button>
          <button class="btn" onclick="location.href='CriticalStocks.php'">Critical Stocks</button>
          <button class="btn" onclick="location.href='InventoryList.php'">Inventory List</button>
          <button class="btn" onclick="location.href='CancelledOrder.php'">Cancelled Order</button>
          <button class="btn" onclick="location.href='StockHistory.php'">Stock In History</button>
          <button class="btn" onclick="location.href='ArchivedProducts.php'">Archived Products</button>
          </div>
      <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>
    <div class="form">
      <div class="form-group">
        <label for="startDate" class="date-label">Filter by</label>
        <input type="date" id="startDate" name="startDate" class="date-input">
        <input type="date" id="endDate" name="endDate" class="date-input">
        <select id="vendor" class="vendor" name="vendor">
          <option value=""  selected>All Cashier</option>
          <?php
          include 'connect.php';
          $stmt = $conn->prepare("SELECT DISTINCT username FROM accounts WHERE role = 'cashier' ORDER BY username");
          $stmt->execute();
          $result = $stmt->get_result();
          while ($row = $result->fetch_assoc()) {
            echo "<option value='" . htmlspecialchars($row['username']) . "'>" . htmlspecialchars($row['username']) . "</option>";
          }
          $stmt->close();
          $conn->close();
          ?>
        </select>
        </select>
        <select id="status" class="status" name="status">
          <option value="" selected>All Status</option>
          <option value="Active">Active</option>
          <option value="Voided">Voided</option>
        </select>
        <div class="load-data-button">
          <i class="fa fa-refresh"></i>
          <span class="load-data-text">Load Data</span>
        </div>
        <div class="print-preview-button">
          <i class="fa-solid fa-print"></i>
          <span class="print-preview-text">Print Preview</span>
        </div>
        <div class="total-sales">
          Total Sales: ₱<span id="totalActiveSales">0.00</span>
        </div>
      </div>
    </div>
      <div class="content">
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Invoice</th>
                <th>Barcode</th>
                <th>Description</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Discount amount</th>
                <th>Total</th>
                <th>Date sold</th>
                <th>Cashier Name</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
    </div>
  </div>
</body>
</html>