<?php
// Ensure auth functions are available
if (!function_exists('isLoggedIn')) {
    require_once(__DIR__ . '/auth.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fa;
            color: #212529;
        }

        /* Navigation Bar */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 70px;
        }

        .nav-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-logo:hover {
            opacity: 0.9;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-greeting {
            color: white;
            font-weight: 500;
            display: none;
        }

        .btn-nav {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary-nav {
            background: white;
            color: #667eea;
        }

        .btn-primary-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary-nav {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-secondary-nav:hover {
            background: white;
            color: #667eea;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }

        /* Cart Badge */
        .cart-link {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .cart-badge {
                position: static;
                display: inline-flex;
                margin-left: 5px;
            }
        }

        /* Responsive */
        @media (min-width: 768px) {
            .user-greeting {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                position: fixed;
                left: -100%;
                top: 70px;
                flex-direction: column;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                width: 100%;
                text-align: center;
                transition: 0.3s;
                box-shadow: 0 10px 27px rgba(0, 0, 0, 0.05);
                padding: 20px 0;
                gap: 0;
            }

            .nav-menu.active {
                left: 0;
            }

            .nav-link {
                display: block;
                padding: 15px;
                width: 100%;
            }

            .nav-user {
                flex-direction: column;
                width: 100%;
                padding: 15px;
                gap: 10px;
            }

            .btn-nav {
                width: 90%;
            }

            .mobile-menu-toggle {
                display: block;
            }
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="products.php" class="nav-logo">
                <span>🛒</span>
                <span>ShopHub</span>
            </a>

            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                ☰
            </button>

            <ul class="nav-menu" id="navMenu">
                <li><a href="products.php" class="nav-link">Products</a></li>
                <li>
                    <a href="cart.php" class="nav-link cart-link">
                        🛒 Cart
                        <?php
                        require_once(__DIR__ . '/cart_functions.php');
                        $cart_count = getCartCount();
                        if ($cart_count > 0):
                        ?>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="profile.php" class="nav-link">Profile</a></li>
                <?php endif; ?>

                <li class="nav-user">
                    <?php if (isLoggedIn()): ?>
                        <span class="user-greeting">Hi, <?= htmlspecialchars(getCurrentUsername()) ?>!</span>
                        <a href="logout.php" class="btn-nav btn-secondary-nav">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-nav btn-secondary-nav">Login</a>
                        <a href="register.php" class="btn-nav btn-primary-nav">Sign Up</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const navMenu = document.getElementById('navMenu');

        mobileMenuToggle?.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-container')) {
                navMenu.classList.remove('active');
            }
        });

        function closeToast() {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.classList.add('hiding');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }

        // Auto-hide toast after 3 seconds
        window.addEventListener('DOMContentLoaded', () => {
            const toast = document.getElementById('toast');
            if (toast) {
                setTimeout(() => {
                    closeToast();
                }, 3000);
            }
        });
    </script>