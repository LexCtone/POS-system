<?php
session_start();
// Database connection
include 'connect.php'; // Ensure this path is correct and the file exists

// Fetch the username of the logged-in admin
$admin_username = "ADMINISTRATOR"; // Default value
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query_admin = "SELECT username FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($query_admin);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin_username = $row['username'];
    }
    $stmt->close();
}?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account</title>
  <link rel="stylesheet" href="NAVBAR.css">
  <link rel="stylesheet" href="CSS/UserSettings.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <header>
    <h2 class="Header">Account</h2>
  </header>
  
  <nav class="sidebar">
    <header>
      <img src="profile.png" alt="Profile"/>
      <br><?php echo htmlspecialchars($admin_username); ?>
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
  </nav>

  <div class="container">
    <div class="account-box">
      <div class="button-container">
        <button class="btn" onclick="location.href='UserSettings.php'">Create Account</button>
        <button class="btn" onclick="location.href='ChangePassword.php'">Change Password</button>
        <button class="btn" onclick="location.href='ActDeact.php'">Activate/Deactivate Account</button>
        <button class="btn" onclick="location.href='Accounts.php'">Accounts</button>
      </div>
      <div class="form">
      <form id="password-form" action="save_account.php" method="POST">
  <div class="form-group">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required>
  </div>
  <div class="form-group">
    <label for="new-password">Password</label>
    <input type="password" id="new-password" name="new-password" required>
  </div>
  <div class="form-group">
    <label for="retype-password">Re-Type Password</label>
    <input type="password" id="retype-password" name="retype-password" required>
    <small id="password-error" style="color: red; display: none;">Passwords do not match.</small>
  </div>
  <div class="form-group">
    <label for="role">Role</label>
    <select id="role" name="role" required>
      <option value="" disabled selected>Select Role</option>
      <option value="Admin">Admin</option>
      <option value="Cashier">Cashier</option>
    </select>
  </div> 
  <div class="button-group">
    <button type="submit" class="save-btn">Save</button>
    <button type="button" class="cancel-btn" onclick="location.href='UserSettings.php'">Cancel</button>
  </div>
</form>


  <script>
    function validatePasswords() {
      var newPassword = document.getElementById('new-password').value;
      var retypePassword = document.getElementById('retype-password').value;
      var passwordError = document.getElementById('password-error');

      if (newPassword !== retypePassword) {
        passwordError.style.display = 'block';
        return false; // Prevent form submission
      } else {
        passwordError.style.display = 'none';
        return true; // Allow form submission
      }
    }

    document.getElementById('password-form').addEventListener('submit', function(event) {
      if (!validatePasswords()) {
        event.preventDefault(); // Prevent form submission if validation fails
      }
    });
  </script>
</body>
</html>
