<?php
require_once('../config/database.php');
require_once('../includes/auth.php');
require_once('../includes/cart_functions.php');

$page_title = 'Shopping Cart - ShopHub';

// Get cart items
$cart_items = getCartItems();
$cart_total = getCartTotal();
$cart_count = getCartCount();

require_once('../includes/header.php');
?>

<div class="container">
    <h1 style="margin-bottom: 2rem;">Shopping Cart</h1>

    <?php if (empty($cart_items)): ?>
        <!-- Empty Cart State -->
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added anything to your cart yet.</p>
            <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;">Continue Shopping</a>
        </div>
    <?php else: ?>
        <!-- Cart Items -->
        <div class="cart-container">
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <?php
                    $item_total = $item['price'] * $item['quantity'];
                    $isOnSale = !empty($item['sale_price']) && $item['sale_price'] > 0 && $item['sale_price'] < $item['price'];
                    $currentPrice = $isOnSale ? $item['sale_price'] : $item['price'];
                    ?>
                    
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="<?= htmlspecialchars($item['image'] ?? '../assets/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>">
                        </div>

                        <div class="cart-item-details">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="cart-item-category"><?= htmlspecialchars($item['category'] ?? '') ?></p>
                            
                            <div class="cart-item-price">
                                <?php if ($isOnSale): ?>
                                    <span class="original-price">$<?= number_format($item['price'], 2) ?></span>
                                <?php endif; ?>
                                <span class="price">$<?= number_format($currentPrice, 2) ?></span>
                            </div>
                        </div>

                        <div class="cart-item-quantity">
                            <form method="POST" action="../includes/updateCart.php" class="quantity-form">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <button type="submit" name="action" value="decrease" class="qty-btn">−</button>
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                       min="1" max="<?= $item['stock'] ?>" class="qty-input" readonly>
                                <button type="submit" name="action" value="increase" class="qty-btn">+</button>
                            </form>
                        </div>

                        <div class="cart-item-total">
                            <strong>$<?= number_format($currentPrice * $item['quantity'], 2) ?></strong>
                        </div>

                        <div class="cart-item-remove">
                            <form method="POST" action="../includes/removeFromCart.php">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="remove-btn" title="Remove item">🗑️</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <h2>Order Summary</h2>
                
                <div class="summary-row">
                    <span>Items (<?= $cart_count ?>)</span>
                    <span>$<?= number_format($cart_total, 2) ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>FREE</span>
                </div>
                
                <hr>
                
                <div class="summary-row summary-total">
                    <strong>Total</strong>
                    <strong>$<?= number_format($cart_total, 2) ?></strong>
                </div>

                <button class="btn btn-primary btn-checkout" style="width: 100%; margin-top: 1rem;">
                    Proceed to Checkout
                </button>

                <a href="products.php" class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem; display: block; text-align: center; text-decoration: none;">
                    Continue Shopping
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Cart Page Styles */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-cart-icon {
    font-size: 5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-cart h2 {
    color: #333;
    margin-bottom: 0.5rem;
}

.empty-cart p {
    color: #666;
    margin-bottom: 2rem;
}

.cart-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    align-items: start;
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.cart-item {
    display: grid;
    grid-template-columns: 100px 1fr auto auto auto;
    gap: 1.5rem;
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    align-items: center;
}

.cart-item-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-item-details h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.cart-item-category {
    color: #666;
    font-size: 0.9rem;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.cart-item-price .original-price {
    text-decoration: line-through;
    color: #999;
    margin-right: 0.5rem;
}

.cart-item-price .price {
    font-weight: 600;
    color: #333;
}

.quantity-form {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.qty-btn:hover {
    background: #f5f5f5;
    border-color: #667eea;
}

.qty-input {
    width: 60px;
    height: 32px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-weight: 600;
}

.cart-item-total {
    font-size: 1.1rem;
    min-width: 100px;
    text-align: right;
}

.remove-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s;
    padding: 0.5rem;
}

.remove-btn:hover {
    opacity: 1;
}

/* Cart Summary */
.cart-summary {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: sticky;
    top: 90px;
}

.cart-summary h2 {
    margin: 0 0 1.5rem 0;
    font-size: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    color: #666;
}

.summary-total {
    font-size: 1.2rem;
    color: #333;
}

.cart-summary hr {
    border: none;
    border-top: 1px solid #eee;
    margin: 1.5rem 0;
}

.btn-checkout {
    font-size: 1.1rem;
    padding: 1rem;
}

.btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
    padding: 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}

.btn-secondary:hover {
    background: #f5f5f5;
}

/* Responsive */
@media (max-width: 968px) {
    .cart-container {
        grid-template-columns: 1fr;
    }

    .cart-summary {
        position: static;
    }

    .cart-item {
        grid-template-columns: 80px 1fr;
        gap: 1rem;
    }

    .cart-item-quantity,
    .cart-item-total {
        grid-column: 2;
    }

    .cart-item-remove {
        grid-column: 2;
        justify-self: end;
    }
}

@media (max-width: 480px) {
    .cart-item {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .cart-item-image img {
        margin: 0 auto;
    }

    .cart-item-quantity,
    .cart-item-total,
    .cart-item-remove {
        grid-column: 1;
        justify-self: center;
    }
}
</style>

<?php require_once('../includes/footer.php'); ?>