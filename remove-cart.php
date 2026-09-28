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

/* Delete only if the item belongs to this customer's cart */
$st = mysqli_prepare(
    $conn,
    'DELETE ci FROM cart_items ci
     JOIN cart c ON c.id = ci.cart_id
     WHERE ci.id = ? AND c.user_id = ?'
);
mysqli_stmt_bind_param($st, 'ii', $itemId, $userId);
mysqli_stmt_execute($st);
$removed = mysqli_stmt_affected_rows($st) > 0;
mysqli_stmt_close($st);

flash_set(
    $removed ? 'success' : 'error',
    $removed ? 'Item removed from your cart.' : 'That cart item was not found.'
);

header('Location: cart.php');
exit;
