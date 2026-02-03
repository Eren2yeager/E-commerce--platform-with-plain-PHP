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

$result = removeFromCart($product_id);

if ($result['success']) {
    setToast($result['message'], 'success');
} else {
    setToast($result['message'], 'error');
}

header('Location: ../pages/cart.php');
exit;
?>