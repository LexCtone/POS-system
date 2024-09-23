<?php
// Include your database connection file here
require_once 'connect.php';

// Ensure the database connection is established
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? "Unknown error"));
}

// Initialize variables for filtering
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$cashierName = isset($_GET['cashier']) ? $_GET['cashier'] : 'all';

// Prepare the base query
$query = "SELECT barcode, description, price, quantity, discount_amount, total, cashier_name FROM sales";

// Add filters
$conditions = [];
$params = [];
$types = '';

if ($startDate && $endDate) {
    $conditions[] = "sale_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';
}

if ($cashierName != 'all') {
    $conditions[] = "cashier_name = ?";
    $params[] = $cashierName;
    $types .= 's';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

// Add ordering
$query .= " ORDER BY sale_date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// If it's an AJAX request, return JSON data
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $data = [];
    $totalSales = 0;
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalSales += $row['total'];
    }
    echo json_encode(['data' => $data, 'totalSales' => $totalSales]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sold Items</title>
  <link rel="stylesheet" href="CSS\SoldItems.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                <form id="filterForm" method="GET">
                    <div class="form-group">
                        <label for="startDate" class="date-label">Filter by</label>
                        <input type="date" id="startDate" name="startDate" class="date-input" value="<?php echo $startDate; ?>">
                        <input type="date" id="endDate" name="endDate" class="date-input" value="<?php echo $endDate; ?>">
                        <select id="cashier" class="vendor" name="cashier">
                            <option value="all">All Cashiers</option>
                            <?php
                            // Fetch all unique cashier names from the sales table
                            $cashierStmt = $conn->prepare("SELECT DISTINCT cashier_name FROM sales ORDER BY cashier_name");
                            $cashierStmt->execute();
                            $cashierResult = $cashierStmt->get_result();
                            while ($row = $cashierResult->fetch_assoc()) {
                                $selected = ($cashierName == $row['cashier_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['cashier_name']) . "' $selected>" . htmlspecialchars($row['cashier_name']) . "</option>";
                            }
                            $cashierStmt->close();
                            ?>
                        </select>
                        <button type="submit" class="load-data-button">
                            <i class="fa fa-refresh"></i>
                            <span class="load-data-text">Load Data</span>
                        </button>
                </form>
                <div class="print-preview-button" onclick="window.print()">
                        <i class="fa-solid fa-print"></i>
                    <span class="print-preview-text">Print Preview</span>
                </div>
                <div class="total-sales">
                    Total Sales: ₱<span id="totalSales">0.00</span>
                </div>
            </div>
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
              <th>PRICE</th>
              <th>QTY</th>
              <th>DISCOUNT</th>
              <th>TOTAL SALES</th>
              <th>CASHIER</th>
            </tr>
          </thead>
          <tbody id="salesTableBody">
            <?php
            $totalSales = 0;
            $rowNumber = 1;
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $rowNumber . "</td>";
                echo "<td>" . htmlspecialchars($row['barcode']) . "</td>";
                echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                echo "<td>" . $row['quantity'] . "</td>";
                echo "<td>₱" . number_format($row['discount_amount'], 2) . "</td>";
                echo "<td>₱" . number_format($row['total'], 2) . "</td>";
                echo "<td>" . htmlspecialchars($row['cashier_name']) . "</td>";
                echo "</tr>";
                $totalSales += $row['total'];
                $rowNumber++;
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <script>
    $(document).ready(function() {
        // Initialize total sales
        updateTotalSales();

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            loadData();
        });

        function loadData() {
            $.ajax({
                url: 'SoldItems.php',
                method: 'GET',
                data: $('#filterForm').serialize(),
                dataType: 'json',
                success: function(response) {
                    let tableBody = '';
                    let rowNumber = 1;
                    response.data.forEach(function(row) {
                        tableBody += `<tr>
                            <td>${rowNumber}</td>
                            <td>${escapeHtml(row.barcode)}</td>
                            <td>${escapeHtml(row.description)}</td>
                            <td>₱${parseFloat(row.price).toFixed(2)}</td>
                            <td>${row.quantity}</td>
                            <td>₱${parseFloat(row.discount_amount).toFixed(2)}</td>
                            <td>₱${parseFloat(row.total).toFixed(2)}</td>
                            <td>${escapeHtml(row.cashier_name)}</td>
                        </tr>`;
                        rowNumber++;
                    });
                    $('#salesTableBody').html(tableBody);
                    updateTotalSales();
                },
                error: function() {
                    alert('An error occurred while loading the data.');
                }
            });
        }

        function updateTotalSales() {
            let total = 0;
            $('#salesTableBody tr').each(function() {
                let rowTotal = parseFloat($(this).find('td:eq(6)').text().replace('₱', '').replace(',', ''));
                if (!isNaN(rowTotal)) {
                    total += rowTotal;
                }
            });
            $('#totalSales').text(total.toFixed(2));
        }

        function escapeHtml(unsafe) {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }
    });
    </script>
</body>
</html>