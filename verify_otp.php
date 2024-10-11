<?php
session_start();
include('connect.php'); // Database connection

// Check if user is logged in and OTP was generated
if (!isset($_SESSION['pending_user_id']) || !isset($_SESSION['pending_username'])) {
    header('Location: Login.php'); // Redirect to login if session is missing
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp']);
    $user_id = $_SESSION['pending_user_id'];

    // Query to check if OTP is valid and not expired
    $query = "SELECT * FROM login_otps WHERE user_id = ? AND otp = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $user_id, $entered_otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // OTP is valid; clear OTPs and redirect based on role
        $conn->query("DELETE FROM login_otps WHERE user_id = $user_id"); // Clear OTPs

        // Redirect based on role
        switch ($_SESSION['pending_role']) {
            case 'admin':
                header('Location: Dashboard.php');
                break;
            case 'cashier':
                header('Location: dash/Cashier_dashboard.php');
                break;
            default:
                $error_message = 'Invalid role detected. Please contact support.';
                break;
        }
        exit();
    } else {
        // OTP is invalid or expired
        $error_message = "Invalid or expired OTP. Please try again.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <style>
        .container {
            width: 300px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify OTP</h2>
        <?php if (!empty($error_message)) { ?>
            <div class="error">
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php } ?>
        <form action="verify_otp.php" method="POST">
            <label for="otp">Enter OTP:</label>
            <input type="text" name="otp" required>
            <button type="submit">Verify OTP</button>
        </form>
    </div>
</body>
</html>
