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
    echo "<div style='text-align:center; padding:50px;'><h2>Product not found!</h2><a href='products.php'>Back to products</a></div>";
    exit;
}

// Handle review submission
$review_message = '';
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (isLoggedIn()) {
        $user_id = getCurrentUserId();
        $rating = (int)$_POST['rating'];
        $review_text = trim($_POST['review_text']);
        
        // Validate
        if ($rating >= 1 && $rating <= 5 && !empty($review_text)) {
            // Check if user already reviewed this product
            $check_sql = "SELECT id FROM reviews WHERE product_id = ? AND user_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $id, $user_id);
            mysqli_stmt_execute($check_stmt);
            $existing = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($existing) > 0) {
                // Update existing review
                $update_sql = "UPDATE reviews SET rating = ?, review_text = ?, updated_at = NOW() WHERE product_id = ? AND user_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "isii", $rating, $review_text, $id, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $review_message = "Your review has been updated successfully!";
                } else {
                    $review_error = "Failed to update review. Please try again.";
                }
            } else {
                // Insert new review
                $insert_sql = "INSERT INTO reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "iiis", $id, $user_id, $rating, $review_text);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $review_message = "Thank you for your review!";
                } else {
                    $review_error = "Failed to submit review. Please try again.";
                }
            }
        } else {
            $review_error = "Please provide a valid rating and review text.";
        }
    } else {
        $review_error = "Please login to submit a review.";
    }
}

// Get reviews for this product
$reviews_sql = "SELECT r.*, u.username, u.profile_image 
                FROM reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? 
                ORDER BY r.created_at DESC";
$reviews_stmt = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($reviews_stmt, "i", $id);
mysqli_stmt_execute($reviews_stmt);
$reviews = mysqli_stmt_get_result($reviews_stmt);

// Calculate average rating
$avg_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?";
$avg_stmt = mysqli_prepare($conn, $avg_sql);
mysqli_stmt_bind_param($avg_stmt, "i", $id);
mysqli_stmt_execute($avg_stmt);
$rating_data = mysqli_fetch_assoc(mysqli_stmt_get_result($avg_stmt));
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$review_count = $rating_data['review_count'];

// Check if current user has reviewed
$user_review = null;
if (isLoggedIn()) {
    $user_id = getCurrentUserId();
    $user_review_sql = "SELECT * FROM reviews WHERE product_id = ? AND user_id = ?";
    $user_review_stmt = mysqli_prepare($conn, $user_review_sql);
    mysqli_stmt_bind_param($user_review_stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($user_review_stmt);
    $user_review = mysqli_fetch_assoc(mysqli_stmt_get_result($user_review_stmt));
}

// Price Logic
$isOnSale = !empty($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['price'];
$currentPrice = $isOnSale ? $product['sale_price'] : $product['price'];
$originalPrice = $product['price'];
$stock = (int)$product['stock'];
$imagePath = !empty($product['image']) ? $product['image'] : '../assets/placeholder.png';
$page_title = $product['name'];

?>
<style>
/* Product Detail Page Styles */
.product-detail-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    background: var(--surface);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .product-detail-container {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
}

.detail-image-container {
    position: relative;
    border-radius: var(--radius);
    overflow: hidden;
    background: #f1f5f9;
}

.detail-image {
    width: 100%;
    height: auto;
    object-fit: contain; /* Changed to contain so full product is visible */
    max-height: 500px;
    mix-blend-mode: multiply; /* Optional: helps if images have white bg */
}

.detail-info {
    display: flex;
    flex-direction: column;
}

.detail-category {
    text-transform: uppercase;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.detail-title {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--text-main);
    line-height: 1.1;
}

.detail-price-wrapper {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.detail-original-price {
    text-decoration: line-through;
    color: var(--text-muted);
    font-size: 1.25rem;
    font-weight: 400;
}

.detail-sale-price {
    color: var(--danger);
}

.detail-description {
    color: var(--text-muted);
    line-height: 1.6;
    margin-bottom: 2rem;
    font-size: 1.05rem;
}

.detail-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.meta-info {
    margin-top: auto;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.meta-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
}

.meta-label {
    color: var(--text-muted);
    font-weight: 500;
}

.meta-value {
    font-weight: 600;
    color: var(--text-main);
}

/* Review / Comments Section */
.comments-section {
    background: var(--surface);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.comments-title {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border);
}

.comment-form {
    margin-bottom: 3rem;
    padding: 1.5rem;
    background: var(--background);
    border-radius: var(--radius);
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    font-family: inherit;
    font-size: 1rem;
    transition: var(--transition);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.comment-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.comment-item {
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border);
}

.comment-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.comment-author {
    font-weight: 700;
    color: var(--text-main);
}

.comment-date {
    font-size: 0.85rem;
    color: var(--text-muted);
}
</style>

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
                    <form method="POST" action="../includes/addToCart.php" style="margin: 0;" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit"
                            class="btn <?= $stock > 0 ? 'btn-primary' : 'btn-disabled' ?>"
                            style="flex: 1; font-size: 1.1rem;"
                            <?= $stock <= 0 ? 'disabled' : '' ?>
                            data-product-id="<?= $product['id'] ?>"
                            data-product-name="<?= htmlspecialchars($product['name']) ?>">
                            <?= $stock > 0 ? 'Add to Cart' : 'Sold Out' ?>
                        </button>
                    </form>
                </div>

                <div class="meta-info">
                    <?php if ($review_count > 0): ?>
                        <div class="meta-item" style="padding-bottom: 1rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem;">
                            <span class="meta-label">Customer Rating</span>
                            <span class="meta-value" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="color: #ffc107; font-size: 1.2rem;">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= round($avg_rating) ? '★' : '☆';
                                    }
                                    ?>
                                </span>
                                <span><?= $avg_rating ?> (<?= $review_count ?> review<?= $review_count != 1 ? 's' : '' ?>)</span>
                            </span>
                        </div>
                    <?php endif; ?>
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
        <!-- Reviews Section -->
        <div class="comments-section">
            <h2 class="comments-title">
                Customer Reviews 
                <?php if ($review_count > 0): ?>
                    <span style="color: #ffc107; font-size: 1.2rem; margin-left: 0.5rem;">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= round($avg_rating) ? '★' : '☆';
                        }
                        ?>
                    </span>
                    <span style="font-size: 1rem; color: #666; font-weight: normal;">
                        <?= $avg_rating ?> out of 5 (<?= $review_count ?> review<?= $review_count != 1 ? 's' : '' ?>)
                    </span>
                <?php endif; ?>
            </h2>

            <?php if ($review_message): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #c3e6cb;">
                    ✓ <?= htmlspecialchars($review_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($review_error): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #f5c6cb;">
                    ✗ <?= htmlspecialchars($review_error) ?>
                </div>
            <?php endif; ?>

            <!-- Review Form -->
            <?php if (isLoggedIn()): ?>
                <div class="comment-form">
                    <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">
                        <?= $user_review ? 'Update Your Review' : 'Write a Review' ?>
                    </h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Rating *</label>
                            <div class="star-rating" id="starRating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" 
                                        <?= $user_review && $user_review['rating'] == $i ? 'checked' : '' ?> required>
                                    <label for="star<?= $i ?>" title="<?= $i ?> stars">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Your Review *</label>
                            <textarea name="review_text" class="form-textarea" rows="4" 
                                placeholder="Share your experience with this product..." 
                                required><?= $user_review ? htmlspecialchars($user_review['review_text']) : '' ?></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary">
                            <?= $user_review ? 'Update Review' : 'Submit Review' ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="comment-form" style="text-align: center; padding: 2rem;">
                    <p style="color: #666; margin-bottom: 1rem;">Please login to write a review</p>
                    <a href="login.php" class="btn btn-primary">Login to Review</a>
                </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <?php if (mysqli_num_rows($reviews) > 0): ?>
                <div class="comment-list">
                    <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                        <div class="comment-item">
                            <div class="comment-header" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                                <img src="<?= !empty($review['profile_image']) ? $review['profile_image'] : '../assets/placeholder.png' ?>" 
                                     alt="<?= htmlspecialchars($review['username']) ?>"
                                     style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e0e0;"
                                     onerror="this.src='../assets/placeholder.png'">
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span class="comment-author"><?= htmlspecialchars($review['username']) ?></span>
                                        <span class="comment-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                                    </div>
                                    <div style="color: #ffc107; font-size: 1rem;">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $review['rating'] ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <p style="color: var(--text-muted); line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                            </p>
                            <?php if ($review['updated_at'] != $review['created_at']): ?>
                                <p style="font-size: 0.85rem; color: #999; margin-top: 0.5rem; font-style: italic;">
                                    (Updated on <?= date('M d, Y', strtotime($review['updated_at'])) ?>)
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #999;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                    <h3>No reviews yet</h3>
                    <p>Be the first to review this product!</p>
                </div>
            <?php endif; ?>
        </div>


    </div>
    
    <script>
    // AJAX Add to Cart for Product Detail Page
    (function() {
        const cartForm = document.querySelector('.add-to-cart-form');
        
        if (cartForm) {
            cartForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const button = this.querySelector('button[type="submit"]');
                const productId = button.dataset.productId;
                const originalText = button.textContent;
                
                button.disabled = true;
                button.textContent = 'Adding...';
                
                try {
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
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        updateCartBadge(data.cart_count);
                    } else {
                        showToast(data.message, 'error');
                    }
                    
                } catch (error) {
                    console.error('Add to cart error:', error);
                    showToast('Failed to add to cart. Please try again.', 'error');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        }
        
        function showToast(message, type = 'success') {
            const existingToast = document.getElementById('dynamicToast');
            if (existingToast) existingToast.remove();
            
            const toast = document.createElement('div');
            toast.id = 'dynamicToast';
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.remove()">×</button>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function updateCartBadge(count) {
            const cartBadge = document.querySelector('.cart-badge');
            
            if (count > 0) {
                if (cartBadge) {
                    cartBadge.textContent = count;
                } else {
                    const cartLink = document.querySelector('.cart-link');
                    if (cartLink) {
                        const badge = document.createElement('span');
                        badge.className = 'cart-badge';
                        badge.textContent = count;
                        cartLink.appendChild(badge);
                    }
                }
            } else {
                if (cartBadge) cartBadge.remove();
            }
        }
    })();
    </script>
    
    <style>
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

    /* Star Rating */
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 0.25rem;
        font-size: 2rem;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }

    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: #ffc107;
    }
    </style>
    
    <?php require_once('../includes/footer.php'); ?>
