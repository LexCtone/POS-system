<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account</title>
  <link rel="stylesheet" href="CSS/UserSettings.css">
  <link rel="stylesheet" href="Accounts.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <header>
    <h2 class="Header">Account</h2>
  </header>
  
  <nav class="sidebar">
    <header>
      <img src="profile.png" alt="Profile"/>
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
      </div>
      </div>
      
      
 <div class="content">
        <div id="message" class="message"></div>
        <div class="modals">
            <div class="table-container">
                <table id="product-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                    </tbody>
                </table>
            </div>
            <script>
  document.addEventListener("DOMContentLoaded", function () {
    fetch("fetch_cashiers.php")
      .then(response => response.json())
      .then(data => {
        const tbody = document.querySelector("#product-table tbody");
        
        // Clear any existing rows in the table
        tbody.innerHTML = "";
        
        // Populate table with fetched data
        data.forEach((account, index) => {
          const row = document.createElement("tr");
          row.innerHTML = `
            <td>${index + 1}</td>
            <td>${account.username}</td>
            <td>${account.password}</td>
            <td>${account.role}</td>
            <td>${account.status}</td>
          `;
          tbody.appendChild(row);
        });
      })
      .catch(error => console.error("Error fetching cashier data:", error));
  });
</script>

</body>
</html>
