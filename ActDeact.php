<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account</title>
  <link rel="stylesheet" href="CSS\ActDeact.css">
  <link rel="stylesheet" href="NAVBAR.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <header>
    <h2 class="Header">Account</h2>
  </header>
  
  <nav class="sidebar">
    <header class="name">
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
    <button onclick="location.href='UserSettings.php'">Create Account</button>
    <button onclick="location.href='ChangePassword.php'">Change Password</button> 
    <button onclick="location.href='ActDeact.php'">Activate/Deactivate Account</button> 
  </div>
      <div class="form">
        <form>
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username">
          </div>
          <div>
          <div class="form-group checkbox-group">
      <div>
        <input type="checkbox" id="Check" name="Check">
        <label for="Check">Activate</label>
      </div>
      <div>
        <input type="checkbox" id="Checko" name="Checko">
        <label for="Checko">Deactivate</label>
      </div>
    </div>

          </div>
          <div class="button-group">
            <button type="submit" class="save-btn">Save</button>
            <button type="button" class="cancel-btn">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="usersettings.js"></script>

</body>
</html>
