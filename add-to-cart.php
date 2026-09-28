<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$back = safe_back($_POST['back'] ?? '');

/* Only accept form posts */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

/* Must be logged in to have a cart */
if (!is_logged_in() || !current_user()) {
    header('Location: login.php?next=' . urlencode('products.php'));
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: ' . $back);
    exit;
}

$userId    = (int) $_SESSION['user_id'];
$productId = (int) ($_POST['product_id'] ?? 0);
$qty       = max(1, min(10, (int) ($_POST['quantity'] ?? 1)));

/* Product must exist and be active — price/stock always come from the DB */
$st = mysqli_prepare(
    $conn,
    "SELECT id, name, stock
     FROM products
     WHERE id = ? AND status <> 'inactive'
     LIMIT 1"
);
mysqli_stmt_bind_param($st, 'i', $productId);
mysqli_stmt_execute($st);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$product) {
    flash_set('error', 'That product is no longer available.');
    header('Location: ' . $back);
    exit;
}

$stock = (int) $product['stock'];

if ($stock <= 0) {
    flash_set('error', $product['name'] . ' is out of stock.');
    header('Location: ' . $back);
    exit;
}

$cartId = cart_id_for_user($userId);

/* What's already in the cart for this product? */
$st = mysqli_prepare(
    $conn,
    'SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1'
);
mysqli_stmt_bind_param($st, 'ii', $cartId, $productId);
mysqli_stmt_execute($st);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

$current = $existing ? (int) $existing['quantity'] : 0;

if ($current >= $stock) {
    flash_set('error', 'You already have all ' . $stock . ' available ' . $product['name'] . ' in your cart.');
    header('Location: ' . $back);
    exit;
}

$newQty = min($current + $qty, $stock);

if ($existing) {
    $st = mysqli_prepare(
        $conn,
        'UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?'
    );
    mysqli_stmt_bind_param($st, 'iii', $newQty, $cartId, $productId);
} else {
    $st = mysqli_prepare(
        $conn,
        'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)'
    );
    mysqli_stmt_bind_param($st, 'iii', $cartId, $productId, $newQty);
}

mysqli_stmt_execute($st);
mysqli_stmt_close($st);

if ($newQty < $current + $qty) {
    flash_set('success', $product['name'] . ' added — only ' . $stock . ' in stock, so your cart is at the maximum.');
} else {
    flash_set('success', $product['name'] . ' added to your cart.');
}

header('Location: ' . $back);
exit;
