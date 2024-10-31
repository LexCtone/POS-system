<?php
session_start();
// Database connection
include 'connect.php'; // Ensure this path is correct and the file exists

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


$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch vendors from the database
$sql = "SELECT id, vendor FROM vendor"; // Adjust table name and columns as needed
$result = $conn->query($sql);

if (!$result) {
    die("Error: " . $conn->error);
}

$current_date = date('Y-m-d'); // Format: YYYY-MM-DD
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock Entry</title>
  <link rel="stylesheet" href="CSS/Stocks.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<header>
    <h2 class="StockHeader">Stock Entry</h2>
</header>
<div class="button-container">
<button onclick="location.href='StockEntry.php'" class="selected">Stock Entry</button>
    <button onclick="location.href='StockinHistory.php'">Stock in History</button> 
    <button onclick="location.href='StockAdjustment.php'">Stock Adjustments</button> 
</div>
<nav>
<nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li> <!-- Added Dashboard back -->
        <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product
        <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i> <!-- Submenu symbol added -->
        </a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="StockEntry.php" class="selected"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav> 
</nav>

<!-- Main Content -->
<div class="modals">
    <div class="horizontal-form">
    <div class="form-group">
        <label for="referenceNo">REFERENCE NO</label>
        <input type="text" id="referenceNo" name="referenceNo" value="" readonly>
    </div>
        <div class="form-group">
            <label for="contactPerson">CONTACT PERSON</label>
            <input type="text" id="contactPerson" name="contactPerson">
        </div>
        <div class="form-group">
            <label disabled for="stockInBy">STOCK IN BY</label>
            <input type="text" id="stockInBy" name="stockInBy" value="<?php echo htmlspecialchars($admin_name); ?>" readonly>
            </div>
        <div class="form-group">
            <label for="vendor">VENDOR</label>
            <select id="vendor" name="vendor">
                <option value="" disabled selected>Select Vendor</option>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['id']); ?>"><?= htmlspecialchars($row['vendor']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="stockInDate">STOCK IN DATE</label>
            <input type="date" id="stockInDate" name="stockInDate" value="<?php echo $current_date; ?>">
            </div>
        <div class="form-group">
            <label for="address">ADDRESS</label>
            <input type="text" id="address" name="address" autocomplete="off">
        </div>
        <div class="form-group-browse">
            <a href="#" class="browse-products-link">[Click Here To Browse Product]</a>
        </div>
    </div>
</div>

<!-- Table below the modal to show selected products -->
<div class="content">
<div class="table-container">
  <table class="table" id="product-table">
    <thead>
      <tr>
        <th>#</th>
        <th>REF#</th>
        <th>BARCODE</th>
        <th>Description</th>
        <th>QTY</th>
        <th>STOCK IN DATE</th>
        <th>STOCK IN BY</th>
        <th>VENDOR</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <!-- Rows will be populated dynamically via JavaScript -->
    </tbody>
  </table>
</div>

<!-- Save Button -->
<div class="save-container">
    <button type="button" id="save-button">Save</button>
  </div>
<script src="JAVASCRIPT\StockEntry.js"></script>
</body>

<div id="product-modal" class="modal-overlay">
    <div class="modal-content">
        <span class="close">&times;</span> <!-- This is the close button -->
        <h2>Product List</h2>
        <div class="modal-body">
        <table class="productTable" id="productModalTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Barcode</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Product rows will be inserted here -->
            </tbody>
        </table>
    </div>
</div>
</div>

</html>
<?php
// Close the database connection
$conn->close();
?>