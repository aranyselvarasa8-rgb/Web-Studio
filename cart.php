<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login('cart.php');

$user = current_user();

$page_title = 'My Cart | ' . SITE_NAME;

$lines = cart_lines((int) $user['id']);
$flash = flash_get();

$total     = 0.0;
$itemCount = 0;
$canCheckout = count($lines) > 0;

foreach ($lines as $line) {
    if (!$line['enough']) {
        $canCheckout = false;
        continue;
    }
    $total     += $line['subtotal'];
    $itemCount += $line['quantity'];
}

include __DIR__ . '/includes/header.php';

?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow">SHOPPING CART</span>
        <h1>My Cart</h1>
        <p>Review your memory products before checkout.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:1080px">

        <?php if ($flash): ?>
            <div class="alert <?= $flash['type'] === 'success' ? 'success-alert' : 'error-alert' ?>" style="margin-bottom:22px">
                <div><?= e($flash['message']) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$lines): ?>

            <div class="empty-bookings">
                <h3>Your cart is empty</h3>
                <p>Browse our frames and albums and add your favourites.</p>
                <a class="btn gold" href="products.php">Shop Products</a>
            </div>

        <?php else: ?>

            <div class="cart-layout">

                <div class="cart-list">

                    <?php foreach ($lines as $line): ?>

                        <article class="cart-item <?= $line['enough'] ? '' : 'cart-item-problem' ?>">

                            <div class="cart-item-img">
                                <img
                                    src="<?= e(product_image_url($line)) ?>"
                                    alt="<?= e($line['name']) ?>"
                                    loading="lazy"
                                >
                            </div>

                            <div class="cart-item-info">
                                <h3><?= e($line['name']) ?></h3>
                                <p class="cart-unit"><?= money($line['price']) ?> each</p>

                                <?php if (!$line['available']): ?>
                                    <p class="cart-warn">This product is currently unavailable. Please remove it to continue.</p>
                                <?php elseif (!$line['enough']): ?>
                                    <p class="cart-warn">Only <?= $line['stock'] ?> left in stock — please lower the quantity.</p>
                                <?php endif; ?>

                                <div class="cart-item-actions">

                                    <?php if ($line['available']): ?>
                                        <form method="post" action="update-cart.php" class="cart-qty-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="item_id" value="<?= $line['item_id'] ?>">
                                            <label class="cart-qty-label">
                                                Qty
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    value="<?= $line['quantity'] ?>"
                                                    min="1"
                                                    max="<?= $line['stock'] ?>"
                                                >
                                            </label>
                                            <button type="submit" class="cart-link-btn">Update</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="remove-cart.php">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="item_id" value="<?= $line['item_id'] ?>">
                                        <button type="submit" class="cart-link-btn cart-remove">Remove</button>
                                    </form>

                                </div>
                            </div>

                            <div class="cart-item-total">
                                <?= $line['enough'] ? money($line['subtotal']) : '—' ?>
                            </div>

                        </article>

                    <?php endforeach; ?>

                    <a class="back-link" href="products.php">← Continue shopping</a>

                </div>

                <aside class="pay-summary-card cart-summary">
                    <span class="eyebrow">ORDER SUMMARY</span>
                    <h2>Your cart</h2>

                    <div class="pay-summary-row">
                        <span>Items</span>
                        <strong><?= $itemCount ?></strong>
                    </div>

                    <div class="pay-summary-advance">
                        <span>Total</span>
                        <strong><?= money($total) ?></strong>
                    </div>

                    <?php if ($canCheckout): ?>
                        <a class="btn gold cart-checkout-btn" href="checkout.php">Proceed to Checkout →</a>
                    <?php else: ?>
                        <p class="cart-summary-note">Fix the items marked above to continue to checkout.</p>
                    <?php endif; ?>
                </aside>

            </div>

        <?php endif; ?>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
