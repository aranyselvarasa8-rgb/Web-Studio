<?php

require_once '../includes/auth.php';


/* Already logged in */

if (isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$error = '';


/* Rate limiting
   Maximum 5 login attempts in 10 minutes
*/

if (empty($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = [];
}

$_SESSION['admin_login_attempts'] = array_filter(
    $_SESSION['admin_login_attempts'],
    fn($t) => $t > time() - 600
);


/* Login */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Check login attempts */

    if (count($_SESSION['admin_login_attempts']) >= 5) {

        $error = 'Too many attempts. Please wait a few minutes and try again.';

    }

    /* Check CSRF */

    elseif (!verify_csrf($_POST['csrf_token'] ?? null)) {

        $error = 'Security check failed. Please try again.';

    }

    else {

        $email = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';


        /* Find admin */

        $stmt = mysqli_prepare(
            $conn,
            'SELECT id, name, email, password_hash, status
             FROM admins
             WHERE email = ?
             LIMIT 1'
        );

        mysqli_stmt_bind_param(
            $stmt,
            's',
            $email
        );

        mysqli_stmt_execute($stmt);

        $admin = mysqli_stmt_get_result($stmt)->fetch_assoc();

        mysqli_stmt_close($stmt);


        /* Check admin login */

        if (
            $admin &&
            $admin['status'] === 'active' &&
            password_verify(
                $password,
                $admin['password_hash']
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['admin'] = [
                'id' => $admin['id'],
                'name' => $admin['name'],
                'email' => $admin['email']
            ];

            unset($_SESSION['admin_login_attempts']);

            header('Location: index.php');
            exit;
        }


        /* Invalid login */

        $_SESSION['admin_login_attempts'][] = time();

        $error = 'Invalid email or password.';
    }
}

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
        Admin Login | Web Studio
    </title>

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

</head>


<body class="login-page">

<div class="login-shell">


    <!-- Login Brand -->

    <section class="login-brand">

        <div class="brand-logo">

            <img
                src="../assets/images/logo-light.png"
                alt="Web Studio"
            >

        </div>


        <div>

            <p class="eyebrow">
                WEB STUDIO
            </p>

            <h1>
                Admin Portal
            </h1>

            <p class="brand-copy">
                Manage your photography studio, bookings,
                services, packages and products from one place.
            </p>

        </div>


        <div class="brand-footer">

            © <?= date('Y') ?> Web Studio

        </div>

    </section>


    <!-- Login Form -->

    <section class="login-card-wrap">

        <form
            class="login-card"
            method="post"
            autocomplete="off"
        >


            <!-- Heading -->

            <div class="login-heading">

                <span class="login-icon">
                    JS
                </span>

                <p class="eyebrow">
                    WELCOME BACK
                </p>

                <h2>
                    Admin Login
                </h2>

                <p>
                    Sign in to continue to your dashboard.
                </p>

            </div>


            <!-- Error Message -->

            <?php if ($error): ?>

                <div class="login-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- CSRF Token -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token()) ?>"
            >


            <!-- Email -->

            <label>
                Email
            </label>

            <input
                type="email"
                name="username"
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                placeholder="Enter admin email"
                required
                autofocus
            >


            <!-- Password -->

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Enter password"
                required
            >


            <!-- Login Button -->

            <button
                type="submit"
                class="login-button"
            >
                Sign In
                <span>→</span>
            </button>

        </form>

    </section>

</div>

</body>

</html>