-- Customer contact table for promotions (separate from orders)
-- Phone number is the primary key (stored as digits only, recommended)

CREATE TABLE IF NOT EXISTS customer_contacts (
  phone VARCHAR(50) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  zipcode VARCHAR(20),
  address TEXT,
  building VARCHAR(255),
  room VARCHAR(50),
  first_seen_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  last_seen_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Helpful index for admin/promotions lookups by name
CREATE INDEX IF NOT EXISTS idx_customer_contacts_name ON customer_contacts (name);
