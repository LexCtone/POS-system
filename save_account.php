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
    $username = $_POST['username'];
    $password = $_POST['new-password']; // Make sure this matches your form field name
    $role = $_POST['role'];

    // Encryption settings
    $cipher = "AES-256-CBC";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher)); // Generate a unique IV

    // Encrypt the password
    $encryptedPassword = openssl_encrypt($password, $cipher, $key, 0, $iv);

    // Convert IV to hexadecimal format for storage
    $iv_hex = bin2hex($iv);

    // Debugging output: Print the encrypted password and IV
    echo "Encrypted Password: " . htmlspecialchars($encryptedPassword) . "<br>";
    echo "IV (Hex): " . htmlspecialchars($iv_hex) . "<br>";

    // Prepare and bind the SQL statement
    $stmt = $conn->prepare("INSERT INTO accounts (username, password, iv, role, status) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $username, $encryptedPassword, $iv_hex, $role);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to a success page or display a success message
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
