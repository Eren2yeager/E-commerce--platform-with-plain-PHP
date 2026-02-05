<?php
require_once('config/database.php');

// Get some statistics for the landing page
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='customer'"))['count'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];

// Get featured products (latest 6 products)
$featured_sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT 6";
$featured_products = mysqli_query($conn, $featured_sql);

// Get categories
$categories_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL LIMIT 6";
$categories = mysqli_query($conn, $categories_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopHub - Your Ultimate Shopping Destination</title>
    <link rel="icon" href="assets/icons/favicon.ico" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: #333;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s;
        }

        .navbar.scrolled {
            background: white;
            box-shadow: 0 2px 30px rgba(0, 0, 0, 0.15);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 1rem;
            }
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #667eea;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            min-height: 90vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -200px;
            right: -200px;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-image {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .hero-image-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            animation: float 6s ease-in-out infinite;
            max-width: 450px;
            margin: 0 auto;
        }

        .hero-image-item {
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s;
        }

        .hero-image-item:hover {
            transform: scale(1.05);
        }

        .hero-image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Stats Section */
        .stats {
            padding: 4rem 2rem;
            background: white;
        }

        @media (max-width: 768px) {
            .stats {
                padding: 3rem 1rem;
            }
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }

        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

        @media (max-width: 768px) {
            .features {
                padding: 3rem 1rem;
            }
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .section-header p {
            font-size: 1.2rem;
            color: #666;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Categories Section */
        .categories {
            padding: 6rem 2rem;
            background: white;
        }

        @media (max-width: 768px) {
            .categories {
                padding: 3rem 1rem;
            }
        }

        .categories-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .category-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }

        .category-card:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        /* Products Section */
        .products {
            padding: 6rem 2rem;
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

        @media (max-width: 768px) {
            .products {
                padding: 3rem 1rem;
            }
        }

        .products-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-category {
            font-size: 0.85rem;
            color: #667eea;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #28a745;
        }

        /* CTA Section */
        .cta {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-align: center;
            color: white;
        }

        @media (max-width: 768px) {
            .cta {
                padding: 3rem 1rem;
            }
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 4rem 2rem 2rem;
        }

        @media (max-width: 768px) {
            .footer {
                padding: 3rem 1rem 2rem;
            }
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.75rem;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
        }

        /* Responsive */
        @media (max-width: 968px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-image {
                order: -1;
            }

            .hero-image-grid {
                max-width: 350px;
            }

            .nav-links {
                display: none;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .footer-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero {
                min-height: auto;
                padding: 2rem 0;
            }

            .hero-container {
                padding: 2rem 1rem;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }

            .hero-image-grid {
                max-width: 280px;
            }

            .section-header h2 {
                font-size: 2rem;
            }

            .section-header p {
                font-size: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .features-grid {
                gap: 1.5rem;
            }

            .feature-card {
                padding: 2rem;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .cta h2 {
                font-size: 2rem;
            }

            .cta p {
                font-size: 1.1rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 1.5rem;
            }

            .hero-content h1 {
                font-size: 1.75rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .hero-image-grid {
                max-width: 220px;
            }

            .stat-icon {
                font-size: 2rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }

            .feature-icon {
                font-size: 2.5rem;
            }

            .feature-card h3 {
                font-size: 1.2rem;
            }

            .section-header h2 {
                font-size: 1.75rem;
            }

            .cta h2 {
                font-size: 1.75rem;
            }

            .cta p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="logo">
                <span>🛒</span>
                <span>ShopHub</span>
            </div>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#categories">Categories</a></li>
                <li><a href="#products">Products</a></li>
                <li><a href="pages/products.php" class="btn btn-primary">Shop Now</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Welcome to ShopHub</h1>
                <p>Your ultimate shopping destination with thousands of products, amazing deals, and seamless shopping experience.</p>
                <div class="hero-buttons">
                    <a href="pages/products.php" class="btn btn-primary">Start Shopping</a>
                    <a href="pages/register.php" class="btn btn-secondary">Create Account</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-image-grid">
                    <div class="hero-image-item">
                        <img src="https://images.unsplash.com/photo-1599058917212-d750089bc07e?w=400&h=400&fit=crop" alt="Product 1">
                    </div>
                    <div class="hero-image-item">
                        <img src="https://images.unsplash.com/photo-1527814050087-3793815479db?w=400&h=400&fit=crop" alt="Product 2">
                    </div>
                    <div class="hero-image-item">
                        <img src="https://encrypted-tbn1.gstatic.com/shopping?q=tbn:ANd9GcQKJiloJA8om0UDTvGKY4JqZo6jEMKXcUUEcgoVuS0IWb2V2AtZEuT-4QpX506kR3RdMda7w_iBVs4bPuYbt4nhAvhCLUuj" alt="Product 3">
                    </div>
                    <div class="hero-image-item">
                        <img src="https://encrypted-tbn2.gstatic.com/shopping?q=tbn:ANd9GcQZIfwVtoRzO4dSqRt4MsvGICZRBpfv5oJ6Jaiv6rQUSTJHggWI3_u2kT3pjgJWe_eNX8MW3iQ9z42Sv4oGq1X0J9-0dVF60rLgWFVoHoIjiNx2cPKYNOCW0w" alt="Product 4">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-number"><?= number_format($total_products) ?>+</div>
                <div class="stat-label">Products Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?= number_format($total_users) ?>+</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?= number_format($total_orders) ?>+</div>
                <div class="stat-label">Orders Delivered</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number">4.8</div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2>Why Choose ShopHub?</h2>
            <p>Experience the best online shopping with our amazing features</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3>Free Shipping</h3>
                <p>Enjoy free shipping on all orders. No minimum purchase required!</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure Payment</h3>
                <p>Your transactions are 100% secure with our encrypted payment system.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💳</div>
                <h3>Cash on Delivery</h3>
                <p>Pay when you receive your order. Shop with confidence!</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Easy Returns</h3>
                <p>Not satisfied? Return within 30 days for a full refund.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Fast Delivery</h3>
                <p>Get your orders delivered quickly with our express shipping.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>24/7 Support</h3>
                <p>Our customer support team is always here to help you.</p>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories" id="categories">
        <div class="section-header">
            <h2>Shop by Category</h2>
            <p>Browse through our wide range of product categories</p>
        </div>
        <div class="categories-grid">
            <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                <a href="pages/products.php?category=<?= urlencode($category['category']) ?>" class="category-card">
                    <?= htmlspecialchars($category['category']) ?>
                </a>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="products" id="products">
        <div class="section-header">
            <h2>Featured Products</h2>
            <p>Check out our latest and most popular products</p>
        </div>
        <div class="products-grid">
            <?php while ($product = mysqli_fetch_assoc($featured_products)): ?>
                <a href="pages/product-detail.php?id=<?= $product['id'] ?>" class="product-card">
                    <img src="<?= htmlspecialchars($product['image'] ?? 'assets/placeholder.png') ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="product-image"
                         onerror="this.src='assets/placeholder.png'">
                    <div class="product-info">
                        <?php if (!empty($product['category'])): ?>
                            <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                        <?php endif; ?>
                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        <div style="text-align: center; margin-top: 3rem;">
            <a href="pages/products.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2.5rem;">
                View All Products →
            </a>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <h2>Ready to Start Shopping?</h2>
        <p>Join thousands of happy customers and experience the best online shopping!</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="pages/register.php" class="btn btn-secondary" style="font-size: 1.1rem; padding: 1rem 2.5rem;">
                Create Free Account
            </a>
            <a href="pages/products.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 1.1rem; padding: 1rem 2.5rem;">
                Browse Products
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>🛒 ShopHub</h3>
                <p style="color: rgba(255, 255, 255, 0.7); line-height: 1.6;">
                    Your trusted online shopping destination with quality products and excellent service.
                </p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="pages/products.php">Shop</a></li>
                    <li><a href="pages/cart.php">Cart</a></li>
                    <li><a href="pages/orders.php">My Orders</a></li>
                    <li><a href="pages/profile.php">Profile</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Track Order</a></li>
                    <li><a href="#">Returns</a></li>
                    <li><a href="#">Shipping Info</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul>
                    <li>📧 support@shophub.com</li>
                    <li>📞 +1 (555) 123-4567</li>
                    <li>📍 123 Shopping St, NY 10001</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 ShopHub. All rights reserved. Made with ❤️ for shoppers worldwide.</p>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
