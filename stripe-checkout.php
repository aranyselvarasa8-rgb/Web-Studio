<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/stripe.php';
require_login('checkout.php');
$user=current_user(); $id=(int)($_GET['id']??0);
try {
 $st=mysqli_prepare($conn,"SELECT id,total_amount,payment_status FROM orders WHERE id=? AND user_id=? LIMIT 1");
 mysqli_stmt_bind_param($st,'ii',$id,$user['id']); mysqli_stmt_execute($st); $o=mysqli_fetch_assoc(mysqli_stmt_get_result($st)); mysqli_stmt_close($st);
 if(!$o) throw new RuntimeException('Order not found.');
 if($o['payment_status']==='paid'){header('Location: order-success.php?id='.$id);exit;}
 $session=stripe_create_checkout_session('order',$id,(float)$o['total_amount'],'Web Studio order #'.$id,$user['email']??'');
 if(empty($session['url'])) throw new RuntimeException('Stripe Checkout could not be created.');
 header('Location: '.$session['url']); exit;
} catch(Throwable $e) {
 error_log('Stripe order checkout: '.$e->getMessage());
 header('Location: order-success.php?id='.$id.'&stripe_error='.urlencode($e->getMessage())); exit;
}
