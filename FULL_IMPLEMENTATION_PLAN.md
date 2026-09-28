# Web Studio – Full PHP + MySQL Implementation Plan

## Development order
1. Database foundation
2. Customer register/login/logout/profile
3. Services management
4. Packages management
5. Booking system
6. Availability calendar
7. My Bookings
8. Admin booking management
9. Payments
10. Products
11. Cart
12. Orders
13. Wishlist
14. Reviews & ratings
15. Notifications
16. Coupons
17. Gallery management
18. Contact messages
19. Admin dashboard
20. Reports
21. Security hardening
22. Testing and deployment

## 1. Database
Update `database/web_studio.sql` with these tables:
- users: id, name, email, phone, password, profile_image, status, created_at
- admins: id, name, email, password, status, created_at
- services: id, name, description, price, duration, image, status, created_at
- packages: id, name, description, price, image, featured, status, created_at
- package_items: id, package_id, item_name
- bookings: id, user_id, name, phone, email, event_type, service_id, package_id, event_date, start_time, end_time, location, message, status, created_at
- booking_services: id, booking_id, service_id, price
- payments: id, booking_id, user_id, amount, payment_method, transaction_id, status, paid_at, created_at
- products: id, name, price, stock, image, description, category, status, created_at
- cart: id, user_id, created_at, updated_at
- cart_items: id, cart_id, product_id, quantity, price
- orders: id, user_id, total_amount, payment_status, order_status, shipping_address, phone, created_at
- order_items: id, order_id, product_id, product_name, price, quantity, subtotal
- reviews: id, user_id, service_id, package_id, product_id, rating, review, status, created_at
- wishlists: id, user_id, product_id, created_at
- coupons: id, code, discount_type, discount_value, minimum_amount, max_discount, start_date, expiry_date, usage_limit, used_count, status
- notifications: id, user_id, title, message, type, is_read, created_at
- gallery: id, title, category, image, description, status, created_at
- contact_messages: id, name, email, phone, subject, message, status, created_at

Add foreign keys and indexes after the tables are created. Keep passwords hashed with `password_hash()`.

## 2. Authentication
Create:
- `register.php`
- `login.php`
- `logout.php`
- `forgot-password.php`
- `profile.php`
- `includes/auth.php`
- `includes/admin-auth.php`

Use `$_SESSION['user_id']` for customers and `$_SESSION['admin_id']` for admins. Replace the current hardcoded admin credentials with the `admins` table.

## 3. Shared PHP helpers
Extend `includes/config.php` and create `includes/functions.php` for:
- database connection
- escaping output with `htmlspecialchars`
- login checks
- admin checks
- CSRF token creation/validation
- flash messages
- booking availability checks
- cart totals
- coupon validation
- notification creation

Use prepared statements for all SQL containing user input.

## 4. Header/navigation
Update `includes/header.php`:
- Login/Register when logged out
- Profile/My Bookings/My Orders/Logout when logged in
- Cart count
- Wishlist link
- Notifications count
- Keep Book Now button

## 5. Services
Customer: `services.php` reads from `services` table.
Admin: create `admin/services.php` with add/edit/delete/enable/disable.

## 6. Packages
Customer: `packages.php` reads from `packages` and `package_items`.
Admin: create `admin/packages.php`.

## 7. Booking
Upgrade `booking.php` to require login and save `user_id`, service/package, date, start/end time, location and message.
Create:
- `booking.php`
- `booking-details.php`
- `my-bookings.php`
- `api/check-availability.php`

Statuses: pending, confirmed, completed, cancelled.

Prevent double booking with a server-side availability query before inserting.

## 8. Calendar
Create `calendar.php` and `admin/calendar.php`.
Show available/booked dates and time slots. Do not rely only on JavaScript; always verify availability on the server.

## 9. Admin bookings
Upgrade `admin/bookings.php`:
- list/filter/search
- view details
- confirm
- reject/cancel
- mark completed
- payment status
- customer details

When status changes, create a notification for the customer.

## 10. Payments
Create:
- `payment.php`
- `payment-success.php`
- `payment-failed.php`
- `admin/payments.php`

Store every payment in `payments`. Use a real payment gateway only after its credentials/webhook are configured. Never mark a payment paid only from a client-side success page; verify the gateway response/webhook server-side.

## 11. Products
Upgrade `products.php` to database-driven cards with category, stock, price and Add to Cart.
Admin: `admin/products.php` handles CRUD and stock.

## 12. Cart
Create:
- `cart.php`
- `add-to-cart.php`
- `update-cart.php`
- `remove-cart.php`
- `checkout.php`

Use `cart` and `cart_items`. Recalculate prices from the database at checkout; never trust browser-submitted prices.

## 13. Orders
Create:
- `my-orders.php`
- `order-details.php`
- `admin/orders.php`

Order statuses: pending, confirmed, processing, ready, delivered, cancelled.

## 14. Wishlist
Create `wishlist.php` and `api/wishlist.php`. Store product favourites in `wishlists`.

## 15. Reviews
Create customer review form and `admin/reviews.php`.
Allow reviews only after a completed booking or delivered product order. Admin approval controls publication.

## 16. Coupons
Create `admin/coupons.php` and coupon validation in `checkout.php`/booking payment flow. Validate dates, usage limits and minimum amount server-side.

## 17. Notifications
Create `notifications.php` and `api/notifications.php`. Trigger notifications for booking confirmation, payment result, order status and promotions.

## 18. Gallery
Upgrade `gallery.php` to database-driven gallery. Create `admin/gallery.php` for image upload/delete/publish. Validate file type, extension, MIME type and file size; generate safe filenames.

## 19. Contact messages
Upgrade `contact.php` to save messages. Create `admin/messages.php` to read and mark messages handled.

## 20. Admin dashboard
Upgrade `admin/index.php` with:
- customers
- bookings
- pending bookings
- orders
- revenue
- today's bookings
- low-stock products
- recent orders/customers

## 21. Reports
Create `admin/reports.php` with daily/monthly/yearly booking and sales totals, best services/packages/products, and export options.

## 22. Invoice
Create `invoice.php` and generate a printable invoice after a verified payment/order.

## 23. WhatsApp
Keep the existing WhatsApp feature but generate booking/order messages dynamically from the database. Replace the placeholder number in `includes/config.php` with the real business number.

## 24. Security checklist
- password_hash/password_verify
- prepared SQL statements
- CSRF tokens on POST forms
- session_regenerate_id after login
- authorization checks on every admin/customer page
- HTML escaping
- upload MIME/size validation
- rate limiting for login
- no hardcoded admin password
- never trust client-side price/payment status
- production errors disabled, server logs enabled

## 25. Testing order
Test after every phase:
1. Register/login/logout
2. Service/package CRUD
3. Booking insert
4. Duplicate booking prevention
5. Booking status changes
6. Payment success/failure/webhook
7. Cart calculations
8. Order creation/stock reduction
9. Wishlist
10. Review permissions
11. Coupon calculations
12. Notifications
13. Gallery upload
14. Admin authorization
15. Mobile/responsive pages

## Final target structure
```
web-studio/
├── index.php
├── about.php
├── services.php
├── packages.php
├── gallery.php
├── products.php
├── booking.php
├── calendar.php
├── login.php
├── register.php
├── profile.php
├── my-bookings.php
├── my-orders.php
├── wishlist.php
├── cart.php
├── checkout.php
├── contact.php
├── invoice.php
├── api/
├── admin/
├── auth/
├── includes/
├── assets/
└── database/
```

## Important
Implement the phases in order. Do not replace the entire project at once. Take a backup of the working ZIP/database before each major phase.
