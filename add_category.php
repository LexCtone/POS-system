<?php
include 'connect.php'; // Ensure this path is correct and the file exists

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoryName = $_POST['category-name'];

  // Validate input
  if (!empty($categoryName)) {
    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO categories (Category) VALUES (?)");
    $stmt->bind_param('s', $categoryName);

    if ($stmt->execute()) {
      echo 'Category added successfully';
    } else {
      echo 'Failed to add category';
    }

    $stmt->close();
  } else {
    echo 'Category name is required';
  }
}

$conn->close();
?>
