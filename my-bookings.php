<?php

require_once __DIR__ . '/includes/auth.php';

require_login('my-bookings.php');


$user = current_user();

$page_title = 'My Bookings | ' . SITE_NAME;


/* Get user bookings */

$st = mysqli_prepare(
    $conn,
    "SELECT 
        b.*,
        GROUP_CONCAT(
            bs.item_name
            ORDER BY bs.id
            SEPARATOR ', '
        ) AS selected_item

     FROM bookings b

     LEFT JOIN booking_services bs
        ON bs.booking_id = b.id

     WHERE b.user_id = ?

     GROUP BY b.id

     ORDER BY b.event_date DESC, b.id DESC"
);


mysqli_stmt_bind_param(
    $st,
    'i',
    $user['id']
);


mysqli_stmt_execute($st);


$bookings = mysqli_stmt_get_result($st);


require __DIR__ . '/includes/header.php';

?>


<section class="page-banner">

    <div class="container">

        <span class="eyebrow">
            CUSTOMER AREA
        </span>

        <h1>
            My Bookings
        </h1>

        <p>
            Track your photography requests and booking status
            in one place.
        </p>

    </div>

</section>


<section class="section">

    <div class="container">

        <div class="booking-list-head">

            <div>

                <h2>
                    Your booking history
                </h2>

                <p>
                    Pending requests are reviewed by Web Studio
                    before confirmation.
                </p>

            </div>


            <a class="btn gold" href="booking.php">
                + New Booking
            </a>

        </div>


        <div class="my-bookings-grid">

            <?php if (mysqli_num_rows($bookings)): ?>

                <?php while ($b = mysqli_fetch_assoc($bookings)): ?>

                    <article class="my-booking-card">

                        <div class="my-booking-top">

                            <span class="booking-number">

                                Booking #

                                <?= str_pad(
                                    (string) $b['id'],
                                    4,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>

                            </span>


                            <span class="booking-status <?= e($b['status']) ?>">

                                <?= ucfirst(e($b['status'])) ?>

                            </span>

                        </div>


                        <h3>

                            <?= e(
                                $b['selected_item']
                                ?: $b['event_type']
                                ?: 'Photography Booking'
                            ) ?>

                        </h3>


                        <div class="booking-meta">

                            <div>

                                <small>
                                    Event date
                                </small>

                                <strong>

                                    <?= $b['event_date']
                                        ? date(
                                            'd M Y',
                                            strtotime($b['event_date'])
                                        )
                                        : '—'
                                    ?>

                                </strong>

                            </div>


                            <div>

                                <small>
                                    Event
                                </small>

                                <strong>

                                    <?= e(
                                        $b['event_type']
                                        ?: 'Photography'
                                    ) ?>

                                </strong>

                            </div>


                            <div>

                                <small>
                                    Total
                                </small>

                                <strong>

                                    Rs.
                                    <?= number_format(
                                        (float) $b['total_amount'],
                                        2
                                    ) ?>

                                </strong>

                            </div>

                        </div>


                        <p>

                            <?= e(
                                $b['message']
                                ?: 'No additional message.'
                            ) ?>

                        </p>


                        <a
                            class="booking-view"
                            href="booking-details.php?id=<?= $b['id'] ?>"
                        >
                            View details →
                        </a>

                    </article>

                <?php endwhile; ?>


            <?php else: ?>

                <div class="empty-bookings">

                    <h3>
                        No bookings yet
                    </h3>

                    <p>
                        Choose a service or package to make
                        your first booking.
                    </p>

                    <a class="btn gold" href="booking.php">
                        Book Now
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<?php

require __DIR__ . '/includes/footer.php';

?>