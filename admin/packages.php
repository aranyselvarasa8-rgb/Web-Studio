<?php

require_once '../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';


/* Save Package */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');

    $status = ($_POST['status'] ?? 'active') === 'inactive'
        ? 'inactive'
        : 'active';

    $items = $_POST['item_name'] ?? [];
    $qty = $_POST['quantity'] ?? [];
    $service_ids = $_POST['service_id'] ?? [];


    /* Validation */

    if ($name === '') {

        $error = 'Package name is required.';

    } elseif ($price < 0) {

        $error = 'Price cannot be negative.';

    } else {

        /* Update Package */

        if ($id) {

            $st = mysqli_prepare(
                $conn,
                'UPDATE packages
                 SET name=?, description=?, price=?, status=?
                 WHERE id=?'
            );

            mysqli_stmt_bind_param(
                $st,
                'ssdsi',
                $name,
                $desc,
                $price,
                $status,
                $id
            );

            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            mysqli_query(
                $conn,
                "DELETE FROM package_items WHERE package_id=$id"
            );

        }

        /* Add Package */

        else {

            $st = mysqli_prepare(
                $conn,
                'INSERT INTO packages
                 (name, description, price, status)
                 VALUES (?, ?, ?, ?)'
            );

            mysqli_stmt_bind_param(
                $st,
                'ssds',
                $name,
                $desc,
                $price,
                $status
            );

            mysqli_stmt_execute($st);

            $id = mysqli_insert_id($conn);

            mysqli_stmt_close($st);
        }


        /* Add Package Items */

        $st = mysqli_prepare(
            $conn,
            'INSERT INTO package_items
             (package_id, service_id, item_name, quantity)
             VALUES (?, ?, ?, ?)'
        );

        foreach ($items as $i => $item) {

            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $sid = (int) ($service_ids[$i] ?? 0);
            $q = max(1, (int) ($qty[$i] ?? 1));

            mysqli_stmt_bind_param(
                $st,
                'iisi',
                $id,
                $sid,
                $item,
                $q
            );

            mysqli_stmt_execute($st);
        }

        mysqli_stmt_close($st);

        header('Location: packages.php?saved=1');
        exit;
    }
}


/* Delete Package */

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    mysqli_query(
        $conn,
        "DELETE FROM packages WHERE id=$id"
    );

    header('Location: packages.php?deleted=1');
    exit;
}


/* Success Messages */

if (isset($_GET['saved'])) {
    $success = 'Package saved successfully.';
}

if (isset($_GET['deleted'])) {
    $success = 'Package deleted successfully.';
}


/* Edit Package */

$edit = null;
$edit_items = [];

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];

    $r = mysqli_query(
        $conn,
        "SELECT * FROM packages WHERE id=$id LIMIT 1"
    );

    $edit = mysqli_fetch_assoc($r);


    $ir = mysqli_query(
        $conn,
        "SELECT * FROM package_items
         WHERE package_id=$id
         ORDER BY id"
    );

    while ($x = mysqli_fetch_assoc($ir)) {
        $edit_items[] = $x;
    }
}


/* Active Services */

$services = mysqli_query(
    $conn,
    "SELECT id, name
     FROM services
     WHERE status='active'
     ORDER BY name"
);

$service_options = [];

while ($x = mysqli_fetch_assoc($services)) {
    $service_options[] = $x;
}


/* Package List */

$list = mysqli_query(
    $conn,
    "SELECT
        p.*,
        COUNT(pi.id) AS item_count
     FROM packages p
     LEFT JOIN package_items pi
        ON pi.package_id = p.id
     GROUP BY p.id
     ORDER BY p.id DESC"
);


/* Package Statistics */

$total = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            'SELECT COUNT(*) FROM packages'
        )
    )[0] ?? 0
);

$active = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            "SELECT COUNT(*)
             FROM packages
             WHERE status='active'"
        )
    )[0] ?? 0
);

$inactive = $total - $active;

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
        Packages | Web Studio Admin
    </title>

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

    <style>

        .package-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
            font-size: 10px;
            margin-bottom: 7px;
        }

        .mini-stat strong {
            font-size: 22px;
        }

        .mini-stat em {
            display: block;
            font-style: normal;
            color: #a0a8b4;
            font-size: 9px;
            margin-top: 4px;
        }

        .notice {
            border-radius: 7px;
            padding: 11px 13px;
            font-size: 10px;
            margin-bottom: 14px;
        }

        .notice.error {
            background: #fff0f0;
            color: #b14b4b;
            border: 1px solid #f4d6d6;
        }

        .notice.success {
            background: #ebfaf4;
            color: #168361;
            border: 1px solid #d2f0e3;
        }

        .package-form {
            display: grid;
            grid-template-columns: 1.2fr .6fr .6fr;
            gap: 10px;
            align-items: start;
        }

        .package-form input,
        .package-form textarea,
        .package-form select {
            width: 100%;
            border: 1px solid #dfe5eb;
            border-radius: 6px;
            padding: 11px 12px;
            background: #fff;
            color: #25344d;
            font: inherit;
            font-size: 11px;
            outline: none;
        }

        .package-form textarea {
            grid-column: 1 / -1;
            min-height: 70px;
            resize: vertical;
        }

        .package-form input:focus,
        .package-form textarea:focus,
        .package-form select:focus {
            border-color: #aebbc9;
            box-shadow: 0 0 0 3px #f1f4f7;
        }

        .items-block {
            grid-column: 1 / -1;
            background: #f8fafc;
            border: 1px solid #edf0f3;
            border-radius: 8px;
            padding: 13px;
        }

        .items-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 7px;
        }

        .items-head strong {
            font-size: 10px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 1.5fr 1.1fr 80px 38px;
            gap: 8px;
            margin: 8px 0;
        }

        .item-row input,
        .item-row select {
            margin: 0;
        }

        .item-row button {
            border: 1px solid #e1e6ec;
            background: #fff;
            border-radius: 6px;
            color: #b54e4e;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 8px;
        }

        .primary {
            border: 0;
            background: #16c79a;
            color: #fff;
            border-radius: 6px;
            padding: 11px 16px;
            font-weight: 700;
            font-size: 10px;
        }

        .secondary {
            border: 1px solid #e1e6ec;
            background: #fff;
            color: #566174;
            border-radius: 6px;
            padding: 10px 15px;
            text-decoration: none;
            font-size: 10px;
        }

        .package-table {
            width: 100%;
            border-collapse: collapse;
        }

        .package-table th,
        .package-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf0f3;
            text-align: left;
        }

        .package-table th {
            font-size: 8px;
            color: #8a94a4;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .package-table td {
            font-size: 10px;
        }

        .package-name strong {
            display: block;
            font-size: 11px;
        }

        .package-name small {
            display: block;
            color: #929baa;
            font-size: 9px;
            margin-top: 4px;
            max-width: 450px;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .badge.active {
            background: #e8faf3;
            color: #13845f;
        }

        .badge.inactive {
            background: #f1f3f5;
            color: #7d8794;
        }

        .actions a {
            text-decoration: none;
            font-size: 9px;
            margin-right: 10px;
            color: #2c6fa3;
        }

        .actions a.delete {
            color: #b54e4e;
        }

        .empty-row {
            text-align: center !important;
            padding: 45px !important;
            color: #9aa3b0 !important;
        }

        @media (max-width: 900px) {

            .package-form {
                grid-template-columns: 1fr 1fr;
            }

            .package-form textarea,
            .items-block,
            .form-actions {
                grid-column: 1 / -1;
            }

            .package-stats {
                grid-template-columns: 1fr 1fr;
            }

            .package-stats .mini-stat:last-child {
                grid-column: 1 / -1;
            }

        }

        @media (max-width: 650px) {

            .package-stats {
                grid-template-columns: 1fr;
            }

            .package-stats .mini-stat:last-child {
                grid-column: auto;
            }

            .package-form {
                grid-template-columns: 1fr;
            }

            .item-row {
                grid-template-columns: 1fr;
            }

            .table-wrap {
                margin-left: -5px;
                margin-right: -5px;
            }

            .package-table {
                min-width: 760px;
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
                Packages
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

            <a
                class="active"
                href="packages.php"
            >
                <span>▦</span>
                Packages
            </a>

            <a href="products.php">
                <span>▣</span>
                Products
            </a>

            <a href="orders.php">
                <span>▤</span> Orders
            </a>

            <a href="bookings.php">
                <span>◷</span>
                Bookings
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


    <!-- Main Content -->

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
                    JS
                </span>

            </div>

        </header>


        <section class="content">


            <!-- Page Heading -->

            <div class="page-heading">

                <div>

                    <h1>
                        Packages
                    </h1>

                    <p>
                        Create photography packages and connect included services.
                    </p>

                </div>

                <div class="date-pill">

                    Package management

                    <span>
                        ⌄
                    </span>

                </div>

            </div>


            <!-- Error -->

            <?php if ($error): ?>

                <div class="notice error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- Success -->

            <?php if ($success): ?>

                <div class="notice success">

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- Statistics -->

            <div class="package-stats">

                <div class="mini-stat">

                    <span>
                        Total packages
                    </span>

                    <strong>
                        <?= $total ?>
                    </strong>

                    <em>
                        All packages in catalogue
                    </em>

                </div>


                <div class="mini-stat">

                    <span>
                        Active packages
                    </span>

                    <strong>
                        <?= $active ?>
                    </strong>

                    <em>
                        Visible to customers
                    </em>

                </div>


                <div class="mini-stat">

                    <span>
                        Inactive packages
                    </span>

                    <strong>
                        <?= $inactive ?>
                    </strong>

                    <em>
                        Temporarily hidden
                    </em>

                </div>

            </div>


            <!-- Add / Edit Package -->

            <section
                class="panel"
                style="margin-bottom:18px"
            >

                <div class="panel-head">

                    <div>

                        <h2>
                            <?= $edit ? 'Edit Package' : 'Add Package' ?>
                        </h2>

                        <p>
                            Build a package with services and quantities.
                        </p>

                    </div>

                </div>


                <form
                    method="post"
                    class="package-form"
                >


                    <input
                        type="hidden"
                        name="id"
                        value="<?= $edit['id'] ?? 0 ?>"
                    >


                    <input
                        name="name"
                        placeholder="Package name"
                        value="<?= htmlspecialchars($edit['name'] ?? '') ?>"
                        required
                    >


                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        placeholder="Price"
                        value="<?= $edit['price'] ?? '' ?>"
                        required
                    >


                    <select name="status">

                        <option
                            value="active"
                            <?= ($edit['status'] ?? 'active') === 'active'
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= ($edit['status'] ?? '') === 'inactive'
                                ? 'selected'
                                : '' ?>
                        >
                            Inactive
                        </option>

                    </select>


                    <textarea
                        name="description"
                        placeholder="Description"
                    ><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>


                    <!-- Package Items -->

                    <div class="items-block">

                        <div class="items-head">

                            <strong>
                                Package Items
                            </strong>

                            <button
                                type="button"
                                class="secondary"
                                onclick="addItem()"
                            >
                                + Add item
                            </button>

                        </div>


                        <div id="items">

                            <?php if ($edit_items): ?>

                                <?php foreach ($edit_items as $it): ?>

                                    <div class="item-row">

                                        <input
                                            name="item_name[]"
                                            value="<?= htmlspecialchars($it['item_name']) ?>"
                                            placeholder="Item name"
                                        >


                                        <select name="service_id[]">

                                            <option value="0">
                                                No linked service
                                            </option>

                                            <?php foreach ($service_options as $so): ?>

                                                <option
                                                    value="<?= $so['id'] ?>"
                                                    <?= $it['service_id'] == $so['id']
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    <?= htmlspecialchars($so['name']) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>


                                        <input
                                            type="number"
                                            min="1"
                                            name="quantity[]"
                                            value="<?= $it['quantity'] ?>"
                                        >


                                        <button
                                            type="button"
                                            onclick="this.parentNode.remove()"
                                        >
                                            ×
                                        </button>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="item-row">

                                    <input
                                        name="item_name[]"
                                        placeholder="Item name"
                                    >


                                    <select name="service_id[]">

                                        <option value="0">
                                            No linked service
                                        </option>

                                        <?php foreach ($service_options as $so): ?>

                                            <option
                                                value="<?= $so['id'] ?>"
                                            >
                                                <?= htmlspecialchars($so['name']) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>


                                    <input
                                        type="number"
                                        min="1"
                                        name="quantity[]"
                                        value="1"
                                    >


                                    <button
                                        type="button"
                                        onclick="this.parentNode.remove()"
                                    >
                                        ×
                                    </button>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- Form Buttons -->

                    <div class="form-actions">

                        <button
                            class="primary"
                            type="submit"
                        >
                            <?= $edit ? 'Update Package' : 'Add Package' ?>
                        </button>


                        <?php if ($edit): ?>

                            <a
                                class="secondary"
                                href="packages.php"
                            >
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </section>


            <!-- Package List -->

            <section class="panel">

                <div class="panel-head">

                    <div>

                        <h2>
                            Package List
                        </h2>

                        <p>
                            Current customer-facing packages.
                        </p>

                    </div>

                    <span class="right-note">
                        <?= $total ?> packages
                    </span>

                </div>


                <div class="table-wrap">

                    <table class="package-table">

                        <thead>

                            <tr>

                                <th>S/N</th>
                                <th>Package</th>
                                <th>Price</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($list && mysqli_num_rows($list)): ?>

                            <?php

                            $i = 1;

                            while ($p = mysqli_fetch_assoc($list)):

                            ?>

                                <tr>

                                    <td>
                                        <?= $i++ ?>
                                    </td>


                                    <td class="package-name">

                                        <strong>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </strong>

                                        <small>
                                            <?= htmlspecialchars($p['description'] ?? '') ?>
                                        </small>

                                    </td>


                                    <td>

                                        <strong>
                                            Rs.
                                            <?= number_format(
                                                (float) $p['price'],
                                                2
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>
                                        <?= $p['item_count'] ?> items
                                    </td>


                                    <td>

                                        <span
                                            class="badge <?= htmlspecialchars($p['status']) ?>"
                                        >
                                            <?= htmlspecialchars($p['status']) ?>
                                        </span>

                                    </td>


                                    <td class="actions">

                                        <a
                                            href="?edit=<?= $p['id'] ?>"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            class="delete"
                                            href="?delete=<?= $p['id'] ?>"
                                            onclick="return confirm('Delete this package?')"
                                        >
                                            Delete
                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="empty-row"
                                >
                                    No packages available yet.
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


<!-- Add Item JavaScript -->

<script>

    const serviceOptions = `
        <option value="0">
            No linked service
        </option>

        <?php foreach ($service_options as $so): ?>

            <option value="<?= $so['id'] ?>">
                <?= htmlspecialchars($so['name'], ENT_QUOTES) ?>
            </option>

        <?php endforeach; ?>
    `;


    function addItem()
    {
        const div = document.createElement('div');

        div.className = 'item-row';

        div.innerHTML = `
            <input
                name="item_name[]"
                placeholder="Item name"
            >

            <select name="service_id[]">
                ${serviceOptions}
            </select>

            <input
                type="number"
                min="1"
                name="quantity[]"
                value="1"
            >

            <button
                type="button"
                onclick="this.parentNode.remove()"
            >
                ×
            </button>
        `;

        document
            .getElementById('items')
            .appendChild(div);
    }

</script>



</body>

</html>