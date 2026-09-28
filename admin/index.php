<?php

require_once '../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}


/* Get single value from database */
function scalar($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_row($result);

    return $row ? $row[0] : 0;
}


/* Dashboard counts */

$customers = scalar(
    $conn,
    "SELECT COUNT(*) FROM users"
);

$services = scalar(
    $conn,
    "SELECT COUNT(*) FROM services WHERE status='active'"
);

$packages = scalar(
    $conn,
    "SELECT COUNT(*) FROM packages WHERE status='active'"
);

$products = scalar(
    $conn,
    "SELECT COUNT(*) FROM products"
);

$bookings = scalar(
    $conn,
    "SELECT COUNT(*) FROM bookings"
);

$pending = scalar(
    $conn,
    "SELECT COUNT(*) FROM bookings WHERE status='pending'"
);

$confirmed = scalar(
    $conn,
    "SELECT COUNT(*) FROM bookings WHERE status='confirmed'"
);

$revenue = scalar(
    $conn,
    "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'"
);


/* Recent bookings */

$recent = mysqli_query(
    $conn,
    "
    SELECT
        b.id,
        b.name,
        b.event_type,
        b.event_date,
        b.total_amount,
        b.status,
        b.created_at
    FROM bookings b
    ORDER BY b.created_at DESC
    LIMIT 8
    "
);

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Admin Dashboard | Web Studio
    </title>

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

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
                    <?= htmlspecialchars($_SESSION['admin']['name'] ?? 'Admin') ?>
                </strong>

                <small>
                    <?= htmlspecialchars($_SESSION['admin']['email'] ?? 'Administrator') ?>
                </small>

            </div>

        </div>


        <div class="side-balance">

            <span>
                Studio revenue
            </span>

            <strong>
                Rs <?= number_format((float)$revenue, 2) ?>
            </strong>

        </div>


        <nav>

            <a
                class="active"
                href="index.php"
            >
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

            <a href="bookings.php">
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


    <!-- Main Dashboard -->

    <main class="dashboard-main">


        <!-- Top Bar -->

        <header class="topbar">

            

            <div class="top-actions">

                <span>
                    Admin Panel
                </span>

                <span class="bell">
                    ♢
                </span>

                <span class="top-avatar">
                    WS
                </span>

            </div>

        </header>


        <section class="content">


            <!-- Page Heading -->

            <div class="page-heading">

                <div>

                    <h1>
                        Dashboard
                    </h1>

                    <p>
                        Studio performance and booking overview
                    </p>

                </div>

                <div class="date-pill">
                    Last 30 days
                    <span>⌄</span>
                </div>

            </div>


            <!-- Studio Overview -->

            <div class="overview-card">

                <div class="overview-title">

                    <div>

                        <h2>
                            Studio overview
                        </h2>

                        <p>
                            Current business summary
                        </p>

                    </div>

                    <a
                        class="gold-btn"
                        href="bookings.php"
                    >
                        View bookings
                    </a>

                </div>


                <div class="stats-grid">


                    <!-- Total Bookings -->

                    <div class="stat">

                        <span class="stat-icon">
                            ◷
                        </span>

                        <div>

                            <small>
                                Total bookings
                            </small>

                            <strong>
                                <?= $bookings ?>
                            </strong>

                            <em>
                                <?= $pending ?> pending
                            </em>

                        </div>

                    </div>


                    <!-- Confirmed -->

                    <div class="stat">

                        <span class="stat-icon">
                            ✓
                        </span>

                        <div>

                            <small>
                                Confirmed
                            </small>

                            <strong>
                                <?= $confirmed ?>
                            </strong>

                            <em>
                                Active bookings
                            </em>

                        </div>

                    </div>


                    <!-- Revenue -->

                    <div class="stat">

                        <span class="stat-icon">
                            Rs
                        </span>

                        <div>

                            <small>
                                Paid revenue
                            </small>

                            <strong>
                                Rs <?= number_format((float)$revenue, 0) ?>
                            </strong>

                            <em>
                                Successful payments
                            </em>

                        </div>

                    </div>


                    <!-- Customers -->

                    <div class="stat">

                        <span class="stat-icon">
                            ♙
                        </span>

                        <div>

                            <small>
                                Customers
                            </small>

                            <strong>
                                <?= $customers ?>
                            </strong>

                            <em>
                                Registered users
                            </em>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Two Column Section -->

            <div class="two-column">


                <!-- Studio Resources -->

                <section class="panel service-panel">

                    <div class="panel-head">

                        <div>

                            <h2>
                                Studio resources
                            </h2>

                            <p>
                                Manage your available content
                            </p>

                        </div>

                    </div>


                    <div class="resource-row">

                        <span>
                            Services
                        </span>

                        <strong>
                            <?= $services ?>
                        </strong>

                        <a href="services.php">
                            Manage →
                        </a>

                    </div>


                    <div class="resource-row">

                        <span>
                            Packages
                        </span>

                        <strong>
                            <?= $packages ?>
                        </strong>

                        <a href="packages.php">
                            Manage →
                        </a>

                    </div>


                    <div class="resource-row">

                        <span>
                            Products
                        </span>

                        <strong>
                            <?= $products ?>
                        </strong>

                        <a href="products.php">
                            Manage →
                        </a>

                    </div>

                </section>


                <!-- Quick Actions -->

                <section class="panel quick-panel">

                    <div class="panel-head">

                        <div>

                            <h2>
                                Quick actions
                            </h2>

                            <p>
                                Frequently used tools
                            </p>

                        </div>

                    </div>


                    <a
                        class="quick-action"
                        href="services.php"
                    >
                        <span>＋</span>
                        Add service
                        <b>→</b>
                    </a>


                    <a
                        class="quick-action"
                        href="packages.php"
                    >
                        <span>＋</span>
                        Add package
                        <b>→</b>
                    </a>


                    <a
                        class="quick-action"
                        href="products.php"
                    >
                        <span>＋</span>
                        Add product
                        <b>→</b>
                    </a>

                </section>

            </div>


            <!-- Recent Bookings -->

            <section class="panel bookings-panel">

                <div class="panel-head">

                    <div>

                        <h2>
                            Recent bookings
                        </h2>

                        <p>
                            Latest customer requests
                        </p>

                    </div>

                    <a
                        class="outline-btn"
                        href="bookings.php"
                    >
                        View all
                    </a>

                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    S/N
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Event
                                </th>

                                <th>
                                    Event date
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($recent && mysqli_num_rows($recent)): ?>

                            <?php

                            $i = 1;

                            while ($row = mysqli_fetch_assoc($recent)):

                            ?>

                                <tr>

                                    <td>
                                        <?= $i++ ?>
                                    </td>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars($row['name']) ?>
                                        </strong>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['event_type'] ?: 'Photography'
                                        ) ?>
                                    </td>

                                    <td>

                                        <?= $row['event_date']
                                            ? date(
                                                'M d, Y',
                                                strtotime($row['event_date'])
                                            )
                                            : '—'
                                        ?>

                                    </td>

                                    <td>
                                        Rs
                                        <?= number_format(
                                            (float)$row['total_amount'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status <?= htmlspecialchars($row['status']) ?>"
                                        >
                                            <?= ucfirst($row['status']) ?>
                                        </span>

                                    </td>

                                    <td class="arrow">
                                        →
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty"
                                >
                                    No bookings found yet.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>


            <!-- Footer -->

            <div class="footer-note">

                Web Studio Admin ·
                Manage your photography studio from one place.

            </div>


        </section>

    </main>

</div>



</body>

</html>