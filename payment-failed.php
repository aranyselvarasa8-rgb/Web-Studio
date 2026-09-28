<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login('my-bookings.php');

$user      = current_user();
$bookingId = (int) ($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: my-bookings.php');
    exit;
}

/* This customer's booking */
$st = mysqli_prepare(
    $conn,
    'SELECT id, name, event_type, event_date, total_amount, status
     FROM bookings
     WHERE id = ? AND user_id = ?
     LIMIT 1'
);
mysqli_stmt_bind_param($st, 'ii', $bookingId, $user['id']);
mysqli_stmt_execute($st);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$booking) {
    header('Location: my-bookings.php');
    exit;
}

/* Latest payment */
$st = mysqli_prepare(
    $conn,
    'SELECT id, amount, method, transaction_id, status, created_at
     FROM payments
     WHERE booking_id = ? AND user_id = ?
     ORDER BY id DESC
     LIMIT 1'
);
mysqli_stmt_bind_param($st, 'ii', $bookingId, $user['id']);
mysqli_stmt_execute($st);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

/* This page only makes sense when the studio marked the payment as failed */
if (!$payment) {
    header('Location: payment.php?id=' . $bookingId);
    exit;
}

if ($payment['status'] === 'paid' || $payment['status'] === 'pending') {
    header('Location: payment-success.php?id=' . $bookingId);
    exit;
}

if ($payment['status'] !== 'failed') {
    header('Location: payment.php?id=' . $bookingId);
    exit;
}

$bookRef = ref_no($bookingId);

$waMessage = implode("\n", [
    'Hello Web Studio, my advance payment for a booking was marked as failed.',
    '',
    'Booking: #' . $bookRef,
    'Amount: ' . money((float) $payment['amount']),
    'Reference: ' . ($payment['transaction_id'] ?: '-'),
    '',
    'Could you please check it? Thank you!',
]);

$page_title = 'Payment Failed | ' . SITE_NAME;

include __DIR__ . '/includes/header.php';

?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow">PAYMENT</span>
        <h1>Payment could not be verified</h1>
        <p>Booking #<?= $bookRef ?></p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:720px">

        <div class="result-card result-failed">

            <div class="result-icon" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>

            <h2>Your payment was not accepted</h2>
            <p>Stripe did not complete the payment. Your booking is still held — you can try Stripe again.</p>

            <div class="result-details">
                <div><span>Booking</span><strong>#<?= $bookRef ?> · <?= e($booking['event_type'] ?: 'Photography Session') ?></strong></div>
                <div><span>Event date</span><strong><?= $booking['event_date'] ? e(date('d M Y', strtotime($booking['event_date']))) : '—' ?></strong></div>
                <div><span>Advance amount</span><strong><?= money((float) $payment['amount']) ?></strong></div>
                <?php if (!empty($payment['transaction_id'])): ?>
                    <div><span>Reference used</span><strong><?= e($payment['transaction_id']) ?></strong></div>
                <?php endif; ?>
                <div><span>Status</span><strong><span class="booking-status cancelled">Failed</span></strong></div>
            </div>

            <div class="result-help">
                <strong>Common reasons</strong>
                <ul>
                    <li>The The Stripe payment was cancelled</li>
                    <li>Stripe declined or could not complete the transaction</li>
                    <li>The Stripe account or checkout credentials are not configured correctly</li>
                </ul>
            </div>

            <div class="result-actions">
                <a class="btn gold" href="payment.php?id=<?= $bookingId ?>">Try again</a>
                <a class="btn wa-btn" target="_blank" rel="noopener noreferrer" href="<?= e(whatsapp_link($waMessage)) ?>">
                    <?= whatsapp_icon_svg(18) ?> Ask the studio on WhatsApp
                </a>
                <a class="btn outline-dark" href="my-bookings.php">My Bookings</a>
            </div>

        </div>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
