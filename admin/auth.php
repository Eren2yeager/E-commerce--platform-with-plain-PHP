<?php
// Admin authentication check
session_start();

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getAdminUsername() {
    return $_SESSION['admin_username'] ?? 'Admin';
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? 0;
}
?>
