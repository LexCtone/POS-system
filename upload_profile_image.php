<?php
session_start();
require 'db_connection.php'; // Your database connection

$admin_id = $_SESSION['admin_id']; // Assuming the logged-in user's ID is stored in session

// Check if the user is an admin
$query = "SELECT role FROM accounts WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$admin_id]);
$user_role = $stmt->fetchColumn();

if ($user_role !== 'admin') {
    die("You do not have permission to upload a profile image.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profileImage'])) {
    $errors = [];
    $uploadDir = 'uploads/profile_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileTmpPath = $_FILES['profileImage']['tmp_name'];
    $fileName = $_FILES['profileImage']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($fileExtension, $allowedExtensions)) {
        $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
    }

    if (empty($errors)) {
        $newFileName = uniqid() . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $query = "UPDATE accounts SET profile_image = ? WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$destPath, $admin_id]);

            echo "Profile image updated successfully.";
        } else {
            echo "Error uploading the file.";
        }
    } else {
        foreach ($errors as $error) {
            echo "<p>$error</p>";
        }
    }
}
?>
