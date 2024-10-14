<?php
session_start();
$error_message = '';
$success_message = '';

if (!isset($_SESSION['user_id'])) {
    header('Location: forgot_password.php');
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password === $confirm_password) {
        include('connect.php');
        
        // Assuming you have a decryption key stored securely
        $env = require __DIR__ . '/.env.php'; // Ensure this contains your encryption key
        $key = base64_decode($env['ENCRYPTION_KEY']); // Example of fetching encryption key
        
        // Encrypt the new password
        $cipher = "AES-256-CBC"; // Use the same cipher as before
        $iv = random_bytes(openssl_cipher_iv_length($cipher)); // Generate a new IV
        $encrypted_password = openssl_encrypt($new_password, $cipher, $key, 0, $iv);
        $iv_hex = bin2hex($iv); // Convert IV to hex for storage

        $user_id = $_SESSION['user_id'];

        // Update the encrypted password and IV in the database
        $updatePasswordQuery = "UPDATE accounts SET password = ?, iv = ? WHERE id = ?";
        if ($stmt = $conn->prepare($updatePasswordQuery)) {
            $stmt->bind_param('ssi', $encrypted_password, $iv_hex, $user_id);
            if ($stmt->execute()) {
                // Optionally clear OTP records after successful password change
                $deleteOtpQuery = "DELETE FROM forgot_password_otps WHERE user_id = ?";
                $deleteStmt = $conn->prepare($deleteOtpQuery);
                $deleteStmt->bind_param('i', $user_id);
                $deleteStmt->execute();

                // Set success message to be displayed on login_again.php
                $_SESSION['success_message'] = "Password updated successfully! You can now log in.";
                header('Location: login_again.php');
                exit();
            } else {
                $error_message = "Failed to update password. Please try again.";
                error_log("Error updating password for User ID: $user_id - " . $stmt->error);
            }
        } else {
            $error_message = "Failed to prepare statement.";
            error_log("Failed to prepare update password query: " . $conn->error);
        }
    } else {
        $error_message = "Passwords do not match. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password</title>
    <link rel="stylesheet" href="CSS/login.css">
</head>
<body id="loginBody">
    <div class="container">
        <img src="logo.jpg" alt="LOGO">
        <?php if (!empty($error_message)) { ?>
            <div class="error-message">
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php } ?>
        <form action="new_password.php" method="POST">
            <input type="password" name="new_password" placeholder="Set new password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm your password" required><br>
            <input class="submit" type="submit" value="Change">
        </form>
    </div>
</body>
</html>
