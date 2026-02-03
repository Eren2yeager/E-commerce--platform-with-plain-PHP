 <?php
require_once(__DIR__ . '/toast.php');
showToast();
?>
<!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <!-- Footer Top -->
            <div class="footer-top">
                <div class="footer-section">
                    <h3 class="footer-title">
                        <span>🛒</span> ShopHub
                    </h3>
                    <p class="footer-description">
                        Your one-stop destination for quality products at amazing prices. Shop with confidence!
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Facebook">📘</a>
                        <a href="#" class="social-link" aria-label="Twitter">🐦</a>
                        <a href="#" class="social-link" aria-label="Instagram">📷</a>
                        <a href="#" class="social-link" aria-label="LinkedIn">💼</a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4 class="footer-heading">Shop</h4>
                    <ul class="footer-links">
                        <li><a href="products.php">All Products</a></li>
                        <li><a href="products.php?category=Electronics">Electronics</a></li>
                        <li><a href="products.php?category=Sports">Sports</a></li>
                        <li><a href="products.php?category=Home">Home & Kitchen</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4 class="footer-heading">Account</h4>
                    <ul class="footer-links">
                        <?php if (isLoggedIn()): ?>
                            <li><a href="profile.php">My Profile</a></li>
                            <li><a href="orders.php">Order History</a></li>
                            <li><a href="cart.php">Shopping Cart</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4 class="footer-heading">Support</h4>
                    <ul class="footer-links">
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping Info</a></li>
                        <li><a href="#">Returns</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4 class="footer-heading">Newsletter</h4>
                    <p class="footer-newsletter-text">Subscribe to get special offers and updates!</p>
                    <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Thanks for subscribing!');">
                        <input type="email" placeholder="Your email" class="newsletter-input" required>
                        <button type="submit" class="newsletter-btn">Subscribe</button>
                    </form>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> ShopHub. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <span>•</span>
                    <a href="#">Terms of Service</a>
                    <span>•</span>
                    <a href="#">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
            margin-top: 4rem;
            padding: 3rem 0 1rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-top {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(236, 240, 241, 0.1);
        }

        .footer-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
        }

        .footer-description {
            color: #bdc3c7;
            line-height: 1.6;
            font-size: 0.9rem;
        }

        .footer-heading {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .footer-links a:hover {
            color: #3498db;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #3498db;
            transform: translateY(-3px);
        }

        .footer-newsletter-text {
            color: #bdc3c7;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .newsletter-form {
            display: flex;
            gap: 0.5rem;
        }

        .newsletter-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
        }

        .newsletter-input::placeholder {
            color: #95a5a6;
        }

        .newsletter-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.15);
        }

        .newsletter-btn {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .newsletter-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            font-size: 0.9rem;
            color: #95a5a6;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-bottom-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .footer-bottom-links a {
            color: #95a5a6;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a:hover {
            color: #3498db;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .footer-top {
                grid-template-columns: 1fr;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .newsletter-form {
                flex-direction: column;
            }

            .newsletter-btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .footer {
                padding: 2rem 0 1rem;
            }

            .social-links {
                justify-content: center;
            }
        }
    </style>
</body>
</html>