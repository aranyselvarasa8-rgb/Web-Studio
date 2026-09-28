<?php

require_once __DIR__ . '/includes/auth.php';

require_login('my-bookings.php');

$user = current_user();

$id = (int) ($_GET['id'] ?? 0);


/* Get booking details */

$st = mysqli_prepare(
    $conn,
    "SELECT
        b.*,
        GROUP_CONCAT(
            CONCAT(
                bs.item_name,
                ' — Rs. ',
                FORMAT(bs.price, 2)
            )
            ORDER BY bs.id
            SEPARATOR '||'
        ) AS items
     FROM bookings b
     LEFT JOIN booking_services bs
        ON bs.booking_id = b.id
     WHERE b.id = ?
       AND b.user_id = ?
     GROUP BY b.id
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $st,
    'ii',
    $id,
    $user['id']
);

mysqli_stmt_execute($st);

$booking = mysqli_fetch_assoc(
    mysqli_stmt_get_result($st)
);

mysqli_stmt_close($st);


/* Check booking */

if (!$booking) {

    http_response_code(404);

    die('Booking not found.');
}


$page_title =
    'Booking #' .
    str_pad((string) $id, 4, '0', STR_PAD_LEFT) .
    ' | ' .
    SITE_NAME;


require __DIR__ . '/includes/header.php';

?>


<!-- Page Banner -->

<section class="page-banner">

    <div class="container">

        <span class="eyebrow">
            BOOKING DETAILS
        </span>

        <h1>
            Booking #<?= str_pad((string) $id, 4, '0', STR_PAD_LEFT) ?>
        </h1>

        <p>
            Your booking request and current status.
        </p>

    </div>

</section>


<!-- Booking Details -->

<section class="section">

    <div class="container details-wrap">


        <!-- Back Link -->

        <a
            class="back-link"
            href="my-bookings.php"
        >
            ← Back to My Bookings
        </a>


        <div class="details-card">


            <!-- Details Header -->

            <div class="details-header">

                <div>

                    <span class="eyebrow">
                        WEB STUDIO
                    </span>

                    <h2>
                        <?= e($booking['event_type'] ?: 'Photography Booking') ?>
                    </h2>

                </div>


                <span
                    class="booking-status <?= e($booking['status']) ?>"
                >
                    <?= ucfirst(e($booking['status'])) ?>
                </span>

            </div>


            <!-- Details Grid -->

            <div class="details-grid">


                <div>

                    <small>Customer</small>

                    <strong>
                        <?= e($booking['name']) ?>
                    </strong>

                </div>


                <div>

                    <small>Phone</small>

                    <strong>
                        <?= e($booking['phone']) ?>
                    </strong>

                </div>


                <div>

                    <small>Email</small>

                    <strong>
                        <?= e($booking['email'] ?: '—') ?>
                    </strong>

                </div>


                <div>

                    <small>Event date</small>

                    <strong>
                        <?= $booking['event_date']
                            ? date(
                                'd M Y',
                                strtotime($booking['event_date'])
                            )
                            : '—'
                        ?>
                    </strong>

                </div>


                <div>

                    <small>
                        Selected service / package
                    </small>

                    <strong>

                        <?php
                        foreach (
                            explode(
                                '||',
                                $booking['items'] ?: 'Photography'
                            ) as $item
                        ):
                        ?>

                            <?= e($item) ?><br>

                        <?php endforeach; ?>

                    </strong>

                </div>


                <div>

                    <small>Total amount</small>

                    <strong>
                        Rs.
                        <?= number_format(
                            (float) $booking['total_amount'],
                            2
                        ) ?>
                    </strong>

                </div>

            </div>


            <!-- Message -->

            <div class="details-message">

                <small>Message</small>

                <p>
                    <?= nl2br(
                        e(
                            $booking['message']
                            ?: 'No additional message.'
                        )
                    ) ?>
                </p>

            </div>


            <!-- Payment Button -->

            <?php if ($booking['status'] === 'confirmed'): ?>

                <?php
                $paymentCheck = mysqli_prepare(
                    $conn,
                    "SELECT status FROM payments
                     WHERE booking_id = ?
                     ORDER BY id DESC
                     LIMIT 1"
                );
                mysqli_stmt_bind_param($paymentCheck, 'i', $id);
                mysqli_stmt_execute($paymentCheck);
                $latestPayment = mysqli_fetch_assoc(mysqli_stmt_get_result($paymentCheck));
                mysqli_stmt_close($paymentCheck);
                ?>

                <div style="margin-top:18px">

                    <?php if ($latestPayment && $latestPayment['status'] === 'paid'): ?>

                        <div class="alert success-alert">Advance payment: <strong>Paid</strong>
                            <a href="payment-success.php?id=<?= $id ?>">View receipt →</a></div>

                    <?php elseif ($latestPayment && $latestPayment['status'] === 'pending'): ?>

                        <div class="alert success-alert">Advance payment: <strong>Pending verification</strong>
                            <a href="payment-success.php?id=<?= $id ?>">View status →</a></div>

                    <?php elseif ($latestPayment && $latestPayment['status'] === 'failed'): ?>

                        <div class="alert error-alert">Advance payment: <strong>Failed</strong>
                            <a href="payment-failed.php?id=<?= $id ?>">See why &amp; try again →</a></div>

                    <?php else: ?>

                        <a
                            class="btn gold"
                            href="payment.php?id=<?= $id ?>"
                        >
                            Pay <?= ADVANCE_PERCENT ?>% Advance →
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <!-- WhatsApp -->

            <?php
            $waBookingMsg = implode("\n", [
                'Hello Web Studio, I have a question about my booking.',
                '',
                'Booking: #' . ref_no($id),
                'Event date: ' . ($booking['event_date'] ? date('d M Y', strtotime($booking['event_date'])) : '-'),
                'Status: ' . ucfirst($booking['status']),
            ]);
            ?>

            <div style="margin-top:18px">
                <a
                    class="btn wa-btn"
                    target="_blank"
                    rel="noopener noreferrer"
                    href="<?= e(whatsapp_link($waBookingMsg)) ?>"
                >
                    <?= whatsapp_icon_svg(18) ?> Message the studio on WhatsApp
                </a>
            </div>


            <!-- Status Note -->

            <div class="status-note">

                <strong>
                    Current status:
                    <?= ucfirst(e($booking['status'])) ?>
                </strong>


                <span>

                    <?php if ($booking['status'] === 'pending'): ?>

                        The studio will review your request
                        and confirm the date.

                    <?php elseif ($booking['status'] === 'confirmed'): ?>

                        Your date has been confirmed by
                        Web Studio.

                    <?php elseif ($booking['status'] === 'completed'): ?>

                        This booking has been marked completed.

                    <?php else: ?>

                        This booking has been cancelled.
                        Please contact the studio if you need help.

                    <?php endif; ?>

                </span>

            </div>

        </div>

    </div>

</section>


<?php

require __DIR__ . '/includes/footer.php';

?>