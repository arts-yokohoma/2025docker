-- PostgreSQL table for menu prices (single-row, id=1)
CREATE TABLE IF NOT EXISTS menu_prices (
  id INTEGER PRIMARY KEY,
  size_s NUMERIC(8,2) NOT NULL,
  size_m NUMERIC(8,2) NOT NULL,
  size_l NUMERIC(8,2) NOT NULL,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
