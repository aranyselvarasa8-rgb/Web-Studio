<?php

require_once '../includes/auth.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

/* Get selected month */
$month = $_GET['month'] ?? date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

/* First day of month */
$first = DateTime::createFromFormat('Y-m-d', $month . '-01');

if (!$first) {
    $first = new DateTime('first day of this month');
}

$start = $first->format('Y-m-01');
$end   = $first->format('Y-m-t');

/* Get bookings */
$sql = "
    SELECT 
        b.id,
        b.event_date,
        b.status,
        b.name,
        GROUP_CONCAT(bs.item_name SEPARATOR ', ') AS item
    FROM bookings b
    LEFT JOIN booking_services bs 
        ON bs.booking_id = b.id
    WHERE b.event_date BETWEEN ? AND ?
    AND b.status IN ('pending', 'confirmed', 'completed')
    GROUP BY b.id
    ORDER BY b.event_date
";

$st = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($st, 'ss', $start, $end);
mysqli_stmt_execute($st);

$res = mysqli_stmt_get_result($st);

$events = [];

while ($r = mysqli_fetch_assoc($res)) {
    $events[$r['event_date']][] = $r;
}

mysqli_stmt_close($st);

/* Previous and next month */
$prev = clone $first;
$prev->modify('-1 month');

$next = clone $first;
$next->modify('+1 month');

/* Calendar details */
$days   = (int) $first->format('t');
$offset = (int) $first->format('w');

?>

<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Calendar | Web Studio Admin</title>

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

    <style>

        .admin-calendar {
            background: #fff;
            border: 1px solid #e4eaf1;
            border-radius: 12px;
            padding: 24px;
        }

        .cal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .cal-head h2 {
            margin: 0;
            font-size: 22px;
            color: #0b2344;
        }

        .cal-nav {
            display: flex;
            gap: 7px;
        }

        .cal-nav a {
            border: 1px solid #dfe6ee;
            border-radius: 7px;
            padding: 8px 11px;
            font-size: 11px;
            text-decoration: none;
            color: #0b2344;
        }

        .week,
        .days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 7px;
        }

        .week div {
            padding: 10px;
            font-size: 10px;
            color: #8993a3;
            text-transform: uppercase;
            text-align: center;
        }

        .day {
            min-height: 110px;
            border: 1px solid #e7ecf2;
            border-radius: 8px;
            padding: 9px;
            background: #fff;
        }

        .day.empty {
            background: #fafbfc;
        }

        .day.today {
            border-color: #d8a42a;
        }

        .day strong {
            font-size: 13px;
            color: #0b2344;
        }

        .event {
            display: block;
            margin-top: 7px;
            padding: 7px;
            border-radius: 6px;
            background: #eef3f9;
            font-size: 10px;
            color: #0b2344;
        }

        .event.pending {
            background: #fff5df;
            color: #9a6d08;
        }

        .event.completed {
            background: #edf1ff;
            color: #5266b8;
        }

        .legend {
            display: flex;
            gap: 18px;
            margin: 0 0 15px;
            font-size: 11px;
            color: #66748a;
        }

        .dot {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            margin-right: 5px;
            background: #d8a42a;
        }

        .dot.confirmed {
            background: #0b2344;
        }

        .dot.pending {
            background: #d8a42a;
        }

        .dot.completed {
            background: #5266b8;
        }

        @media (max-width: 700px) {

            .week,
            .days {
                min-width: 760px;
            }

            .admin-calendar {
                overflow: auto;
            }

            .cal-head {
                align-items: flex-start;
                gap: 12px;
                flex-direction: column;
            }

        }

    </style>

</head>

<body class="admin-body">

<div class="app-shell">

    <!-- Sidebar -->

    <aside class="sidebar">

        <div class="brand-box">
            <img
                src="../assets/images/logo-light.png"
                alt="Web Studio"
            >
        </div>

        <div class="profile-mini">

            <div class="avatar">
                JS
            </div>

            <div>

                <strong>
                    <?= e($_SESSION['admin']['name'] ?? 'Admin') ?>
                </strong>

                <small>
                    <?= e($_SESSION['admin']['email'] ?? 'Administrator') ?>
                </small>

            </div>

        </div>

        <div class="side-balance">

            <span>Studio revenue</span>

            <strong>Calendar</strong>

        </div>

        <nav>

            <a href="index.php">
                <span>⌂</span> Dashboard
            </a>

            <a href="services.php">
                <span>◉</span> Services
            </a>

            <a href="packages.php">
                <span>▦</span> Packages
            </a>

            <a href="products.php">
                <span>▣</span> Products
            </a>

            <a href="bookings.php">
                <span>◷</span> Bookings
            </a>

            <a href="orders.php">
                <span>▤</span> Orders
            </a>

            <a href="payments.php">
                <span>Rs</span> Payments
            </a>

            <a
                class="active"
                href="calendar.php"
            >
                <span>□</span> Calendar
            </a>

            <a
                href="../index.php"
                target="_blank"
            >
                <span>↗</span> View Website
            </a>

        </nav>

        <a
            class="logout-link"
            href="logout.php"
        >
            ↪ &nbsp; Logout
        </a>

    </aside>


    <!-- Main Content -->

    <main class="dashboard-main">

        <header class="topbar">

            <div class="top-actions">

                <span>
                    Admin Panel
                </span>

                <span class="top-avatar">
                    JS
                </span>

            </div>

        </header>


        <section class="content">

            <div class="page-heading">

                <div>

                    <h1>
                        Availability Calendar
                    </h1>

                    <p>
                        View pending, confirmed and completed photography bookings by date.
                    </p>

                </div>

            </div>


            <!-- Calendar -->

            <section class="admin-calendar">

                <div class="cal-head">

                    <h2>
                        <?= e($first->format('F Y')) ?>
                    </h2>

                    <div class="cal-nav">

                        <a href="calendar.php?month=<?= $prev->format('Y-m') ?>">
                            ← Previous
                        </a>

                        <a href="calendar.php?month=<?= date('Y-m') ?>">
                            Today
                        </a>

                        <a href="calendar.php?month=<?= $next->format('Y-m') ?>">
                            Next →
                        </a>

                    </div>

                </div>


                <!-- Legend -->

                <div class="legend">

                    <span>
                        <i class="dot confirmed"></i>
                        Confirmed
                    </span>

                    <span>
                        <i class="dot pending"></i>
                        Pending
                    </span>

                    <span>
                        <i class="dot completed"></i>
                        Completed
                    </span>

                </div>


                <!-- Week Names -->

                <div class="week">

                    <?php

                    foreach (
                        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
                        as $d
                    ):

                    ?>

                        <div>
                            <?= $d ?>
                        </div>

                    <?php endforeach; ?>

                </div>


                <!-- Calendar Days -->

                <div class="days">

                    <?php

                    /* Empty days before first day */

                    for ($i = 0; $i < $offset; $i++):

                    ?>

                        <div class="day empty"></div>

                    <?php endfor; ?>


                    <?php

                    /* Days of the month */

                    for ($d = 1; $d <= $days; $d++):

                        $date = $first->format('Y-m-')
                              . str_pad(
                                    (string) $d,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                );

                    ?>

                        <div
                            class="day <?= ($date === date('Y-m-d')) ? 'today' : '' ?>"
                        >

                            <strong>
                                <?= $d ?>
                            </strong>


                            <?php if (!empty($events[$date])): ?>

                                <?php foreach ($events[$date] as $ev): ?>

                                    <a
                                        class="event <?= e($ev['status']) ?>"
                                        href="booking-details.php?id=<?= $ev['id'] ?? 0 ?>"
                                    >

                                        <?= e($ev['name']) ?>

                                        <br>

                                        <?= e($ev['status']) ?>

                                    </a>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div
                                    style="
                                        font-size:10px;
                                        color:#a0a8b4;
                                        margin-top:30px
                                    "
                                >
                                    Available
                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endfor; ?>

                </div>

            </section>

        </section>

    </main>

</div>



</body>

</html>