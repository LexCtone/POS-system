<?php
session_start();
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
// Handle form submission for adding a new vendor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vendor-name'])) {
    $vendorName = $_POST['vendor-name'];
    $contactPerson = $_POST['contact-person'];
    $contactNumber = $_POST['contact-number'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $fax = $_POST['fax'];

    // Validate input
    if (!empty($vendorName) && !empty($contactPerson) && !empty($contactNumber) && !empty($address) && !empty($email) && !empty($fax)) {
        // Insert the new vendor into the database
        $stmt = $conn->prepare("INSERT INTO vendor (vendor, contact, telephone, address, email, fax) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        $stmt->bind_param('ssssss', $vendorName, $contactzPerson, $contactNumber, $address, $email, $fax);

        if ($stmt->execute()) {
            // Fetch the newly added vendor data
            $VendorId = $conn->insert_id;
            $stmt = $conn->prepare("SELECT * FROM vendor WHERE id = ?");
            $stmt->bind_param('i', $VendorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $VendorId = $result->fetch_assoc();

            echo json_encode($Vendor);
        } else {
            echo json_encode(['error' => 'Failed to add vendor: ' . htmlspecialchars($stmt->error)]);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'All fields are required']);
    }

    $conn->close();
    exit();
}

// Handle delete request
if (isset($_GET['deleteid'])) {
    $id_to_delete = $_GET['deleteid'];

    // Delete the record
    $delete_sql = "DELETE FROM vendor WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param('i', $id_to_delete);
    $stmt->execute();
    $stmt->close();

    // Rearrange IDs after deletion
    $reset_sql = "SET @num := 0;";
    mysqli_query($conn, $reset_sql);

    $update_sql = "UPDATE vendor SET id = @num := (@num + 1);";
    mysqli_query($conn, $update_sql);

    // Reset AUTO_INCREMENT to the next available ID
    $max_id_sql = "SELECT MAX(id) FROM vendor";
    $max_id_result = mysqli_query($conn, $max_id_sql);
    $max_id_row = mysqli_fetch_array($max_id_result);
    $max_id = $max_id_row[0] + 1;

    $alter_sql = "ALTER TABLE vendor AUTO_INCREMENT = $max_id";
    mysqli_query($conn, $alter_sql);

    // Redirect to clear GET data
    header('Location: Vendor.php');
    exit();
}

// Fetch and display vendors
$sql = "SELECT * FROM vendor";
$result = mysqli_query($conn, $sql);
if ($result === false) {
    die('Query failed: ' . htmlspecialchars($con->error));
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendor List</title>
  <link rel="stylesheet" type="text/css" href="CSS\Vendor.css">
  <script type="text/javascript" src="JAVASCRIPT\Vendor.js" defer></script>
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
        <h2 class="VendorHeader">Vendor List  
            <input id="search-input" type="text" placeholder="Search...">
            <button id="search-button"><i class="fas fa-search"></i></button>
            <button id="add-vendor-button"><i class='fas fa-plus'></i></button>
        </h2>    
    </header>
    <div class="table-container">
        <table class="table" id="vendor-table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Vendor Name</th>
                    <th scope="col">Contact Person</th>
                    <th scope="col">Contact Number</th>
                    <th scope="col">Address</th>
                    <th scope="col">Email</th>
                    <th scope="col">Fax</th>
                    <th scope="col">Operation</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <th scope="row"><?= htmlspecialchars($row['id'])  ?></th>
                            <td><?= htmlspecialchars($row['vendor']) ?></td>
                            <td><?= htmlspecialchars($row['contact']) ?></td>
                            <td><?= htmlspecialchars($row['telephone']) ?></td>
                            <td><?= htmlspecialchars($row['address']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['fax']) ?></td>
                            <td>
                                <button class="button update-button" 
                                        data-id="<?= htmlspecialchars($row['id']) ?>" 
                                        data-vendor-name="<?= htmlspecialchars($row['vendor']) ?>" 
                                        data-contact-person="<?= htmlspecialchars($row['contact']) ?>"
                                        data-contact-number="<?= htmlspecialchars($row['telephone']) ?>"
                                        data-address="<?= htmlspecialchars($row['address']) ?>"
                                        data-email="<?= htmlspecialchars($row['email']) ?>"
                                        data-fax="<?= htmlspecialchars($row['fax']) ?>">
                                    Update
                                </button>
                                <button class="button"><a href="?deleteid=<?= htmlspecialchars($row['id']) ?>" class="text-light">Delete</a></button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Add Vendor Modal -->
    <div id="add-vendor-modal"  class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
                <form id="add-vendor-form" action="Vendor.php" method="post">
                <label for="vendor-name">Vendor Name:</label>
                <input type="text" id="vendor-name" name="vendor-name" required>
                <label for="contact-person">Contact Person:</label>
                <input type="text" id="contact-person" name="contact-person" required>
                <label for="contact-number">Contact Number:</label>
                <input type="text" id="contact-number" name="contact-number" required>
                <label for="address">Address:</label>
                <input type="text" id="address" name="address">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
                <label for="fax">Fax:</label>
                <input type="text" id="fax" name="fax">
                <button type="submit">Add Vendor</button>
            </form>
        </div>
    </div>
    <!-- Update Vendor Modal -->
    <div id="update-vendor-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Update Vendor</h2>
            <form id="update-vendor-form" action="update_vendor.php" method="post">
                <input type="hidden" id="update-vendor-id" name="vendor-id">
                <label for="update-vendor-name">Vendor Name:</label>
                <input type="text" id="update-vendor-name" name="vendor-name" required>
                <label for="update-contact-person">Contact Person:</label>
                <input type="text" id="update-contact-person" name="contact-person" required>
                <label for="update-contact-number">Contact Number:</label>
                <input type="text" id="update-contact-number" name="contact-number" required>
                <label for="update-address">Address:</label>
                <input type="text" id="update-address" name="address" required>
                <label for="update-email">Email:</label>
                <input type="email" id="update-email" name="email" required>
                <label for="update-fax">Fax:</label>
                <input type="text" id="update-fax" name="fax" required>
                <button type="submit">Update Vendor</button>
            </form>
        </div>
    </div>
</body>
</html>
