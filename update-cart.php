<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

if (!is_logged_in() || !current_user()) {
    header('Location: login.php?next=' . urlencode('cart.php'));
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: cart.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$itemId = (int) ($_POST['item_id'] ?? 0);
$qty    = (int) ($_POST['quantity'] ?? 1);

/* The item must belong to THIS customer's cart */
$st = mysqli_prepare(
    $conn,
    'SELECT ci.id, p.name, p.stock, p.status
     FROM cart_items ci
     JOIN cart c ON c.id = ci.cart_id
     JOIN products p ON p.id = ci.product_id
     WHERE ci.id = ? AND c.user_id = ?
     LIMIT 1'
);
mysqli_stmt_bind_param($st, 'ii', $itemId, $userId);
mysqli_stmt_execute($st);
$item = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$item) {
    flash_set('error', 'That cart item was not found.');
    header('Location: cart.php');
    exit;
}

/* 0 or less = remove */
if ($qty <= 0) {
    $st = mysqli_prepare($conn, 'DELETE FROM cart_items WHERE id = ?');
    mysqli_stmt_bind_param($st, 'i', $itemId);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);

    flash_set('success', $item['name'] . ' removed from your cart.');
    header('Location: cart.php');
    exit;
}

$stock = (int) $item['stock'];

if ($item['status'] === 'inactive' || $stock <= 0) {
    flash_set('error', $item['name'] . ' is currently unavailable. Please remove it from your cart.');
    header('Location: cart.php');
    exit;
}

$newQty = min($qty, $stock, 99);

$st = mysqli_prepare($conn, 'UPDATE cart_items SET quantity = ? WHERE id = ?');
mysqli_stmt_bind_param($st, 'ii', $newQty, $itemId);
mysqli_stmt_execute($st);
mysqli_stmt_close($st);

if ($newQty < $qty) {
    flash_set('error', 'Only ' . $stock . ' of ' . $item['name'] . ' in stock — quantity set to ' . $newQty . '.');
} else {
    flash_set('success', 'Cart updated.');
}

header('Location: cart.php');
exit;
