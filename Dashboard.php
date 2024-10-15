<?php
session_start();
// Connect to the database
include('connect.php');

// Query for daily sales
$query_daily_sales = "SELECT SUM(total) as daily_sales FROM sales WHERE DATE(sale_date) = CURDATE()";
$result_daily_sales = $conn->query($query_daily_sales);
$daily_sales = 0;
if ($result_daily_sales && $row = $result_daily_sales->fetch_assoc()) {
    $daily_sales = $row['daily_sales'] ? $row['daily_sales'] : 0;
}

// Query for stock on hand
$query_stock_on_hand = "SELECT SUM(Quantity) as stock_on_hand FROM products";
$result_stock_on_hand = $conn->query($query_stock_on_hand);
$stock_on_hand = 0;
if ($result_stock_on_hand && $row = $result_stock_on_hand->fetch_assoc()) {
    $stock_on_hand = $row['stock_on_hand'] ? $row['stock_on_hand'] : 0;
}

// Query for critical items
$query_critical_items = "SELECT COUNT(*) as critical_items FROM products WHERE Quantity < 10";
$result_critical_items = $conn->query($query_critical_items);
$critical_items = 0;
if ($result_critical_items && $row = $result_critical_items->fetch_assoc()) {
    $critical_items = $row['critical_items'] ? $row['critical_items'] : 0;
}
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
  <title>Dashboard</title>
  <link rel="stylesheet" type="text/css" href="CSS/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <div class="sidebar">
    <header>
      <img src="profile.png" alt="profile"/><br>
    <?php echo htmlspecialchars($admin_name); ?>
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
  </div>
  
  <div class="container">
    <div class="orange" id="box1">
        <?= number_format($daily_sales, 2) ?><br> DAILY SALES
    </div>
    <div class="yellow" id="box2">
        <?= number_format($stock_on_hand) ?><br> STOCK ON HAND
    </div>
    <div class="green" id="box3">
        <?= number_format($critical_items) ?><br> CRITICAL ITEMS
    </div>
  </div>
  <div class="pie-chart">
    <div class="slice" style="--percentage: 30;"></div>
    <div class="slice" style="--percentage: 20;"></div>
    <div class="text-at-40">40%</div>
    <div class="text-at-60">60%</div>
    <div class="slice" style="--percentage: 50;"></div>
    <div class="inner-circle"></div>
  </div>

  <div class="small-container">
    <div class="first box-container">2023</div>
    <div class="second box-container">2024</div>
  </div>
</body>
</html>
