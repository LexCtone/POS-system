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

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $activate = isset($_POST['activate']) ? 1 : 0;
    $deactivate = isset($_POST['deactivate']) ? 1 : 0;

    if (empty($username)) {
        $message = "Please enter a username.";
    } elseif ($activate && $deactivate) {
        $message = "Please select either Activate or Deactivate, not both.";
    } elseif (!$activate && !$deactivate) {
        $message = "Please select either Activate or Deactivate.";
    } else {
        // Check if the user exists
        $check_stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            $message = "User not found.";
        } else {
            $new_status = $activate ? 1 : 0;
            
            $update_stmt = $conn->prepare("UPDATE accounts SET status = ? WHERE username = ?");
            $update_stmt->bind_param("is", $new_status, $username);
            
            if ($update_stmt->execute()) {
                $message = $activate ? "Account activated successfully." : "Account deactivated successfully.";
            } else {
                $message = "Error updating account status: " . $conn->error;
            }
            
            $update_stmt->close();
        }
        
        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate/Deactivate Account</title>
    <link rel="stylesheet" href="CSS/ActDeact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header>
    <h2 class="PageHeader">
        <div class="flex-container">
            <span class="header-title">Activate / Deactivate</span>
        </div>
    </h2>
</header>
</h2>
    </header>
  
    <nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li> <!-- Added Dashboard back -->
        <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product</a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="PurchaseOrder.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php" class="selected"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>
    <div class="container">
        <div class="account-box">
            <div class="button-container">
                <button onclick="location.href='UserSettings.php'">Create Account</button>
                <button onclick="location.href='ChangePassword.php'">Change Password</button> 
                <button class="btn selected" onclick="location.href='ActDeact.php'">Activate/Deactivate Account</button> 
                <button class="btn" onclick="location.href='Accounts.php'">Accounts</button>
            </div>
            <div class="form">
                <div id="message-container"></div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group checkbox-group">
                        <div>
                            <input type="checkbox" id="activate" name="activate">
                            <label for="activate">Activate</label>
                        </div>
                        <div>
                            <input type="checkbox" id="deactivate" name="deactivate">
                            <label for="deactivate">Deactivate</label>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="save-btn">Save</button>
                        <button type="button" class="cancel-btn" onclick="location.href='ActDeact.php'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activateCheckbox = document.getElementById('activate');
            const deactivateCheckbox = document.getElementById('deactivate');

            activateCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    deactivateCheckbox.checked = false;
                }
            });

            deactivateCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    activateCheckbox.checked = false;
                }
            });

            // Display message if it exists
            <?php if (!empty($message)): ?>
            const messageContainer = document.getElementById('message-container');
            const messageElement = document.createElement('div');
            messageElement.className = 'message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>';
            messageElement.textContent = '<?php echo addslashes($message); ?>';
            messageContainer.appendChild(messageElement);

            // Remove message after 5 seconds with fade-out animation
            setTimeout(() => {
                messageElement.classList.add('fadeOut');
                messageElement.addEventListener('animationend', () => {
                    messageElement.remove();
                });
            }, 2000);
            <?php endif; ?>
        });
    </script>
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