<?php

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');


$date = $_GET['date'] ?? '';


/* Validate date */
if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
    strtotime($date) === false
) {
    echo json_encode([
        'available' => false,
        'message' => 'Please select a valid date.'
    ]);

    exit;
}


/* Check future date */
if (strtotime($date) < strtotime(date('Y-m-d'))) {

    echo json_encode([
        'available' => false,
        'message' => 'Please choose a future date.'
    ]);

    exit;
}


/* Check booking */
$st = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) 
     FROM bookings 
     WHERE event_date = ? 
     AND status IN ('pending', 'confirmed')"
);

mysqli_stmt_bind_param(
    $st,
    's',
    $date
);

mysqli_stmt_execute($st);

$count = (int) (
    mysqli_fetch_row(
        mysqli_stmt_get_result($st)
    )[0] ?? 0
);

mysqli_stmt_close($st);


/* Return result */
echo json_encode([
    'available' => $count === 0,
    'message' => $count === 0
        ? 'This date is available! You can continue your booking.'
        : 'This date is already reserved. Please choose another date.'
]);

?>