<?php
session_start();
// Database connection
include 'connect.php'; // Ensure this path is correct and the file exists

// Fetch the username of the logged-in admin
$admin_name = "ADMINISTRATOR"; // Default value
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query_admin = "SELECT name FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($query_admin);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin_name = $row['name'];
    }
    $stmt->close();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Category List</title>
  <link rel="stylesheet" type="text/css" href="CSS/Category.css">
  <script type="text/javascript" src="JAVASCRIPT/category.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php" class="<?php echo ($current_page == 'Dashboard.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li>
        <li>
            <a href="Product.php" class="<?php echo ($current_page == 'Product.php' || $current_page == 'Brand.php' || $current_page == 'Category.php') ? 'selected' : ''; ?>">
                <i class='fas fa-archive' style='font-size:30px'></i>Product
                <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i>
            </a>
            <ul class="submenu" style="<?php echo ($current_page == 'Brand.php' || $current_page == 'Category.php') ? 'display:block;' : ''; ?>">
                <li><a href="Brand.php" class="<?php echo ($current_page == 'Brand.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php" class="<?php echo ($current_page == 'Category.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php" class="<?php echo ($current_page == 'Vendor.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="StockEntry.php" class="<?php echo ($current_page == 'StockEntry.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
        <li><a href="Records.php" class="<?php echo ($current_page == 'Records.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php" class="<?php echo ($current_page == 'UserSettings.php') ? 'selected' : ''; ?>"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav>

<header>    
    <h2 class="ProductHeader">Category List  
        <input id="search-input" type="text" placeholder="Search..." onkeyup="searchCategories()">
        <button id="add-product-button"><i class='fas fa-plus'></i></button>
    </h2>
</header>
<div class="content">
    <div class="table-container">
        <table class="table" id="category-table">
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

                // Redirect back to the category list
                header('Location: Category.php');
                exit();
            }

            // Fetch and display categories
            $sql = "SELECT * FROM categories";
            $result = mysqli_query($conn, $sql);
            $row_number = 1; // Initialize row number
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $category = $row['Category'];

                    echo '<tr>
                    <td scope="row">'.$row_number.'</td> <!-- Display row number -->
                    <td>'.$category.'</td>
                    <td>
                        <button class="button update-button" data-id="'.$row['id'].'" data-category="'.htmlspecialchars($category, ENT_QUOTES, 'UTF-8').'"><a href="#" class="text-light">Update</a></button>
                        <button class="button"><a href="?deleteid='.$row['id'].'" class="text-light">Delete</a></button>
                    </td>
                    </tr>';
                    $row_number++; // Increment row number
                }
            }
            mysqli_close($conn);
            ?>
            </tbody>
        </table>
    </div>
    <!-- Modal for Adding Category -->
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
</div>

<script>
function searchCategories() {
    const input = document.getElementById('search-input');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('category-table');
    const trs = table.getElementsByTagName('tr');

    for (let i = 1; i < trs.length; i++) { // Start from 1 to skip header
        const td = trs[i].getElementsByTagName('td')[1]; // Get Category column
        if (td) {
            const txtValue = td.textContent || td.innerText;
            trs[i].style.display = txtValue.toLowerCase().includes(filter) ? '' : 'none';
        }
    }
}
</script>
</body>
</html>
