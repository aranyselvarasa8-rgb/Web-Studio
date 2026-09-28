<?php

require_once __DIR__ . '/config.php';


/* =========================================================
   WhatsApp helpers
   ========================================================= */

/* Turn a local Sri Lankan number (076 355 1264) into wa.me format (94763551264) */
function wa_number_clean(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
    }

    if (strpos($digits, '0') === 0) {
        return '94' . substr($digits, 1);
    }

    if (strlen($digits) === 9) {
        return '94' . $digits;
    }

    return $digits;
}


/* Click-to-chat link with a pre-filled message.
   No $number = message goes to the studio (WHATSAPP_NUMBER).
   With $number = message goes to that customer. */
function whatsapp_link(string $message, ?string $number = null): string
{
    $target = $number !== null
        ? wa_number_clean($number)
        : WHATSAPP_NUMBER;

    return 'https://wa.me/' . $target . '?text=' . rawurlencode($message);
}


/* Bank picker: customer chooses the bank, we show OUR account in that bank.
   Banks come from BANK_ACCOUNTS in config.php. "Other bank" -> CEFT to the first account. */
function bank_boxes_html(float $amount, string $reference): string
{
    $icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c99a2e" stroke-width="2"><rect x="3" y="10" width="18" height="10" rx="1"/><path d="M12 3 2 9h20L12 3Z"/><line x1="6" y1="14" x2="6" y2="16"/><line x1="12" y1="14" x2="12" y2="16"/><line x1="18" y1="14" x2="18" y2="16"/></svg>';
    $row = function (string $label, string $value): string {
        return '<div class="bank-row"><span class="bank-row-label">' . e($label)
            . '</span><span class="bank-row-value">' . e($value) . '</span></div>';
    };

    $preBank = (string) ($_GET['bank'] ?? '');
    $html = '<div style="margin:0 0 14px"><label for="pay-from-bank" style="display:block;font-weight:600;margin-bottom:6px">Which bank will you pay from?</label>'
        . '<select id="pay-from-bank" style="width:100%;padding:11px 12px;border:1px solid #d9d9d9;border-radius:8px;font:inherit">'
        . '<option value="">Select your bank</option>';
    foreach (BANK_ACCOUNTS as $i => $b) {
        $html .= '<option value="' . (int) $i . '"' . ($preBank === (string) $i ? ' selected' : '') . '>' . e($b['bank']) . '</option>';
    }
    $html .= '<option value="other"' . ($preBank === 'other' ? ' selected' : '') . '>Other bank (CEFT transfer)</option></select>'
        . '<p id="pay-from-note" class="hint" style="margin:8px 0 0;display:none"></p></div>';

    foreach (BANK_ACCOUNTS as $i => $b) {
        $html .= '<div class="bank-box studio-bank" data-idx="' . (int) $i . '" style="margin-bottom:14px;display:none">'
            . '<div class="bank-box-head">' . $icon . '<strong>' . e($b['bank']) . '</strong></div>'
            . $row('Account name', $b['name'])
            . $row('Account number', $b['number']);
        if (!empty($b['branch'])) {
            $html .= $row('Branch', $b['branch']);
        }
        $html .= '</div>';
    }

    $html .= '<div class="bank-box" id="pay-amount-box" style="display:none">'
        . $row('Amount to transfer', money($amount))
        . $row('Reference', $reference)
        . '</div>';

    $names = json_encode(array_column(BANK_ACCOUNTS, 'bank'));
    $html .= '<script>document.addEventListener("DOMContentLoaded",function(){'
        . 'var names=' . $names . ',sel=document.getElementById("pay-from-bank"),note=document.getElementById("pay-from-note"),'
        . 'amt=document.getElementById("pay-amount-box"),boxes=document.querySelectorAll(".studio-bank"),'
        . 'wa=document.querySelector("a[data-wa-link]"),base=wa?wa.getAttribute("href"):"";'
        . 'if(!sel)return;'
        . 'sel.addEventListener("change",function(){'
        . 'var v=sel.value,label=sel.options[sel.selectedIndex].text,show=(v==="other")?"0":v;'
        . 'boxes.forEach(function(bx){bx.style.display=(v!==""&&bx.getAttribute("data-idx")===show)?"block":"none";});'
        . 'amt.style.display=v===""?"none":"block";'
        . 'if(v===""){note.style.display="none";}'
        . 'else if(v==="other"){note.textContent="Transfer from your bank to the account below using online banking / CEFT.";note.style.display="block";}'
        . 'else{note.textContent="Transfer to our "+label+" account below (same-bank transfer, or deposit cash at any "+label+" branch / CDM).";note.style.display="block";}'
        . 'if(wa&&base){var t=base.split("?text=");var msg=decodeURIComponent(t[1]||"");'
        . 'if(v!==""){msg+="\nPaying from: "+label;}'
        . 'wa.setAttribute("href",t[0]+"?text="+encodeURIComponent(msg));}'
        . '});if(sel.value!==""){sel.dispatchEvent(new Event("change"));}});</script>';

    return $html;
}


function whatsapp_icon_svg(int $size = 18): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 32 32" aria-hidden="true">'
        . '<path fill="currentColor" d="M19.11 17.19c-.27-.14-1.58-.78-1.83-.87-.25-.09-.43-.14-.61.14-.18.27-.7.87-.86 1.05-.16.18-.32.2-.59.07-.27-.14-1.12-.41-2.14-1.32-.79-.7-1.32-1.56-1.47-1.83-.16-.27-.02-.42.12-.56.12-.12.27-.32.41-.48.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.48-.07-.14-.61-1.47-.84-2.02-.22-.53-.45-.46-.61-.47h-.52c-.18 0-.48.07-.73.34-.25.27-.95.93-.95 2.27s.98 2.63 1.11 2.81c.14.18 1.92 2.93 4.66 4.11.65.28 1.16.45 1.55.57.65.21 1.24.18 1.7.11.52-.08 1.58-.65 1.8-1.28.23-.63.23-1.17.16-1.28-.07-.11-.25-.18-.52-.32z"/>'
        . '<path fill="currentColor" d="M16.01 3.2c-7.06 0-12.8 5.73-12.8 12.78 0 2.25.59 4.45 1.71 6.38L3.1 28.8l6.59-1.73a12.77 12.77 0 0 0 6.32 1.67h.01c7.05 0 12.78-5.74 12.78-12.79S23.06 3.2 16.01 3.2zm0 23.37h-.01a10.6 10.6 0 0 1-5.4-1.48l-.39-.23-3.91 1.03 1.04-3.81-.25-.39a10.58 10.58 0 1 1 8.92 4.88z"/>'
        . '</svg>';
}


/* =========================================================
   Small utilities
   ========================================================= */

function ref_no(int $id): string
{
    return str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}


function money(float $amount): string
{
    return 'Rs. ' . number_format($amount, 2);
}


/* One-shot messages that survive a redirect */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}


function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}


/* =========================================================
   Products / cart
   ========================================================= */

/* Same image rules the Products page has always used */
function product_image_url(array $p): string
{
    $byName = [
        'Premium Photo Album'      => 'assets/images/products/premium-photo-album.jpg',
        'Marble Frame'             => 'assets/images/products/marble-frame.jpg',
        '3D Crystal Frame'         => 'assets/images/products/3d-crystal-frame.jpg',
        '3D Crystal Cutting Frame' => 'assets/images/products/3d-crystal-cutting-frame.jpg',
        'Custom Photo Calendar'    => 'assets/images/products/custom-photo-calendar.jpg',
        'Canvas Photo Print'       => 'assets/images/products/canvas-photo-print.jpg',
    ];

    if (isset($byName[$p['name']])) {
        return $byName[$p['name']];
    }

    $image = trim((string) ($p['image'] ?? ''));

    if ($image === '') {
        return 'assets/images/products/premium-photo-album.jpg';
    }

    return strpos($image, 'assets/') === 0
        ? $image
        : 'assets/images/' . $image;
}


/* Total item quantity in the logged-in customer's cart (0 for guests) */
function cart_count(): int
{
    global $conn;

    if (empty($_SESSION['user_id'])) {
        return 0;
    }

    $userId = (int) $_SESSION['user_id'];

    $st = mysqli_prepare(
        $conn,
        'SELECT COALESCE(SUM(ci.quantity), 0)
         FROM cart c
         JOIN cart_items ci ON ci.cart_id = c.id
         WHERE c.user_id = ?'
    );

    mysqli_stmt_bind_param($st, 'i', $userId);
    mysqli_stmt_execute($st);

    $count = (int) (mysqli_fetch_row(mysqli_stmt_get_result($st))[0] ?? 0);

    mysqli_stmt_close($st);

    return $count;
}


/* Find (or create) the customer's cart row */
function cart_id_for_user(int $userId): int
{
    global $conn;

    $st = mysqli_prepare($conn, 'SELECT id FROM cart WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($st, 'i', $userId);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    if ($row) {
        return (int) $row['id'];
    }

    $st = mysqli_prepare($conn, 'INSERT INTO cart (user_id) VALUES (?)');
    mysqli_stmt_bind_param($st, 'i', $userId);
    mysqli_stmt_execute($st);
    $id = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($st);

    return $id;
}


/* Cart lines with LIVE product data. Prices always come from the products
   table, never from the browser. */
function cart_lines(int $userId): array
{
    global $conn;

    $st = mysqli_prepare(
        $conn,
        'SELECT ci.id AS item_id, ci.quantity,
                p.id AS product_id, p.name, p.price, p.stock, p.status, p.image
         FROM cart c
         JOIN cart_items ci ON ci.cart_id = c.id
         JOIN products p ON p.id = ci.product_id
         WHERE c.user_id = ?
         ORDER BY ci.id'
    );

    mysqli_stmt_bind_param($st, 'i', $userId);
    mysqli_stmt_execute($st);

    $lines = [];
    $result = mysqli_stmt_get_result($st);

    while ($row = mysqli_fetch_assoc($result)) {
        $row['price']    = (float) $row['price'];
        $row['stock']    = (int) $row['stock'];
        $row['quantity'] = (int) $row['quantity'];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $row['available'] = $row['status'] !== 'inactive' && $row['stock'] > 0;
        $row['enough']    = $row['available'] && $row['quantity'] <= $row['stock'];
        $lines[] = $row;
    }

    mysqli_stmt_close($st);

    return $lines;
}


/* Only allow redirects back to these pages */
function safe_back(?string $page): string
{
    return in_array($page, ['products.php', 'cart.php'], true)
        ? $page
        : 'products.php';
}


/* Payment methods offered at checkout */
const ORDER_PAYMENT_METHODS = ['Stripe', 'Bank Transfer', 'Cash on Delivery'];

/* Makes sure orders.payment_method exists (safe to call on every request) */
function ensure_order_payment_method_column(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $res = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'payment_method'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query(
            $conn,
            "ALTER TABLE orders
             ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'Bank Transfer'
             AFTER payment_status"
        );
    }
}