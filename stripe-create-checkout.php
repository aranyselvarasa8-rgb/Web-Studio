<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/stripe.php';
require_login('payment.php');
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
        throw new RuntimeException('Invalid request or security token.');
    }
    $user = current_user();
    $type = trim($_POST['type'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    if (!in_array($type, ['booking','order'], true) || $id <= 0) throw new RuntimeException('Invalid payment reference.');

    if ($type === 'booking') {
        $st=mysqli_prepare($conn,"SELECT id,total_amount,status FROM bookings WHERE id=? AND user_id=? LIMIT 1");
        mysqli_stmt_bind_param($st,'ii',$id,$user['id']); mysqli_stmt_execute($st);
        $row=mysqli_fetch_assoc(mysqli_stmt_get_result($st)); mysqli_stmt_close($st);
        if (!$row) throw new RuntimeException('Booking not found.');
        if ($row['status'] !== 'confirmed') throw new RuntimeException('This booking is not ready for payment.');
        $st=mysqli_prepare($conn,"SELECT COALESCE(SUM(amount),0) paid FROM payments WHERE booking_id=? AND status='paid'");
        mysqli_stmt_bind_param($st,'i',$id); mysqli_stmt_execute($st);
        $paid=(float)(mysqli_fetch_assoc(mysqli_stmt_get_result($st))['paid']??0); mysqli_stmt_close($st);
        $amount=max(0,round(((float)$row['total_amount']*ADVANCE_PERCENT/100)-$paid,2));
        if ($amount<=0) throw new RuntimeException('This booking has already been paid.');
        $description='Web Studio booking #' . ref_no($id) . ' advance payment';
    } else {
        ensure_order_payment_method_column($conn);
        $st=mysqli_prepare($conn,"SELECT id,total_amount,payment_status FROM orders WHERE id=? AND user_id=? LIMIT 1");
        mysqli_stmt_bind_param($st,'ii',$id,$user['id']); mysqli_stmt_execute($st);
        $row=mysqli_fetch_assoc(mysqli_stmt_get_result($st)); mysqli_stmt_close($st);
        if (!$row) throw new RuntimeException('Order not found.');
        if ($row['payment_status']==='paid') throw new RuntimeException('This order has already been paid.');
        $amount=(float)$row['total_amount'];
        if ($amount<=0) throw new RuntimeException('Invalid order amount.');
        $description='Web Studio order #' . $id;
    }

    $session=stripe_create_checkout_session($type,$id,$amount,$description,$user['email']??'');
    if (empty($session['id']) || empty($session['url'])) throw new RuntimeException('Stripe Checkout session could not be created.');

    echo json_encode(['success'=>true,'url'=>$session['url'],'session_id'=>$session['id']]);
} catch(Throwable $e) {
    error_log('Stripe create checkout: '.$e->getMessage());
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
