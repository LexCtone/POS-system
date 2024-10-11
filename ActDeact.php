<?php
session_start();
include 'connect.php';

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
</body>
</html>