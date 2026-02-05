<?php
session_start();
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/cart_functions.php');

// Require login
if (!isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/checkout.php');
    exit;
}

$user_id = getCurrentUserId();

// Get cart items
$cart_items = getCartItems();
$cart_total = getCartTotal();

// Validate cart is not empty
if (empty($cart_items)) {
    $_SESSION['error'] = 'Your cart is empty';
    header('Location: ../pages/cart.php');
    exit;
}

// Get form data
$shipping_name = trim($_POST['shipping_name'] ?? '');
$shipping_email = trim($_POST['shipping_email'] ?? '');
$shipping_phone = trim($_POST['shipping_phone'] ?? '');
$shipping_address = trim($_POST['shipping_address'] ?? '');
$shipping_city = trim($_POST['shipping_city'] ?? '');
$shipping_state = trim($_POST['shipping_state'] ?? '');
$shipping_zip = trim($_POST['shipping_zip'] ?? '');
$shipping_country = trim($_POST['shipping_country'] ?? 'USA');
$payment_method = $_POST['payment_method'] ?? 'cash_on_delivery';
$notes = trim($_POST['notes'] ?? '');

// Validate required fields
if (empty($shipping_name) || empty($shipping_email) || empty($shipping_phone) || 
    empty($shipping_address) || empty($shipping_city) || empty($shipping_zip)) {
    $_SESSION['error'] = 'Please fill in all required fields';
    header('Location: ../pages/checkout.php');
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert order
    $sql = "INSERT INTO orders (
        user_id, total, status, payment_method,
        shipping_name, shipping_email, shipping_phone,
        shipping_address, shipping_city, shipping_state,
        shipping_zip, shipping_country, notes
    ) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    // i = integer, d = double/decimal, s = string
    // user_id(i), total(d), payment_method(s), shipping_name(s), shipping_email(s), 
    // shipping_phone(s), shipping_address(s), shipping_city(s), shipping_state(s),
    // shipping_zip(s), shipping_country(s), notes(s)
    mysqli_stmt_bind_param($stmt, "idssssssssss", 
        $user_id, 
        $cart_total, 
        $payment_method,
        $shipping_name, 
        $shipping_email, 
        $shipping_phone,
        $shipping_address, 
        $shipping_city, 
        $shipping_state,
        $shipping_zip, 
        $shipping_country, 
        $notes
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to create order');
    }
    
    $order_id = mysqli_insert_id($conn);
    
    // Insert order items
    $sql = "INSERT INTO order_items (
        order_id, product_id, product_name, product_price, quantity, subtotal
    ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    foreach ($cart_items as $item) {
        $isOnSale = !empty($item['sale_price']) && $item['sale_price'] > 0 && $item['sale_price'] < $item['price'];
        $currentPrice = $isOnSale ? $item['sale_price'] : $item['price'];
        $subtotal = $currentPrice * $item['quantity'];
        
        mysqli_stmt_bind_param($stmt, "iisdid",
            $order_id,
            $item['id'],
            $item['name'],
            $currentPrice,
            $item['quantity'],
            $subtotal
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to add order items');
        }
        
        // Update product stock
        $update_sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $item['quantity'], $item['id']);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception('Failed to update product stock');
        }
    }
    
    // Clear cart
    clearCart();
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect to success page
    $_SESSION['order_success'] = true;
    $_SESSION['order_id'] = $order_id;
    header('Location: ../pages/order-success.php?order=' . $order_id);
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    $_SESSION['error'] = 'Failed to process order. Please try again.';
    header('Location: ../pages/checkout.php');
    exit;
}
?>
