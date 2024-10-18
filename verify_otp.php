<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
include('connect.php');

if (!isset($_SESSION['user_id'])) {
    error_log("Unauthorized access attempt to verify login OTP");
    header('Location: Login.php');
    exit();
}

$error_message = '';

// Check for OTP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp']);
    $user_id = $_SESSION['user_id'];

    error_log("Verifying login OTP for User ID: " . $user_id);

    $query = "SELECT * FROM login_otps WHERE user_id = ? AND BINARY otp = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('is', $user_id, $entered_otp);

        if (!$stmt->execute()) {
            error_log("Query execution failed: " . $stmt->error);
            $error_message = "An error occurred while verifying OTP. Please try again.";
        } else {
            $result = $stmt->get_result();
            error_log("Query result rows: " . $result->num_rows);

            if ($result->num_rows > 0) {
                // OTP is valid
                $_SESSION['authenticated'] = true;

                // Redirect to the dashboard based on role
                $role_query = "SELECT role FROM accounts WHERE id = ?";
                $role_stmt = $conn->prepare($role_query);
                $role_stmt->bind_param('i', $user_id);
                $role_stmt->execute();
                $role_result = $role_stmt->get_result();
                $user_role = $role_result->fetch_assoc()['role'];

                if ($user_role === 'admin') {
                    header('Location: Dashboard.php');
                } elseif ($user_role === 'cashier') {
                    header('Location: dash/transaction.php');
                } else {
                    $error_message = "Unknown user role. Please contact an administrator.";
                    error_log("Unknown user role: " . $user_role);
                }
                exit();
            } else {
                $error_message = "Invalid OTP entered for User ID: " . $user_id;
                error_log("Invalid OTP entered for user ID: " . $user_id . ", Entered OTP: " . $entered_otp);
            }
        }
    } else {
        $error_message = "Failed to prepare SQL statement.";
        error_log("Failed to prepare query: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login OTP</title>
    <link rel="stylesheet" href="CSS/verify_otp.css">
    <script>
        let countdownTimer;

        function startCountdown() {
            let countdownTime = 2; // 2 minutes in seconds
            const countdownDisplay = document.getElementById('countdown');

            countdownTimer = setInterval(function() {
                const minutes = Math.floor(countdownTime / 60);
                const seconds = countdownTime % 60;

                countdownDisplay.innerHTML = `Resend OTP in ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

                countdownTime--;

                if (countdownTime < 0) {
                    clearInterval(countdownTimer);
                    document.getElementById('resend-button').disabled = false; // Enable resend button
                    countdownDisplay.innerHTML = ''; // Clear countdown
                }
            }, 1000);
        }

        window.onload = function() {
            startCountdown();
        };
    </script>
</head>
<body id="loginBody">
    <div class="container">
        <h2>Verify Login OTP</h2>
        <?php if (!empty($error_message)) { ?>
            <div class="error-message">
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php } ?>
        <form action="verify_otp.php" method="POST" style="margin-top: 20px;">
    <div class="input-container">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <input class="submit" type="submit" value="Verify OTP">
    </div>
</form>

        <div id="countdown"></div>
        <button id="resend-button" onclick="location.href='resend_otp.php'" disabled>Resend OTP</button>
        <p><a href="Login.php">Back to Login</a></p>
    </div>
</body>
</html>
