-- ============================================================
-- WEB STUDIO — SAMPLE / DEMO DATA
-- Run this AFTER web_studio.sql (needs services, packages,
-- products, admins to already exist).
-- Safe to re-run: every insert is guarded with WHERE NOT EXISTS.
--
-- Demo customer login for ALL 5 users below:
--   Password: WebStudio@2026
-- (Same hash as the default admin — change after testing.)
-- ============================================================

USE web_studio;

-- ------------------------------------------------------------
-- 1. Demo customers (users)
-- ------------------------------------------------------------
INSERT INTO users (name, phone, email, password_hash, status)
SELECT 'Nadeesha Perera', '0771234567', 'nadeesha.perera@gmail.com',
       '$2b$12$3zQW7f62FGRS60cbSg8/GeblreIf2NFbtgtzcYXhuHpsS34DvyML.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='nadeesha.perera@gmail.com');

INSERT INTO users (name, phone, email, password_hash, status)
SELECT 'Kasun Fernando', '0712345678', 'kasun.fernando@gmail.com',
       '$2b$12$3zQW7f62FGRS60cbSg8/GeblreIf2NFbtgtzcYXhuHpsS34DvyML.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='kasun.fernando@gmail.com');

INSERT INTO users (name, phone, email, password_hash, status)
SELECT 'Priya Rajendran', '0765432109', 'priya.rajendran@gmail.com',
       '$2b$12$3zQW7f62FGRS60cbSg8/GeblreIf2NFbtgtzcYXhuHpsS34DvyML.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='priya.rajendran@gmail.com');

INSERT INTO users (name, phone, email, password_hash, status)
SELECT 'Ishara Silva', '0778889990', 'ishara.silva@gmail.com',
       '$2b$12$3zQW7f62FGRS60cbSg8/GeblreIf2NFbtgtzcYXhuHpsS34DvyML.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='ishara.silva@gmail.com');

INSERT INTO users (name, phone, email, password_hash, status)
SELECT 'Mohamed Rizwan', '0701122334', 'mohamed.rizwan@gmail.com',
       '$2b$12$3zQW7f62FGRS60cbSg8/GeblreIf2NFbtgtzcYXhuHpsS34DvyML.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='mohamed.rizwan@gmail.com');

-- ------------------------------------------------------------
-- 2. Bookings (one per demo customer)
-- ------------------------------------------------------------
INSERT INTO bookings (user_id, name, phone, email, event_type, service_id, package_id, event_date, location, message, status, total_amount)
SELECT u.id, u.name, u.phone, u.email, 'Wedding Photography', s.id, NULL,
       DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'Colombo', 'Full day wedding coverage please.', 'confirmed', 45000.00
FROM users u JOIN services s ON s.name='Wedding Photography'
WHERE u.email='nadeesha.perera@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE email='nadeesha.perera@gmail.com' AND event_type='Wedding Photography');

INSERT INTO bookings (user_id, name, phone, email, event_type, service_id, package_id, event_date, location, message, status, total_amount)
SELECT u.id, u.name, u.phone, u.email, 'Gold Wedding Package', NULL, p.id,
       DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'Kandy', 'Need drone shots if possible.', 'confirmed', 145000.00
FROM users u JOIN packages p ON p.name='Gold Wedding Package'
WHERE u.email='kasun.fernando@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE email='kasun.fernando@gmail.com' AND event_type='Gold Wedding Package');

INSERT INTO bookings (user_id, name, phone, email, event_type, service_id, package_id, event_date, location, message, status, total_amount)
SELECT u.id, u.name, u.phone, u.email, 'Birthday & Event Photography', s.id, NULL,
       DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Jaffna', '1st birthday party, 2 hours.', 'pending', 18000.00
FROM users u JOIN services s ON s.name='Birthday & Event Photography'
WHERE u.email='priya.rajendran@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE email='priya.rajendran@gmail.com' AND event_type='Birthday & Event Photography');

INSERT INTO bookings (user_id, name, phone, email, event_type, service_id, package_id, event_date, location, message, status, total_amount)
SELECT u.id, u.name, u.phone, u.email, 'Pre-Wedding Photography', s.id, NULL,
       DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Galle', 'Beach location pre-shoot.', 'completed', 25000.00
FROM users u JOIN services s ON s.name='Pre-Wedding Photography'
WHERE u.email='ishara.silva@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE email='ishara.silva@gmail.com' AND event_type='Pre-Wedding Photography');

INSERT INTO bookings (user_id, name, phone, email, event_type, service_id, package_id, event_date, location, message, status, total_amount)
SELECT u.id, u.name, u.phone, u.email, 'Premium Wedding Package', NULL, p.id,
       DATE_ADD(CURDATE(), INTERVAL 50 DAY), 'Negombo', 'Full package with drone + video.', 'confirmed', 225000.00
FROM users u JOIN packages p ON p.name='Premium Wedding Package'
WHERE u.email='mohamed.rizwan@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE email='mohamed.rizwan@gmail.com' AND event_type='Premium Wedding Package');

-- ------------------------------------------------------------
-- 3. Booking line items
-- ------------------------------------------------------------
INSERT INTO booking_services (booking_id, service_id, item_name, price)
SELECT b.id, s.id, 'Wedding Photography', 45000.00
FROM bookings b JOIN services s ON s.name='Wedding Photography'
WHERE b.email='nadeesha.perera@gmail.com' AND b.event_type='Wedding Photography'
  AND NOT EXISTS (SELECT 1 FROM booking_services bs WHERE bs.booking_id=b.id);

INSERT INTO booking_services (booking_id, package_id, item_name, price)
SELECT b.id, p.id, 'Gold Wedding Package', 145000.00
FROM bookings b JOIN packages p ON p.name='Gold Wedding Package'
WHERE b.email='kasun.fernando@gmail.com' AND b.event_type='Gold Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM booking_services bs WHERE bs.booking_id=b.id);

INSERT INTO booking_services (booking_id, service_id, item_name, price)
SELECT b.id, s.id, 'Birthday & Event Photography', 18000.00
FROM bookings b JOIN services s ON s.name='Birthday & Event Photography'
WHERE b.email='priya.rajendran@gmail.com' AND b.event_type='Birthday & Event Photography'
  AND NOT EXISTS (SELECT 1 FROM booking_services bs WHERE bs.booking_id=b.id);

INSERT INTO booking_services (booking_id, service_id, item_name, price)
SELECT b.id, s.id, 'Pre-Wedding Photography', 25000.00
FROM bookings b JOIN services s ON s.name='Pre-Wedding Photography'
WHERE b.email='ishara.silva@gmail.com' AND b.event_type='Pre-Wedding Photography'
  AND NOT EXISTS (SELECT 1 FROM booking_services bs WHERE bs.booking_id=b.id);

INSERT INTO booking_services (booking_id, package_id, item_name, price)
SELECT b.id, p.id, 'Premium Wedding Package', 225000.00
FROM bookings b JOIN packages p ON p.name='Premium Wedding Package'
WHERE b.email='mohamed.rizwan@gmail.com' AND b.event_type='Premium Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM booking_services bs WHERE bs.booking_id=b.id);

-- ------------------------------------------------------------
-- 4. Advance payments (30%) for the confirmed / completed bookings
-- ------------------------------------------------------------
INSERT INTO payments (booking_id, user_id, amount, method, transaction_id, status, paid_at)
SELECT b.id, u.id, 13500.00, 'Bank Transfer', 'TXN-NP-10001', 'paid', NOW()
FROM bookings b JOIN users u ON u.email='nadeesha.perera@gmail.com'
WHERE b.email='nadeesha.perera@gmail.com' AND b.event_type='Wedding Photography'
  AND NOT EXISTS (SELECT 1 FROM payments WHERE booking_id=b.id);

INSERT INTO payments (booking_id, user_id, amount, method, transaction_id, status)
SELECT b.id, u.id, 43500.00, 'Bank Transfer', 'TXN-KF-10002', 'pending'
FROM bookings b JOIN users u ON u.email='kasun.fernando@gmail.com'
WHERE b.email='kasun.fernando@gmail.com' AND b.event_type='Gold Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM payments WHERE booking_id=b.id);

INSERT INTO payments (booking_id, user_id, amount, method, transaction_id, status, paid_at)
SELECT b.id, u.id, 7500.00, 'Bank Transfer', 'TXN-IS-10003', 'paid', NOW()
FROM bookings b JOIN users u ON u.email='ishara.silva@gmail.com'
WHERE b.email='ishara.silva@gmail.com' AND b.event_type='Pre-Wedding Photography'
  AND NOT EXISTS (SELECT 1 FROM payments WHERE booking_id=b.id);

INSERT INTO payments (booking_id, user_id, amount, method, transaction_id, status, paid_at)
SELECT b.id, u.id, 67500.00, 'Bank Transfer', 'TXN-MR-10004', 'paid', NOW()
FROM bookings b JOIN users u ON u.email='mohamed.rizwan@gmail.com'
WHERE b.email='mohamed.rizwan@gmail.com' AND b.event_type='Premium Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM payments WHERE booking_id=b.id);

-- ------------------------------------------------------------
-- 5. Product orders
-- ------------------------------------------------------------
INSERT INTO orders (user_id, total_amount, payment_status, shipping_address, phone, status)
SELECT u.id, 5500.00, 'paid', '45, Galle Road, Colombo 03', u.phone, 'completed'
FROM users u WHERE u.email='nadeesha.perera@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM orders o JOIN users uu ON uu.id=o.user_id WHERE uu.email='nadeesha.perera@gmail.com');

INSERT INTO orders (user_id, total_amount, payment_status, shipping_address, phone, status)
SELECT u.id, 8500.00, 'pending', '12, Peradeniya Road, Kandy', u.phone, 'pending'
FROM users u WHERE u.email='kasun.fernando@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM orders o JOIN users uu ON uu.id=o.user_id WHERE uu.email='kasun.fernando@gmail.com');

INSERT INTO orders (user_id, total_amount, payment_status, shipping_address, phone, status)
SELECT u.id, 3999.99, 'paid', '7, Hospital Road, Jaffna', u.phone, 'shipped'
FROM users u WHERE u.email='priya.rajendran@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM orders o JOIN users uu ON uu.id=o.user_id WHERE uu.email='priya.rajendran@gmail.com');

INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
SELECT o.id, pr.id, 'Marble Frame', 5500.00, 1
FROM orders o
JOIN users u ON u.id=o.user_id AND u.email='nadeesha.perera@gmail.com'
JOIN products pr ON pr.name='Marble Frame'
WHERE NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id=o.id);

INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
SELECT o.id, pr.id, 'Premium Photo Album', 8500.00, 1
FROM orders o
JOIN users u ON u.id=o.user_id AND u.email='kasun.fernando@gmail.com'
JOIN products pr ON pr.name='Premium Photo Album'
WHERE NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id=o.id);

INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
SELECT o.id, pr.id, '3D Crystal Frame', 3999.99, 1
FROM orders o
JOIN users u ON u.id=o.user_id AND u.email='priya.rajendran@gmail.com'
JOIN products pr ON pr.name='3D Crystal Frame'
WHERE NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id=o.id);

-- ------------------------------------------------------------
-- 6. Carts in progress (not checked out yet)
-- ------------------------------------------------------------
INSERT INTO cart (user_id)
SELECT u.id FROM users u WHERE u.email='ishara.silva@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM cart c JOIN users uu ON uu.id=c.user_id WHERE uu.email='ishara.silva@gmail.com');

INSERT INTO cart (user_id)
SELECT u.id FROM users u WHERE u.email='mohamed.rizwan@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM cart c JOIN users uu ON uu.id=c.user_id WHERE uu.email='mohamed.rizwan@gmail.com');

INSERT INTO cart_items (cart_id, product_id, quantity)
SELECT c.id, pr.id, 1
FROM cart c JOIN users u ON u.id=c.user_id AND u.email='ishara.silva@gmail.com'
JOIN products pr ON pr.name='3D Crystal Cutting Frame'
WHERE NOT EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id=c.id AND ci.product_id=pr.id);

INSERT INTO cart_items (cart_id, product_id, quantity)
SELECT c.id, pr.id, 2
FROM cart c JOIN users u ON u.id=c.user_id AND u.email='mohamed.rizwan@gmail.com'
JOIN products pr ON pr.name='Marble Frame'
WHERE NOT EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id=c.id AND ci.product_id=pr.id);

-- ------------------------------------------------------------
-- 7. Reviews
-- ------------------------------------------------------------
INSERT INTO reviews (user_id, booking_id, service_id, rating, title, comment, status)
SELECT u.id, b.id, s.id, 5, 'Amazing wedding coverage!',
       'The team captured every moment beautifully. Highly recommend Web Studio for weddings.', 'approved'
FROM users u
JOIN bookings b ON b.email='nadeesha.perera@gmail.com'
JOIN services s ON s.name='Wedding Photography'
WHERE u.email='nadeesha.perera@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=u.id AND booking_id=b.id);

INSERT INTO reviews (user_id, booking_id, package_id, rating, title, comment, status)
SELECT u.id, b.id, p.id, 4, 'Great value package',
       'Very professional crew, drone shots came out great. A bit late to arrive though.', 'approved'
FROM users u
JOIN bookings b ON b.email='mohamed.rizwan@gmail.com'
JOIN packages p ON p.name='Premium Wedding Package'
WHERE u.email='mohamed.rizwan@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=u.id AND booking_id=b.id);

INSERT INTO reviews (user_id, product_id, rating, title, comment, status)
SELECT u.id, pr.id, 5, 'Beautiful frame',
       'The marble frame quality is excellent, arrived well packaged.', 'approved'
FROM users u JOIN products pr ON pr.name='Marble Frame'
WHERE u.email='nadeesha.perera@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=u.id AND product_id=pr.id);

INSERT INTO reviews (user_id, service_id, rating, title, comment, status)
SELECT u.id, s.id, 5, 'Lovely pre-wedding shoot',
       'Loved the beach location suggestions, photos turned out stunning.', 'pending'
FROM users u JOIN services s ON s.name='Pre-Wedding Photography'
WHERE u.email='ishara.silva@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM reviews WHERE user_id=u.id AND service_id=s.id);

-- ------------------------------------------------------------
-- 8. Wishlists
-- ------------------------------------------------------------
INSERT INTO wishlists (user_id, product_id)
SELECT u.id, pr.id FROM users u JOIN products pr ON pr.name='Premium Photo Album'
WHERE u.email='ishara.silva@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM wishlists WHERE user_id=u.id AND product_id=pr.id);

INSERT INTO wishlists (user_id, product_id)
SELECT u.id, pr.id FROM users u JOIN products pr ON pr.name='3D Crystal Frame'
WHERE u.email='priya.rajendran@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM wishlists WHERE user_id=u.id AND product_id=pr.id);

INSERT INTO wishlists (user_id, product_id)
SELECT u.id, pr.id FROM users u JOIN products pr ON pr.name='Marble Frame'
WHERE u.email='kasun.fernando@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM wishlists WHERE user_id=u.id AND product_id=pr.id);

-- ------------------------------------------------------------
-- 9. Coupons
-- ------------------------------------------------------------
INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_uses, starts_at, expires_at, status)
SELECT 'WELCOME10', 'percent', 10.00, 5000.00, 100, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'active'
WHERE NOT EXISTS (SELECT 1 FROM coupons WHERE code='WELCOME10');

INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_uses, starts_at, expires_at, status)
SELECT 'FESTIVE5000', 'fixed', 5000.00, 50000.00, 50, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active'
WHERE NOT EXISTS (SELECT 1 FROM coupons WHERE code='FESTIVE5000');

-- ------------------------------------------------------------
-- 10. Notifications
-- ------------------------------------------------------------
INSERT INTO notifications (user_id, title, message, is_read)
SELECT u.id, 'Booking Confirmed', 'Your Wedding Photography booking has been confirmed by the studio.', 0
FROM users u WHERE u.email='nadeesha.perera@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM notifications WHERE user_id=u.id AND title='Booking Confirmed');

INSERT INTO notifications (user_id, title, message, is_read)
SELECT u.id, 'Payment Received', 'We have received your advance payment. Thank you!', 1
FROM users u WHERE u.email='ishara.silva@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM notifications WHERE user_id=u.id AND title='Payment Received');

INSERT INTO notifications (user_id, title, message, is_read)
SELECT u.id, 'Order Shipped', 'Your order has been shipped and is on the way.', 0
FROM users u WHERE u.email='priya.rajendran@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM notifications WHERE user_id=u.id AND title='Order Shipped');

-- ------------------------------------------------------------
-- 11. Gallery (reuses existing sample images already in assets/)
-- ------------------------------------------------------------
INSERT INTO gallery (title, image, category, description, status)
SELECT 'Studio Hero Shot', 'assets/images/hero-studio.png', 'Studio', 'Our main studio setup.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM gallery WHERE title='Studio Hero Shot');

INSERT INTO gallery (title, image, category, description, status)
SELECT 'Marble Frame Product', 'assets/images/products/marble-frame.jpg', 'Products', 'Marble photo frame close-up.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM gallery WHERE title='Marble Frame Product');

INSERT INTO gallery (title, image, category, description, status)
SELECT 'Premium Album Detail', 'assets/images/products/premium-photo-album.jpg', 'Products', 'Premium wedding album pages.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM gallery WHERE title='Premium Album Detail');

INSERT INTO gallery (title, image, category, description, status)
SELECT '3D Crystal Frame', 'assets/images/products/3d-crystal-frame.jpg', 'Products', '3D crystal cut photo frame.', 'active'
WHERE NOT EXISTS (SELECT 1 FROM gallery WHERE title='3D Crystal Frame');

-- ------------------------------------------------------------
-- 12. Contact messages
-- ------------------------------------------------------------
INSERT INTO contact_messages (name, email, phone, subject, message, status)
SELECT 'Sahan Wickramasinghe', 'sahan.w@gmail.com', '0761112223', 'Pricing enquiry',
       'Hi, could you send me pricing for a birthday shoot in Colombo?', 'new'
WHERE NOT EXISTS (SELECT 1 FROM contact_messages WHERE email='sahan.w@gmail.com');

INSERT INTO contact_messages (name, email, phone, subject, message, status)
SELECT 'Dilani Jayasuriya', 'dilani.j@gmail.com', '0754445556', 'Availability check',
       'Are you available for a wedding shoot on the 25th next month?', 'read'
WHERE NOT EXISTS (SELECT 1 FROM contact_messages WHERE email='dilani.j@gmail.com');

INSERT INTO contact_messages (name, email, phone, subject, message, status)
SELECT 'Roshan Kumar', 'roshan.kumar@gmail.com', '0787778889', 'Product question',
       'Do you deliver photo frames outside Colombo?', 'replied'
WHERE NOT EXISTS (SELECT 1 FROM contact_messages WHERE email='roshan.kumar@gmail.com');
