<?php
include 'connect.php'; // Ensure this path is correct and the file exists

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $vendorName = $_POST['vendor-name'];
    $contactPerson = $_POST['contact-person'];
    $contactNumber = $_POST['contact-number'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $fax = $_POST['fax'];

    // Validate input
    if (!empty($vendorName) && !empty($contactPerson) && !empty($contactNumber) && !empty($address) && !empty($email) && !empty($fax)) {
        // Insert into the database
        $stmt = $conn->prepare("INSERT INTO vendor (vendor, contact, telephone, address, email, fax) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $vendorName, $contactPerson, $contactNumber, $address, $email, $fax);

        if ($stmt->execute()) {
            echo 'Vendor added successfully';
        } else {
            echo 'Failed to add vendor';
        }

        $stmt->close();
    } else {
        echo 'All fields are required';
    }
}
$conn->close();
?>