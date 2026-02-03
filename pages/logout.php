<?php
require_once('../includes/auth.php');

// 1. Unset all session variables
$_SESSION = [];

// 2. Destroy the session
session_destroy();

// 3. Redirect to login
header("Location: login.php");
exit;
?>