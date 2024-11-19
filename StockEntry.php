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
  <title>Purchase Order</title>
  <link rel="stylesheet" href="CSS/Stocks.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<header>
    <h2 class="PageHeader">
        <div class="flex-container">
            <span class="header-title">Orders</span>
        </div>
    </h2>
</header>

<div class="button-container">
    <button onclick="location.href='PurchaseOrder.php'">Purchase Order</button>
    <button onclick="location.href='StockEntry.php'" class="selected">Orders</button> 
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
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li>
        <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product
        <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i>
        </a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="PurchaseOrder.php" class="selected"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
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
        <div class="form-group">
    <label for="poReference">Purchase Order Reference</label>
    <select class="form-control" id="poReference" name="poReference">
        <option value="">Select Purchase Order</option>
        <?php
        // Fetch purchase orders using mysqli connection
        $sql = "SELECT po_number FROM purchase_orders"; // Adjust this if you have a specific condition for filtering
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['po_number']) . "'>" . htmlspecialchars($row['po_number']) . "</option>";
            }
        } else {
            echo "<option value='' disabled>No available purchase orders</option>";
        }

        // Close the connection
        $conn->close();
        ?>
    </select>
</div>
        <div class="form-group-browse">
            <a href="#" class="browse-products-link">[Click Here To Browse Product]</a>
        </div>
    </div>
</div>

<style>
        /* Modal styles */
        .modal {
          display: none; /* Hidden by default */
          position: fixed; /* Stay in place */
          z-index: 1000; /* Sit on top */
          left: 0;
          top: 0;
          width: 100%; /* Full width */
          height: 100%; /* Full height */
          overflow: auto; /* Enable scroll if needed */
          background-color: rgba(0, 0, 0, 0.5); /* Black with opacity */
      }

      /* Modal content */
      .modal-content {
          background-color: #fefefe; /* White background */
          margin: 15% auto; /* 15% from the top and centered */
          padding: 20px;
          border: 1px solid #888; /* Gray border */
          width: 350px; /* Could be more or less, depending on screen size */
          border-radius: 8px; /* Rounded corners */
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow effect */
      }

      /* Close button */
      .close {
          color: #aaa; /* Light gray */
          float: right; /* Position to the right */
          font-size: 28px; /* Larger font size */
          font-weight: bold; /* Bold text */
      }

      .conf{
        font-size: 24px;
        font-weight: bolder;
      }

      .par{
        font-size: 18px;
        margin-left: 10px;
      }

      .close:hover,
      .close:focus {
          color: black; /* Change color on hover */
          text-decoration: none; /* No underline */
          cursor: pointer; /* Pointer cursor */
      }

      /* Button styles */
      .confirm-btn,
      .cancel-btn {
          background-color: #005b99; /* Blue background */
          border: none; /* No borders */
          color: white; /* White text */
          padding: 10px 20px; /* Some padding */
          text-align: center; /* Centered text */
          text-decoration: none; /* No underline */
          display: inline-block; /* Align buttons */
          font-size: 16px; /* Larger font */
          margin: 10px 2px; /* Margins around buttons */
          margin-left: 55px;
          margin-top: 20px;
          cursor: pointer; /* Pointer cursor */
          border-radius: 5px; /* Rounded corners */
          transition: background-color 0.3s; /* Smooth transition */
      }

      .cancel-btn {
          background-color: red; /* Gray background for cancel */
      }

      .cancel-btn:hover {
          background-color: maroon; /* Darker gray on hover */
      }

      .confirmLogout:hover{
        background-color: lightblue; /* Darker gray on hover */
      }
    </style>
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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be populated dynamically via JavaScript -->
            </tbody>
        </table>
    </div>
    
                <!-- Success Alert for Purchase Order Save -->
<div id="successAlert" style="display: none;" class="alert alert-success" role="alert">
  <p>Purchase Order created successfully!</p>
</div>

<!-- Success Alert for Purchase Order Save -->
<div id="successAlert" style="display: none;" class="alert alert-success" role="alert">
    <p>Purchase Order created successfully!</p>
</div>

<!-- Custom Confirmation Alert -->
<div id="confirmSaveAlert" class="custom-alert">
    <div class="alert-content">
        <p>Do you want to save this entry as a Purchase Order?</p>
        <button id="confirmSaveButton" class="confirm-btn">Yes</button>
        <button id="cancelSaveButton" class="cancel-btn">No</button>
    </div>
</div>


    <!-- Save Button -->
    <div class="save-container">
        <button type="button" id="save-button">Save</button>
    </div>
</div>

<script src="JAVASCRIPT/StockEntry.js"></script>

<div id="product-modal" class="modal-overlay">
    <div class="modal-contents">
        <span class="close">&times;</span>
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
  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLogoutModal()">&times;</span>
            <h2 class="conf">Logout Confirmation</h2>
            <p class="par">Are you sure you want to log out?</p>
            <button id="confirmLogout" class="confirm-btn">Logout</button>
            <button class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>
        </div>
    </div>

    <script>
        // Function to show the logout modal
        function confirmLogout() {
            document.getElementById("logoutModal").style.display = "block"; // Show the modal
            return false; // Prevent the default link action
        }

        // Function to close the logout modal
        function closeLogoutModal() {
            document.getElementById("logoutModal").style.display = "none"; // Hide the modal
        }

        // Confirm logout action
        document.getElementById("confirmLogout").onclick = function() {
            window.location.href = "Login.php"; // Redirect to the login page or handle logout
        };

        // Close the modal if the user clicks anywhere outside of it
        window.onclick = function(event) {
            var logoutModal = document.getElementById("logoutModal");
            if (event.target == logoutModal) {
                closeLogoutModal();
            }
        };
    </script>

    
</body>
</html>
