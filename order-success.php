<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login('cart.php');

ensure_order_payment_method_column($conn);

$user = current_user();

$orderId = (int) ($_GET['id'] ?? 0);

/* Only the customer who placed the order can see it */
$st = mysqli_prepare(
    $conn,
    'SELECT id, total_amount, payment_status, payment_method, shipping_address, phone, status, created_at
     FROM orders
     WHERE id = ? AND user_id = ?
     LIMIT 1'
);
mysqli_stmt_bind_param($st, 'ii', $orderId, $user['id']);
mysqli_stmt_execute($st);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$order) {
    header('Location: cart.php');
    exit;
}

$st = mysqli_prepare(
    $conn,
    'SELECT product_name, price, quantity
     FROM order_items
     WHERE order_id = ?
     ORDER BY id'
);
mysqli_stmt_bind_param($st, 'i', $orderId);
mysqli_stmt_execute($st);
$items = [];
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) {
    $items[] = $row;
}
mysqli_stmt_close($st);

$orderRef  = ref_no($orderId);
$total     = (float) $order['total_amount'];
$method    = $order['payment_method'] ?: 'Bank Transfer';
$isBank    = $method === 'Bank Transfer';
$isStripe = $method === 'Stripe';
$isCash    = $method === 'Cash on Delivery';
$isPaid    = $order['payment_status'] === 'paid';

/* Pre-filled WhatsApp message to the studio, built from the database */
$waLines = [
    'Hello Web Studio, I just placed an order on your website.',
    '',
    'Order: #' . $orderRef,
];

foreach ($items as $it) {
    $waLines[] = '- ' . $it['product_name'] . ' x ' . $it['quantity'];
}

$waLines[] = 'Total: ' . money($total);
$waLines[] = '';
$waLines[] = 'Payment method: ' . $method;
$waLines[] = '';
$waLines[] = $isBank
    ? 'I will send my bank transfer slip here. Thank you!'
    : ($isCash
        ? 'I will pay in cash on delivery / at the studio. Thank you!'
        : 'Please complete the Stripe payment from the checkout page. Thank you!');

$waLink = whatsapp_link(implode("\n", $waLines));

$page_title = 'Order Placed | ' . SITE_NAME;

include __DIR__ . '/includes/header.php';

?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow">ORDER PLACED</span>
        <h1>Thank you, <?= e(explode(' ', trim($user['name']))[0]) ?>!</h1>
        <p>Your order #<?= $orderRef ?> has been received.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:1080px">

        <div class="alert success-alert" style="margin-bottom:26px">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div>
                <?php if ($isStripe): ?>
                    <?php if ($isPaid): ?>
                        Payment received via Stripe. Your order is confirmed — thank you!
                    <?php else: ?>
                        We're confirming your Stripe payment. This page will update shortly; you'll also get a WhatsApp message once it's confirmed.
                    <?php endif; ?>
                <?php elseif ($isBank): ?>
                    Your items are reserved. Please transfer the total below and send your payment slip on WhatsApp.
                    The studio will confirm your order once the payment is verified.
                <?php elseif ($isCash): ?>
                    Your items are reserved. Pay in cash when your order is delivered, or in person at the studio.
                    The studio will contact you on WhatsApp to confirm delivery.
                <?php else: ?>
                    Your items are reserved. Send us a WhatsApp message and the studio will share a secure
                    <?= e(strtolower($method)) ?> link to complete your payment.
                <?php endif; ?>
            </div>
        </div>

        <div class="pay-wrap">

            <div class="pay-summary-card">
                <span class="eyebrow">ORDER #<?= $orderRef ?></span>
                <h2>Order summary</h2>
                <p class="pay-date"><?= e(date('d M Y, h:i A', strtotime($order['created_at']))) ?></p>

                <?php foreach ($items as $it): ?>
                    <div class="co-line">
                        <span><?= e($it['product_name']) ?> <em>× <?= (int) $it['quantity'] ?></em></span>
                        <strong><?= money((float) $it['price'] * (int) $it['quantity']) ?></strong>
                    </div>
                <?php endforeach; ?>

                <div class="pay-summary-advance">
                    <span><?= $isBank ? 'Total to transfer' : 'Total to pay' ?></span>
                    <strong><?= money($total) ?></strong>
                </div>

                <div class="pay-summary-badge">
                    <?= $isPaid ? '✓ Paid' : '⏳ Awaiting payment' ?> · <?= e($method) ?>
                </div>
            </div>

            <div class="pay-form-card">

                <?php if ($isStripe): ?>
                    <h3>Stripe</h3>
                    <?php if ($isPaid): ?>
                        <p class="hint">Your payment of <?= money($total) ?> was received via Stripe. We're preparing your order.</p>
                    <?php elseif (isset($_GET['cancelled'])): ?>
                        <p class="hint">Payment was cancelled. Your order is still reserved — you can try again.</p>
                        <a class="btn gold pay-submit-btn" href="stripe-checkout.php?type=order&amp;id=<?= $orderId ?>">Try payment again</a>
                    <?php else: ?>
                        <p class="hint">
                            Still confirming your payment with Stripe — this can take a minute. If it doesn't update,
                            message us on WhatsApp with your order number and we'll check it manually.
                        </p>
                        <a
                            class="btn wa-btn pay-submit-btn"
                            data-wa-link href="<?= e($waLink) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <?= whatsapp_icon_svg(20) ?>
                            Message us about this order
                        </a>
                    <?php endif; ?>

                <?php elseif ($isBank): ?>
                <h3>Bank transfer details</h3>
                <p class="hint">Transfer the total to the account below, then send us the slip on WhatsApp quoting your order number.</p>

                <?= bank_boxes_html($total, 'Order #' . $orderRef) ?>

                <?php elseif ($isCash): ?>
                <h3>Cash on Delivery</h3>
                <p class="hint">Pay in cash when your order is delivered, or in person at the studio. No advance payment needed online — just confirm your order with us on WhatsApp.</p>

                <div class="bank-box">
                    <div class="bank-row">
                        <span class="bank-row-label">Amount to pay</span>
                        <span class="bank-row-value"><?= money($total) ?></span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-row-label">Reference</span>
                        <span class="bank-row-value">Order #<?= $orderRef ?></span>
                    </div>
                </div>

                <?php else: ?>
                <h3><?= e($method) ?></h3>
                <p class="hint">
                    <?php if ($method === 'Credit / Debit Card'): ?>
                        Card payments are confirmed with the studio. Tap the button below and we will send you a secure card payment link on WhatsApp. Card details are never taken on this website.
                    <?php else: ?>
                        Tap the button below and we will send you a secure payment link (LankaQR / mobile wallet) on WhatsApp quoting your order number.
                    <?php endif; ?>
                </p>
                <div class="bank-box">
                    <div class="bank-row">
                        <span class="bank-row-label">Amount to pay</span>
                        <span class="bank-row-value"><?= money($total) ?></span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-row-label">Reference</span>
                        <span class="bank-row-value">Order #<?= $orderRef ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$isStripe): ?>
                <a
                    class="btn wa-btn pay-submit-btn"
                    data-wa-link href="<?= e($waLink) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?= whatsapp_icon_svg(20) ?>
                    <?php if ($isBank): ?>
                        Send order &amp; payment slip on WhatsApp
                    <?php elseif ($isCash): ?>
                        Confirm order on WhatsApp
                    <?php else: ?>
                        Request payment link on WhatsApp
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <p class="checkout-note">
                    Delivering to: <?= nl2br(e($order['shipping_address'])) ?><br>
                    Phone: <?= e($order['phone']) ?>
                </p>

                <div style="margin-top:18px">
                    <a class="back-link" href="products.php">← Continue shopping</a>
                </div>

            </div>
        </div>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>