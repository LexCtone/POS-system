<?php
session_start();
$_SESSION = array(); // Clear session variables
session_destroy(); // Destroy the session
header("Location: login.php"); // Redirect to login page
exit();
?>
