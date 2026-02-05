<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

$page_title = 'Our Products - ShopHub';

// Get filter parameters from URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$in_stock_only = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';

// Pagination setup
$per_page = 12; // Products per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Current page, minimum 1
$offset = ($page - 1) * $per_page; // Calculate offset for SQL

// Build SQL query with filters
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";
$has_filters = false; // Track if we have actual filters (not just pagination)

// Search filter
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ? OR brand LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
    $has_filters = true;
}

// Category filter
if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
    $has_filters = true;
}

// Price range filter
if ($min_price !== null && $max_price !== null) {
    $sql .= " AND price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
    $types .= "dd";
    $has_filters = true;
} elseif ($min_price !== null) {
    $sql .= " AND price >= ?";
    $params[] = $min_price;
    $types .= "d";
    $has_filters = true;
} elseif ($max_price !== null) {
    $sql .= " AND price <= ?";
    $params[] = $max_price;
    $types .= "d";
    $has_filters = true;
}

// Stock filter
if ($in_stock_only) {
    $sql .= " AND stock > 0";
}

// Sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY name ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

// Count total products BEFORE adding LIMIT (for pagination)
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$count_stmt = null;
$count_result = null;

if ($has_filters && !empty($types) && !empty($params)) {
    // Use prepared statement for count when we have filters
    $count_stmt = mysqli_prepare($conn, $count_sql);
    if (!$count_stmt) {
        die("Count Prepare Error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    if (!mysqli_stmt_execute($count_stmt)) {
        die("Count Execute Error: " . mysqli_stmt_error($count_stmt));
    }
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_products = mysqli_fetch_assoc($count_result)['total'];
} else {
    // No filters, use simple query
    $count_result = mysqli_query($conn, $count_sql);
    if (!$count_result) {
        die("Count Query Error: " . mysqli_error($conn));
    }
    $total_products = mysqli_fetch_assoc($count_result)['total'];
}

// Calculate total pages
$total_pages = ceil($total_products / $per_page);

// Add pagination to main query - DON'T use prepared statement for pagination
$sql .= " LIMIT $per_page OFFSET $offset";

// Execute query with pagination

if ($has_filters && !empty($types) && !empty($params)) {
    // Use prepared statement only when we have actual filters
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Prepare Error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        die("Execute Error: " . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        die("Get Result Error: " . mysqli_stmt_error($stmt));
    }
} else {
    // No filters, use simple query (pagination is already in SQL string)
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query Error: " . mysqli_error($conn));
    }
}

// Get all categories for filter dropdown
$categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);

?>
<style>
    /* Modern Search & Filter Layout */
    .search-filter-wrapper {
        margin-bottom: 2rem;
    }

    /* Big Search Bar */
    .main-search-container {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 1rem;
    }

    .search-bar-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .search-icon {
        font-size: 1.5rem;
        color: #999;
        margin-left: 0.5rem;
    }

    .main-search-input {
        flex: 1;
        padding: 1rem 1.25rem;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1.1rem;
        transition: all 0.3s;
        background: #f8f9fa;
    }

    .main-search-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .filter-toggle-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem 1.5rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        white-space: nowrap;
    }

    .filter-toggle-btn:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .filter-toggle-btn.active {
        background: #5568d3;
    }

    .filter-icon {
        font-size: 1.2rem;
    }

    .filter-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ff4757;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        border: 2px solid white;
    }

    /* Filter Panel */
    .filter-panel {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .filter-panel.show {
        max-height: 500px;
        opacity: 1;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
    }

    .filter-input {
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
        background: white;
    }

    .filter-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .price-inputs {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 0.75rem;
    }

    .price-input-small {
        width: 100%;
    }

    .price-to {
        color: #999;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .checkbox-filter-new {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        transition: all 0.2s;
        background: white;
    }

    .checkbox-filter-new:hover {
        border-color: #667eea;
        background: #f5f7ff;
    }

    .checkbox-filter-new input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #667eea;
    }

    .checkbox-filter-new span {
        font-size: 0.95rem;
        color: #333;
        font-weight: 500;
    }

    .filter-actions {
        display: flex;
        gap: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e0e0e0;
    }

    .btn-apply-filters {
        flex: 1;
        padding: 0.875rem 1.5rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-apply-filters:hover {
        background: #5568d3;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-clear-filters {
        padding: 0.875rem 1.5rem;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-block;
    }

    .btn-clear-filters:hover {
        background: #f5f7ff;
    }

    /* AJAX Search Dropdown - Updated positioning */
    .search-results-dropdown {
        position: absolute;
        top: calc(100% + 0.5rem);
        left: 3rem;
        right: 10rem;
        background: white;
        border: 1px solid #ddd;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        max-height: 450px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }

    .search-results-dropdown.show {
        display: block;
    }

    .search-result-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        color: inherit;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-item:hover {
        background: #f8f9fa;
    }

    .search-result-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .search-result-info {
        flex: 1;
        min-width: 0;
    }

    .search-result-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .search-result-category {
        font-size: 0.85rem;
        color: #999;
    }

    .search-result-price {
        font-weight: 700;
        color: #667eea;
        white-space: nowrap;
        font-size: 1.1rem;
    }

    .search-result-price.sale {
        color: #28a745;
    }

    .search-result-original-price {
        font-size: 0.85rem;
        color: #999;
        text-decoration: line-through;
        margin-right: 0.5rem;
    }

    .search-loading {
        padding: 1.5rem;
        text-align: center;
        color: #999;
    }

    .search-no-results {
        padding: 2rem;
        text-align: center;
        color: #999;
    }

    .search-no-results strong {
        color: #333;
        display: block;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .search-bar-wrapper {
            flex-wrap: wrap;
        }

        .main-search-input {
            width: 100%;
            font-size: 1rem;
        }

        .filter-toggle-btn {
            width: 100%;
            justify-content: center;
        }

        .search-results-dropdown {
            left: 0;
            right: 0;
        }

        .filter-grid {
            grid-template-columns: 1fr;
        }

        .filter-actions {
            flex-direction: column;
        }

        .search-icon {
            display: none;
        }
    }

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

    .results-info {
        margin-bottom: 1.5rem;
        padding: 0.75rem 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        color: #666;
        font-size: 0.95rem;
    }

    .results-info strong {
        color: #333;
    }

    /* Pagination Styles */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 3rem;
        padding: 2rem 0;
    }

    .pagination-btn {
        padding: 0.75rem 1.25rem;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }

    .pagination-btn:hover:not(.disabled) {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .pagination-btn.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        border-color: #ccc;
        color: #999;
    }

    .pagination-numbers {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .pagination-number {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        color: #667eea;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }

    .pagination-number:hover:not(.active) {
        border-color: #667eea;
        background: #f5f7ff;
    }

    .pagination-number.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
        cursor: default;
    }

    .pagination-dots {
        color: #999;
        padding: 0 0.25rem;
    }

    /* Responsive Filters */
    @media (max-width: 768px) {
        .filters-form {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-input,
        .filter-btn {
            width: 100%;
        }

        .search-input {
            min-width: 100%;
        }

        .price-input {
            width: 100%;
        }

        .price-separator {
            display: none;
        }

        /* Responsive Pagination */
        .pagination {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .pagination-number {
            width: 36px;
            height: 36px;
            font-size: 0.9rem;
        }
    }
</style>
<?php require_once('../includes/header.php'); ?>
<div class="container">
    <h1>Our Products</h1>

    <!-- Search and Filter Section -->
    <div class="search-filter-wrapper">
        <!-- Big Search Bar -->
        <div class="main-search-container">
            <div class="search-bar-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text"
                    id="searchInput"
                    name="search"
                    placeholder="Search for products, brands, categories..."
                    value="<?= htmlspecialchars($search) ?>"
                    class="main-search-input"
                    autocomplete="off">
                <button type="button" class="filter-toggle-btn" id="filterToggleBtn">
                    <span class="filter-icon">⚙️</span>
                    <span>Filters</span>
                    <?php 
                    $active_filters = 0;
                    if (!empty($search)) $active_filters++;
                    if (!empty($category)) $active_filters++;
                    if ($min_price !== null || $max_price !== null) $active_filters++;
                    if ($in_stock_only) $active_filters++;
                    if ($active_filters > 0): 
                    ?>
                        <span class="filter-badge"><?= $active_filters ?></span>
                    <?php endif; ?>
                </button>
                
                <!-- AJAX Search Results Dropdown -->
                <div id="searchResults" class="search-results-dropdown"></div>
            </div>
        </div>

        <!-- Collapsible Filter Panel -->
        <div class="filter-panel" id="filterPanel">
            <form method="GET" action="products.php" class="filters-form">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                
                <div class="filter-grid">
                    <!-- Category Filter -->
                    <div class="filter-item">
                        <label class="filter-label">Category</label>
                        <select name="category" class="filter-input">
                            <option value="">All Categories</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0); // Reset pointer
                            while ($cat = mysqli_fetch_assoc($categories_result)): 
                            ?>
                                <option value="<?= htmlspecialchars($cat['category']) ?>"
                                    <?= $category === $cat['category'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-item">
                        <label class="filter-label">Price Range</label>
                        <div class="price-inputs">
                            <input type="number"
                                name="min_price"
                                placeholder="Min"
                                value="<?= $min_price !== null ? $min_price : '' ?>"
                                min="0"
                                step="0.01"
                                class="filter-input price-input-small">
                            <span class="price-to">to</span>
                            <input type="number"
                                name="max_price"
                                placeholder="Max"
                                value="<?= $max_price !== null ? $max_price : '' ?>"
                                min="0"
                                step="0.01"
                                class="filter-input price-input-small">
                        </div>
                    </div>

                    <!-- Sort -->
                    <div class="filter-item">
                        <label class="filter-label">Sort By</label>
                        <select name="sort" class="filter-input">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A-Z</option>
                        </select>
                    </div>

                    <!-- Stock Filter -->
                    <div class="filter-item">
                        <label class="filter-label">Availability</label>
                        <label class="checkbox-filter-new">
                            <input type="checkbox" 
                                name="in_stock" 
                                value="1" 
                                <?= $in_stock_only ? 'checked' : '' ?>>
                            <span>In Stock Only</span>
                        </label>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="filter-actions">
                    <button type="submit" class="btn-apply-filters">
                        Apply Filters
                    </button>
                    <a href="products.php" class="btn-clear-filters">
                        Clear All
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Info -->
    <div class="results-info">
        <p>
            <strong><?= $total_products ?></strong> product<?= $total_products !== 1 ? 's' : '' ?> found
            <?php if (!empty($search)): ?>
                for "<strong><?= htmlspecialchars($search) ?></strong>"
            <?php endif; ?>
            <?php if (!empty($category)): ?>
                in <strong><?= htmlspecialchars($category) ?></strong>
            <?php endif; ?>
            <?php if ($in_stock_only): ?>
                <span style="color: #28a745; font-weight: 600;">• In Stock</span>
            <?php endif; ?>
            <?php if ($min_price !== null || $max_price !== null): ?>
                <span style="color: #667eea; font-weight: 600;">
                    • Price: 
                    <?php if ($min_price !== null && $max_price !== null): ?>
                        $<?= number_format($min_price, 2) ?> - $<?= number_format($max_price, 2) ?>
                    <?php elseif ($min_price !== null): ?>
                        From $<?= number_format($min_price, 2) ?>
                    <?php else: ?>
                        Up to $<?= number_format($max_price, 2) ?>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
            <?php if ($total_pages > 1): ?>
                <span style="color: #999; margin-left: 10px;">
                    (Page <?= $page ?> of <?= $total_pages ?>)
                </span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Products Grid -->
    <div class="products-grid">
        <?php 
        // Reset result pointer to beginning
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_data_seek($result, 0); // Reset to first row
        ?>
            <?php 
            $product_count = 0;
            while ($row = mysqli_fetch_assoc($result)) { 
                $product_count++;
            ?>
                <?php
                $isOnSale = !empty($row['sale_price']) && $row['sale_price'] > 0 && $row['sale_price'] < $row['price'];
                $currentPrice = $isOnSale ? $row['sale_price'] : $row['price'];
                $originalPrice = $row['price'];
                $stock = (int)$row['stock'];
                $imagePath = !empty($row['image']) ? $row['image'] : '../assets/placeholder.png';
                ?>

                <a href="product-detail.php?id=<?= $row['id'] ?>" style="text-decoration: none; color: inherit;">
                    <div class="product-card">
                        <?php if ($isOnSale): ?>
                            <div class="badge">Sale</div>
                        <?php endif; ?>

                        <img class="product-image" src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($row['name']) ?>">

                        <div class="product-info">
                            <?php if (!empty($row['category'])): ?>
                                <div class="product-category"><?= htmlspecialchars($row['category']) ?></div>
                            <?php endif; ?>

                            <h3 class="product-title"><?= htmlspecialchars($row['name']) ?></h3>

                            <p class="product-description">
                                <?= htmlspecialchars(substr($row['description'] ?? '', 0, 80)) ?>...
                            </p>

                            <div class="product-footer">
                                <div class="price-wrapper">
                                    <?php if ($isOnSale): ?>
                                        <span class="original-price">$<?= number_format($originalPrice, 2) ?></span>
                                    <?php endif; ?>
                                    <span class="price <?= $isOnSale ? 'sale' : '' ?>">
                                        $<?= number_format($currentPrice, 2) ?>
                                    </span>
                                </div>

                                <form method="POST" action="../includes/addToCart.php" style="margin: 0;" onclick="event.stopPropagation();" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit"
                                        class="btn <?= $stock > 0 ? 'btn-primary' : 'btn-disabled' ?>"
                                        <?= $stock <= 0 ? 'disabled' : '' ?>
                                        data-product-id="<?= $row['id'] ?>"
                                        data-product-name="<?= htmlspecialchars($row['name']) ?>">
                                        <?= $stock > 0 ? 'Add to Cart' : 'Sold Out' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </a>

            <?php } ?>
        <?php } else { ?>
            <div class="empty-state">
                <h2>No products found</h2>
                <p>Try adjusting your filters or search terms.</p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;">View All Products</a>
            </div>
        <?php } ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            // Build query string for pagination links (preserve filters)
            $query_params = $_GET;
            unset($query_params['page']); // Remove page param, we'll add it back
            $query_string = http_build_query($query_params);
            $base_url = 'products.php?' . ($query_string ? $query_string . '&' : '');
            ?>

            <!-- Previous Button -->
            <?php if ($page > 1): ?>
                <a href="<?= $base_url ?>page=<?= $page - 1 ?>" class="pagination-btn">
                    ← Previous
                </a>
            <?php else: ?>
                <span class="pagination-btn disabled">← Previous</span>
            <?php endif; ?>

            <!-- Page Numbers -->
            <div class="pagination-numbers">
                <?php
                // Show max 7 page numbers with smart logic
                $start_page = max(1, $page - 3);
                $end_page = min($total_pages, $page + 3);

                // Show first page if not in range
                if ($start_page > 1):
                ?>
                    <a href="<?= $base_url ?>page=1" class="pagination-number">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Page number buttons -->
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination-number active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $base_url ?>page=<?= $i ?>" class="pagination-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Show last page if not in range -->
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                    <a href="<?= $base_url ?>page=<?= $total_pages ?>" class="pagination-number"><?= $total_pages ?></a>
                <?php endif; ?>
            </div>

            <!-- Next Button -->
            <?php if ($page < $total_pages): ?>
                <a href="<?= $base_url ?>page=<?= $page + 1 ?>" class="pagination-btn">
                    Next →
                </a>
            <?php else: ?>
                <span class="pagination-btn disabled">Next →</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// AJAX Search Implementation
(function() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout = null;

    // Debounce function - waits for user to stop typing
    function debounce(func, delay) {
        return function(...args) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Fetch search results from API
    async function searchProducts(query) {
        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }

        // Show loading state
        searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
        searchResults.classList.add('show');

        try {
            // Fetch API call
            const response = await fetch(`../includes/search_api.php?q=${encodeURIComponent(query)}`);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            // Parse JSON
            const data = await response.json();

            // Display results
            displayResults(data);

        } catch (error) {
            console.error('Search error:', error);
            searchResults.innerHTML = '<div class="search-no-results">Error loading results. Please try again.</div>';
        }
    }

    // Display search results in dropdown
    function displayResults(data) {
        if (!data.success || data.products.length === 0) {
            searchResults.innerHTML = `
                <div class="search-no-results">
                    <strong>No products found</strong>
                    <p>Try a different search term</p>
                </div>
            `;
            return;
        }

        // Build HTML for results
        let html = '';
        data.products.forEach(product => {
            html += `
                <a href="product-detail.php?id=${product.id}" class="search-result-item">
                    <img src="${product.image}" alt="${product.name}" class="search-result-image">
                    <div class="search-result-info">
                        <div class="search-result-name">${product.name}</div>
                        <div class="search-result-category">${product.category || 'Uncategorized'}</div>
                    </div>
                    <div class="search-result-price ${product.is_on_sale ? 'sale' : ''}">
                        ${product.original_price ? `<span class="search-result-original-price">$${product.original_price}</span>` : ''}
                        $${product.price}
                    </div>
                </a>
            `;
        });

        searchResults.innerHTML = html;
    }

    // Event listener with debounce (waits 300ms after user stops typing)
    searchInput.addEventListener('input', debounce(function(e) {
        searchProducts(e.target.value);
    }, 300));

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.remove('show');
        }
    });

    // Show dropdown when focusing on search input (if there's content)
    searchInput.addEventListener('focus', function() {
        if (searchInput.value.length >= 2 && searchResults.innerHTML) {
            searchResults.classList.add('show');
        }
    });
})();

// Filter Panel Toggle
(function() {
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel = document.getElementById('filterPanel');
    const filterForm = filterPanel.querySelector('form');
    
    filterToggleBtn.addEventListener('click', function() {
        filterPanel.classList.toggle('show');
        filterToggleBtn.classList.toggle('active');
    });

    // Clean up empty values before form submission
    filterForm.addEventListener('submit', function(e) {
        // Get all form inputs
        const inputs = this.querySelectorAll('input[type="text"], input[type="number"], select');
        
        inputs.forEach(input => {
            // Remove empty values so they don't appear in URL
            if (input.value === '' || input.value === null) {
                input.removeAttribute('name');
            }
        });
        
        // Don't remove checkbox if unchecked, just let it not submit
    });
})();

// AJAX Add to Cart Implementation
(function() {
    const cartForms = document.querySelectorAll('.add-to-cart-form');
    
    cartForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const button = this.querySelector('button[type="submit"]');
            const productId = button.dataset.productId;
            const productName = button.dataset.productName;
            const originalText = button.textContent;
            
            // Disable button and show loading
            button.disabled = true;
            button.textContent = 'Adding...';
            
            try {
                // Make POST request with Fetch API
                const response = await fetch('../includes/cart_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });
                
                // Parse JSON response
                const data = await response.json();
                
                // Show toast notification
                if (data.success) {
                    showToast(data.message, 'success');
                    
                    // Update cart badge count
                    updateCartBadge(data.cart_count);
                } else {
                    showToast(data.message, 'error');
                }
                
            } catch (error) {
                console.error('Add to cart error:', error);
                showToast('Failed to add to cart. Please try again.', 'error');
            } finally {
                // Re-enable button
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
    
    // Show toast notification
    function showToast(message, type = 'success') {
        // Remove existing toast if any
        const existingToast = document.getElementById('dynamicToast');
        if (existingToast) {
            existingToast.remove();
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.id = 'dynamicToast';
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        // Add to page
        document.body.appendChild(toast);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Update cart badge count
    function updateCartBadge(count) {
        const cartBadge = document.querySelector('.cart-badge');
        
        if (count > 0) {
            if (cartBadge) {
                // Update existing badge
                cartBadge.textContent = count;
            } else {
                // Create new badge
                const cartLink = document.querySelector('.cart-link');
                if (cartLink) {
                    const badge = document.createElement('span');
                    badge.className = 'cart-badge';
                    badge.textContent = count;
                    cartLink.appendChild(badge);
                }
            }
        } else {
            // Remove badge if count is 0
            if (cartBadge) {
                cartBadge.remove();
            }
        }
    }
})();
</script>



<?php require_once('../includes/footer.php'); ?>