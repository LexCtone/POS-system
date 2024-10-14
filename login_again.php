<?php
session_start();
$success_message = '';

// Retrieve success message from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}

// Set a flag to indicate successful password reset
$_SESSION['password_reset_success'] = true;

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Again</title>
    <link rel="stylesheet" href="CSS/login.css">
</head>
<body id="loginBody">
    <div class="container">
        <img src="logo.jpg" alt="LOGO">
        <?php if (!empty($success_message)) { ?>
            <div class="success-message">
                <p><?= htmlspecialchars($success_message) ?></p>
            </div>
        <?php } ?>
        <form action="Login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="password_reset_success" value="1">
            <input class="submit" type="submit" value="Login now">
        </form>
    </div>
</body>
</html>