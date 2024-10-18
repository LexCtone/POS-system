<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
include('connect.php');

// Load PHPMailer classes
require 'vendor/phpmailer/PHPMailer/Exception.php';
require 'vendor/phpmailer/PHPMailer/PHPMailer.php';
require 'vendor/phpmailer/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Generate a new OTP
$new_otp = rand(100000, 999999);
$expires_at = date('Y-m-d H:i:s', strtotime('+2 minutes')); // Set expiration time

// Insert new OTP into the database
$query = "INSERT INTO login_otps (user_id, otp, expires_at) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('iss', $user_id, $new_otp, $expires_at);

if ($stmt->execute()) {
    // Fetch the user's email
    $email_query = "SELECT email FROM accounts WHERE id = ?";
    $email_stmt = $conn->prepare($email_query);
    $email_stmt->bind_param('i', $user_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $email_row = $email_result->fetch_assoc();
    $user_email = $email_row['email'];

    // Sending OTP via email
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'stvincenthardware2020@gmail.com'; // Replace with your email
        $mail->Password = 'rmmn tfus kfxk dmym'; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('noreply@yourdomain.com', 'Your Name');
        $mail->addAddress($user_email);

        $mail->isHTML(true);
        $mail->Subject = "Your OTP Code";
        $mail->Body = "Your OTP is: <b>$new_otp</b>. It will expire in 2 minutes.";

        $mail->send();

        $_SESSION['otp_sent'] = true; // Flag to indicate OTP was sent
        header('Location: verify_otp.php'); // Redirect back to OTP verification page
        exit();
    } catch (Exception $e) {
        error_log("Failed to send OTP email. Error: {$mail->ErrorInfo}");
    }
} else {
    // Handle the error
    error_log("Error inserting new OTP: " . $stmt->error);
}

$stmt->close();
$conn->close();
