<?php

require_once __DIR__ . '/includes/auth.php';

require_login();


$user = current_user();

$page_title = 'My Profile | ' . SITE_NAME;

$message = '';


/* Update profile */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $phone = trim($_POST['phone'] ?? '');


    if ($name === '') {

        $message = 'Name is required.';

    } else {

        $stmt = mysqli_prepare(
            $conn,
            'UPDATE users SET name=?, phone=? WHERE id=?'
        );


        mysqli_stmt_bind_param(
            $stmt,
            'ssi',
            $name,
            $phone,
            $user['id']
        );


        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);


        $message = 'Profile updated successfully.';

        $user = current_user();
    }
}


require __DIR__ . '/includes/header.php';

?>


<main
    class="container"
    style="padding:60px 0;max-width:650px"
>

    <h1>
        My Profile
    </h1>


    <?php if ($message): ?>

        <div class="alert">

            <?= e($message) ?>

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
                value="<?= e($user['name']) ?>"
            >

        </label>


        <label>

            Phone

            <input
                name="phone"
                value="<?= e($user['phone'] ?? '') ?>"
            >

        </label>


        <label>

            Email

            <input
                value="<?= e($user['email']) ?>"
                disabled
            >

        </label>


        <button
            class="btn"
            type="submit"
        >
            Save Changes
        </button>


        <a
            class="btn"
            href="logout.php"
        >
            Logout
        </a>

    </form>

</main>


<?php

require __DIR__ . '/includes/footer.php';

?>