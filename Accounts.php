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
  <title>Account</title>
  <link rel="stylesheet" href="CSS/UserSettings.css">
  <link rel="stylesheet" href="Accounts.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <header>
    <h2 class="Header">Accounts</h2>
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
        <li><a href="StockEntry.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php" class="selected"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>
  <div class="container">
    <div class="account-box">
      <div class="button-container">
        <button class="btn" onclick="location.href='UserSettings.php'">Create Account</button>
        <button class="btn" onclick="location.href='ChangePassword.php'">Change Password</button>
        <button class="btn" onclick="location.href='ActDeact.php'">Activate/Deactivate Account</button>
        <button class="btn selected" onclick="location.href='Accounts.php'">Accounts</button>
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
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Password</th> <!-- Added Password column -->
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
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
            <td class="editable" data-field="name">${account.name}</td>
            <td class="editable" data-field="username">${account.username}</td>
            <td class="editable" data-field="email">${account.email}</td>
            <td>${account.password}</td> <!-- Display decrypted password -->
            <td>${account.role}</td>
            <td>${account.status}</td>
            <td>
              <button class="edit-btn" data-id="${account.id}" onclick="editAccount(this)">Edit</button>
              <button class="save-btn" data-id="${account.id}" style="display:none;" onclick="saveAccount(this)">Save</button>
            </td>
          `;
          tbody.appendChild(row);
        });
      })
      .catch(error => console.error("Error fetching cashier data:", error));
  });

  function editAccount(button) {
    const row = button.closest("tr");
    const editables = row.querySelectorAll(".editable");

    // Make fields editable by replacing text with input fields
    editables.forEach(td => {
      const value = td.textContent;
      const field = td.getAttribute("data-field");
      td.innerHTML = `<input type="text" value="${value}" name="${field}">`; // Turn text into input fields
    });

    button.style.display = "none"; // Hide Edit button
    const saveButton = row.querySelector(".save-btn");
    saveButton.style.display = "inline"; // Show Save button
  }

  function saveAccount(button) {
    const row = button.closest("tr");
    const id = button.getAttribute("data-id");
    const editables = row.querySelectorAll(".editable");
    const updatedData = {};

    // Collect the updated data from the input fields
    editables.forEach(td => {
      const input = td.querySelector("input");
      if (input) {
        updatedData[td.getAttribute("data-field")] = input.value; // Get updated values
        td.textContent = input.value; // Replace input with the new text value
      }
    });

    // Send the updated data to the server
    fetch("update_account.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        id: id,
        ...updatedData,
      }),
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert("Account updated successfully.");
      } else {
        alert("Error updating account.");
      }
    })
    .catch(error => console.error("Error updating account:", error));

    button.style.display = "none"; // Hide Save button
    const editButton = row.querySelector(".edit-btn");
    editButton.style.display = "inline"; // Show Edit button
  }
</script>

</body>
</html>
