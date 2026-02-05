<?php
require_once('../config/database.php');
$page_title = 'Order Details';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order with user info including profile image
$sql = "SELECT o.*, u.username, u.email, u.profile_image 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
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

require_once('header.php');
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1>Order #<?= $order_id ?></h1>
        <p>Order details and information</p>
    </div>
    <a href="orders.php" class="btn btn-secondary">← Back to Orders</a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
    <!-- Left Column -->
    <div>
        <!-- Order Items -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h2 style="margin-bottom: 1.5rem;">📦 Order Items</h2>
            
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($order_items)): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="<?= !empty($item['product_image']) ? htmlspecialchars($item['product_image']) : '../assets/placeholder.png' ?>" 
                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #e0e0e0;"
                                         onerror="this.src='../assets/placeholder.png'">
                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                </div>
                            </td>
                            <td>$<?= number_format($item['product_price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><strong>$<?= number_format($item['subtotal'], 2) ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                    <tr style="border-top: 2px solid #e0e0e0;">
                        <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                        <td><strong style="font-size: 1.2rem; color: #28a745;">$<?= number_format($order['total'], 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Shipping Information -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">🚚 Shipping Information</h2>
            
            <div style="line-height: 1.8; color: #666;">
                <p><strong style="color: #333;"><?= htmlspecialchars($order['shipping_name']) ?></strong></p>
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
                    <strong style="color: #333;">Order Notes:</strong>
                    <p style="margin-top: 0.5rem; color: #666;"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column -->
    <div>
        <!-- Order Status -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h2 style="margin-bottom: 1.5rem;">📊 Order Status</h2>
            
            <form method="POST" action="orders.php">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label class="form-label">Current Status</label>
                    <select name="status" class="form-select">
                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Update Status
                </button>
            </form>
        </div>

        <!-- Order Information -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">ℹ️ Order Information</h2>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">Customer</div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <img src="<?= !empty($order['profile_image']) ? $order['profile_image'] : '../assets/placeholder.png' ?>" 
                             alt="<?= htmlspecialchars($order['username']) ?>"
                             style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e0e0;"
                             onerror="this.src='../assets/placeholder.png'">
                        <div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($order['username']) ?></div>
                            <div style="font-size: 0.9rem; color: #666;"><?= htmlspecialchars($order['email']) ?></div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">Order Date</div>
                    <div style="font-weight: 600;"><?= date('F d, Y', strtotime($order['created_at'])) ?></div>
                    <div style="font-size: 0.9rem; color: #666;"><?= date('h:i A', strtotime($order['created_at'])) ?></div>
                </div>
                
                <div>
                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">Payment Method</div>
                    <div style="font-weight: 600;">
                        <?= $order['payment_method'] === 'cash_on_delivery' ? '💵 Cash on Delivery' : ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>
                    </div>
                </div>
                
                <div>
                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">Total Amount</div>
                    <div style="font-weight: 700; font-size: 1.5rem; color: #28a745;">
                        $<?= number_format($order['total'], 2) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
</body>
</html>
