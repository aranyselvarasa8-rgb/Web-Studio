<?php

require_once __DIR__ . '/includes/auth.php';

$page_title = 'Verify Email | ' . SITE_NAME;

$email = strtolower(trim($_GET['email'] ?? ''));
$token = trim($_GET['token'] ?? '');

$status = 'error'; // error | success | expired
$message = 'This verification link is invalid.';

if ($email !== '' && $token !== '') {

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, email_verified, verification_token, verification_expires
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($user && (int) $user['email_verified'] === 1) {

        $status = 'success';
        $message = 'Your email is already verified. You can log in now.';

    } elseif ($user && hash_equals((string) $user['verification_token'], $token)) {

        if (strtotime($user['verification_expires']) < time()) {

            $status = 'expired';
            $message = 'This verification link has expired. Please register again or request a new link.';

        } else {

            $upd = mysqli_prepare(
                $conn,
                'UPDATE users
                 SET email_verified = 1, verification_token = NULL, verification_expires = NULL
                 WHERE id = ?'
            );
            mysqli_stmt_bind_param($upd, 'i', $user['id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $status = 'success';
            $message = 'Your email has been verified successfully. You can log in now.';
        }
    }
}

require __DIR__ . '/includes/header.php';

?>

<main class="container" style="padding:60px 0;max-width:600px">

    <div class="card" style="padding:30px;text-align:center">

        <h1><?= $status === 'success' ? 'Email Verified' : 'Verification' ?></h1>

        <p><?= e($message) ?></p>

        <?php if ($status === 'success'): ?>
            <a class="btn gold" href="login.php">Go to Login</a>
        <?php else: ?>
            <a class="btn" href="register.php">Back to Register</a>
        <?php endif; ?>

    </div>

</main>

<?php

require __DIR__ . '/includes/footer.php';

?>
