<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Records</title>
  <link rel="stylesheet" href="CSS\CancelledOrder.css">
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
        <button class="btn" onclick="location.href='SalesHistory.php'">Sales History</button>
        <button class="btn" onclick="location.href='CriticalStocks.php'">Critical Stocks</button>
        <button class="btn" onclick="location.href='InventoryList.php'">Inventory List</button>
        <button class="btn" onclick="location.href='CancelledOrder.php'">Cancelled Order</button>
        <button class="btn" onclick="location.href='StockHistory.php'">Stock In History</button>
        <button class="btn" onclick="location.href='DeletedProducts.php'">Deleted Products</button>
      </div>
      <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>

      <div class="form">
  <div class="form-group">
    <label for="startDate" class="date-label">Filter by</label>
    <input type="date" id="startDate" name="startDate" class="date-input">
    <input type="date" id="endDate" name="endDate" class="date-input">
  <div class="load-data-button">
    <i class="fa fa-refresh"></i>
    <span class="load-data-text">Load Data</span>
  </div>
  <div class="print-preview-button">
        <i class="fa-solid fa-print"></i>
        <span class="print-preview-text">Print Preview</span>
    </div>
    <div>
  <label for="toggleView">View:</label>
  <button id="toggleView">Switch to Transaction View</button>
</div>
  </div>
</div>
<div class="content">
  <div class="table-container">
    <table id="recordsTable">
      <thead>
        <tr id="tableHeader">
          <!-- Table headers will be dynamically updated -->
        </tr>
      </thead>
      <tbody id="tableBody">
        <!-- Table rows will be dynamically updated -->
      </tbody>
    </table>
  </div>
</div>
<script type="text/javascript" src="JAVASCRIPT/CancelledOrder.js" defer></script>
</body>
</html>
