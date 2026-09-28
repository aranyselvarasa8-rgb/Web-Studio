<?php

require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin'])) {
    header('Location:login.php');
    exit;
}

$allowed = [
    'pending',
    'confirmed',
    'completed',
    'cancelled'
];


/* Update booking status */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['id'], $_POST['status'])
) {

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        header('Location:bookings.php?error=csrf');
        exit;
    }

    $id = (int) $_POST['id'];
    $status = $_POST['status'];

    if (in_array($status, $allowed, true)) {

        $st = mysqli_prepare(
            $conn,
            'UPDATE bookings SET status=? WHERE id=?'
        );

        mysqli_stmt_bind_param(
            $st,
            'si',
            $status,
            $id
        );

        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);

        /* After confirming, offer a one-click WhatsApp message to the customer */
        if ($status === 'confirmed') {
            header('Location:bookings.php?updated=1&wa=' . $id);
        } else {
            header('Location:bookings.php?updated=1');
        }
        exit;
    }
}


/* WhatsApp message for a booking that was just confirmed */

$waConfirm = null;

if (isset($_GET['updated'], $_GET['wa'])) {

    $waId = (int) $_GET['wa'];

    $st = mysqli_prepare(
        $conn,
        "SELECT b.id, b.name, b.phone, b.event_date, b.total_amount,
                GROUP_CONCAT(bs.item_name ORDER BY bs.id SEPARATOR ', ') AS item
         FROM bookings b
         LEFT JOIN booking_services bs ON bs.booking_id = b.id
         WHERE b.id = ? AND b.status = 'confirmed'
         GROUP BY b.id
         LIMIT 1"
    );

    mysqli_stmt_bind_param($st, 'i', $waId);
    mysqli_stmt_execute($st);

    $wb = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

    mysqli_stmt_close($st);

    if ($wb && !empty($wb['phone'])) {

        $advance = round(((float) $wb['total_amount'] * ADVANCE_PERCENT) / 100, 2);

        $waConfirm = [
            'ref'  => ref_no((int) $wb['id']),
            'name' => $wb['name'],
            'link' => whatsapp_link(
                implode("\n", [
                    'Hello ' . $wb['name'] . ',',
                    '',
                    'Your booking #' . ref_no((int) $wb['id']) . ' (' . ($wb['item'] ?: 'Photography') . ') for ' .
                        ($wb['event_date'] ? date('d M Y', strtotime($wb['event_date'])) : 'your event date') .
                        ' is CONFIRMED by Web Studio.',
                    '',
                    'To secure your date, please pay the ' . ADVANCE_PERCENT . '% advance (' . money($advance) . ').',
                    'Log in to our website > My Bookings > Pay Advance and upload your bank transfer slip.',
                    '',
                    'Thank you for choosing Web Studio!',
                ]),
                $wb['phone']
            ),
        ];
    }
}


/* Booking counts */

$total = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            'SELECT COUNT(*) FROM bookings'
        )
    )[0] ?? 0
);

$pending = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            "SELECT COUNT(*) FROM bookings
             WHERE status='pending'"
        )
    )[0] ?? 0
);

$confirmed = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            "SELECT COUNT(*) FROM bookings
             WHERE status='confirmed'"
        )
    )[0] ?? 0
);

$completed = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            "SELECT COUNT(*) FROM bookings
             WHERE status='completed'"
        )
    )[0] ?? 0
);


/* Get bookings */

$q = mysqli_query(
    $conn,
    "SELECT
        b.*,
        GROUP_CONCAT(
            bs.item_name
            ORDER BY bs.id
            SEPARATOR ', '
        ) selected_item
     FROM bookings b
     LEFT JOIN booking_services bs
        ON bs.booking_id = b.id
     GROUP BY b.id
     ORDER BY b.created_at DESC"
);

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        Bookings | Web Studio Admin
    </title>

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

    <style>

        .booking-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 18px;
        }

        .mini-stat {
            background: #fff;
            border: 1px solid #e9edf2;
            border-radius: 10px;
            padding: 18px 20px;
        }

        .mini-stat span {
            display: block;
            color: #8b95a4;
            font-size: 11px;
            margin-bottom: 7px;
        }

        .mini-stat strong {
            font-size: 24px;
        }

        .mini-stat em {
            display: block;
            font-style: normal;
            color: #a0a8b4;
            font-size: 10px;
            margin-top: 4px;
        }

        .notice {
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 12px;
            margin-bottom: 14px;
            background: #ebfaf4;
            color: #168361;
            border: 1px solid #d2f0e3;
        }

        .notice.error {
            background: #fff2f2;
            color: #b74b4b;
            border-color: #f3d6d6;
        }

        .booking-table {
            width: 100%;
            border-collapse: collapse;
        }

        .booking-table th,
        .booking-table td {
            padding: 14px 11px;
            border-bottom: 1px solid #edf0f3;
            text-align: left;
            vertical-align: middle;
        }

        .booking-table th {
            font-size: 10px;
            color: #8a94a4;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .booking-table td {
            font-size: 12px;
        }

        .customer strong {
            display: block;
            font-size: 13px;
        }

        .customer small {
            display: block;
            color: #929baa;
            font-size: 10px;
            margin-top: 3px;
        }

        .booking-item {
            font-weight: 700;
            color: #172a49;
        }

        .booking-date {
            font-weight: 600;
            white-space: nowrap;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .status.pending {
            background: #fff5df;
            color: #b47a11;
        }

        .status.confirmed {
            background: #e8faf3;
            color: #13845f;
        }

        .status.completed {
            background: #edf1ff;
            color: #5266b8;
        }

        .status.cancelled {
            background: #ffeded;
            color: #b74b4b;
        }

        .status-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .status-actions form {
            margin: 0;
        }

        .status-actions button,
        .view-btn {
            border: 1px solid #dfe5ec;
            border-radius: 6px;
            padding: 7px 9px;
            background: #fff;
            text-decoration: none;
            color: #536074;
            font-size: 10px;
            cursor: pointer;
        }

        .status-actions .confirm {
            border-color: #d5eee4;
            color: #13845f;
        }

        .status-actions .cancel {
            border-color: #f0d5d5;
            color: #b74b4b;
        }

        .view-btn {
            display: inline-block;
            margin-bottom: 6px;
            color: #0b2344;
            font-weight: 700;
        }

        .empty-row {
            text-align: center !important;
            padding: 45px !important;
            color: #9aa3b0 !important;
        }

        @media (max-width: 1000px) {

            .booking-stats {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 650px) {

            .booking-stats {
                grid-template-columns: 1fr;
            }

            .booking-table {
                min-width: 1050px;
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
                    <?= e(
                        $_SESSION['admin']['email']
                        ?? 'Administrator'
                    ) ?>
                </small>

            </div>

        </div>


        <div class="side-balance">

            <span>
                Studio revenue
            </span>

            <strong>
                Bookings
            </strong>

        </div>


        <nav>

            <a href="index.php">
                <span>⌂</span>
                Dashboard
            </a>

            <a href="services.php">
                <span>◉</span>
                Services
            </a>

            <a href="packages.php">
                <span>▦</span>
                Packages
            </a>

            <a href="products.php">
                <span>▣</span>
                Products
            </a>

            <a
                class="active"
                href="bookings.php"
            >
                <span>◷</span>
                Bookings
            </a>

            <a href="orders.php">
                <span>▤</span> Orders
            </a>

            <a href="payments.php">
                <span>Rs</span>
                Payments
            </a>

            <a href="calendar.php">
                <span>□</span>
                Calendar
            </a>

            <a
                href="../index.php"
                target="_blank"
            >
                <span>↗</span>
                View Website
            </a>

        </nav>


        <a
            class="logout-link"
            href="logout.php"
        >
            ↪ &nbsp; Logout
        </a>

    </aside>


    <!-- Main -->

    <main class="dashboard-main">

        <header class="topbar">

            <div class="top-actions">

                <span>
                    Admin Panel
                </span>

                <span class="bell">
                    ♢
                </span>

                <span class="top-avatar">
                    JS
                </span>

            </div>

        </header>


        <section class="content">


            <!-- Page Heading -->

            <div class="page-heading">

                <div>

                    <h1>
                        Bookings
                    </h1>

                    <p>
                        Review customer requests,
                        view details and manage booking status.
                    </p>

                </div>

                <div class="date-pill">

                    Booking management

                    <span>
                        ⌄
                    </span>

                </div>

            </div>


            <!-- Messages -->

            <?php if (isset($_GET['updated'])): ?>

                <div class="notice">
                    Booking status updated successfully.

                    <?php if ($waConfirm): ?>
                        <a
                            href="<?= e($waConfirm['link']) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            style="margin-left:10px;font-weight:700;color:#13845f"
                        >
                            Notify <?= e($waConfirm['name']) ?> on WhatsApp →
                        </a>
                    <?php endif; ?>
                </div>

            <?php elseif (isset($_GET['error'])): ?>

                <div class="notice error">
                    Security check failed.
                    Please try again.
                </div>

            <?php endif; ?>


            <!-- Statistics -->

            <div class="booking-stats">

                <div class="mini-stat">

                    <span>
                        Total bookings
                    </span>

                    <strong>
                        <?= $total ?>
                    </strong>

                    <em>
                        All customer requests
                    </em>

                </div>


                <div class="mini-stat">

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?= $pending ?>
                    </strong>

                    <em>
                        Waiting for review
                    </em>

                </div>


                <div class="mini-stat">

                    <span>
                        Confirmed
                    </span>

                    <strong>
                        <?= $confirmed ?>
                    </strong>

                    <em>
                        Upcoming confirmed jobs
                    </em>

                </div>


                <div class="mini-stat">

                    <span>
                        Completed
                    </span>

                    <strong>
                        <?= $completed ?>
                    </strong>

                    <em>
                        Finished bookings
                    </em>

                </div>

            </div>


            <!-- Booking Table -->

            <section class="panel">

                <div class="panel-head">

                    <div>

                        <h2>
                            Booking Requests
                        </h2>

                        <p>
                            Customer selections,
                            event dates and status.
                        </p>

                    </div>

                    <span class="right-note">
                        <?= $total ?> requests
                    </span>

                </div>


                <div class="table-wrap">

                    <table class="booking-table">

                        <thead>

                            <tr>

                                <th>S/N</th>
                                <th>Customer</th>
                                <th>Service / Package</th>
                                <th>Event date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Update</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($q && mysqli_num_rows($q)): ?>

                            <?php

                            $i = 1;

                            while ($b = mysqli_fetch_assoc($q)):

                                $s = $b['status'] ?? 'pending';

                            ?>

                                <tr>

                                    <td>
                                        <?= $i++ ?>
                                    </td>


                                    <td class="customer">

                                        <strong>
                                            <?= e($b['name']) ?>
                                        </strong>

                                        <small>

                                            <?= e($b['phone']) ?>

                                            <?php if (!empty($b['email'])): ?>

                                                · <?= e($b['email']) ?>

                                            <?php endif; ?>

                                        </small>

                                    </td>


                                    <td>

                                        <span class="booking-item">

                                            <?= e(
                                                $b['selected_item']
                                                ?: $b['event_type']
                                                ?: 'Photography'
                                            ) ?>

                                        </span>

                                        <small
                                            style="
                                                display:block;
                                                color:#929baa;
                                                margin-top:4px
                                            "
                                        >

                                            <?= e(
                                                $b['event_type']
                                                ?: 'Event'
                                            ) ?>

                                        </small>

                                    </td>


                                    <td class="booking-date">

                                        <?= $b['event_date']
                                            ? date(
                                                'd M Y',
                                                strtotime(
                                                    $b['event_date']
                                                )
                                            )
                                            : '—'
                                        ?>

                                    </td>


                                    <td>

                                        Rs.
                                        <?= number_format(
                                            (float) $b['total_amount'],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status <?= e($s) ?>"
                                        >
                                            <?= e($s) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <a
                                            class="view-btn"
                                            href="booking-details.php?id=<?= $b['id'] ?>"
                                        >
                                            View details
                                        </a>


                                        <div class="status-actions">


                                            <?php if ($s !== 'confirmed'): ?>

                                                <form method="post">

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(
                                                            csrf_token()
                                                        ) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= $b['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="confirmed"
                                                    >

                                                    <button
                                                        class="confirm"
                                                        type="submit"
                                                    >
                                                        Confirm
                                                    </button>

                                                </form>

                                            <?php endif; ?>


                                            <?php if ($s !== 'completed'): ?>

                                                <form method="post">

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(
                                                            csrf_token()
                                                        ) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= $b['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="completed"
                                                    >

                                                    <button type="submit">
                                                        Complete
                                                    </button>

                                                </form>

                                            <?php endif; ?>


                                            <?php if ($s !== 'cancelled'): ?>

                                                <form
                                                    method="post"
                                                    onsubmit="
                                                        return confirm(
                                                            'Cancel this booking?'
                                                        )
                                                    "
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(
                                                            csrf_token()
                                                        ) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= $b['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="cancelled"
                                                    >

                                                    <button
                                                        class="cancel"
                                                        type="submit"
                                                    >
                                                        Reject
                                                    </button>

                                                </form>

                                            <?php endif; ?>


                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>


                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-row"
                                >
                                    No booking requests yet.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </section>

    </main>

</div>



</body>

</html>