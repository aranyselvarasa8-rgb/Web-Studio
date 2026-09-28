<?php

require_once __DIR__ . '/includes/auth.php';

require_login('calendar.php');

$page_title = 'Availability Calendar | ' . SITE_NAME;


$month = $_GET['month'] ?? date('Y-m');


/* Validate month */

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {

    $month = date('Y-m');
}


/* Get first day of month */

$first = DateTime::createFromFormat(
    'Y-m-d',
    $month . '-01'
);


if (!$first) {

    $first = new DateTime('first day of this month');
}


$start = $first->format('Y-m-01');

$end = $first->format('Y-m-t');


/* Get bookings */

$st = mysqli_prepare(
    $conn,
    "SELECT event_date, status
     FROM bookings
     WHERE event_date BETWEEN ? AND ?
     AND status IN ('pending', 'confirmed')
     ORDER BY event_date"
);

mysqli_stmt_bind_param(
    $st,
    'ss',
    $start,
    $end
);

mysqli_stmt_execute($st);

$res = mysqli_stmt_get_result($st);

$busy = [];


while ($r = mysqli_fetch_assoc($res)) {

    $busy[$r['event_date']] = $r['status'];
}


mysqli_stmt_close($st);


/* Previous and next month */

$prev = clone $first;

$prev->modify('-1 month');


$next = clone $first;

$next->modify('+1 month');


/* Calendar details */

$days = (int) $first->format('t');

$offset = (int) $first->format('w');


require __DIR__ . '/includes/header.php';

?>


<!-- Page Banner -->

<section class="page-banner">

    <div class="container">

        <span class="eyebrow">
            AVAILABILITY
        </span>

        <h1>
            Check our photography calendar.
        </h1>

        <p>
            Choose an available date before sending
            your booking request.
        </p>

    </div>

</section>


<!-- Calendar -->

<section class="section">

    <div class="container calendar-page">


        <!-- Calendar Top -->

        <div class="calendar-top">

            <div>

                <h2>
                    <?= e($first->format('F Y')) ?>
                </h2>

                <p>
                    Green dates are available.
                    Reserved dates are blocked.
                </p>

            </div>


            <!-- Calendar Navigation -->

            <div class="calendar-nav">

                <a
                    class="btn outline-dark"
                    href="calendar.php?month=<?= $prev->format('Y-m') ?>"
                >
                    ← Previous
                </a>

                <a
                    class="btn outline-dark"
                    href="calendar.php?month=<?= date('Y-m') ?>"
                >
                    Today
                </a>

                <a
                    class="btn outline-dark"
                    href="calendar.php?month=<?= $next->format('Y-m') ?>"
                >
                    Next →
                </a>

            </div>

        </div>


        <!-- Calendar Legend -->

        <div class="calendar-legend">

            <span>

                <i class="dot available-dot"></i>

                Available

            </span>


            <span>

                <i class="dot pending-dot"></i>

                Pending

            </span>


            <span>

                <i class="dot booked-dot"></i>

                Confirmed

            </span>

        </div>


        <!-- Calendar Grid -->

        <div class="calendar-grid">


            <!-- Weekdays -->

            <div class="calendar-weekdays">

                <?php foreach (
                    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
                    as $d
                ): ?>

                    <div>
                        <?= $d ?>
                    </div>

                <?php endforeach; ?>

            </div>


            <!-- Calendar Days -->

            <div class="calendar-days">


                <!-- Empty Days -->

                <?php for ($i = 0; $i < $offset; $i++): ?>

                    <div class="calendar-day empty"></div>

                <?php endfor; ?>


                <!-- Month Days -->

                <?php for ($d = 1; $d <= $days; $d++): ?>

                    <?php

                    $date =
                        $first->format('Y-m-') .
                        str_pad(
                            (string) $d,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                    $status = $busy[$date] ?? '';

                    $past = $date < date('Y-m-d');


                    $class = $past
                        ? 'past'
                        : (
                            $status === 'confirmed'
                                ? 'confirmed'
                                : (
                                    $status === 'pending'
                                        ? 'pending'
                                        : 'available'
                                )
                        );

                    ?>


                    <div class="calendar-day <?= $class ?>">

                        <strong>
                            <?= $d ?>
                        </strong>


                        <?php if ($status): ?>

                            <span>
                                <?= ucfirst($status) ?>
                            </span>


                        <?php elseif ($past): ?>

                            <span>
                                Past
                            </span>


                        <?php else: ?>

                            <a href="booking.php?date=<?= $date ?>">
                                Book this date
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endfor; ?>

            </div>

        </div>

    </div>

</section>


<?php

require __DIR__ . '/includes/footer.php';

?>