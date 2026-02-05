<?php
require_once('../config/database.php');
$page_title = 'Orders Management';

// Get filter parameter
$user_id_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Get user info if filtering by user
$user_info = null;
if ($user_id_filter > 0) {
    $user_sql = "SELECT username, email, profile_image FROM users WHERE id = ?";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id_filter);
    mysqli_stmt_execute($user_stmt);
    $user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
}

// Get all orders with user information including profile images
$sql = "SELECT o.*, u.username, u.email, u.profile_image,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id";

// Add user filter if specified
if ($user_id_filter > 0) {
    $sql .= " WHERE o.user_id = ?";
}

$sql .= " ORDER BY o.created_at DESC";

// Execute query
if ($user_id_filter > 0) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id_filter);
    mysqli_stmt_execute($stmt);
    $orders = mysqli_stmt_get_result($stmt);
} else {
    $orders = mysqli_query($conn, $sql);
}

$total_orders = mysqli_num_rows($orders);

// Get order statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN status='shipped' THEN 1 ELSE 0 END) as shipped,
    SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered
FROM orders";

if ($user_id_filter > 0) {
    $stats_sql .= " WHERE user_id = ?";
    $stats_stmt = mysqli_prepare($conn, $stats_sql);
    mysqli_stmt_bind_param($stats_stmt, "i", $user_id_filter);
    mysqli_stmt_execute($stats_stmt);
    $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));
} else {
    $stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_sql));
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Order status updated successfully!";
        // Refresh page to show updated data
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

require_once('header.php');
?>

<?php if (isset($message)): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1>Orders Management</h1>
        <?php if ($user_info): ?>
            <p>Viewing orders for: <strong><?= htmlspecialchars($user_info['username']) ?></strong> (<?= htmlspecialchars($user_info['email']) ?>)</p>
            <a href="orders.php" style="color: #667eea; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                ← Back to All Orders
            </a>
        <?php else: ?>
            <p>View and manage customer orders - <?= $total_orders ?> total orders</p>
        <?php endif; ?>
    </div>
    <?php if ($user_info): ?>
        <div style="display: flex; align-items: center; gap: 1rem; background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <img src="<?= !empty($user_info['profile_image']) ? $user_info['profile_image'] : '../assets/placeholder.png' ?>" 
                 alt="<?= htmlspecialchars($user_info['username']) ?>"
                 style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e0e0;"
                 onerror="this.src='../assets/placeholder.png'">
            <div>
                <div style="font-weight: 600; color: #333;"><?= htmlspecialchars($user_info['username']) ?></div>
                <div style="font-size: 0.9rem; color: #666;"><?= $total_orders ?> order<?= $total_orders != 1 ? 's' : '' ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Order Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
        <h3 style="font-size: 2rem; margin-bottom: 0.5rem;"><?= $stats['pending'] ?></h3>
        <p style="color: #856404; font-weight: 600;">Pending</p>
    </div>
    <div class="card" style="background: #cce5ff; border-left: 4px solid #007bff;">
        <h3 style="font-size: 2rem; margin-bottom: 0.5rem;"><?= $stats['processing'] ?></h3>
        <p style="color: #004085; font-weight: 600;">Processing</p>
    </div>
    <div class="card" style="background: #d1ecf1; border-left: 4px solid #17a2b8;">
        <h3 style="font-size: 2rem; margin-bottom: 0.5rem;"><?= $stats['shipped'] ?></h3>
        <p style="color: #0c5460; font-weight: 600;">Shipped</p>
    </div>
    <div class="card" style="background: #d4edda; border-left: 4px solid #28a745;">
        <h3 style="font-size: 2rem; margin-bottom: 0.5rem;"><?= $stats['delivered'] ?></h3>
        <p style="color: #155724; font-weight: 600;">Delivered</p>
    </div>
</div>

<!-- Orders Table -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($orders) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td><strong>#<?= $order['id'] ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <img src="<?= !empty($order['profile_image']) ? $order['profile_image'] : '../assets/placeholder.png' ?>" 
                                     alt="<?= htmlspecialchars($order['username']) ?>"
                                     style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e0e0;"
                                     onerror="this.src='../assets/placeholder.png'">
                                <div>
                                    <strong><?= htmlspecialchars($order['username']) ?></strong><br>
                                    <small style="color: #666;"><?= htmlspecialchars($order['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= $order['item_count'] ?> items</td>
                        <td><strong>$<?= number_format($order['total'], 2) ?></strong></td>
                        <td>
                            <?php if ($order['payment_method'] === 'cash_on_delivery'): ?>
                                💵 COD
                            <?php else: ?>
                                <?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" 
                                    onchange="if(confirm('Update order status?')) this.form.submit();"
                                    style="padding: 0.5rem; border-radius: 6px; border: 2px solid #e0e0e0; font-weight: 600;
                                    <?php
                                    switch($order['status']) {
                                        case 'pending': echo 'background: #fff3cd; color: #856404;'; break;
                                        case 'processing': echo 'background: #cce5ff; color: #004085;'; break;
                                        case 'shipped': echo 'background: #d1ecf1; color: #0c5460;'; break;
                                        case 'delivered': echo 'background: #d4edda; color: #155724;'; break;
                                        case 'cancelled': echo 'background: #f8d7da; color: #721c24;'; break;
                                    }
                                    ?>">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </form>
                        </td>
                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        <td>
                            <a href="order-details.php?id=<?= $order['id'] ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                View Details
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem; color: #999;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                        <h3>No orders found</h3>
                        <?php if ($user_info): ?>
                            <p>This user hasn't placed any orders yet.</p>
                        <?php else: ?>
                            <p>Orders will appear here once customers start placing them.</p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    </div>
</body>
</html>
