<?php
require_once('../config/database.php');
$page_title = 'Dashboard';

// Get statistics
$stats = [];

// Total Products
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products");
$stats['total_products'] = mysqli_fetch_assoc($result)['count'];

// Total Users
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
$stats['total_users'] = mysqli_fetch_assoc($result)['count'];

// Low Stock Products (less than 5)
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock < 5 AND stock > 0");
$stats['low_stock'] = mysqli_fetch_assoc($result)['count'];

// Out of Stock
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock = 0");
$stats['out_of_stock'] = mysqli_fetch_assoc($result)['count'];

// Recent Products
$recent_products = mysqli_query($conn, "SELECT * FROM products ORDER BY created_at DESC LIMIT 5");

require_once('header.php');
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars(getAdminUsername()) ?>!</p>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?= $stats['total_products'] ?></h3>
        <p style="opacity: 0.9;">Total Products</p>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?= $stats['total_users'] ?></h3>
        <p style="opacity: 0.9;">Total Users</p>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #ffa751 0%, #ffe259 100%); color: white;">
        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?= $stats['low_stock'] ?></h3>
        <p style="opacity: 0.9;">Low Stock Items</p>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white;">
        <h3 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?= $stats['out_of_stock'] ?></h3>
        <p style="opacity: 0.9;">Out of Stock</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom: 2rem;">
    <h2 style="margin-bottom: 1rem;">Quick Actions</h2>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="products.php?action=add" class="btn btn-primary">➕ Add New Product</a>
        <a href="products.php" class="btn btn-secondary">📦 Manage Products</a>
        <a href="../pages/products.php" target="_blank" class="btn btn-secondary">🛒 View Store</a>
    </div>
</div>

<!-- Recent Products -->
<div class="card">
    <h2 style="margin-bottom: 1rem;">Recent Products</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Added</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = mysqli_fetch_assoc($recent_products)): ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($product['image'] ?? '../assets/placeholder.png') ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                        </td>
                        <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                        <td><?= htmlspecialchars($product['category'] ?? 'N/A') ?></td>
                        <td>$<?= number_format($product['price'], 2) ?></td>
                        <td>
                            <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
                                <?php if ($product['stock'] == 0): ?>
                                    background: #fee; color: #c33;
                                <?php elseif ($product['stock'] < 5): ?>
                                    background: #fff3cd; color: #856404;
                                <?php else: ?>
                                    background: #d4edda; color: #155724;
                                <?php endif; ?>">
                                <?= $product['stock'] ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($product['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

    </div>
</body>
</html>
