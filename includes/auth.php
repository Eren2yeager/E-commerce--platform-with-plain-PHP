<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function: Check if user is logged in
function isLoggedIn()
{
    // TODO: Return true if $_SESSION['user_id'] is set, false otherwise
    return isset($_SESSION['user_id']);
}

// Function: Get current user ID
function getCurrentUserId()
{
    // TODO: Return $_SESSION['user_id'] if logged in, null otherwise
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    return null;
}

// Function: Get current username
function getCurrentUsername()
{
    // TODO: Return $_SESSION['username'] if logged in, null otherwise
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    return null;
}

// Function: Require login (redirect if not logged in)
function requireLogin()
{
    // TODO: If not logged in, redirect to login.php
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}
?>