<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

// Require login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = 'My Orders - ShopHub';
$user_id = getCurrentUserId();

// Get filter parameters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $order_id = (int)$_POST['order_id'];
    
    // Verify order belongs to user and can be cancelled
    $check_sql = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $order_check = mysqli_fetch_assoc($check_result);
    
    if ($order_check && in_array($order_check['status'], ['pending', 'processing'])) {
        // Cancel the order
        $cancel_sql = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?";
        $cancel_stmt = mysqli_prepare($conn, $cancel_sql);
        mysqli_stmt_bind_param($cancel_stmt, "ii", $order_id, $user_id);
        
        if (mysqli_stmt_execute($cancel_stmt)) {
            $success_message = "Order #$order_id has been cancelled successfully.";
        } else {
            $error_message = "Failed to cancel order. Please try again.";
        }
    } else {
        $error_message = "This order cannot be cancelled.";
    }
}

// Build query with filters
$sql = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];
$types = "i";

// Status filter
if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Search filter (by order ID or total amount)
if (!empty($search)) {
    $sql .= " AND (id LIKE ? OR total LIKE ? OR shipping_city LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

// Sorting
switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    case 'amount_high':
        $sql .= " ORDER BY total DESC";
        break;
    case 'amount_low':
        $sql .= " ORDER BY total ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

// Execute query
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

// Get order statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_count,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
    SUM(total) as total_spent
FROM orders WHERE user_id = ?";
$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "i", $user_id);
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));

require_once('../includes/header.php');
?>

<div class="container">
    <div class="page-header">
        <h1>My Orders</h1>
        <p>Track and manage your orders</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            ✓ <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            ✗ <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Order Statistics -->
    <?php if ($stats['total_orders'] > 0): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['total_orders'] ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['pending_count'] + $stats['processing_count'] ?></div>
                    <div class="stat-label">Active Orders</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['delivered_count'] ?></div>
                    <div class="stat-label">Delivered</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <div class="stat-value">$<?= number_format($stats['total_spent'], 2) ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="filters-section">
            <form method="GET" action="orders.php" class="filters-form">
                <div class="filter-group">
                    <input type="text" 
                        name="search" 
                        placeholder="Search by order ID, city..." 
                        value="<?= htmlspecialchars($search) ?>"
                        class="filter-input">
                </div>

                <div class="filter-group">
                    <select name="status" class="filter-input">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="sort" class="filter-input">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="amount_high" <?= $sort === 'amount_high' ? 'selected' : '' ?>>Highest Amount</option>
                        <option value="amount_low" <?= $sort === 'amount_low' ? 'selected' : '' ?>>Lowest Amount</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Apply</button>
                
                <?php if (!empty($search) || !empty($status_filter) || $sort !== 'newest'): ?>
                    <a href="orders.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($orders_result) > 0): ?>
        <div class="orders-list">
            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                <?php
                // Get order items count
                $items_sql = "SELECT COUNT(*) as count FROM order_items WHERE order_id = ?";
                $items_stmt = mysqli_prepare($conn, $items_sql);
                mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
                mysqli_stmt_execute($items_stmt);
                $items_count = mysqli_fetch_assoc(mysqli_stmt_get_result($items_stmt))['count'];
                ?>
                
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>Order #<?= $order['id'] ?></h3>
                            <p class="order-date">
                                Placed on <?= date('F d, Y', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <div class="order-status">
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-body">
                        <div class="order-details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Items</span>
                                <span class="detail-value"><?= $items_count ?> item<?= $items_count != 1 ? 's' : '' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total</span>
                                <span class="detail-value total-amount">$<?= number_format($order['total'], 2) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Payment</span>
                                <span class="detail-value">
                                    <?= $order['payment_method'] === 'cash_on_delivery' ? 'Cash on Delivery' : ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Shipping To</span>
                                <span class="detail-value"><?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="order-footer">
                        <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-primary">
                            View Details
                        </a>
                        
                        <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                <input type="hidden" name="action" value="cancel_order">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" class="btn btn-danger">
                                    Cancel Order
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'delivered'): ?>
                            <a href="products.php" class="btn btn-secondary">
                                Order Again
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <h2>No Orders Yet</h2>
            <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
            <a href="products.php" class="btn btn-primary" style="margin-top: 1.5rem;">
                Browse Products
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.page-header p {
    margin: 0;
    color: #666;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filters-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.filter-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s;
}

.order-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.order-info h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1.2rem;
}

.order-date {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.order-status {
    text-align: right;
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

.order-body {
    padding: 1.5rem;
}

.order-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
    font-size: 1rem;
    font-weight: 600;
    color: #333;
}

.total-amount {
    color: #28a745;
    font-size: 1.3rem;
}

.order-footer {
    padding: 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 1rem;
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

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-secondary:hover:not(:disabled) {
    background: #f5f7ff;
}

.btn-secondary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h2 {
    color: #333;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #666;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }

    .filters-form {
        flex-direction: column;
    }

    .filter-group {
        width: 100%;
    }

    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .order-status {
        text-align: left;
    }

    .order-details-grid {
        grid-template-columns: 1fr;
    }

    .order-footer {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php require_once('../includes/footer.php'); ?>
