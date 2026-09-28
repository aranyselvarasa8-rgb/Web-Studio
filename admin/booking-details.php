<?php

require_once '../includes/auth.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$st = mysqli_prepare(
    $conn,
    "SELECT 
        b.*,
        GROUP_CONCAT(
            CONCAT(bs.item_name, ' — Rs. ', FORMAT(bs.price, 2))
            ORDER BY bs.id
            SEPARATOR '||'
        ) items
     FROM bookings b
     LEFT JOIN booking_services bs ON bs.booking_id = b.id
     WHERE b.id = ?
     GROUP BY b.id
     LIMIT 1"
);

mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);

$b = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

mysqli_stmt_close($st);

if (!$b) {
    http_response_code(404);
    die('Booking not found.');
}

$paymentRows = mysqli_query(
    $conn,
    "SELECT id, amount, method, transaction_id, status, paid_at
     FROM payments
     WHERE booking_id = " . $id . "
     ORDER BY created_at DESC"
);

$paidTotal = (float) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            "SELECT COALESCE(SUM(amount), 0)
             FROM payments
             WHERE booking_id = " . $id . "
             AND status = 'paid'"
        )
    )[0] ?? 0
);

$page_title = 'Booking Details | Web Studio';
$status = $b['status'];

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= $page_title ?></title>


<style>
:root{
    --navy:#0b2344;
    --navy2:#173a67;
    --gold:#d8a42a;
    --bg:#f4f7fb;
    --card:#ffffff;
    --text:#172033;
    --muted:#718096;
    --border:#e5eaf1;
    --green:#138a5b;
    --red:#c83c3c;
    --shadow:0 12px 35px rgba(8,38,74,.08);
}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{
    font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
}
.app-shell{min-height:100vh;display:flex}
.sidebar{
    position:fixed;left:0;top:0;bottom:0;width:270px;
    background:linear-gradient(180deg,var(--navy),#061c38);
    color:#fff;padding:24px 18px;display:flex;flex-direction:column;
    z-index:10;
}
.brand-box{background:none!important;box-shadow:none!important;
    height:105px;background:#fff;border-radius:18px;
    display:flex;align-items:center;justify-content:center;
    padding:12px;margin-bottom:22px;overflow:hidden;
}
.brand-box img{max-width:100%;max-height:82px;object-fit:contain}
.profile-mini{display:flex;align-items:center;gap:12px;padding:10px 8px 18px}
.avatar,.top-avatar{
    display:flex;align-items:center;justify-content:center;
    border-radius:50%;font-weight:800;
}
.avatar{width:43px;height:43px;background:var(--gold);color:var(--navy)}
.profile-mini strong{display:block;font-size:14px}
.profile-mini small{display:block;color:#b8c7da;font-size:11px;margin-top:3px;max-width:165px;overflow:hidden;text-overflow:ellipsis}
.side-balance{
    background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08);
    border-radius:14px;padding:13px 14px;margin:0 2px 18px
}
.side-balance span{display:block;color:#9fb1c7;font-size:11px}
.side-balance strong{display:block;font-size:15px;margin-top:4px}
.sidebar nav{display:flex;flex-direction:column;gap:6px}
.sidebar nav a,.logout-link{
    color:#dce7f4;text-decoration:none;padding:12px 14px;border-radius:11px;
    display:flex;align-items:center;gap:11px;font-size:14px;transition:.2s
}
.sidebar nav a span{width:20px;text-align:center}
.sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,.12);color:#fff}
.sidebar nav a.active{box-shadow:inset 3px 0 var(--gold)}
.logout-link{margin-top:auto;border-top:1px solid rgba(255,255,255,.1);border-radius:0;padding-top:18px}
.logout-link:hover{color:#fff;background:rgba(255,255,255,.06)}
.dashboard-main{margin-left:270px;width:calc(100% - 270px);min-height:100vh}
.topbar{
    height:76px;background:#fff;border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:flex-end;padding:0 34px;
    position:sticky;top:0;z-index:5
}
.top-actions{display:flex;align-items:center;gap:18px;color:#526176;font-size:14px}
.bell{font-size:20px}
.top-avatar{width:38px;height:38px;background:#eaf0f7;color:var(--navy);font-size:12px}
.content{padding:34px}
.details-admin{max-width:1050px;margin:0 auto}
.back-admin{
    display:inline-flex;align-items:center;gap:7px;text-decoration:none;
    color:var(--navy);font-weight:700;font-size:14px;margin-bottom:18px
}
.back-admin:hover{text-decoration:underline}
.detail-card{
    background:var(--card);border:1px solid var(--border);
    border-radius:22px;box-shadow:var(--shadow);overflow:hidden
}
.detail-head{
    padding:28px 30px 24px;border-bottom:1px solid var(--border);
    display:flex;align-items:flex-start;justify-content:space-between;gap:20px
}
.eyebrow{
    display:inline-block;color:var(--gold);font-weight:800;
    font-size:11px;letter-spacing:1.5px;margin-bottom:8px
}
.detail-head h1{margin:0;color:var(--navy);font-size:30px;line-height:1.2}
.detail-head>div>span:last-child{display:block;color:var(--muted);font-size:12px;margin-top:8px}
.status{
    padding:8px 13px;border-radius:999px;font-size:12px;font-weight:800;
    text-transform:capitalize;white-space:nowrap
}
.status.pending{background:#fff4d6;color:#9a6b00}
.status.confirmed{background:#e4f7ee;color:#08734b}
.status.completed{background:#e5efff;color:#235da8}
.status.cancelled{background:#fde8e8;color:#b62e2e}
.detail-grid{
    display:grid;grid-template-columns:repeat(2,minmax(0,1fr));
    gap:0;border-bottom:1px solid var(--border)
}
.detail-item{padding:21px 30px;border-bottom:1px solid var(--border)}
.detail-item:nth-last-child(-n+2){border-bottom:0}
.detail-item small,.detail-message small{
    display:block;color:var(--muted);font-size:11px;font-weight:700;
    text-transform:uppercase;letter-spacing:.8px;margin-bottom:7px
}
.detail-item strong{font-size:15px;color:#202b3d;line-height:1.7;word-break:break-word}
.detail-message{padding:21px 30px;border-bottom:1px solid var(--border)}
.detail-message p{margin:0;color:#445066;font-size:14px;line-height:1.7}
.detail-message p strong{color:var(--navy)}
.detail-message a{color:var(--navy);font-weight:700;text-decoration:none}
.detail-message a:hover{text-decoration:underline}
.admin-actions{
    padding:24px 30px;display:flex;flex-wrap:wrap;gap:10px;background:#fbfcfe
}
.admin-actions form{margin:0}
.admin-actions button{
    border:0;border-radius:10px;padding:11px 16px;font-weight:800;
    font-size:13px;cursor:pointer;background:#e9eef5;color:#26364d
}
.admin-actions button:hover{filter:brightness(.97)}
.admin-actions .confirm{background:var(--green);color:#fff}
.admin-actions .reject{background:#fff0f0;color:var(--red);border:1px solid #f3cccc}
@media(max-width:900px){
    .sidebar{width:220px}
    .dashboard-main{margin-left:220px;width:calc(100% - 220px)}
    .content{padding:22px}
}
@media(max-width:700px){
    .sidebar{position:relative;width:100%;height:auto;min-height:0}
    .app-shell{display:block}
    .dashboard-main{margin-left:0;width:100%}
    .sidebar nav{display:grid;grid-template-columns:repeat(2,1fr)}
    .logout-link{margin-top:12px}
    .topbar{height:64px;padding:0 18px}
    .content{padding:16px}
    .detail-head{padding:22px 20px;flex-direction:column}
    .detail-head h1{font-size:24px}
    .detail-grid{grid-template-columns:1fr}
    .detail-item,.detail-message{padding:18px 20px}
    .detail-item:nth-last-child(-n+2){border-bottom:1px solid var(--border)}
    .detail-item:last-child{border-bottom:0}
    .admin-actions{padding:20px;flex-direction:column}
    .admin-actions form,.admin-actions button{width:100%}
}
</style>

</head>

<body>

<div class="app-shell">

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

            <a class="active" href="bookings.php">
                <span>◷</span>
                Bookings
            </a>

            <a href="../index.php" target="_blank">
                <span>↗</span>
                View Website
            </a>

        </nav>

        <a class="logout-link" href="logout.php">
            ↪ &nbsp; Logout
        </a>

    </aside>


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

            <div class="details-admin">

                <a
                    class="back-admin"
                    href="bookings.php"
                >
                    ← Back to bookings
                </a>


                <div class="detail-card">

                    <div class="detail-head">

                        <div>

                            <span class="eyebrow">
                                BOOKING #
                                <?= str_pad(
                                    (string) $b['id'],
                                    4,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span>

                            <h1>
                                <?= e(
                                    $b['event_type']
                                    ?: 'Photography Booking'
                                ) ?>
                            </h1>

                            <span>
                                Created
                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime($b['created_at'])
                                ) ?>
                            </span>

                        </div>

                        <span class="status <?= e($status) ?>">
                            <?= ucfirst(e($status)) ?>
                        </span>

                    </div>


                    <div class="detail-grid">

                        <div class="detail-item">

                            <small>
                                Customer name
                            </small>

                            <strong>
                                <?= e($b['name']) ?>
                            </strong>

                        </div>


                        <div class="detail-item">

                            <small>
                                Phone
                            </small>

                            <strong>
                                <?= e($b['phone']) ?>
                            </strong>

                        </div>


                        <div class="detail-item">

                            <small>
                                Email
                            </small>

                            <strong>
                                <?= e($b['email'] ?: '—') ?>
                            </strong>

                        </div>


                        <div class="detail-item">

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


                        <div class="detail-item">

                            <small>
                                Selected service / package
                            </small>

                            <strong>

                                <?php
                                foreach (
                                    explode(
                                        '||',
                                        $b['items'] ?: 'Photography'
                                    ) as $item
                                ):
                                ?>

                                    <?= e($item) ?><br>

                                <?php endforeach; ?>

                            </strong>

                        </div>


                        <div class="detail-item">

                            <small>
                                Total amount
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


                    <div class="detail-message">

                        <small>
                            Payment summary
                        </small>

                        <p>

                            Verified paid:
                            <strong>
                                Rs.
                                <?= number_format(
                                    $paidTotal,
                                    2
                                ) ?>
                            </strong>

                            ·

                            <a href="orders.php">
                <span>▤</span> Orders
            </a>

            <a href="payments.php">
                                Manage payments →
                            </a>

                        </p>

                    </div>


                    <div class="detail-message">

                        <small>
                            Customer message
                        </small>

                        <p>
                            <?= nl2br(
                                e(
                                    $b['message']
                                    ?: 'No additional message.'
                                )
                            ) ?>
                        </p>

                    </div>


                    <div class="admin-actions">

                        <form
                            method="post"
                            action="bookings.php"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>"
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
                                ✓ Confirm Booking
                            </button>

                        </form>


                        <form
                            method="post"
                            action="bookings.php"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>"
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
                                Mark Completed
                            </button>

                        </form>


                        <form
                            method="post"
                            action="bookings.php"
                            onsubmit="return confirm('Reject this booking?')"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>"
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
                                class="reject"
                                type="submit"
                            >
                                ✕ Reject Booking
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </section>

    </main>

</div>



</body><?php

require_once '../includes/auth.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$st = mysqli_prepare(
    $conn,
    "SELECT 
        b.*,
        GROUP_CONCAT(
            CONCAT(bs.item_name, ' — Rs. ', FORMAT(bs.price, 2))
            ORDER BY bs.id
            SEPARATOR '||'
        ) items
     FROM bookings b
     LEFT JOIN booking_services bs ON bs.booking_id = b.id
     WHERE b.id = ?
     GROUP BY b.id
     LIMIT 1"
);

mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);

$b = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

mysqli_stmt_close($st);

if (!$b) {
    http_response_code(404);
    die('Booking not found.');
}

$paymentRows = mysqli_query(
    $conn,
    "SELECT id, amount, method, transaction_id, status, paid_at
     FROM payments
     WHERE booking_id = " . $id . "
     ORDER BY created_at DESC"
);

$paidTotal = (float) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            "SELECT COALESCE(SUM(amount), 0)
             FROM payments
             WHERE booking_id = " . $id . "
             AND status = 'paid'"
        )
    )[0] ?? 0
);

$page_title = 'Booking Details | Web Studio';
$status = $b['status'];

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= $page_title ?></title>

