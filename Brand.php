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

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Brand</title>
  <link rel="stylesheet" type="text/css" href="CSS/Brand.css">
  <script type="text/javascript" src="JAVASCRIPT/Brand.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php" class="<?php echo ($current_page == 'Dashboard.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li>
        <li>
            <a href="Product.php" class="<?php echo ($current_page == 'Product.php' || $current_page == 'Brand.php' || $current_page == 'Category.php') ? 'selected' : ''; ?>">
                <i class='fas fa-archive' style='font-size:30px'></i>Product
                <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i>
            </a>
            <ul class="submenu" style="<?php echo ($current_page == 'Brand.php' || $current_page == 'Category.php') ? 'display:block;' : ''; ?>">
                <li><a href="Brand.php" class="<?php echo ($current_page == 'Brand.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php" class="<?php echo ($current_page == 'Category.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php" class="<?php echo ($current_page == 'Vendor.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="PurchaseOrder.php" class="<?php echo ($current_page == 'PurchaseOrder.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php" class="<?php echo ($current_page == 'Records.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php" class="<?php echo ($current_page == 'UserSettings.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>

<header>    
    <h2 class="ProductHeader">Brand List
        <input id="search-input" type="text" placeholder="Search..." onkeyup="searchBrands()">
        <button id="add-brand-button"><i class='fas fa-plus'></i></button>
    </h2>
</header>
<div class="content">
    <div class="table-container">
        <table class="table" id="brand-table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Brand</th>
                    <th scope="col">Operation</th>
                </tr>
            </thead>
            <tbody>
            <?php
                include 'connect.php';

                // Handle delete request
                if (isset($_GET['deleteid'])) {
                    $id_to_delete = $_GET['deleteid'];

                    // Delete the record
                    $delete_sql = "DELETE FROM brands WHERE id = $id_to_delete";
                    mysqli_query($conn, $delete_sql);

                    // Redirect back to the brand list
                    header('Location: Brand.php');
                    exit();
                }

                // Fetch and display brands
                $sql = "SELECT * FROM brands";
                $result = mysqli_query($conn, $sql);
                $row_number = 1; // Initialize row number
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $brand = $row['Brand'];

                        echo '<tr>
                        <td scope="row">'.$row_number.'</td> <!-- Display row number -->
                        <td>'.$brand.'</td>
                        <td>
                            <button class="button update-button" data-id="'.$row['id'].'" data-brand="'.htmlspecialchars($brand, ENT_QUOTES, 'UTF-8').'"><a href="#" class="text-light">Update</a></button>
                            <button class="button"><a href="?deleteid='.$row['id'].'" class="text-light">Delete</a></button>
                        </td>
                        </tr>';
                        $row_number++; // Increment row number
                    }
                } else {
                    echo '<tr><td colspan="3" style="text-align: center; font-size: 18px; color: #555;">No brands found.</td></tr>';
                }
                mysqli_close($conn);
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
    <!-- Modal for Adding Brand -->
    <div id="brand-modal" class="modals">
        <div class="modal-contents">
            <span class="close-button">&times;</span>
            <h2>Add New Brand</h2>
            <form id="brand-form" action="add_brand.php" method="post">
                <label for="brand-name">Brand Name:</label>
                <input type="text" id="brand-name" name="brand-name" required>
                <button type="submit">Add Brand</button>
            </form>
        </div>
    </div>
    <!-- Update Brand Modal -->
    <div id="update-brand-modal" class="modals">
        <div class="modal-contents">
            <span class="close-button">&times;</span>
            <h2>Update Brand</h2>
            <form id="update-brand-form" action="update_brand.php" method="post">
                <input type="hidden" id="update-brand-id" name="brand-id">
                <label for="update-brand-name">Brand Name:</label>
                <input type="text" id="update-brand-name" name="brand-name" required>
                <button type="submit">Update Brand</button>
            </form>
        </div>
    </div>
</div>

<script>
function searchBrands() {
    const input = document.getElementById('search-input');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('brand-table');
    const trs = table.getElementsByTagName('tr');

    for (let i = 1; i < trs.length; i++) { // Start from 1 to skip header
        const td = trs[i].getElementsByTagName('td')[1]; // Get Brand column
        if (td) {
            const txtValue = td.textContent || td.innerText;
            trs[i].style.display = txtValue.toLowerCase().includes(filter) ? '' : 'none';
        }
    }
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
