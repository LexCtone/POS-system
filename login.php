<?php
session_start();
$error_message = '';

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token mismatch');
    }

    // Include database connection
    include('connect.php'); // Ensure this file contains a MySQLi connection ($conn)

    // Sanitize and assign user inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $selected_role = trim($_POST['role']);

    // Prepare SQL query to check for user credentials
    $query = 'SELECT * FROM accounts WHERE username = ? AND role = ? AND status = 1';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $username, $selected_role);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists and verify password
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Store user info in session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            session_regenerate_id(true); // Prevent session fixation

            // Redirect based on role
            switch ($selected_role) {
                case 'admin':
                    header('Location: Dashboard.php');
                    break;
                case 'cashier':
                    header('Location: DASH/Cashier_dashboard.php');
                    break;
                default:
                    $error_message = 'Invalid role selected.';
                    break;
            }
            exit();
        } else {
            $error_message = 'Invalid password. Please try again.';
        }
    } else {
        $error_message = 'Invalid username or account is inactive. Please try again or contact an administrator.';
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St Vincent Hardware Login</title>
    <link rel="stylesheet" href="CSS/login.css">
</head>
<body id="loginBody">
    <div class="container">
        <img src="logo.jpg" alt="LOGO">
        <?php if (!empty($error_message)) { ?>
            <div class="error">
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php } ?>
        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <select name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="cashier">Cashier</option>
            </select><br>
            <input class="submit" type="submit" value="Login">
        </form>
    </div>
</body>
</html>
