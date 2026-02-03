<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/auth.php');

// Add item to cart
function addToCart($product_id, $quantity = 1) {
    global $conn;
    
    // Validate inputs
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    if ($product_id <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Invalid product or quantity'];
    }
    
    try {
        if (isLoggedIn()) {
            // Database: Add or update quantity
            $user_id = getCurrentUserId();
            $sql = "INSERT INTO cart (user_id, product_id, quantity) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE quantity = quantity + ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiii", $user_id, $product_id, $quantity, $quantity);
            
            if (mysqli_stmt_execute($stmt)) {
                return ['success' => true, 'message' => 'Added to cart'];
            } else {
                return ['success' => false, 'message' => 'Failed to add to cart'];
            }
        } else {
            // Session: Add or update quantity
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            
            return ['success' => true, 'message' => 'Added to cart'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Get all cart items with product details
// Get all cart items with product details
function getCartItems() {
    global $conn;
    
    try {
        if (isLoggedIn()) {
            $user_id = getCurrentUserId();
            $sql = "SELECT 
                        cart.id as cart_id,
                        cart.quantity,
                        products.*
                    FROM cart 
                    JOIN products ON cart.product_id = products.id 
                    WHERE cart.user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $items = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
            return $items;
        } else {
            $cart = $_SESSION['cart'] ?? [];
            $items = [];
            
            foreach ($cart as $product_id => $quantity) {
                $sql = "SELECT * FROM products WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $product_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $product = mysqli_fetch_assoc($result);
                
                if ($product) {
                    $product['quantity'] = $quantity;
                    $items[] = $product;
                }
            }
            return $items;
        }
    } catch (Exception $e) {
        return [];
    }
}

// Update quantity
function updateCartQuantity($product_id, $quantity) {
    global $conn;
    
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    // If quantity is 0 or negative, remove item
    if ($quantity <= 0) {
        return removeFromCart($product_id);
    }
    
    try {
        if (isLoggedIn()) {
            $user_id = getCurrentUserId();
            $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iii", $quantity, $user_id, $product_id);
            mysqli_stmt_execute($stmt);
            return ['success' => true, 'message' => 'Quantity updated'];
        } else {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            return ['success' => true, 'message' => 'Quantity updated'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Remove item
function removeFromCart($product_id) {
    global $conn;
    
    $product_id = (int)$product_id;
    
    try {
        if (isLoggedIn()) {
            $user_id = getCurrentUserId();
            $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
            mysqli_stmt_execute($stmt);
            return ['success' => true, 'message' => 'Item removed'];
        } else {
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
            return ['success' => true, 'message' => 'Item removed'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Get cart count
function getCartCount() {
    global $conn;
    
    try {
        if (isLoggedIn()) {
            $user_id = getCurrentUserId();
            $sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            return (int)($row['total'] ?? 0);
        } else {
            $cart = $_SESSION['cart'] ?? [];
            return array_sum($cart);  // Sum all quantities
        }
    } catch (Exception $e) {
        return 0;
    }
}

// Get cart total price
// Get cart total price
function getCartTotal() {
    global $conn;
    
    try {
        if (isLoggedIn()) {
            $user_id = getCurrentUserId();
            $sql = "SELECT 
                        cart.quantity,
                        CASE 
                            WHEN products.sale_price IS NOT NULL 
                                 AND products.sale_price > 0 
                                 AND products.sale_price < products.price 
                            THEN products.sale_price 
                            ELSE products.price 
                        END as current_price
                    FROM cart 
                    JOIN products ON cart.product_id = products.id 
                    WHERE cart.user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $total = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $total += $row['current_price'] * $row['quantity'];
            }
            return $total;
        } else {
            $cart = $_SESSION['cart'] ?? [];
            $total = 0;
            
            foreach ($cart as $product_id => $quantity) {
                $sql = "SELECT 
                            CASE 
                                WHEN sale_price IS NOT NULL 
                                     AND sale_price > 0 
                                     AND sale_price < price 
                                THEN sale_price 
                                ELSE price 
                            END as current_price
                        FROM products WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $product_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                if ($row) {
                    $total += $row['current_price'] * $quantity;
                }
            }
            return $total;
        }
    } catch (Exception $e) {
        return 0;
    }
}
?>