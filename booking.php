<?php
require_once __DIR__ . '/includes/auth.php';
require_login('booking.php');

$user = current_user();
$page_title = 'Book a Session | ' . SITE_NAME;

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Selected services / packages
|--------------------------------------------------------------------------
*/
$selectedKeys = $_POST['item_keys'] ?? ($_GET['item_keys'] ?? []);

if (!is_array($selectedKeys)) {
    $selectedKeys = $selectedKeys !== ''
        ? explode(',', (string)$selectedKeys)
        : [];
}

$selectedKeys = array_values(
    array_unique(
        array_filter(
            array_map('strval', $selectedKeys),
            function ($v) {
                return preg_match('/^(service|package)-\d+$/', $v);
            }
        )
    )
);

/*
|--------------------------------------------------------------------------
| Load active services and packages
|--------------------------------------------------------------------------
*/
$services = mysqli_query(
    $conn,
    "SELECT id, name, price
     FROM services
     WHERE status='active'
     ORDER BY name"
);

$packages = mysqli_query(
    $conn,
    "SELECT id, name, price
     FROM packages
     WHERE status='active'
     ORDER BY price"
);

$serviceRows = [];
$packageRows = [];

while ($row = mysqli_fetch_assoc($services)) {
    $serviceRows[(int)$row['id']] = $row;
}

while ($row = mysqli_fetch_assoc($packages)) {
    $packageRows[(int)$row['id']] = $row;
}

/*
|--------------------------------------------------------------------------
| Rebuild selected items securely from database
|--------------------------------------------------------------------------
*/
$selectedItems = [];
$selectedTotal = 0.0;

$selectedServiceIds = [];
$selectedPackageIds = [];

foreach ($selectedKeys as $key) {

    [$kind, $rawId] = explode('-', $key, 2);

    $itemId = (int)$rawId;

    if ($kind === 'service' && isset($serviceRows[$itemId])) {

        $price = (float)$serviceRows[$itemId]['price'];

        $selectedItems[] = [
            'key'   => $key,
            'type'  => 'service',
            'id'    => $itemId,
            'name'  => $serviceRows[$itemId]['name'],
            'price' => $price
        ];

        $selectedServiceIds[] = $itemId;
        $selectedTotal += $price;

    } elseif ($kind === 'package' && isset($packageRows[$itemId])) {

        $price = (float)$packageRows[$itemId]['price'];

        $selectedItems[] = [
            'key'   => $key,
            'type'  => 'package',
            'id'    => $itemId,
            'name'  => $packageRows[$itemId]['name'],
            'price' => $price
        ];

        $selectedPackageIds[] = $itemId;
        $selectedTotal += $price;
    }
}

$selectedServiceIds = array_values(array_unique($selectedServiceIds));
$selectedPackageIds = array_values(array_unique($selectedPackageIds));


/*
|--------------------------------------------------------------------------
| Handle booking submission
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please refresh and try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    /*
     * Event type is removed.
     * Selected services/packages are the booking items.
     */
    $eventType = '';

    $date = trim($_POST['event_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');

    $location = trim($_POST['location'] ?? '');
    $message = trim($_POST['message'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (!$error && count($selectedItems) === 0) {
        $error = 'Please select at least one service or package.';
    }

    if (!$error && !$name) {
        $error = 'Name is required.';
    }

    if (!$error && !$phone) {
        $error = 'Phone number is required.';
    }

    if (!$error && !$date) {
        $error = 'Please select your event date.';
    }

    if (
        !$error &&
        strtotime($date) < strtotime(date('Y-m-d'))
    ) {
        $error = 'Please choose a future date.';
    }

    if (!$error && !$startTime) {
        $error = 'Please select the event start time.';
    }

    if (!$error && !$endTime) {
        $error = 'Please select the event end time.';
    }

    /*
     * Compare 24-hour values.
     */
    if (
        !$error &&
        $startTime &&
        $endTime &&
        strtotime($endTime) <= strtotime($startTime)
    ) {
        $error = 'End time must be later than start time.';
    }


    /*
    |--------------------------------------------------------------------------
    | Check date availability
    |--------------------------------------------------------------------------
    */
    if (!$error) {

        $st = mysqli_prepare(
            $conn,
            "SELECT COUNT(*)
             FROM bookings
             WHERE event_date = ?
             AND status IN ('pending','confirmed')"
        );

        mysqli_stmt_bind_param(
            $st,
            's',
            $date
        );

        mysqli_stmt_execute($st);

        $result = mysqli_stmt_get_result($st);
        $busy = (int)(mysqli_fetch_row($result)[0] ?? 0);

        mysqli_stmt_close($st);

        if ($busy > 0) {
            $error =
                'Sorry, that date is already reserved. Please choose another date.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save booking
    |--------------------------------------------------------------------------
    */
    if (!$error) {

        mysqli_begin_transaction($conn);

        try {

            /*
            |--------------------------------------------------------------------------
            | Final availability check
            |--------------------------------------------------------------------------
            */
            $st = mysqli_prepare(
                $conn,
                "SELECT COUNT(*)
                 FROM bookings
                 WHERE event_date = ?
                 AND status IN ('pending','confirmed')
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param(
                $st,
                's',
                $date
            );

            mysqli_stmt_execute($st);

            $result = mysqli_stmt_get_result($st);
            $busy = (int)(mysqli_fetch_row($result)[0] ?? 0);

            mysqli_stmt_close($st);

            if ($busy > 0) {
                throw new RuntimeException(
                    'That date was just reserved by another booking. Please choose another date.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Legacy columns
            |--------------------------------------------------------------------------
            |
            | Keep first selected service/package in old columns
            | for compatibility with existing project.
            |
            */
            $primaryServiceId = $selectedServiceIds[0] ?? null;
            $primaryPackageId = $selectedPackageIds[0] ?? null;


            /*
            |--------------------------------------------------------------------------
            | Insert main booking
            |--------------------------------------------------------------------------
            */
            $st = mysqli_prepare(
                $conn,
                "INSERT INTO bookings
                (
                    user_id,
                    name,
                    phone,
                    email,
                    event_type,
                    service_id,
                    package_id,
                    event_date,
                    start_time,
                    end_time,
                    location,
                    message,
                    status,
                    total_amount
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'pending',
                    ?
                )"
            );


            mysqli_stmt_bind_param(
                $st,
                'issssiisssssd',
                $user['id'],
                $name,
                $phone,
                $email,
                $eventType,
                $primaryServiceId,
                $primaryPackageId,
                $date,
                $startTime,
                $endTime,
                $location,
                $message,
                $selectedTotal
            );


            if (!mysqli_stmt_execute($st)) {
                throw new RuntimeException(
                    'Could not create the booking.'
                );
            }


            $bookingId = mysqli_insert_id($conn);

            mysqli_stmt_close($st);


            /*
            |--------------------------------------------------------------------------
            | Save EVERY selected service/package
            |--------------------------------------------------------------------------
            */
            foreach ($selectedItems as $item) {

                $serviceId =
                    $item['type'] === 'service'
                        ? $item['id']
                        : null;

                $packageId =
                    $item['type'] === 'package'
                        ? $item['id']
                        : null;

                $itemName = $item['name'];
                $itemPrice = (float)$item['price'];


                $st = mysqli_prepare(
                    $conn,
                    "INSERT INTO booking_services
                    (
                        booking_id,
                        service_id,
                        package_id,
                        item_name,
                        price
                    )
                    VALUES (?, ?, ?, ?, ?)"
                );


                mysqli_stmt_bind_param(
                    $st,
                    'iiisd',
                    $bookingId,
                    $serviceId,
                    $packageId,
                    $itemName,
                    $itemPrice
                );


                if (!mysqli_stmt_execute($st)) {

                    throw new RuntimeException(
                        'Could not save the selected booking items.'
                    );
                }

                mysqli_stmt_close($st);
            }


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */
            mysqli_commit($conn);


            $success =
                'Booking request submitted successfully. ' .
                'Your booking is now pending admin confirmation.';


            /*
            |--------------------------------------------------------------------------
            | Clear form selection
            |--------------------------------------------------------------------------
            */
            $_POST = [];

            $selectedKeys = [];
            $selectedItems = [];
            $selectedTotal = 0.0;

            $selectedServiceIds = [];
            $selectedPackageIds = [];


        } catch (Throwable $ex) {

            mysqli_rollback($conn);

            $error =
                $ex->getMessage()
                ?: 'Booking could not be submitted. Please try again.';
        }
    }
}


require __DIR__ . '/includes/header.php';
?>


<!-- =========================================================
     PAGE BANNER
========================================================= -->

<section class="page-banner">
    <div class="container">

        <span class="eyebrow">BOOKING</span>

        <h1>
            Reserve your photography date.
        </h1>

        <p>
            Select multiple services or packages, choose your event date
            and time, then send your booking request.
        </p>

    </div>
</section>


<!-- =========================================================
     BOOKING SECTION
========================================================= -->

<section class="section">

    <div class="container booking-page">


        <!-- INTRO -->

        <div class="booking-intro">

            <div>

                <h2>
                    Book with <?= e(SITE_NAME) ?>
                </h2>

                <p>
                    Your account details are already filled in.
                    Select everything you need and we will review the request.
                </p>

            </div>


            <a
                class="btn outline-dark"
                href="my-bookings.php"
            >
                My Bookings
            </a>

        </div>


        <!-- SUCCESS -->

        <?php if ($success): ?>

            <div class="alert success-alert">

                <?= e($success) ?>

                <a href="my-bookings.php">
                    View My Bookings →
                </a>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if ($error): ?>

            <div class="alert error-alert">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <!-- =====================================================
             FORM
        ====================================================== -->

        <form
            method="post"
            class="booking-card"
            id="bookingForm"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >


            <!-- =================================================
                 STEP 01
            ================================================== -->

            <div class="booking-step">

                <span>01</span>

                <div>

                    <h3>
                        Select services or packages
                    </h3>

                    <p>
                        You can choose more than one.
                        The total updates automatically.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 SERVICES + PACKAGES
            ================================================== -->

            <div
                class="choice-grid"
                id="choiceGrid"
            >


                <!-- SERVICES -->

                <?php foreach ($serviceRows as $s): ?>

                    <?php

                    $sid = (int)$s['id'];

                    $key = 'service-' . $sid;

                    $isSelected =
                        in_array(
                            $key,
                            $selectedKeys,
                            true
                        );

                    ?>

                    <label
                        class="choice-card <?= $isSelected ? 'selected' : '' ?>"
                    >

                        <input
                            type="checkbox"
                            name="item_keys[]"
                            value="<?= e($key) ?>"
                            data-type="service"
                            data-id="<?= $sid ?>"
                            data-key="<?= e($key) ?>"
                            data-name="<?= e($s['name']) ?>"
                            data-price="<?= e($s['price']) ?>"
                            <?= $isSelected ? 'checked' : '' ?>
                        >


                        <span class="choice-type">
                            SERVICE
                        </span>


                        <strong>
                            <?= e($s['name']) ?>
                        </strong>


                        <b>
                            Rs.
                            <?= number_format(
                                (float)$s['price'],
                                2
                            ) ?>
                        </b>

                    </label>

                <?php endforeach; ?>


                <!-- PACKAGES -->

                <?php foreach ($packageRows as $p): ?>

                    <?php

                    $pid = (int)$p['id'];

                    $key = 'package-' . $pid;

                    $isSelected =
                        in_array(
                            $key,
                            $selectedKeys,
                            true
                        );

                    ?>

                    <label
                        class="choice-card <?= $isSelected ? 'selected' : '' ?>"
                    >

                        <input
                            type="checkbox"
                            name="item_keys[]"
                            value="<?= e($key) ?>"
                            data-type="package"
                            data-id="<?= $pid ?>"
                            data-key="<?= e($key) ?>"
                            data-name="<?= e($p['name']) ?>"
                            data-price="<?= e($p['price']) ?>"
                            <?= $isSelected ? 'checked' : '' ?>
                        >


                        <span class="choice-type">
                            PACKAGE
                        </span>


                        <strong>
                            <?= e($p['name']) ?>
                        </strong>


                        <b>
                            Rs.
                            <?= number_format(
                                (float)$p['price'],
                                2
                            ) ?>
                        </b>

                    </label>

                <?php endforeach; ?>


            </div>


            <!-- =================================================
                 SELECTED ITEMS BOX
            ================================================== -->

            <div
                class="booking-selection-box"
                id="selectionBox"
                style="<?= $selectedItems ? '' : 'display:none' ?>"
            >

                <div class="selection-box-head">

                    <div>

                        <small>
                            YOUR SELECTION
                        </small>

                        <strong id="selectedCount">
                            <?= count($selectedItems) ?>
                            item(s) selected
                        </strong>

                    </div>


                    <div class="selection-total">

                        <small>
                            TOTAL
                        </small>

                        <strong id="selectedTotal">
                            Rs.
                            <?= number_format(
                                $selectedTotal,
                                2
                            ) ?>
                        </strong>

                    </div>

                </div>


                <div
                    class="selected-items-note"
                    id="selectedItemsNote"
                >
                    ✓ Your selected services/packages
                    are included in the booking total.
                </div>


                <div
                    class="selected-items-list"
                    id="selectedItemsList"
                >

                    <?php foreach ($selectedItems as $item): ?>

                        <div class="selected-item">

                            <span>

                                <small>
                                    <?= e(
                                        strtoupper(
                                            $item['type']
                                        )
                                    ) ?>
                                </small>

                                <?= e($item['name']) ?>

                            </span>


                            <strong>
                                Rs.
                                <?= number_format(
                                    $item['price'],
                                    2
                                ) ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- =================================================
                 STEP 02
            ================================================== -->

            <div class="booking-step">

                <span>02</span>

                <div>

                    <h3>
                        Choose your event date & time
                    </h3>

                    <p>
                        Select the date and the expected
                        photography/videography time.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 DATE + TIME
            ================================================== -->

            <div class="date-check">


                <!-- DATE -->

                <label>

                    Event date

                    <input
                        type="date"
                        id="event_date"
                        name="event_date"
                        min="<?= date(
                            'Y-m-d',
                            strtotime('+1 day')
                        ) ?>"
                        value="<?= e(
                            $_POST['event_date']
                            ?? ($_GET['date'] ?? '')
                        ) ?>"
                        required
                    >

                </label>


                <!-- START TIME -->

                <label>

                    Start time

                    <select
                        id="start_time"
                        name="start_time"
                        required
                    >

                        <option value="">
                            Select start time
                        </option>


                        <?php

                        $savedStart =
                            $_POST['start_time']
                            ?? '09:00';


                        for ($h = 0; $h < 24; $h++) {

                            for (
                                $m = 0;
                                $m < 60;
                                $m += 30
                            ) {

                                $value =
                                    sprintf(
                                        '%02d:%02d',
                                        $h,
                                        $m
                                    );


                                $hour12 =
                                    $h % 12;

                                if ($hour12 === 0) {
                                    $hour12 = 12;
                                }


                                $ampm =
                                    $h < 12
                                        ? 'AM'
                                        : 'PM';


                                $label =
                                    sprintf(
                                        '%02d:%02d %s',
                                        $hour12,
                                        $m,
                                        $ampm
                                    );

                                ?>

                                <option
                                    value="<?= e($value) ?>"
                                    <?= $savedStart === $value
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($label) ?>
                                </option>

                                <?php
                            }
                        }

                        ?>

                    </select>

                </label>


                <!-- END TIME -->

                <label>

                    End time

                    <select
                        id="end_time"
                        name="end_time"
                        required
                    >

                        <option value="">
                            Select end time
                        </option>


                        <?php

                        $savedEnd =
                            $_POST['end_time']
                            ?? '18:00';


                        for ($h = 0; $h < 24; $h++) {

                            for (
                                $m = 0;
                                $m < 60;
                                $m += 30
                            ) {

                                $value =
                                    sprintf(
                                        '%02d:%02d',
                                        $h,
                                        $m
                                    );


                                $hour12 =
                                    $h % 12;

                                if ($hour12 === 0) {
                                    $hour12 = 12;
                                }


                                $ampm =
                                    $h < 12
                                        ? 'AM'
                                        : 'PM';


                                $label =
                                    sprintf(
                                        '%02d:%02d %s',
                                        $hour12,
                                        $m,
                                        $ampm
                                    );

                                ?>

                                <option
                                    value="<?= e($value) ?>"
                                    <?= $savedEnd === $value
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($label) ?>
                                </option>

                                <?php
                            }
                        }

                        ?>

                    </select>

                </label>


                <!-- AVAILABILITY -->

                <div
                    class="availability-box idle"
                    id="availabilityBox"
                >

                    <span class="availability-dot"></span>

                    <strong>
                        Select a date to check availability
                    </strong>

                </div>

            </div>


            <!-- =================================================
                 STEP 03
            ================================================== -->

            <div class="booking-step">

                <span>03</span>

                <div>

                    <h3>
                        Your details
                    </h3>

                    <p>
                        Confirm your contact information
                        for the selected services or packages.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 CUSTOMER DETAILS
            ================================================== -->

            <div class="form-grid">


                <label>

                    Name

                    <input
                        name="name"
                        required
                        value="<?= e(
                            $_POST['name']
                            ?? $user['name']
                        ) ?>"
                    >

                </label>


                <label>

                    Phone

                    <input
                        name="phone"
                        required
                        value="<?= e(
                            $_POST['phone']
                            ?? $user['phone']
                        ) ?>"
                    >

                </label>


                <label>

                    Email

                    <input
                        type="email"
                        name="email"
                        value="<?= e(
                            $_POST['email']
                            ?? $user['email']
                        ) ?>"
                    >

                </label>


                <label>

                    Location

                    <input
                        name="location"
                        value="<?= e(
                            $_POST['location']
                            ?? ''
                        ) ?>"
                        placeholder="Event venue / city"
                    >

                </label>

            </div>


            <!-- MESSAGE -->

            <label>

                Message

                <textarea
                    name="message"
                    rows="5"
                    placeholder="Tell us about your event, venue or special requirements..."
                ><?= e(
                    $_POST['message'] ?? ''
                ) ?></textarea>

            </label>


            <!-- =================================================
                 SUBMIT
            ================================================== -->

            <div class="booking-submit">

                <div>

                    <small>
                        Booking total
                    </small>

                    <strong id="submitTotal">
                        Rs.
                        <?= number_format(
                            $selectedTotal,
                            2
                        ) ?>
                    </strong>

                </div>


                <button
                    class="btn gold"
                    id="submitBtn"
                    type="submit"
                >
                    Send Booking Request
                </button>

            </div>


        </form>

    </div>

</section>


<!-- =========================================================
     CSS
========================================================= -->

<style>

.booking-selection-box {
    margin: 18px 0 28px;
    padding: 20px;
    border: 1px solid #ead9a5;
    border-radius: 16px;
    background: #fffaf0;
}

.selection-box-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 14px;
}

.selection-box-head small,
.selection-total small {
    display: block;
    color: #9a720d;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
}

.selection-box-head strong,
.selection-total strong {
    display: block;
    margin-top: 4px;
    color: #10264a;
}

.selection-total {
    text-align: right;
}

.selected-items-list {
    display: grid;
    gap: 8px;
}

.selected-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 10px 12px;
    border-radius: 10px;
    background: #fff;
}

.selected-item span {
    display: flex;
    flex-direction: column;
    gap: 2px;
    color: #10264a;
    font-weight: 700;
}

.selected-item span small {
    color: #b18412;
    font-size: 10px;
    letter-spacing: 1px;
}

.selected-item strong {
    color: #a6780a;
    white-space: nowrap;
}

.selected-items-note {
    margin: 0 0 10px;
    padding: 8px 10px;
    border-radius: 8px;
    background: #fff;
    color: #7a6840;
    font-size: 12px;
}


/* =========================================================
   SERVICE / PACKAGE CARDS
========================================================= */

.choice-card {
    position: relative;
    cursor: pointer;
    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        transform .2s ease;
}

.choice-card:hover {
    transform: translateY(-2px);
}

.choice-card input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.choice-card.selected {
    border-color: #c99a2e !important;
    box-shadow:
        0 0 0 3px rgba(201,154,46,.14),
        0 10px 25px rgba(12,35,68,.08);
    background: #fffaf0;
}

.choice-card.selected::after {
    content: '✓';
    position: absolute;
    top: 12px;
    right: 12px;
    width: 26px;
    height: 26px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #c99a2e;
    color: #fff;
    font-weight: 900;
}


/* =========================================================
   DATE + TIME
========================================================= */

.date-check {
    display: grid;
    grid-template-columns:
        1fr
        1fr
        1fr;
    gap: 18px;
    align-items: end;
}

.date-check .availability-box {
    grid-column: 1 / -1;
}


/* =========================================================
   SELECT DROPDOWN
========================================================= */

.date-check select {
    width: 100%;
    min-height: 54px;
    padding: 0 16px;
    border: 1px solid #d9e0ea;
    border-radius: 10px;
    background: #fff;
    color: #10264a;
    font-size: 15px;
    font-weight: 600;
    outline: none;
    cursor: pointer;
}

.date-check select:focus {
    border-color: #c99a2e;
    box-shadow: 0 0 0 3px rgba(201,154,46,.12);
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 800px) {

    .date-check {
        grid-template-columns: 1fr;
    }

    .date-check .availability-box {
        grid-column: auto;
    }

    .selection-box-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .selection-total {
        text-align: left;
    }

    .selected-item {
        align-items: flex-start;
    }

}

</style>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

(function () {

    const form =
        document.getElementById('bookingForm');

    const cards =
        document.querySelectorAll('.choice-card');

    const selectionBox =
        document.getElementById('selectionBox');

    const selectedItemsList =
        document.getElementById('selectedItemsList');

    const selectedCount =
        document.getElementById('selectedCount');

    const selectedTotal =
        document.getElementById('selectedTotal');

    const submitTotal =
        document.getElementById('submitTotal');


    const dateInput =
        document.getElementById('event_date');

    const startInput =
        document.getElementById('start_time');

    const endInput =
        document.getElementById('end_time');

    const box =
        document.getElementById('availabilityBox');


    /*
    |--------------------------------------------------------------------------
    | Money format
    |--------------------------------------------------------------------------
    */

    function money(value) {

        return 'Rs. ' +
            Number(value).toLocaleString(
                'en-LK',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Get selected services/packages
    |--------------------------------------------------------------------------
    */

    function getSelected() {

        return Array.from(
            document.querySelectorAll(
                '.choice-card input[type="checkbox"]:checked'
            )
        ).map(input => ({

            type: input.dataset.type,

            id: Number(
                input.dataset.id
            ),

            key: input.dataset.key,

            name: input.dataset.name,

            price: Number(
                input.dataset.price
            )

        }));
    }


    /*
    |--------------------------------------------------------------------------
    | Render selected items
    |--------------------------------------------------------------------------
    */

    function renderSelection() {

        const selected =
            getSelected();


        selectedItemsList.innerHTML = '';


        let total = 0;


        selected.forEach(item => {

            total += item.price;


            const row =
                document.createElement('div');

            row.className =
                'selected-item';


            const left =
                document.createElement('span');


            const type =
                document.createElement('small');

            type.textContent =
                item.type.toUpperCase();


            left.appendChild(type);


            left.appendChild(
                document.createTextNode(
                    item.name
                )
            );


            const price =
                document.createElement('strong');

            price.textContent =
                money(item.price);


            row.appendChild(left);

            row.appendChild(price);


            selectedItemsList.appendChild(row);

        });


        selectedCount.textContent =
            selected.length +
            ' item' +
            (
                selected.length === 1
                    ? ''
                    : 's'
            ) +
            ' selected';


        selectedTotal.textContent =
            money(total);


        submitTotal.textContent =
            money(total);


        /*
         * IMPORTANT:
         *
         * Selected box appears ONLY
         * when at least one item is selected.
         */

        if (selected.length > 0) {

            selectionBox.style.display =
                '';

        } else {

            selectionBox.style.display =
                'none';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Card checkbox changes
    |--------------------------------------------------------------------------
    */

    cards.forEach(card => {

        const checkbox =
            card.querySelector(
                'input[type="checkbox"]'
            );


        checkbox.addEventListener(
            'change',
            function () {

                card.classList.toggle(
                    'selected',
                    checkbox.checked
                );


                renderSelection();

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Date availability
    |--------------------------------------------------------------------------
    */

    async function checkDate() {

        if (!dateInput.value) {

            box.className =
                'availability-box idle';


            box.innerHTML =
                '<span class="availability-dot"></span>' +
                '<strong>Select a date to check availability</strong>';


            return false;
        }


        box.className =
            'availability-box loading';


        box.innerHTML =
            '<span class="availability-dot"></span>' +
            '<strong>Checking availability...</strong>';


        try {

            const r =
                await fetch(
                    'api/check-availability.php?date=' +
                    encodeURIComponent(
                        dateInput.value
                    ),
                    {
                        headers: {
                            Accept:
                                'application/json'
                        },
                        cache: 'no-store'
                    }
                );


            if (!r.ok) {
                throw new Error(
                    'Availability request failed'
                );
            }


            const d =
                await r.json();


            box.className =
                'availability-box ' +
                (
                    d.available
                        ? 'available'
                        : 'unavailable'
                );


            box.innerHTML =
                '<span class="availability-dot"></span>' +
                '<strong>' +
                String(
                    d.message ||
                    'Availability checked.'
                ) +
                '</strong>';


            return !!d.available;


        } catch (e) {

            box.className =
                'availability-box unavailable';


            box.innerHTML =
                '<span class="availability-dot"></span>' +
                '<strong>' +
                'Could not check availability. ' +
                'We will verify the date when you submit.' +
                '</strong>';


            return false;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Date change
    |--------------------------------------------------------------------------
    */

    dateInput.addEventListener(
        'change',
        checkDate
    );


    /*
    |--------------------------------------------------------------------------
    | Start time change
    |--------------------------------------------------------------------------
    */

    startInput.addEventListener(
        'change',
        function () {

            if (
                endInput.value &&
                startInput.value &&
                endInput.value <= startInput.value
            ) {

                endInput.value = '';

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Submit validation
    |--------------------------------------------------------------------------
    */

    form.addEventListener(
        'submit',
        function (event) {

            const selected =
                getSelected();


            /*
            | At least one item
            */

            if (selected.length === 0) {

                event.preventDefault();


                alert(
                    'Please select at least one service or package.'
                );


                document
                    .getElementById('choiceGrid')
                    .scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });


                return;
            }


            /*
            | Date
            */

            if (!dateInput.value) {

                event.preventDefault();

                alert(
                    'Please select your event date.'
                );

                dateInput.focus();

                return;
            }


            /*
            | Start + End time
            */

            if (
                !startInput.value ||
                !endInput.value
            ) {

                event.preventDefault();

                alert(
                    'Please select the event start and end time.'
                );

                return;
            }


            /*
            | End must be later
            */

            if (
                endInput.value <=
                startInput.value
            ) {

                event.preventDefault();

                alert(
                    'End time must be later than start time.'
                );

                endInput.focus();

                return;
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Initial render
    |--------------------------------------------------------------------------
    */

    renderSelection();


    /*
    |--------------------------------------------------------------------------
    | Check existing date
    |--------------------------------------------------------------------------
    */

    if (dateInput.value) {
        checkDate();
    }

})();

</script>


<?php
include __DIR__ . '/includes/footer.php';
?>