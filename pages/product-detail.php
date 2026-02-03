<?php
require_once('../config/database.php');
require_once('../includes/auth.php');
// Get product ID safely
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if invalid ID
if ($id <= 0) {
    header("Location: products.php");
    exit;
}

// Fetch product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

// Check if product exists
if (!$product) {
    // Optional: If id=3 is requested and not found, and we are in dev/demo mode, we *could* show the mock data the user provided.
    // However, clean implementation implies returning not found. 
    // Given the user instruction "here the data i am getting", let's assume if it fails, we show a nice message.
    echo "<div style='text-align:center; padding:50px;'><h2>Product not found!</h2><a href='products.php'>Back to products</a></div>";
    exit;
}

// Price Logic
$isOnSale = !empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price'];
$currentPrice = $isOnSale ? $product['sale_price'] : $product['price'];
$originalPrice = $product['price'];
$stock = (int)$product['stock'];
$imagePath = !empty($product['image']) ? $product['image'] : '../assets/placeholder.jpg';
$page_title = $product['name'];

?>


    <?php require_once('../includes/header.php'); ?>
    <div class="container">
        <!-- Breadcrumb / Back -->
        <div style="margin-bottom: 1rem;">
            <a href="products.php" style="color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                &larr; Back to Products
            </a>
        </div>

        <div class="product-detail-container">
            <!-- Left: Image -->
            <div class="detail-image-container">
                <?php if ($isOnSale) { ?>
                    <div class="badge" style="position:absolute; margin: 1rem;">Sale</div>
                <?php } ?>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="detail-image">
            </div>

            <!-- Right: Info -->
            <div class="detail-info">
                <div>
                    <?php if (!empty($product['category'])) { ?>
                        <div class="detail-category"><?php echo htmlspecialchars($product['category']); ?></div>
                    <?php } ?>
                    <h2 class="detail-title"><?php echo htmlspecialchars($product['name']); ?></h2>

                    <div class="detail-price-wrapper">
                        <?php if ($isOnSale) { ?>
                            <span class="detail-original-price">$<?php echo number_format($originalPrice, 2); ?></span>
                            <span class="detail-sale-price">$<?php echo number_format($currentPrice, 2); ?></span>
                        <?php } else { ?>
                            <span>$<?php echo number_format($currentPrice, 2); ?></span>
                        <?php } ?>
                    </div>
                </div>

                <div class="detail-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <div class="stock-status <?php echo $stock > 0 ? ($stock < 5 ? 'low-stock' : 'in-stock') : 'out-of-stock'; ?>">
                    <?php
                    if ($stock > 0) {
                        echo $stock < 5 ? "Only $stock left in stock!" : "In Stock ($stock available)";
                    } else {
                        echo "Out of Stock";
                    }
                    ?>
                </div>

                <div class="detail-actions">
                    <form method="POST" action="../includes/addToCart.php" style="margin: 0;">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="redirect_to" value="product-detail.php?id=<?= $product['id'] ?>">
                        <button type="submit"
                            class="btn <?= $stock > 0 ? 'btn-primary' : 'btn-disabled' ?>"
                            style="flex: 1; font-size: 1.1rem;"
                            <?= $stock <= 0 ? 'disabled' : '' ?>>
                            <?= $stock > 0 ? 'Add to Cart' : 'Sold Out' ?>
                        </button>
                    </form>
                </div>

                <div class="meta-info">
                    <div class="meta-item">
                        <span class="meta-label">Brand</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?></span>
                    </div>
                    <?php if (!empty($product['created_at'])) { ?>
                        <div class="meta-item">
                            <span class="meta-label">Listed</span>
                            <span class="meta-value"><?php echo date("M d, Y", strtotime($product['created_at'])); ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>
        <!-- Comments Section -->
        <div class="comments-section">
            <h2 class="comments-title">Customer Reviews</h2>

            <!-- Simple Review Form -->
            <div class="comment-form">
                <form onsubmit="event.preventDefault(); alert('Review submitted! This is a demo.');">
                    <div class="form-group">
                        <label class="form-label">Your Name</label>
                        <input type="text" class="form-input" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Review</label>
                        <textarea class="form-textarea" rows="4" placeholder="Share your experience with this product..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            </div>

            <!-- Mock Comments -->
            <div class="comment-list">
                <div class="comment-item">
                    <div class="comment-header">
                        <span class="comment-author">Alice Wonder</span>
                        <span class="comment-date">Feb 1, 2026</span>
                    </div>
                    <p style="color: var(--text-muted);">Absolutely love these running shoes! The cushioning is amazing for long distance runs. Highly recommended.</p>
                </div>

                <div class="comment-item">
                    <div class="comment-header">
                        <span class="comment-author">Mark Runner</span>
                        <span class="comment-date">Jan 28, 2026</span>
                    </div>
                    <p style="color: var(--text-muted);">Good value for the price. Fits a bit tight so maybe order a half size up.</p>
                </div>
            </div>
        </div>


    </div>
    <?php require_once('../includes/footer.php'); ?>
