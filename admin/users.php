<?php
require_once('../config/database.php');
$page_title = 'Users Management';

// Handle user actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $user_id = (int)$_POST['user_id'];
        
        switch ($_POST['action']) {
            case 'delete':
                // Check if user is not admin
                $check_sql = "SELECT role FROM users WHERE id = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "i", $user_id);
                mysqli_stmt_execute($check_stmt);
                $user_check = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
                
                if ($user_check && $user_check['role'] !== 'admin') {
                    $delete_sql = "DELETE FROM users WHERE id = ?";
                    $delete_stmt = mysqli_prepare($conn, $delete_sql);
                    mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                    
                    if (mysqli_stmt_execute($delete_stmt)) {
                        $success_message = "User deleted successfully.";
                    } else {
                        $error_message = "Failed to delete user.";
                    }
                } else {
                    $error_message = "Cannot delete admin users.";
                }
                break;
                
            case 'toggle_role':
                $new_role = $_POST['new_role'];
                $update_sql = "UPDATE users SET role = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $new_role, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $success_message = "User role updated successfully.";
                } else {
                    $error_message = "Failed to update user role.";
                }
                break;
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$sql = "SELECT u.id, u.username, u.email, u.role, u.created_at, u.profile_image,
        COUNT(DISTINCT o.id) as order_count,
        COALESCE(SUM(o.total), 0) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE 1=1";
$params = [];
$types = "";

// Search filter
if (!empty($search)) {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

// Role filter
if (!empty($role_filter)) {
    $sql .= " AND u.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$sql .= " GROUP BY u.id";

// Sorting
switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY u.created_at ASC";
        break;
    case 'name':
        $sql .= " ORDER BY u.username ASC";
        break;
    case 'orders':
        $sql .= " ORDER BY order_count DESC";
        break;
    case 'spent':
        $sql .= " ORDER BY total_spent DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY u.created_at DESC";
        break;
}

// Execute query
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $users = mysqli_stmt_get_result($stmt);
} else {
    $users = mysqli_query($conn, $sql);
}

$total_users = mysqli_num_rows($users);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customer_count
FROM users";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_sql));

require_once('header.php');
?>

<div class="page-header">
    <div>
        <h1>Users Management</h1>
        <p>Manage customer accounts - <?= $total_users ?> total users</p>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-error">✗ <?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['customer_count'] ?></div>
            <div class="stat-label">Customers</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👑</div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['admin_count'] ?></div>
            <div class="stat-label">Admins</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 1.5rem;">
    <form method="GET" action="users.php" style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <input type="text" 
            name="search" 
            placeholder="Search by name, email, or ID..." 
            value="<?= htmlspecialchars($search) ?>"
            class="form-input"
            style="flex: 1; min-width: 250px;">
        
        <select name="role" class="form-select" style="min-width: 150px;">
            <option value="">All Roles</option>
            <option value="customer" <?= $role_filter === 'customer' ? 'selected' : '' ?>>Customers</option>
            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
        </select>
        
        <select name="sort" class="form-select" style="min-width: 150px;">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
            <option value="orders" <?= $sort === 'orders' ? 'selected' : '' ?>>Most Orders</option>
            <option value="spent" <?= $sort === 'spent' ? 'selected' : '' ?>>Highest Spent</option>
        </select>
        
        <button type="submit" class="btn btn-primary">Apply</button>
        
        <?php if (!empty($search) || !empty($role_filter) || $sort !== 'newest'): ?>
            <a href="users.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($users) > 0): ?>
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <img src="<?= !empty($user['profile_image']) ? $user['profile_image'] : '../assets/placeholder.png' ?>" 
                                     alt="<?= htmlspecialchars($user['username']) ?>"
                                     style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e0e0;"
                                     onerror="this.src='../assets/placeholder.png'">
                                <div>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                    <div style="font-size: 0.85rem; color: #999;">ID: <?= $user['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
                                <?php if ($user['role'] === 'admin'): ?>
                                    background: #667eea; color: white;
                                <?php else: ?>
                                    background: #e0e0e0; color: #333;
                                <?php endif; ?>">
                                <?= ucfirst($user['role'] ?? 'customer') ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= $user['order_count'] ?></strong> order<?= $user['order_count'] != 1 ? 's' : '' ?>
                        </td>
                        <td>
                            <strong style="color: #28a745;">$<?= number_format($user['total_spent'], 2) ?></strong>
                        </td>
                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="orders.php?user_id=<?= $user['id'] ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                    View Orders
                                </a>
                                
                                <!-- Toggle Role -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Change user role?');">
                                    <input type="hidden" name="action" value="toggle_role">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="new_role" value="<?= $user['role'] === 'admin' ? 'customer' : 'admin' ?>">
                                    <button type="submit" class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem; background: #ffc107; color: #333;">
                                        <?= $user['role'] === 'admin' ? 'Demote' : 'Promote' ?>
                                    </button>
                                </form>
                                
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: #999;">
                        No users found
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
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
}

.stat-icon {
    font-size: 2.5rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    text-transform: uppercase;
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
</style>

    </div>
</body>
</html>
