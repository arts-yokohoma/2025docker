<?php
// app/create_schema.php
require __DIR__ . '/../config/db.php';

// Safety: ensure InnoDB + utf8mb4
$mysqli->set_charset('utf8mb4');

// Run in a transaction-like way (DDL auto-commits in MySQL, но мы хотя бы остановимся на ошибке)
$queries = [];

/* =========================
   1) customer
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
   2) menu (A: price_s/m/l)
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
  create_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  update_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_menu_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

/* =========================
   3) orders
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  delivery_address TEXT NOT NULL,
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
   4) order_items
   - добавил size, потому что у тебя S/M/L
   - price = unit price at order time
   ========================= */
$queries[] = "
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_id INT NOT NULL,
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
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
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

echo "\n🎉 Schema ready: customer, menu, orders, order_items\n";
echo "</pre>";
