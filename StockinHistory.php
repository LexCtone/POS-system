<?php
session_start();
include 'connect.php';

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
  <title>Stock Entry</title>
  <link rel="stylesheet" href="CSS/StockinHistory.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <header>
    <h2 class="StockHeader">Stock-in History</h2>
  </header>
  <div class="button-container">
  <button onclick="location.href='PurchaseOrder.php'">Purchase Order</button>
  <button onclick="location.href='StockEntry.php'">Orders</button>  
    <button onclick="location.href='StockinHistory.php'" class="selected">Stock in History</button> 
    <button onclick="location.href='StockAdjustment.php'">Stock Adjustments</button> 
  </div>

  <form id="filter-form" method="GET" action="StockinHistory.php">
    <label class="labelstart" for="stockInDate">Start Date:</label>
    <input type="date" id="stockInDate" name="startDate" value="<?php echo isset($_GET['startDate']) ? htmlspecialchars($_GET['startDate']) : ''; ?>">
    <label class="labelend" for="endDate">End Date:</label>
    <input type="date" id="endDate" name="endDate" class="date-input" value="<?php echo isset($_GET['endDate']) ? htmlspecialchars($_GET['endDate']) : ''; ?>">
    <button type="submit">Filter</button>
    <button type="button" onclick="clearFilters()">Reload</button>
  </form>
  
  <nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li> <!-- Added Dashboard back -->
        <li><a href="Product.php""><i class='fas fa-archive' style='font-size:30px'></i>Product
        <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i> <!-- Submenu symbol added -->
        </a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="PurchaseOrder.php"  class="selected"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>

  <div class="content">
  <div class="table-container">
    <table class="table" id="product-table">
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
          width: 375px; /* Could be more or less, depending on screen size */
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
        margin-left: 20px
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
          margin-left: 63px;
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
  <script>
    function clearFilters() {
      // Redirect to the page without query parameters to clear the filters
      window.location.href = 'StockinHistory.php';
    }
  </script>
  
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