<?php
session_start();
// Enable logging of errors to a file (instead of outputting them on the page)

// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Connect to the database
include('connect.php');

// Fetch daily sales and daily profit
$today = date('Y-m-d'); // Current date
$query_daily = "
    SELECT SUM(total) AS daily_sales, 
           SUM(total - discount_amount - (p.cost_price * s.quantity)) AS daily_profit
    FROM sales s
    JOIN products p ON s.barcode = p.Barcode
    WHERE s.status = 'active' AND DATE(s.sale_date) = ?
";
$stmt_daily = $conn->prepare($query_daily);
$stmt_daily->bind_param("s", $today);
$stmt_daily->execute();
$result_daily = $stmt_daily->get_result();
$daily_sales = 0;
$daily_profit = 0;
if ($row_daily = $result_daily->fetch_assoc()) {
    $daily_sales = $row_daily['daily_sales'] ?? 0;
    $daily_profit = $row_daily['daily_profit'] ?? 0;
}
$stmt_daily->close();

// Ensure that $daily_sales and $daily_profit are properly converted to floats
$daily_sales = (float)$daily_sales;
$daily_profit = (float)$daily_profit;

// Fetch annual sales and annual profit (for the current year)
$current_year = date('Y');
$query_annual = "
    SELECT SUM(total) AS annual_sales, 
           SUM(total - discount_amount - (p.cost_price * s.quantity)) AS annual_profit
    FROM sales s
    JOIN products p ON s.barcode = p.Barcode
    WHERE s.status = 'active' AND YEAR(s.sale_date) = ?
";
$stmt_annual = $conn->prepare($query_annual);
$stmt_annual->bind_param("s", $current_year);
$stmt_annual->execute();
$result_annual = $stmt_annual->get_result();
$annual_sales = 0;
$annual_profit = 0;
if ($row_annual = $result_annual->fetch_assoc()) {
    $annual_sales = $row_annual['annual_sales'] ?? 0;
    $annual_profit = $row_annual['annual_profit'] ?? 0;
}
$stmt_annual->close();

// Ensure that $annual_sales and $annual_profit are properly converted to floats
$annual_sales = (float)$annual_sales;
$annual_profit = (float)$annual_profit;

// Fetch monthly sales and profit (grouped by month)
$query_monthly = "
    SELECT MONTH(s.sale_date) AS month, 
           SUM(s.total) AS monthly_sales, 
           SUM(s.total - s.discount_amount - (p.cost_price * s.quantity)) AS monthly_profit
    FROM sales s
    JOIN products p ON s.barcode = p.Barcode
    WHERE s.status = 'active' AND YEAR(s.sale_date) = ?
    GROUP BY MONTH(s.sale_date)
";
$stmt_monthly = $conn->prepare($query_monthly);
$stmt_monthly->bind_param("s", $current_year);
$stmt_monthly->execute();
$result_monthly = $stmt_monthly->get_result();

$monthly_sales = [];
$monthly_profit = [];
for ($i = 1; $i <= 12; $i++) {
    $monthly_sales[$i] = 0; // Initialize months 1-12 to zero
    $monthly_profit[$i] = 0;
}

while ($row = $result_monthly->fetch_assoc()) {
    $month = $row['month'];
    $monthly_sales[$month] = (float)$row['monthly_sales']; // Ensure it's a float
    $monthly_profit[$month] = (float)$row['monthly_profit']; // Ensure it's a float
}
$stmt_monthly->close();

// Fetch weekly sales and profit (grouped by week for the last 30 days)
$query_weekly = "
    SELECT WEEK(s.sale_date) AS week, 
           SUM(s.total) AS weekly_sales, 
           SUM(s.total - s.discount_amount - (p.cost_price * s.quantity)) AS weekly_profit
    FROM sales s
    JOIN products p ON s.barcode = p.Barcode
    WHERE s.status = 'active' AND DATE(s.sale_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY WEEK(s.sale_date)
";
$result_weekly = $conn->query($query_weekly);

$weekly_sales = [];
$weekly_profit = [];
for ($i = 1; $i <= 5; $i++) { // Handle last 5 weeks
    $weekly_sales[$i] = 0; 
    $weekly_profit[$i] = 0;
}

while ($row = $result_weekly->fetch_assoc()) {
    $week = $row['week'];
    $weekly_sales[$week] = (float)$row['weekly_sales']; // Ensure it's a float
    $weekly_profit[$week] = (float)$row['weekly_profit']; // Ensure it's a float
}

// Fetch daily sales and profit for the last 7 days, grouped by day of the week
$query_daily_week = "
    SELECT DAYNAME(s.sale_date) AS day_name, 
           SUM(s.total) AS daily_sales, 
           SUM(s.total - s.discount_amount - (p.cost_price * s.quantity)) AS daily_profit
    FROM sales s
    JOIN products p ON s.barcode = p.Barcode
    WHERE s.status = 'active' 
    AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY day_name
    ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
";

$result_daily_week = $conn->query($query_daily_week);

$weekly_sales = [];
$weekly_profit = [];
$weekly_labels = [];

// Reset the arrays to ensure correct indexing
$weekly_sales = array_fill(0, 7, 0);
$weekly_profit = array_fill(0, 7, 0);
$weekly_labels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

while ($row = $result_daily_week->fetch_assoc()) {
    $day_name = $row['day_name']; // Get the day name
    $index = array_search($day_name, $weekly_labels); // Find the index of the day name

    if ($index !== false) { // Ensure the day exists in the labels
        $weekly_sales[$index] = (float)$row['daily_sales']; // Daily sales as float
        $weekly_profit[$index] = (float)$row['daily_profit']; // Daily profit as float
    }
}

// Encode for JavaScript
$weekly_sales_json = json_encode($weekly_sales);
$weekly_profit_json = json_encode($weekly_profit);
$weekly_labels_json = json_encode($weekly_labels);
// Encode for JavaScript usage (monthly and weekly)
$monthly_sales_json = json_encode(array_values($monthly_sales));
$monthly_profit_json = json_encode(array_values($monthly_profit));

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


// Query to fetch the top 10 selling products for the pie chart
$query_top_10_dashboard = "
    SELECT products.Description, SUM(sales.quantity) AS total_quantity
    FROM sales
    JOIN products ON sales.barcode = products.Barcode
    GROUP BY products.Description
    ORDER BY total_quantity DESC
    LIMIT 10";
$result_top_10_dashboard = $conn->query($query_top_10_dashboard);

$labels_dashboard = [];
$data_dashboard = [];

if ($result_top_10_dashboard) {
    while ($row = $result_top_10_dashboard->fetch_assoc()) {
        $labels_dashboard[] = $row['Description'];
        $data_dashboard[] = (float)$row['total_quantity']; // Ensure it's a float
    }
} else {
    die("Query Error: " . $conn->error);  // Handle query errors
}

// Encode data for JavaScript usage
$labels_json_dashboard = json_encode($labels_dashboard);
$data_json_dashboard = json_encode($data_dashboard);

// Encode monthly and weekly data for JavaScript usage
$monthly_sales_json = json_encode(array_values($monthly_sales));
$monthly_profit_json = json_encode(array_values($monthly_profit));
$weekly_sales_json = json_encode(array_values($weekly_sales));
$weekly_profit_json = json_encode(array_values($weekly_profit));
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
<nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php" class="selected"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li> <!-- Added Dashboard back -->
        <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product</a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="StockEntry.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>
<header>    
        <h2 class="ProductHeader">Dashboard 
            <input id="search-input" type="text" placeholder="Search...">
            <button id="add-product-button"><i class='fas fa-plus'></i></button>
        </h2>    
    </header>
    <div>
    <div class="main-content-wrapper">
    <main class="main-content">
        <header class="header">
            <h2 style='text-align: left'>Sales Overview</h2>
        </header>
        <section class="cards">
            <div class="card" id="annual-sales">
                <i class="fas fa-chart-line icon-style"></i> 
                Annual Sales<br>
                ₱<?php echo number_format($annual_sales, 2); ?> <!-- PHP variable for Annual Sales -->
            </div>
            <div class="card" id="annual-profit">
                <i class="fas fa-exclamation-triangle icon-style"></i> 
                Annual Profit<br>
                ₱<?php echo number_format($annual_profit, 2); ?> <!-- PHP variable for Annual Profit -->
            </div>
            <div class="card" id="daily-sales">
                <i class="fas fa-box icon-style"></i> 
                Daily Sales<br>
                ₱<?php echo number_format($daily_sales, 2); ?> <!-- PHP variable for Daily Sales -->
            </div>
            <div class="card" id="daily-profit">
                <i class="fas fa-archive icon-style"></i> 
                Daily Profit<br>
                ₱<?php echo number_format($daily_profit, 2); ?> <!-- PHP variable for Daily Profit -->
            </div>
        </section>
    </main>
    <div id="annual_sales_json" style="display:none;"><?php echo $annual_sales; ?></div>
    <div id="annual_profit_json" style="display:none;"><?php echo $annual_profit; ?></div>
    <div id="daily_sales_json" style="display:none;"><?php echo $daily_sales; ?></div>
    <div id="daily_profit_json" style="display:none;"><?php echo $daily_profit; ?></div>
    <div id="monthly_sales_json" style="display:none;"><?php echo $monthly_sales_json; ?></div>
    <div id="monthly_profit_json" style="display:none;"><?php echo $monthly_profit_json; ?></div>
    <div id="weekly_sales_json" style="display:none;"><?php echo $weekly_sales_json; ?></div>
    <div id="weekly_profit_json" style="display:none;"><?php echo $weekly_profit_json; ?></div>
    <div id="weekly_labels_json" style="display:none;"><?php echo $weekly_labels_json; ?></div>
    </div>
    </div>


  <div class="Purchase-overview">
    <main class="purchase-content">
      <header class="header">
        <h2 style='text-align: left'>Purchase Overview</h2>
      </header>
      <section class="cards">
        <div class="card">
          <i class="fas fa-chart-line icon-style"></i> No. of purchase<br>£12,458
        </div>
        <div class="card">
          <i class="fas fa-exclamation-triangle icon-style"></i> Cancel Orders<br>£8,248
        </div>
        <div class="card">
          <i class="fas fa-box icon-style"></i> Purchase Amount<br>£880
        </div>
      </section>
    </main>
  </div>
  <div class="Stock">
    <main class="Stock-content">
      <header class="header">
        <h2 style='text-align: left'>Stocks</h2>
      </header>
      <section class="cards">
        <div class="card">
          <i class="fas fa-chart-line icon-style"></i> Stock on hand<br>£12,458
        </div>
        <div class="card">
          <i class="fas fa-chart-line icon-style"></i> Critical Stocks<br>£12,458
        </div>
      </section>
    </main>
    <div class="Todo">
    <main class="Todo-content">
      <header class="header">
      <h2 style="text-align: left; display: inline-block;">To-do list</h2>
<button 
    style="margin-left: 150px; padding: 5px 10px; font-size: 16px; cursor: pointer;" 
    onclick="addTodo()">
    Add
</button>

<ul id="todoList" style="list-style-type: none; padding: 0;"></ul>

<script>
function addTodo() {
    const todoText = prompt("Enter a new to-do item:");
    if (todoText) {
        const todoList = document.getElementById("todoList");

        // Create the <li> element
        const listItem = document.createElement("li");
        listItem.style.display = "flex";
        listItem.style.alignItems = "center";
        listItem.style.marginBottom = "5px";

        // Create the radio button
        const radioButton = document.createElement("input");
        radioButton.type = "radio";
        radioButton.style.marginRight = "10px";

        // Attach click event to remove the item
        radioButton.onclick = () => todoList.removeChild(listItem);

        // Set the item text
        const itemText = document.createElement("span");
        itemText.textContent = todoText;

        // Append the radio button and text to the <li>
        listItem.appendChild(radioButton);
        listItem.appendChild(itemText);

        // Append the <li> to the to-do list
        todoList.appendChild(listItem);
    }
}
</script>
      </header>
      <section class="cards">
      </section>
    </main>
  </div>
  <div class="linechart-overview">
    <main class="linechart-content">
      <header class="header">
        <h2 style='text-align: left'>Statistics</h2>
      </header>
      <div class="chart-container" style="width: 80%; margin: 30px auto;">
    <canvas id="myLineChart"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </main>
  </div>
  <div class="piechart-overview">
  <main class="piechart-content">
    <header class="header">
      <h2 style='text-align: left'>Top Selling</h2>
    </header>
    <div class="chart-container" style="width: 100%; height: 1000px;">
      <canvas id="myPieChart"></canvas>
      <canvas id="legendLineCanvas" style="position: absolute; top: 0; left: 0; pointer-events: none;"></canvas>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="JAVASCRIPT/dashboard.js"></script>
<div id="labels_json_dashboard" style="display:none;"><?php echo json_encode($labels_dashboard); ?></div>
<div id="data_json_dashboard" style="display:none;"><?php echo json_encode($data_dashboard); ?></div>
</body>
</html>
