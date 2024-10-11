<?php
session_start();
$error_message = '';
$remaining_time = 0; // Initialize the variable for countdown

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load PHPMailer classes
require 'vendor/phpmailer/PHPMailer/Exception.php';
require 'vendor/phpmailer/PHPMailer/PHPMailer.php';
require 'vendor/phpmailer/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token mismatch');
    }

    // Include database connection
    include('connect.php'); // Ensure this file contains a MySQLi connection ($conn)
    $env = require __DIR__ . '/.env.php'; // Load encryption key
    $key = base64_decode($env['ENCRYPTION_KEY']);

    // Sanitize and assign user inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $selected_role = trim($_POST['role']);

    // Prepare SQL query to check for user credentials
    $query = 'SELECT password, iv, role, id, username, email, failed_attempts, is_locked, lockout_time FROM accounts WHERE username = ? AND status = 1';
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists and verify password
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check if the account is locked
            if ($user['is_locked'] == 1) {
                // Check if lockout period has expired
                $lockout_duration = 3 * 60; // 3 minutes in seconds
                if (strtotime($user['lockout_time']) + $lockout_duration > time()) {
                    // Set remaining time for countdown
                    $remaining_time = (strtotime($user['lockout_time']) + $lockout_duration) - time();
                    $error_message = 'Your account is locked due to multiple failed login attempts. Please wait ' . floor($remaining_time / 60) . ' minutes and ' . ($remaining_time % 60) . ' seconds before trying again.';
                    exit();
                } else {
                    // Unlock the account if the duration has passed
                    $unlockQuery = "UPDATE accounts SET failed_attempts = 0, is_locked = 0, lockout_time = NULL WHERE id = ?";
                    $unlockStmt = $conn->prepare($unlockQuery);
                    $unlockStmt->bind_param('i', $user['id']);
                    $unlockStmt->execute();
                }
            }

            // Decrypt the stored password
            $cipher = "AES-256-CBC";
            $iv = hex2bin($user['iv']); // Convert IV from hex to binary
            $decryptedPassword = openssl_decrypt($user['password'], $cipher, $key, 0, $iv);

            // Check password and role
            if ($decryptedPassword === $password && $user['role'] === $selected_role) {
                // Reset failed attempts on successful login
                $resetAttemptsQuery = "UPDATE accounts SET failed_attempts = 0, is_locked = 0 WHERE id = ?";
                $resetStmt = $conn->prepare($resetAttemptsQuery);
                $resetStmt->bind_param('i', $user['id']);
                $resetStmt->execute();

                // Generate a 6-digit OTP and set expiration to 5 minutes from now
                $otp = rand(100000, 999999);
                $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes')); // Set expiration time

                // Insert OTP into login_otps table
                $insertOtpQuery = "INSERT INTO login_otps (user_id, otp, created_at, expires_at) VALUES (?, ?, NOW(), ?)";
                $otpStmt = $conn->prepare($insertOtpQuery);
                $otpStmt->bind_param('iss', $user['id'], $otp, $expires_at);

                if ($otpStmt->execute()) {
                    // Send OTP email using PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        // SMTP server configuration
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
                        $mail->SMTPAuth = true;
                        $mail->Username = 'lexdecastro123@gmail.com'; // Replace with your email
                        $mail->Password = 'hocn ufth dcvk ukda'; // Use your app-specific password here
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // Recipients
                        $mail->setFrom('noreply@yourdomain.com', 'Your Application');
                        $mail->addAddress($user['email']);

                        // Email content
                        $mail->isHTML(true);
                        $mail->Subject = "Your Login OTP Code";
                        $mail->Body = "Your OTP code is: <b>$otp</b>. It will expire in 5 minutes.";

                        $mail->send();

                        // Store user details for OTP verification and main session
                        $_SESSION['user_id'] = $user['id']; // Set main user session variable
                        $_SESSION['username'] = $user['username']; // Store username
                        $_SESSION['role'] = $user['role']; // Store role

                        // Redirect to OTP verification page
                        header('Location: verify_otp.php');
                        exit();
                    } catch (Exception $e) {
                        $error_message = "Failed to send OTP email. Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error_message = "Failed to generate OTP. Please try again.";
                }
            } else {
                // Increment failed attempts
                $failedAttemptsQuery = "UPDATE accounts SET failed_attempts = failed_attempts + 1 WHERE id = ?";
                $attemptStmt = $conn->prepare($failedAttemptsQuery);
                $attemptStmt->bind_param('i', $user['id']);
                $attemptStmt->execute();

                // Check if it exceeds the limit (e.g., 3)
                $attemptsCheckQuery = "SELECT failed_attempts FROM accounts WHERE id = ?";
                $attemptsCheckStmt = $conn->prepare($attemptsCheckQuery);
                $attemptsCheckStmt->bind_param('i', $user['id']);
                $attemptsCheckStmt->execute();
                $attemptsCheckResult = $attemptsCheckStmt->get_result();
                $attemptsCount = $attemptsCheckResult->fetch_assoc();

                if ($attemptsCount['failed_attempts'] >= 3) {
                    // Lock the account and set lockout time
                    $lockQuery = "UPDATE accounts SET is_locked = 1, lockout_time = NOW() WHERE id = ?";
                    $lockStmt = $conn->prepare($lockQuery);
                    $lockStmt->bind_param('i', $user['id']);
                    $lockStmt->execute();

                    $error_message = 'Your account is locked due to multiple failed login attempts. Please wait 3 minutes before trying again.';
                } else {
                    $error_message = 'Invalid password or role. Please try again.';
                }
            }
        } else {
            $error_message = 'Invalid username or account is inactive. Please try again or contact an administrator.';
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
                <?php if ($remaining_time > 0) { ?>
                    <p id="countdown"></p> <!-- Countdown display -->
                <?php } ?>
            </div>
        <?php } ?>
        <form action="Login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <select name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="cashier">Cashier</option>
            </select><br>
            <input class="submit" type="submit" value="Login">
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const countdownElement = document.getElementById('countdown');
            if (countdownElement) {
                let totalTime = <?= $remaining_time ?>;
                const countdownInterval = setInterval(() => {
                    if (totalTime <= 0) {
                        clearInterval(countdownInterval);
                        countdownElement.textContent = 'You can now try logging in again.';
                    } else {
                        const minutes = Math.floor(totalTime / 60);
                        const seconds = totalTime % 60;
                        countdownElement.textContent = `You can try logging in again in ${minutes}:${seconds < 10 ? '0' : ''}${seconds} seconds.`;
                    }
                    totalTime--;
                }, 1000);
            }
        });
    </script>
</body>
</html>
