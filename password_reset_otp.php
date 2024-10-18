<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
include('connect.php');

require 'vendor/phpmailer/PHPMailer/Exception.php';
require 'vendor/phpmailer/PHPMailer/PHPMailer.php';
require 'vendor/phpmailer/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is redirected after sending OTP
$success_message = '';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Get user's email
    $email_query = "SELECT email FROM accounts WHERE id = ?";
    $email_stmt = $conn->prepare($email_query);
    $email_stmt->bind_param('i', $user_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $email_row = $email_result->fetch_assoc();
    $user_email = $email_row['email'];

    if (isset($_SESSION['success_message'])) {
        // Set success message without <b> tags
        $success_message = "We have sent you the OTP to reset your password - $user_email.";
        unset($_SESSION['success_message']); // Clear the message after displaying
    }
} else {
    header('Location: forgot_password.php');
    exit();
}

$error_message = '';

// Handle POST request for OTP verification or resend
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    // Check if the request is for OTP verification
    if (isset($_POST['otp'])) {
        $entered_otp = trim($_POST['otp']); // Use a more descriptive name for the input

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

    // Check if the request is for resending the OTP
    if (isset($_POST['resend_otp'])) {
        $new_otp = rand(100000, 999999);
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes')); // Set expiration time

        // Insert new OTP into the database
        $query = "INSERT INTO forgot_password_otps (user_id, otp, expires_at) VALUES (?, ?, ?)";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param('iss', $user_id, $new_otp, $expires_at);
            if ($stmt->execute()) {
                // Send OTP email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'stvincenthardware2020@gmail.com'; // Your email
                    $mail->Password = 'rmmn tfus kfxk dmym'; // Your email password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('noreply@yourdomain.com', 'Your Company Name');
                    $mail->addAddress($user_email);

                    $mail->isHTML(true);
                    $mail->Subject = "Your Password Reset OTP Code";
                    $mail->Body = "Your OTP code is: <b>$new_otp</b>. It will expire in 5 minutes.";

                    $mail->send();
                    $_SESSION['success_message'] = "A new OTP has been sent to your email.";
                } catch (Exception $e) {
                    $error_message = "Failed to send OTP email. Error: {$mail->ErrorInfo}";
                }
            } else {
                $error_message = "Failed to generate OTP. Please try again.";
            }
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
    <script>
        // Function to hide the error message after 2 seconds
        function hideErrorMessage() {
            const errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 2000); // 2000 milliseconds = 2 seconds
            }
        }

        window.onload = function() {
            hideErrorMessage(); // Call function to hide error message on load
        };
    </script>
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
            <div class="error-message" id="error-message"> <!-- Added ID for JavaScript targeting -->
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php } ?>
        <form action="password_reset_otp.php" method="POST">
            <input type="text" name="otp" placeholder="Enter code"><br>
            <input class="submit" type="submit" value="Submit">
            <input type="hidden" name="resend_otp" value="1">
            <button type="submit" class="submit" style="margin-top: 10px;">Resend OTP</button>
        </form>
    </div>
</body>
</html>
