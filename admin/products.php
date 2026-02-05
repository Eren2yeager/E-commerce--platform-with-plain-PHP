<?php
require_once('../config/database.php');
$page_title = 'Manage Products';

$action = $_GET['action'] ?? 'list';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                // Validate inputs
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = (float)($_POST['price'] ?? 0);
                $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
                $category = trim($_POST['category'] ?? '');
                $brand = trim($_POST['brand'] ?? '');
                $stock = (int)($_POST['stock'] ?? 0);
                $image = trim($_POST['image'] ?? '');
                
                if (empty($name) || $price <= 0) {
                    $error = 'Name and valid price are required';
                } else {
                    if ($_POST['action'] === 'add') {
                        // Insert new product
                        $sql = "INSERT INTO products (name, description, price, sale_price, category, brand, stock, image, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ssddssis", $name, $description, $price, $sale_price, $category, $brand, $stock, $image);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $message = 'Product added successfully!';
                            $action = 'list';
                        } else {
                            $error = 'Failed to add product';
                        }
                    } else {
                        // Update existing product
                        $id = (int)$_POST['id'];
                        $sql = "UPDATE products SET name=?, description=?, price=?, sale_price=?, category=?, brand=?, stock=?, image=? WHERE id=?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ssddssisi", $name, $description, $price, $sale_price, $category, $brand, $stock, $image, $id);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $message = 'Product updated successfully!';
                            $action = 'list';
                        } else {
                            $error = 'Failed to update product';
                        }
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $sql = "DELETE FROM products WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Product deleted successfully!';
                } else {
                    $error = 'Failed to delete product';
                }
                $action = 'list';
                break;
        }
    }
}

// Get product for editing
$product = null;
if ($action === 'edit' && $product_id > 0) {
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    
    if (!$product) {
        $error = 'Product not found';
        $action = 'list';
    }
}

// Get all products for list view
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $sql = "SELECT * FROM products";
    
    if (!empty($search)) {
        $sql .= " WHERE name LIKE ? OR category LIKE ? OR brand LIKE ?";
        $search_term = "%$search%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $search_term, $search_term, $search_term);
        mysqli_stmt_execute($stmt);
        $products = mysqli_stmt_get_result($stmt);
    } else {
        $sql .= " ORDER BY created_at DESC";
        $products = mysqli_query($conn, $sql);
    }
}

require_once('header.php');
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- Products List -->
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>Products</h1>
            <p>Manage your product catalog</p>
        </div>
        <a href="?action=add" class="btn btn-primary">➕ Add New Product</a>
    </div>

    <!-- Search -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <form method="GET" action="" style="display: flex; gap: 1rem;">
            <input type="text" 
                name="search" 
                placeholder="Search products..." 
                value="<?= htmlspecialchars($search) ?>"
                class="form-input"
                style="flex: 1;">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if (!empty($search)): ?>
                <a href="products.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Products Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($products) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($products)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td>
                                <img src="<?= htmlspecialchars($row['image'] ?? '../assets/placeholder.png') ?>" 
                                     alt="<?= htmlspecialchars($row['name']) ?>"
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                                <small style="color: #666;"><?= htmlspecialchars(substr($row['description'] ?? '', 0, 50)) ?>...</small>
                            </td>
                            <td><?= htmlspecialchars($row['category'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($row['sale_price']) && $row['sale_price'] < $row['price']): ?>
                                    <span style="text-decoration: line-through; color: #999;">$<?= number_format($row['price'], 2) ?></span><br>
                                    <strong style="color: #28a745;">$<?= number_format($row['sale_price'], 2) ?></strong>
                                <?php else: ?>
                                    <strong>$<?= number_format($row['price'], 2) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
                                    <?php if ($row['stock'] == 0): ?>
                                        background: #fee; color: #c33;
                                    <?php elseif ($row['stock'] < 5): ?>
                                        background: #fff3cd; color: #856404;
                                    <?php else: ?>
                                        background: #d4edda; color: #155724;
                                    <?php endif; ?>">
                                    <?= $row['stock'] ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="?action=edit&id=<?= $row['id'] ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #999;">
                            No products found. <a href="?action=add">Add your first product</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Product Form -->
    <div class="page-header">
        <h1><?= $action === 'add' ? 'Add New Product' : 'Edit Product' ?></h1>
        <p><?= $action === 'add' ? 'Create a new product' : 'Update product details' ?></p>
    </div>

    <div class="card">
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?= $action ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Product Name *</label>
                    <input type="text" 
                        name="name" 
                        class="form-input" 
                        value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" 
                        name="category" 
                        class="form-input" 
                        value="<?= htmlspecialchars($product['category'] ?? '') ?>"
                        placeholder="e.g., Electronics, Clothing">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" 
                    class="form-textarea"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Price *</label>
                    <input type="number" 
                        name="price" 
                        class="form-input" 
                        step="0.01"
                        min="0"
                        value="<?= $product['price'] ?? '' ?>"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label">Sale Price</label>
                    <input type="number" 
                        name="sale_price" 
                        class="form-input" 
                        step="0.01"
                        min="0"
                        value="<?= $product['sale_price'] ?? '' ?>"
                        placeholder="Optional">
                </div>

                <div class="form-group">
                    <label class="form-label">Stock *</label>
                    <input type="number" 
                        name="stock" 
                        class="form-input" 
                        min="0"
                        value="<?= $product['stock'] ?? 0 ?>"
                        required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Brand</label>
                    <input type="text" 
                        name="brand" 
                        class="form-input" 
                        value="<?= htmlspecialchars($product['brand'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Image URL</label>
                    <input type="text" 
                        name="image" 
                        class="form-input" 
                        value="<?= htmlspecialchars($product['image'] ?? '') ?>"
                        placeholder="../assets/uploads/product.jpg">
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-success">
                    <?= $action === 'add' ? '➕ Add Product' : '💾 Update Product' ?>
                </button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

    </div>
</body>
</html>
