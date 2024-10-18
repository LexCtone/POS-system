<?php
// Include the existing database connection file
include 'connect.php'; // Ensure this path is correct

// Load the .env.php file
$env = require __DIR__ . '/.env.php'; // Adjust the path as needed

// Access the encryption key
$key = base64_decode($env['ENCRYPTION_KEY']); // Decode the encryption key

// SQL query to fetch both admin and cashier accounts including password
$sql = "SELECT id, name, username, email, password, iv, role, status FROM accounts WHERE role IN ('Admin', 'Cashier')";
$result = $conn->query($sql);

// Array to store results
$data = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Decrypt the password
        $cipher = "AES-256-CBC";
        $iv = hex2bin($row['iv']); // Convert IV from hex to binary
        $decryptedPassword = openssl_decrypt($row['password'], $cipher, $key, 0, $iv);

        // Add to data array with the decrypted password and email
        $data[] = [
            'id' => $row['id'],
            'name' => htmlspecialchars($row['name']), // Sanitize output
            'username' => htmlspecialchars($row['username']), // Sanitize output
            'email' => htmlspecialchars($row['email']), // Sanitize output
            'password' => $decryptedPassword, // Use decrypted password
            'role' => htmlspecialchars($row['role']), // Sanitize output
            'status' => htmlspecialchars($row['status']) // Sanitize output
        ];
    }
}

// Return data as JSON
header('Content-Type: application/json'); // Set the content type
echo json_encode($data);

$conn->close();
?>
