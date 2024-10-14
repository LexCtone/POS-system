<?php
session_start();
$error_message = '';
$remaining_time = 0;

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
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token mismatch');
    }

    // Include database connection
    include('connect.php');
    $env = require __DIR__ . '/.env.php';
    $key = base64_decode($env['ENCRYPTION_KEY']);

    // Sanitize and assign user inputs
    $username = trim($_POST['username'] ?? ''); // Use null coalescing operator
    $password = trim($_POST['password'] ?? ''); // Use null coalescing operator

    // Prepare SQL query to check for user credentials
    $query = 'SELECT password, iv, role, id, username, email, failed_attempts, is_locked, lockout_time FROM accounts WHERE username = ? AND status = 1';
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check if the account is locked
            if ($user['is_locked'] == 1) {
                $lockout_duration = 179; // 2:59 in seconds
                if (strtotime($user['lockout_time']) + $lockout_duration > time()) {
                    $remaining_time = (strtotime($user['lockout_time']) + $lockout_duration) - time();
                    $error_message = 'Your account is locked due to multiple failed login attempts. Please wait before trying again.';
                } else {
                    // Unlock the account if the duration has passed
                    $unlockQuery = "UPDATE accounts SET failed_attempts = 0, is_locked = 0, lockout_time = NULL WHERE id = ?";
                    $unlockStmt = $conn->prepare($unlockQuery);
                    $unlockStmt->bind_param('i', $user['id']);
                    $unlockStmt->execute();
                }
            }

            if (!$user['is_locked']) {
                // Decrypt the stored password
                $cipher = "AES-256-CBC";
                $iv = hex2bin($user['iv']);
                $decryptedPassword = openssl_decrypt($user['password'], $cipher, $key, 0, $iv);

                // Check password and role
                if ($decryptedPassword === $password && $user['role']) {
                    // Reset failed attempts on successful login
                    $resetAttemptsQuery = "UPDATE accounts SET failed_attempts = 0, is_locked = 0 WHERE id = ?";
                    $resetStmt = $conn->prepare($resetAttemptsQuery);
                    $resetStmt->bind_param('i', $user['id']);
                    $resetStmt->execute();

                    // Generate OTP and send email
                    $otp = rand(100000, 999999);
                    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                    $insertOtpQuery = "INSERT INTO login_otps (user_id, otp, created_at, expires_at) VALUES (?, ?, NOW(), ?)";
                    $otpStmt = $conn->prepare($insertOtpQuery);
                    $otpStmt->bind_param('iss', $user['id'], $otp, $expires_at);

                    if ($otpStmt->execute()) {
                        $mail = new PHPMailer(true);
                        try {
                            // SMTP configuration
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'lexdecastro123@gmail.com'; // Your email
                            $mail->Password = 'yygy vaqn mbwn agrq'; // Your email password
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            $mail->setFrom('noreply@yourdomain.com', 'Your Application');
                            $mail->addAddress($user['email']);

                            $mail->isHTML(true);
                            $mail->Subject = "Your Login OTP Code";
                            $mail->Body = "Your OTP code is: <b>$otp</b>. It will expire in 5 minutes.";

                            $mail->send();

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['role'] = $user['role'];

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

                    // Check if it exceeds the limit
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

                        $remaining_time = 179; // 2:59 in seconds
                        $error_message = 'Your account is locked due to multiple failed login attempts. Please wait before trying again.';
                    } else {
                        $error_message = 'Invalid password or role. Please try again.';
                    }
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
    <style>
        .success-message {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body id="loginBody">
    <div class="container">
        <img src="logo.jpg" alt="LOGO">
        <?php if (!empty($error_message)) { ?>
            <div class="error">
                <p><?= htmlspecialchars($error_message) ?></p>
                <?php if ($remaining_time > 0) { ?>
                    <p id="countdown" aria-live="polite"></p>
                <?php } ?>
            </div>
        <?php } ?>
        <form action="Login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <p><a href="forgot_password.php">Forgot Password?</a></p>
            <input class="submit" type="submit" value="Login">
        </form>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const countdownElement = document.getElementById('countdown');
    if (countdownElement) {
        let totalSeconds = <?= $remaining_time ?>;

        function updateCountdown() {
            if (totalSeconds <= 0) {
                countdownElement.textContent = 'You can now log in again.';
                countdownElement.className = 'success-message';
                return;
            }

            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            countdownElement.textContent = `You can try logging in again in ${minutes}:${seconds.toString().padStart(2, '0')}.`;
            totalSeconds--;

            setTimeout(updateCountdown, 1000);
        }

        updateCountdown();
    }
});
</script>
</body>
</html>
