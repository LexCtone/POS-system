<?php
session_start();
include('connect.php');

// Check if the user is redirected after sending OTP
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}

if (!isset($_SESSION['user_id'])) {
    header('Location: forgot_password.php');
    exit();
}

$error_message = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['username']); // Use a more descriptive name for the input
    $user_id = $_SESSION['user_id'];

    $query = "SELECT * FROM forgot_password_otps WHERE user_id = ? AND BINARY otp = ? AND expires_at > NOW() LIMIT 1";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('is', $user_id, $entered_otp);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // OTP is valid
                header('Location: new_password.php');
                exit();
            } else {
                $error_message = "Invalid OTP. Please try again.";
            }
        } else {
            $error_message = "An error occurred while verifying OTP. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="CSS/login.css">
</head>
<body id="loginBody">
    <div class="container">
        <img src="logo.jpg" alt="LOGO">
        <?php if (!empty($success_message)) { ?>
            <div class="success-message">
                <p><?= htmlspecialchars($success_message) ?></p>
            </div>
        <?php } ?>
        <?php if (!empty($error_message)) { ?>
            <div class="error-message">
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php } ?>
        <form action="password_reset_otp.php" method="POST">
            <input type="text" name="username" placeholder="Enter code" required><br>
            <input class="submit" type="submit" value="Submit">
        </form>
    </div>
</body>
</html>
