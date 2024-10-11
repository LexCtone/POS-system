<?php
session_start();
// Database connection
include 'connect.php'; // Ensure this path is correct and the file exists

// Fetch the username of the logged-in admin
$admin_username = "ADMINISTRATOR"; // Default value
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query_admin = "SELECT username FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($query_admin);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin_username = $row['username'];
    }
    $stmt->close();
}?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Category List</title>
  <link rel="stylesheet" type="text/css" href="CSS\Category.css">
  <script type="text/javascript" src="JAVASCRIPT\category.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
      <nav class="sidebar">
          <header>
            <img src="profile.png" alt="profile"/>
            <br><?php echo htmlspecialchars($admin_username); ?>
          </header>
          <ul>
              <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Home</a></li>
              <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product</a></li>
              <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
              <li><a href="StockEntry.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
              <li><a href="Brand.php"><i class='fa-solid fa-tag' style='font-size:30px'></i>Brand</a></li>
              <li><a href="Category.php"><i class='fa-solid fa-layer-group' style='font-size:30px'></i>Category</a></li>
              <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
              <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
              <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
              </ul>
      </nav>    
      <header>    
      <h2 class="ProductHeader">Category List  
  <input id="search-input" type="text" placeholder="Search...">
  <button id="search-button"><i class="fas fa-search"></i></button>
  <button id="add-product-button"><i class='fas fa-plus'></i></button>
</h2>
   
      </header>
    <div class="table-container">
        <table class="table" id="product-table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Category</th>
              <th scope="col">Operation</th>
            </tr>
          </thead>
          <tbody>
          <?php
include 'connect.php';
// Handle delete request
if (isset($_GET['deleteid'])) {
    $id_to_delete = $_GET['deleteid'];

    // Delete the record
    $delete_sql = "DELETE FROM categories WHERE id = $id_to_delete";
    mysqli_query($conn, $delete_sql);

    // Rearrange IDs after deletion
    $reset_sql = "SET @num := 0;";
    mysqli_query($conn, $reset_sql);

    $update_sql = "UPDATE categories SET id = @num := (@num + 1);";
    mysqli_query($conn, $update_sql);

    // Reset AUTO_INCREMENT to the next available ID
    $max_id_sql = "SELECT MAX(id) FROM categories";
    $max_id_result = mysqli_query($conn, $max_id_sql);
    $max_id_row = mysqli_fetch_array($max_id_result);
    $max_id = $max_id_row[0] + 1;

    $alter_sql = "ALTER TABLE categories AUTO_INCREMENT = $max_id";
    mysqli_query($conn, $alter_sql);

    // Redirect back to the category list
    header('Location: Category.php');
    exit();
}

// Fetch and display categories
$sql = "SELECT * FROM categories";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id'];
        $category = $row['Category'];

        echo '<tr>
        <th scope="row">'.$id.'</th>
        <td>'.$category.'</td>
        <td>
            <button class="button update-button" data-id="'.$id.'" data-category="'.htmlspecialchars($category, ENT_QUOTES, 'UTF-8').'"><a href="#" class="text-light">Update</a></button>
            <button class="button"><a href="?deleteid='.$id.'" class="text-light">Delete</a></button>
        </td>
        </tr>';
    }
}
mysqli_close($conn);
?>
    <div id="category-modal" class="modal">
      <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Add New Category</h2>
        <form id="category-form" action="add_category.php" method="post">
          <label for="category-name">Category Name:</label>
          <input type="text" id="category-name" name="category-name" required>
          <button type="submit">Add Category</button>
        </form>
      </div>
    </div>
    <!-- Update Category Modal -->
<div id="update-category-modal" class="modal">
  <div class="modal-content">
    <span class="close-button">&times;</span>
    <h2>Update Category</h2>
    <form id="update-category-form" action="update_category.php" method="post">
      <input type="hidden" id="update-category-id" name="category-id">
      <label for="update-category-name">Category Name:</label>
      <input type="text" id="update-category-name" name="category-name" required>
      <button type="submit">Update Category</button>
    </form>
  </div>
</div>
</body>
</html>
