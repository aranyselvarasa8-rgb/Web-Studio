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

/* Latest payment — the page shows what the DATABASE says, never what the
   URL says, so nobody can reach a "paid" screen by typing an address. */
$st = mysqli_prepare(
    $conn,
    'SELECT id, amount, method, transaction_id, status, paid_at, created_at
     FROM payments
     WHERE booking_id = ? AND user_id = ?
     ORDER BY id DESC
     LIMIT 1'
);
mysqli_stmt_bind_param($st, 'ii', $bookingId, $user['id']);
mysqli_stmt_execute($st);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$payment || $payment['status'] === 'refunded') {
    header('Location: payment.php?id=' . $bookingId);
    exit;
}

if ($payment['status'] === 'failed') {
    header('Location: payment-failed.php?id=' . $bookingId);
    exit;
}

$isPaid   = $payment['status'] === 'paid';
$isBank   = ($payment['method'] ?? '') === 'Bank Transfer';
$bookRef  = ref_no($bookingId);

$waMessage = implode("\n", [
    'Hello Web Studio, I completed my booking advance payment.',
    '',
    'Booking: #' . $bookRef,
    'Amount: ' . money((float) $payment['amount']),
    'Reference: ' . ($payment['transaction_id'] ?: '-'),
    '',
    'Please confirm my booking payment. Thank you!',
]);

$page_title = ($isPaid ? 'Payment Verified' : 'Payment Submitted') . ' | ' . SITE_NAME;

include __DIR__ . '/includes/header.php';

?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow"><?= $isPaid ? 'PAYMENT VERIFIED' : 'PAYMENT SUBMITTED' ?></span>
        <h1><?= $isPaid ? 'Payment received' : 'Payment submitted' ?></h1>
        <p>Booking #<?= $bookRef ?></p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:720px">

        <div class="result-card result-success">

            <div class="result-icon" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>

            <?php if ($isPaid): ?>
                <h2>Your advance is confirmed</h2>
                <p>The studio has verified your payment. See you at the shoot!</p>
            <?php else: ?>
                <h2>Payment request submitted</h2>
                <p><?= $isBank ? 'Your bank transfer details have been submitted. The studio will verify the transfer and update the payment status.' : 'Your Stripe payment has been received and your booking payment status has been updated.' ?></p>
            <?php endif; ?>

            <div class="result-details">
                <div><span>Booking</span><strong>#<?= $bookRef ?> · <?= e($booking['event_type'] ?: 'Photography Session') ?></strong></div>
                <div><span>Event date</span><strong><?= $booking['event_date'] ? e(date('d M Y', strtotime($booking['event_date']))) : '—' ?></strong></div>
                <div><span>Advance amount</span><strong><?= money((float) $payment['amount']) ?></strong></div>
                <?php if (!empty($payment['transaction_id'])): ?>
                    <div><span>Reference</span><strong><?= e($payment['transaction_id']) ?></strong></div>
                <?php endif; ?>
                <div>
                    <span>Status</span>
                    <strong>
                        <span class="booking-status <?= $isPaid ? 'confirmed' : 'pending' ?>">
                            <?= $isPaid ? 'Paid' : 'Pending verification' ?>
                        </span>
                    </strong>
                </div>
            </div>

            <div class="result-actions">
                <a class="btn gold" href="booking-details.php?id=<?= $bookingId ?>">View booking</a>
                <a class="btn outline-dark" href="my-bookings.php">My Bookings</a>
                <?php if (!$isPaid): ?>
                    <a class="btn wa-btn" target="_blank" rel="noopener noreferrer" href="<?= e(whatsapp_link($waMessage)) ?>">
                        <?= whatsapp_icon_svg(18) ?> Tell the studio on WhatsApp
                    </a>
                <?php endif; ?>
            </div>

        </div>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
