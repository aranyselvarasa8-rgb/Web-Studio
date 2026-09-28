<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login('checkout.php');

$user   = current_user();
$userId = (int) $user['id'];

$page_title = 'Checkout | ' . SITE_NAME;
$error = '';

/* Errors that are safe to show the customer (mysqli_sql_exception also
   extends RuntimeException, so a dedicated class keeps DB errors private). */
class CheckoutException extends Exception
{
}

/* Cart contents with live prices/stock from the database */
$lines = cart_lines($userId);

if (!$lines) {
    flash_set('error', 'Your cart is empty.');
    header('Location: cart.php');
    exit;
}

foreach ($lines as $line) {
    if (!$line['enough']) {
        flash_set('error', 'Please fix the highlighted items before checking out.');
        header('Location: cart.php');
        exit;
    }
}

$total = 0.0;
foreach ($lines as $line) {
    $total += $line['subtotal'];
}

$name    = trim($_POST['name']    ?? $user['name']  ?? '');
$phone   = trim($_POST['phone']   ?? $user['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$method  = trim($_POST['payment_method'] ?? 'Stripe');
$payBank = trim($_POST['pay_bank'] ?? '');   /* bank the customer will pay from: index in BANK_ACCOUNTS or "other" */
if ($payBank !== 'other' && !(ctype_digit($payBank) && isset(BANK_ACCOUNTS[(int) $payBank]))) {
    $payBank = '';
}

ensure_order_payment_method_column($conn);


/* Place order */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please refresh and try again.';
    }

    if (!$error && !in_array($method, ORDER_PAYMENT_METHODS, true)) {
        $error = 'Please choose a payment method.';
    }

    if (!$error && $method === 'Bank Transfer' && $payBank === '') {
        $error = 'Please select the bank you will pay from.';
    }

    if (!$error && $name === '') {
        $error = 'Please enter your name.';
    }

    if (!$error && !preg_match('/^[0-9+\-\s()]{9,20}$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    }

    if (!$error && mb_strlen($address) < 10) {
        $error = 'Please enter your full delivery address.';
    }

    if (!$error && mb_strlen($address) > 500) {
        $error = 'Delivery address is too long.';
    }

    if (!$error) {

        mysqli_begin_transaction($conn);

        try {

            /* Lock each product row, re-check stock and re-read the price.
               Nothing the browser sends is used for prices. */

            $orderLines = [];
            $orderTotal = 0.0;

            foreach ($lines as $line) {

                $st = mysqli_prepare(
                    $conn,
                    "SELECT id, name, price, stock, status
                     FROM products
                     WHERE id = ?
                     FOR UPDATE"
                );
                mysqli_stmt_bind_param($st, 'i', $line['product_id']);
                mysqli_stmt_execute($st);
                $p = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
                mysqli_stmt_close($st);

                if (
                    !$p ||
                    $p['status'] === 'inactive' ||
                    (int) $p['stock'] < $line['quantity']
                ) {
                    throw new CheckoutException(
                        ($p['name'] ?? 'An item') .
                        ' is no longer available in the quantity you selected. Please review your cart.'
                    );
                }

                $price = (float) $p['price'];

                $orderLines[] = [
                    'product_id' => (int) $p['id'],
                    'name'       => $p['name'],
                    'price'      => $price,
                    'quantity'   => $line['quantity'],
                ];

                $orderTotal += $price * $line['quantity'];
            }

            /* Order header. The orders table has no separate name column,
               so the customer name is stored with the address. */

            $shipping = $name . "\n" . $address;

            $st = mysqli_prepare(
                $conn,
                "INSERT INTO orders
                 (user_id, total_amount, payment_status, payment_method, shipping_address, phone, status)
                 VALUES (?, ?, 'pending', ?, ?, ?, 'pending')"
            );
            mysqli_stmt_bind_param($st, 'idsss', $userId, $orderTotal, $method, $shipping, $phone);
            mysqli_stmt_execute($st);
            $orderId = (int) mysqli_insert_id($conn);
            mysqli_stmt_close($st);

            /* Order items + stock reduction */

            foreach ($orderLines as $ol) {

                $st = mysqli_prepare(
                    $conn,
                    "INSERT INTO order_items
                     (order_id, product_id, product_name, price, quantity)
                     VALUES (?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $st,
                    'iisdi',
                    $orderId,
                    $ol['product_id'],
                    $ol['name'],
                    $ol['price'],
                    $ol['quantity']
                );
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);

                $st = mysqli_prepare(
                    $conn,
                    "UPDATE products
                     SET stock = stock - ?
                     WHERE id = ? AND stock >= ?"
                );
                mysqli_stmt_bind_param(
                    $st,
                    'iii',
                    $ol['quantity'],
                    $ol['product_id'],
                    $ol['quantity']
                );
                mysqli_stmt_execute($st);
                $reduced = mysqli_stmt_affected_rows($st);
                mysqli_stmt_close($st);

                if ($reduced !== 1) {
                    throw new CheckoutException(
                        $ol['name'] . ' just ran out of stock. Please review your cart.'
                    );
                }
            }

            /* Empty the cart */

            $cartId = cart_id_for_user($userId);

            $st = mysqli_prepare($conn, 'DELETE FROM cart_items WHERE cart_id = ?');
            mysqli_stmt_bind_param($st, 'i', $cartId);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            mysqli_commit($conn);

            /* Auto WhatsApp to the customer's own number (never blocks the order) */
            require_once __DIR__ . '/includes/whatsapp-api.php';
            wa_notify_order_placed($conn, $orderId);

            if ($method === 'Stripe') {
                header('Location: stripe-checkout.php?type=order&id=' . $orderId);
                exit;
            }

            header('Location: order-success.php?id=' . $orderId . ($method === 'Bank Transfer' && $payBank !== '' ? '&bank=' . urlencode($payBank) : ''));
            exit;

        } catch (CheckoutException $ex) {

            mysqli_rollback($conn);
            $error = $ex->getMessage();

        } catch (Throwable $ex) {

            mysqli_rollback($conn);
            error_log('Checkout failed: ' . $ex->getMessage());
            $error = 'Your order could not be placed. Please try again.';
        }
    }
}

include __DIR__ . '/includes/header.php';

?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow">CHECKOUT</span>
        <h1>Place your order</h1>
        <p>Confirm your delivery details and choose how you'd like to pay.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:1080px">

        <?php if ($error): ?>
            <div class="alert error-alert" style="margin-bottom:22px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div><?= e($error) ?> <a href="cart.php">Back to cart</a></div>
            </div>
        <?php endif; ?>

        <div class="pay-wrap">

            <!-- Order summary -->
            <div class="pay-summary-card">
                <span class="eyebrow">YOUR ORDER</span>
                <h2>Order summary</h2>

                <?php foreach ($lines as $line): ?>
                    <div class="co-line">
                        <span><?= e($line['name']) ?> <em>× <?= $line['quantity'] ?></em></span>
                        <strong><?= money($line['subtotal']) ?></strong>
                    </div>
                <?php endforeach; ?>

                <div class="pay-summary-advance">
                    <span>Total to pay</span>
                    <strong><?= money($total) ?></strong>
                </div>

                <div class="pay-summary-badge" id="co-method-badge"><?= e($method) ?></div>
            </div>

            <!-- Delivery form -->
            <div class="pay-form-card">

                <h3>Delivery details</h3>
                <p class="hint">We'll contact you on this number to confirm your order and delivery.</p>

                <form method="post" id="checkout-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="field-group">
                        <label for="co-name">Full name</label>
                        <input type="text" id="co-name" name="name" maxlength="120" required value="<?= e($name) ?>">
                    </div>

                    <div class="field-group">
                        <label for="co-phone">Phone number</label>
                        <input type="tel" id="co-phone" name="phone" maxlength="20" required placeholder="0763551264" value="<?= e($phone) ?>">
                    </div>

                    <div class="field-group">
                        <label for="co-address">Delivery address</label>
                        <textarea id="co-address" name="address" rows="4" maxlength="500" required placeholder="House no, street, town, district"><?= e($address) ?></textarea>
                    </div>


                    <div class="field-group">
                        <label for="co-method">Payment method <span class="req">*</span></label>
                        <select id="co-method" name="payment_method" required>
                            <?php foreach (ORDER_PAYMENT_METHODS as $m): ?>
                                <option value="<?= e($m) ?>" <?= $m === $method ? 'selected' : '' ?>><?= e($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Stripe -->
                    <div class="method-panel" data-method="Stripe">
                        <p>You'll be taken to Stripe's secure Checkout to complete your order payment. Your order will be marked paid after Stripe confirms the payment.</p>
                        <p class="method-note">
                            <?php if (strtolower(STRIPE_CURRENCY) === 'usd'): ?>
                                Prices on this site are in LKR. Stripe will charge you the equivalent in USD at checkout (approx. rate: 1 USD = <?= number_format(USD_EXCHANGE_RATE, 2) ?> LKR).
                            <?php else: ?>
                                The website charges in LKR. Card details are handled securely by Stripe.
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Bank transfer -->
                    <div class="method-panel" data-method="Bank Transfer">
                        <div class="field-group">
                            <label for="co-bank">Which bank will you pay from?</label>
                            <select id="co-bank" name="pay_bank">
                                <option value="">Select your bank</option>
                                <?php foreach (BANK_ACCOUNTS as $i => $b): ?>
                                    <option value="<?= (int) $i ?>" <?= $payBank === (string) $i ? 'selected' : '' ?>><?= e($b['bank']) ?></option>
                                <?php endforeach; ?>
                                <option value="other" <?= $payBank === 'other' ? 'selected' : '' ?>>Other bank (CEFT transfer)</option>
                            </select>
                        </div>

                        <?php foreach (BANK_ACCOUNTS as $i => $b): ?>
                            <p class="bank-detail" data-bank="<?= (int) $i ?>" hidden style="margin:0 0 10px">
                                <strong><?= e($b['bank']) ?></strong><br>
                                Account name: <?= e($b['name']) ?><br>
                                Account number: <?= e($b['number']) ?><?= !empty($b['branch']) ? '<br>Branch: ' . e($b['branch']) : '' ?>
                            </p>
                        <?php endforeach; ?>
                        <p class="bank-detail" data-bank="other" hidden style="margin:0 0 10px">
                            Transfer from your bank using online banking / CEFT to any of our accounts. Details are on the next page.
                        </p>

                        <p style="font-size:13px;opacity:.8;margin:0">Full details and a WhatsApp button to send your payment slip appear on the next page.</p>
                    </div>

                    <!-- Cash on Delivery -->
                    <div class="method-panel" data-method="Cash on Delivery">
                        <p>Pay in cash when your order is delivered, or in person at the studio. No advance payment needed online.</p>
                        <p class="method-note">The studio will contact you on WhatsApp to confirm your order and delivery.</p>
                    </div>

                    <!-- Card (details are NOT submitted: fields have no name attribute) -->
                    <div class="method-panel" data-method="Credit / Debit Card" hidden>
                        <div class="field-group">
                            <label for="card-name">Name on card</label>
                            <input type="text" id="card-name" autocomplete="cc-name" placeholder="Name on card" maxlength="80">
                        </div>
                        <div class="field-group">
                            <label for="card-number">Card number</label>
                            <input type="text" id="card-number" inputmode="numeric" autocomplete="cc-number" placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                        <div class="card-row">
                            <div class="field-group">
                                <label for="card-exp">Expiry</label>
                                <input type="text" id="card-exp" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="field-group">
                                <label for="card-cvv">CVV</label>
                                <input type="text" id="card-cvv" inputmode="numeric" autocomplete="off" placeholder="123" maxlength="4">
                            </div>
                        </div>
                        <p class="method-note">🔒 For your security card details are never stored on our server. The studio will confirm your card payment with you on WhatsApp.</p>
                    </div>

                    <!-- Online payment -->
                    <div class="method-panel" data-method="Online Payment" hidden>
                        <p>Pay with LankaQR or your mobile wallet. The studio will send you a secure payment link on WhatsApp after you place the order.</p>
                    </div>

                    <button type="submit" class="btn gold pay-submit-btn">
                        Place Order — <?= money($total) ?>
                    </button>

                    <p class="checkout-note">
                        Placing the order reserves your items. Payment instructions appear on the next page.
                    </p>
                </form>

            </div>
        </div>

    </div>
</section>


<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('co-method');
    var badge  = document.getElementById('co-method-badge');
    var panels = document.querySelectorAll('.method-panel');

    function showMethod() {
        panels.forEach(function (p) {
            p.hidden = p.getAttribute('data-method') !== select.value;
        });
        if (badge) { badge.textContent = select.value; }
    }
    select.addEventListener('change', showMethod);
    showMethod();

    /* Show the account of the bank the customer picked */
    var bankSel = document.getElementById('co-bank');
    function showBank() {
        document.querySelectorAll('.bank-detail').forEach(function (p) {
            p.hidden = p.getAttribute('data-bank') !== bankSel.value;
        });
    }
    if (bankSel) {
        bankSel.addEventListener('change', showBank);
        showBank();
    }

    /* Card field formatting (display only) */
    var num = document.getElementById('card-number');
    var exp = document.getElementById('card-exp');
    var cvv = document.getElementById('card-cvv');
    num.addEventListener('input', function () {
        var d = num.value.replace(/\D/g, '').slice(0, 16);
        num.value = d.replace(/(.{4})/g, '$1 ').trim();
    });
    exp.addEventListener('input', function () {
        var d = exp.value.replace(/\D/g, '').slice(0, 4);
        exp.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
    });
    cvv.addEventListener('input', function () {
        cvv.value = cvv.value.replace(/\D/g, '').slice(0, 4);
    });

    /* Card selected: check the visible card fields (they are never sent to the server) */
    function fieldError(input, msg) {
        var id = input.id + '-err';
        var el = document.getElementById(id);
        if (!el) {
            el = document.createElement('div');
            el.id = id;
            el.style.cssText = 'color:#c0392b;font-size:13px;margin-top:6px';
            input.parentNode.appendChild(el);
        }
        el.textContent = msg;
        input.style.borderColor = msg ? '#c0392b' : '';
    }

    document.getElementById('checkout-form').addEventListener('submit', function (e) {
        if (select.value !== 'Credit / Debit Card') { return; }
        var name = document.getElementById('card-name');
        var digits = num.value.replace(/\s/g, '').length;
        var m = exp.value.match(/^(0[1-9]|1[0-2])\/(\d{2})$/);
        var expOk = false;
        if (m) {
            var now = new Date();
            var y = 2000 + parseInt(m[2], 10), mo = parseInt(m[1], 10);
            expOk = y > now.getFullYear() || (y === now.getFullYear() && mo >= now.getMonth() + 1);
        }
        var checks = [
            [name, name.value.trim() !== '', 'Enter the name on your card.'],
            [num, digits >= 13, 'Card number needs 13-16 digits (you entered ' + digits + ').'],
            [exp, expOk, 'Enter a valid future expiry as MM/YY, e.g. 05/29.'],
            [cvv, cvv.value.length >= 3, 'CVV needs 3 or 4 digits.']
        ];
        var firstBad = null;
        checks.forEach(function (c) {
            fieldError(c[0], c[1] ? '' : c[2]);
            if (!c[1] && !firstBad) { firstBad = c[0]; }
        });
        if (firstBad) {
            e.preventDefault();
            firstBad.focus();
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>