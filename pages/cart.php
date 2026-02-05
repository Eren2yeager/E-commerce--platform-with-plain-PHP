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
        <div class="empty-cart" id="emptyCart">
            <div class="empty-cart-icon">🛒</div>
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added anything to your cart yet.</p>
            <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;">Continue Shopping</a>
        </div>
    <?php else: ?>
        <!-- Cart Items -->
        <div class="cart-container" id="cartContainer">
            <div class="cart-items" id="cartItemsList">
                <?php foreach ($cart_items as $item): ?>
                    <?php
                    $item_total = $item['price'] * $item['quantity'];
                    $isOnSale = !empty($item['sale_price']) && $item['sale_price'] > 0 && $item['sale_price'] < $item['price'];
                    $currentPrice = $isOnSale ? $item['sale_price'] : $item['price'];
                    ?>
                    
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="<?= htmlspecialchars($item['image'] ?? '../assets/placeholder.png') ?>" 
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
                            <form class="quantity-form" data-product-id="<?= $item['id'] ?>" data-stock="<?= $item['stock'] ?>">
                                <button type="button" class="qty-btn qty-decrease" data-action="decrease">−</button>
                                <input type="number" class="qty-input" value="<?= $item['quantity'] ?>" 
                                       min="1" max="<?= $item['stock'] ?>" readonly>
                                <button type="button" class="qty-btn qty-increase" data-action="increase">+</button>
                            </form>
                        </div>

                        <div class="cart-item-total" data-price="<?= $currentPrice ?>">
                            <strong>$<span class="item-total"><?= number_format($currentPrice * $item['quantity'], 2) ?></span></strong>
                        </div>

                        <div class="cart-item-remove">
                            <button type="button" class="remove-btn" data-product-id="<?= $item['id'] ?>" title="Remove item">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary" id="cartSummary">
                <h2>Order Summary</h2>
                
                <div class="summary-row">
                    <span>Items (<span id="summaryCount"><?= $cart_count ?></span>)</span>
                    <span>$<span id="summaryTotal"><?= number_format($cart_total, 2) ?></span></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>FREE</span>
                </div>
                
                <hr>
                
                <div class="summary-row summary-total">
                    <strong>Total</strong>
                    <strong>$<span id="summaryTotalFinal"><?= number_format($cart_total, 2) ?></span></strong>
                </div>

                <a href="checkout.php" class="btn btn-primary btn-checkout" style="width: 100%; margin-top: 1rem; text-align: center; text-decoration: none; display: block;">
                    Proceed to Checkout
                </a>

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

<script>
// AJAX Cart Management
(function() {
    const cartItemsList = document.getElementById('cartItemsList');
    const emptyCart = document.getElementById('emptyCart');
    const cartContainer = document.getElementById('cartContainer');

    // Update quantity
    document.addEventListener('click', async function(e) {
        // Handle quantity buttons
        if (e.target.classList.contains('qty-btn')) {
            const form = e.target.closest('.quantity-form');
            const productId = form.dataset.productId;
            const qtyInput = form.querySelector('.qty-input');
            const currentQty = parseInt(qtyInput.value);
            const stock = parseInt(form.dataset.stock);
            const action = e.target.dataset.action;
            
            let newQty = currentQty;
            if (action === 'increase' && currentQty < stock) {
                newQty = currentQty + 1;
            } else if (action === 'decrease' && currentQty > 1) {
                newQty = currentQty - 1;
            } else {
                return; // Can't increase/decrease further
            }

            // Disable buttons
            const buttons = form.querySelectorAll('.qty-btn');
            buttons.forEach(btn => btn.disabled = true);

            try {
                const response = await fetch('../includes/cart_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update',
                        product_id: productId,
                        quantity: newQty
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Update quantity input
                    qtyInput.value = newQty;
                    
                    // Update item total
                    const cartItem = form.closest('.cart-item');
                    const itemTotalEl = cartItem.querySelector('.item-total');
                    const price = parseFloat(cartItem.querySelector('.cart-item-total').dataset.price);
                    itemTotalEl.textContent = (price * newQty).toFixed(2);
                    
                    // Update cart summary
                    updateCartSummary(data.cart_total, data.cart_count);
                    updateCartBadge(data.cart_count);
                    
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Update cart error:', error);
                showToast('Failed to update cart. Please try again.', 'error');
            } finally {
                buttons.forEach(btn => btn.disabled = false);
            }
        }

        // Handle remove button
        if (e.target.classList.contains('remove-btn')) {
            const productId = e.target.dataset.productId;
            const cartItem = e.target.closest('.cart-item');

            if (!confirm('Remove this item from cart?')) {
                return;
            }

            // Disable button
            e.target.disabled = true;

            try {
                const response = await fetch('../includes/cart_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'remove',
                        product_id: productId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Remove item with animation
                    cartItem.style.opacity = '0';
                    cartItem.style.transform = 'translateX(-20px)';
                    
                    setTimeout(() => {
                        cartItem.remove();
                        
                        // Check if cart is empty
                        if (data.cart_count === 0) {
                            showEmptyCart();
                        } else {
                            updateCartSummary(data.cart_total, data.cart_count);
                        }
                        
                        updateCartBadge(data.cart_count);
                    }, 300);
                    
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                    e.target.disabled = false;
                }
            } catch (error) {
                console.error('Remove from cart error:', error);
                showToast('Failed to remove item. Please try again.', 'error');
                e.target.disabled = false;
            }
        }
    });

    function updateCartSummary(total, count) {
        document.getElementById('summaryCount').textContent = count;
        document.getElementById('summaryTotal').textContent = total;
        document.getElementById('summaryTotalFinal').textContent = total;
    }

    function showEmptyCart() {
        if (cartContainer) {
            cartContainer.style.opacity = '0';
            setTimeout(() => {
                cartContainer.remove();
                if (emptyCart) {
                    emptyCart.style.display = 'block';
                } else {
                    // Create empty cart element
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-cart';
                    emptyDiv.innerHTML = `
                        <div class="empty-cart-icon">🛒</div>
                        <h2>Your cart is empty</h2>
                        <p>Looks like you haven't added anything to your cart yet.</p>
                        <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;">Continue Shopping</a>
                    `;
                    document.querySelector('.container').appendChild(emptyDiv);
                }
            }, 300);
        }
    }

    function updateCartBadge(count) {
        const cartBadge = document.querySelector('.cart-badge');
        
        if (count > 0) {
            if (cartBadge) {
                cartBadge.textContent = count;
            } else {
                const cartLink = document.querySelector('.cart-link');
                if (cartLink) {
                    const badge = document.createElement('span');
                    badge.className = 'cart-badge';
                    badge.textContent = count;
                    cartLink.appendChild(badge);
                }
            }
        } else {
            if (cartBadge) cartBadge.remove();
        }
    }

    function showToast(message, type = 'success') {
        const existingToast = document.getElementById('dynamicToast');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.id = 'dynamicToast';
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Add smooth transitions to cart items
    if (cartItemsList) {
        const items = cartItemsList.querySelectorAll('.cart-item');
        items.forEach(item => {
            item.style.transition = 'opacity 0.3s, transform 0.3s';
        });
    }
})();
</script>

<style>
/* Toast Notification */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 1rem;
    z-index: 9999;
    animation: slideIn 0.3s ease;
    max-width: 400px;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.toast.hiding {
    animation: slideOut 0.3s ease;
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

.toast-success {
    border-left: 4px solid #28a745;
}

.toast-error {
    border-left: 4px solid #dc3545;
}

.toast-message {
    flex: 1;
    font-weight: 500;
    color: #333;
}

.toast-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.toast-close:hover {
    color: #333;
}

/* Cart Item Transitions */
.cart-item {
    transition: opacity 0.3s, transform 0.3s;
}
</style>

<?php require_once('../includes/footer.php'); ?>