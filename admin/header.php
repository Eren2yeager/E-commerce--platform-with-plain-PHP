<?php
require_once('auth.php');
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/icons/favicon.ico" type="image/x-icon">
    
    <title><?= $page_title ?? 'Admin Panel' ?> - ShopHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Admin Navigation */
        .admin-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .admin-nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 70px;
        }

        .admin-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-logo:hover {
            opacity: 0.9;
        }

        .admin-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            list-style: none;
        }

        .admin-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .admin-menu a:hover,
        .admin-menu a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-username {
            font-weight: 500;
            display: none;
        }

        .btn-logout {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-logout:hover {
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

        /* Main Container */
        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            white-space: nowrap;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsive */
        @media (min-width: 768px) {
            .admin-username {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .admin-nav-container {
                min-height: 60px;
            }

            .admin-logo {
                font-size: 1.2rem;
            }

            .admin-menu {
                position: fixed;
                left: -100%;
                top: 60px;
                flex-direction: column;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                width: 100%;
                text-align: center;
                transition: 0.3s;
                box-shadow: 0 10px 27px rgba(0, 0, 0, 0.05);
                padding: 20px 0;
                gap: 0;
                align-items: stretch;
            }

            .admin-menu.active {
                left: 0;
            }

            .admin-menu a {
                display: flex;
                padding: 15px 20px;
                width: 100%;
                justify-content: center;
            }

            .admin-user {
                flex-direction: column;
                width: 100%;
                padding: 15px 20px;
                gap: 10px;
                align-items: stretch;
            }

            .btn-logout {
                width: 100%;
                text-align: center;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .admin-container {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            /* Make tables scrollable on mobile */
            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }

            /* Stack buttons on mobile */
            .page-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .admin-logo span:last-child {
                display: none;
            }

            .admin-logo::after {
                content: 'Admin';
            }
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="dashboard.php" class="admin-logo">
                <span>🛡️</span>
                <span>ShopHub Admin</span>
            </a>

            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                ☰
            </button>

            <ul class="admin-menu" id="adminMenu">
                <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <span>📊</span> Dashboard
                </a></li>
                <li><a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>">
                    <span>📦</span> Products
                </a></li>
                <li><a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
                    <span>🛒</span> Orders
                </a></li>
                <li><a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                    <span>👥</span> Users
                </a></li>
                <li><a href="../pages/products.php" target="_blank">
                    <span>🏪</span> View Store
                </a></li>

                <li class="admin-user">
                    <span class="admin-username">👤 <?= htmlspecialchars(getAdminUsername()) ?></span>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const adminMenu = document.getElementById('adminMenu');

        mobileMenuToggle?.addEventListener('click', () => {
            adminMenu.classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.admin-nav-container')) {
                adminMenu.classList.remove('active');
            }
        });

        // Close menu when clicking a link
        adminMenu?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                adminMenu.classList.remove('active');
            });
        });
    </script>

    <div class="admin-container">
