<?php
session_start();
include 'connect.php';

// Load the .env.php file
$env = require __DIR__ . '/.env.php'; // Adjust the path as needed

// Access the encryption key and decode it
$key = base64_decode($env['ENCRYPTION_KEY']);

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
$debug_info = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $old_password = $_POST['old-password'];
    $new_password = $_POST['new-password'];
    $retype_password = $_POST['retype-password'];

    // Debug information
    $debug_info .= "POST data received. Username: $username\n";

    if ($new_password !== $retype_password) {
        $message = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password, iv FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        $debug_info .= "Query executed. Rows returned: " . $result->num_rows . "\n";

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            // Debugging the stored password
            $debug_info .= "Stored encrypted password from DB: " . htmlspecialchars($row['password']) . "\n";

            // Decrypt the stored password
            $iv = hex2bin($row['iv']);
            $cipher = "AES-256-CBC";
            $decrypted_password = openssl_decrypt($row['password'], $cipher, $key, 0, $iv);

            // Verify the old password against the decrypted password
            if ($old_password === $decrypted_password) {
                // Encrypt the new password
                $encrypted_password = openssl_encrypt($new_password, $cipher, $key, 0, $iv);
                $update_stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE username = ?");
                $update_stmt->bind_param("ss", $encrypted_password, $username);

                if ($update_stmt->execute()) {
                    $message = "Password changed successfully.";
                    $debug_info .= "Password updated successfully.\n";
                } else {
                    $message = "Error updating password: " . $conn->error;
                    $debug_info .= "Error updating password: " . $conn->error . "\n";
                }
                $update_stmt->close();
            } else {
                $message = "Incorrect old password.";
                $debug_info .= "Incorrect old password.\n";
            }
        } else {
            $message = "Username not found.";
            $debug_info .= "Username not found.\n";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="CSS/ChangePassword.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header>
        <h2 class="Header">Account</h2>
    </header>
  
    <nav class="sidebar">
        <header>
            <img src="profile.png" alt="profile"/>
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
                <button onclick="location.href='UserSettings.php'">Create Account</button>
                <button class="btn selected" onclick="location.href='ChangePassword.php'">Change Password</button> 
                <button onclick="location.href='ActDeact.php'">Activate/Deactivate Account</button> 
                <button class="btn" onclick="location.href='Accounts.php'">Accounts</button>
            </div>
            <div class="form">
                                <!-- Message container for animations -->
                            <div id="message-container"></div>
                <!-- PHP block to generate the message -->
                <?php if (!empty($message)): ?>
                <script>
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
                </script>
                <?php endif; ?>
                <form id="change-password-form" method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="old-password">Old Password</label>
                        <input type="password" id="old-password" name="old-password" required>
                    </div>
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new-password" required>
                    </div>
                    <div class="form-group">
                        <label for="retype-password">Re-Type New Password</label>
                        <input type="password" id="retype-password" name="retype-password" required>
                        <small id="password-error" style="color: red; display: none;">Passwords do not match.</small>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="save-btn">Save</button>
                        <button type="button" class="cancel-btn" onclick="location.href='UserSettings.php'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php /*
if (!empty($debug_info)): ?>
    <div style="margin-left: 420px; width: 500px; padding: 10px; background-color: #f0f0f0; border: 1px solid #ccc;">
        <h3>Debug Information:</h3>
        <pre><?php echo htmlspecialchars($debug_info); ?></pre>
    </div>
<?php endif; */ ?>


    <script>
        document.getElementById('change-password-form').addEventListener('submit', function(event) {
            var newPassword = document.getElementById('new-password').value;
            var retypePassword = document.getElementById('retype-password').value;
            var passwordError = document.getElementById('password-error');

            if (newPassword !== retypePassword) {
                event.preventDefault();
                passwordError.style.display = 'block';
            } else {
                passwordError.style.display = 'none';
            }
        });
    </script>
</body>
</html>
