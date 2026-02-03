<?php
session_start();
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/cart_functions.php');
require_once(__DIR__ . '/toast.php');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Method not allowed');
}

// Check if required parameters exist
if (!isset($_POST['product_id'])) {
    setToast('Invalid request', 'error');
    header("Location: " . ($_POST['redirect_to'] ?? '../pages/products.php'));
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$redirect_to = $_POST['redirect_to'] ?? '../pages/products.php';

// Add to cart
$result = addToCart($product_id, $quantity);

// Set toast based on result
if ($result['success']) {
    setToast($result['message'], 'success');
} else {
    setToast($result['message'], 'error');
}

// Redirect back
header("Location: $redirect_to");
exit;
?>