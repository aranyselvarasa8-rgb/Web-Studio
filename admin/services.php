<?php

require_once '../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

/* Save Service (add / update) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id       = (int) ($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $price    = (float) ($_POST['price'] ?? 0);
    $duration = ($_POST['duration'] ?? '') === '' ? null : (int) $_POST['duration'];
    $desc     = trim($_POST['description'] ?? '');
    $status   = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($name === '') {

        $error = 'Service name is required.';

    } elseif ($price < 0 || ($duration !== null && $duration < 0)) {

        $error = 'Price and duration cannot be negative.';

    } else {

        if ($id) {

            $st = mysqli_prepare(
                $conn,
                'UPDATE services
                 SET name=?, description=?, price=?, duration=?, status=?
                 WHERE id=?'
            );

            mysqli_stmt_bind_param($st, 'ssdisi', $name, $desc, $price, $duration, $status, $id);

        } else {

            $st = mysqli_prepare(
                $conn,
                'INSERT INTO services(name, description, price, duration, status)
                 VALUES(?, ?, ?, ?, ?)'
            );

            mysqli_stmt_bind_param($st, 'ssdis', $name, $desc, $price, $duration, $status);
        }

        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);

        header('Location: services.php?saved=1');
        exit;
    }
}

/* Toggle Status */
if (isset($_GET['toggle'])) {

    $id = (int) $_GET['toggle'];

    if ($id > 0) {
        mysqli_query(
            $conn,
            "UPDATE services
             SET status = IF(status='active','inactive','active')
             WHERE id=$id"
        );
    }

    header('Location: services.php?updated=1');
    exit;
}

/* Delete Service */
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM services WHERE id=$id");
    }

    header('Location: services.php?deleted=1');
    exit;
}

/* Edit Service (load into form) */
$edit = null;

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];

    if ($id > 0) {
        $res = mysqli_query($conn, "SELECT * FROM services WHERE id=$id");
        $edit = $res ? mysqli_fetch_assoc($res) : null;
    }
}

/* Messages */
if (isset($_GET['saved']))   { $success = 'Service saved successfully.'; }
if (isset($_GET['updated'])) { $success = 'Service status updated.'; }
if (isset($_GET['deleted'])) { $success = 'Service deleted successfully.'; }

/* Service List */
$list = mysqli_query($conn, 'SELECT * FROM services ORDER BY id DESC');

/* Stats */
$total    = (int) (mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM services'))[0] ?? 0);
$active   = (int) (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM services WHERE status='active'"))[0] ?? 0);
$inactive = $total - $active;

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        Services | Web Studio Admin
    </title>

    <link rel="stylesheet" href="assets/admin.css">

    <style>

        .service-stats {
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

        .form-panel {
            margin-bottom: 18px;
        }

        .service-form {
            display: grid;
            grid-template-columns: 1.2fr .6fr .5fr .6fr 1.5fr auto;
            gap: 10px;
            align-items: start;
        }

        .service-form input,
        .service-form select,
        .service-form textarea {
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

        .service-form textarea {
            min-height: 43px;
            resize: vertical;
        }

        .service-form input:focus,
        .service-form textarea:focus {
            border-color: #aebbc9;
            box-shadow: 0 0 0 3px #f1f4f7;
        }

        .form-actions button {
            border: 0;
            background: #16c79a;
            color: #fff;
            border-radius: 6px;
            padding: 11px 16px;
            font-weight: 700;
            font-size: 10px;
            cursor: pointer;
            white-space: nowrap;
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

        .service-table {
            width: 100%;
            border-collapse: collapse;
        }

        .service-table th,
        .service-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf0f3;
            text-align: left;
        }

        .service-table th {
            font-size: 8px;
            color: #8a94a4;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .service-table td {
            font-size: 10px;
        }

        .service-name strong {
            display: block;
            font-size: 11px;
            color: #25344d;
        }

        .service-name small {
            display: block;
            color: #929baa;
            font-size: 9px;
            margin-top: 4px;
            max-width: 450px;
        }

        .price {
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 700;
        }

        .status-badge.ok {
            background: #e8faf3;
            color: #13845f;
        }

        .status-badge.off {
            background: #fff5df;
            color: #b47a11;
        }

        .actions {
            white-space: nowrap;
        }

        .actions a {
            text-decoration: none;
            font-size: 9px;
            margin-right: 10px;
            color: #b54e4e;
        }

        .empty-row {
            text-align: center !important;
            padding: 45px !important;
            color: #9aa3b0 !important;
        }

        @media (max-width: 1000px) {

            .service-form {
                grid-template-columns: 1fr 1fr;
            }

            .service-form textarea {
                grid-column: 1 / -1;
            }

            .form-actions {
                grid-column: 1 / -1;
            }

            .service-stats {
                grid-template-columns: 1fr 1fr;
            }

            .service-stats .mini-stat:last-child {
                grid-column: 1 / -1;
            }

        }

        @media (max-width: 650px) {

            .service-stats {
                grid-template-columns: 1fr;
            }

            .service-stats .mini-stat:last-child {
                grid-column: auto;
            }

            .service-form {
                grid-template-columns: 1fr;
            }

            .service-form textarea,
            .form-actions {
                grid-column: auto;
            }

            .table-wrap {
                margin-left: -5px;
                margin-right: -5px;
            }

            .service-table {
                min-width: 760px;
            }

        }

    
        .actions a.edit { color: #2f6fb5; }
        .actions a.toggle { color: #8a6a13; }
        .form-actions a { font-size: 10px; margin-left: 8px; color: #8b95a4; text-decoration: none; }
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
                    <?= htmlspecialchars(
                        $_SESSION['admin']['name'] ?? 'Admin'
                    ) ?>
                </strong>

                <small>
                    <?= htmlspecialchars(
                        $_SESSION['admin']['email'] ?? 'Administrator'
                    ) ?>
                </small>

            </div>

        </div>

        <div class="side-balance">

            <span>
                Studio revenue
            </span>

            <strong>
                Studio services
            </strong>

        </div>

        <nav>

            <a href="index.php">
                <span>⌂</span> Dashboard
            </a>

            <a class="active" href="services.php">
                <span>◉</span> Services
            </a>

            <a href="packages.php">
                <span>▦</span> Packages
            </a>

            <a href="products.php">
                <span>▣</span> Products
            </a>

            <a href="orders.php">
                <span>▤</span> Orders
            </a>

            <a href="bookings.php">
                <span>◷</span> Bookings
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

                <span class="bell">
                    ♢
                </span>

                <span class="top-avatar">
                    JS
                </span>

            </div>

        </header>


        <section class="content">

            <div class="page-heading">

                <div>
                    <h1>Services</h1>
                    <p>Add and manage the photography services you offer.</p>
                </div>

                <div class="date-pill">
                    Service management
                    <span>⌄</span>
                </div>

            </div>

            <?php if ($error): ?>
                <div class="notice error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="notice success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>


            <div class="service-stats">

                <div class="mini-stat">
                    <span>Total services</span>
                    <strong><?= $total ?></strong>
                    <em>Services in catalogue</em>
                </div>

                <div class="mini-stat">
                    <span>Active</span>
                    <strong><?= $active ?></strong>
                    <em>Visible to customers</em>
                </div>

                <div class="mini-stat">
                    <span>Inactive</span>
                    <strong><?= $inactive ?></strong>
                    <em>Hidden from customers</em>
                </div>

            </div>


            <section class="panel form-panel">

                <div class="panel-head">
                    <div>
                        <h2><?= $edit ? 'Edit Service' : 'Add Service' ?></h2>
                        <p>Set the name, price, duration and status.</p>
                    </div>
                </div>

                <form method="post" class="service-form">

                    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

                    <input
                        name="name"
                        placeholder="Service name"
                        value="<?= htmlspecialchars($edit['name'] ?? '') ?>"
                        required
                    >

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        placeholder="Price"
                        value="<?= htmlspecialchars($edit['price'] ?? '') ?>"
                        required
                    >

                    <input
                        type="number"
                        min="0"
                        name="duration"
                        placeholder="Duration (min)"
                        value="<?= htmlspecialchars($edit['duration'] ?? '') ?>"
                    >

                    <select name="status">
                        <option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>

                    <textarea name="description" placeholder="Description"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>

                    <div class="form-actions">
                        <button><?= $edit ? 'Update Service' : 'Add Service' ?></button>
                        <?php if ($edit): ?>
                            <a href="services.php">Cancel</a>
                        <?php endif; ?>
                    </div>

                </form>

            </section>


            <section class="panel">

                <div class="panel-head">
                    <div>
                        <h2>Service List</h2>
                        <p>Manage the services customers can book.</p>
                    </div>

                    <span class="right-note"><?= $total ?> services</span>
                </div>

                <div class="table-wrap">

                    <table class="service-table">

                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Service</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($list && mysqli_num_rows($list)): ?>

                                <?php $i = 1; while ($s = mysqli_fetch_assoc($list)): ?>

                                    <tr>

                                        <td><?= $i++ ?></td>

                                        <td class="service-name">
                                            <strong><?= htmlspecialchars($s['name']) ?></strong>
                                            <small><?= htmlspecialchars($s['description'] ?? '') ?></small>
                                        </td>

                                        <td class="price">
                                            Rs. <?= number_format((float) $s['price'], 2) ?>
                                        </td>

                                        <td>
                                            <?= $s['duration'] !== null ? (int) $s['duration'] . ' min' : '—' ?>
                                        </td>

                                        <td>
                                            <span class="status-badge <?= $s['status'] === 'active' ? 'ok' : 'off' ?>">
                                                <?= ucfirst($s['status']) ?>
                                            </span>
                                        </td>

                                        <td class="actions">
                                            <a class="edit" href="?edit=<?= $s['id'] ?>">Edit</a>
                                            <a class="toggle" href="?toggle=<?= $s['id'] ?>">
                                                <?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                            <a
                                                href="?delete=<?= $s['id'] ?>"
                                                onclick="return confirm('Delete this service?')"
                                            >Delete</a>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" class="empty-row">
                                        No services available yet.
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