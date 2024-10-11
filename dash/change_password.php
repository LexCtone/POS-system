<?php
session_start();
include '../connect.php';

// Load the .env.php file to access the encryption key
$env = require '../.env.php'; // Correct path to .env.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Debugging log to ensure the request is hitting the backend
error_log("Password change request received for user ID: {$_SESSION['user_id']}");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $userId = $_SESSION['user_id'];

    // Debugging log to check form data
    error_log("Form Data - Current Password: $currentPassword, New Password: $newPassword");

    // Fetch the stored password and IV
    $stmt = $conn->prepare("SELECT password, iv FROM accounts WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Check if the user exists
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Decrypt the stored password
    $cipher = "AES-256-CBC";
    $key = base64_decode($env['ENCRYPTION_KEY']); // Decode the encryption key
    $iv = hex2bin($user['iv']); // Convert IV from hex to binary
    $decryptedPassword = openssl_decrypt($user['password'], $cipher, $key, 0, $iv);

    // Check if the decrypted password matches the current password input
    if ($decryptedPassword !== $currentPassword) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }

    // Validate the new password
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
        exit();
    }

    // Encrypt the new password
    $newIv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher)); // Generate a new IV
    $encryptedNewPassword = openssl_encrypt($newPassword, $cipher, $key, 0, $newIv); // Encrypt new password

    // Update password
    $stmt = $conn->prepare("UPDATE accounts SET password = ?, iv = ? WHERE id = ?");
    $hexIv = bin2hex($newIv); // Convert IV to hex for storage
    $stmt->bind_param("ssi", $encryptedNewPassword, $hexIv, $userId); // Pass the variable instead

    if ($stmt->execute()) {
        session_destroy(); // End the current session
        echo json_encode(['success' => true, 'message' => 'Password updated successfully. Please log in again.']);
    } else {
        // Log the error message from the database
        error_log("Error updating password: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
