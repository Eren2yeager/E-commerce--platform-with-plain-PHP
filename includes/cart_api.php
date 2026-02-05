<?php
// API endpoint for AJAX cart operations
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/cart_functions.php');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Determine action
$action = $input['action'] ?? 'add';

switch ($action) {
    case 'add':
        // Add to cart
        if (!isset($input['product_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request - product_id required'
            ]);
            exit;
        }

        $product_id = (int)$input['product_id'];
        $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

        $result = addToCart($product_id, $quantity);
        break;

    case 'update':
        // Update quantity
        if (!isset($input['product_id']) || !isset($input['quantity'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request - product_id and quantity required'
            ]);
            exit;
        }

        $product_id = (int)$input['product_id'];
        $quantity = (int)$input['quantity'];

        $result = updateCartQuantity($product_id, $quantity);
        break;

    case 'remove':
        // Remove from cart
        if (!isset($input['product_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request - product_id required'
            ]);
            exit;
        }

        $product_id = (int)$input['product_id'];
        $result = removeFromCart($product_id);
        break;

    case 'get':
        // Get cart data
        $cart_items = getCartItems();
        $cart_total = getCartTotal();
        $cart_count = getCartCount();

        echo json_encode([
            'success' => true,
            'items' => $cart_items,
            'total' => number_format($cart_total, 2),
            'count' => $cart_count
        ]);
        exit;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        exit;
}

// Get updated cart data
$cart_items = getCartItems();
$cart_total = getCartTotal();
$cart_count = getCartCount();

// Return JSON response
echo json_encode([
    'success' => $result['success'],
    'message' => $result['message'],
    'cart_count' => $cart_count,
    'cart_total' => number_format($cart_total, 2),
    'items' => $cart_items
]);
?>
