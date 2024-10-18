<?php
// Include the database connection file
include 'connect.php'; // Ensure this path is correct

// Load the .env.php file
$env = require __DIR__ . '/.env.php'; // Adjust the path as needed

// Access the encryption key and decode it
$key = base64_decode($env['ENCRYPTION_KEY']);

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['new-password'];
    $role = $_POST['role'];

    // Check if the username already exists
    $query_check_username = "SELECT id FROM accounts WHERE username = ?";
    $stmt_check_username = $conn->prepare($query_check_username);
    $stmt_check_username->bind_param("s", $username);
    $stmt_check_username->execute();
    $stmt_check_username->store_result();

    if ($stmt_check_username->num_rows > 0) {
        // Username already exists, redirect back with error
        header("Location: UserSettings.php?error=username_taken");
        $stmt_check_username->close();
        exit();
    }
    $stmt_check_username->close();

    // Check if the email already exists
    $query_check_email = "SELECT id FROM accounts WHERE email = ?";
    $stmt_check_email = $conn->prepare($query_check_email);
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();

    if ($stmt_check_email->num_rows > 0) {
        // Email already exists, redirect back with error
        header("Location: UserSettings.php?error=email_taken");
        $stmt_check_email->close();
        exit();
    }
    $stmt_check_email->close();

    // Encryption settings
    $cipher = "AES-256-CBC";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

    // Encrypt the password
    $encryptedPassword = openssl_encrypt($password, $cipher, $key, 0, $iv);

    // Convert IV to hexadecimal format for storage
    $iv_hex = bin2hex($iv);

    // Prepare and bind the SQL statement
    $stmt = $conn->prepare("INSERT INTO accounts (name, username, email, password, iv, role, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssssss", $name, $username, $email, $encryptedPassword, $iv_hex, $role);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to UserSettings.php with success message
        header("Location: UserSettings.php?success=1");
        exit();
    } else {
        // Handle the error
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
