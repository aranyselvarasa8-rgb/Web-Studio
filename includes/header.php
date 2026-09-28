<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';      // so the nav knows if the customer is logged in on every page
require_once __DIR__ . '/functions.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($page_title ?? SITE_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div id="pageLoader" aria-hidden="true">
    <div class="loader-ring">
        <img src="assets/images/logo-wide.png" alt="Web Studio">
    </div>
</div>

<header class="site-header">
    <div class="container nav-wrap">

        <a class="brand" href="index.php" aria-label="Web Studio Home">
            <img src="assets/images/logo-wide.png" alt="Web Studio">
        </a>

        <button
            class="menu-btn"
            type="button"
            aria-label="Open menu"
            onclick="document.querySelector('.nav').classList.toggle('open')"
        >☰</button>

        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <nav class="nav">
            <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>>Home</a>
            <a href="about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>" <?= $currentPage === 'about.php' ? 'aria-current="page"' : '' ?>>About</a>
            <a href="services.php" class="<?= $currentPage === 'services.php' ? 'active' : '' ?>" <?= $currentPage === 'services.php' ? 'aria-current="page"' : '' ?>>Services</a>
            <a href="gallery.php" class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>" <?= $currentPage === 'gallery.php' ? 'aria-current="page"' : '' ?>>Gallery</a>
            <a href="packages.php" class="<?= $currentPage === 'packages.php' ? 'active' : '' ?>" <?= $currentPage === 'packages.php' ? 'aria-current="page"' : '' ?>>Packages</a>
            <a href="products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>" <?= $currentPage === 'products.php' ? 'aria-current="page"' : '' ?>>Products</a>
            <a href="contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>" <?= $currentPage === 'contact.php' ? 'aria-current="page"' : '' ?>>Contact</a>

            <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                <a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>" <?= $currentPage === 'profile.php' ? 'aria-current="page"' : '' ?>>My Profile</a>
                <a href="my-bookings.php" class="<?= $currentPage === 'my-bookings.php' ? 'active' : '' ?>" <?= $currentPage === 'my-bookings.php' ? 'aria-current="page"' : '' ?>>My Bookings</a>
                <a href="calendar.php" class="<?= $currentPage === 'calendar.php' ? 'active' : '' ?>" <?= $currentPage === 'calendar.php' ? 'aria-current="page"' : '' ?>>Calendar</a>
                <a href="logout.php" class="<?= $currentPage === 'logout.php' ? 'active' : '' ?>" <?= $currentPage === 'logout.php' ? 'aria-current="page"' : '' ?>>Logout</a>
            <?php else: ?>
                <a href="login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>" <?= $currentPage === 'login.php' ? 'aria-current="page"' : '' ?>>Login</a>
                <a href="register.php" class="<?= $currentPage === 'register.php' ? 'active' : '' ?>" <?= $currentPage === 'register.php' ? 'aria-current="page"' : '' ?>>Register</a>
            <?php endif; ?>

            <?php $navCartCount = cart_count(); ?>
            <a class="nav-cart" href="cart.php" aria-label="Cart">
                Cart<?php if ($navCartCount > 0): ?><span class="cart-badge"><?= $navCartCount ?></span><?php endif; ?>
            </a>

            <a class="nav-book" href="booking.php">Book Now</a>
        </nav>

    </div>
</header>
