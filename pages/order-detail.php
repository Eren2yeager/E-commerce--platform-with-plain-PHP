<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

// Require login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Order Details - ShopHub';
$user_id = getCurrentUserId();
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order (ensure it belongs to current user)
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items with product images
$sql = "SELECT oi.*, p.image as product_image 
        FROM order_items oi
        LEFT JOIN products p ON oi.product_name = p.name
        WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_items = mysqli_stmt_get_result($stmt);

require_once('../includes/header.php');
?>

<div class="container">
    <div style="margin-bottom: 2rem;">
        <a href="orders.php" style="color: #667eea; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
            ← Back to My Orders
        </a>
    </div>

    <div class="order-detail-container">
        <!-- Left Column -->
        <div class="order-main">
            <!-- Order Header -->
            <div class="order-detail-card">
                <div class="order-detail-header">
                    <div>
                        <h1>Order #<?= $order_id ?></h1>
                        <p style="color: #666; margin-top: 0.5rem;">
                            Placed on <?= date('F d, Y \a\t h:i A', strtotime($order['created_at'])) ?>
                        </p>
                    </div>
                    <span class="status-badge status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-detail-card">
                <h2>Order Items</h2>
                <div class="items-list">
                    <?php while ($item = mysqli_fetch_assoc($order_items)): ?>
                        <div class="item-row">
                            <div class="item-image">
                                <img src="<?= !empty($item['product_image']) ? htmlspecialchars($item['product_image']) : '../assets/placeholder.png' ?>" 
                                     alt="<?= htmlspecialchars($item['product_name']) ?>"
                                     onerror="this.src='../assets/placeholder.png'">
                            </div>
                            <div class="item-info">
                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                <span class="item-meta">
                                    Qty: <?= $item['quantity'] ?> × $<?= number_format($item['product_price'], 2) ?>
                                </span>
                            </div>
                            <div class="item-total">
                                $<?= number_format($item['subtotal'], 2) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div class="items-total">
                        <span>Total</span>
                        <strong>$<?= number_format($order['total'], 2) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="order-detail-card">
                <h2>Shipping Information</h2>
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

                <?php if (!empty($order['notes'])): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e0e0e0;">
                        <strong>Order Notes:</strong>
                        <p style="margin-top: 0.5rem; color: #666;"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="order-sidebar">
            <div class="order-detail-card">
                <h2>Order Summary</h2>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?= number_format($order['total'], 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="text-success">FREE</span>
                </div>
                <div class="summary-row">
                    <span>Tax</span>
                    <span>$0.00</span>
                </div>
                <hr>
                <div class="summary-row summary-total">
                    <strong>Total</strong>
                    <strong>$<?= number_format($order['total'], 2) ?></strong>
                </div>
            </div>

            <div class="order-detail-card">
                <h2>Payment Method</h2>
                <p style="color: #666;">
                    <?= $order['payment_method'] === 'cash_on_delivery' ? '💵 Cash on Delivery' : ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>
                </p>
            </div>

            <button onclick="window.print()" class="btn btn-secondary" style="width: 100%;">
                Print Order
            </button>
        </div>
    </div>
</div>

<style>
.order-detail-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    align-items: start;
}

.order-detail-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
}

.order-detail-card h2 {
    margin: 0 0 1.5rem 0;
    font-size: 1.3rem;
    color: #333;
}

.order-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.order-detail-header h1 {
    margin: 0;
    font-size: 2rem;
    color: #333;
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

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.items-list {
    border-top: 1px solid #e0e0e0;
}

.item-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.item-image {
    flex-shrink: 0;
}

.item-image img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.item-meta {
    font-size: 0.9rem;
    color: #666;
}

.item-total {
    font-weight: 700;
    color: #333;
}

.items-total {
    display: flex;
    justify-content: space-between;
    padding: 1.5rem 0 0.5rem;
    font-size: 1.3rem;
    color: #333;
}

.shipping-info {
    line-height: 1.8;
    color: #666;
}

.shipping-info p {
    margin: 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    color: #666;
}

.summary-total {
    font-size: 1.2rem;
    color: #333;
    margin-top: 1rem;
}

.summary-row hr {
    border: none;
    border-top: 1px solid #e0e0e0;
    margin: 1rem 0;
}

.text-success {
    color: #28a745;
    font-weight: 600;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    border: none;
    font-size: 1rem;
    transition: all 0.3s;
}

.btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-secondary:hover {
    background: #f5f7ff;
}

@media (max-width: 968px) {
    .order-detail-container {
        grid-template-columns: 1fr;
    }

    .order-detail-header {
        flex-direction: column;
        gap: 1rem;
    }
}

@media print {
    .btn, a {
        display: none !important;
    }
}
</style>

<?php require_once('../includes/footer.php'); ?>
