<?php
session_start();
include 'connect.php';

// Fetch vendors from the database
$vendorQuery = "SELECT id, vendor FROM vendor ORDER BY vendor";
$vendorResult = $conn->query($vendorQuery);

// Fetch all products for initialization
$productQuery = "SELECT id, Barcode, Description, Brand, Category, Price, cost_price, vendor_id 
                 FROM products ORDER BY Description";
$productResult = $conn->query($productQuery);

// Store products in an array for JavaScript use
$products = [];
while ($row = $productResult->fetch_assoc()) {
    $products[] = $row;
}

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

// Retrieve GET parameters with defaults
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
$vendor_id = isset($_GET['vendor_id']) ? $_GET['vendor_id'] : null; // Default to null
$product_name = isset($_GET['product_name']) ? $_GET['product_name'] : '';
$cost_price = isset($_GET['cost_price']) ? $_GET['cost_price'] : '0.00'; // Default to 0.00
$brand = isset($_GET['brand']) ? $_GET['brand'] : ''; // Default to "Unknown"
$category = isset($_GET['category']) ? $_GET['category'] : ''; // Default to "Unknown"

// Fetch vendor name based on vendor_id
$vendor_name = 'Unknown Vendor';
if ($vendor_id) {
    $vendor_query = "SELECT vendor FROM vendor WHERE id = ?";
    $vendor_stmt = $conn->prepare($vendor_query);
    $vendor_stmt->bind_param("i", $vendor_id);
    $vendor_stmt->execute();
    $vendor_result = $vendor_stmt->get_result();
    if ($vendor_result->num_rows > 0) {
        $vendor_name = $vendor_result->fetch_assoc()['vendor'];
    }
    $vendor_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders</title>
    <link rel="stylesheet" href="CSS/PurchaseOrder.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<header>
    <h2 class="PageHeader">
        <div class="flex-container">
            <span class="header-title">Purchase Orders</span>
        </div>
    </h2>
</header>

    <nav>
<nav class="sidebar">
    <header>
        <img src="profile.png" alt="profile"/>
        <br><?php echo htmlspecialchars($admin_name); ?>
    </header>
    <ul>
        <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Dashboard</a></li>
        <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product
        <i class="fa-solid fa-caret-down" style="font-size: 18px; margin-left: 5px;"></i>
        </a>
            <ul class="submenu">
                <li><a href="Brand.php"><i class='fa-solid fa-tag'></i> Brand</a></li>
                <li><a href="Category.php"><i class='fa-solid fa-layer-group'></i> Category</a></li>
            </ul>
        </li>
        <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
        <li><a href="PurchaseOrder.php" class="selected"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Purchase Order</a></li>
        <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
        <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
        <li><a href="Login.php" onclick="return confirmLogout();" style="cursor: pointer;"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
</nav> 
</nav>
    <div class="button-container">
        <button onclick="location.href='PurchaseOrder.php'" class="selected">Purchase Order</button>
        <button onclick="location.href='StockEntry.php'">Orders</button>
        <button onclick="location.href='StockinHistory.php'">Stock in History</button>
        <button onclick="location.href='StockAdjustment.php'">Stock Adjustments</button>
    </div>

    <div class="containers">
    <form id="orderForm">
        <div class="form-container">
            <!-- PO Number Field -->
            <div class="form-group">
                <label for="poNumber">PO Number</label>
                <input type="text" class="form-control" id="poNumber" value="PO-<?php echo date('Ymd-'); ?><?php echo rand(1000,9999); ?>" readonly>
            </div>

            <!-- Order Date Field -->
            <div class="form-group">
                <label for="orderDate">Order Date</label>
                <input type="date" class="form-control" id="orderDate" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <!-- Vendor Selection Field -->
            <div class="form-group">
                <label for="vendor">Vendor</label>
                <select class="form-control" id="vendor" required>
                    <option value="">Select Vendor</option>
                    <?php while ($vendor = $vendorResult->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($vendor['id']); ?>"
                            <?php if($vendor['id'] == $vendor_id) echo "selected"; ?>>
                            <?php echo htmlspecialchars($vendor['vendor']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Expected Delivery Date Field -->
            <div class="form-group">
                <label for="deliveryDate">Expected Delivery Date</label>
                <input type="date" class="form-control" id="deliveryDate" required>
            </div>

        </div>

        <!-- Items Table -->
        <div class="table-container">
            <table id="itemsTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Cost Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select class="form-control product-select" required>
                                <option value="">Select Product</option>
                                <?php 
                                $productResult->data_seek(0);
                                while ($product = $productResult->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                            <?php if($product['id'] == $product_id) echo "selected"; ?>
                                            data-cost="<?php echo htmlspecialchars($product['cost_price']); ?>"
                                            data-brand="<?php echo htmlspecialchars($product['Brand']); ?>"
                                            data-category="<?php echo htmlspecialchars($product['Category']); ?>">
                                        <?php echo htmlspecialchars($product['Description']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <td class="brand"><?php echo $brand; ?></td>
                        <td class="category"><?php echo $category; ?></td>
                        <td><input type="number" class="form-control cost-price" value="<?php echo $cost_price; ?>" readonly required></td>
                        <td><input type="number" class="form-control quantity" min="1" required></td>
                        <td class="total">0.00</td>
                        <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div id="successAlert" style="display: none;" class="alert alert-success" role="alert">
  <p>Purchase Order created successfully!</p>
  </button>
</div>
        <!-- Grand Total Section -->
        <div class="total-section">
            <strong>Grand Total: ₱</strong>
            <span id="grandTotal">0.00</span>
        </div>
        
        <button type="button" class="btn" id="addRow" style="margin-top: 10px;">Add Item</button>

        <!-- Submit Button -->
        <div class="submit-container">
            <button type="submit" class="btn" style="margin-top: 20px;">Create Purchase Order</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // DOM elements
    const vendorSelect = document.getElementById('vendor');
    const tableBody = document.querySelector('#itemsTable tbody');
    const addRowButton = document.getElementById('addRow');
    const orderForm = document.getElementById('orderForm');
    const grandTotalElement = document.getElementById('grandTotal');

    // Store all products fetched from PHP
    const allProducts = <?php echo json_encode($products); ?>;

    // Update product dropdown based on selected vendor
    vendorSelect.addEventListener('change', function () {
        const vendorId = this.value;

        // Clear all product dropdowns in the table
        tableBody.querySelectorAll('.product-select').forEach(productSelect => {
            productSelect.innerHTML = '<option value="">Select Product</option>';
        });

        // Filter products based on vendor
        const filteredProducts = allProducts.filter(product => product.vendor_id == vendorId);
        if (filteredProducts.length === 0) {
            alert('No products available for the selected vendor.');
        }

        // Populate product dropdowns in the table
        tableBody.querySelectorAll('.product-select').forEach(productSelect => {
            filteredProducts.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = product.Description;
                option.dataset.cost = product.cost_price;
                option.dataset.brand = product.Brand;
                option.dataset.category = product.Category;
                productSelect.appendChild(option);
            });
        });
    });

    // Attach events to rows
    function attachRowEvents(row) {
        const productSelect = row.querySelector('.product-select');
        const costInput = row.querySelector('.cost-price');
        const quantityInput = row.querySelector('.quantity');
        const totalCell = row.querySelector('.total');
        const brandCell = row.querySelector('.brand');
        const categoryCell = row.querySelector('.category');
        const removeButton = row.querySelector('.remove-row');

        // Update fields when product is selected
        productSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset) {
                costInput.value = selectedOption.dataset.cost || '';
                brandCell.textContent = selectedOption.dataset.brand || '';
                categoryCell.textContent = selectedOption.dataset.category || '';
                updateRowTotal(); // Update total when product changes
            }
        });

        // Update total when cost or quantity changes
        function updateRowTotal() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const cost = parseFloat(costInput.value) || 0;
            totalCell.textContent = (quantity * cost).toFixed(2);
            updateGrandTotal();
        }

        costInput.addEventListener('input', updateRowTotal);
        quantityInput.addEventListener('input', updateRowTotal);

        // Remove row
        removeButton.addEventListener('click', function () {
            if (tableBody.querySelectorAll('tr').length > 1) {
                row.remove();
                updateGrandTotal();
            }
        });
    }

    // Add new row
    addRowButton.addEventListener('click', function () {
        const newRow = tableBody.rows[0].cloneNode(true);

        // Reset values in the new row
        newRow.querySelectorAll('input, select').forEach(input => {
            input.value = '';
        });
        newRow.querySelector('.total').textContent = '0.00';
        newRow.querySelector('.brand').textContent = '';
        newRow.querySelector('.category').textContent = '';

        tableBody.appendChild(newRow);
        attachRowEvents(newRow); // Attach events to the new row
    });

    // Update grand total
    function updateGrandTotal() {
        const totals = Array.from(document.querySelectorAll('.total')).map(cell =>
            parseFloat(cell.textContent) || 0
        );
        const grandTotal = totals.reduce((sum, value) => sum + value, 0);
        grandTotalElement.textContent = grandTotal.toFixed(2);
    }

    // Attach events to the initial row
    attachRowEvents(tableBody.querySelector('tr'));

    // Handle form submission
    orderForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = {
            poNumber: document.getElementById('poNumber').value,
            orderDate: document.getElementById('orderDate').value,
            vendor: vendorSelect.value,
            deliveryDate: document.getElementById('deliveryDate').value,
            items: Array.from(tableBody.querySelectorAll('tr')).map(row => ({
                product_id: row.querySelector('.product-select').value,
                quantity: parseInt(row.querySelector('.quantity').value),
                cost_price: parseFloat(row.querySelector('.cost-price').value),
                total: parseFloat(row.querySelector('.total').textContent),
            })),
        };

        fetch('save_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Purchase Order created successfully!');
                    orderForm.reset();
                    tableBody.querySelectorAll('tr:not(:first-child)').forEach(row => row.remove());
                    updateGrandTotal();
                } else {
                    alert('Error creating Purchase Order: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating the Purchase Order.');
            });
    });
});

</script>

    
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
        margin-left: 20px
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
          margin-left: 63px;
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
        // Function to show the logout modal
        function confirmLogout() {
            document.getElementById("logoutModal").style.display = "block"; // Show the modal
            return false; // Prevent the default link action
        }

        // Function to close the logout modal
        function closeLogoutModal() {
            document.getElementById("logoutModal").style.display = "none"; // Hide the modal
        }

        // Confirm logout action
        document.getElementById("confirmLogout").onclick = function() {
            window.location.href = "Login.php"; // Redirect to the login page or handle logout
        };

        // Close the modal if the user clicks anywhere outside of it
        window.onclick = function(event) {
            var logoutModal = document.getElementById("logoutModal");
            if (event.target == logoutModal) {
                closeLogoutModal();
            }
        };
    </script>
</body>
</html>
<?php
$conn->close();
?>