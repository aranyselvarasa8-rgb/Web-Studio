<?php
require_once __DIR__ . '/config.php';

/* Stripe Checkout helpers. Uses Stripe's HTTP API directly, so no Composer package is required. */

function stripe_secret_key(): string {
    $key = defined('STRIPE_SECRET_KEY') ? trim((string) STRIPE_SECRET_KEY) : '';
    if ($key === '' || strpos($key, 'REPLACE_WITH') !== false || strpos($key, 'YOUR_') !== false) {
        throw new RuntimeException('Stripe Secret Key is not configured in includes/config.php.');
    }
    return $key;
}

function stripe_request(string $method, string $path, array $params = []): array {
    $url = 'https://api.stripe.com/v1/' . ltrim($path, '/');
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . stripe_secret_key(),
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    if ($method !== 'GET' && $params) {
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $err !== '') {
        throw new RuntimeException('Stripe connection failed: ' . ($err ?: 'Unknown cURL error'));
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('Stripe returned an invalid response.');
    }
    if ($http < 200 || $http >= 300) {
        $msg = $data['error']['message'] ?? 'Stripe request failed.';
        throw new RuntimeException('Stripe error: ' . $msg);
    }
    return $data;
}

/* Converts an LKR amount (all prices in the DB are LKR) into the smallest unit
   (cents) of whatever STRIPE_CURRENCY is set to. LKR itself has no decimal
   sub-unit issue for Stripe, so it passes through as-is; any other currency
   is converted using USD_EXCHANGE_RATE (LKR per 1 USD). */
function stripe_amount_in_currency(float $amountLkr, string $currency): int {
    $currency = strtolower($currency);

    if ($currency === 'lkr') {
        return (int) round($amountLkr * 100);
    }

    if ($currency === 'usd') {
        $rate = defined('USD_EXCHANGE_RATE') ? (float) USD_EXCHANGE_RATE : 0;
        if ($rate <= 0) {
            throw new RuntimeException('USD_EXCHANGE_RATE is not set correctly in includes/config.php.');
        }
        $usd = $amountLkr / $rate;
        return (int) round($usd * 100);
    }

    /* Unknown currency: fall back to treating the LKR figure as-is so the
       order never silently overcharges/undercharges by a huge factor. */
    return (int) round($amountLkr * 100);
}

function stripe_create_checkout_session(string $type, int $id, float $amountLkr, string $description, string $customerEmail = ''): array {
    if ($amountLkr <= 0) throw new RuntimeException('Invalid payment amount.');
    $currency = STRIPE_CURRENCY;
    $amount = stripe_amount_in_currency($amountLkr, $currency);
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    $origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $success = $origin . $base . '/stripe-success.php?session_id={CHECKOUT_SESSION_ID}&type=' . rawurlencode($type) . '&id=' . $id;
    $cancel = $origin . $base . '/payment.php?' . ($type === 'booking' ? 'booking_id=' : 'id=') . $id;

    $params = [
        'mode' => 'payment',
        'success_url' => $success,
        'cancel_url' => $cancel,
        'line_items[0][price_data][currency]' => strtolower($currency),
        'line_items[0][price_data][product_data][name]' => $description,
        'line_items[0][price_data][unit_amount]' => $amount,
        'line_items[0][quantity]' => 1,
        'metadata[type]' => $type,
        'metadata[id]' => (string)$id,
    ];
    if ($customerEmail !== '') $params['customer_email'] = $customerEmail;
    return stripe_request('POST', 'checkout/sessions', $params);
}

function stripe_get_checkout_session(string $sessionId): array {
    if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) throw new RuntimeException('Invalid Stripe session.');
    return stripe_request('GET', 'checkout/sessions/' . rawurlencode($sessionId));
}
?>
