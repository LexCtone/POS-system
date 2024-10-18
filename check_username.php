<?php
// Include the database connection file
include 'connect.php';

if (isset($_POST['username'])) {
    $username = $_POST['username'];
    $query = "SELECT id FROM accounts WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo 'username_taken';
    } else {
        echo 'username_available';
    }
    $stmt->close();
}

if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $query = "SELECT id FROM accounts WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo 'email_taken';
    } else {
        echo 'email_available';
    }
    $stmt->close();
}
?>
