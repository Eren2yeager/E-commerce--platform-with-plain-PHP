<?php
require_once('../config/database.php');
require_once('../includes/auth.php');
require_once('../includes/cart_functions.php');

// Require login for checkout
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

$page_title = 'Checkout - ShopHub';

// Get cart items
$cart_items = getCartItems();
$cart_total = getCartTotal();
$cart_count = getCartCount();

// Redirect if cart is empty
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Get user info for pre-filling
$user_id = getCurrentUserId();
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

require_once('../includes/header.php');
?>

<div class="container">
    <h1 style="margin-bottom: 2rem;">Checkout</h1>

    <div class="checkout-container">
        <!-- Checkout Form -->
        <div class="checkout-form-section">
            <form id="checkoutForm" method="POST" action="../includes/process_order.php">
                <!-- Shipping Information -->
                <div class="checkout-card">
                    <h2 class="section-title">📦 Shipping Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" 
                                name="shipping_name" 
                                class="form-input" 
                                value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" 
                                name="shipping_email" 
                                class="form-input" 
                                value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" 
                            name="shipping_phone" 
                            class="form-input" 
                            placeholder="+1 (555) 123-4567"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Street Address *</label>
                        <input type="text" 
                            name="shipping_address" 
                            class="form-input" 
                            placeholder="123 Main Street, Apt 4B"
                            required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City *</label>
                            <input type="text" 
                                name="shipping_city" 
                                class="form-input" 
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">State/Province</label>
                            <input type="text" 
                                name="shipping_state" 
                                class="form-input">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">ZIP/Postal Code *</label>
                            <input type="text" 
                                name="shipping_zip" 
                                class="form-input" 
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Country *</label>
                            <input type="text" 
                                name="shipping_country" 
                                class="form-input" 
                                value="USA"
                                required>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-card">
                    <h2 class="section-title">💳 Payment Method</h2>
                    
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash_on_delivery" checked>
                            <div class="payment-option-content">
                                <strong>💵 Cash on Delivery</strong>
                                <p>Pay when you receive your order</p>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="credit_card" disabled>
                            <div class="payment-option-content">
                                <strong>💳 Credit Card</strong>
                                <p>Coming soon</p>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="paypal" disabled>
                            <div class="payment-option-content">
                                <strong>🅿️ PayPal</strong>
                                <p>Coming soon</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="checkout-card">
                    <h2 class="section-title">📝 Order Notes (Optional)</h2>
                    <textarea name="notes" 
                        class="form-textarea" 
                        rows="4"
                        placeholder="Any special instructions for your order?"></textarea>
                </div>

                <button type="submit" class="btn-place-order" id="placeOrderBtn">
                    Place Order - $<?= number_format($cart_total, 2) ?>
                </button>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="order-summary-section">
            <div class="checkout-card order-summary-sticky">
                <h2 class="section-title">📋 Order Summary</h2>

                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                        <?php
                        $isOnSale = !empty($item['sale_price']) && $item['sale_price'] > 0 && $item['sale_price'] < $item['price'];
                        $currentPrice = $isOnSale ? $item['sale_price'] : $item['price'];
                        $itemTotal = $currentPrice * $item['quantity'];
                        ?>
                        <div class="order-item">
                            <img src="<?= htmlspecialchars($item['image'] ?? '../assets/placeholder.png') ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 class="order-item-image">
                            <div class="order-item-details">
                                <div class="order-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="order-item-quantity">Qty: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="order-item-price">
                                $<?= number_format($itemTotal, 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-totals">
                    <div class="order-total-row">
                        <span>Subtotal</span>
                        <span>$<?= number_format($cart_total, 2) ?></span>
                    </div>
                    <div class="order-total-row">
                        <span>Shipping</span>
                        <span class="text-success">FREE</span>
                    </div>
                    <div class="order-total-row">
                        <span>Tax</span>
                        <span>$0.00</span>
                    </div>
                    <hr>
                    <div class="order-total-row order-total-final">
                        <strong>Total</strong>
                        <strong>$<?= number_format($cart_total, 2) ?></strong>
                    </div>
                </div>

                <div class="secure-checkout">
                    🔒 Secure Checkout
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
    align-items: start;
}

.checkout-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.3rem;
    margin-bottom: 1.5rem;
    color: #333;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 0.875rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-textarea {
    resize: vertical;
    font-family: inherit;
}

/* Payment Options */
.payment-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.payment-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-option:has(input:checked) {
    border-color: #667eea;
    background: #f5f7ff;
}

.payment-option:has(input:disabled) {
    opacity: 0.5;
    cursor: not-allowed;
}

.payment-option input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #667eea;
}

.payment-option-content {
    flex: 1;
}

.payment-option-content strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #333;
}

.payment-option-content p {
    margin: 0;
    font-size: 0.85rem;
    color: #666;
}

/* Order Summary */
.order-summary-sticky {
    position: sticky;
    top: 90px;
}

.order-items {
    margin-bottom: 1.5rem;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.order-item-details {
    flex: 1;
}

.order-item-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.order-item-quantity {
    font-size: 0.85rem;
    color: #666;
}

.order-item-price {
    font-weight: 700;
    color: #333;
}

.order-totals {
    padding-top: 1rem;
}

.order-total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    color: #666;
}

.order-total-final {
    font-size: 1.2rem;
    color: #333;
    margin-top: 1rem;
}

.order-totals hr {
    border: none;
    border-top: 1px solid #e0e0e0;
    margin: 1rem 0;
}

.text-success {
    color: #28a745;
    font-weight: 600;
}

.secure-checkout {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    color: #666;
    font-weight: 600;
    margin-top: 1.5rem;
}

.btn-place-order {
    width: 100%;
    padding: 1.25rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.2rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-place-order:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-place-order:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Responsive */
@media (max-width: 968px) {
    .checkout-container {
        grid-template-columns: 1fr;
    }

    .order-summary-sticky {
        position: static;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Form submission handling
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true;
    btn.textContent = 'Processing Order...';
});
</script>

<?php require_once('../includes/footer.php'); ?>
