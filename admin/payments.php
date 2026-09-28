<?php

require_once '../includes/auth.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

/* Update payment status */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        header('Location: payments.php?error=csrf');
        exit;
    }

    $id = (int) $_POST['id'];
    $status = $_POST['status'];

    if (in_array($status, ['paid', 'failed', 'refunded'], true)) {

        $st = mysqli_prepare(
            $conn,
            "UPDATE payments 
             SET status = ?, 
                 paid_at = IF(? = 'paid', NOW(), NULL) 
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($st, 'ssi', $status, $status, $id);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);

        header('Location: payments.php?updated=1');
        exit;
    }
}

/* Get payments */
$q = mysqli_query(
    $conn,
    "SELECT p.*, b.name, b.event_date, b.id booking_id
     FROM payments p
     LEFT JOIN bookings b ON b.id = p.booking_id
     ORDER BY p.created_at DESC"
);

?>

<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Payments | Web Studio Admin</title>

    <link rel="stylesheet" href="assets/admin.css">

    <style>

        .pay-card {
            background: #fff;
            border: 1px solid #e4eaf1;
            border-radius: 12px;
            padding: 25px;
        }

        .pay-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pay-table th,
        .pay-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf1f5;
            text-align: left;
            font-size: 12px;
        }

        .pay-table th {
            font-size: 10px;
            color: #8993a3;
            text-transform: uppercase;
        }

        .pay-status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
        }

        .pay-status.pending {
            background: #fff5df;
            color: #9a6d08;
        }

        .pay-status.paid {
            background: #e8faf3;
            color: #13845f;
        }

        .pay-status.failed,
        .pay-status.refunded {
            background: #ffeded;
            color: #b74b4b;
        }

        .pay-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .pay-actions form {
            margin: 0;
        }

        .pay-actions button {
            border: 1px solid #dfe5ec;
            border-radius: 6px;
            background: #fff;
            padding: 7px 9px;
            font-size: 10px;
            cursor: pointer;
        }

        .pay-actions .paid {
            background: #0b2344;
            color: #fff;
            border-color: #d8a42a;
        }

        .notice {
            padding: 12px 14px;
            border-radius: 8px;
            background: #ebfaf4;
            color: #168361;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .notice.error {
            background: #fff2f2;
            color: #b74b4b;
        }

        @media (max-width: 700px) {

            .pay-table {
                min-width: 900px;
            }

        }

    </style>

</head>

<body class="admin-body">

<div class="app-shell">

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="brand-box">
            <img src="../assets/images/logo-light.png" alt="Web Studio">
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
            <strong>Payments</strong>
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

            <a class="active" href="payments.php">
                <span>Rs</span> Payments
            </a>

            <a href="calendar.php">
                <span>□</span> Calendar
            </a>

            <a href="../index.php" target="_blank">
                <span>↗</span> View Website
            </a>

        </nav>

        <a class="logout-link" href="logout.php">
            ↪ &nbsp; Logout
        </a>

    </aside>

    <!-- Main -->
    <main class="dashboard-main">

        <header class="topbar">

            <div class="top-actions">

                <span>Admin Panel</span>

                <span class="top-avatar">
                    JS
                </span>

            </div>

        </header>

        <section class="content">

            <div class="page-heading">

                <div>

                    <h1>Payments</h1>

                    <p>
                        Review advance payment requests and verify successful payments.
                    </p>

                </div>

            </div>

            <!-- Messages -->
            <?php if (isset($_GET['updated'])): ?>

                <div class="notice">
                    Payment status updated successfully.
                </div>

            <?php elseif (isset($_GET['error'])): ?>

                <div class="notice error">
                    Security check failed.
                </div>

            <?php endif; ?>

            <!-- Payment Table -->
            <section class="pay-card">

                <div class="table-wrap">

                    <table class="pay-table">

                        <thead>

                            <tr>

                                <th>Payment</th>
                                <th>Customer</th>
                                <th>Booking</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($q && mysqli_num_rows($q)): ?>

                                <?php while ($p = mysqli_fetch_assoc($q)): ?>

                                    <tr>

                                        <!-- Payment -->
                                        <td>

                                            #<?= str_pad(
                                                (string) $p['id'],
                                                4,
                                                '0',
                                                STR_PAD_LEFT
                                            ) ?>

                                            <br>

                                            <small>
                                                <?= date(
                                                    'd M Y, h:i A',
                                                    strtotime($p['created_at'])
                                                ) ?>
                                            </small>

                                        </td>

                                        <!-- Customer -->
                                        <td>

                                            <strong>
                                                <?= e($p['name'] ?: 'Customer') ?>
                                            </strong>

                                        </td>

                                        <!-- Booking -->
                                        <td>

                                            <a href="booking-details.php?id=<?= $p['booking_id'] ?>">

                                                #<?= str_pad(
                                                    (string) $p['booking_id'],
                                                    4,
                                                    '0',
                                                    STR_PAD_LEFT
                                                ) ?>

                                            </a>

                                            <br>

                                            <small>
                                                <?= e($p['event_date'] ?: '—') ?>
                                            </small>

                                        </td>

                                        <!-- Method -->
                                        <td>

                                            <?= e($p['method'] ?: '—') ?>

                                            <br>

                                            <small>
                                                <?= e(
                                                    $p['transaction_id'] ?: 'No reference'
                                                ) ?>
                                            </small>
                                            <?php if (!empty($p['proof_image'])): ?>
                                                <br>
                                                <a href="../uploads/payment-proofs/<?= rawurlencode(basename($p['proof_image'])) ?>" target="_blank" rel="noopener">
                                                    View payment proof ↗
                                                </a>
                                            <?php endif; ?>

                                        </td>

                                        <!-- Amount -->
                                        <td>
                                            Rs.
                                            <?= number_format(
                                                (float) $p['amount'],
                                                2
                                            ) ?>
                                        </td>

                                        <!-- Status -->
                                        <td>

                                            <span class="pay-status <?= e($p['status']) ?>">

                                                <?= e($p['status']) ?>

                                            </span>

                                        </td>

                                        <!-- Action -->
                                        <td>

                                            <div class="pay-actions">

                                                <?php if ($p['status'] === 'pending'): ?>

                                                    <!-- Mark Paid -->
                                                    <form method="post">

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e(csrf_token()) ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= $p['id'] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="paid"
                                                        >

                                                        <button class="paid">
                                                            Mark Paid
                                                        </button>

                                                    </form>

                                                    <!-- Reject -->
                                                    <form method="post">

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e(csrf_token()) ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= $p['id'] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="failed"
                                                        >

                                                        <button>
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
                                        style="text-align:center;padding:45px;color:#9aa3b0"
                                    >
                                        No payment requests yet.
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