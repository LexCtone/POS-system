<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Records</title>
  <link rel="stylesheet" href="CSS\SalesHistory.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
              <li><a href="SalesHistory.php"><i class='fa-solid fa-clock-rotate-left' style='font-size:30px'></i>Sales History</a></li>
              <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
              <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
              </ul>
  </nav>
  <div class="container">
      <div class="form">
      <div class="form-group">
    <label for="startDate" class="date-label">Filter by</label>
    <input type="date" id="startDate" name="startDate" class="date-input">
    <input type="date" id="endDate" name="endDate" class="date-input">
    <select id="vendor" class="vendor" name="vendor">
        <option value="" disabled selected>sold by</option>
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
              <th>Invoce</th>
              <th>Pcode</th>
              <th>Description</th>
              <th>Price</th>
              <th>QTY</th>
              <th>DISC.</th>
              <th>Total.</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>21313123</td>
              <td>P0001</td>
              <td>Heavy Duty Hammer</td>
              <td>500.00</td>
              <td>1</td>
              <td>1.00</td>
              <td>499</td>
            </tr>
            <tr>
            <td>2</td>
              <td>123123123</td>
              <td>P0002</td>
              <td>Heavy Duty Toyo</td>
              <td>2500.00</td>
              <td>1</td>
              <td>1.00</td>
              <td>2499.00</td>
            </tr>
          </tbody>
        </table>
      </div>
</body>
</html>
