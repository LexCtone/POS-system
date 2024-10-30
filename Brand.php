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
  <title>Brand</title>
  <link rel="stylesheet" type="text/css" href="CSS/Brand.css">
  <script type="text/javascript" src="JAVASCRIPT/Brand.js" defer></script>
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
    <h2 class="ProductHeader">Brand List
        <input id="search-input" type="text" placeholder="Search..." onkeyup="searchBrands()">
        <button id="add-brand-button"><i class='fas fa-plus'></i></button>
    </h2>
</header>
<div class="content">
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
            include 'connect.php';

            // Handle delete request
            if (isset($_GET['deleteid'])) {
                $id_to_delete = $_GET['deleteid'];

                // Delete the record
                $delete_sql = "DELETE FROM brands WHERE id = $id_to_delete";
                mysqli_query($conn, $delete_sql);

                // Redirect back to the brand list
                header('Location: Brand.php');
                exit();
            }

            // Fetch and display brands
            $sql = "SELECT * FROM brands";
            $result = mysqli_query($conn, $sql);
            $row_number = 1; // Initialize row number
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $brand = $row['Brand'];

                    echo '<tr>
                    <td scope="row">'.$row_number.'</td> <!-- Display row number -->
                    <td>'.$brand.'</td>
                    <td>
                        <button class="button update-button" data-id="'.$row['id'].'" data-brand="'.htmlspecialchars($brand, ENT_QUOTES, 'UTF-8').'"><a href="#" class="text-light">Update</a></button>
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
    <!-- Modal for Adding Brand -->
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
</div>

<script>
function searchBrands() {
    const input = document.getElementById('search-input');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('brand-table');
    const trs = table.getElementsByTagName('tr');

    for (let i = 1; i < trs.length; i++) { // Start from 1 to skip header
        const td = trs[i].getElementsByTagName('td')[1]; // Get Brand column
        if (td) {
            const txtValue = td.textContent || td.innerText;
            trs[i].style.display = txtValue.toLowerCase().includes(filter) ? '' : 'none';
        }
    }
}
</script>
</body>
</html>
