<?php
session_start();
$error_message = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token mismatch');
    }

    include('connect.php'); // Ensure this file contains a MySQLi connection ($conn)

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $selected_role = trim($_POST['role']);

    // Modified query to include status check
    $query = 'SELECT * FROM accounts WHERE username = ? AND role = ? AND status = 1';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $username, $selected_role);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            session_regenerate_id(true);

            // Redirect based on the role
            if ($selected_role === 'admin') {
                header('Location: Dashboard.php');
            } elseif ($selected_role === 'cashier') {
                header('Location: DASH/Cashier_dashboard.php');
            }
            exit();
        } else {
            $error_message = 'Invalid credentials. Please try again.';
        }
    } else {
        $error_message = 'Invalid credentials or account is inactive. Please try again or contact an administrator.';
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