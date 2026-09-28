<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login('payment.php');
$user=current_user();
$bookingId=(int)($_GET['booking_id']??$_GET['id']??0);
$error=trim($_GET['stripe_error']??'');
$booking=null;
if ($bookingId>0) {
    $st=mysqli_prepare($conn,"SELECT * FROM bookings WHERE id=? AND user_id=? LIMIT 1");
    mysqli_stmt_bind_param($st,'ii',$bookingId,$user['id']); mysqli_stmt_execute($st);
    $booking=mysqli_fetch_assoc(mysqli_stmt_get_result($st)); mysqli_stmt_close($st);
}
if (!$booking) $error=$error?:'Booking not found.';
$total=(float)($booking['total_amount']??0);
$advance=round($total*ADVANCE_PERCENT/100,2);
$paid=0;
if ($booking) {
    $st=mysqli_prepare($conn,"SELECT COALESCE(SUM(amount),0) paid FROM payments WHERE booking_id=? AND status='paid'");
    mysqli_stmt_bind_param($st,'i',$bookingId); mysqli_stmt_execute($st);
    $paid=(float)(mysqli_fetch_assoc(mysqli_stmt_get_result($st))['paid']??0); mysqli_stmt_close($st);
}
$remaining=max(0,$advance-$paid);
$page_title='Payment | '.SITE_NAME;
include __DIR__.'/includes/header.php';
?>
<section class="page-banner"><div class="container"><span class="eyebrow">SECURE PAYMENT</span><h1>Pay booking advance</h1><p>Complete your 30% booking advance securely with Stripe.</p></div></section>
<section class="section"><div class="container" style="max-width:720px">
<?php if($error): ?><div class="alert error-alert"><?=e($error)?></div><?php endif; ?>
<div class="pay-summary-card">
<span class="eyebrow">BOOKING #<?=e(ref_no($bookingId))?></span>
<h2><?=e($booking['event_type']??'Photography Session')?></h2>
<div class="pay-summary-advance"><span>Total booking</span><strong><?=money($total)?></strong></div>
<div class="pay-summary-advance"><span>30% advance</span><strong><?=money($remaining)?></strong></div>
<?php if($remaining>0): ?>
<form method="post" action="stripe-create-checkout.php" id="stripe-booking-form">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<input type="hidden" name="type" value="booking"><input type="hidden" name="id" value="<?=$bookingId?>">
<button class="btn gold" type="submit" id="stripe-pay-btn">Pay securely with Stripe</button>
</form>
<p class="hint">You will be redirected to Stripe Checkout. Card details are handled by Stripe, not stored on this website.</p>
<?php else: ?><p class="booking-status confirmed">Payment completed</p><?php endif; ?>
</div></div></section>
<script>
document.getElementById('stripe-booking-form')?.addEventListener('submit',async function(e){
 e.preventDefault(); const b=document.getElementById('stripe-pay-btn'); b.disabled=true; b.textContent='Opening Stripe...';
 try{const r=await fetch(this.action,{method:'POST',body:new FormData(this)});const d=await r.json();if(!d.success)throw new Error(d.error||'Stripe checkout failed.');window.location.href=d.url;}
 catch(err){alert(err.message);b.disabled=false;b.textContent='Pay securely with Stripe';}
});
</script>
<?php include __DIR__.'/includes/footer.php'; ?>
