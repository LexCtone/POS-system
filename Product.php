<?php
session_start();
include 'connect.php';


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

// Fetch the highest barcode
$barcode_query = "SELECT MAX(CAST(Barcode AS UNSIGNED)) AS max_barcode FROM products";
$barcode_result = mysqli_query($conn, $barcode_query);
$max_barcode_row = mysqli_fetch_assoc($barcode_result);
$next_barcode = $max_barcode_row['max_barcode'] ? $max_barcode_row['max_barcode'] + 1 : 1000001; // Default to 1000001 if no barcodes exist

// Fetch and display products
$sql = "
    SELECT 
        p.id, 
        p.Barcode, 
        p.Description, 
        p.Brand, 
        p.Category, 
        p.Price, 
        p.cost_price, 
        p.Quantity, 
        p.vendor_id,  -- Ensure vendor_id is included
        COALESCE(v.vendor, 'Unknown') AS Vendor
    FROM 
        products p
    LEFT JOIN 
        vendor v ON p.vendor_id = v.id";


$result = mysqli_query($conn, $sql);

// Fetch brands, vendor, and categories for dropdowns
$brand_query = "SELECT * FROM brands";
$brand_result = mysqli_query($conn, $brand_query);
$brand_result_update = mysqli_query($conn, $brand_query);

$category_query = "SELECT * FROM categories";
$category_result = mysqli_query($conn, $category_query);
$category_result_update = mysqli_query($conn, $category_query);

if (!$brand_result || !$category_result) {
    die("Error fetching data: " . mysqli_error($conn));
}

$vendor_query = "SELECT id, vendor FROM vendor";
$vendor_result = mysqli_query($conn, $vendor_query);

if (!$vendor_result) {
    die("Error fetching vendors: " . mysqli_error($conn));
}




// Handle archive request
if (isset($_GET['archiveid'])) {
    $id_to_archive = $_GET['archiveid'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch product details
        $product_query = "SELECT id, Barcode, Description, Brand, Category, Price, cost_price, Quantity FROM products WHERE id = ?";
        $stmt = $conn->prepare($product_query);
        $stmt->bind_param('i', $id_to_archive);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
    
        if (!$product) {
            throw new Exception("Product not found for archiving.");
        }
    
        // Fetch vendor details and Reference_Number
        $vendor_query = "SELECT vendor, reference FROM stock_in_history WHERE Barcode = ? ORDER BY reference DESC LIMIT 1";
        $vendor_stmt = $conn->prepare($vendor_query);
        $vendor_stmt->bind_param('s', $product['Barcode']);
        $vendor_stmt->execute();
        $vendor_result = $vendor_stmt->get_result();
        $vendor_row = $vendor_result->fetch_assoc();
    
        $vendor_name = $vendor_row ? $vendor_row['vendor'] : 'Unknown';
        $reference_number = $vendor_row ? $vendor_row['reference'] : 'Unknown';
    
        // Get the current admin who is archiving the product
        $archived_by = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';
    
        // Insert the product into archived_products
        $archived_product_sql = "INSERT INTO archived_products (id, Barcode, reference, Description, Brand, Category, Price, cost_price, Quantity, Vendor, archived_by) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $archived_stmt = $conn->prepare($archived_product_sql);
        $archived_stmt->bind_param(
            'isssssddsss',
            $product['id'],
            $product['Barcode'],
            $reference_number,
            $product['Description'],
            $product['Brand'],
            $product['Category'],
            $product['Price'],
            $product['cost_price'],
            $product['Quantity'],
            $vendor_name,
            $archived_by
        );
    
        if (!$archived_stmt->execute()) {
            throw new Exception("Error archiving product: " . $archived_stmt->error);
        }
    
        
    // Function to rearrange IDs in the products table
    function rearrangeProductIDs($conn) {
    // Fetch all current product IDs, ordered by ID
    $products_query = "SELECT id FROM products ORDER BY id";
    $products_result = $conn->query($products_query);
    $new_id = 1;

    while ($product = $products_result->fetch_assoc()) {
        $update_query = "UPDATE products SET id = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('ii', $new_id, $product['id']);
        $update_stmt->execute();
        $new_id++;
    }

    // Reset auto-increment to the next available ID
    $conn->query("ALTER TABLE products AUTO_INCREMENT = $new_id");
}

        // Delete the stock adjustment records for this product
        $delete_adjustments_sql = "DELETE FROM stock_adjustment WHERE product_id = ?";
        $delete_adjustments_stmt = $conn->prepare($delete_adjustments_sql);
        $delete_adjustments_stmt->bind_param('i', $id_to_archive);
        
        if (!$delete_adjustments_stmt->execute()) {
            throw new Exception("Error deleting stock adjustments: " . $delete_adjustments_stmt->error);
        }

        // Delete the record from products table
        $delete_sql = "DELETE FROM products WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('i', $id_to_archive);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Error deleting product: " . $delete_stmt->error);
        }
        
        rearrangeProductIDs($conn);


        // Log the archiving action
        $log_sql = "INSERT INTO product_logs (action, product_barcode, performed_by) VALUES ('Archived', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param('ss', $product['Barcode'], $archived_by);
        
        if (!$log_stmt->execute()) {
            throw new Exception("Error logging archive action: " . $log_stmt->error);
        }

        // Commit transaction
        $conn->commit();
        
        $_SESSION['success_message'] = "Product archived successfully.";
        header('Location: Product.php');
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error in Product.php (archiving): " . $e->getMessage());
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header('Location: Product.php');
        exit();
    }
}

// Handle restore request
if (isset($_GET['restoreid'])) {
    $id_to_restore = $_GET['restoreid'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch product details from the archived_products table without ID
        $archived_product_query = "SELECT Barcode, Description, Brand, Category, Price, Quantity, Vendor FROM archived_products WHERE id = ?";
        $stmt = $conn->prepare($archived_product_query);
        $stmt->bind_param('i', $id_to_restore);
        $stmt->execute();
        $result = $stmt->get_result();
        $archived_product = $result->fetch_assoc();

        if (!$archived_product) {
            throw new Exception("Archived product not found for restoring.");
        }

        // Insert the product back into products table with a new ID
        $restore_product_sql = "INSERT INTO products (Barcode, Description, Brand, Category, Price, cost_price, Quantity, Vendor) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $restore_stmt = $conn->prepare($restore_product_sql);
        $restore_stmt->bind_param(
            'ssssdis',  // Update this to match the new parameter list (now including cost_price)
            $archived_product['Barcode'],
            $archived_product['Description'],
            $archived_product['Brand'],
            $archived_product['Category'],
            $archived_product['Price'],
            $archived_product['cost_price'],  // Add cost_price here
            $archived_product['Quantity'],
            $archived_product['Vendor']
        );


        if (!$restore_stmt->execute()) {
            throw new Exception("Error restoring product: " . $restore_stmt->error);
        }

        // Delete the product from archived_products after successful restore
        $delete_archived_sql = "DELETE FROM archived_products WHERE id = ?";
        $delete_archived_stmt = $conn->prepare($delete_archived_sql);
        $delete_archived_stmt->bind_param('i', $id_to_restore);

        if (!$delete_archived_stmt->execute()) {
            throw new Exception("Error deleting archived product: " . $delete_archived_stmt->error);
        }

        // Commit transaction
        $conn->commit();

        $_SESSION['success_message'] = "Product restored successfully.";
        header('Location: archived_products.php'); // Adjust the redirect location as needed
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error in restoring product: " . $e->getMessage());
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header('Location: archived_products.php');
        exit();
    }
}
?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link rel="stylesheet" type="text/css" href="CSS/Product.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon"> <!-- Adjust path if necessary -->
    <script type="text/javascript" src="JAVASCRIPT/Product.js" defer></script>
</head>
<body>
<nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i> Dashboard</a></li>
        <li>
            <a href="Product.php" class="selected"><i class='fas fa-archive' style='font-size:30px'></i> Product
                <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i> <!-- Submenu symbol added -->
            </a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i> Vendor</a></li>
        <li><a href="PurchaseOrder.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i> Purchase Order</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i> Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i> User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px ; margin-right: 10px;'></i>Logout</a></li>
    </ul>
</nav>

<header>
    <h2 class="ProductHeader">
        <div class="flex-container">
            <span class="header-title">Product List</span>
            <div class="filter-tools">
                <select id="brand-filter">
                    <option value="all">All Brands</option>
                    <?php while ($brand = mysqli_fetch_assoc($brand_result)): ?>
                        <option value="<?= htmlspecialchars($brand['Brand']) ?>">
                            <?= htmlspecialchars($brand['Brand']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select id="category-filter">
                    <option value="all">All Categories</option>
                    <?php while ($category = mysqli_fetch_assoc($category_result)): ?>
                        <option value="<?= htmlspecialchars($category['Category']) ?>">
                            <?= htmlspecialchars($category['Category']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select id="vendor-filter">
                    <option value="all">All Vendors</option>
                    <?php while ($vendor = mysqli_fetch_assoc($vendor_result)): ?>
                        <option value="<?= htmlspecialchars($vendor['vendor']) ?>">
                            <?= htmlspecialchars($vendor['vendor']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input id="search-input" type="text" placeholder="Search...">
                <button id="add-product-button"><i class='fas fa-plus'></i></button>
            </div>
        </div>
    </h2>
</header>



    <div class="content">
    <div class="table-container">
        <table class="table" id="product-table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Barcode</th>
                <th scope="col">Description</th>
                <th scope="col">Brand</th>
                <th scope="col">Category</th>
                <th scope="col">Price</th>
                <th scope="col">Base Price</th>
                <th scope="col">Quantity</th>
                <th scope="col">Vendor</th>
                <th scope="col">Operation</th>
            </tr>
        </thead>
        <tbody>
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['Barcode']) ?></td>
                <td><?= htmlspecialchars($row['Description']) ?></td>
                <td><?= htmlspecialchars($row['Brand']) ?></td>
                <td><?= htmlspecialchars($row['Category']) ?></td>
                <td><?= htmlspecialchars($row['Price']) ?></td>
                <td><?= htmlspecialchars($row['cost_price']) ?></td>
                <td><?= htmlspecialchars($row['Quantity']) ?></td>
                <td><?= htmlspecialchars($row['Vendor'] ?? 'Unknown') ?></td>
                <td>
                    <button class="button update-button" 
                            data-id="<?= htmlspecialchars($row['id']) ?>" 
                            data-barcode="<?= htmlspecialchars($row['Barcode']) ?>" 
                            data-description="<?= htmlspecialchars($row['Description']) ?>" 
                            data-brand="<?= htmlspecialchars($row['Brand']) ?>" 
                            data-category="<?= htmlspecialchars($row['Category']) ?>" 
                            data-price="<?= htmlspecialchars($row['Price']) ?>" 
                            data-cost-price="<?= htmlspecialchars($row['cost_price']) ?>" 
                            data-quantity="<?= htmlspecialchars($row['Quantity']) ?>" 
                            data-vendor-id="<?= htmlspecialchars($row['vendor_id'] ?? '') ?>"> 
                        Update
                    </button>
                    <button class="button"><a href="?archiveid=<?= htmlspecialchars($row['id']) ?>" class="text-light">Archive</a></button>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="10" style="text-align: center; font-size: 18px; color: #555;">
                No products found.
            </td>
        </tr>
    <?php endif; ?>
</tbody>

        </table>
    </div>

<!-- Add Product Modal -->
<div id="product-modal" class="modals">
    <div class="modal-contents">
        <span class="close-button">&times;</span>
        <h2>Add New Product</h2>
        <form id="product-form" action="add_product.php" method="post">
            <label for="barcode">Barcode:</label>
            <input type="text" id="barcode" name="barcode" value="<?= $next_barcode ?>" readonly>
            <label for="generatedBarcode">Generated Barcode:</label>
            <input type="text" id="generatedBarcode" name="generatedBarcode" readonly> <!-- Generated barcode in input -->

            <label for="description">Description:</label>
            <input type="text" id="description" name="description" required>
        <form id="product-form" action="add_product.php" method="post">

        <label for="vendor">Vendor:</label>
            <select id="vendor" name="vendor" required>
                <option value="" disabled selected>Select Vendor</option>
                <?php mysqli_data_seek($vendor_result, 0); ?>
                <?php while ($vendor = mysqli_fetch_assoc($vendor_result)): ?>
                    <option value="<?= htmlspecialchars($vendor['id']) ?>">
                        <?= htmlspecialchars($vendor['vendor']) ?> <!-- Display the vendor name -->
                    </option>
                <?php endwhile; ?>
            </select>


            <label for="brand">Brand:</label>
            <select id="brand" name="brand" required>
                <option value="" disabled selected>Select Brand</option>
                <?php mysqli_data_seek($brand_result, 0); ?>
                <?php while ($brand = mysqli_fetch_assoc($brand_result)): ?>
                    <option value="<?= htmlspecialchars($brand['Brand']) ?>">
                        <?= htmlspecialchars($brand['Brand']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="" disabled selected>Select Category</option>
                <?php mysqli_data_seek($category_result, 0); ?>
                <?php while ($category = mysqli_fetch_assoc($category_result)): ?>
                    <option value="<?= htmlspecialchars($category['Category']) ?>">
                        <?= htmlspecialchars($category['Category']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" required>

            <label for="cost_price">Cost Price:</label>
            <input type="number" id="cost_price" name="cost_price" step="0.01" required>

            <button type="submit">Add Product</button>
        </form>
    </div>
</div>

<!-- Update Product Modal -->
<div id="update-product-modal" class="modals">
    <div class="modal-contents">
        <span class="close-button">&times;</span>
        <h2>Update Product</h2>
        <form id="update-product-form" action="update_product.php" method="post">
            <input type="hidden" id="update-product-id" name="product-id">

            <label for="update-barcode">Barcode:</label>
            <input type="text" id="update-barcode" name="barcode" readonly>

            <label for="update-generated-barcode">Generated Barcode:</label>
            <input type="text" id="update-generatedBarcode" name="generatedBarcode" readonly>

            <label for="update-description">Description:</label>
            <input type="text" id="update-description" name="description" required>

            <label for="update-vendor">Vendor:</label>
            <select id="update-vendor" name="vendor" required>
                <option value="" disabled selected>Select Vendor</option>
                <?php mysqli_data_seek($vendor_result, 0); ?>
                <?php while ($vendor = mysqli_fetch_assoc($vendor_result)): ?>
                    <option value="<?= htmlspecialchars($vendor['id']) ?>">
                        <?= htmlspecialchars($vendor['vendor']) ?>
                    </option>
                <?php endwhile; ?>
            </select>


            <label for="update-brand">Brand:</label>
            <select id="update-brand" name="brand" required>
                <option value="" disabled selected>Select Brand</option>
                <?php mysqli_data_seek($brand_result_update, 0); ?>
                <?php while ($brand = mysqli_fetch_assoc($brand_result_update)): ?>
                    <option value="<?= htmlspecialchars($brand['Brand']) ?>">
                        <?= htmlspecialchars($brand['Brand']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="update-category">Category:</label>
            <select id="update-category" name="category" required>
                <option value="" disabled selected>Select Category</option>
                <?php mysqli_data_seek($category_result_update, 0); ?>
                <?php while ($category = mysqli_fetch_assoc($category_result_update)): ?>
                    <option value="<?= htmlspecialchars($category['Category']) ?>">
                        <?= htmlspecialchars($category['Category']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="update-price">Price:</label>
            <input type="number" id="update-price" name="price" step="0.01" required>

            <label for="update-cost-price">Cost Price:</label>
            <input type="number" id="update-cost-price" name="cost_price" step="0.01" required>

            <button type="submit">Update Product</button>
        </form>
    </div>
</div>



    <style>
        /* Modal styles */
        .modal {
          display: none; /* Hidden by default */
          position: fixed; /* Stay in place */
          z-index: 1000; /* Sit on top */
          left: 0;
          top: 0;
          width: 100%; /* Full width */
          height: 100%; /* Full height */
          overflow: auto; /* Enable scroll if needed */
          background-color: rgba(0, 0, 0, 0.5); /* Black with opacity */
      }

      /* Modal content */
      .modal-content {
          background-color: #fefefe; /* White background */
          margin: 15% auto; /* 15% from the top and centered */
          padding: 20px;
          border: 1px solid #888; /* Gray border */
          width: 375px; /* Could be more or less, depending on screen size */
          border-radius: 8px; /* Rounded corners */
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow effect */
      }

      /* Close button */
      .close {
          color: #aaa; /* Light gray */
          float: right; /* Position to the right */
          font-size: 28px; /* Larger font size */
          font-weight: bold; /* Bold text */
      }

      .conf{
        font-size: 24px;
        font-weight: bolder;
      }

      .par{
        font-size: 18px;
      }

      .close:hover,
      .close:focus {
          color: black; /* Change color on hover */
          text-decoration: none; /* No underline */
          cursor: pointer; /* Pointer cursor */
      }

      /* Button styles */
      .confirm-btn,
      .cancel-btn {
          background-color: #005b99; /* Blue background */
          border: none; /* No borders */
          color: white; /* White text */
          padding: 10px 20px; /* Some padding */
          text-align: center; /* Centered text */
          text-decoration: none; /* No underline */
          display: inline-block; /* Align buttons */
          font-size: 16px; /* Larger font */
          margin: 10px 2px; /* Margins around buttons */
          margin-left: 55px;
          margin-top: 20px;
          cursor: pointer; /* Pointer cursor */
          border-radius: 5px; /* Rounded corners */
          transition: background-color 0.3s; /* Smooth transition */
      }

      .cancel-btn {
          background-color: red; /* Gray background for cancel */
      }

      .cancel-btn:hover {
          background-color: maroon; /* Darker gray on hover */
      }

      .confirmLogout:hover{
        background-color: lightblue; /* Darker gray on hover */
      }
   
    </style>
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLogoutModal()">&times;</span>
            <h2 class="conf">Logout Confirmation</h2>
            <p class="par">Are you sure you want to log out?</p>
            <button id="confirmLogout" class="confirm-btn">Logout</button>
            <button class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>
        </div>
    </div>

    <script>
        // Function to show the update product modal
        function openUpdateProductModal() {
            document.getElementById("update-product-modal").style.display = "block"; // Show the modal
        }

        // Function to close the update product modal
        function closeUpdateProductModal() {
            document.getElementById("update-product-modal").style.display = "none"; // Hide the modal
        }

        // Function to show the logout modal
        function confirmLogout() {
            document.getElementById("logoutModal").style.display = "block"; // Show the modal
            return false; // Prevent the default link action
        }

        // Function to close the logout modal
        function closeLogoutModal() {
            document.getElementById("logoutModal").style.display = "none"; // Hide the modal
        }

        // Close the modal if the user clicks anywhere outside of it
        window.onclick = function(event) {
            var updateModal = document.getElementById("update-product-modal");
            var logoutModal = document.getElementById("logoutModal");
            if (event.target == updateModal) {
                closeUpdateProductModal();
            } else if (event.target == logoutModal) {
                closeLogoutModal();
            }
        };

        // Confirm logout action
        document.getElementById("confirmLogout").onclick = function() {
            window.location.href = "Login.php"; // Redirect to the login page or handle logout
        };
    </script>
<script>
  const nextBarcode = <?= $next_barcode ?>;
</script>

</body>
</html>
<?php
$conn->close();
?>