<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'PLSPCART - Your Campus Marketplace'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="assets/css/homepage.css">
    <style>
        .navbar {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .navbar-brand {
            font-size: 1.25rem;
        }
        .nav-link {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .navbar .container {
            padding-top: 0;
            padding-bottom: 0;
        }
        .navbar .input-group {
            height: 38px;
        }
        .navbar .btn {
            padding: 0.25rem 0.5rem;
        }
        .navbar .form-control {
            height: 38px;
        }
    </style>
    <script src="<?php echo asset_url('js/main.js'); ?>" defer></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-success" href="<?php echo url(''); ?>">PLSPCART</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php'); ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('about.php'); ?>">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('products.php'); ?>">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('contact.php'); ?>">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('buy-and-sell.php'); ?>">Sell</a>
                    </li>
                </ul>

                <form class="d-flex me-3" action="<?php echo url('products.php'); ?>" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search products..." aria-label="Search">
                        <button class="btn btn-outline-success" type="submit">
                            <i class="ri-search-2-line"></i>
                        </button>
                    </div>
                </form>

                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo url('admin/dashboard.php'); ?>">Admin</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo url('customer/account.php'); ?>">My Account</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo url('orders.php'); ?>">Orders</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/logout.php'); ?>">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/login.php'); ?>">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/register.php'); ?>">Register</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo url('customer/cart.php'); ?>">
                            <i class="ri-shopping-cart-line fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo isLoggedIn() ? count(getCartItems($_SESSION['user_id'])) : '0'; ?>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Adjust padding-top to match new navbar height -->
    <div style="padding-top: 60px;"></div>