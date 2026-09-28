<?php
/* =========================================================
   WhatsApp Cloud API (Meta) - automatic messages to CUSTOMERS
   The message goes to the phone number the customer typed at
   checkout. Nothing here can break checkout: every failure is
   caught and written to logs/whatsapp.log.
   ========================================================= */

require_once __DIR__ . '/functions.php';


function wa_api_log(string $line): void
{
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($dir . '/whatsapp.log', '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n", FILE_APPEND);
}


/* Template params may not contain new lines / tabs / long space runs */
function wa_param(string $text): string
{
    $text = preg_replace('/\s*[\r\n\t]+\s*/', ' | ', trim($text));
    $text = preg_replace('/ {2,}/', ' ', $text);
    return mb_substr($text, 0, 500);
}


/* Send one approved template message. Returns true on success. */
function wa_send_template(string $phone, string $template, array $params): bool
{
    if (!WA_API_ENABLED) {
        return false;
    }

    if (WA_ACCESS_TOKEN === '' || WA_PHONE_NUMBER_ID === '') {
        wa_api_log('SKIPPED ' . $template . ': token / phone number id not set in config.php');
        return false;
    }

    $to = wa_number_clean($phone);
    if (!preg_match('/^94\d{9}$/', $to)) {
        wa_api_log('SKIPPED ' . $template . ': bad phone number "' . $phone . '"');
        return false;
    }

    $parameters = [];
    foreach ($params as $p) {
        $parameters[] = ['type' => 'text', 'text' => wa_param((string) $p)];
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $to,
        'type'              => 'template',
        'template'          => [
            'name'       => $template,
            'language'   => ['code' => WA_TEMPLATE_LANG],
            'components' => [['type' => 'body', 'parameters' => $parameters]],
        ],
    ];

    $url = 'https://graph.facebook.com/' . WA_API_VERSION . '/' . WA_PHONE_NUMBER_ID . '/messages';

    if (!function_exists('curl_init')) {
        wa_api_log('FAILED ' . $template . ': PHP cURL extension is not enabled');
        return false;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 8,
    ]);

    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status < 200 || $status >= 300) {
        wa_api_log('FAILED ' . $template . ' to ' . $to . ' (HTTP ' . $status . ') ' . ($err ?: (string) $body));
        return false;
    }

    wa_api_log('SENT ' . $template . ' to ' . $to);
    return true;
}


/* Load what the messages need about an order */
function wa_order_info(mysqli $conn, int $orderId): ?array
{
    $st = mysqli_prepare(
        $conn,
        'SELECT id, total_amount, shipping_address, phone, status FROM orders WHERE id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($st, 'i', $orderId);
    mysqli_stmt_execute($st);
    $o = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    if (!$o || trim((string) $o['phone']) === '') {
        return null;
    }

    /* checkout stores "name\naddress" */
    $first = trim(explode("\n", (string) $o['shipping_address'])[0]);
    $o['first_name'] = $first !== '' ? explode(' ', $first)[0] : 'Customer';
    $o['ref']        = ref_no((int) $o['id']);
    $o['total']      = money((float) $o['total_amount']);

    return $o;
}


function wa_bank_summary(): string
{
    $parts = [];
    foreach (BANK_ACCOUNTS as $b) {
        $parts[] = $b['bank'] . ' - ' . $b['name'] . ' - ' . $b['number'];
    }
    return implode(' | ', $parts);
}


/* ---- Events (each one wrapped so it can never throw) ---- */

function wa_notify_order_placed(mysqli $conn, int $orderId): void
{
    try {
        $o = wa_order_info($conn, $orderId);
        if ($o) {
            wa_send_template($o['phone'], WA_TPL_ORDER_PLACED, [
                $o['first_name'], '#' . $o['ref'], $o['total'], wa_bank_summary(),
            ]);
        }
    } catch (Throwable $e) {
        wa_api_log('ERROR order_placed: ' . $e->getMessage());
    }
}

function wa_notify_payment(mysqli $conn, int $orderId, string $paymentStatus): void
{
    try {
        $o = wa_order_info($conn, $orderId);
        if (!$o) {
            return;
        }
        if ($paymentStatus === 'paid') {
            wa_send_template($o['phone'], WA_TPL_PAYMENT_RECEIVED, [$o['first_name'], '#' . $o['ref'], $o['total']]);
        } elseif ($paymentStatus === 'failed') {
            wa_send_template($o['phone'], WA_TPL_PAYMENT_FAILED, [$o['first_name'], '#' . $o['ref']]);
        }
    } catch (Throwable $e) {
        wa_api_log('ERROR payment: ' . $e->getMessage());
    }
}

function wa_notify_order_status(mysqli $conn, int $orderId): void
{
    try {
        $o = wa_order_info($conn, $orderId);
        if ($o) {
            wa_send_template($o['phone'], WA_TPL_ORDER_STATUS, [
                $o['first_name'], '#' . $o['ref'], ucfirst($o['status']),
            ]);
        }
    } catch (Throwable $e) {
        wa_api_log('ERROR order_status: ' . $e->getMessage());
    }
}


/* ---------------------------------------------------------
   No Meta API yet? After the admin updates an order, prepare a
   ready WhatsApp link to the CUSTOMER's number. The admin page
   shows it as a button (one tap, then Send).
   --------------------------------------------------------- */
function wa_customer_prompt(mysqli $conn, int $orderId, string $kind): ?array
{
    $o = wa_order_info($conn, $orderId);
    if (!$o) {
        return null;
    }

    $n = $o['first_name'];
    $r = '#' . $o['ref'];

    if ($kind === 'paid') {
        $msg   = "Hi $n, we received your payment of {$o['total']} for order $r. Thank you! We will process your order now. - Web Studio";
        $label = 'Send "Payment received" to customer on WhatsApp';
    } elseif ($kind === 'failed') {
        $msg   = "Hi $n, we could not verify the payment for order $r ({$o['total']}). Please send your bank transfer slip again on this chat. - Web Studio";
        $label = 'Send "Payment not verified" to customer on WhatsApp';
    } else {
        $msg   = "Hi $n, your Web Studio order $r status is now: " . ucfirst($o['status']) . '.';
        $label = 'Send status update to customer on WhatsApp';
    }

    return ['link' => whatsapp_link($msg, $o['phone']), 'label' => $label, 'phone' => $o['phone']];
}
