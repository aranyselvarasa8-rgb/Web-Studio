<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';


if (is_logged_in()) {

    header('Location: profile.php');

    exit;
}


$page_title = 'Register | ' . SITE_NAME;

$errors = [];
$registered = false;


/* Registration */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $password = $_POST['password'] ?? '';

    $confirm = $_POST['confirm_password'] ?? '';


    /* Validation */

    if ($name === '') {

        $errors[] = 'Name is required.';
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = 'Enter a valid email address.';
    }


    if (strlen($password) < 8) {

        $errors[] = 'Password must be at least 8 characters.';
    }


    if ($password !== $confirm) {

        $errors[] = 'Passwords do not match.';
    }


    /* Check existing email */

    if (!$errors) {

        $stmt = mysqli_prepare(
            $conn,
            'SELECT id FROM users WHERE email=? LIMIT 1'
        );


        mysqli_stmt_bind_param(
            $stmt,
            's',
            $email
        );


        mysqli_stmt_execute($stmt);

        mysqli_stmt_store_result($stmt);


        if (mysqli_stmt_num_rows($stmt)) {

            $errors[] =
                'An account with this email already exists.';
        }


        mysqli_stmt_close($stmt);
    }


    /* Create account */

    if (!$errors) {

        $hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO users
             (name, phone, email, password_hash, verification_token, verification_expires)
             VALUES (?, ?, ?, ?, ?, ?)'
        );


        mysqli_stmt_bind_param(
            $stmt,
            'ssssss',
            $name,
            $phone,
            $email,
            $hash,
            $token,
            $expires
        );


        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            $verifyLink = rtrim(SITE_URL, '/')
                . '/verify.php?email=' . urlencode($email)
                . '&token=' . $token;

            $mailSent = send_verification_email($email, $name, $verifyLink);

            if ($mailSent) {
                $registered = true;
            } else {
                // Account was created but the email failed to send (e.g. SMTP
                // credentials not set up yet in includes/config.php). Let the
                // user know instead of silently losing their verification link.
                $errors[] =
                    'Account created, but the verification email could not be sent. '
                    . 'Please contact support or check the site\'s email setup.';
            }
        } else {

            $errors[] =
                'Registration failed. Please try again.';

            mysqli_stmt_close($stmt);
        }
    }
}


require __DIR__ . '/includes/header.php';

?>


<main
    class="container"
    style="padding:60px 0;max-width:600px"
>

    <h1>
        Create Account
    </h1>


    <?php if ($registered): ?>

        <div class="alert success-alert">
            <p>
                <strong>Almost there!</strong> We've sent a verification
                link to your email address. Please check your inbox
                (and spam/junk folder, just in case) and click the link
                to activate your account before logging in.
            </p>
        </div>

    <?php else: ?>


    <?php if ($errors): ?>

        <div class="alert">

            <?php foreach ($errors as $err): ?>

                <p>
                    <?= e($err) ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <form
        method="post"
        class="card"
        style="padding:25px"
    >

        <label>

            Full Name

            <input
                name="name"
                required
                value="<?= e($_POST['name'] ?? '') ?>"
            >

        </label>


        <label>

            Phone

            <input
                name="phone"
                value="<?= e($_POST['phone'] ?? '') ?>"
            >

        </label>


        <label>

            Email

            <input
                type="email"
                name="email"
                required
                value="<?= e($_POST['email'] ?? '') ?>"
            >

        </label>


        <label>

            Password

            <input
                type="password"
                name="password"
                required
                minlength="8"
            >

        </label>


        <label>

            Confirm Password

            <input
                type="password"
                name="confirm_password"
                required
                minlength="8"
            >

        </label>


        <button
            class="btn"
            type="submit"
        >
            Create Account
        </button>


        <p>

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </p>

    </form>

    <?php endif; ?>

</main>


<?php

require __DIR__ . '/includes/footer.php';

?>