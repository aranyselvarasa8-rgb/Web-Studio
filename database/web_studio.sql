CREATE DATABASE IF NOT EXISTS web_studio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_studio;

CREATE TABLE IF NOT EXISTS users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 phone VARCHAR(40) DEFAULT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 status ENUM('active','blocked') NOT NULL DEFAULT 'active',
 email_verified TINYINT(1) NOT NULL DEFAULT 0,
 verification_token VARCHAR(64) DEFAULT NULL,
 verification_expires DATETIME DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 status ENUM('active','blocked') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 description TEXT,
 price DECIMAL(10,2) NOT NULL DEFAULT 0,
 duration INT DEFAULT NULL,
 image VARCHAR(255) DEFAULT NULL,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS packages (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 description TEXT,
 price DECIMAL(10,2) NOT NULL DEFAULT 0,
 image VARCHAR(255) DEFAULT NULL,
 featured TINYINT(1) NOT NULL DEFAULT 0,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS package_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 package_id INT NOT NULL,
 service_id INT DEFAULT NULL,
 item_name VARCHAR(180) NOT NULL,
 quantity INT NOT NULL DEFAULT 1,
 FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
 FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 price DECIMAL(10,2) NOT NULL DEFAULT 0,
 stock INT NOT NULL DEFAULT 0,
 image VARCHAR(255) DEFAULT NULL,
 description TEXT,
 category VARCHAR(100) DEFAULT NULL,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT DEFAULT NULL,
 name VARCHAR(120) NOT NULL,
 phone VARCHAR(40) NOT NULL,
 email VARCHAR(150),
 event_type VARCHAR(120),
 service_id INT DEFAULT NULL,
 package_id INT DEFAULT NULL,
 event_date DATE,
 start_time TIME DEFAULT NULL,
 end_time TIME DEFAULT NULL,
 location VARCHAR(255) DEFAULT NULL,
 message TEXT,
 status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
 total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
 FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL,
 INDEX idx_bookings_date (event_date),
 INDEX idx_bookings_user (user_id),
 INDEX idx_bookings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_services (
 id INT AUTO_INCREMENT PRIMARY KEY,
 booking_id INT NOT NULL,
 service_id INT DEFAULT NULL,
 package_id INT DEFAULT NULL,
 item_name VARCHAR(180) NOT NULL,
 price DECIMAL(10,2) NOT NULL DEFAULT 0,
 FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
 FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
 FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT DEFAULT NULL,
 total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
 payment_method VARCHAR(50) NOT NULL DEFAULT 'Bank Transfer',
 shipping_address TEXT,
 phone VARCHAR(40) DEFAULT NULL,
 status ENUM('pending','confirmed','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT NOT NULL,
 product_id INT DEFAULT NULL,
 product_name VARCHAR(150) NOT NULL,
 price DECIMAL(10,2) NOT NULL DEFAULT 0,
 quantity INT NOT NULL DEFAULT 1,
 FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS payments (
 id INT AUTO_INCREMENT PRIMARY KEY,
 booking_id INT DEFAULT NULL,
 order_id INT DEFAULT NULL,
 user_id INT DEFAULT NULL,
 amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 method VARCHAR(50) DEFAULT NULL,
 transaction_id VARCHAR(150) DEFAULT NULL,
 currency VARCHAR(10) NOT NULL DEFAULT 'LKR',
 gateway_order_id VARCHAR(150) DEFAULT NULL,
 gateway_payment_id VARCHAR(150) DEFAULT NULL,
 gateway_status_code VARCHAR(50) DEFAULT NULL,
 gateway_method VARCHAR(80) DEFAULT NULL,
 gateway_message VARCHAR(255) DEFAULT NULL,
 proof_image VARCHAR(255) DEFAULT NULL,
 status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
 paid_at DATETIME DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
 FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_payments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 cart_id INT NOT NULL,
 product_id INT NOT NULL,
 quantity INT NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_cart_product (cart_id, product_id),
 FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT DEFAULT NULL,
 booking_id INT DEFAULT NULL,
 service_id INT DEFAULT NULL,
 package_id INT DEFAULT NULL,
 product_id INT DEFAULT NULL,
 rating TINYINT NOT NULL,
 title VARCHAR(150) DEFAULT NULL,
 comment TEXT,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
 FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
 FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL,
 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
 CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wishlists (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 product_id INT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_wishlist (user_id, product_id),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
 id INT AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(50) NOT NULL UNIQUE,
 discount_type ENUM('fixed','percent') NOT NULL,
 discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
 min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 max_uses INT DEFAULT NULL,
 used_count INT NOT NULL DEFAULT 0,
 starts_at DATETIME DEFAULT NULL,
 expires_at DATETIME DEFAULT NULL,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_notifications_user (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery (
 id INT AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 image VARCHAR(255) NOT NULL,
 category VARCHAR(100) DEFAULT NULL,
 description TEXT,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL,
 phone VARCHAR(40) DEFAULT NULL,
 subject VARCHAR(180) DEFAULT NULL,
 message TEXT NOT NULL,
 status ENUM('new','read','replied') NOT NULL DEFAULT 'new',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starter products (only inserted when the table is empty)
INSERT INTO products (name,price,stock,image,description)
SELECT '3D Crystal Cutting Frame',7500.00,10,'assets/images/products/3d-crystal-cutting-frame.jpg','Premium 3D crystal photo frame.'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='3D Crystal Cutting Frame');

INSERT INTO products (name,price,stock,image,description)
SELECT '3D Crystal Frame',3999.99,3,'assets/images/products/3d-crystal-frame.jpg','Elegant crystal frame for your favourite photo.'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='3D Crystal Frame');

INSERT INTO products (name,price,stock,image,description)
SELECT 'Marble Frame',5500.00,5,'assets/images/products/marble-frame.jpg','Premium marble photo frame for special memories.'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Marble Frame');

INSERT INTO products (name,price,stock,image,description)
SELECT 'Premium Photo Album',8500.00,8,'assets/images/products/premium-photo-album.jpg','High-quality custom wedding photo album.'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Premium Photo Album');

INSERT INTO products (name,price,stock,image,description)
SELECT 'Custom Photo Calendar',2999.00,10,'assets/images/products/custom-photo-calendar.jpg','Personalized photo calendar for memorable moments.'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Custom Photo Calendar');

INSERT INTO products (name,price,stock,image,description)
SELECT 'Canvas Photo Print',4999.00,10,'assets/images/products/canvas-photo-print.jpg','Premium canvas print for your favourite photograph.'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Canvas Photo Print');

USE web_studio;

-- Step 2 demo services
INSERT INTO services (name, description, price, status)
SELECT 'Wedding Photography', 'Professional wedding photography coverage for your special day.', 45000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Wedding Photography');

INSERT INTO services (name, description, price, status)
SELECT 'Wedding Videography', 'Cinematic wedding video coverage with professional editing.', 55000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Wedding Videography');

INSERT INTO services (name, description, price, status)
SELECT 'Pre-Wedding Photography', 'Creative outdoor or studio pre-wedding photo session.', 25000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Pre-Wedding Photography');

INSERT INTO services (name, description, price, status)
SELECT 'Birthday & Event Photography', 'Professional photography for birthdays and private events.', 18000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Birthday & Event Photography');

INSERT INTO services (name, description, price, status)
SELECT 'Drone Coverage', 'Aerial photography and video coverage for outdoor events.', 20000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Drone Coverage');

-- Step 2 demo packages
INSERT INTO packages (name, description, price, status)
SELECT 'Silver Wedding Package', 'Essential wedding photography package for intimate celebrations.', 85000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name='Silver Wedding Package');

INSERT INTO packages (name, description, price, status)
SELECT 'Gold Wedding Package', 'Complete wedding photo and cinematic video package.', 145000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name='Gold Wedding Package');

INSERT INTO packages (name, description, price, status)
SELECT 'Premium Wedding Package', 'Premium full-day coverage with photography, video and drone.', 225000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name='Premium Wedding Package');

-- Package items
INSERT INTO package_items (package_id, service_id, item_name, quantity)
SELECT p.id, s.id, 'Wedding Photography', 1
FROM packages p JOIN services s ON s.name='Wedding Photography'
WHERE p.name='Silver Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM package_items pi WHERE pi.package_id=p.id AND pi.item_name='Wedding Photography');

INSERT INTO package_items (package_id, service_id, item_name, quantity)
SELECT p.id, s.id, 'Wedding Photography', 1
FROM packages p JOIN services s ON s.name='Wedding Photography'
WHERE p.name='Gold Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM package_items pi WHERE pi.package_id=p.id AND pi.item_name='Wedding Photography');

INSERT INTO package_items (package_id, service_id, item_name, quantity)
SELECT p.id, s.id, 'Wedding Videography', 1
FROM packages p JOIN services s ON s.name='Wedding Videography'
WHERE p.name='Gold Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM package_items pi WHERE pi.package_id=p.id AND pi.item_name='Wedding Videography');

INSERT INTO package_items (package_id, service_id, item_name, quantity)
SELECT p.id, s.id, 'Wedding Photography', 1
FROM packages p JOIN services s ON s.name='Wedding Photography'
WHERE p.name='Premium Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM package_items pi WHERE pi.package_id=p.id AND pi.item_name='Wedding Photography');

INSERT INTO package_items (package_id, service_id, item_name, quantity)
SELECT p.id, s.id, 'Wedding Videography', 1
FROM packages p JOIN services s ON s.name='Wedding Videography'
WHERE p.name='Premium Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM package_items pi WHERE pi.package_id=p.id AND pi.item_name='Wedding Videography');

INSERT INTO package_items (package_id, service_id, item_name, quantity)
SELECT p.id, s.id, 'Drone Coverage', 1
FROM packages p JOIN services s ON s.name='Drone Coverage'
WHERE p.name='Premium Wedding Package'
  AND NOT EXISTS (SELECT 1 FROM package_items pi WHERE pi.package_id=p.id AND pi.item_name='Drone Coverage');

-- ============================================================
-- WEB STUDIO DEFAULT ADMIN
-- Email: admin@webstudio.lk
-- Password: WebStudio@2026
-- Change this password after first login.
-- ============================================================
INSERT INTO admins (name, email, password_hash, status)
SELECT 'Web Studio Admin',
       'admin@webstudio.lk',
       '$2b$12$3zQW7f62FGRS60cbSg8/GeblreIf2NFbtgtzcYXhuHpsS34DvyML.',
       'active'
WHERE NOT EXISTS (
    SELECT 1 FROM admins WHERE email='admin@webstudio.lk'
);

-- If this database was already set up with the old default admin,
-- update that row to the new email + password instead of leaving it behind.
UPDATE admins
SET email = 'admin@webstudio.lk',
    password_hash = '$2b$12$3zQW7f62FGRS60cbSg8/GeblreIf2NFbtgtzcYXhuHpsS34DvyML.'
WHERE email = 'admin@jeevanstudios.lk';
