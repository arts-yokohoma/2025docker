<?php
/**
 * Database schema installation script
 * 
 * Creates all required tables for the pizza delivery application:
 * - roles: User roles (admin, user, moderator, guest)
 * - users: User accounts with authentication
 * - customer: Customer information with privacy consent tracking
 * - menu: Menu items with S/M/L pricing
 * - orders: Order records with delivery info (DATETIME format)
 * - order_items: Individual items in each order
 * - store_hours: Store operating hours and shift configuration
 * 
 * Version: 3.0
 * Changes:
 * - Added roles table with 4 predefined roles
 * - Added users table with authentication (username, email, password, role_id)
 * - Added automatic timestamp management (created_at, updated_at)
 */
require __DIR__ . '/../config/db.php';

// Ensure UTF-8 encoding for Japanese text
$mysqli->set_charset('utf8mb4');

$queries = [];

/* =========================
   1) roles table
   Stores the 4 user roles (admin, user, moderator, guest)
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   2) users table
   Stores user accounts with authentication and role assignment
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_email (email),
  INDEX idx_users_username (username),
  INDEX idx_users_role (role_id),
  CONSTRAINT fk_users_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   3) customer table
   Stores customer information with privacy consent tracking
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS customer (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  address TEXT NOT NULL,
  consent TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Privacy policy consent flag',
  consent_time DATETIME NULL COMMENT 'Time when user gave consent',
  active TINYINT(1) NOT NULL DEFAULT 1,
  create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_email (email),
  INDEX idx_customer_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   4) menu table
   Supports S/M/L pricing - at least one price must be set
   Uses soft deletion (deleted flag) to preserve order history
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS menu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  photo_path VARCHAR(255) NOT NULL,
  description TEXT NULL,
  price_s INT NOT NULL DEFAULT 0,
  price_m INT NOT NULL DEFAULT 0,
  price_l INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_menu_active (active),
  INDEX idx_menu_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   5) orders table
   Stores order information with delivery details
   delivery_comment stored separately from delivery_address
   delivery_time is DATETIME for proper date/time handling
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  delivery_address TEXT NOT NULL,
  delivery_comment TEXT NULL,
  delivery_time DATETIME NULL COMMENT 'Scheduled delivery date and time',
  total_price INT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'NEW',
  create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_orders_customer (customer_id),
  INDEX idx_orders_status (status),
  INDEX idx_orders_delivery_time (delivery_time),
  CONSTRAINT fk_orders_customer
    FOREIGN KEY (customer_id) REFERENCES customer(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   6) order_items table
   Stores individual items in each order
   - size: S/M/L (defaults to M)
   - price: unit price at time of order (price snapshot)
   - menu_id is nullable with SET NULL on delete (preserves order history)
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_id INT NULL,
  size VARCHAR(2) NOT NULL DEFAULT 'M',
  quantity INT NOT NULL,
  price INT NOT NULL,
  create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_items_order (order_id),
  INDEX idx_items_menu (menu_id),
  CONSTRAINT fk_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_items_menu
    FOREIGN KEY (menu_id) REFERENCES menu(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   7) store_hours table
   Store operating hours and shift configuration
   Single record with id=1 (singleton pattern)
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS store_hours (
  id INT NOT NULL PRIMARY KEY,
  open_time TIME NULL,
  close_time TIME NULL,
  last_order_offset_min INT NOT NULL DEFAULT 30,
  early_shift_start TIME NULL,
  early_shift_end TIME NULL,
  late_shift_start TIME NULL,
  late_shift_end TIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   8) shifts table
   Weekly shift assignments: user_id per day, time_slot, role (kitchen/delivery)
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  day_of_week TINYINT NOT NULL COMMENT '0=Mon, 6=Sun',
  time_slot VARCHAR(20) NOT NULL COMMENT 'e.g. 9:00-15:00, 15:00-23:00',
  role VARCHAR(30) NOT NULL COMMENT 'キッチン or ドライバー',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shift_slot (day_of_week, time_slot, role),
  INDEX idx_shifts_user (user_id),
  CONSTRAINT fk_shifts_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   Initial Data
   ========================= */

// Insert the 4 roles
$queries[] = "
INSERT IGNORE INTO roles (name) VALUES 
  ('admin'),
  ('manager'),
  ('kitchen'),
  ('driver')
";

// Insert default store hours if not exists
$queries[] = "
INSERT IGNORE INTO store_hours 
  (id, open_time, close_time, last_order_offset_min, active)
VALUES 
  (1, '11:00:00', '22:00:00', 30, 1)
";

/* =========================
   Execute
   ========================= */
echo "<pre>";

foreach ($queries as $i => $sql) {
    $ok = $mysqli->query($sql);
    if (!$ok) {
        echo "❌ Failed on query #" . ($i + 1) . "\n";
        echo $mysqli->error . "\n\n";
        echo "SQL:\n" . $sql . "\n";
        exit;
    }
    echo "✅ OK query #" . ($i + 1) . "\n";
}

echo "\n🎉 Schema ready: roles, users, customer, menu, orders, order_items, store_hours\n";
echo "\nSchema version: 3.0\n";
echo "Features:\n";
echo "  ✅ User authentication (users table with roles)\n";
echo "  ✅ 4 predefined roles (admin, user, moderator, guest)\n";
echo "  ✅ Privacy consent tracking\n";
echo "  ✅ DATETIME format for delivery_time\n";
echo "</pre>";
