<?php
session_start();
$error_message = '';

if ($_POST) {
    include('connect.php'); // Ensure this file contains a MySQLi connection ($conn)

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $selected_role = trim($_POST['role']); // Get the selected role

    $query = 'SELECT * FROM accounts WHERE username = ? AND role = ?';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $username, $selected_role); // Bind the username and role
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) { // Use this if passwords are hashed
            $_SESSION['user_id'] = $user['id']; // Store user ID
            $_SESSION['role'] = $user['role']; // Store user role

            // Redirect based on the role selected in the dropdown
            if ($selected_role === 'admin') {
                header('Location: Dashboard.php');
            } elseif ($selected_role === 'cashier') {
                header('Location: DASH/Cashier_dashboard.php');
            }
            exit();
        } else {
            $error_message = 'Wrong Username or Password! Please try again!';
        }
    } else {
        $error_message = 'Wrong Username, Password, or Role! Please try again!';
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>St Vincent Hardware Login</title>
    <link rel="stylesheet" href="CSS/login.css">
</head>
<body id="loginBody">
    <?php if (!empty($error_message)) { ?>
        <div class="error">
            <p>Error: <?= htmlspecialchars($error_message) ?></p>
        </div>
    <?php } ?>
    <div class="container">
        <img src="logo.jpg" alt="LOGO">
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <select name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="cashier">Cashier</option>
            </select><br>
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>
