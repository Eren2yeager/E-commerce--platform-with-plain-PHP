<?php
session_start();

// Clear admin session
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
?>
