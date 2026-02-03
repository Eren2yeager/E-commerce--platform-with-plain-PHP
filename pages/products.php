<?php
// Step 1: Include database connection
// (hint: use require_once - what's the path from pages/ to config/?)
require_once('../config/database.php');
require_once('../includes/auth.php');
require_once('../includes/cart_functions.php');
$page_title = 'Our Products - ShopHub';
// Step 2: Write SQL query to get all products
$sql = "SELECT * FROM products";  // What query gets all products?
// Step 3: Execute query to get all products
$result = mysqli_query($conn, $sql);


?>

    <?php require_once('../includes/header.php'); ?>
    <div class="container">

        <h1>Our Products</h1>

        <div class="products-grid">
            <!-- Your HTML to display products goes here -->
            <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>


                    <?php
                    $isOnSale = !empty($row['sale_price']) && $row['sale_price'] > 0 && $row['sale_price'] < $row['price'];
                    $currentPrice = $isOnSale ? $row['sale_price'] : $row['price'];
                    $originalPrice = $row['price'];
                    $stock = (int)$row['stock'];

                    $isInStock =  $stock > 0;

                    $imagePath = !empty($row['image']) ? $row['image'] : '../assets/placeholder.jpg';

                    ?>

                    


                    <a href="product-detail.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="product-card">
                            <?php if ($isOnSale): ?>
                                <div class="badge">Sale</div>
                            <?php endif; ?>
                            <img class="product-image" src=<?php echo htmlspecialchars($imagePath) ?> alt="" style="">



                            <div class="product-info">
                                <?php if (!empty($row['category'])): ?>
                                    <div class="product-category"><?php echo htmlspecialchars($row['category']); ?></div>
                                <?php endif; ?>

                                <h3 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h3>

                                <p class="product-description">
                                    <?php echo htmlspecialchars($row['description'] ?? ''); ?>
                                </p>



                                <div class="product-footer">
                                    <div class="price-wrapper">
                                        <?php if ($isOnSale): ?>
                                            <span class="original-price">$<?php echo number_format($originalPrice, 2); ?></span>
                                        <?php endif; ?>
                                        <span class="price <?php echo $isOnSale ? 'sale' : ''; ?>">
                                            $<?php echo number_format($currentPrice, 2); ?>
                                        </span>
                                    </div>

                                    <form method="POST" action="../includes/addToCart.php" style="margin: 0;" onclick="event.stopPropagation()">
                                        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="quantity" value="1"> 
                                        <button type="submit" name="add_to_cart"
                                            class="btn <?= $stock > 0 ? 'btn-primary' : 'btn-disabled'; ?>"
                                            <?= $stock <= 0 ? 'disabled' : ''; ?>>
                                            <?= $stock > 0 ? 'Add to Cart' : 'Sold Out'; ?>
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
                    <p>Measurements are being calibrated. Check back soon.</p>
                </div>

            <?php } ?>

        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
