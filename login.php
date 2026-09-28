<?php

require_once __DIR__ . '/includes/auth.php';


if (is_logged_in()) {

    header('Location: profile.php');

    exit;
}


$page_title = 'Login | ' . SITE_NAME;

$error = '';


$next = trim(
    $_GET['next'] ?? $_POST['next'] ?? 'profile.php'
);


/* Validate redirect URL */

if (
    !preg_match(
        '/^[a-zA-Z0-9_\-\.]+(?:\?[a-zA-Z0-9_=&\-\.]+)?$/',
        $next
    )
    || str_contains($next, '..')
) {

    $next = 'profile.php';
}


/* Login */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $password = $_POST['password'] ?? '';


    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, password_hash, status, email_verified
         FROM users
         WHERE email = ?
         LIMIT 1'
    );


    mysqli_stmt_bind_param(
        $stmt,
        's',
        $email
    );


    mysqli_stmt_execute($stmt);


    $user = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );


    mysqli_stmt_close($stmt);


    if (
        $user
        && $user['status'] === 'active'
        && password_verify(
            $password,
            $user['password_hash']
        )
    ) {

        if ((int) $user['email_verified'] !== 1) {

            $error = 'Please verify your email before logging in. '
                . 'Check your inbox (and spam folder) for the verification link we sent you.';

        } else {

            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];

            header('Location: ' . $next);

            exit;
        }

    } else {

        $error = 'Invalid email or password.';
    }
}


require __DIR__ . '/includes/header.php';

?>


<main class="container auth-page">

    <div class="auth-card">

        <div class="eyebrow">
            CUSTOMER ACCOUNT
        </div>


        <h1>
            Welcome back
        </h1>


        <p class="auth-subtitle">
            Login to choose a service, check your date and
            manage your bookings.
        </p>


        <?php if ($error): ?>

            <div class="alert error-alert">

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <form method="post">

            <input
                type="hidden"
                name="next"
                value="<?= e($next) ?>"
            >


            <label>

                Email

                <input
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                >

            </label>


            <label>

                Password

                <input
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >

            </label>


            <button
                class="btn gold auth-btn"
                type="submit"
            >
                Login
            </button>

        </form>


        <p class="auth-foot">

            New customer?

            <a href="register.php">
                Create an account
            </a>

        </p>

    </div>

</main>


<?php

require __DIR__ . '/includes/footer.php';

?>