<?php

require_once '../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

/* Add Product */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? 0);
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') {

        $error = 'Product name is required.';

    } elseif ($price < 0 || $stock < 0) {

        $error = 'Price and stock cannot be negative.';

    } else {

        $st = mysqli_prepare(
            $conn,
            'INSERT INTO products(name, price, stock, description)
             VALUES(?, ?, ?, ?)'
        );

        mysqli_stmt_bind_param(
            $st,
            'sdis',
            $name,
            $price,
            $stock,
            $desc
        );

        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);

        header('Location: products.php?saved=1');
        exit;
    }
}

/* Delete Product */
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {
        mysqli_query(
            $conn,
            "DELETE FROM products WHERE id=$id"
        );
    }

    header('Location: products.php?deleted=1');
    exit;
}

/* Success Messages */
if (isset($_GET['saved'])) {
    $success = 'Product added successfully.';
}

if (isset($_GET['deleted'])) {
    $success = 'Product deleted successfully.';
}

/* Product List */
$list = mysqli_query(
    $conn,
    'SELECT * FROM products ORDER BY id DESC'
);

/* Total Products */
$total = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            'SELECT COUNT(*) FROM products'
        )
    )[0] ?? 0
);

/* Total Stock */
$stock = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            'SELECT COALESCE(SUM(stock),0) FROM products'
        )
    )[0] ?? 0
);

/* Low Stock */
$low = (int) (
    mysqli_fetch_row(
        mysqli_query(
            $conn,
            'SELECT COUNT(*) FROM products WHERE stock<=5'
        )
    )[0] ?? 0
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
        Products | Web Studio Admin
    </title>

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

    <style>

        .product-stats {
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

        .product-form {
            display: grid;
            grid-template-columns: 1.2fr .6fr .5fr 1.5fr auto;
            gap: 10px;
            align-items: start;
        }

        .product-form input,
        .product-form textarea {
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

        .product-form textarea {
            min-height: 43px;
            resize: vertical;
        }

        .product-form input:focus,
        .product-form textarea:focus {
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

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf0f3;
            text-align: left;
        }

        .product-table th {
            font-size: 8px;
            color: #8a94a4;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .product-table td {
            font-size: 10px;
        }

        .product-name strong {
            display: block;
            font-size: 11px;
            color: #25344d;
        }

        .product-name small {
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

        .stock-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 700;
        }

        .stock-badge.ok {
            background: #e8faf3;
            color: #13845f;
        }

        .stock-badge.low {
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

            .product-form {
                grid-template-columns: 1fr 1fr;
            }

            .product-form textarea {
                grid-column: 1 / -1;
            }

            .form-actions {
                grid-column: 1 / -1;
            }

            .product-stats {
                grid-template-columns: 1fr 1fr;
            }

            .product-stats .mini-stat:last-child {
                grid-column: 1 / -1;
            }

        }

        @media (max-width: 650px) {

            .product-stats {
                grid-template-columns: 1fr;
            }

            .product-stats .mini-stat:last-child {
                grid-column: auto;
            }

            .product-form {
                grid-template-columns: 1fr;
            }

            .product-form textarea,
            .form-actions {
                grid-column: auto;
            }

            .table-wrap {
                margin-left: -5px;
                margin-right: -5px;
            }

            .product-table {
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
                Product store
            </strong>

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

            <a
                class="active"
                href="products.php"
            >
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

            <!-- Page Heading -->
            <div class="page-heading">

                <div>

                    <h1>
                        Products
                    </h1>

                    <p>
                        Add and manage albums, frames and studio products.
                    </p>

                </div>

                <div class="date-pill">

                    Product management

                    <span>
                        ⌄
                    </span>

                </div>

            </div>


            <!-- Error Message -->
            <?php if ($error): ?>

                <div class="notice error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- Success Message -->
            <?php if ($success): ?>

                <div class="notice success">

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- Product Stats -->
            <div class="product-stats">

                <div class="mini-stat">

                    <span>
                        Total products
                    </span>

                    <strong>
                        <?= $total ?>
                    </strong>

                    <em>
                        Products in catalogue
                    </em>

                </div>


                <div class="mini-stat">

                    <span>
                        Total stock
                    </span>

                    <strong>
                        <?= $stock ?>
                    </strong>

                    <em>
                        Units currently listed
                    </em>

                </div>


                <div class="mini-stat">

                    <span>
                        Low stock
                    </span>

                    <strong>
                        <?= $low ?>
                    </strong>

                    <em>
                        5 units or fewer
                    </em>

                </div>

            </div>


            <!-- Add Product -->
            <section class="panel form-panel">

                <div class="panel-head">

                    <div>

                        <h2>
                            Add Product
                        </h2>

                        <p>
                            Create a product customers can purchase.
                        </p>

                    </div>

                </div>


                <form
                    method="post"
                    class="product-form"
                >

                    <input
                        name="name"
                        placeholder="Product name"
                        required
                    >

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        placeholder="Price"
                        required
                    >

                    <input
                        type="number"
                        min="0"
                        name="stock"
                        placeholder="Stock"
                        required
                    >

                    <textarea
                        name="description"
                        placeholder="Description"
                    ></textarea>

                    <div class="form-actions">

                        <button>
                            Add Product
                        </button>

                    </div>

                </form>

            </section>


            <!-- Product List -->
            <section class="panel">

                <div class="panel-head">

                    <div>

                        <h2>
                            Product List
                        </h2>

                        <p>
                            Manage your current store inventory.
                        </p>

                    </div>

                    <span class="right-note">
                        <?= $total ?> products
                    </span>

                </div>


                <div class="table-wrap">

                    <table class="product-table">

                        <thead>

                            <tr>

                                <th>
                                    S/N
                                </th>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Stock
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (
                                $list &&
                                mysqli_num_rows($list)
                            ): ?>

                                <?php

                                $i = 1;

                                while (
                                    $p = mysqli_fetch_assoc($list)
                                ):

                                ?>

                                    <tr>

                                        <td>
                                            <?= $i++ ?>
                                        </td>


                                        <td class="product-name">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $p['name']
                                                ) ?>
                                            </strong>

                                            <small>
                                                <?= htmlspecialchars(
                                                    $p['description'] ?? ''
                                                ) ?>
                                            </small>

                                        </td>


                                        <td class="price">

                                            Rs.
                                            <?= number_format(
                                                (float) $p['price'],
                                                2
                                            ) ?>

                                        </td>


                                        <td>

                                            <span
                                                class="stock-badge <?= $p['stock'] <= 5 ? 'low' : 'ok' ?>"
                                            >

                                                <?= $p['stock'] ?>
                                                units

                                            </span>

                                        </td>


                                        <td class="actions">

                                            <a
                                                href="?delete=<?= $p['id'] ?>"
                                                onclick="return confirm('Delete this product?')"
                                            >
                                                Delete
                                            </a>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="5"
                                        class="empty-row"
                                    >
                                        No products available yet.
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