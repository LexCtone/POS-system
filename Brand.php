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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Brand</title>
  <link rel="stylesheet" type="text/css" href="CSS\Brand.css">
  <script type="text/javascript" src="JAVASCRIPT\Brand.js" defer></script>
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
      <h2 class="ProductHeader">Brand List
        <input id="search-input" type="text" placeholder="Search...">
        <button id="search-button"><i class="fas fa-search"></i></button>
        <button id="add-brand-button"><i class='fas fa-plus'></i></button>
      </h2>
      </header>
    <div class="table-container">
        <table class="table" id="brand-table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Brand</th>
              <th scope="col">Operation</th>
            </tr>
          </thead>
          <tbody>
          <?php
include 'connect.php'; // Ensure this path is correct and the file exists

// Handle delete request
if (isset($_GET['deleteid'])) {
    $id_to_delete = $_GET['deleteid'];

    // Delete the record
    $delete_sql = "DELETE FROM brands WHERE id = $id_to_delete";
    mysqli_query($conn, $delete_sql);

    // Rearrange IDs after deletion
    $reorder_sql = "
        SET @num := 0;
        UPDATE brands SET id = (@num := @num + 1) ORDER BY id;
    ";
    mysqli_multi_query($conn, $reorder_sql);
    while (mysqli_next_result($conn)) {;} // Flush multi-query results

    // Reset AUTO_INCREMENT to the next available ID
    $alter_sql = "ALTER TABLE brands AUTO_INCREMENT = 1";
    mysqli_query($conn, $alter_sql);

    // Redirect back to the brand list
    header('Location: Brand.php');
    exit();
}

// Fetch and display brands
$sql = "SELECT * FROM brands";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id'];
        $brand = $row['Brand'];

        echo '<tr>
        <th scope="row">'.$id.'</th>
        <td>'.$brand.'</td>
        <td>
            <button class="button update-button" data-id="'.$id.'" data-brand="'.htmlspecialchars($brand, ENT_QUOTES, 'UTF-8').'"><a href="#" class="text-light">Update</a></button>
            <button class="button"><a href="?deleteid='.$id.'" class="text-light">Delete</a></button>
        </td>
        </tr>';
    }
}
mysqli_close($conn);
?>
    <div id="brand-modal" class="modal">
      <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Add New Brand</h2>
        <form id="brand-form" action="add_brand.php" method="post">
          <label for="brand-name">Brand Name:</label>
          <input type="text" id="brand-name" name="brand-name" required>
          <button type="submit">Add Brand</button>
        </form>
      </div>
    </div>
    <!-- Update Brand Modal -->
<div id="update-brand-modal" class="modal">
  <div class="modal-content">
    <span class="close-button">&times;</span>
    <h2>Update Brand</h2>
    <form id="update-brand-form" action="update_brand.php" method="post">
      <input type="hidden" id="update-brand-id" name="brand-id">
      <label for="update-brand-name">Brand Name:</label>
      <input type="text" id="update-brand-name" name="brand-name" required>
      <button type="submit">Update Brand</button>
    </form>
  </div>
</div>
</body>
</html>
