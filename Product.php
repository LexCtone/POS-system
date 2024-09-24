<?php
include 'connect.php'; // Ensure this path is correct and the file exists

// Fetch brands for dropdown
$brand_query = "SELECT * FROM brands";
$brand_result = mysqli_query($conn, $brand_query);
$brand_result_update = mysqli_query($conn, $brand_query);

// Fetch categories for dropdown
$category_query = "SELECT * FROM categories";
$category_result = mysqli_query($conn, $category_query);
$category_result_update = mysqli_query($conn, $category_query);

// Handle form submission for adding a new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];
    $description = $_POST['description'];
    $brand = $_POST['brand'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    // Validate input
    if (!empty($barcode) && !empty($description) && !empty($brand) && !empty($category) && is_numeric($price) && is_numeric($quantity)) {
        // Insert the new product into the database
        $stmt = $conn->prepare("INSERT INTO products (Barcode, Description, Brand, Category, Price, Quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssi', $barcode, $description, $brand, $category, $price, $quantity);

        if ($stmt->execute()) {
            echo 'Product added successfully';
        } else {
            echo 'Failed to add product: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'All fields are required and price/quantity must be numeric';
    }
}

// Handle delete request
if (isset($_GET['deleteid'])) {
    $id_to_delete = $_GET['deleteid'];

    // Delete the record
    $delete_sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param('i', $id_to_delete);
    $stmt->execute();
    $stmt->close();

    // Rearrange IDs after deletion
    $reset_sql = "SET @num := 0;";
    mysqli_query($conn, $reset_sql);

    $update_sql = "UPDATE products SET id = @num := (@num + 1);";
    mysqli_query($conn, $update_sql);

    // Reset AUTO_INCREMENT to the next available ID
    $max_id_sql = "SELECT MAX(id) FROM products";
    $max_id_result = mysqli_query($conn, $max_id_sql);
    $max_id_row = mysqli_fetch_array($max_id_result);
    $max_id = $max_id_row[0] + 1;

    $alter_sql = "ALTER TABLE products AUTO_INCREMENT = $max_id";
    mysqli_query($conn, $alter_sql);

    // Redirect back to the product list
    header('Location: Product.php');
    exit();
}

// Fetch and display products
$sql = "SELECT * FROM products";
$result = mysqli_query($conn, $sql);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product List</title>
  <link rel="stylesheet" type="text/css" href="CSS/Product.css">
  <script type="text/javascript" src="JAVASCRIPT/Product.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script>
      document.addEventListener('DOMContentLoaded', function() {
          // Search functionality
          const searchInput = document.getElementById('search-input');
          const productTable = document.getElementById('product-table');

          searchInput.addEventListener('input', function() {
              const searchTerm = this.value.toLowerCase();
              const rows = productTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

              Array.from(rows).forEach(row => {
                  const barcodeCell = row.cells[1].textContent.toLowerCase();
                  row.style.display = barcodeCell.includes(searchTerm) ? '' : 'none';
              });
          });
      });
  </script>
</head>
<body>
    <nav class="sidebar">
        <header>
            <img src="profile.png" alt="profile"/>
            <br>ADMINISTRATOR
        </header>
        <ul>
            <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Home</a></li>
            <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product</a></li>
            <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
            <li><a href="StockEntry.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
            <li><a href="Brand.php"><i class='fa-solid fa-tag' style='font-size:30px'></i>Brand</a></li>
            <li><a href="Category.php"><i class='fa-solid fa-layer-group' style='font-size:30px'></i>Category</a></li>
            <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
            <li><a href="SalesHistory.php"><i class='fa-solid fa-clock-rotate-left' style='font-size:30px'></i>Sales History</a></li>
            <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
            <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
        </ul>
    </nav>    
    <header>    
        <h2 class="ProductHeader">Product List  
            <input id="search-input" type="text" placeholder="Search...">
            <button id="add-product-button"><i class='fas fa-plus'></i></button>
        </h2>    
    </header>
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
                    <th scope="col">Quantity</th>
                    <th scope="col">Operation</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <th scope="row"><?= $row['id'] ?></th>
                            <td><?= htmlspecialchars($row['Barcode']) ?></td>
                            <td><?= htmlspecialchars($row['Description']) ?></td>
                            <td><?= htmlspecialchars($row['Brand']) ?></td>
                            <td><?= htmlspecialchars($row['Category']) ?></td>
                            <td><?= htmlspecialchars($row['Price']) ?></td>
                            <td><?= htmlspecialchars($row['Quantity']) ?></td>
                            <td>
                                <button class="button update-button" 
                                        data-id="<?= $row['id'] ?>" 
                                        data-barcode="<?= htmlspecialchars($row['Barcode']) ?>" 
                                        data-description="<?= htmlspecialchars($row['Description']) ?>"
                                        data-brand="<?= htmlspecialchars($row['Brand']) ?>"
                                        data-category="<?= htmlspecialchars($row['Category']) ?>"
                                        data-price="<?= htmlspecialchars($row['Price']) ?>"
                                        data-quantity="<?= htmlspecialchars($row['Quantity']) ?>">
                                    Update
                                </button>
                                <button class="button"><a href="?deleteid=<?= $row['id'] ?>" class="text-light">Delete</a></button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<!-- Add Product Modal -->
<div id="product-modal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Add New Product</h2>
        <form id="product-form" action="add_product.php" method="post">
            <label for="barcode">Barcode:</label>
            <input type="text" id="barcode" name="barcode" required>
            <label for="description">Description:</label>
            <input type="text" id="description" name="description" required>
            <label for="brand">Brand:</label>
            <select id="brand" name="brand" required>
                <option value="" disabled selected>Select Brand</option>
                <?php while ($brand = mysqli_fetch_assoc($brand_result)): ?>
                    <option value="<?= htmlspecialchars($brand['Brand']) ?>">
                        <?= htmlspecialchars($brand['Brand']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="" disabled selected>Select Category</option>
                <?php while ($category = mysqli_fetch_assoc($category_result)): ?>
                    <option value="<?= htmlspecialchars($category['Category']) ?>">
                        <?= htmlspecialchars($category['Category']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" required>
            <button type="submit">Add Product</button>
        </form>
    </div>
</div>
<!-- Update Product Modal -->
<div id="update-product-modal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Update Product</h2>
        <form id="update-product-form" action="update_product.php" method="post">
            <input type="hidden" id="update-product-id" name="product-id">
            <label for="update-barcode">Barcode:</label>
            <input type="text" id="update-barcode" name="barcode" required>
            <label for="update-description">Description:</label>
            <input type="text" id="update-description" name="description" required>
            <label for="update-brand">Brand:</label>
            <select id="update-brand" name="brand" required>
                <option value="" disabled selected>Select Brand</option>
                <?php while ($brand = mysqli_fetch_assoc($brand_result_update)): ?>
                    <option value="<?= htmlspecialchars($brand['Brand']) ?>">
                        <?= htmlspecialchars($brand['Brand']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="update-category">Category:</label>
            <select id="update-category" name="category" required>
                <option value="" disabled selected>Select Category</option>
                <?php while ($category = mysqli_fetch_assoc($category_result_update)): ?>
                    <option value="<?= htmlspecialchars($category['Category']) ?>">
                        <?= htmlspecialchars($category['Category']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="update-price">Price:</label>
            <input type="text" id="update-price" name="price" required>
            <div id="update-form-feedback" class="form-feedback"></div>
            <div id="update-loading" class="loading-indicator" style="display: none;">Updating...</div>
            <button type="submit">Update Product</button>
        </form>
    </div>
</div>
<script>
    // Handle barcode scanning
    const barcodeInput = document.getElementById('barcode');
    barcodeInput.addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Prevent form submission
            const barcode = this.value.trim();

            if (barcode) {
                // Perform an AJAX request to fetch product details based on the barcode
                fetch(`get_product.php?barcode=${encodeURIComponent(barcode)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data) {
                            document.getElementById('description').value = data.Description;
                            document.getElementById('brand').value = data.Brand;
                            document.getElementById('category').value = data.Category;
                            document.getElementById('price').value = data.Price;
                            document.getElementById('quantity').value = data.Quantity;
                        } else {
                            alert('Product not found. You can add it as a new product.');
                            document.getElementById('description').value = '';
                            document.getElementById('brand').value = '';
                            document.getElementById('category').value = '';
                            document.getElementById('price').value = '';
                            document.getElementById('quantity').value = '';
                        }
                    })
                    .catch(error => console.error('Error fetching product details:', error));
            }
        }
    });
</script>
</body>
</html>
