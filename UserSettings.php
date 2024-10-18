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
      <br><?php echo htmlspecialchars($admin_name); ?>
      </header>
    <ul>
      <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Home</a></li>
      <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product</a></li>
      <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
      <li><a href="StockEntry.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
      <li><a href="Brand.php"><i class='fa-solid fa-tag' style='font-size:30px'></i>Brand</a></li>
      <li><a href="Category.php"><i class='fa-solid fa-layer-group' style='font-size:30px'></i>Category</a></li>
      <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
      <li><a href="UserSettings.php" class="selected"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
      <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
  </nav>
  <div class="container">
    <div class="account-box">
      <div class="button-container">
        <button class="btn selected" onclick="location.href='UserSettings.php'">Create Account</button>
        <button class="btn" onclick="location.href='ChangePassword.php'">Change Password</button>
        <button class="btn" onclick="location.href='ActDeact.php'">Activate/Deactivate Account</button>
        <button class="btn" onclick="location.href='Accounts.php'">Accounts</button>
      </div>
                <?php
          // Check if error messages are set in the query string
          if (isset($_GET['error'])) {
              $errors = explode(',', $_GET['error']);

              if (in_array('username_taken', $errors)) {
                  echo '<p style="color: red;">Username is already taken.</p>';
              }

              if (in_array('email_taken', $errors)) {
                  echo '<p style="color: red;">Email is already used in another account.</p>';
              }
          }
          ?>
      <div class="form">
        <form id="password-form" action="save_account.php" method="POST">
          <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>
          </div>
            <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <small id="username-error" style="color: red; display: none; margin-left: 10px;">Username is already taken.</small>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" required>
            <small id="email-error" style="color: red; display: none; margin-left: 10px;">Email is already used in another account.</small>
          </div>
          <div class="form-group">
            <label for="new-password">Password</label>
            <input type="password" id="new-password" name="new-password" required>
          </div>
          <div class="form-group">
            <label for="retype-password">Re-Type Password</label>
            <input type="password" id="retype-password" name="retype-password" required>
            <small id="password-error" style="color: red; display: none; margin-left: 10px;">Passwords do not match.</small>
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
      </div>
    </div>
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

    function checkUsername(callback) {
        var username = document.getElementById('username').value;
        var usernameError = document.getElementById('username-error');

        if (username.length > 0) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_username.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.responseText === 'username_taken') {
                    usernameError.style.display = 'block';
                    callback(false); // Username is taken
                } else {
                    usernameError.style.display = 'none';
                    callback(true); // Username is available
                }
            };
            xhr.send('username=' + encodeURIComponent(username));
        } else {
            usernameError.style.display = 'none';
            callback(true); // No username, assume valid for now
        }
    }

    function checkEmail(callback) {
        var email = document.getElementById('email').value;
        var emailError = document.getElementById('email-error');

        if (email.length > 0) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_username.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.responseText === 'email_taken') {
                    emailError.style.display = 'block';
                    callback(false); // Email is taken
                } else {
                    emailError.style.display = 'none';
                    callback(true); // Email is available
                }
            };
            xhr.send('email=' + encodeURIComponent(email));
        } else {
            emailError.style.display = 'none';
            callback(true); // No email, assume valid for now
        }
    }

    document.getElementById('password-form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent form submission by default

        // First check username availability
        checkUsername(function(isUsernameValid) {
            if (isUsernameValid) {
                // Then check email availability
                checkEmail(function(isEmailValid) {
                    if (isEmailValid && validatePasswords()) {
                        // Submit the form only if the username, email, and passwords are valid
                        document.getElementById('password-form').submit();
                    }
                });
            }
        });
    });
</script>

</body>
</html>
