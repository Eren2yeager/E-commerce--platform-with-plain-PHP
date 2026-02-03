<?php
session_start();
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/cart_functions.php');
require_once(__DIR__ . '/toast.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Method not allowed');
}

$product_id = (int)$_POST['product_id'];
$action = $_POST['action'] ?? '';

// Get current quantity from cart
$cart_items = getCartItems();
$current_qty = 0;

foreach ($cart_items as $item) {
    if ($item['id'] == $product_id) {
        $current_qty = $item['quantity'];
        break;
    }
}

// Calculate new quantity based on action
if ($action === 'increase') {
    $new_qty = $current_qty + 1;
} elseif ($action === 'decrease') {
    $new_qty = $current_qty - 1;
} elseif (isset($_POST['quantity'])) {
    $new_qty = (int)$_POST['quantity'];
} else {
    setToast('Invalid action', 'error');
    header('Location: ../pages/cart.php');
    exit;
}

// Update quantity
$result = updateCartQuantity($product_id, $new_qty);

if ($result['success']) {
    setToast($result['message'], 'success');
} else {
    setToast($result['message'], 'error');
}

header('Location: ../pages/cart.php');
exit;
?>