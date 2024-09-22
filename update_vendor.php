<?php
include 'connect.php'; // Ensure this path is correct and the file exists

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $vendorId = $_POST['vendor-id'];
    $vendorName = $_POST['vendor-name'];
    $contactPerson = $_POST['contact-person'];
    $contactNumber = $_POST['contact-number'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $fax = $_POST['fax'];

    // Validate input
    if (!empty($vendorId) && !empty($vendorName) && !empty($contactPerson) && !empty($contactNumber) && !empty($address) && !empty($email) && !empty($fax)) {
        // Update the vendor in the database
        $stmt = $conn->prepare("UPDATE vendor SET vendor = ?, contact = ?, telephone = ?, address = ?, email = ?, fax = ? WHERE id = ?");
        $stmt->bind_param('ssssssi', $vendorName, $contactPerson, $contactNumber, $address, $email, $fax, $vendorId);

        if ($stmt->execute()) {
            echo 'Vendor updated successfully';
        } else {
            echo 'Failed to update vendor';
        }

        $stmt->close();
    } else {
        echo 'All fields are required';
    }
}

$conn->close();
?>
