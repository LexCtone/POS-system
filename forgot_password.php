<?php
session_start();
$error_message = '';
$success_message = '';

// Load PHPMailer classes
require 'vendor/phpmailer/PHPMailer/Exception.php';
require 'vendor/phpmailer/PHPMailer/PHPMailer.php';
require 'vendor/phpmailer/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['username']);

    include('connect.php');

    // Check if email exists
    $query = 'SELECT id, email FROM accounts WHERE email = ? AND status = 1';
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $otp = rand(100000, 999999);
            $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            // Store OTP in the database
            $insertOtpQuery = "INSERT INTO forgot_password_otps (user_id, otp, created_at, expires_at) VALUES (?, ?, NOW(), ?)";
            $otpStmt = $conn->prepare($insertOtpQuery);
            $otpStmt->bind_param('iss', $user['id'], $otp, $expires_at);
            if ($otpStmt->execute()) {
                // Send OTP email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // SMTP configuration
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'lexdecastro123@gmail.com';
                    $mail->Password = 'yygy vaqn mbwn agrq';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('noreply@yourdomain.com', 'Your Application'); // Update as needed
                    $mail->addAddress($user['email']);

                    $mail->isHTML(true);
                    $mail->Subject = "Your Password Reset OTP Code";
                    $mail->Body = "Your OTP code is: <b>$otp</b>. It will expire in 5 minutes.";

                    $mail->send();

                    // Set success message to be displayed in the next page
                    $_SESSION['success_message'] = "We have sent you the OTP to reset your password - <b>{$user['email']}</b>";
                    $_SESSION['user_id'] = $user['id']; // Store user ID in session for later use
                    header('Location: password_reset_otp.php');
                    exit();
                } catch (Exception $e) {
                    $error_message = "Failed to send OTP email. Error: {$mail->ErrorInfo}";
                }
            } else {
                $error_message = "Failed to generate OTP. Please try again.";
            }
        } else {
            $error_message = "Email not found or inactive.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St Vincent Hardware Login</title>
    <link rel="stylesheet" href="CSS/login.css">
</head>
<body id="loginBody">
    <div class="container">
        <img src="logo.jpg" alt="LOGO">
        <?php if (!empty($error_message)) { ?>
            <div class="error">
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php } ?>
        <form action="forgot_password.php" method="POST">
            <input type="text" name="username" placeholder="Enter your email" required><br>
            <input class="submit" type="submit" value="Continue">
        </form>
    </div>
</body>
</html>
