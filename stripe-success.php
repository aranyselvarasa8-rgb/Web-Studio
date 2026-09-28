<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/stripe.php';
require_login('my-bookings.php');

$user=current_user();
$sessionId=trim($_GET['session_id']??'');
$type=trim($_GET['type']??'');
$id=(int)($_GET['id']??0);

try {
    if (!in_array($type,['booking','order'],true) || $id<=0) throw new RuntimeException('Invalid payment reference.');
    $session=stripe_get_checkout_session($sessionId);
    if (($session['payment_status']??'') !== 'paid') throw new RuntimeException('Stripe payment has not been completed.');
    $meta=$session['metadata']??[];
    if (($meta['type']??'')!==$type || (int)($meta['id']??0)!==$id) throw new RuntimeException('Stripe payment does not match this record.');
    $currency=strtolower($session['currency']??'');
    if ($currency!==strtolower(STRIPE_CURRENCY)) throw new RuntimeException('Payment currency mismatch.');
    /* amount_total is in whatever STRIPE_CURRENCY is (e.g. USD cents), while every
       amount stored in the database is in LKR. Compare in Stripe's own smallest
       unit so a currency conversion never causes a false "amount mismatch". */
    $paidCents=(int) round((float)($session['amount_total']??0));

    if ($type==='booking') {
        $st=mysqli_prepare($conn,"SELECT id,total_amount,status FROM bookings WHERE id=? AND user_id=? LIMIT 1");
        mysqli_stmt_bind_param($st,'ii',$id,$user['id']); mysqli_stmt_execute($st); $b=mysqli_fetch_assoc(mysqli_stmt_get_result($st)); mysqli_stmt_close($st);
        if (!$b || $b['status']!=='confirmed') throw new RuntimeException('Booking is not available.');
        $expected=round((float)$b['total_amount']*ADVANCE_PERCENT/100,2);
        $expectedCents=stripe_amount_in_currency($expected, STRIPE_CURRENCY);
        if (abs($paidCents-$expectedCents)>1) throw new RuntimeException('Payment amount mismatch.');
        $paid=$expected;
        $st=mysqli_prepare($conn,"SELECT id,status FROM payments WHERE booking_id=? ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($st,'i',$id); mysqli_stmt_execute($st); $existing=mysqli_fetch_assoc(mysqli_stmt_get_result($st)); mysqli_stmt_close($st);
        $tx=$session['payment_intent']??$sessionId;
        if ($existing) {
            $st=mysqli_prepare($conn,"UPDATE payments SET user_id=?,amount=?,method='Stripe',transaction_id=?,status='paid',paid_at=NOW(),proof_image=NULL WHERE id=?");
            mysqli_stmt_bind_param($st,'idsi',$user['id'],$paid,$tx,$existing['id']);
        } else {
            $st=mysqli_prepare($conn,"INSERT INTO payments (booking_id,user_id,amount,method,transaction_id,status,paid_at) VALUES (?,?,?,'Stripe',?,'paid',NOW())");
            mysqli_stmt_bind_param($st,'iids',$id,$user['id'],$paid,$tx);
        }
        mysqli_stmt_execute($st); mysqli_stmt_close($st);
        header('Location: payment-success.php?id='.$id); exit;
    }

    ensure_order_payment_method_column($conn);
    $st=mysqli_prepare($conn,"SELECT id,total_amount,payment_status FROM orders WHERE id=? AND user_id=? LIMIT 1");
    mysqli_stmt_bind_param($st,'ii',$id,$user['id']); mysqli_stmt_execute($st); $o=mysqli_fetch_assoc(mysqli_stmt_get_result($st)); mysqli_stmt_close($st);
    if (!$o) throw new RuntimeException('Order not found.');
    $expectedCents=stripe_amount_in_currency((float)$o['total_amount'], STRIPE_CURRENCY);
    if (abs($paidCents-$expectedCents)>1) throw new RuntimeException('Payment amount mismatch.');
    $paid=(float)$o['total_amount'];
    if ($o['payment_status']!=='paid') {
        $st=mysqli_prepare($conn,"UPDATE orders SET payment_status='paid',status='confirmed',payment_method='Stripe' WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($st,'ii',$id,$user['id']); mysqli_stmt_execute($st); mysqli_stmt_close($st);
        $tx=$session['payment_intent']??$sessionId;
        $st=mysqli_prepare($conn,"INSERT INTO payments (order_id,user_id,amount,method,transaction_id,status,paid_at) VALUES (?,?,?,'Stripe',?,'paid',NOW())");
        mysqli_stmt_bind_param($st,'iids',$id,$user['id'],$paid,$tx); mysqli_stmt_execute($st); mysqli_stmt_close($st);
    }
    header('Location: order-success.php?id='.$id); exit;
} catch(Throwable $e) {
    error_log('Stripe success: '.$e->getMessage());
    $target=($type==='booking'?'payment.php?booking_id=':'order-success.php?id=').$id;
    header('Location: '.$target.'&stripe_error='.urlencode($e->getMessage())); exit;
}
