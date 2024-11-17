<?php
session_start();
include 'connect.php';

// Fetch the username of the logged-in admin
$admin_name = "ADMINISTRATOR";
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
  <title>Archived Products</title>
  <link rel="stylesheet" href="CSS/ArchivedProducts.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header>
    <h2 class="StockHeader">Archived Products</h2>
  </header>
  
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
        <li><a href="PurchaseOrder.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php" class="selected"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>
  <div class="container">
    <div class="account-box">
      <div class="button-container">
        <button class="btn" onclick="location.href='Records.php'">Top Selling</button>
        <button class="btn" onclick="location.href='SalesHistory.php'">Sales History</button>
        <button class="btn" onclick="location.href='CancelledOrder.php'">Cancelled Order</button>
        <button class="btn" onclick="location.href='StockHistory.php'">Stock In History</button>
        <button class="btn selected" onclick="location.href='ArchivedProducts.php'">Archived Products</button>
        <button class="btn" onclick="location.href='SalesReport.php'">Sales Report</button>
        <button class="btn" onclick="location.href='CriticalStocks.php'">Critical Stocks</button>
        <button class="btn" onclick="location.href='InventoryList.php'">Inventory List</button>
      </div>
      <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>

      <div class="form">
        <form id="filter-form" method="GET" action="ArchivedProducts.php">
          <div class="form-group">
            <label for="startDate" class="date-label">Filter by</label>
            <input type="date" id="startDate" name="startDate" class="date-input" value="<?php echo isset($_GET['startDate']) ? htmlspecialchars($_GET['startDate']) : ''; ?>">
            <input type="date" id="endDate" name="endDate" class="date-input" value="<?php echo isset($_GET['endDate']) ? htmlspecialchars($_GET['endDate']) : ''; ?>">
            <button type="submit" class="load-data-button">
              <i class="fa fa-refresh"></i>
              <span class="load-data-text">Load Data</span>
            </button>
            <div class="print-preview-button" onclick="printTable()">
              <i class="fa-solid fa-print"></i>
              <span class="print-preview-text">Print Preview</span>
            </div>
          </div>
        </form>
      </div>

      <div class="content">
        <div class="table-container">
          <table class="table" id="product-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Barcode</th>
                <th>Reference Number</th>
                <th>Description</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Vendor</th>
                <th>Archived By</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Set default query
              $query = "SELECT * FROM archived_products";

              // Check for date filtering
              if (isset($_GET['startDate']) && isset($_GET['endDate'])) {
                  $startDate = $_GET['startDate'];
                  $endDate = $_GET['endDate'];

                  if (!empty($startDate) && !empty($endDate)) {
                      $query .= " WHERE DATE(archived_at) BETWEEN '$startDate' AND '$endDate'";
                  }
              }

              $result = mysqli_query($conn, $query);

              if (mysqli_num_rows($result) > 0) {
                  $counter = 1;
                  while ($row = mysqli_fetch_assoc($result)) {
                      echo "<tr>";
                      echo "<td>" . $counter++ . "</td>";
                      echo "<td>" . htmlspecialchars($row['Barcode']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['reference']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Brand']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Category']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Price']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Quantity']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Vendor']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['archived_by']) . "</td>";
                      echo "<td><button class='restore-btn' data-id='" . $row['id'] . "'>Restore</button></td>";
                      echo "</tr>";
                  }
              } else {
                echo "<tr><td colspan='11' style='text-align: center;'>No archived products found.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const restoreButtons = document.querySelectorAll('.restore-btn');
      restoreButtons.forEach(button => {
        button.addEventListener('click', function() {
          const productId = this.getAttribute('data-id');
          if (confirm('Are you sure you want to restore this product?')) {
            fetch('restore_product.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: 'id=' + productId
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                alert('Product restored successfully');
                location.reload();
              } else {
                alert('Failed to restore product: ' + data.message);
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert('An error occurred while restoring the product');
            });
          }
        });
      });
    });
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
        // Function to show the modal
        function confirmLogout() {
            document.getElementById("logoutModal").style.display = "block"; // Show the modal
            return false; // Prevent the default link action
        }

        // Function to close the modal
        function closeLogoutModal() {
            document.getElementById("logoutModal").style.display = "none"; // Hide the modal
        }

        // Close the modal if the user clicks anywhere outside of it
        window.onclick = function(event) {
            var modal = document.getElementById("logoutModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };

        // Confirm logout action
        document.getElementById("confirmLogout").onclick = function() {
            window.location.href = "Login.php"; // Redirect to the login page or handle logout
        };
    </script>
    <script>
    function printTable() {
    document.body.classList.add('print-mode');
    window.print();
    document.body.classList.remove('print-mode');
}
</script>
</body>
</html>