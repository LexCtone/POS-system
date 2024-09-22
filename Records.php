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
      <div class="form-group">
    <label for="startDate" class="date-label">Filter by</label>
    <input type="date" id="startDate" name="startDate" class="date-input">
    <input type="date" id="endDate" name="endDate" class="date-input">
    <select id="vendor" class="vendor" name="vendor">
        <option value="" disabled selected>Sort by</option>
        <option class="option" value="Jeremy">Jeremy</option>
        <option class="option" value="Bong">Bong</option>            
    </select>
    <div class="load-data-button">
        <i class="fa fa-refresh"></i>
        <span class="load-data-text">Load Data</span>
    </div>
    <div class="print-preview-button">
        <i class="fa-solid fa-print"></i>
        <span class="print-preview-text">Print Preview</span>
    </div>
</div>
<div class="content">
      <!-- Left Column: Table -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>PCODE</th>
              <th>Description</th>
              <th>QTY</th>
              <th>TOTAL SALES</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>P0001</td>
              <td>Heavy Duty Hammer</td>
              <td>21</td>
              <td>10,569.00</td>
            </tr>
            <tr>
              <td>2</td>
              <td>P0002</td>
              <td>Steel Phillips Head Screws</td>
              <td>21</td>
              <td>7,569.00</td>
            </tr>
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
</div>
<script>
  const ctx = document.getElementById('salesChart').getContext('2d');
  const salesChart = new Chart(ctx, {
    type: 'doughnut', // or 'pie'
    data: {
      labels: ['P0001', 'P0002'],
      datasets: [{
        data: [10569, 7569],
        backgroundColor: ['#1C75A6', '#4FC3F7'],
        hoverBackgroundColor: ['#1976D2', '#29B6F6']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right'
        }
      }
    }
  });
</script>
</body>
</html>
