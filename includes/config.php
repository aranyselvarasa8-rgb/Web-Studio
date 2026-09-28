<?php
// TEMP DEBUG - remove these 2 lines once the issue is found
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'web_studio');

define('SITE_NAME', 'Web Studio');

/* ---- Email verification (Gmail SMTP) ----
   1. Use a Gmail account (create a free one just for this if you like).
   2. Turn on 2-Step Verification on that Gmail account:
      https://myaccount.google.com/security
   3. Create an "App Password": https://myaccount.google.com/apppasswords
      (choose app: Mail, device: Other -> "Web Studio"). Google gives you
      a 16-character password like "abcd efgh ijkl mnop" - paste it below
      WITHOUT spaces.
   4. Never use your normal Gmail login password here - only the App
      Password. Do not commit real credentials to a public git repo. */
define('GMAIL_SMTP_USER', 'aranyselvarasa8@gmail.com');
define('GMAIL_SMTP_APP_PASSWORD', 'hitiryfwqrgrsbet');

/* Base URL of this site, no trailing slash. Used to build the verification
   link that gets emailed to the user (e.g. https://example.com or, for
   local testing, http://localhost/web-studio). */
define('SITE_URL', 'http://localhost/web-studio');
define('WHATSAPP_NUMBER', '94763551264'); // Web Studio WhatsApp (076 355 1264)
define('BANK_NAME', 'Commercial Bank');
define('BANK_ACCOUNT_NAME', 'Web Studio');
define('BANK_ACCOUNT_NO', 'Contact studio for account details');

/* Studio bank accounts shown to customers. Add as many banks as you like.
   The customer picks a bank and sees OUR account in that bank. Other banks use CEFT to the first account. */
const BANK_ACCOUNTS = [
    [
        'bank'   => 'Commercial Bank',
        'name'   => 'Web Studio',
        'number' => 'ENTER ACCOUNT NUMBER',
        'branch' => '',
    ],
    [
        'bank'   => 'Bank of Ceylon',
        'name'   => 'Web Studio',
        'number' => 'ENTER ACCOUNT NUMBER',
        'branch' => '',
    ],
    [
        'bank'   => "People's Bank",
        'name'   => 'Web Studio',
        'number' => 'ENTER ACCOUNT NUMBER',
        'branch' => '',
    ],
    [
        'bank'   => 'Hatton National Bank (HNB)',
        'name'   => 'Web Studio',
        'number' => 'ENTER ACCOUNT NUMBER',
        'branch' => '',
    ],
    [
        'bank'   => 'Sampath Bank',
        'name'   => 'Web Studio',
        'number' => 'ENTER ACCOUNT NUMBER',
        'branch' => '',
    ],
];

/* ---- WhatsApp Cloud API: automatic messages to customers ----
   Set WA_API_ENABLED to true after you fill the 2 values below.
   See WHATSAPP_API_SETUP.md */
define('WA_API_ENABLED', false);
define('WA_ACCESS_TOKEN', '');        // permanent System User token from Meta
define('WA_PHONE_NUMBER_ID', '');     // Phone number ID (NOT the phone number)
define('WA_API_VERSION', 'v21.0');
define('WA_TEMPLATE_LANG', 'en');     // language code you chose for the templates
define('WA_TPL_ORDER_PLACED', 'order_placed');
define('WA_TPL_PAYMENT_RECEIVED', 'payment_received');
define('WA_TPL_PAYMENT_FAILED', 'payment_failed');
define('WA_TPL_ORDER_STATUS', 'order_status_update');

define('ADVANCE_PERCENT', 30);
define('BOOKING_MAX_AMOUNT', 90000);

/* ---- Stripe payment gateway ----
   Add your Stripe Secret Key from the Stripe Dashboard.
   Test key: sk_test_...
   Live key:  sk_live_...
*/
define('STRIPE_SECRET_KEY', 'PUT_YOUR_STRIPE_SECRET_KEY_HERE');

/* All product prices in the database/admin are stored in LKR.
   Set STRIPE_CURRENCY to 'lkr' to charge customers directly in LKR (no conversion needed),
   or 'usd' to charge in US Dollars — in that case the LKR total is converted using
   USD_EXCHANGE_RATE below before it is sent to Stripe. */
define('STRIPE_CURRENCY', 'usd');

/* How many LKR = 1 USD. Update this every so often to match the real market rate
   (check google "1 usd to lkr" or xe.com). As of Sep 2026 the rate is around 336. */
define('USD_EXCHANGE_RATE', 336.00);

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Database connection failed. Please import database/web_studio.sql first.");
}
mysqli_set_charset($conn, 'utf8mb4');
?>






