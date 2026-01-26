-- PostgreSQL table for pizza orders

CREATE TABLE IF NOT EXISTS orders (
  order_number VARCHAR(6) PRIMARY KEY,

  -- Reservation / time slot selected in time.php
  time_slot VARCHAR(50),

  -- Menu quantities
  qty_s INTEGER NOT NULL DEFAULT 0,
  qty_m INTEGER NOT NULL DEFAULT 0,
  qty_l INTEGER NOT NULL DEFAULT 0,

  -- Total amount in yen
  total_yen INTEGER NOT NULL DEFAULT 0,

  -- Delivery info
  customer_name VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(50) NOT NULL,
  zipcode VARCHAR(10) NOT NULL,
  address TEXT NOT NULL,
  building VARCHAR(255),
  room VARCHAR(50),

  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE orders
  DROP CONSTRAINT IF EXISTS chk_orders_order_number_format;

ALTER TABLE orders
  ADD CONSTRAINT chk_orders_order_number_format
  CHECK (order_number ~ '^[A-Z]{3}[0-9]{3}$') NOT VALID;

CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_orders_phone ON orders(customer_phone);
