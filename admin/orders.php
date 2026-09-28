<?php

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/whatsapp-api.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

ensure_order_payment_method_column($conn);

$orderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'completed', 'cancelled'];

/* Restore stock for a cancelled order (checkout reduced it) */
function restore_order_stock(mysqli $conn, int $orderId): void
{
    $st = mysqli_prepare(
        $conn,
        'UPDATE products p
         JOIN order_items oi ON oi.product_id = p.id
         SET p.stock = p.stock + oi.quantity
         WHERE oi.order_id = ?'
    );
    mysqli_stmt_bind_param($st, 'i', $orderId);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
}

/* Update payment / order status */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        header('Location: orders.php?error=csrf');
        exit;
    }

    $id = (int) $_POST['id'];

    $st = mysqli_prepare($conn, 'SELECT status, payment_status FROM orders WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $cur = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    if ($cur) {

        /* Payment verification: paid / failed / refunded */
        if (isset($_POST['payment_status']) && in_array($_POST['payment_status'], ['paid', 'failed', 'refunded'], true)) {

            $ps = $_POST['payment_status'];

            /* A verified payment moves a pending order to confirmed */
            $st = mysqli_prepare(
                $conn,
                "UPDATE orders
                 SET payment_status = ?,
                     status = IF(? = 'paid' AND status = 'pending', 'confirmed', status)
                 WHERE id = ?"
            );
            mysqli_stmt_bind_param($st, 'ssi', $ps, $ps, $id);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            if ($ps !== $cur['payment_status']) {
                wa_notify_payment($conn, $id, $ps);   // paid / failed -> customer WhatsApp (API)
                if (!WA_API_ENABLED && in_array($ps, ['paid', 'failed'], true)) {
                    $_SESSION['wa_prompt'] = wa_customer_prompt($conn, $id, $ps);
                }
            }
        }

        /* Order status */
        if (isset($_POST['order_status']) && in_array($_POST['order_status'], $orderStatuses, true)) {

            $os = $_POST['order_status'];

            if ($os !== $cur['status']) {

                $st = mysqli_prepare($conn, 'UPDATE orders SET status = ? WHERE id = ?');
                mysqli_stmt_bind_param($st, 'si', $os, $id);
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);

                if ($os === 'cancelled' && $cur['status'] !== 'cancelled') {
                    restore_order_stock($conn, $id);
                }

                wa_notify_order_status($conn, $id);   // customer WhatsApp (API)
                if (!WA_API_ENABLED) {
                    $_SESSION['wa_prompt'] = wa_customer_prompt($conn, $id, 'status');
                }

            }
        }
    }

    header('Location: orders.php?updated=1' . (isset($_GET['f']) ? '&f=' . urlencode($_GET['f']) : ''));
    exit;
}

/* Filter */
$filter = $_GET['f'] ?? 'all';
$where = '';
if ($filter === 'awaiting') {
    $where = "WHERE o.payment_status = 'pending' AND o.status <> 'cancelled'";
} elseif ($filter === 'paid') {
    $where = "WHERE o.payment_status = 'paid'";
} elseif ($filter === 'cancelled') {
    $where = "WHERE o.status = 'cancelled'";
} else {
    $filter = 'all';
}

$q = mysqli_query(
    $conn,
    "SELECT o.*, u.name AS customer_name, u.email AS customer_email,
            (SELECT GROUP_CONCAT(CONCAT(oi.product_name, ' x ', oi.quantity) SEPARATOR '||')
             FROM order_items oi WHERE oi.order_id = o.id) AS items
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     $where
     ORDER BY o.created_at DESC"
);

?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orders | Web Studio Admin</title>
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

        .pay-actions .wa-customer {
            display: inline-block;
            background: #25d366;
            color: #fff;
            border-radius: 6px;
            padding: 7px 9px;
            font-size: 10px;
            font-weight: 600;
            text-decoration: none;
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


        .ord-items{margin:0;padding:0;list-style:none;font-size:12px;color:#55647a}
        .ord-items li{margin:2px 0}
        .ord-addr{font-size:11px;color:#8a94a6;max-width:200px;white-space:normal}
        .ord-status-form{display:flex;gap:5px;margin-top:8px}
        .ord-status-form select{border:1px solid #dfe5ec;border-radius:6px;padding:6px;font-size:11px;background:#fff}
        .ord-status-form button{border:1px solid #dfe5ec;border-radius:6px;background:#fff;padding:6px 9px;font-size:10px;cursor:pointer}
        .pay-status.confirmed,.pay-status.processing,.pay-status.shipped{background:#e8f0ff;color:#2b5cb8}
        .pay-status.completed{background:#e8faf3;color:#13845f}
        .pay-status.cancelled{background:#ffeded;color:#b74b4b}
        .ord-filter{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
        .ord-filter a{padding:7px 13px;border-radius:20px;border:1px solid #dfe5ec;background:#fff;font-size:12px;color:#0b2344}
        .ord-filter a.on{background:#0b2344;color:#fff;border-color:#d8a42a}
    </style>
</head>

<body class="admin-body">

<div class="app-shell">

    <aside class="sidebar">

        <div class="brand-box">
            <img src="../assets/images/logo-light.png" alt="Web Studio">
        </div>

        <div class="profile-mini">
            <div class="avatar">JS</div>
            <div>
                <strong><?= e($_SESSION['admin']['name'] ?? 'Admin') ?></strong>
                <small><?= e($_SESSION['admin']['email'] ?? 'Administrator') ?></small>
            </div>
        </div>

        <div class="side-balance">
            <span>Shop</span>
            <strong>Orders</strong>
        </div>

        <nav>
            <a href="index.php"><span>⌂</span> Dashboard</a>
            <a href="services.php"><span>◉</span> Services</a>
            <a href="packages.php"><span>▦</span> Packages</a>
            <a href="products.php"><span>▣</span> Products</a>
            <a class="active" href="orders.php"><span>▤</span> Orders</a>
            <a href="bookings.php"><span>◷</span> Bookings</a>
            <a href="payments.php"><span>Rs</span> Payments</a>
            <a href="calendar.php"><span>□</span> Calendar</a>
            <a href="../index.php" target="_blank"><span>↗</span> View Website</a>
        </nav>

        <a class="logout-link" href="logout.php">↪ &nbsp; Logout</a>

    </aside>

    <main class="dashboard-main">

        <header class="topbar">
            <div class="top-actions">
                <span>Admin Panel</span>
                <span class="top-avatar">JS</span>
            </div>
        </header>

        <section class="content">

            <div class="page-heading">
                <div>
                    <h1>Orders</h1>
                    <p>Verify customer payments and update product order status.</p>
                </div>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="notice">Order updated successfully.</div>
                <?php if (!empty($_SESSION['wa_prompt'])): ?>
                    <div class="notice" style="background:#e8fbef;border-color:#25d366">
                        <a href="<?= e($_SESSION['wa_prompt']['link']) ?>" target="_blank" rel="noopener noreferrer"
                           style="display:inline-block;background:#25d366;color:#fff;padding:10px 16px;border-radius:8px;font-weight:600;text-decoration:none">
                            <?= e($_SESSION['wa_prompt']['label']) ?> (<?= e($_SESSION['wa_prompt']['phone']) ?>)
                        </a>
                    </div>
                    <?php unset($_SESSION['wa_prompt']); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="notice error">Security check failed.</div>
            <?php endif; ?>

            <div class="ord-filter">
                <a href="orders.php" class="<?= $filter === 'all' ? 'on' : '' ?>">All</a>
                <a href="orders.php?f=awaiting" class="<?= $filter === 'awaiting' ? 'on' : '' ?>">Awaiting payment</a>
                <a href="orders.php?f=paid" class="<?= $filter === 'paid' ? 'on' : '' ?>">Paid</a>
                <a href="orders.php?f=cancelled" class="<?= $filter === 'cancelled' ? 'on' : '' ?>">Cancelled</a>
            </div>

            <section class="pay-card">
                <div class="table-wrap">
                    <table class="pay-table">

                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Method</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Order status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($q && mysqli_num_rows($q)): ?>
                            <?php while ($o = mysqli_fetch_assoc($q)): ?>

                                <?php
                                $addr = (string) ($o['shipping_address'] ?? '');
                                $items = $o['items'] !== null ? explode('||', $o['items']) : [];

                                /* WhatsApp message TO the customer's own number */
                                $ref  = ref_no((int) $o['id']);
                                $cust = trim(explode(' ', trim((string) ($o['customer_name'] ?: 'Customer')))[0]);
                                $tot  = money((float) $o['total_amount']);

                                if ($o['status'] === 'cancelled') {
                                    $msg = "Hi $cust, your Web Studio order #$ref has been cancelled. Please contact us if you have any questions.";
                                } elseif ($o['payment_status'] === 'paid') {
                                    $msg = "Hi $cust, we received your payment of $tot for order #$ref. Thank you! Current status: " . ucfirst($o['status']) . '.';
                                } elseif ($o['payment_status'] === 'failed') {
                                    $msg = "Hi $cust, we could not verify the payment for order #$ref ($tot). Please send your bank slip again on this chat.";
                                } else {
                                    $msg = "Hi $cust, thank you for your order #$ref ($tot) at Web Studio. Please transfer the total from any bank and send us the payment slip here. Bank details: "
                                        . implode(' | ', array_map(fn($b) => $b['bank'] . ' - ' . $b['name'] . ' - ' . $b['number'], BANK_ACCOUNTS));
                                }

                                $custWaLink = !empty($o['phone']) ? whatsapp_link($msg, (string) $o['phone']) : '';
                                ?>

                                <tr>
                                    <td>
                                        #<?= ref_no((int) $o['id']) ?><br>
                                        <small><?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></small>
                                    </td>

                                    <td>
                                        <strong><?= e($o['customer_name'] ?: 'Customer') ?></strong><br>
                                        <small><?= e($o['phone'] ?: '—') ?></small>
                                        <div class="ord-addr"><?= nl2br(e($addr)) ?></div>
                                    </td>

                                    <td>
                                        <ul class="ord-items">
                                            <?php foreach ($items as $it): ?>
                                                <li><?= e($it) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>

                                    <td><?= e($o['payment_method'] ?: 'Bank Transfer') ?></td>

                                    <td>Rs. <?= number_format((float) $o['total_amount'], 2) ?></td>

                                    <td>
                                        <span class="pay-status <?= e($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span>
                                    </td>

                                    <td>
                                        <span class="pay-status <?= e($o['status']) ?>"><?= e($o['status']) ?></span>

                                        <form method="post" class="ord-status-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                            <select name="order_status">
                                                <?php foreach ($orderStatuses as $s): ?>
                                                    <option value="<?= $s ?>" <?= $s === $o['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button onclick="return this.form.order_status.value !== 'cancelled' || confirm('Cancel this order and return the items to stock?')">Update</button>
                                        </form>
                                    </td>

                                    <td>
                                        <div class="pay-actions">
                                            <?php if ($custWaLink): ?>
                                                <a class="wa-customer" href="<?= e($custWaLink) ?>" target="_blank" rel="noopener noreferrer">WhatsApp customer</a>
                                            <?php endif; ?>
                                            <?php if ($o['payment_status'] === 'pending'): ?>

                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                                    <input type="hidden" name="payment_status" value="paid">
                                                    <button class="paid" onclick="return confirm('Confirm you received this payment?')">Mark Paid</button>
                                                </form>

                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                                    <input type="hidden" name="payment_status" value="failed">
                                                    <button>Reject</button>
                                                </form>

                                            <?php elseif ($o['payment_status'] === 'paid'): ?>

                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                                    <input type="hidden" name="payment_status" value="refunded">
                                                    <button onclick="return confirm('Mark this payment as refunded?')">Refund</button>
                                                </form>

                                            <?php elseif ($o['payment_status'] === 'failed'): ?>

                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                                    <input type="hidden" name="payment_status" value="paid">
                                                    <button class="paid" onclick="return confirm('Confirm you received this payment?')">Mark Paid</button>
                                                </form>

                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center;padding:45px;color:#9aa3b0">
                                    No orders found.
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