<?php

require_once __DIR__ . '/config.php';


/* Check login */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}


/* Get current user */
function current_user(): ?array
{
    global $conn;

    if (!is_logged_in()) {
        return null;
    }

    $id = (int) $_SESSION['user_id'];

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, name, phone, email, status, created_at
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $user = mysqli_fetch_assoc($result) ?: null;

    mysqli_stmt_close($stmt);


    /* Check user status */
    if (!$user || $user['status'] !== 'active') {

        unset($_SESSION['user_id']);

        return null;
    }

    return $user;
}


/* Require login */
function require_login(string $next = 'booking.php'): void
{
    if (!is_logged_in() || !current_user()) {

        $next = preg_replace(
            '/[^a-zA-Z0-9_\-\.\?=&]/',
            '',
            $next
        );

        header(
            'Location: login.php?next=' . urlencode($next)
        );

        exit;
    }
}


/* Escape HTML */
function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* Create CSRF token */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}


/* Verify CSRF token */
function verify_csrf(?string $token): bool
{
    return (
        !empty($token) &&
        !empty($_SESSION['csrf_token']) &&
        hash_equals(
            $_SESSION['csrf_token'],
            $token
        )
    );
}

?>