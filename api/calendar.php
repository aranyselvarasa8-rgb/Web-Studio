<?php

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$month = $_GET['month'] ?? date('Y-m');


/* Check month format */
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {

    http_response_code(400);

    echo json_encode([
        'error' => 'Invalid month'
    ]);

    exit;
}


/* Get month start and end */
$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));


/* Get bookings */
$st = mysqli_prepare(
    $conn,
    "SELECT event_date, status
     FROM bookings
     WHERE event_date BETWEEN ? AND ?
     AND status IN ('pending', 'confirmed')
     ORDER BY event_date"
);

mysqli_stmt_bind_param(
    $st,
    'ss',
    $start,
    $end
);

mysqli_stmt_execute($st);

$r = mysqli_stmt_get_result($st);

$data = [];


/* Store booking dates */
while ($row = mysqli_fetch_assoc($r)) {

    $data[$row['event_date']] = $row['status'];
}


mysqli_stmt_close($st);


/* Return JSON */
echo json_encode([
    'month' => $month,
    'booked' => $data,
    'ok' => true
]);

?>