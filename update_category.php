<?php
include 'connect.php'; // Ensure this path is correct and the file exists

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoryId = $_POST['category-id'];
  $categoryName = $_POST['category-name'];

  // Validate input
  if (!empty($categoryId) && !empty($categoryName)) {
    // Update the category in the database
    $stmt = $conn->prepare("UPDATE categories SET Category = ? WHERE id = ?");
    $stmt->bind_param('si', $categoryName, $categoryId);

    if ($stmt->execute()) {
      echo 'Category updated successfully';
    } else {
      echo 'Failed to update category';
    }

    $stmt->close();
  } else {
    echo 'Category ID and name are required';
  }
}

$conn->close()
?>
