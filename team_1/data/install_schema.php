<?php
/**
 * Database schema installation script
 * 
 * Creates all required tables for the pizza delivery application:
 * - customer: Customer information
 * - menu: Menu items with S/M/L pricing
 * - orders: Order records with delivery info
 * - order_items: Individual items in each order
 * - store_hours: Store operating hours and shift configuration
 * 
 * Note: DDL statements auto-commit in MySQL, but we stop on first error
 */
require __DIR__ . '/../config/db.php';

// Ensure UTF-8 encoding for Japanese text
$mysqli->set_charset('utf8mb4');

$queries = [];

/* =========================
   1) customer table
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS customer (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  address TEXT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_email (email),
  INDEX idx_customer_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   2) menu table
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
   3) orders table
   Stores order information with delivery details
   delivery_comment stored separately from delivery_address
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  delivery_address TEXT NOT NULL,
  delivery_comment TEXT NULL,
  delivery_time VARCHAR(50) NULL,
  total_price INT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'NEW',
  create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_orders_customer (customer_id),
  INDEX idx_orders_status (status),
  CONSTRAINT fk_orders_customer
    FOREIGN KEY (customer_id) REFERENCES customer(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   4) order_items table
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
   5) store_hours table
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
   Initial Data
   ========================= */

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

echo "\n🎉 Schema ready: customer, menu, orders, order_items, store_hours\n";
echo "</pre>";
