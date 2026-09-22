<?php
/**
 * Shared page header: <head>, nav bar, opens <main>.
 * Include after bootstrap.php. Optionally set $pageTitle before including.
 */
$__user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? SITE_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/site.css">
    <script>
        (function () {
            var saved = localStorage.getItem('daniTheme');
            if (saved === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body class="site-body">
<header>
    <nav class="navbar navbar-expand-lg navbar-dark dani-navbar shadow-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/index.php">
                <img src="/assets/images/logo/DA Logo1.png" alt="Dani Group Logo" class="site-logo me-2">
                <div class="brand-block">
                    <span class="brand-text">Dani Group</span>
                    <small class="brand-subtext">Auto Parts &bull; Services &bull; Delivery</small>
                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link nav-main-link" href="/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link nav-main-link" href="/products.php">Store</a></li>
                    <li class="nav-item"><a class="nav-link nav-main-link" href="/dropper.php">Dropper</a></li>
                    <li class="nav-item"><a class="nav-link nav-main-link" href="/towing.php">Towing</a></li>
                    <li class="nav-item"><a class="nav-link nav-main-link" href="/about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link nav-main-link" href="/contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link nav-main-link" href="/returns.php">Returns</a></li>
                </ul>

                <ul class="navbar-nav align-items-lg-center">
                    <?php if ($__user): ?>
                        <li class="nav-item"><a class="nav-link nav-main-link" href="/cart.php">Cart<?= $__cartCount > 0 ? ' (' . (int) $__cartCount . ')' : '' ?></a></li>
                        <li class="nav-item"><a class="nav-link nav-main-link" href="/orders/index.php">My Orders</a></li>
                        <li class="nav-item"><a class="nav-link nav-main-link" href="/account/dashboard.php">My Account</a></li>
                        <?php if ($__user['role'] === 'Admin'): ?>
                            <li class="nav-item"><a class="nav-link nav-main-link" href="/admin/index.php">Admin</a></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <form action="/account/logout.php" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-dani-outline btn-sm ms-lg-2">Log Out</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link nav-main-link" href="/account/login.php">Log In</a></li>
                        <li class="nav-item"><a class="btn btn-dani-primary btn-sm ms-lg-2" href="/account/register.php">Register</a></li>
                    <?php endif; ?>

                    <button type="button" id="themeToggleBtn" class="theme-toggle-btn" title="Toggle light / dark mode" aria-label="Toggle light and dark mode">
                        <span class="theme-icon-dark">&#127769;</span>
                        <span class="theme-icon-light">&#9728;&#65039;</span>
                    </button>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container my-4 content-wrapper">
