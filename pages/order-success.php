<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

// Require login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Order Confirmed - ShopHub';

// Get order ID
$order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$user_id = getCurrentUserId();

// Fetch order details
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header('Location: products.php');
    exit;
}

// Fetch order items
$sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_items = mysqli_stmt_get_result($stmt);

require_once('../includes/header.php');
?>

<div class="container">
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Order Confirmed!</h1>
        <p class="success-message">
            Thank you for your order. We've received your order and will process it shortly.
        </p>

        <div class="order-number">
            Order #<?= $order_id ?>
        </div>

        <!-- Order Details -->
        <div class="order-details-card">
            <h2>📦 Order Details</h2>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Order Date</span>
                    <span class="detail-value"><?= date('F d, Y', strtotime($order['created_at'])) ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="status-badge status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value">
                        <?= $order['payment_method'] === 'cash_on_delivery' ? '💵 Cash on Delivery' : ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Total Amount</span>
                    <span class="detail-value total-amount">$<?= number_format($order['total'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="order-details-card">
            <h2>🚚 Shipping Information</h2>
            
            <div class="shipping-info">
                <p><strong><?= htmlspecialchars($order['shipping_name']) ?></strong></p>
                <p><?= htmlspecialchars($order['shipping_email']) ?></p>
                <p><?= htmlspecialchars($order['shipping_phone']) ?></p>
                <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                <p>
                    <?= htmlspecialchars($order['shipping_city']) ?>, 
                    <?= htmlspecialchars($order['shipping_state']) ?> 
                    <?= htmlspecialchars($order['shipping_zip']) ?>
                </p>
                <p><?= htmlspecialchars($order['shipping_country']) ?></p>
            </div>
        </div>

        <!-- Order Items -->
        <div class="order-details-card">
            <h2>📋 Order Items</h2>
            
            <div class="order-items-list">
                <?php while ($item = mysqli_fetch_assoc($order_items)): ?>
                    <div class="order-item-row">
                        <div class="item-info">
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                            <span class="item-quantity">Qty: <?= $item['quantity'] ?> × $<?= number_format($item['product_price'], 2) ?></span>
                        </div>
                        <div class="item-total">
                            $<?= number_format($item['subtotal'], 2) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <div class="order-total-row">
                    <strong>Total</strong>
                    <strong>$<?= number_format($order['total'], 2) ?></strong>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="success-actions">
            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            <button onclick="window.print()" class="btn btn-secondary">Print Order</button>
        </div>

        <!-- What's Next -->
        <div class="whats-next">
            <h3>What happens next?</h3>
            <ul>
                <li>✓ You'll receive an email confirmation shortly</li>
                <li>✓ We'll process your order within 24 hours</li>
                <li>✓ You'll be notified when your order ships</li>
                <li>✓ Estimated delivery: 3-5 business days</li>
            </ul>
        </div>
    </div>
</div>

<style>
.success-container {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
    padding: 2rem 0;
}

.success-icon {
    font-size: 5rem;
    margin-bottom: 1rem;
    animation: scaleIn 0.5s ease;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.success-container h1 {
    font-size: 2.5rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.success-message {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 2rem;
}

.order-number {
    display: inline-block;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50px;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 2rem;
}

.order-details-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
    text-align: left;
}

.order-details-card h2 {
    font-size: 1.3rem;
    margin-bottom: 1.5rem;
    color: #333;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-label {
    font-size: 0.85rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.total-amount {
    color: #28a745;
    font-size: 1.5rem;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cce5ff;
    color: #004085;
}

.status-shipped {
    background: #d1ecf1;
    color: #0c5460;
}

.status-delivered {
    background: #d4edda;
    color: #155724;
}

.shipping-info {
    line-height: 1.8;
    color: #666;
}

.shipping-info p {
    margin: 0;
}

.order-items-list {
    border-top: 1px solid #e0e0e0;
}

.order-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.item-quantity {
    font-size: 0.9rem;
    color: #666;
}

.item-total {
    font-weight: 700;
    color: #333;
}

.order-total-row {
    display: flex;
    justify-content: space-between;
    padding: 1.5rem 0 0.5rem;
    font-size: 1.3rem;
    color: #333;
}

.success-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.btn {
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    border: none;
    font-size: 1rem;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-secondary:hover {
    background: #f5f7ff;
}

.whats-next {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 12px;
    text-align: left;
}

.whats-next h3 {
    margin-bottom: 1rem;
    color: #333;
}

.whats-next ul {
    list-style: none;
    padding: 0;
    line-height: 2;
    color: #666;
}

@media (max-width: 768px) {
    .success-container h1 {
        font-size: 2rem;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .success-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}

@media print {
    .success-actions,
    .whats-next {
        display: none;
    }
}
</style>

<?php require_once('../includes/footer.php'); ?>
