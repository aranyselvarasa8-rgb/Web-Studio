# Automatic WhatsApp messages to customers (WhatsApp Cloud API)

Messages go to the phone number the customer typed at checkout.

| When | Template |
|---|---|
| Customer places an order | `order_placed` |
| Admin clicks **Mark Paid** | `payment_received` |
| Admin clicks **Reject** | `payment_failed` |
| Admin changes order status (shipped, completed, cancelled...) | `order_status_update` |

## 1. Meta setup (one time)
1. developers.facebook.com -> create an app (type: Business) -> add the **WhatsApp** product.
2. Add and verify the business phone number (the number customers will see messages FROM).
   It must NOT be already registered in the normal WhatsApp / WhatsApp Business app.
3. Copy the **Phone number ID** (not the phone number).
4. Business Settings -> System Users -> create one -> assign the app -> generate a
   **permanent token** with `whatsapp_business_messaging` + `whatsapp_business_management`.
5. Put both values in `includes/config.php` and set `WA_API_ENABLED` to `true`.

## 2. Create these 4 templates (WhatsApp Manager -> Message templates)
Category: **Utility**, Language: **English** (code `en`). Names must match exactly.

**order_placed**
```
Hi {{1}}, thank you for your order {{2}} at Web Studio. Total: {{3}}. You can pay from any bank. Bank details: {{4}}. Please send your payment slip on this chat quoting the order number.
```
**payment_received**
```
Hi {{1}}, we received your payment of {{3}} for order {{2}}. Thank you! - Web Studio
```
**payment_failed**
```
Hi {{1}}, we could not verify the payment for order {{2}}. Please send your bank transfer slip again on this chat. - Web Studio
```
**order_status_update**
```
Hi {{1}}, your Web Studio order {{2}} status is now: {{3}}.
```
Templates are usually approved in minutes to a few hours. Until approved, sending fails (see log).

## 3. Rules to know
- Needs a live server with internet and PHP **cURL** enabled (XAMPP: enable `extension=curl` in php.ini).
- Every send result is written to `logs/whatsapp.log` (SENT / FAILED with Meta's error).
- While the app is in Development mode, you can only message numbers added as test recipients.
  Switch the app to Live to message any customer.
- Meta charges per conversation/template message (utility rate); check current Meta pricing.
- If a customer's number is not on WhatsApp, the send simply fails and is logged. Orders are never blocked.
- Keep the manual "WhatsApp customer" button in admin as a backup.
